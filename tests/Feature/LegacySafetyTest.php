<?php

namespace Tests\Feature;

use App\Auth\LegacyHasher;
use App\Exceptions\LegacyWriteBlocked;
use App\Models\Customer;
use App\Services\Outbound\MailGateway;
use App\Services\Outbound\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacySafetyTest extends TestCase
{
    #[Test]
    public function a_legacy_password_signs_in_and_is_rewritten_as_bcrypt_when_writes_are_allowed(): void
    {
        config(['legacy.read_only' => false]);
        $hasher = new LegacyHasher(4);
        $stored = $hasher->saltedSha1('correct horse', 'saltsalt1');

        Customer::query()->create($this->customer('legacy@example.test', $stored));

        $this->post('/login', [
            'email' => 'legacy@example.test',
            'password' => 'correct horse',
        ])->assertRedirect('/account');

        $this->assertAuthenticatedAs(Customer::query()->where('email', 'legacy@example.test')->first(), 'customer');

        $updated = Customer::query()->where('email', 'legacy@example.test')->value('password');
        $this->assertSame('bcrypt', password_get_info($updated)['algoName']);
        $this->assertTrue(password_verify('correct horse', $updated));
    }

    #[Test]
    public function read_only_mode_lets_the_same_password_in_without_changing_it(): void
    {
        config(['legacy.read_only' => true]);
        $stored = md5('plain-secret');
        Customer::query()->getConnection()->unprepared('SET @read_guard := 1');

        DB::transactionLevel();
        config(['legacy.read_only' => false]);
        Customer::query()->create($this->customer('plain@example.test', $stored));
        config(['legacy.read_only' => true]);

        $this->post('/login', [
            'email' => 'plain@example.test',
            'password' => 'plain-secret',
        ])->assertRedirect('/account');

        $this->assertSame($stored, Customer::query()->where('email', 'plain@example.test')->value('password'));
    }

    #[Test]
    public function read_only_mode_blocks_model_and_query_writes(): void
    {
        config(['legacy.read_only' => true]);

        try {
            DB::table('prox_customer')->where('customer_id', 1)->update(['name' => 'Changed']);
            $this->fail('A raw update was allowed.');
        } catch (LegacyWriteBlocked $exception) {
            $this->assertStringContainsString('read-only', $exception->getMessage());
        }

        $customer = new Customer($this->customer('blocked@example.test', md5('x')));
        $this->expectException(LegacyWriteBlocked::class);
        $customer->save();
    }

    #[Test]
    public function registration_is_refused_while_the_legacy_database_is_read_only(): void
    {
        config(['legacy.read_only' => true]);

        $this->post('/register', [
            'name' => 'New Client',
            'email' => 'new@example.test',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'terms' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('prox_customer', ['email' => 'new@example.test']);
    }

    #[Test]
    public function registration_uses_the_existing_id_gap_and_a_bcrypt_password(): void
    {
        config(['legacy.read_only' => false]);

        $this->post('/register', [
            'name' => 'New Client',
            'email' => 'new@example.test',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'terms' => '1',
        ])->assertRedirect('/account');

        $customer = Customer::query()->where('email', 'new@example.test')->first();
        $this->assertNotNull($customer);
        $this->assertSame(452441, (int) $customer->customer_id);
        $this->assertTrue(password_verify('long-enough-password', $customer->password));
    }

    #[Test]
    public function safe_mode_does_not_send_mail_or_call_a_payment_provider(): void
    {
        config(['legacy.safe_mode' => true]);
        Mail::fake();

        $sent = app(MailGateway::class)->send('person@example.test', 'Hello', 'Body');

        $this->assertFalse($sent);
        Mail::assertNothingSent();

        $this->expectExceptionMessage('SAFE_MODE');
        app(PaymentGateway::class)->charge('mpesa', 10, 'ref');
    }

    /**
     * @return array<string, mixed>
     */
    private function customer(string $email, string $password): array
    {
        return [
            'google_analytics_id' => '',
            'email' => $email,
            'name' => 'Test Client',
            'country_id' => 0,
            'site_id' => 1,
            'password' => $password,
            'secure_key' => md5($email),
            'reg_date' => now(),
        ];
    }
}
