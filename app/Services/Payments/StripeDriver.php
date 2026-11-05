<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StripeDriver
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
        $intent = $this->checkout->intent($customer, 'stripe', 'order', $amount, $order);
        $response = Http::withToken((string) config('services.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('desk.order', $order).'?paid=1',
                'cancel_url' => route('desk.order', $order).'?paid=0',
                'client_reference_id' => $intent->reference,
                'customer_email' => $customer->email,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => 'usd',
                'line_items[0][price_data][unit_amount]' => (int) round($amount * 100),
                'line_items[0][price_data][product_data][name]' => 'Order #'.$order->order_id,
            ]);

        $url = $response->json('url');
        if (! $url) {
            return ['ok' => false, 'message' => 'Stripe did not open a checkout page.', 'redirect' => null];
        }

        return ['ok' => true, 'message' => 'Continue to Stripe.', 'redirect' => $url];
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $secret = (string) config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        if (! Signatures::stripe($payload, (string) $request->header('Stripe-Signature'), $secret)) {
            return response('Invalid signature', 400);
        }

        if (config('legacy.safe_mode') || config('legacy.read_only')) {
            return response('Paused', 200);
        }

        $event = json_decode($payload, true);
        if (($event['type'] ?? '') !== 'checkout.session.completed') {
            return response('Ignored', 200);
        }

        $session = $event['data']['object'] ?? [];
        $reference = (string) ($session['client_reference_id'] ?? '');
        $transaction = (string) ($session['payment_intent'] ?? $session['id'] ?? '');
        $cents = $session['amount_total'] ?? null;
        if ($reference !== '' && $transaction !== '') {
            $this->checkout->settle($reference, $transaction, $cents !== null ? ((int) $cents) / 100 : null);
        }

        return response('OK', 200);
    }
}
