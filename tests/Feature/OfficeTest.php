<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\MpesaPayout;
use App\Models\Order;
use App\Models\OrderBid;
use App\Models\PaymentRequest;
use App\Services\Office\Totp;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficeTest extends TestCase
{
    #[Test]
    public function two_factor_is_required_before_the_office_opens(): void
    {
        $writer = $this->staff(Employee::GROUP_WRITER, 'setup-writer@example.test', false);

        $this->actingAs($writer, 'staff')
            ->get('/office')
            ->assertRedirect(route('office.setup'));

        $this->actingAs($writer, 'staff')->get('/office/security')->assertOk()->assertSee('Set up an authenticator');
        $secret = session('office.setup_secret');
        $this->post('/office/security', ['code' => Totp::at($secret)])
            ->assertRedirect('/office');

        $this->assertTrue((bool) $writer->fresh()->two_factor_enabled);
        $this->get('/office')->assertOk();
    }

    #[Test]
    public function a_configured_authenticator_must_match_before_entry(): void
    {
        $writer = $this->staff(Employee::GROUP_WRITER, 'challenge-writer@example.test', true);

        $this->actingAs($writer, 'staff')
            ->get('/office')
            ->assertRedirect(route('office.challenge'));

        $this->post('/office/challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/office/challenge', ['code' => Totp::at('JBSWY3DPEHPK3PXP')])
            ->assertRedirect('/office');
        $this->get('/office')->assertOk()->assertSee('Hello, Test User.');
    }

    #[Test]
    public function each_role_is_kept_to_its_permissions_and_customer_details_stay_hidden(): void
    {
        config(['legacy.read_only' => false]);
        $customer = Customer::query()->create([
            'google_analytics_id' => 'hidden-pii@example.test',
            'email' => 'hidden-pii@example.test',
            'name' => 'Private Client',
            'phone' => '0700111222',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5('hidden'),
            'reg_date' => now(),
        ]);
        $writer = $this->staff(Employee::GROUP_WRITER, 'writer-role@example.test');
        $editor = $this->staff(Employee::GROUP_EDITOR, 'editor-role@example.test');
        $sub = $this->staff(Employee::GROUP_SUB_ADMIN, 'sub-role@example.test');
        $admin = $this->staff(Employee::GROUP_ADMIN, 'admin-role@example.test');
        $order = $this->order($customer, $writer, Order::DONE);

        $this->office($writer)->get(route('office.orders.show', $order))
            ->assertOk()
            ->assertDontSee('hidden-pii@example.test')
            ->assertDontSee('0700111222')
            ->assertSee('Customer contact details are hidden');
        $this->office($writer)->get('/office/roles')->assertForbidden();
        $this->office($writer)->get('/office/customers')->assertForbidden();
        $this->office($writer)->get('/office/settings')->assertForbidden();

        $this->office($editor)->get(route('office.orders.show', $order))
            ->assertOk()
            ->assertDontSee('hidden-pii@example.test')
            ->assertSee('Return to the writer');
        $this->office($editor)->get('/office/customers')->assertForbidden();
        $this->office($editor)->get('/office/settings')->assertForbidden();

        $this->office($sub)->get('/office/roles')->assertForbidden();
        $this->office($sub)->get('/office/settings')->assertForbidden();
        $this->office($sub)->get('/office/customers')->assertOk()->assertSee('hidden-pii@example.test');

        $this->office($admin)->get(route('office.orders.show', $order))
            ->assertOk()
            ->assertSee('hidden-pii@example.test')
            ->assertSee('0700111222');
        $this->office($admin)->get('/office/roles')->assertOk()->assertSee('Sub-admin');
    }

    #[Test]
    public function a_writer_bid_can_be_approved_into_an_assignment(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('bid-customer@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'bidder@example.test');
        $admin = $this->staff(Employee::GROUP_ADMIN, 'bid-admin@example.test');
        $order = $this->order($customer, null, Order::ORDER_PUBLISHED);

        $this->office($writer)->post(route('office.pool.bid', $order), [
            'amount' => 40,
            'hours' => 36,
        ])->assertRedirect();

        $bid = OrderBid::query()->where('order_id', $order->order_id)->where('writer_id', $writer->employee_id)->first();
        $this->assertNotNull($bid);
        $this->assertSame(40.0, (float) $bid->amount);

        $this->office($admin)->post(route('office.orders.bids.approve', [$order, $bid]))->assertRedirect();
        $order->refresh();
        $this->assertSame((int) $writer->employee_id, (int) $order->writer_id);
        $this->assertSame(Order::WRITER_ASSIGNED, (int) $order->status_id);
        $this->assertTrue((bool) $bid->fresh()->is_assigned);
    }

    #[Test]
    public function an_editor_can_return_work_and_then_approve_it(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('edit-customer@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'edit-writer@example.test');
        $editor = $this->staff(Employee::GROUP_EDITOR, 'edit-editor@example.test');
        $order = $this->order($customer, $writer, Order::DONE);

        $this->office($editor)->post(route('office.orders.return', $order), [
            'body' => 'The opening does not match the brief. Please rewrite the first paragraph.',
        ])->assertRedirect();
        $this->assertSame(Order::REVISION, (int) $order->fresh()->status_id);

        $fresh = $order->fresh();
        $fresh->status_id = Order::DONE;
        $fresh->save();
        $this->office($editor)->post(route('office.orders.approve', $fresh))->assertRedirect();
        $this->assertSame(Order::DELIVERED, (int) $order->fresh()->status_id);
    }

    #[Test]
    public function a_payout_request_is_stored_and_not_sent_while_safe_mode_is_on(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => true]);
        $writer = $this->staff(Employee::GROUP_WRITER, 'payout-writer@example.test');

        $this->office($writer)->post('/office/earnings', [
            'phone' => '0712345678',
            'amount' => 25,
        ])->assertRedirect()->assertSessionHas('status');

        $request = PaymentRequest::query()->where('user_id', $writer->employee_id)->first();
        $this->assertNotNull($request);
        $this->assertSame(0, (int) $request->status);
        $payout = MpesaPayout::query()->where('payment_request_id', $request->payment_request_id)->first();
        $this->assertNotNull($payout);
        $this->assertFalse((bool) $payout->is_paid);
        $this->assertStringContainsString('safe-mode', (string) $payout->transaction_id);
    }

    #[Test]
    public function read_only_mode_blocks_a_writer_bid(): void
    {
        $customer = $this->customer('ro-customer@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'ro-writer@example.test');
        $order = $this->order($customer, null, Order::ORDER_PUBLISHED);
        config(['legacy.read_only' => true]);

        $this->office($writer)->post(route('office.pool.bid', $order), [
            'amount' => 10,
            'hours' => 12,
        ])->assertSessionHasErrors('legacy');

        $this->assertDatabaseMissing('prox_order_bid', [
            'order_id' => $order->order_id,
            'writer_id' => $writer->employee_id,
        ]);
    }

    private function office(Employee $employee)
    {
        return $this->actingAs($employee, 'staff')->withSession([
            'office.2fa' => true,
            'office.seen' => time(),
        ]);
    }

    private function staff(int $group, string $email, bool $twoFactor = true): Employee
    {
        return Employee::query()->create([
            'email' => $email,
            'employee_group' => $group,
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => $twoFactor ? 1 : 0,
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
            'name' => 'Client',
            'phone' => '0700000000',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'reg_date' => now(),
        ]);
    }

    private function order(Customer $customer, ?Employee $writer, int $status): Order
    {
        $order = new Order;
        $order->site_id = 1;
        $order->form_type_id = Order::SERVICE_ARTICLE;
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->writer_id = $writer?->employee_id;
        $order->status_id = $status;
        $order->parent_id = 0;
        $order->service_type = Order::SERVICE_ARTICLE;
        $order->tariff_id = 0;
        $order->title = 'Office order';
        $order->academic_level_id = 0;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 0;
        $order->spacing = 'double';
        $order->paper_details = 'A professional brief for the office test.';
        $order->total = 80;
        $order->raw_cost = 80;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return $order;
    }
}
