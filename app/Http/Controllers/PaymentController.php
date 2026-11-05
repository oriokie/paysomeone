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

        return back()->with('status', $result['message']);
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
