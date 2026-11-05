<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaypalDriver
{
    public function __construct(private readonly CheckoutService $checkout) {}

    /**
     * @return array{ok: bool, message: string, redirect: ?string}
     */
    public function start(Customer $customer, Order $order): array
    {
        if ($reason = $this->checkout->providerBlocked() ?? $this->checkout->gatewayClosed('paypal')) {
            return ['ok' => false, 'message' => $reason, 'redirect' => null];
        }

        $amount = round(max(0, (float) $order->total - (float) $order->total_paid), 2);
        $intent = $this->checkout->intent($customer, 'paypal', 'order', $amount, $order);
        $token = $this->token();
        if (! $token) {
            $this->checkout->fail($intent->reference, 'PayPal did not accept the sandbox login.');

            return ['ok' => false, 'message' => 'PayPal did not accept the sandbox login.', 'redirect' => null];
        }

        $response = Http::withToken($token)->acceptJson()->post($this->base().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $order->order_id,
                'custom_id' => $intent->reference,
                'invoice_id' => $intent->reference,
                'amount' => ['currency_code' => 'USD', 'value' => number_format($amount, 2, '.', '')],
            ]],
            'application_context' => [
                'return_url' => route('payments.paypal.return', ['reference' => $intent->reference]),
                'cancel_url' => route('payments.paypal.cancel', ['reference' => $intent->reference]),
                'user_action' => 'PAY_NOW',
            ],
        ]);

        $approve = collect($response->json('links', []))->firstWhere('rel', 'approve')['href'] ?? null;
        $paypalId = (string) $response->json('id', '');
        if (! $approve || $paypalId === '') {
            $this->checkout->fail($intent->reference, 'PayPal did not return a checkout link.');

            return ['ok' => false, 'message' => 'PayPal did not return a checkout link.', 'redirect' => null];
        }

        $intent->payload = ['paypal_order_id' => $paypalId];
        $intent->save();

        return ['ok' => true, 'message' => 'Continue to PayPal.', 'redirect' => $approve];
    }

    public function returned(Request $request): \Illuminate\Http\RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        $paypalOrder = (string) $request->query('token', '');
        $intent = PaymentIntent::query()->where('reference', $reference)->where('provider', 'paypal')->first();
        if (! $intent || ! $intent->order_id) {
            return redirect()->route('account');
        }

        $order = Order::query()->find($intent->order_id);
        $target = $order ? route('desk.order', $order) : route('account');
        if ($intent->status === 'paid') {
            return redirect()->to($target)->with('status', 'Paid. The receipt is on this order.')->with('pay_phase', 'success')->with('tab', 'payment');
        }

        if ($reason = $this->checkout->providerBlocked()) {
            return redirect()->to($target)->with('status', $reason)->with('pay_phase', 'failed')->with('tab', 'payment');
        }

        $token = $this->token();
        $response = $token ? Http::withToken($token)->acceptJson()->post($this->base().'/v2/checkout/orders/'.$paypalOrder.'/capture') : null;
        $capture = $response?->json('purchase_units.0.payments.captures.0') ?? [];
        $status = (string) ($capture['status'] ?? $response?->json('status') ?? '');
        $captureId = (string) ($capture['id'] ?? '');
        $paid = isset($capture['amount']['value']) ? (float) $capture['amount']['value'] : null;
        if ($status === 'COMPLETED' && $captureId !== '') {
            $this->checkout->settle($reference, $captureId, $paid);

            return redirect()->to($target)->with('status', 'Paid with PayPal.')->with('pay_phase', 'success')->with('tab', 'payment');
        }

        $this->checkout->fail($reference, 'PayPal did not capture the payment.');
        Log::warning('paypal.capture_failed', ['reference' => $reference, 'status' => $status]);

        return redirect()->to($target)->with('status', 'PayPal did not capture the payment.')->with('pay_phase', 'failed')->with('tab', 'payment');
    }

    public function cancelled(Request $request): \Illuminate\Http\RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        $this->checkout->cancel($reference, 'The PayPal checkout was cancelled.');
        $intent = PaymentIntent::query()->where('reference', $reference)->first();
        $order = $intent?->order_id ? Order::query()->find($intent->order_id) : null;

        return redirect()->to($order ? route('desk.order', $order) : route('account'))
            ->with('status', 'PayPal checkout was cancelled. Nothing was charged.')
            ->with('pay_phase', 'failed')
            ->with('tab', 'payment');
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $payload = $request->getContent();
        $webhookId = (string) config('services.paypal.webhook_id');
        $headers = array_change_key_case($request->headers->all(), CASE_LOWER);
        $flat = [];
        foreach ($headers as $key => $value) {
            $flat[$key] = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        }
        $certificate = (string) config('services.paypal.webhook_certificate');
        if (! Signatures::paypal($payload, $flat, $webhookId, $certificate !== '' ? $certificate : null)) {
            return response('Invalid signature', 400);
        }

        if (config('legacy.safe_mode') || config('legacy.read_only')) {
            return response('Paused', 200);
        }

        $event = json_decode($payload, true);
        $type = (string) ($event['event_type'] ?? '');
        $resource = $event['resource'] ?? [];
        if (in_array($type, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED', 'CHECKOUT.ORDER.CANCELLED'], true)) {
            $reference = (string) ($resource['custom_id'] ?? $resource['invoice_id'] ?? '');
            if ($reference !== '') {
                $this->checkout->fail($reference, 'PayPal reported '.$type.'.');
            }

            return response('OK', 200);
        }
        if ($type === 'PAYMENT.CAPTURE.REFUNDED') {
            $this->checkout->markRefunded((string) ($resource['id'] ?? ''));

            return response('OK', 200);
        }
        if (! in_array($type, ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED'], true)) {
            return response('Ignored', 200);
        }
        if ($type === 'CHECKOUT.ORDER.APPROVED') {
            return response('Ignored', 200);
        }

        $reference = (string) ($resource['custom_id'] ?? $resource['invoice_id'] ?? $resource['purchase_units'][0]['custom_id'] ?? '');
        $transaction = (string) ($resource['id'] ?? '');
        $amount = isset($resource['amount']['value']) ? (float) $resource['amount']['value'] : null;
        if ($reference !== '' && $transaction !== '') {
            $this->checkout->settle($reference, $transaction, $amount);
        }

        return response('OK', 200);
    }

    /**
     * Classic PayPal IPN, the path the previous office used. A local test
     * fakes the verification call. A real notice is posted back to PayPal.
     */
    public function ipn(Request $request): \Illuminate\Http\Response
    {
        $verify = Http::asForm()->post($this->ipnEndpoint(), array_merge(['cmd' => '_notify-validate'], $request->post()));
        if (trim($verify->body()) !== 'VERIFIED') {
            return response('Invalid', 400);
        }

        if (config('legacy.safe_mode') || config('legacy.read_only')) {
            return response('Paused', 200);
        }

        $status = (string) $request->input('payment_status', '');
        $txn = (string) $request->input('txn_id', '');
        $reference = (string) $request->input('custom', $request->input('invoice', ''));
        $amount = $request->filled('mc_gross') ? (float) $request->input('mc_gross') : null;
        if ($reference === '' && $request->filled('item_number')) {
            $intent = PaymentIntent::query()
                ->where('provider', 'paypal')
                ->where('order_id', (int) $request->input('item_number'))
                ->where('status', 'pending')
                ->latest('id')
                ->first();
            $reference = (string) ($intent->reference ?? '');
        }
        if ($status === 'Completed' && $txn !== '' && $reference !== '') {
            $this->checkout->settle($reference, $txn, $amount);
        } elseif (in_array($status, ['Denied', 'Failed', 'Voided'], true) && $reference !== '') {
            $this->checkout->fail($reference, 'PayPal reported '.$status.'.');
        } elseif ($status === 'Refunded' && $txn !== '') {
            $this->checkout->markRefunded($txn);
        }

        return response('OK', 200);
    }

    private function token(): ?string
    {
        return Http::withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
            ->asForm()
            ->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->json('access_token');
    }

    private function base(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function ipnEndpoint(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://ipnpb.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    }
}
