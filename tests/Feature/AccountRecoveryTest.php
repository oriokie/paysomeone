<?php

namespace Tests\Feature;

use App\Auth\LegacyHasher;
use App\Services\Office\Totp;
use App\Auth\LegacyUserProvider;
use App\Filament\Widgets\StudioOverview;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Employee;
use App\Services\Outbound\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    #[Test]
    public function a_reset_link_is_shown_only_in_safe_mode_and_updates_the_password_once(): void
    {
        config(['legacy.read_only' => false, 'legacy.safe_mode' => true]);
        $customer = Customer::query()->create([
            'google_analytics_id' => 'reset@example.test',
            'email' => 'reset@example.test',
            'name' => 'Reset',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('old-password-1'),
            'secure_key' => md5('reset'),
            'reg_date' => now(),
        ]);

        $this->post('/forgot-password', ['email' => 'nobody@example.test'])->assertSessionHas('status');
        $this->assertSame(0, DB::table('app_password_resets')->count());

        $response = $this->post('/forgot-password', ['email' => 'reset@example.test']);
        $response->assertSessionHas('status');
        $status = session('status');
        $this->assertStringContainsString('/reset-password/', $status);
        preg_match('#/reset-password/([^?]+)#', $status, $match);
        $token = $match[1];

        $this->post('/reset-password', [
            'email' => 'reset@example.test',
            'token' => 'not-the-token',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('email');

        $this->post('/reset-password', [
            'email' => 'reset@example.test',
            'token' => $token,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password-1', $customer->fresh()->password));
        $this->assertSame(0, DB::table('app_password_resets')->where('email', 'reset@example.test')->count());

        config(['legacy.read_only' => true]);
        $this->post('/forgot-password', ['email' => 'reset@example.test'])->assertSessionHasErrors('email');
    }

    #[Test]
    public function contact_blog_and_the_old_password_formats_behave_as_documented(): void
    {
        $this->post('/contact', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'company' => 'Northwind',
            'message' => 'We need a launch note for the existing customers next week.',
        ])->assertSessionHas('status');
        $this->assertSame('Ada', ContactMessage::query()->where('email', 'ada@example.test')->value('name'));

        $this->get('/blog')->assertOk();
        $this->get('/sitemap.xml')->assertOk();

        $hasher = new LegacyHasher(4);
        $this->assertSame('md5', $hasher->info(md5('secret'))['algoName']);
        $this->assertSame('salted-sha1', $hasher->info($hasher->saltedSha1('secret', 'saltsha01'))['algoName']);
        $this->assertSame('unknown', $hasher->info('not-a-hash')['algoName']);
        $this->assertSame('unknown', $hasher->info(null)['algoName']);
        $this->assertFalse($hasher->check('', ''));
        $this->assertFalse($hasher->check('secret', 'short'));
        $this->assertTrue($hasher->needsRehash(md5('secret')));
        $this->assertTrue($hasher->needsRehash(''));

        $provider = new LegacyUserProvider(Hash::driver(), Customer::class);
        $customer = new Customer;
        $customer->password = md5('secret');
        config(['legacy.read_only' => true]);
        $provider->rehashPasswordIfRequired($customer, ['password' => 'secret'], true);
        $this->assertSame(md5('secret'), $customer->password);

        config(['legacy.safe_mode' => false]);
        $this->expectExceptionMessage('not switched on');
        app(PaymentGateway::class)->charge('paypal', 10, 'ref');
    }

    #[Test]
    public function the_studio_overview_counts_rows_without_calling_a_gateway(): void
    {
        $widget = new StudioOverview;
        $method = new \ReflectionMethod($widget, 'getStats');
        $stats = $method->invoke($widget);
        $this->assertCount(5, $stats);

        config(['legacy.live_database' => config('database.connections.mysql.database')]);
        $this->artisan('legacy:import-schema')->assertFailed();
        config(['legacy.live_database' => 'tutoress_paysomeone']);
        $this->artisan('legacy:import-schema')->assertSuccessful();
    }

    #[Test]
    public function two_factor_setup_shows_recovery_codes_and_a_wrong_code_does_not_enable_it(): void
    {
        $staff = Employee::query()->create([
            'email' => 'codes@example.test',
            'employee_group' => Employee::GROUP_ADMIN,
            'first_name' => 'Code',
            'last_name' => 'Admin',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5('codes'),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => 0,
            'reg_date' => now(),
        ]);
        $this->actingAs($staff, 'staff')->get('/office/security')->assertOk();
        $secret = session('office.setup_secret');
        $this->actingAs($staff, 'staff')->post('/office/security', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse((bool) $staff->fresh()->two_factor_enabled);
        $this->actingAs($staff, 'staff')->post('/office/security', ['code' => Totp::at($secret)])->assertRedirect('/office');
        $this->actingAs($staff, 'staff')->get('/office/security')->assertOk()->assertSee('Recovery codes');
    }
}
