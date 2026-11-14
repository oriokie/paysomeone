<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MpesaPayout;
use App\Models\Order;
use App\Models\OrderTip;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\PaymentRequest;
use App\Models\WalletTransaction;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\MpesaB2c;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentDriversTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    #[Test]
    public function an_mpesa_prompt_stores_the_checkout_id_and_a_bad_number_does_not(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.mpesa.consumer_key' => 'key',
            'services.mpesa.consumer_secret' => 'secret',
            'services.mpesa.shortcode' => '174379',
            'services.mpesa.passkey' => 'pass',
            'services.mpesa.callback_secret' => 'callback-secret',
            'services.mpesa.env' => 'sandbox',
        ]);
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'oauth')) {
                return Http::response(['access_token' => 'tok']);
            }

            return Http::response(['CheckoutRequestID' => 'ws_CO_new']);
        });
        $customer = $this->customer('stk@example.test');
        $order = $this->order($customer, 22);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $order), ['provider' => 'mpesa', 'phone' => '0712345678'])
            ->assertRedirect()
            ->assertSessionHas('pay_phase', 'waiting');

        $intent = PaymentIntent::query()->where('order_id', $order->order_id)->first();
        $this->assertSame('ws_CO_new', $intent->reference);
        $this->assertSame('pending', $intent->status);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $order), ['provider' => 'mpesa', 'phone' => '123'])
            ->assertSessionHas('pay_phase', 'failed');
        $this->assertSame(1, PaymentIntent::query()->where('order_id', $order->order_id)->count());
    }

    #[Test]
    public function mpesa_refuses_to_start_when_safaricom_does_not_issue_a_token(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.mpesa.consumer_key' => 'key',
            'services.mpesa.consumer_secret' => 'secret',
            'services.mpesa.shortcode' => '174379',
            'services.mpesa.passkey' => 'pass',
        ]);
        Http::fake(['sandbox.safaricom.co.ke/*' => Http::response([], 401)]);
        $customer = $this->customer('stk-token@example.test');
        $order = $this->order($customer, 10);

        $this->actingAs($customer, 'customer')
            ->from(route('desk.order', $order))
            ->post(route('desk.pay', $order), ['provider' => 'mpesa', 'phone' => '254712345678'])
            ->assertSessionHas('pay_phase', 'failed');
        $this->assertSame('failed', PaymentIntent::query()->where('order_id', $order->order_id)->value('status'));
    }

    #[Test]
    public function paypal_capture_on_return_pays_once_and_cancel_does_not(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.paypal.client_id' => 'id',
            'services.paypal.secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'oauth2/token')) {
                return Http::response(['access_token' => 'tok']);
            }
            if (str_contains($url, '/capture')) {
                return Http::response([
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => ['captures' => [[
                            'id' => 'CAP9',
                            'status' => 'COMPLETED',
                            'amount' => ['value' => '30.00'],
                        ]]],
                    ]],
                ]);
            }

            return Http::response([
                'id' => 'PAYORDER1',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYORDER1']],
            ]);
        });
        $customer = $this->customer('paypal-return@example.test');
        $order = $this->order($customer, 30);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $order), ['provider' => 'paypal'])
            ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYORDER1');
        $intent = PaymentIntent::query()->where('order_id', $order->order_id)->first();
        $this->assertSame('PAYORDER1', $intent->payload['paypal_order_id']);

        $this->get(route('payments.paypal.return', ['reference' => $intent->reference, 'token' => 'PAYORDER1']))
            ->assertRedirect(route('desk.order', $order));
        $this->assertSame('paid', $intent->fresh()->status);
        $this->assertSame(1, Payment::query()->where('transaction_id', 'CAP9')->where('payment_method', 'paypal')->count());
        $this->assertTrue((bool) $order->fresh()->is_paid);

        $second = $this->order($customer, 12);
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'oauth2/token')) {
                return Http::response(['access_token' => 'tok']);
            }

            return Http::response([
                'id' => 'PAYORDER2',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYORDER2']],
            ]);
        });
        $this->actingAs($customer, 'customer')->post(route('desk.pay', $second), ['provider' => 'paypal']);
        $other = PaymentIntent::query()->where('order_id', $second->order_id)->first();
        $this->get(route('payments.paypal.cancel', ['reference' => $other->reference]))
            ->assertRedirect(route('desk.order', $second));
        $this->assertSame('cancelled', $other->fresh()->status);
        $this->assertFalse((bool) $second->fresh()->is_paid);
    }

    #[Test]
    public function pesapal_and_stripe_start_only_hand_the_customer_a_checkout_link(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.pesapal.consumer_key' => 'key',
            'services.pesapal.consumer_secret' => 'secret',
            'services.pesapal.ipn_id' => 'ipn',
            'services.stripe.secret' => 'sk_test',
        ]);
        Http::fake([
            'cybqa.pesapal.com/*' => function ($request) {
                if (str_contains($request->url(), 'RequestToken')) {
                    return Http::response(['token' => 'tok']);
                }

                return Http::response(['redirect_url' => 'https://cybqa.pesapal.com/pay/abc']);
            },
            'api.stripe.com/*' => Http::response(['url' => 'https://checkout.stripe.com/c/pay/cs_test']),
        ]);
        $customer = $this->customer('redirects@example.test');
        $pesa = $this->order($customer, 14);
        $card = $this->order($customer, 16);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $pesa), ['provider' => 'pesapal'])
            ->assertRedirect('https://cybqa.pesapal.com/pay/abc');
        $this->assertSame('pending', PaymentIntent::query()->where('order_id', $pesa->order_id)->value('status'));

        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $card), ['provider' => 'stripe'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');
        $cardIntent = PaymentIntent::query()->where('order_id', $card->order_id)->first();
        $this->get(route('payments.stripe.cancel', ['reference' => $cardIntent->reference]))
            ->assertRedirect(route('desk.order', $card));
        $this->assertSame('cancelled', $cardIntent->fresh()->status);
    }

    #[Test]
    public function the_wallet_pays_an_order_and_a_tip_and_refuses_a_short_balance(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        $customer = $this->customer('wallet-spend@example.test', 50);
        $order = $this->order($customer, 20);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $order), ['provider' => 'wallet'])
            ->assertSessionHas('pay_phase', 'success');
        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertSame(30.0, (float) $customer->fresh()->wallet_balance);
        $this->assertSame('order', WalletTransaction::query()->where('customer_id', $customer->customer_id)->value('source'));
        $this->assertSame('wallet', Payment::query()->where('node_id', $order->order_id)->value('payment_method'));

        $order->status_id = Order::DELIVERED;
        $order->save();
        $this->actingAs($customer, 'customer')
            ->post(route('desk.tip.store', $order), ['amount' => 5])
            ->assertRedirect();
        $this->assertSame(25.0, (float) $customer->fresh()->wallet_balance);
        $this->assertSame(5.0, (float) OrderTip::query()->where('order_id', $order->order_id)->value('amount'));
        $this->assertSame('tip', Payment::query()->where('node_type', 'tip')->where('node_id', $order->order_id)->value('node_type'));

        $big = $this->order($customer, 100);
        $this->actingAs($customer, 'customer')
            ->post(route('desk.pay', $big), ['provider' => 'wallet'])
            ->assertSessionHas('pay_phase', 'failed');
        $this->assertFalse((bool) $big->fresh()->is_paid);
    }

    #[Test]
    public function b2c_uses_the_mock_or_a_faked_daraja_response_and_ignores_a_failed_result(): void
    {
        config([
            'legacy.safe_mode' => false,
            'services.mpesa.consumer_key' => 'key',
            'services.mpesa.consumer_secret' => 'secret',
            'services.mpesa.shortcode' => '600000',
            'services.mpesa.initiator' => 'testapi',
            'services.mpesa.security_credential' => 'cred',
            'services.mpesa.b2c_mock' => false,
            'services.mpesa.env' => 'sandbox',
        ]);
        $request = new PaymentRequest;
        $request->user_id = 1;
        $request->total_pay = 10;
        $request->status = 0;
        $request->date_add = now();
        $request->date_upd = now();
        $request->save();
        $b2c = app(MpesaB2c::class);

        $badPhone = $b2c->send($request, '123', 10);
        $this->assertFalse($badPhone['ok']);

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'oauth')) {
                return Http::response(['access_token' => 'tok']);
            }

            return Http::response(['ResponseCode' => '0', 'ConversationID' => 'AG_1']);
        });
        $sent = $b2c->send($request, '0712345678', 10);
        $this->assertTrue($sent['ok']);
        $this->assertSame('AG_1', $sent['conversation']);
        $this->assertFalse($b2c->reconcile(['Result' => ['ResultCode' => 1]]));

        config(['services.mpesa.b2c_mock' => true]);
        $mock = $b2c->send($request, '254712345678', 10);
        $this->assertSame('mock-'.$request->payment_request_id, $mock['conversation']);
        $this->assertSame(0, MpesaPayout::query()->where('transaction_id', 'AG_1')->count());
    }

    #[Test]
    public function read_only_mode_refuses_a_wallet_payment_before_any_row_is_written(): void
    {
        $customer = $this->customer('paused-pay@example.test', 40);
        $order = $this->order($customer, 10);
        config(['legacy.read_only' => true]);

        $message = app(CheckoutService::class)->payWithWallet($order, $customer);
        $this->assertStringContainsString('paused', $message);
        $this->assertSame(0, Payment::query()->where('node_id', $order->order_id)->count());
    }

    private function customer(string $email, float $wallet = 0): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Payer',
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
        $order->title = 'Payable order';
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
