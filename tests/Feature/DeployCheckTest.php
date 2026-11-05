<?php

namespace Tests\Feature;

use App\Models\JobHeartbeat;
use App\Support\InstallGate;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeployCheckTest extends TestCase
{
    #[Test]
    public function the_command_passes_and_does_not_print_the_application_key(): void
    {
        $this->artisan('deploy:check')
            ->assertSuccessful()
            ->expectsOutputToContain('prox_customer')
            ->expectsOutputToContain('No migration file names a prox_ table')
            ->doesntExpectOutputToContain((string) config('app.key'));
    }

    #[Test]
    public function the_installer_stays_off_without_a_token_and_never_prints_secrets(): void
    {
        $this->get('/install')->assertNotFound();

        $secret = 'db-secret-should-not-appear';
        $root = $this->scratch();
        file_put_contents($root.'/.env', "APP_KEY=\nINSTALL_TOKEN=gate-token\nDB_PASSWORD={$secret}\nMAIL_MAILER=log\nREAD_ONLY_LEGACY=true\nSAFE_MODE=true\nAPP_ENV=production\nAPP_DEBUG=false\n");
        config([
            'deploy.token' => 'gate-token',
            'deploy.lock' => $root.'/storage/framework/install.lock',
            'deploy.env_file' => $root.'/.env',
        ]);

        $this->get('/install')->assertOk()->assertSee('Installer token')->assertDontSee('prox_customer');
        $this->post('/install', ['token' => 'wrong'])->assertStatus(422)->assertDontSee($secret);
        $this->post('/install', ['token' => 'gate-token', 'action' => 'check'])
            ->assertOk()
            ->assertSee('prox_customer')
            ->assertSee('No migration file names a prox_ table')
            ->assertDontSee($secret)
            ->assertDontSee((string) config('app.key'));

        $written = $this->post('/install', ['token' => 'gate-token', 'action' => 'key'])->assertOk()->assertSee('written into .env');
        $saved = (string) file_get_contents($root.'/.env');
        preg_match('/^APP_KEY=(.+)$/m', $saved, $key);
        $this->assertStringStartsWith('base64:', $key[1]);
        $written->assertDontSee($key[1]);
        $this->post('/install', ['token' => 'gate-token', 'action' => 'key'])->assertOk()->assertSee('will not replace')->assertDontSee($key[1]);

        $this->post('/install', ['token' => 'gate-token', 'action' => 'lock'])->assertOk()->assertSee('now off');
        $this->get('/install')->assertNotFound();
    }

    #[Test]
    public function eight_wrong_tokens_then_the_ninth_is_refused(): void
    {
        $root = $this->scratch();
        config([
            'deploy.token' => 'gate-token',
            'deploy.lock' => $root.'/storage/framework/install.lock',
            'deploy.env_file' => $root.'/.env',
        ]);
        file_put_contents($root.'/.env', "INSTALL_TOKEN=gate-token\nAPP_KEY=\n");

        for ($i = 0; $i < 8; $i++) {
            $this->post('/install', ['token' => 'nope'])->assertStatus(422);
        }
        $this->post('/install', ['token' => 'nope'])->assertStatus(429);
    }

    #[Test]
    public function a_migration_that_mentions_a_legacy_table_fails_the_check(): void
    {
        $root = $this->scratch();
        mkdir($root.'/database/migrations', 0775, true);
        file_put_contents($root.'/database/migrations/bad.php', "<?php Schema::dropIfExists('prox_customer');");
        $gate = new InstallGate($root, $root.'/.env', $root.'/storage/framework/install.lock', [
            'APP_KEY' => 'set',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'MAIL_MAILER' => 'log',
            'READ_ONLY_LEGACY' => 'true',
            'SAFE_MODE' => 'true',
        ], null, ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'intl', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml'], '8.4.1', false);

        $checks = collect($gate->checks())->keyBy('label');
        $this->assertSame('fail', $checks['Migration files']['status']);
        $this->assertSame('ok', $checks['PHP extensions']['status']);
        $this->assertSame('ok', $checks['PHP version']['status']);
        $this->assertStringNotContainsString('prox_customer', $checks['Database']['detail']);
    }

    #[Test]
    public function old_php_and_a_missing_extension_fail_and_a_fresh_heartbeat_passes(): void
    {
        JobHeartbeat::query()->updateOrCreate(['name' => 'alerts:deadlines'], [
            'status' => 'ok',
            'detail' => 'ran',
            'ran_at' => now(),
        ]);
        $root = base_path();
        $gate = new InstallGate($root, $root.'/.env', $root.'/storage/framework/install.lock', [
            'APP_KEY' => 'set',
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'MAIL_MAILER' => 'smtp',
            'MAIL_FROM_ADDRESS' => 'studio@paysomeonetowrite.com',
            'READ_ONLY_LEGACY' => 'true',
            'SAFE_MODE' => 'true',
            'INSTALL_TOKEN' => 'present',
        ], DB::connection()->getPdo(), ['ctype'], '8.2.0', true);

        $checks = collect($gate->checks())->keyBy('label');
        $this->assertSame('fail', $checks['PHP version']['status']);
        $this->assertSame('fail', $checks['PHP extensions']['status']);
        $this->assertSame('ok', $checks['Scheduler']['status']);
        $this->assertSame('ok', $checks['Mail']['status']);
        $this->assertStringNotContainsString('present', $checks['Installer']['detail']);
    }

    #[Test]
    public function the_standalone_gate_connects_without_printing_the_password(): void
    {
        $root = $this->scratch();
        $secret = 'db-secret-Zz9';
        file_put_contents($root.'/.env', implode("\n", [
            'APP_KEY=',
            'INSTALL_TOKEN=standalone-token',
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            'DB_PORT=1',
            'DB_DATABASE=paysomeone_test',
            'DB_USERNAME=paysomeone',
            'DB_PASSWORD='.$secret,
            'MAIL_MAILER=log',
            'READ_ONLY_LEGACY=true',
            'SAFE_MODE=true',
            'APP_ENV=production',
            'APP_DEBUG=false',
        ]));
        symlink(base_path('database/migrations'), $root.'/database/migrations');
        $result = InstallGate::fromRoot($root, $root.'/.env', $root.'/storage/framework/install.lock')
            ->handle(['token' => 'standalone-token', 'action' => 'check']);

        $this->assertSame(200, $result['code']);
        $this->assertStringNotContainsString($secret, $result['body']);
        $this->assertStringContainsString('Could not connect', $result['body']);
    }

    #[Test]
    public function the_gate_covers_a_live_connection_and_the_warning_rows(): void
    {
        $root = $this->scratch();
        $secret = (string) config('database.connections.mysql.password');
        file_put_contents($root.'/.env', implode("\n", [
            'APP_KEY=',
            'INSTALL_TOKEN=standalone-token',
            'DB_CONNECTION=mysql',
            'DB_HOST='.config('database.connections.mysql.host'),
            'DB_PORT='.config('database.connections.mysql.port'),
            'DB_DATABASE='.config('database.connections.mysql.database'),
            'DB_USERNAME='.config('database.connections.mysql.username'),
            'DB_PASSWORD='.$secret,
            'MAIL_MAILER=log',
            'READ_ONLY_LEGACY=false',
            'SAFE_MODE=false',
            'APP_ENV=production',
            'APP_DEBUG=true',
        ]));
        symlink(base_path('database/migrations'), $root.'/database/migrations');
        $connected = InstallGate::fromRoot($root, $root.'/.env', $root.'/storage/framework/install.lock');
        $body = $connected->handle(['token' => 'standalone-token', 'action' => 'check']);
        $this->assertSame(200, $body['code']);
        $this->assertStringContainsString('prox_customer', $body['body']);
        $this->assertStringNotContainsString($secret, $body['body']);
        $this->assertStringContainsString('can change live prox_ rows', $body['body']);
        $this->assertStringContainsString('APP_DEBUG is on', $body['body']);

        $this->assertSame('missing', (new InstallGate($root, $root.'/missing.env', $root.'/storage/framework/install.lock'))->writeKey());
        file_put_contents($root.'/no-key.env', "MAIL_MAILER=log\n");
        $this->assertSame('written', (new InstallGate($root, $root.'/no-key.env', $root.'/storage/framework/install.lock'))->writeKey());

        JobHeartbeat::query()->updateOrCreate(['name' => 'alerts:deadlines'], [
            'status' => 'ok',
            'detail' => 'old',
            'ran_at' => now()->subHours(5),
        ]);
        file_put_contents($root.'/database/migrations-extra', '');
        $extra = $root.'/database/migrations';
        if (is_link($extra)) {
            unlink($extra);
            mkdir($extra, 0775, true);
        }
        file_put_contents($extra.'/2099_01_01_000000_extra.php', "<?php // additive only\n");
        $gate = new InstallGate($root, $root.'/.env', $root.'/nested/install.lock', [
            'APP_KEY' => 'set',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'MAIL_MAILER' => 'smtp',
            'MAIL_FROM_ADDRESS' => 'hello@example.com',
            'READ_ONLY_LEGACY' => 'true',
            'SAFE_MODE' => 'true',
            'INSTALL_TOKEN' => 'standalone-token',
        ], DB::connection()->getPdo(), null, '8.3.6', true);
        $gate->lock();
        $checks = collect($gate->checks())->keyBy('label');
        $this->assertSame('warn', $checks['PHP version']['status']);
        $this->assertSame('warn', $checks['Mail']['status']);
        $this->assertSame('warn', $checks['Scheduler']['status']);
        $this->assertSame('warn', $checks['Migrations']['status']);
        $this->assertSame('ok', $checks['Installer']['status']);
        $this->assertFalse($gate->failed([['label' => 'PHP version', 'status' => 'ok', 'detail' => 'fine']]));

        $sqlite = new \PDO('sqlite::memory:');
        $missing = new InstallGate($root, $root.'/.env', $root.'/storage/framework/install.lock', [
            'APP_KEY' => 'set',
            'MAIL_MAILER' => '',
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'false',
            'READ_ONLY_LEGACY' => 'true',
            'SAFE_MODE' => 'true',
        ], $sqlite, null, '8.4.3', true);
        $sqliteChecks = collect($missing->checks())->keyBy('label');
        $this->assertSame('fail', $sqliteChecks['prox_customer']['status']);
        $this->assertSame('warn', $sqliteChecks['Mail']['status']);
    }

    private function scratch(): string
    {
        $root = sys_get_temp_dir().'/install-gate-'.bin2hex(random_bytes(4));
        mkdir($root.'/storage/framework', 0775, true);
        mkdir($root.'/storage/logs', 0775, true);
        mkdir($root.'/bootstrap/cache', 0775, true);
        mkdir($root.'/database', 0775, true);

        return $root;
    }
}
