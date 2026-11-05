<?php

namespace Tests\Feature;

use App\Models\CreditFine;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\MpesaPayout;
use App\Models\Order;
use App\Models\OrderTip;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\PaymentRequest;
use App\Models\SavedView;
use App\Models\WalletTransaction;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\MpesaB2c;
use App\Services\Payments\MpesaDriver;
use App\Services\Payments\PaypalDriver;
use App\Services\Payments\StripeDriver;
use App\Support\Departments;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentAndOfficeBranchesTest extends TestCase
{
    #[Test]
    public function paypal_and_stripe_reject_bad_signatures_duplicates_refunds_and_amount_mismatches(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.paypal.mode' => 'sandbox',
            'services.paypal.client_id' => 'id',
            'services.paypal.secret' => 'secret',
            'services.paypal.webhook_id' => 'WH',
            'services.stripe.secret' => 'sk_test',
            'services.stripe.webhook_secret' => 'whsec_branch',
        ]);
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response(['id' => 'NO_LINKS']));
        $customer = $this->customer('branch-pay@example.test', 30);
        $referrer = $this->customer('branch-referrer@example.test', 0);
        $customer->referrer_id = $referrer->customer_id;
        $customer->affiliate_used = 0;
        $customer->save();
        $order = $this->order($customer, 15);

        $started = app(PaypalDriver::class)->start($customer, $order);
        $this->assertFalse($started['ok']);
        $this->assertSame('failed', PaymentIntent::query()->where('order_id', $order->order_id)->value('status'));

        $intent = PaymentIntent::query()->where('order_id', $order->order_id)->first();
        $intent->status = 'paid';
        $intent->transaction_id = 'CAP_OLD';
        $intent->save();
        $this->get(route('payments.paypal.return', ['reference' => $intent->reference, 'token' => 'X']))
            ->assertRedirect();
        config(['legacy.safe_mode' => true]);
        $intent->status = 'pending';
        $intent->save();
        $this->get(route('payments.paypal.return', ['reference' => $intent->reference, 'token' => 'X']))
            ->assertSessionHas('pay_phase', 'failed');
        config(['legacy.safe_mode' => false]);
        $this->get(route('payments.paypal.return', ['reference' => 'missing', 'token' => 'X']))
            ->assertRedirect(route('account'));

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $public = openssl_pkey_get_details($key)['key'];
        config(['services.paypal.webhook_certificate' => $public]);
        $denied = json_encode(['event_type' => 'PAYMENT.CAPTURE.DENIED', 'resource' => ['custom_id' => $intent->reference]]);
        $signed = $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($this->paypalHeaders($denied, $key)), $denied);
        $this->assertSame(200, $signed->status(), $signed->getContent());
        $this->assertSame('failed', $intent->fresh()->status);

        $intent->status = 'paid';
        $intent->transaction_id = 'CAP_OLD';
        $intent->save();
        $refund = json_encode(['event_type' => 'PAYMENT.CAPTURE.REFUNDED', 'resource' => ['id' => 'CAP_OLD']]);
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($this->paypalHeaders($refund, $key)), $refund)->assertOk();
        $this->assertSame('refunded', $intent->fresh()->status);
        $again = json_encode(['event_type' => 'PAYMENT.CAPTURE.REFUNDED', 'resource' => ['id' => 'CAP_OLD']]);
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($this->paypalHeaders($again, $key)), $again)->assertOk();
        $this->assertSame(1, PaymentIntent::query()->where('transaction_id', 'CAP_OLD')->count());

        $approved = json_encode(['event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource' => []]);
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars($this->paypalHeaders($approved, $key)), $approved)->assertOk();
        $this->call('POST', '/payments/paypal/webhook', [], [], [], $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'PayPal-Transmission-Id' => 'x']), '{}')->assertStatus(400);

        Http::fake(fn () => Http::response('INVALID'));
        $this->post('/payments/paypal/ipn', ['payment_status' => 'Completed', 'txn_id' => 'NO'])->assertStatus(400);
        $this->assertSame('refunded', $intent->fresh()->status);

        Http::fake(fn () => Http::response([], 500));
        $card = app(StripeDriver::class)->start($customer, $order);
        $this->assertFalse($card['ok']);
        $bad = json_encode(['type' => 'charge.refunded', 'data' => ['object' => ['payment_intent' => 'CAP_OLD']]]);
        $this->call('POST', '/payments/stripe/webhook', [], [], [], $this->transformHeadersToServerVars([
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => 't=1,v1=deadbeef',
        ]), $bad)->assertStatus(400);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$bad, 'whsec_branch');
        $this->call('POST', '/payments/stripe/webhook', [], [], [], $this->transformHeadersToServerVars([
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => 't='.$timestamp.',v1='.$signature,
        ]), $bad)->assertOk();

        $checkout = app(CheckoutService::class);
        $topup = $checkout->intent($customer, 'mpesa', 'wallet', 8, null, 'topup_ref');
        $this->assertTrue($checkout->settle('topup_ref', 'TOP1', 8));
        $this->assertFalse($checkout->settle('topup_ref', 'TOP1', 8));
        $this->assertSame(8.0, (float) WalletTransaction::query()->where('transaction_id', 'TOP1')->value('amount'));
        $tip = $checkout->intent($customer, 'paypal', 'tip', 3, $order, 'tip_ref');
        $this->assertTrue($checkout->settle('tip_ref', 'TIP1', 3));
        $this->assertFalse($checkout->settle('tip_ref', 'TIP1', 3));
        $this->assertSame(1, OrderTip::query()->where('transaction_id', 'TIP1')->count());
        $this->assertSame(1, Payment::query()->where('transaction_id', 'TIP1')->where('node_type', 'tip')->count());
        $gone = $checkout->intent($customer, 'stripe', 'order', 4, null, 'gone_ref');
        $gone->order_id = 99999999;
        $gone->save();
        $this->assertFalse($checkout->settle('gone_ref', 'GONE', 4));
        $mismatch = $checkout->intent($customer, 'stripe', 'order', 9, $order, 'mismatch_ref');
        $this->assertFalse($checkout->settle('mismatch_ref', 'BADAMT', 3));
        $this->assertSame('failed', $mismatch->fresh()->status);
        $this->assertFalse((bool) $order->fresh()->is_paid === false && Payment::query()->where('transaction_id', 'BADAMT')->exists());

        config(['legacy.safe_mode' => true]);
        $this->assertNotNull(app(StripeDriver::class)->start($customer, $order)['message']);
        $this->assertNotNull(app(MpesaDriver::class)->push($customer, $order, '0712345678')['message']);
        $this->assertFalse(app(MpesaB2c::class)->send(new PaymentRequest(['payment_request_id' => 1, 'total_pay' => 1]), '0712345678', 1)['ok']);
    }

    #[Test]
    public function a_verified_live_ipn_can_deny_or_refund_without_calling_paypal(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false, 'services.paypal.mode' => 'live']);
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('VERIFIED'));
        $customer = $this->customer('ipn-live@example.test');
        $order = $this->order($customer, 6);
        $intent = app(CheckoutService::class)->intent($customer, 'paypal', 'order', 6, $order, 'live_ipn');
        $intent->status = 'paid';
        $intent->transaction_id = 'LIVE_CAP';
        $intent->save();

        $this->post('/payments/paypal/ipn', [
            'payment_status' => 'Refunded',
            'txn_id' => 'LIVE_CAP',
        ])->assertOk();
        $this->assertSame('refunded', $intent->fresh()->status);

        $pending = app(CheckoutService::class)->intent($customer, 'paypal', 'order', 6, $order, 'live_deny');
        $this->post('/payments/paypal/ipn', [
            'payment_status' => 'Failed',
            'txn_id' => 'LIVE_FAIL',
            'custom' => $pending->reference,
        ])->assertOk();
        $this->assertSame('failed', $pending->fresh()->status);
    }

    #[Test]
    public function each_office_role_is_stopped_at_the_action_it_does_not_have(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => true, 'services.mpesa.b2c_mock' => true, 'services.mpesa.consumer_key' => 'key', 'services.mpesa.consumer_secret' => 'secret']);
        $admin = $this->staff(Employee::GROUP_ADMIN, 'role-admin@example.test');
        $sub = $this->staff(Employee::GROUP_SUB_ADMIN, 'role-sub@example.test');
        $editor = $this->staff(Employee::GROUP_EDITOR, 'role-editor@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'role-writer@example.test', 4);
        $customer = $this->customer('role-customer@example.test', 0);
        $order = $this->order($customer, 40);
        $order->writer_id = $writer->employee_id;
        $order->status_id = Order::WRITER_ASSIGNED;
        $order->save();

        $this->get('/office/login')->assertOk();
        $this->post('/office/login', ['email' => $writer->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $banned = $this->staff(Employee::GROUP_WRITER, 'role-banned@example.test');
        $banned->is_banned = 1;
        $banned->save();
        $this->post('/office/login', ['email' => $banned->email, 'password' => 'long-enough-password'])->assertSessionHasErrors('email');
        $this->post('/office/login', ['email' => $admin->email, 'password' => 'long-enough-password'])->assertRedirect(route('office.challenge'));
        $this->assertNotNull(\App\Models\StaffDevice::query()->where('employee_id', $admin->employee_id)->first());

        $this->office($writer)->get('/office')->assertOk();
        $this->office($writer)->get(route('office.pool'))->assertOk();
        $this->office($writer)->post(route('office.orders.notes', $order), ['body' => 'Writer note'])->assertSessionHas('status');
        $this->office($writer)->post(route('office.orders.cancel', $order))->assertForbidden();
        $this->office($editor)->get('/office/fines')->assertForbidden();
        $this->office($sub)->get(route('office.staff'))->assertForbidden();
        $this->office($sub)->get(route('office.gateways'))->assertForbidden();

        $this->office($admin)->get('/office')->assertOk();
        $this->office($admin)->post(route('office.orders.assign', $order), ['writer_id' => $writer->employee_id])->assertSessionHas('status');
        $this->office($writer)->post(route('office.orders.submit', $order))->assertSessionHas('status');
        $this->assertSame(Order::DONE, (int) $order->fresh()->status_id);
        $this->office($writer)->post(route('office.orders.submit', $order->fresh()))->assertStatus(422);

        $this->office($admin)->get(route('office.fines'))->assertOk();
        $this->office($admin)->post(route('office.fines.store'), [
            'employee_id' => $writer->employee_id,
            'type' => 'credit',
            'amount' => 6,
            'description' => 'Goodwill credit',
        ])->assertSessionHas('status');
        $this->assertSame('credit', CreditFine::query()->where('description', 'Goodwill credit')->value('type'));
        $fine = CreditFine::query()->where('employee_id', $writer->employee_id)->where('type', 'fine')->first();
        if (! $fine) {
            $fine = new CreditFine;
            $fine->type = 'fine';
            $fine->employee_id = $writer->employee_id;
            $fine->amount = 2;
            $fine->description = 'Other';
            $fine->date_add = now();
            $fine->save();
        }
        $this->office($editor)->post(route('office.fines.appeal', $fine), ['reason' => 'Not my fine at all, thanks.'])->assertForbidden();

        $this->office($writer)->post(route('office.earnings'), ['phone' => '0712345678', 'amount' => 9])->assertSessionHas('status');
        $request = PaymentRequest::query()->where('user_id', $writer->employee_id)->first();
        $this->office($editor)->post(route('office.payouts.approve', $request))->assertForbidden();
        config(['legacy.safe_mode' => false]);
        $this->office($admin)->post(route('office.payouts.approve', $request))->assertSessionHas('status');
        $payout = MpesaPayout::query()->where('payment_request_id', $request->payment_request_id)->first();
        $this->assertStringStartsWith('mock-', (string) $payout->transaction_id);
        $this->assertSame($request->payment_request_id, $payout->paymentRequest->payment_request_id);

        $this->office($admin)->post(route('office.orders.views'), ['name' => 'Paid', 'status' => (string) Order::DONE, 'q' => ''])->assertSessionHas('status');
        $view = SavedView::query()->where('employee_id', $admin->employee_id)->first();
        $this->office($admin)->get(route('office.orders', ['view' => $view->id, 'status' => Order::DONE, 'lane' => 'Review']))->assertOk();
        $this->office($admin)->get(route('office.settings'))->assertOk();
        $this->office($admin)->post(route('office.roles.save'), ['allow' => []])->assertSessionHas('status');
        $this->office($writer)->get(route('office.pool'))->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function paypalHeaders(string $payload, \OpenSSLAsymmetricKey $key): array
    {
        $id = 'tx-'.substr(md5($payload), 0, 8);
        $time = (string) time();
        openssl_sign($id.'|'.$time.'|WH|'.sprintf('%u', crc32($payload)), $raw, $key, OPENSSL_ALGO_SHA256);

        return [
            'CONTENT_TYPE' => 'application/json',
            'PayPal-Transmission-Id' => $id,
            'PayPal-Transmission-Time' => $time,
            'PayPal-Transmission-Sig' => base64_encode($raw),
            'PayPal-Cert-Url' => 'https://api.sandbox.paypal.com/cert.pem',
        ];
    }

    private function office(Employee $employee)
    {
        return $this->actingAs($employee, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()]);
    }

    private function staff(int $group, string $email, int $category = 2): Employee
    {
        return Employee::query()->create([
            'email' => $email,
            'employee_group' => $group,
            'first_name' => 'Role',
            'last_name' => 'User',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => 1,
            'two_factor_type' => 'google',
            'two_factor_gsecret' => 'JBSWY3DPEHPK3PXP',
            'writer_category' => $category,
            'reg_date' => now(),
        ]);
    }

    private function customer(string $email, float $wallet = 0): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Branch',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'wallet_balance' => $wallet,
            'referrer_id' => 0,
            'affiliate_used' => 0,
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
        $order->title = 'Branch order';
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
