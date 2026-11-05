<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\MpesaDriver;
use App\Services\Payments\PaypalDriver;
use App\Services\Payments\PesapalDriver;
use App\Services\Payments\StripeDriver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(Request $request, Order $order, CheckoutService $checkout, MpesaDriver $mpesa, StripeDriver $stripe, PaypalDriver $paypal, PesapalDriver $pesapal): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $data = $request->validate([
            'provider' => ['required', 'in:wallet,mpesa,stripe,paypal,pesapal'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $result = match ($data['provider']) {
            'wallet' => ['message' => $checkout->payWithWallet($order, $customer), 'redirect' => null],
            'mpesa' => $this->wrap($mpesa->push($customer, $order, (string) ($data['phone'] ?? ''))),
            'stripe' => $stripe->start($customer, $order),
            'paypal' => $paypal->start($customer, $order),
            'pesapal' => $pesapal->start($customer, $order),
        };

        if (! empty($result['redirect'])) {
            return redirect()->away($result['redirect']);
        }

        $phase = 'failed';
        if (str_contains($result['message'], 'M-Pesa prompt') || str_contains($result['message'], 'Check the phone')) {
            $phase = 'waiting';
        } elseif (str_starts_with($result['message'], 'Paid')) {
            $phase = 'success';
        }

        return back()->with('status', $result['message'])->with('pay_phase', $phase)->with('tab', 'payment');
    }

    public function mpesa(Request $request, MpesaDriver $driver): \Illuminate\Http\JsonResponse
    {
        return $driver->callback($request);
    }

    public function stripe(Request $request, StripeDriver $driver): \Illuminate\Http\Response
    {
        return $driver->webhook($request);
    }

    public function paypal(Request $request, PaypalDriver $driver): \Illuminate\Http\Response
    {
        return $driver->webhook($request);
    }

    public function paypalIpn(Request $request, PaypalDriver $driver): \Illuminate\Http\Response
    {
        return $driver->ipn($request);
    }

    public function paypalReturn(Request $request, PaypalDriver $driver): RedirectResponse
    {
        return $driver->returned($request);
    }

    public function paypalCancel(Request $request, PaypalDriver $driver): RedirectResponse
    {
        return $driver->cancelled($request);
    }

    public function stripeCancel(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        $checkout->cancel($reference, 'The card checkout was cancelled.');
        $intent = \App\Models\PaymentIntent::query()->where('reference', $reference)->first();
        $order = $intent?->order_id ? Order::query()->find($intent->order_id) : null;

        return redirect()->to($order ? route('desk.order', $order) : route('account'))
            ->with('status', 'Card checkout was cancelled. Nothing was charged.')
            ->with('pay_phase', 'failed')
            ->with('tab', 'payment');
    }

    public function pesapal(Request $request, PesapalDriver $driver): \Illuminate\Http\Response
    {
        return $driver->ipn($request);
    }

    /**
     * @param  array{ok: bool, message: string, reference: ?string}  $result
     * @return array{message: string, redirect: null}
     */
    private function wrap(array $result): array
    {
        return ['message' => $result['message'], 'redirect' => null];
    }

    private function owns(Request $request, Order $order): Customer
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');
        abort_unless((int) $order->customer_id === (int) $customer->customer_id, 403);

        return $customer;
    }
}
