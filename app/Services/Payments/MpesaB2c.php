<?php

namespace App\Services\Payments;

use App\Models\MpesaPayout;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Http;

/**
 * Daraja B2C. Safe mode never calls Safaricom. MPESA_B2C_MOCK records a
 * conversation id without the network so sandbox tests can run the callback.
 */
class MpesaB2c
{
    /**
     * @return array{ok: bool, sent: bool, message: string, conversation: ?string}
     */
    public function send(PaymentRequest $payout, string $phone, float $amount): array
    {
        if (config('legacy.safe_mode')) {
            return ['ok' => false, 'sent' => false, 'message' => 'Safe mode is on. M-Pesa was not contacted.', 'conversation' => null];
        }

        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }
        if (! preg_match('/^2547\d{8}$/', $phone)) {
            return ['ok' => false, 'sent' => false, 'message' => 'Enter a Safaricom number.', 'conversation' => null];
        }

        if (! config('services.mpesa.consumer_key') || ! config('services.mpesa.consumer_secret')) {
            return ['ok' => false, 'sent' => false, 'message' => 'M-Pesa keys are not set.', 'conversation' => null];
        }

        if (config('services.mpesa.b2c_mock')) {
            return ['ok' => true, 'sent' => false, 'message' => 'Sandbox mock accepted the payout. Waiting for the result callback.', 'conversation' => 'mock-'.$payout->payment_request_id];
        }

        $base = config('services.mpesa.env') === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $token = Http::withBasicAuth((string) config('services.mpesa.consumer_key'), (string) config('services.mpesa.consumer_secret'))
            ->acceptJson()
            ->get($base.'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
            ->json('access_token');

        if (! $token) {
            return ['ok' => false, 'sent' => false, 'message' => 'M-Pesa did not issue a token.', 'conversation' => null];
        }

        $callback = url('/payments/mpesa/b2c');
        $response = Http::withToken($token)->acceptJson()->post($base.'/mpesa/b2c/v1/paymentrequest', [
            'InitiatorName' => config('services.mpesa.initiator'),
            'SecurityCredential' => (string) config('services.mpesa.security_credential'),
            'CommandID' => 'BusinessPayment',
            'Amount' => (int) ceil($amount),
            'PartyA' => config('services.mpesa.shortcode'),
            'PartyB' => $phone,
            'Remarks' => 'Payout '.$payout->payment_request_id,
            'QueueTimeOutURL' => $callback,
            'ResultURL' => $callback,
            'Occasion' => 'Payout',
        ]);

        if ((string) $response->json('ResponseCode') !== '0') {
            return ['ok' => false, 'sent' => true, 'message' => 'M-Pesa refused the payout.', 'conversation' => null];
        }

        return [
            'ok' => true,
            'sent' => true,
            'message' => 'M-Pesa accepted the payout. It is paid when the result callback arrives.',
            'conversation' => (string) $response->json('ConversationID'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function reconcile(array $payload): bool
    {
        $result = $payload['Result'] ?? $payload;
        if (! is_array($result) || (string) ($result['ResultCode'] ?? '1') !== '0') {
            return false;
        }
        $transaction = (string) ($result['TransactionID'] ?? '');
        if ($transaction === '') {
            return false;
        }
        $amount = (float) ($result['TransactionAmount'] ?? 0);
        $phone = substr((string) ($result['ReceiverPartyPublicName'] ?? ''), 0, 12);
        $row = MpesaPayout::query()
            ->where('phone', $phone)
            ->where('amount', $amount)
            ->where('is_paid', 0)
            ->orderByDesc('mpesa_payout_id')
            ->first();
        if (! $row) {
            $existing = MpesaPayout::query()->where('transaction_id', $transaction)->first();

            return $existing !== null;
        }
        if ($row->transaction_id === $transaction && $row->is_paid) {
            return true;
        }
        $row->transaction_id = $transaction;
        $row->is_paid = 1;
        $row->paid_at = now();
        $row->date_upd = now();
        $row->save();
        $request = PaymentRequest::query()->find($row->payment_request_id);
        if ($request && (int) $request->status !== 1) {
            $request->status = 1;
            $request->paid_at = now();
            $request->date_upd = now();
            $request->save();
        }

        return true;
    }
}
