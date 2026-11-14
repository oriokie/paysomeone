<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\CreditFine;
use App\Models\Customer;
use App\Models\Deadline;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\OrderRevision;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\PaymentRequest;
use App\Models\ProjectRequest;
use App\Models\Referral;
use App\Models\Site;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsedModelBehaviorTest extends TestCase
{
    #[Test]
    public function the_order_page_loads_the_relations_the_desk_and_office_actually_read(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('model-customer@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'model-writer@example.test');
        $editor = $this->staff(Employee::GROUP_EDITOR, 'model-editor@example.test');
        $admin = $this->staff(Employee::GROUP_ADMIN, 'model-admin@example.test');
        $deadline = Deadline::query()->create([
            'site_id' => 1,
            'name' => '3 days',
            'price_per_page' => 0.04,
            'hrs_customer' => 72,
            'hrs_writer' => 48,
            'min_pages' => 1,
            'is_article_writing' => 0,
            'is_academic_writing' => 0,
            'date_add' => now(),
            'date_upd' => now(),
        ]);
        $order = new Order;
        $order->site_id = 1;
        $order->form_type_id = Order::SERVICE_PAGES;
        $order->form_type = 'editing';
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->writer_id = $writer->employee_id;
        $order->editor_id = $editor->employee_id;
        $order->status_id = Order::DELIVERED;
        $order->parent_id = 0;
        $order->service_type = Order::SERVICE_PAGES;
        $order->tariff_id = $deadline->deadline_id;
        $order->title = 'Edited brief';
        $order->academic_level_id = 2;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 1;
        $order->spacing = 'double';
        $order->total = 30;
        $order->is_paid = 1;
        $order->total_paid = 30;
        $order->order_type = 'academic';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        $this->assertSame('Academic Writing', $order->serviceLabel());
        $professional = $order->replicate();
        $professional->form_type = 'articles';
        $professional->academic_level_id = 0;
        $professional->service_type = Order::SERVICE_ARTICLE;
        $this->assertSame('Article writing', $professional->serviceLabel());
        $this->assertSame($deadline->deadline_id, $order->deadline->deadline_id);
        $this->assertTrue($deadline->orders()->where('order_id', $order->order_id)->exists());
        $this->assertSame($editor->employee_id, $order->editor->employee_id);

        $this->actingAs($customer, 'customer')->get(route('desk.order', $order))->assertOk()->assertSee('Edited brief');
        $this->actingAs($admin, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->get(route('office.orders.show', $order))->assertOk()->assertSee('Edited brief');

        $rating = new OrderRating;
        $rating->order_id = $order->order_id;
        $rating->user_type = 'customer';
        $rating->user_id = $customer->customer_id;
        $rating->service_rating = 4;
        $rating->paper_rating = 4;
        $rating->date_add = now();
        $rating->save();
        $this->assertSame($customer->customer_id, $rating->customer->customer_id);
        $this->assertSame($order->order_id, $rating->order->order_id);
        $this->assertSame(4, (int) $customer->ratings()->first()->service_rating);

        $revision = new OrderRevision;
        $revision->order_id = $order->order_id;
        $revision->file_id = 0;
        $revision->instructions = 'Open with the result.';
        $revision->client_deadline = 24;
        $revision->date_add = now();
        $revision->save();
        $this->assertSame($order->order_id, $revision->order->order_id);
        $this->assertNull($revision->file);

        $tx = new WalletTransaction;
        $tx->customer_id = $customer->customer_id;
        $tx->transaction_id = 'model-tx';
        $tx->source = 'deposit';
        $tx->payment_method = 'mpesa';
        $tx->amount = 4;
        $tx->date_add = now();
        $tx->save();
        $this->assertSame('model-tx', $customer->walletTransactions()->first()->transaction_id);
        $this->assertSame($order->order_id, $customer->orders()->first()->order_id);
    }

    #[Test]
    public function coupons_configuration_and_referrals_follow_the_rules_the_pricing_code_uses(): void
    {
        if (! Site::query()->where('site_id', 1)->exists()) {
            Site::query()->create(['site_id' => 1, 'name' => 'Local', 'email' => 'a@b.c', 'domain' => 'local.test', 'date_add' => now(), 'date_upd' => now()]);
        }
        $expired = new Coupon;
        $expired->coupon_code = 'OLD';
        $expired->coupon_type = Coupon::AMOUNT;
        $expired->coupon_value = 5;
        $expired->active = 1;
        $expired->one_time = 0;
        $expired->expiry = now()->subDay();
        $expired->site_id = 1;
        $expired->date_add = now();
        $expired->save();
        $this->assertFalse($expired->isUsable());
        $this->assertSame(1, (int) $expired->site->site_id);

        $spent = new Coupon;
        $spent->coupon_code = 'ONCE';
        $spent->coupon_type = Coupon::PERCENT;
        $spent->coupon_value = 10;
        $spent->active = 1;
        $spent->one_time = 1;
        $spent->order_id = Order::query()->value('order_id') ?: 1;
        $spent->site_id = 1;
        $spent->date_add = now();
        $spent->save();
        $this->assertFalse($spent->isUsable());

        $this->assertSame('fallback', Configuration::read('DOES_NOT_EXIST', 'fallback'));
        $row = new Configuration;
        $row->name = 'ORDER_AUTOINCREMENT';
        $row->value = '';
        $row->site_id = 1;
        $row->date_add = now();
        $row->date_upd = now();
        $row->save();
        $this->assertSame(2, Configuration::read('ORDER_AUTOINCREMENT', 2));
        $row->value = '4';
        $row->save();
        $this->assertSame('4', Configuration::read('ORDER_AUTOINCREMENT', 2));
        config(['legacy.admin_site_id' => 99]);
        $this->assertSame('4', Configuration::read('ORDER_AUTOINCREMENT', 2));

        $referrer = $this->customer('referrer@example.test');
        $referee = $this->customer('referee@example.test');
        $referral = new Referral;
        $referral->referrer_id = $referrer->customer_id;
        $referral->referree_id = $referee->customer_id;
        $referral->code = 'MODELREF';
        $referral->amount = 10;
        $referral->date_add = now();
        $referral->save();
        $this->assertSame($referee->customer_id, $referral->referee->customer_id);

        $writer = $this->staff(Employee::GROUP_WRITER, 'fine-writer@example.test');
        $owned = new Order;
        $owned->site_id = 1;
        $owned->form_type_id = Order::SERVICE_ARTICLE;
        $owned->gmt = 0;
        $owned->customer_id = $referee->customer_id;
        $owned->status_id = Order::FINISHED;
        $owned->parent_id = 0;
        $owned->service_type = Order::SERVICE_ARTICLE;
        $owned->tariff_id = 0;
        $owned->title = 'Fine order';
        $owned->academic_level_id = 0;
        $owned->paper_type_id = 0;
        $owned->topic_category_id = 0;
        $owned->paper_format_id = 0;
        $owned->spacing = 'double';
        $owned->total = 10;
        $owned->order_type = 'article';
        $owned->date_add = now();
        $owned->date_upd = now();
        $owned->save();
        $fine = new CreditFine;
        $fine->type = 'fine';
        $fine->employee_id = $writer->employee_id;
        $fine->order_id = $owned->order_id;
        $fine->amount = 3;
        $fine->description = 'Late';
        $fine->date_add = now();
        $fine->save();
        $this->assertSame((int) $fine->order_id, $fine->order->order_id);

        $note = new Notification;
        $note->to_department = '7';
        $note->to_user_id = $referee->customer_id;
        $note->from_department = '8';
        $note->from_user_id = 0;
        $note->action = 'order';
        $note->node_type = 'order';
        $note->node_url = '1';
        $note->message = 'Saved';
        $note->is_seen = 0;
        $note->date_add = now();
        $note->date_upd = now();
        $note->save();
        $this->assertSame($referee->customer_id, $note->customer->customer_id);

        $site = Site::query()->first();
        $this->assertGreaterThanOrEqual(0, $site->orders()->count());
        $this->assertGreaterThanOrEqual(0, $site->coupons()->count());

        $payout = new PaymentRequest;
        $payout->user_id = $writer->employee_id;
        $payout->total_pay = 6;
        $payout->status = 0;
        $payout->date_add = now();
        $payout->save();
        $this->assertSame(0, $payout->mpesaPayouts()->count());

        $payment = new Payment;
        $payment->node_id = Order::query()->value('order_id');
        $payment->node_type = 'order';
        $payment->payment_method = 'paypal';
        $payment->transaction_id = 'model-pay';
        $payment->amount = 1;
        $payment->currency_id = 0;
        $payment->date_add = now();
        $payment->save();
        $this->assertNull($payment->currency);

        $intent = PaymentIntent::query()->create([
            'customer_id' => $referee->customer_id,
            'order_id' => $owned->order_id,
            'provider' => 'mpesa',
            'purpose' => 'order',
            'reference' => 'model-intent',
            'amount' => 10,
            'status' => 'paid',
            'transaction_id' => 'MODELINTENT',
        ]);
        $this->actingAs($referee, 'customer')->get(route('desk.payments.show', $intent))->assertOk()->assertSee('model-intent');
        $this->assertSame($owned->order_id, $intent->order->order_id);
        $this->assertSame($referee->customer_id, $intent->customer->customer_id);

        $brief = ProjectRequest::query()->create([
            'customer_id' => $referee->customer_id,
            'service' => 'articles',
            'name' => 'Referee',
            'email' => 'referee@example.test',
            'title' => 'A brief',
            'brief' => 'Professional copy.',
        ]);
        $this->assertSame($referee->customer_id, $brief->customer->customer_id);
        $this->assertSame(0.0, (float) $brief->quote_total);
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Model',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'reg_date' => now(),
        ]);
    }

    private function staff(int $group, string $email): Employee
    {
        return Employee::query()->create([
            'email' => $email,
            'employee_group' => $group,
            'first_name' => 'Model',
            'last_name' => 'Staff',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => 1,
            'two_factor_type' => 'google',
            'two_factor_gsecret' => 'JBSWY3DPEHPK3PXP',
            'writer_category' => 4,
            'reg_date' => now(),
        ]);
    }
}
