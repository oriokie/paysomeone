<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GatewaySetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\WalletTransaction;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentRecorder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentLifecycleTest extends TestCase
{
    public function test_mpesa_decline_marks_the_intent_failed_and_writes_no_payment(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false, 'services.mpesa.callback_secret' => 'callback-secret']);
        $customer = $this->customer('mpesa-fail@example.test');
        $order = $this->order($customer);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'mpesa',
            'purpose' => 'order',
            'reference' => 'ws_CO_fail',
            'amount' => 20,
            'status' => 'pending',
        ]);

        $this->postJson('/payments/mpesa/callback?token=callback-secret', [
            'Body' => ['stkCallback' => ['ResultCode' => 1032, 'CheckoutRequestID' => 'ws_CO_fail']],
        ])->assertOk();

        $this->assertSame('failed', PaymentIntent::query()->where('reference', 'ws_CO_fail')->value('status'));
        $this->assertSame(0, Payment::query()->where('node_id', $order->order_id)->count());
    }

    public function test_paypal_ipn_is_verified_once_and_a_bad_notice_is_rejected(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false, 'services.paypal.mode' => 'sandbox']);
        Http::fake([
            'ipnpb.sandbox.paypal.com/*' => Http::sequence()
                ->push('VERIFIED')
                ->push('VERIFIED')
                ->push('INVALID'),
        ]);
        $customer = $this->customer('paypal@example.test');
        $order = $this->order($customer, 18);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'paypal',
            'purpose' => 'order',
            'reference' => 'paypal_ref_1',
            'amount' => 18,
            'status' => 'pending',
        ]);

        $notice = ['payment_status' => 'Completed', 'txn_id' => 'PAYPAL1', 'custom' => 'paypal_ref_1', 'mc_gross' => '18.00'];
        $this->post('/payments/paypal/ipn', $notice)->assertOk();
        $this->post('/payments/paypal/ipn', $notice)->assertOk();
        $this->assertSame(1, Payment::query()->where('transaction_id', 'PAYPAL1')->where('payment_method', 'paypal')->count());
        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->post('/payments/paypal/ipn', $notice)->assertStatus(400);
    }

    public function test_paypal_webhook_requires_a_real_signature_and_a_refund_does_not_credit_the_wallet(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        $public = openssl_pkey_get_details($key)['key'];
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.paypal.webhook_id' => 'WH-1',
            'services.paypal.webhook_certificate' => $public,
        ]);
        $customer = $this->customer('paypal-hook@example.test');
        $order = $this->order($customer, 12);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'paypal',
            'purpose' => 'order',
            'reference' => 'paypal_ref_2',
            'amount' => 12,
            'status' => 'pending',
        ]);
        $payload = json_encode([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAP1', 'custom_id' => 'paypal_ref_2', 'amount' => ['value' => '12.00']],
        ]);
        $headers = $this->paypalHeaders($payload, $key);
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $payload)->assertStatus(400);

        $signed = $this->paypalHeaders($payload, $key, true);
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($signed), $payload)->assertOk();
        $this->assertSame('paid', PaymentIntent::query()->where('reference', 'paypal_ref_2')->value('status'));

        $refund = json_encode(['event_type' => 'PAYMENT.CAPTURE.REFUNDED', 'resource' => ['id' => 'CAP1']]);
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($this->paypalHeaders($refund, $key, true)), $refund)->assertOk();
        $this->assertSame('refunded', PaymentIntent::query()->where('reference', 'paypal_ref_2')->value('status'));
        $this->assertSame(0, WalletTransaction::query()->where('customer_id', $customer->customer_id)->count());
    }

    public function test_pesapal_settles_only_after_the_provider_says_completed(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false, 'services.pesapal.consumer_key' => 'key', 'services.pesapal.consumer_secret' => 'secret']);
        $customer = $this->customer('pesapal@example.test');
        $order = $this->order($customer, 9);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'pesapal',
            'purpose' => 'order',
            'reference' => 'pesa_1',
            'amount' => 9,
            'status' => 'pending',
        ]);
        $seen = 0;
        Http::fake([
            'cybqa.pesapal.com/*' => function ($request) use (&$seen) {
                if (str_contains($request->url(), 'RequestToken')) {
                    return Http::response(['token' => 'token']);
                }
                $seen++;

                return Http::response(['payment_status_description' => $seen === 1 ? 'FAILED' : 'COMPLETED']);
            },
        ]);
        $this->post('/payments/pesapal/ipn', ['OrderTrackingId' => 'TRK1', 'OrderMerchantReference' => 'pesa_1'])->assertOk();
        $this->assertSame('failed', PaymentIntent::query()->where('reference', 'pesa_1')->value('status'));
        $this->assertSame(0, Payment::query()->where('node_id', $order->order_id)->count());

        PaymentIntent::query()->where('reference', 'pesa_1')->update(['status' => 'pending']);
        $this->post('/payments/pesapal/ipn', ['OrderTrackingId' => 'TRK2', 'OrderMerchantReference' => 'pesa_1'])->assertOk();
        $this->assertSame('paid', PaymentIntent::query()->where('reference', 'pesa_1')->value('status'));
        $this->assertSame('pesapal', Payment::query()->where('transaction_id', 'TRK2')->value('payment_method'));
    }

    public function test_a_wallet_top_up_is_a_deposit_and_a_mismatched_amount_is_not_applied(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        $customer = $this->customer('wallet-dep@example.test');
        app(PaymentRecorder::class)->creditWallet($customer, 25, 'mpesa', 'TOPUP1', null);
        $row = WalletTransaction::query()->where('transaction_id', 'TOPUP1')->first();
        $this->assertSame('deposit', $row->source);
        $this->assertSame(125.0, (float) $customer->fresh()->wallet_balance);

        $order = $this->order($customer, 10);
        $intent = PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'stripe',
            'purpose' => 'order',
            'reference' => 'mismatch_1',
            'amount' => 10,
            'status' => 'pending',
        ]);
        $this->assertFalse(app(CheckoutService::class)->settle('mismatch_1', 'pi_bad', 4));
        $this->assertSame('failed', $intent->fresh()->status);
        $this->assertFalse((bool) $order->fresh()->is_paid);
    }

    public function test_a_turned_off_gateway_does_not_call_paypal(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        Http::preventStrayRequests();
        GatewaySetting::query()->create(['gateway' => 'paypal', 'enabled' => false, 'test_mode' => true, 'currency' => 'USD', 'fee_percent' => 0, 'fee_fixed' => 0]);
        $customer = $this->customer('closed-paypal@example.test');
        $order = $this->order($customer);
        $result = app(\App\Services\Payments\PaypalDriver::class)->start($customer, $order);
        $this->assertFalse($result['ok']);
        $this->assertSame('This payment method is turned off.', $result['message']);
    }

    public function test_payment_history_is_paginated_and_private(): void
    {
        $owner = $this->customer('payer-list@example.test');
        $other = $this->customer('other-list@example.test');
        foreach (range(1, 12) as $i) {
            PaymentIntent::query()->create([
                'customer_id' => $owner->customer_id,
                'provider' => $i % 2 ? 'paypal' : 'mpesa',
                'purpose' => 'order',
                'reference' => 'list_'.$i,
                'amount' => $i,
                'status' => 'paid',
            ]);
        }
        $secret = PaymentIntent::query()->create([
            'customer_id' => $other->customer_id,
            'provider' => 'stripe',
            'purpose' => 'order',
            'reference' => 'secret_ref',
            'amount' => 3,
            'status' => 'paid',
        ]);

        $this->actingAs($owner, 'customer')->get('/account/payments?per_page=10&provider=paypal')->assertOk()->assertSee('PayPal')->assertDontSee('secret_ref');
        $this->actingAs($owner, 'customer')->get('/account/payments?per_page=10&page=2')->assertOk();
        $this->actingAs($owner, 'customer')->get(route('desk.payments.show', $secret))->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function paypalHeaders(string $payload, \OpenSSLAsymmetricKey $key, bool $sign = false): array
    {
        $id = 'trans-1';
        $time = (string) time();
        $message = $id.'|'.$time.'|WH-1|'.sprintf('%u', crc32($payload));
        $raw = 'not-a-signature';
        if ($sign) {
            openssl_sign($message, $raw, $key, OPENSSL_ALGO_SHA256);
        }

        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYPAL_TRANSMISSION_ID' => $id,
            'HTTP_PAYPAL_TRANSMISSION_TIME' => $time,
            'HTTP_PAYPAL_TRANSMISSION_SIG' => base64_encode($raw),
            'HTTP_PAYPAL_CERT_URL' => 'https://api.sandbox.paypal.com/cert.pem',
        ];
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Payer',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'wallet_balance' => 100,
            'reg_date' => now(),
        ]);
    }

    private function order(Customer $customer, float $total = 20): Order
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
        $order->title = 'Payment order';
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
