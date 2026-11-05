<?php

namespace Tests\Feature;

use App\Mail\BrandedMail;
use App\Models\AccountRequest;
use App\Models\Customer;
use App\Models\DeadlineAlert;
use App\Models\Dispute;
use App\Models\Employee;
use App\Models\JobHeartbeat;
use App\Models\MailLog;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\ReviewConsent;
use App\Models\SavedBrief;
use App\Services\Desk\OutboundMail;
use App\Support\Departments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ProductAndMailTest extends TestCase
{
    #[Test]
    public function preferences_skip_ordinary_mail_and_essential_mail_still_goes_to_the_right_person(): void
    {
        config(['legacy.safe_mode' => false, 'legacy.read_only' => false]);
        Mail::fake();
        NotificationFake::fake();
        $customer = $this->customer('prefs-client@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'prefs-writer@example.test');
        NotificationPreference::query()->create([
            'email' => $writer->email,
            'orders' => false,
            'messages' => false,
            'payments' => true,
        ]);
        $order = $this->order($customer, $writer, Order::WRITER_ASSIGNED);
        $order->title = 'Board update for the launch';
        $order->save();

        $this->office($writer);
        app(OutboundMail::class)->send(
            $writer->email,
            'Deadline approaching on order #'.$order->order_id,
            'Deadline approaching',
            ['Order #'.$order->order_id, $order->title],
            route('office.orders.show', $order),
            'Open',
            'orders',
            false,
        );
        Mail::assertNothingQueued();
        $this->assertSame('skipped', MailLog::query()->where('email', $writer->email)->value('status'));

        app(OutboundMail::class)->send(
            $customer->email,
            'Payment receipt',
            'Payment receipt',
            ['Order #'.$order->order_id.' is paid.'],
            route('desk.order', $order),
            'View order',
            'payments',
            true,
        );
        Mail::assertQueued(BrandedMail::class, function (BrandedMail $mail) use ($customer, $writer) {
            return $mail->hasTo($customer->email)
                && $mail->essential === true
                && ! str_contains(implode(' ', $mail->lines), $writer->email);
        });
        NotificationFake::assertNothingSent();
    }

    #[Test]
    public function a_writer_assignment_email_does_not_include_the_customer_email(): void
    {
        config(['legacy.safe_mode' => false, 'legacy.read_only' => false]);
        Mail::fake();
        $customer = $this->customer('secret-client@example.test');
        $admin = $this->staff(Employee::GROUP_ADMIN, 'assign-admin@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'assign-writer@example.test');
        $order = $this->order($customer, null, Order::NEW_PAID);

        $this->office($admin)
            ->post(route('office.orders.assign', $order), ['writer_id' => $writer->employee_id])
            ->assertRedirect();

        Mail::assertQueued(BrandedMail::class, function (BrandedMail $mail) use ($writer, $customer) {
            return $mail->hasTo($writer->email)
                && ! str_contains(implode("\n", $mail->lines).$mail->emailSubject, $customer->email);
        });
        Mail::assertNotQueued(BrandedMail::class, fn (BrandedMail $mail) => $mail->hasTo($customer->email) && str_contains($mail->emailSubject, 'You are the writer'));
    }

    #[Test]
    public function a_failed_send_is_logged_and_does_not_escape_the_mailer(): void
    {
        config(['legacy.safe_mode' => false]);
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('mailbox unavailable'));

        $sent = app(OutboundMail::class)->send(
            'bounce@example.test',
            'Hello',
            'Hello',
            ['A line'],
            null,
            'Open',
            'orders',
            true,
        );

        $this->assertFalse($sent);
        $this->assertSame('failed', MailLog::query()->where('email', 'bounce@example.test')->value('status'));
        $this->assertStringContainsString('mailbox unavailable', (string) MailLog::query()->where('email', 'bounce@example.test')->value('detail'));
    }

    #[Test]
    public function the_admin_can_send_a_test_email_and_safe_mode_records_it(): void
    {
        config(['legacy.safe_mode' => true, 'legacy.read_only' => false]);
        $admin = $this->staff(Employee::GROUP_ADMIN, 'mail-admin@example.test');

        $this->office($admin)
            ->post(route('office.settings.test-email'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('suppressed', MailLog::query()->where('email', $admin->email)->value('status'));
    }

    #[Test]
    public function staff_password_reset_is_essential_and_only_for_a_real_staff_email(): void
    {
        config(['legacy.safe_mode' => false, 'legacy.read_only' => false]);
        Mail::fake();
        $writer = $this->staff(Employee::GROUP_WRITER, 'reset-writer@example.test');
        NotificationPreference::query()->create([
            'email' => $writer->email,
            'orders' => false,
            'messages' => false,
            'payments' => false,
        ]);

        $this->post(route('office.forgot.send'), ['email' => 'nobody@example.test'])->assertRedirect();
        Mail::assertNothingQueued();

        $this->post(route('office.forgot.send'), ['email' => $writer->email])
            ->assertRedirect()
            ->assertSessionHas('status');
        Mail::assertQueued(BrandedMail::class, fn (BrandedMail $mail) => $mail->hasTo($writer->email) && $mail->essential);
    }

    #[Test]
    public function deadline_alerts_email_the_writer_and_customer_once_per_day(): void
    {
        config(['legacy.safe_mode' => false, 'legacy.read_only' => false]);
        Mail::fake();
        $customer = $this->customer('due-client@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'due-writer@example.test');
        $order = $this->order($customer, $writer, Order::WRITER_ASSIGNED);
        $order->writer_deadline = now()->addHours(8);
        $order->title = 'Launch note';
        $order->save();

        $this->artisan('alerts:deadlines')->assertSuccessful();
        $this->artisan('alerts:deadlines')->assertSuccessful();

        Mail::assertQueued(BrandedMail::class, 2);
        Mail::assertQueued(BrandedMail::class, function (BrandedMail $mail) use ($writer, $customer) {
            if (! $mail->hasTo($writer->email)) {
                return false;
            }

            return ! str_contains(implode(' ', $mail->lines), $customer->email);
        });
        $this->assertSame(1, DeadlineAlert::query()->where('order_id', $order->order_id)->count());
        $this->assertNotNull(JobHeartbeat::query()->find('alerts:deadlines'));
    }

    #[Test]
    public function health_robots_and_service_markup_are_public(): void
    {
        $this->get(route('health'))->assertOk()->assertJson(['app' => 'ok', 'database' => 'ok']);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /office', false);
        $this->get(route('services'))->assertOk()->assertSee('schema.org', false)->assertSee('Professional writing', false);
    }

    #[Test]
    public function a_review_is_public_only_with_consent(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('review-client@example.test');
        $order = $this->order($customer, null, Order::FINISHED);
        $order->total_paid = 40;
        $order->save();

        $this->actingAs($customer, 'customer')->post(route('desk.rating.store', $order), [
            'service_rating' => 5,
            'paper_rating' => 5,
            'comment' => 'Clear board note and on the date we asked.',
        ])->assertRedirect();

        $this->get(route('reviews'))->assertOk()->assertDontSee('Clear board note');

        $rating = OrderRating::query()->where('order_id', $order->order_id)->first();
        ReviewConsent::query()->where('rating_id', $rating->rating_id)->update(['consented' => true]);

        $this->get(route('reviews'))->assertOk()->assertSee('Clear board note');
    }

    #[Test]
    public function export_receipt_reorder_and_deletion_stay_on_the_signed_in_customer(): void
    {
        config(['legacy.read_only' => false]);
        $owner = $this->customer('export-owner@example.test');
        $other = $this->customer('export-other@example.test');
        $order = $this->order($owner, null, Order::FINISHED);
        $order->total_paid = 55;
        $order->title = 'Reusable launch brief';
        $order->paper_details = 'Audience is the board.';
        $order->save();

        $this->actingAs($owner, 'customer')->get(route('desk.profile'))->assertOk()->assertSee('Save email preferences');
        $export = $this->actingAs($owner, 'customer')->get(route('desk.export'));
        $export->assertOk();
        $this->assertStringContainsString($owner->email, $export->getContent());
        $this->assertStringNotContainsString($other->email, $export->getContent());

        $pdf = $this->actingAs($owner, 'customer')->get(route('desk.receipt', $order));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString((string) $order->order_id, $pdf->getContent());

        $this->actingAs($other, 'customer')->get(route('desk.receipt', $order))->assertForbidden();

        $this->actingAs($owner, 'customer')
            ->post(route('desk.reorder', $order))
            ->assertRedirect();
        $this->actingAs($owner, 'customer')->get(route('order.create', ['brief' => 1]))
            ->assertOk();
    }

    #[Test]
    public function deletion_waits_while_legacy_writes_are_blocked_and_anonymises_when_they_are_not(): void
    {
        $customer = $this->customer('delete-me@example.test');
        config(['legacy.read_only' => true]);
        $this->actingAs($customer, 'customer')->post(route('desk.deletion'))->assertRedirect();
        $this->assertSame('delete-me@example.test', $customer->fresh()->email);
        $this->assertSame('pending', AccountRequest::query()->where('customer_id', $customer->customer_id)->value('status'));

        config(['legacy.read_only' => false]);
        $this->actingAs($customer, 'customer')->post(route('desk.deletion'))->assertRedirect(route('home'));
        $this->assertStringStartsWith('deleted-'.$customer->customer_id, $customer->fresh()->email);
    }

    #[Test]
    public function disputes_are_manager_only_and_an_approval_credits_the_wallet(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        Mail::fake();
        $customer = $this->customer('dispute-client@example.test');
        $customer->wallet_balance = 0;
        $customer->save();
        $editor = $this->staff(Employee::GROUP_EDITOR, 'dispute-editor@example.test');
        $admin = $this->staff(Employee::GROUP_ADMIN, 'dispute-admin@example.test');
        $order = $this->order($customer, null, Order::FINISHED);
        $order->total = 40;
        $order->total_paid = 40;
        $order->save();

        $this->actingAs($customer, 'customer')->post(route('desk.dispute', $order), [
            'reason' => 'The delivered file missed the agreed audience and the examples.',
        ])->assertRedirect();
        $dispute = Dispute::query()->where('order_id', $order->order_id)->first();
        $this->assertSame('open', $dispute->status);

        $this->office($editor)->get(route('office.disputes'))->assertForbidden();
        $this->office($admin)->get(route('office.home'))->assertOk()->assertSee('Paid revenue')->assertSee('Due within a day');
        $this->office($admin)->post(route('office.disputes.resolve', $dispute), [
            'decision' => 'approve',
            'resolution' => 'Returned to the wallet.',
        ])->assertRedirect();

        $this->assertSame('approved', $dispute->fresh()->status);
        $this->assertEquals(40.0, (float) $customer->fresh()->wallet_balance);
        Mail::assertQueued(BrandedMail::class, fn (BrandedMail $mail) => $mail->hasTo($customer->email) && $mail->essential);
    }

    #[Test]
    public function a_third_revision_request_is_refused(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('revise-client@example.test');
        $order = $this->order($customer, null, Order::DELIVERED);

        foreach ([1, 2] as $n) {
            $order->refresh();
            $order->status_id = Order::DELIVERED;
            $order->save();
            $this->actingAs($customer, 'customer')->post(route('desk.revisions.store', $order), [
                'instructions' => 'Change number '.$n.' in the opening paragraph please.',
                'hours' => 24,
            ])->assertSessionHas('status');
        }

        $order->refresh();
        $order->status_id = Order::DELIVERED;
        $order->save();
        $this->actingAs($customer, 'customer')->post(route('desk.revisions.store', $order), [
            'instructions' => 'A third change that should be refused by the limit.',
            'hours' => 24,
        ])->assertSessionHasErrors('instructions');
    }

    #[Test]
    public function email_preferences_require_a_signed_link(): void
    {
        $this->get('/email/preferences/person@example.test')->assertForbidden();
        $this->post('/email/preferences/person@example.test', ['orders' => '1'])->assertForbidden();
        $url = URL::signedRoute('email.preferences', ['email' => 'person@example.test']);
        $this->get($url)->assertOk()->assertSee('person@example.test');
        $this->post(URL::signedRoute('email.preferences.update', ['email' => 'person@example.test']), [
            'messages' => '1',
        ])->assertRedirect();
        $this->assertFalse(NotificationPreference::allows('person@example.test', 'orders'));
        $this->assertTrue(NotificationPreference::allows('person@example.test', 'messages'));
        $this->assertFalse(NotificationPreference::allows('person@example.test', 'payments'));
    }

    #[Test]
    public function staff_can_reset_a_password_and_bad_links_are_refused(): void
    {
        config(['legacy.safe_mode' => true, 'legacy.read_only' => false]);
        $writer = $this->staff(Employee::GROUP_WRITER, 'office-reset@example.test');
        $this->get(route('office.forgot'))->assertOk();
        $this->post(route('office.forgot.send'), ['email' => $writer->email])->assertRedirect();
        $status = (string) session('status');
        $this->assertStringContainsString('/office/reset/', $status);
        preg_match('#/office/reset/([^?]+)#', $status, $match);
        $token = $match[1];

        $this->get(route('office.reset', ['token' => $token, 'email' => $writer->email]))->assertOk();
        $this->post(route('office.reset.store'), [
            'email' => $writer->email,
            'token' => 'not-the-token',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('email');

        config(['legacy.read_only' => true]);
        $this->post(route('office.reset.store'), [
            'email' => $writer->email,
            'token' => $token,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('email');

        config(['legacy.read_only' => false]);
        DB::table('app_staff_resets')->where('email', $writer->email)->update(['created_at' => now()->subHours(3)]);
        $this->post(route('office.reset.store'), [
            'email' => $writer->email,
            'token' => $token,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('email');

        DB::table('app_staff_resets')->where('email', $writer->email)->update(['created_at' => now()]);
        $this->post(route('office.reset.store'), [
            'email' => $writer->email,
            'token' => $token,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect(route('office.login'));
        $this->assertTrue(Hash::check('new-password-1', $writer->fresh()->password));
    }

    #[Test]
    public function desk_preferences_notifications_reorder_and_a_second_dispute_are_handled(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => false]);
        Mail::fake();
        $customer = $this->customer('desk-extra@example.test');
        $order = $this->order($customer, null, Order::FINISHED);
        $order->total_paid = 20;
        $order->title = 'Saved launch brief';
        $order->paper_details = 'Keep the audience as the board.';
        $order->save();

        $this->actingAs($customer, 'customer')->post(route('desk.preferences'), [
            'messages' => '1',
        ])->assertRedirect();
        $this->assertFalse(NotificationPreference::allows($customer->email, 'orders'));

        Notification::query()->create([
            'to_department' => (string) Departments::CUSTOMER,
            'to_user_id' => $customer->customer_id,
            'from_department' => (string) Departments::SUPPORT,
            'from_user_id' => 0,
            'action' => 'order',
            'node_type' => 'note',
            'node_url' => (string) $order->order_id,
            'message' => 'A note for you',
            'is_seen' => 0,
            'date_add' => now(),
            'date_upd' => now(),
        ]);
        $this->actingAs($customer, 'customer')->post(route('desk.notifications.seen'))->assertRedirect();
        $this->assertSame(1, (int) Notification::query()->where('to_user_id', $customer->customer_id)->value('is_seen'));

        $this->actingAs($customer, 'customer')->post(route('desk.reorder', $order))->assertRedirect();
        $brief = SavedBrief::query()->where('customer_id', $customer->customer_id)->first();
        $this->actingAs($customer, 'customer')->get(route('order.create', ['brief' => $brief->id]))
            ->assertOk()
            ->assertSee('Saved launch brief');

        $this->actingAs($customer, 'customer')->post(route('desk.dispute', $order), [
            'reason' => 'The file did not match the audience we described.',
        ])->assertRedirect();
        $this->actingAs($customer, 'customer')->post(route('desk.dispute', $order), [
            'reason' => 'The file did not match the audience we described.',
        ])->assertSessionHasErrors('reason');

        $admin = $this->staff(Employee::GROUP_ADMIN, 'reject-admin@example.test');
        $dispute = Dispute::query()->where('order_id', $order->order_id)->first();
        $this->assertSame($customer->email, $dispute->customer->email);
        $this->office($admin)->get(route('office.disputes'))->assertOk()->assertSee('did not match the audience');
        $this->office($admin)->post(route('office.disputes.resolve', $dispute), [
            'decision' => 'reject',
            'resolution' => 'The delivery matches the brief.',
        ])->assertRedirect();
        $this->assertSame('rejected', $dispute->fresh()->status);
        $this->assertEquals(0.0, (float) $customer->fresh()->wallet_balance);

        $this->assertFalse(app(OutboundMail::class)->send('not-an-email', 'Hi', 'Hi', ['Line']));
        config(['legacy.safe_mode' => false]);
        Mail::fake();
        $this->assertTrue(app(\App\Services\Outbound\MailGateway::class)->send('raw@example.test', 'Raw', 'Body'));
    }

    #[Test]
    public function health_marks_an_old_deadline_job_as_stale(): void
    {
        JobHeartbeat::query()->updateOrCreate(['name' => 'alerts:deadlines'], [
            'status' => 'ok',
            'detail' => 'old',
            'ran_at' => now()->subHours(5),
        ]);
        $this->get(route('health'))->assertOk()->assertJson(['schedule' => 'stale']);

        JobHeartbeat::query()->where('name', 'alerts:deadlines')->update(['ran_at' => now()]);
        $this->get(route('health'))->assertOk()->assertJson(['schedule' => 'ok']);
    }

    private function office(Employee $employee)
    {
        return $this->actingAs($employee, 'staff')->withSession([
            'office.2fa' => true,
            'office.seen' => time(),
        ]);
    }

    private function staff(int $group, string $email): Employee
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
            'name' => 'Client',
            'phone' => '0700000000',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'reg_date' => now(),
            'wallet_balance' => 0,
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
