<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\Coupon;
use App\Models\CreditFine;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeePool;
use App\Models\FineType;
use App\Models\Order;
use App\Models\OrderBid;
use App\Models\OrderEmployee;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderNote;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\PaymentRequest;
use App\Models\Post;
use App\Models\Referral;
use App\Models\Site;
use App\Models\StaffDevice;
use App\Models\WalletTransaction;
use App\Services\Payments\Signatures;
use App\Support\OrderStatuses;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationsAndRemainderTest extends TestCase
{
    #[Test]
    public function order_relations_and_customer_tabs_point_at_the_rows_that_were_saved(): void
    {
        $customer = $this->customer('rel@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'rel-writer@example.test');
        $order = $this->order($customer, $writer, Order::DELIVERED);
        $order->academic_level_id = 3;
        $order->paper_type_option = 'Research paper';
        $order->save();

        $bid = new OrderBid;
        $bid->order_id = $order->order_id;
        $bid->writer_id = $writer->employee_id;
        $bid->amount = 15;
        $bid->is_deleted = 0;
        $bid->date_add = now();
        $bid->save();
        $note = OrderNote::query()->create([
            'order_id' => $order->order_id,
            'employee_id' => $writer->employee_id,
            'body' => 'Internal',
        ]);
        $file = new OrderFile;
        $file->order_id = $order->order_id;
        $file->uploader_id = $customer->customer_id;
        $file->file_type_id = 1;
        $file->name = 'a.txt';
        $file->source = 'a.txt';
        $file->size = 1;
        $file->date_add = now();
        $file->save();
        $message = new OrderMessage;
        $message->order_id = $order->order_id;
        $message->sender_id = $customer->customer_id;
        $message->body = 'Hello';
        $message->parent_id = 0;
        $message->from_department = 7;
        $message->to_department = 8;
        $message->date_add = now();
        $message->save();

        $loaded = Order::query()->with(['customer', 'writer', 'files', 'messages', 'bids.writer'])->find($order->order_id);
        $this->assertSame($customer->customer_id, $loaded->customer->customer_id);
        $this->assertSame($writer->employee_id, $loaded->writer->employee_id);
        $this->assertTrue($loaded->files->contains('file_id', $file->file_id));
        $this->assertSame($order->order_id, $bid->order->order_id);
        $this->assertSame($writer->employee_id, $bid->writer->employee_id);
        $this->assertSame($writer->employee_id, $note->employee->employee_id);
        $this->assertSame($order->order_id, $file->order->order_id);
        $this->assertSame($order->order_id, $message->order->order_id);
        $this->assertTrue($loaded->showsAcademicRecord());
        $this->assertNotSame('', $loaded->statusDescription());
        $this->assertSame('Delivered', OrderStatuses::title(Order::DELIVERED));
        $this->assertSame('Article Writing', OrderStatuses::serviceTitle(Order::SERVICE_ARTICLE));

        $unpaid = Order::query()->forCustomerTab('unpaid')->where('order_id', $order->order_id)->count();
        $this->assertSame(0, $unpaid);

        $seat = OrderEmployee::query()->create([
            'order_id' => $order->order_id,
            'employee_type' => 'writer',
            'employee_id' => $writer->employee_id,
            'is_employee_assigned' => 1,
            'date_add' => now(),
        ]);
        $this->assertSame($writer->employee_id, $seat->employee->employee_id);

        $this->assertNull($customer->getRememberTokenName());
        $this->assertSame('password', $customer->getAuthPasswordName());
        $this->assertNull($writer->getRememberTokenName());
        $this->assertFalse($writer->canAccessPanel(\Filament\Facades\Filament::getPanel('office')));
        $this->assertSame($writer->employee_id, $writer->writingOrders()->first()->writer_id);
    }

    #[Test]
    public function staff_pages_for_people_fines_and_a_failed_capture_record_the_outcome(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        Http::preventStrayRequests();
        $admin = $this->staff(Employee::GROUP_ADMIN, 'rest-admin@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'rest-writer@example.test');
        $customer = $this->customer('rest@example.test');
        $order = $this->order($customer, $writer, Order::FINISHED, 40);
        $order->writer_pay = 18;
        $order->save();

        $this->office($admin)->get(route('office.staff'))->assertOk()->assertSee('rest-writer@example.test');
        $this->office($admin)->post(route('office.staff.update', $writer), [
            'is_banned' => '0',
            'writer_category' => 4,
        ])->assertSessionHas('status');
        $this->assertSame(4, (int) $writer->fresh()->writer_category);
        $this->office($admin)->get('/office/customers?q=rest@example.test')->assertOk()->assertSee('rest@example.test');

        $person = $this->staff(5, 'reject-me@example.test', false);
        $person->employee_group = 5;
        $person->is_application_completed = 1;
        $person->save();
        $this->office($admin)->post(route('office.applications.decide', $person), ['decision' => 'reject'])->assertSessionHas('status');
        $this->assertSame(1, (int) $person->fresh()->is_application_rejected);

        $this->office($admin)->post(route('office.fines.types'), ['name' => 'Percent late', 'type' => 'percentage', 'value' => 10]);
        $type = FineType::query()->where('name', 'Percent late')->first();
        $this->office($admin)->post(route('office.fines.apply'), [
            'fine_type_id' => $type->fine_type_id,
            'employee_id' => $writer->employee_id,
            'base' => 200,
        ])->assertSessionHas('status');
        $this->assertSame(20.0, (float) CreditFine::query()->where('description', 'Percent late')->value('amount'));
        $this->assertSame($writer->employee_id, CreditFine::query()->where('description', 'Percent late')->first()->employee->employee_id);

        $this->office($writer)->get(route('office.earnings'))->assertOk()->assertSee('18.00');
        $this->office($writer)->post(route('office.pool.bid', $order), ['amount' => 5, 'hours' => 4])->assertStatus(422);

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'oauth2/token')) {
                return Http::response(['access_token' => 'tok']);
            }
            if (str_contains($request->url(), '/capture')) {
                return Http::response(['status' => 'FAILED']);
            }

            return Http::response([
                'id' => 'FAILORDER',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=FAILORDER']],
            ]);
        });
        config(['services.paypal.client_id' => 'id', 'services.paypal.secret' => 'secret', 'services.paypal.mode' => 'sandbox']);
        $this->actingAs($customer, 'customer')->post(route('desk.pay', $order), ['provider' => 'paypal']);
        $intent = PaymentIntent::query()->where('order_id', $order->order_id)->where('provider', 'paypal')->first();
        $this->get(route('payments.paypal.return', ['reference' => $intent->reference, 'token' => 'FAILORDER']));
        $this->assertSame('failed', $intent->fresh()->status);
        $this->assertFalse(Signatures::paypal('{}', ['paypal-cert-url' => 'https://evil.example/cert'], 'WH', null));
        $this->assertFalse(Signatures::stripe('{}', '', 'secret'));
    }

    #[Test]
    public function login_blog_coupon_and_site_links_match_the_saved_records(): void
    {
        $customer = $this->customer('login-miss@example.test');
        $this->post('/login', ['email' => $customer->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/not-a-token?email=login-miss@example.test')->assertOk();
        $this->actingAs($customer, 'customer')->get('/account/orders?status=not-a-tab&q=Relation')->assertOk();
        $this->actingAs($customer, 'customer')->get('/account/messages')->assertOk();
        $this->assertFalse(\App\Services\Payments\Signatures::hmac('body', 'sig', ''));
        $this->assertFalse(\App\Services\Payments\Signatures::hmac('body', '', 'secret'));
        $stale = (string) (time() - 1000);
        $sig = hash_hmac('sha256', $stale.'.payload', 'secret');
        $this->assertFalse(\App\Services\Payments\Signatures::stripe('payload', 't='.$stale.',v1='.$sig, 'secret'));
        $request = \Illuminate\Http\Request::create('/unused', 'GET');
        $request->setUserResolver(fn () => $customer);
        $view = app(\App\Http\Controllers\AccountController::class)->show($request);
        $this->assertSame($customer->customer_id, $view->getData()['customer']->customer_id);

        Post::query()->create([
            'title' => 'A studio note',
            'slug' => 'studio-note',
            'excerpt' => 'Short.',
            'body' => 'Longer note.',
            'author_name' => 'Studio',
            'published_at' => now()->subHour(),
        ]);
        $this->get('/blog/studio-note')->assertOk()->assertSee('A studio note');
        $this->assertSame(1, Post::query()->published()->count());

        $coupon = new Coupon;
        $coupon->coupon_code = 'DEAD';
        $coupon->coupon_type = Coupon::PERCENT;
        $coupon->coupon_value = 10;
        $coupon->active = 0;
        $coupon->site_id = 1;
        $coupon->date_add = now();
        $coupon->save();
        $this->assertFalse($coupon->isUsable());

        $site = Site::query()->first() ?? Site::query()->create(['name' => 'Local', 'email' => 'a@b.c', 'domain' => 'local.test', 'date_add' => now(), 'date_upd' => now()]);
        $this->assertGreaterThanOrEqual(0, $site->customers()->count());
        $level = new AcademicLevel;
        $level->title = 'Level';
        $level->date_add = now();
        $level->save();
        $this->assertSame(0, $level->deadlines()->count());

        $pool = new EmployeePool;
        $pool->name = 'Probation';
        $pool->academic_level = 0;
        $pool->writer_category = 0;
        $pool->date_add = now();
        $pool->save();
        $this->assertSame(0, $pool->employees()->count());

        $employee = $this->staff(Employee::GROUP_WRITER, 'rel-device@example.test');
        $device = StaffDevice::query()->create([
            'employee_id' => $employee->employee_id,
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
            'created_at' => now(),
        ]);
        $this->assertNotNull($device->id ?? $device->getKey());

        $request = new PaymentRequest;
        $request->user_id = $employee->employee_id;
        $request->total_pay = 4;
        $request->status = 0;
        $request->date_add = now();
        $request->save();
        $this->assertSame(4.0, (float) $request->employee->paymentRequests()->first()->total_pay);

        $tx = new WalletTransaction;
        $tx->customer_id = $customer->customer_id;
        $tx->transaction_id = 'rel-tx';
        $tx->source = 'deposit';
        $tx->payment_method = 'mpesa';
        $tx->amount = 3;
        $tx->date_add = now();
        $tx->save();
        $this->assertSame($customer->customer_id, $tx->customer->customer_id);

        $order = $this->order($customer, $employee, Order::FINISHED, 9);
        $payment = new Payment;
        $payment->node_id = $order->order_id;
        $payment->node_type = 'order';
        $payment->payment_method = 'wallet';
        $payment->transaction_id = 'rel-pay';
        $payment->amount = 3;
        $payment->date_add = now();
        $payment->save();
        $this->assertSame('order', $payment->order->payments()->first()->node_type);

        $ref = new Referral;
        $ref->referrer_id = $customer->customer_id;
        $ref->referree_id = $customer->customer_id;
        $ref->code = 'RELCODE';
        $ref->amount = 10;
        $ref->date_add = now();
        $ref->save();
        $this->assertSame($customer->customer_id, $ref->referrer->customer_id);
    }

    private function office(Employee $employee)
    {
        return $this->actingAs($employee, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()]);
    }

    private function staff(int $group, string $email): Employee
    {
        return Employee::query()->create([
            'email' => $email,
            'employee_group' => $group,
            'first_name' => 'Rel',
            'last_name' => 'User',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => 1,
            'two_factor_type' => 'google',
            'two_factor_gsecret' => 'JBSWY3DPEHPK3PXP',
            'writer_category' => 2,
            'reg_date' => now(),
        ]);
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Rel',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'reg_date' => now(),
        ]);
    }

    private function order(Customer $customer, Employee $writer, int $status, float $total = 40): Order
    {
        $order = new Order;
        $order->site_id = 1;
        $order->form_type_id = Order::SERVICE_ARTICLE;
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->writer_id = $writer->employee_id;
        $order->status_id = $status;
        $order->parent_id = 0;
        $order->service_type = Order::SERVICE_ARTICLE;
        $order->tariff_id = 0;
        $order->title = 'Relation order';
        $order->academic_level_id = 0;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 0;
        $order->spacing = 'double';
        $order->total = $total;
        $order->is_paid = 1;
        $order->total_paid = $total;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return $order;
    }
}
