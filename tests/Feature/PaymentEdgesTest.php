<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaypalDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentEdgesTest extends TestCase
{
    #[Test]
    public function paypal_records_a_failed_login_a_denied_capture_and_an_ipn_that_names_the_order(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.paypal.client_id' => 'id',
            'services.paypal.secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);
        Http::preventStrayRequests();
        $mode = 'deny';
        Http::fake(function () use (&$mode) {
            return $mode === 'deny' ? Http::response([], 401) : Http::response('VERIFIED');
        });
        $customer = $this->customer('edge-paypal@example.test');
        $order = $this->order($customer, 11);
        $started = app(PaypalDriver::class)->start($customer, $order);
        $this->assertFalse($started['ok']);
        $this->assertSame('failed', PaymentIntent::query()->where('order_id', $order->order_id)->value('status'));

        PaymentIntent::query()->where('order_id', $order->order_id)->update(['status' => 'pending', 'reference' => 'edge_ref']);
        $mode = 'ipn';
        $this->post('/payments/paypal/ipn', [
            'payment_status' => 'Completed',
            'txn_id' => 'IPNEDGE',
            'item_number' => $order->order_id,
            'mc_gross' => '11.00',
        ])->assertOk();
        $this->assertSame('paid', PaymentIntent::query()->where('reference', 'edge_ref')->value('status'));

        $denied = $this->post('/payments/paypal/ipn', [
            'payment_status' => 'Denied',
            'txn_id' => 'IPNDENY',
            'custom' => 'missing-ref',
        ]);
        $this->assertSame(200, $denied->status(), $denied->getContent());
    }

    #[Test]
    public function a_wallet_payment_and_a_tip_refuse_the_cases_that_should_not_move_money(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        $customer = $this->customer('edge-wallet@example.test', 5);
        $order = $this->order($customer, 5);
        $checkout = app(CheckoutService::class);
        $this->assertSame('Paid from the wallet.', $checkout->payWithWallet($order, $customer));
        $this->assertSame('This order is already paid.', $checkout->payWithWallet($order->fresh(), $customer->fresh()));
        $order->status_id = Order::DELIVERED;
        $order->save();
        $this->assertSame('Enter a tip of at least $1.', $checkout->tipFromWallet($order, $customer->fresh(), 0));
        config(['legacy.read_only' => true]);
        $this->assertStringContainsString('paused', $checkout->tipFromWallet($order, $customer->fresh(), 2));
    }

    #[Test]
    public function stripe_ignores_an_unpaid_session_and_records_a_failed_payment(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.stripe.webhook_secret' => 'whsec_edge',
        ]);
        $customer = $this->customer('edge-stripe@example.test');
        $order = $this->order($customer, 8);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'stripe',
            'purpose' => 'order',
            'reference' => 'edge_stripe',
            'amount' => 8,
            'status' => 'pending',
        ]);
        $unpaid = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['client_reference_id' => 'edge_stripe', 'payment_intent' => 'pi_wait', 'payment_status' => 'unpaid', 'amount_total' => 800]],
        ]);
        $this->call('POST', '/payments/stripe/webhook', [], [], [], $this->transformHeadersToServerVars($this->stripe($unpaid)), $unpaid)->assertOk();
        $this->assertSame('pending', PaymentIntent::query()->where('reference', 'edge_stripe')->value('status'));

        $failed = json_encode([
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['client_reference_id' => 'edge_stripe']],
        ]);
        $this->call('POST', '/payments/stripe/webhook', [], [], [], $this->transformHeadersToServerVars($this->stripe($failed)), $failed)->assertOk();
        $this->assertSame('failed', PaymentIntent::query()->where('reference', 'edge_stripe')->value('status'));
    }

    /**
     * @return array<string, string>
     */
    private function stripe(string $payload): array
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_edge');

        return [
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => 't='.$timestamp.',v1='.$signature,
        ];
    }

    private function customer(string $email, float $wallet = 0): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Edge',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'wallet_balance' => $wallet,
            'reg_date' => now(),
        ]);
    }

    private function order(Customer $customer, float $total): Order
    {
        $order = new Order;
        $order->site_id = 1;
        $order->form_type_id = Order::SERVICE_ARTICLE;
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->status_id = Order::WAITING_FOR_PAYMENT;
        $order->parent_id = 0;
        $order->service_type = Order::SERVICE_ARTICLE;
        $order->tariff_id = 0;
        $order->title = 'Edge order';
        $order->academic_level_id = 0;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 0;
        $order->spacing = 'double';
        $order->total = $total;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return $order;
    }
}
