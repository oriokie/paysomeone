<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaypalDriver
{
    public function __construct(private readonly CheckoutService $checkout) {}

    /**
     * @return array{ok: bool, message: string, redirect: ?string}
     */
    public function start(Customer $customer, Order $order): array
    {
        if ($reason = $this->checkout->providerBlocked()) {
            return ['ok' => false, 'message' => $reason, 'redirect' => null];
        }

        $amount = round(max(0, (float) $order->total - (float) $order->total_paid), 2);
        $intent = $this->checkout->intent($customer, 'paypal', 'order', $amount, $order);
        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = Http::withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
            ->asForm()
            ->post($base.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->json('access_token');

        if (! $token) {
            return ['ok' => false, 'message' => 'PayPal did not accept the sandbox login.', 'redirect' => null];
        }

        $response = Http::withToken($token)->post($base.'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $intent->reference,
                'custom_id' => $intent->reference,
                'amount' => ['currency_code' => 'USD', 'value' => number_format($amount, 2, '.', '')],
            ]],
            'application_context' => [
                'return_url' => route('desk.order', $order),
                'cancel_url' => route('desk.order', $order),
            ],
        ]);

        $approve = collect($response->json('links', []))->firstWhere('rel', 'approve')['href'] ?? null;
        if (! $approve) {
            return ['ok' => false, 'message' => 'PayPal did not return a checkout link.', 'redirect' => null];
        }

        return ['ok' => true, 'message' => 'Continue to PayPal.', 'redirect' => $approve];
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $payload = $request->getContent();
        $secret = (string) config('services.paypal.webhook_secret');
        $provided = (string) $request->header('X-PayPal-Signature', '');
        if (! Signatures::hmac($payload, $provided, $secret)) {
            return response('Invalid signature', 400);
        }

        if (config('legacy.safe_mode') || config('legacy.read_only')) {
            return response('Paused', 200);
        }

        $event = json_decode($payload, true);
        $type = $event['event_type'] ?? '';
        if (! in_array($type, ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED'], true)) {
            return response('Ignored', 200);
        }

        $resource = $event['resource'] ?? [];
        $reference = (string) ($resource['custom_id'] ?? $resource['purchase_units'][0]['custom_id'] ?? $resource['supplementary_data']['related_ids']['order_id'] ?? '');
        $transaction = (string) ($resource['id'] ?? '');
        $amount = isset($resource['amount']['value']) ? (float) $resource['amount']['value'] : null;
        if ($reference !== '' && $transaction !== '') {
            $this->checkout->settle($reference, $transaction, $amount);
        }

        return response('OK', 200);
    }
}
