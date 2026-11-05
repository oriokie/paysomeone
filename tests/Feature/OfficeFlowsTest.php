<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditFine;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FineType;
use App\Models\GatewaySetting;
use App\Models\News;
use App\Models\OfficeSetting;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderNote;
use App\Models\RecoveryCode;
use App\Services\Office\Totp;
use App\Support\Departments;
use App\Support\Office\Gateways;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficeFlowsTest extends TestCase
{
    #[Test]
    public function an_applicant_cannot_sign_in_and_a_recovery_code_works_once(): void
    {
        $applicant = $this->staff(5, 'applicant@example.test', false);
        $this->post('/office/login', ['email' => $applicant->email, 'password' => 'long-enough-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('staff');

        $writer = $this->staff(Employee::GROUP_WRITER, 'recover@example.test');
        RecoveryCode::query()->create([
            'employee_id' => $writer->employee_id,
            'code_hash' => hash('sha256', 'RECOVERCODE'),
            'created_at' => now(),
        ]);
        $this->actingAs($writer, 'staff')->post('/office/challenge', ['code' => 'RECOVERCODE'])->assertRedirect('/office');
        $this->assertNotNull(RecoveryCode::query()->where('employee_id', $writer->employee_id)->value('used_at'));
        $this->assertSame(1, AuditLog::query()->where('action', 'auth.recovery')->where('employee_id', $writer->employee_id)->count());

        $this->post('/office/logout')->assertRedirect(route('office.login'));
        $this->actingAs($writer, 'staff')->withSession(['office.2fa' => false])
            ->post('/office/challenge', ['code' => 'RECOVERCODE'])
            ->assertSessionHasErrors('code');
    }

    #[Test]
    public function a_timed_out_session_and_a_suspended_account_are_sent_back_to_login(): void
    {
        $writer = $this->staff(Employee::GROUP_WRITER, 'timeout@example.test');
        $this->actingAs($writer, 'staff')->withSession([
            'office.2fa' => true,
            'office.seen' => time() - 4000,
        ])->get('/office')->assertRedirect(route('office.login'));

        $banned = $this->staff(Employee::GROUP_EDITOR, 'banned@example.test');
        $banned->is_banned = 1;
        $banned->save();
        $this->actingAs($banned, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->get('/office')
            ->assertRedirect(route('office.login'));
    }

    #[Test]
    public function view_as_is_read_only_and_a_sub_admin_cannot_cancel_above_the_limit(): void
    {
        config(['legacy.read_only' => false]);
        $admin = $this->staff(Employee::GROUP_ADMIN, 'view-admin@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'view-writer@example.test');
        $customer = $this->customer('view-customer@example.test');
        $order = $this->order($customer, null, Order::ORDER_PUBLISHED, 400);
        OfficeSetting::query()->updateOrCreate(['key' => 'subadmin_refund_limit'], ['value' => '150']);

        $this->office($admin)->post(route('office.view-as', $writer))->assertRedirect();
        $this->assertSame($writer->employee_id, session('office.view_as'));
        $this->office($admin)->get('/office')->assertOk()->assertSee('cannot change anything');
        $this->office($admin)->post(route('office.pool.bid', $order), ['amount' => 10, 'hours' => 12])->assertForbidden();
        $this->office($admin)->post(route('office.view-as.stop'))->assertRedirect();
        $this->assertNull(session('office.view_as'));

        $sub = $this->staff(Employee::GROUP_SUB_ADMIN, 'limit-sub@example.test');
        $this->office($sub)->post(route('office.orders.cancel', $order))->assertForbidden();
        $this->assertNotSame(Order::CANCELLED, (int) $order->fresh()->status_id);
        $small = $this->order($customer, null, Order::WAITING_FOR_PAYMENT, 40);
        $this->office($sub)->post(route('office.orders.cancel', $small))->assertRedirect();
        $this->assertSame(Order::CANCELLED, (int) $small->fresh()->status_id);
    }

    #[Test]
    public function gateways_fines_news_reports_and_the_board_save_what_the_admin_submits(): void
    {
        config(['legacy.read_only' => false]);
        $admin = $this->staff(Employee::GROUP_ADMIN, 'ops-admin@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'fined-writer@example.test');
        $customer = $this->customer('board-customer@example.test');
        $order = $this->order($customer, $writer, Order::WRITER_ASSIGNED, 80);

        $this->office($admin)->get(route('office.gateways'))->assertOk()->assertSee('M-Pesa');
        $this->office($admin)->post(route('office.gateways.update'), [
            'gateway' => 'mpesa',
            'enabled' => '1',
            'test_mode' => '1',
            'currency' => 'kes',
            'fee_percent' => '1.5',
            'fee_fixed' => '0',
        ])->assertRedirect();
        $this->assertTrue((bool) GatewaySetting::query()->find('mpesa')->enabled);
        $this->assertSame('KES', GatewaySetting::query()->find('mpesa')->currency);
        $this->assertSame('Not set', Gateways::mask('GATEWAY_KEY_THAT_IS_NOT_CONFIGURED'));

        $this->office($admin)->post(route('office.fines.types'), [
            'name' => 'Late delivery',
            'type' => 'amount',
            'value' => 8,
        ])->assertSessionHas('status');
        $type = FineType::query()->where('name', 'Late delivery')->first();
        $this->office($admin)->post(route('office.fines.apply'), [
            'fine_type_id' => $type->fine_type_id,
            'employee_id' => $writer->employee_id,
            'order_id' => $order->order_id,
            'base' => 0,
        ])->assertSessionHas('status');
        $fine = CreditFine::query()->where('employee_id', $writer->employee_id)->first();
        $this->assertSame(8.0, (float) $fine->amount);
        $this->office($writer)->post(route('office.fines.appeal', $fine), ['reason' => 'The file was delivered before the deadline.'])
            ->assertRedirect();
        $this->assertSame(1, (int) $fine->fresh()->requested);

        $this->office($admin)->post(route('office.news.store'), [
            'title' => 'Pool opens at nine',
            'content' => 'New briefs are published at 09:00.',
            'recipient' => 'writers',
        ])->assertSessionHas('status');
        $this->assertNotNull(News::query()->where('title', 'Pool opens at nine')->first());
        $this->office($writer)->get(route('office.news'))->assertOk()->assertSee('Pool opens at nine');

        $this->office($admin)->post(route('office.settings.save'), [
            'subadmin_refund_limit' => 200,
            'session_minutes' => 45,
        ])->assertSessionHas('status');
        $this->assertSame('45', (string) OfficeSetting::get('session_minutes', '30'));

        $this->office($admin)->get(route('office.orders', ['q' => 'Board brief', 'layout' => 'kanban']))->assertOk()->assertSee('Board brief');
        $this->office($admin)->post(route('office.orders.views'), ['name' => 'Briefs', 'q' => 'Board brief'])->assertSessionHas('status');
        $this->office($admin)->post(route('office.orders.bulk'), [
            'ids' => [$order->order_id],
            'note' => 'Checked the deadline.',
        ])->assertSessionHas('status');
        $this->assertSame('Checked the deadline.', OrderNote::query()->where('order_id', $order->order_id)->value('body'));

        $csv = $this->office($admin)->get(route('office.reports.export'));
        $csv->assertOk();
        $this->assertStringContainsString((string) $order->order_id, $csv->streamedContent());
        $this->office($admin)->get(route('office.reports'))->assertOk();
        $this->office($admin)->get(route('office.activity'))->assertOk()->assertSee('reports.export');
    }

    #[Test]
    public function a_writer_message_stays_hidden_until_an_admin_releases_it_and_files_stay_on_the_order(): void
    {
        config(['legacy.read_only' => false]);
        $admin = $this->staff(Employee::GROUP_ADMIN, 'release-admin@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'release-writer@example.test');
        $editor = $this->staff(Employee::GROUP_EDITOR, 'release-editor@example.test');
        $customer = $this->customer('release-customer@example.test');
        $order = $this->order($customer, $writer, Order::WRITER_ASSIGNED, 60);

        $this->office($admin)->post(route('office.orders.assign', $order), ['editor_id' => $editor->employee_id])->assertSessionHas('status');
        $this->assertSame((int) $editor->employee_id, (int) $order->fresh()->editor_id);

        $this->office($writer)->post(route('office.orders.messages', $order), [
            'to' => Departments::CUSTOMER,
            'body' => 'The draft is ready for a look.',
        ])->assertSessionHas('status');
        $message = OrderMessage::query()->where('order_id', $order->order_id)->first();
        $this->assertFalse((bool) $message->is_verified);
        $this->actingAs($customer, 'customer')->get(route('desk.order', $order))->assertOk()->assertDontSee('The draft is ready for a look.');

        $this->office($admin)->post(route('office.orders.messages.release', [$order, $message]))->assertSessionHas('status');
        $this->assertTrue((bool) $message->fresh()->is_verified);
        $this->actingAs($customer, 'customer')->get(route('desk.order', $order))->assertOk()->assertSee('The draft is ready for a look.');

        $this->office($writer)->post(route('office.orders.files', $order), [
            'upload' => UploadedFile::fake()->create('draft.txt', 1, 'text/plain'),
            'description' => 'First draft',
        ])->assertSessionHas('status');
        $file = $order->files()->first();
        $this->office($admin)->get(route('office.orders.files.download', [$order, $file->file_id]))->assertOk();

        $this->office($admin)->post(route('office.orders.deadline', $order), [
            'customer_deadline' => now()->addDays(4)->toDateTimeString(),
            'writer_deadline' => now()->addDays(3)->toDateTimeString(),
        ])->assertSessionHas('status');
        $this->assertNotNull($order->fresh()->customer_deadline);
    }

    #[Test]
    public function an_application_is_saved_only_after_the_grammar_check_and_an_admin_can_approve_it(): void
    {
        config(['legacy.read_only' => false]);
        $this->get(route('apply'))->assertOk()->assertSee('Apply to write');
        $this->post(route('apply.store'), [
            'first_name' => 'No',
            'last_name' => 'Pass',
            'email' => 'nopass@example.test',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'answers' => ['The report were sent.', 'The draft will be in a position to be sent.', 'There deadline is Friday.', 'The brief is clear, we can start.'],
            'sample' => UploadedFile::fake()->create('sample.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('answers');
        $this->assertNull(Employee::query()->where('email', 'nopass@example.test')->first());

        $this->post(route('apply.store'), [
            'first_name' => 'Yes',
            'last_name' => 'Pass',
            'email' => 'yespass@example.test',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'answers' => [
                'The report was sent.',
                'We will send the draft on Friday.',
                'Their deadline is Friday.',
                'The brief is clear. We can start.',
            ],
            'sample' => UploadedFile::fake()->create('sample.txt', 1, 'text/plain'),
        ])->assertRedirect(route('apply'));
        $person = Employee::query()->where('email', 'yespass@example.test')->first();
        $this->assertSame(5, (int) $person->employee_group);

        $admin = $this->staff(Employee::GROUP_ADMIN, 'hire-admin@example.test');
        $this->office($admin)->post(route('office.applications.decide', $person), [
            'decision' => 'approve',
            'writer_category' => 3,
            'academic_level' => 1,
        ])->assertSessionHas('status');
        $person->refresh();
        $this->assertSame(Employee::GROUP_WRITER, (int) $person->employee_group);
        $this->assertSame(3, (int) $person->writer_category);
        $this->assertTrue((bool) $person->is_started);
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
            'phone' => '0700000002',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'reg_date' => now(),
        ]);
    }

    private function order(Customer $customer, ?Employee $writer, int $status, float $total = 80): Order
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
        $order->title = 'Board brief';
        $order->academic_level_id = 0;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 0;
        $order->spacing = 'double';
        $order->paper_details = 'A professional brief.';
        $order->total = $total;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return $order;
    }
}
