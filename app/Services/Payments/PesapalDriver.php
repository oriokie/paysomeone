<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PesapalDriver
{
    public function __construct(private readonly CheckoutService $checkout) {}

    /**
     * @return array{ok: bool, message: string, redirect: ?string}
     */
    public function start(Customer $customer, Order $order): array
    {
        if ($reason = $this->checkout->providerBlocked() ?? $this->checkout->gatewayClosed('pesapal')) {
            return ['ok' => false, 'message' => $reason, 'redirect' => null];
        }

        $amount = round(max(0, (float) $order->total - (float) $order->total_paid), 2);
        $intent = $this->checkout->intent($customer, 'pesapal', 'order', $amount, $order);
        $base = config('services.pesapal.env') === 'live'
            ? 'https://pay.pesapal.com/v3/api'
            : 'https://cybqa.pesapal.com/pesapalv3/api';

        $token = Http::post($base.'/Auth/RequestToken', [
            'consumer_key' => config('services.pesapal.consumer_key'),
            'consumer_secret' => config('services.pesapal.consumer_secret'),
        ])->json('token');

        if (! $token) {
            return ['ok' => false, 'message' => 'Pesapal did not accept the sandbox login.', 'redirect' => null];
        }

        $response = Http::withToken($token)->post($base.'/Transactions/SubmitOrderRequest', [
            'id' => $intent->reference,
            'currency' => 'USD',
            'amount' => $amount,
            'description' => 'Order '.$order->order_id,
            'callback_url' => route('desk.order', $order),
            'notification_id' => config('services.pesapal.ipn_id'),
            'billing_address' => [
                'email_address' => $customer->email,
                'first_name' => $customer->name,
            ],
        ]);

        $url = $response->json('redirect_url');
        if (! $url) {
            return ['ok' => false, 'message' => 'Pesapal did not return a checkout link.', 'redirect' => null];
        }

        return ['ok' => true, 'message' => 'Continue to Pesapal.', 'redirect' => $url];
    }

    public function ipn(Request $request): \Illuminate\Http\Response
    {
        $tracking = (string) $request->input('OrderTrackingId', $request->input('pesapal_transaction_tracking_id', ''));
        $reference = (string) $request->input('OrderMerchantReference', $request->input('pesapal_merchant_reference', ''));
        if ($tracking === '' || $reference === '') {
            return response('Missing reference', 400);
        }

        if (config('legacy.safe_mode') || config('legacy.read_only')) {
            return response('Paused', 200);
        }

        $status = $this->status($tracking);
        if ($status === 'COMPLETED') {
            $this->checkout->settle($reference, $tracking);
        } elseif (in_array($status, ['FAILED', 'INVALID', 'REVERSED'], true)) {
            $this->checkout->fail($reference, 'Pesapal reported '.$status.'.');
        }

        return response('OK', 200);
    }

    private function status(string $tracking): string
    {
        $base = config('services.pesapal.env') === 'live'
            ? 'https://pay.pesapal.com/v3/api'
            : 'https://cybqa.pesapal.com/pesapalv3/api';
        $token = Http::post($base.'/Auth/RequestToken', [
            'consumer_key' => config('services.pesapal.consumer_key'),
            'consumer_secret' => config('services.pesapal.consumer_secret'),
        ])->json('token');
        if (! $token) {
            return '';
        }

        return strtoupper((string) Http::withToken($token)->get($base.'/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $tracking,
        ])->json('payment_status_description'));
    }
}
