<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MpesaDriver
{
    public function __construct(private readonly CheckoutService $checkout) {}

    /**
     * @return array{ok: bool, message: string, reference: ?string}
     */
    public function push(Customer $customer, Order $order, string $phone): array
    {
        if ($reason = $this->checkout->providerBlocked()) {
            return ['ok' => false, 'message' => $reason, 'reference' => null];
        }

        $amount = round(max(0, (float) $order->total - (float) $order->total_paid), 2);
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }
        if (! preg_match('/^2547\d{8}$/', $phone)) {
            return ['ok' => false, 'message' => 'Enter a Safaricom number in the form 07… or 2547…', 'reference' => null];
        }

        $intent = $this->checkout->intent($customer, 'mpesa', 'order', $amount, $order);
        $timestamp = now()->format('YmdHis');
        $password = base64_encode(config('services.mpesa.shortcode').config('services.mpesa.passkey').$timestamp);
        $base = config('services.mpesa.env') === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $token = Http::withBasicAuth((string) config('services.mpesa.consumer_key'), (string) config('services.mpesa.consumer_secret'))
            ->acceptJson()
            ->get($base.'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
            ->json('access_token');

        if (! $token) {
            return ['ok' => false, 'message' => 'M-Pesa did not accept the sandbox login. Check the consumer key and secret.', 'reference' => null];
        }

        $callback = url('/payments/mpesa/callback').'?token='.urlencode((string) config('services.mpesa.callback_secret'));
        $response = Http::withToken($token)->acceptJson()->post($base.'/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => config('services.mpesa.shortcode'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) ceil($amount),
            'PartyA' => $phone,
            'PartyB' => config('services.mpesa.shortcode'),
            'PhoneNumber' => $phone,
            'CallBackURL' => $callback,
            'AccountReference' => 'Order'.$order->order_id,
            'TransactionDesc' => 'Order '.$order->order_id,
        ]);

        $checkoutId = $response->json('CheckoutRequestID');
        if (! $checkoutId) {
            return ['ok' => false, 'message' => 'M-Pesa did not start the prompt. '.$response->json('errorMessage', ''), 'reference' => null];
        }

        $intent->reference = $checkoutId;
        $intent->save();

        return ['ok' => true, 'message' => 'Check the phone for the M-Pesa prompt.', 'reference' => $checkoutId];
    }

    public function callback(Request $request): \Illuminate\Http\JsonResponse
    {
        $secret = (string) config('services.mpesa.callback_secret');
        $given = (string) $request->query('token', '');
        if ($secret !== '' && ! hash_equals($secret, $given)) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Rejected'], 401);
        }

        if (config('legacy.safe_mode') || config('legacy.read_only')) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored while payments are paused']);
        }

        $callback = $request->input('Body.stkCallback', []);
        $code = (int) ($callback['ResultCode'] ?? 1);
        $checkoutId = (string) ($callback['CheckoutRequestID'] ?? '');
        if ($code !== 0 || $checkoutId === '') {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $receipt = $checkoutId;
        foreach ($callback['CallbackMetadata']['Item'] ?? [] as $item) {
            if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                $receipt = (string) $item['Value'];
            }
        }

        $this->checkout->settle($checkoutId, $receipt);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
