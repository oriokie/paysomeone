<?php

namespace App\Support;

use PDO;
use PDOException;
use Throwable;

/**
 * Shared checks for the one-time installer and `php artisan deploy:check`.
 * Nothing returned by report(), checks(), or the HTML pages includes a
 * password, an application key, or the installer token.
 */
class InstallGate
{
    /** @var list<string> */
    public const PROX_TABLES = ['prox_customer', 'prox_employee', 'prox_order', 'prox_site'];

    /** @var list<string> */
    public const EXTENSIONS = [
        'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'intl', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml',
    ];

    /**
     * @param  array<string, string>  $env
     * @param  list<string>|null  $extensions
     */
    public function __construct(
        private string $root,
        private string $envFile,
        private string $lockFile,
        private array $env = [],
        private ?PDO $pdo = null,
        private ?array $extensions = null,
        private ?string $phpVersion = null,
        private bool $connected = false,
    ) {
        if ($this->env === []) {
            $this->env = self::readEnvFile($this->envFile);
        }
    }

    public static function fromRoot(string $root, ?string $envFile = null, ?string $lockFile = null): self
    {
        $envFile = $envFile ?: $root.'/.env';
        $lockFile = $lockFile ?: $root.'/storage/framework/install.lock';
        $env = self::readEnvFile($envFile);
        $pdo = null;
        $connected = false;
        if (($env['DB_CONNECTION'] ?? 'mysql') === 'mysql' && ($env['DB_DATABASE'] ?? '') !== '') {
            try {
                $pdo = new PDO(
                    'mysql:host='.($env['DB_HOST'] ?: '127.0.0.1').';port='.($env['DB_PORT'] ?: '3306').';dbname='.$env['DB_DATABASE'].';charset=utf8mb4',
                    $env['DB_USERNAME'] ?? '',
                    $env['DB_PASSWORD'] ?? '',
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
                );
                $connected = true;
            } catch (PDOException) {
                $pdo = null;
            }
        }

        return new self($root, $envFile, $lockFile, $env, $pdo, null, null, $connected);
    }

    /**
     * @param  array<string, string>  $env
     */
    public function useRuntime(array $env, ?PDO $pdo): void
    {
        foreach ($env as $key => $value) {
            $this->env[$key] = $value;
        }
        $this->pdo = $pdo;
        $this->connected = $pdo instanceof PDO;
    }

    public function token(): string
    {
        return (string) ($this->env['INSTALL_TOKEN'] ?? '');
    }

    public function enabled(): bool
    {
        return $this->token() !== '';
    }

    public function locked(): bool
    {
        return is_file($this->lockFile);
    }

    public function tokenMatches(string $given): bool
    {
        $token = $this->token();
        if ($token === '' || $given === '') {
            return false;
        }

        return hash_equals($token, $given);
    }

    public function noteFailure(): void
    {
        $path = $this->attemptFile();
        $state = $this->attempts();
        if ($state['until'] < time()) {
            $state = ['count' => 0, 'until' => time() + 600];
        }
        $state['count']++;
        $this->writeJson($path, $state);
    }

    public function tooManyFailures(): bool
    {
        $state = $this->attempts();

        return $state['until'] >= time() && $state['count'] >= 8;
    }

    public function clearFailures(): void
    {
        $path = $this->attemptFile();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function lock(): void
    {
        $dir = dirname($this->lockFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($this->lockFile, 'locked '.gmdate('c')."\n");
    }

    /**
     * @return 'written'|'exists'|'missing'|'failed'
     */
    public function writeKey(): string
    {
        if (! is_file($this->envFile) || ! is_writable($this->envFile)) {
            return is_file($this->envFile) ? 'failed' : 'missing';
        }
        $contents = (string) file_get_contents($this->envFile);
        $current = '';
        if (preg_match('/^APP_KEY=(.*)$/m', $contents, $match)) {
            $current = trim($match[1], " \t\"'");
        }
        if ($current !== '') {
            return 'exists';
        }
        $key = 'base64:'.base64_encode(random_bytes(32));
        if (preg_match('/^APP_KEY=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1) ?? $contents;
        } else {
            $contents = rtrim($contents)."\nAPP_KEY=".$key."\n";
        }
        $written = file_put_contents($this->envFile, $contents);

        return $written === false ? 'failed' : 'written';
    }

    /**
     * @return list<array{label: string, status: string, detail: string}>
     */
    public function checks(?PDO $pdo = null): array
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
            $this->connected = true;
        }

        $checks = [];
        $version = $this->phpVersion ?: PHP_VERSION;
        if (version_compare($version, '8.3.0', '<')) {
            $checks[] = $this->row('PHP version', 'fail', $version.' is too old. Use PHP 8.4.');
        } elseif (version_compare($version, '8.4.0', '<')) {
            $checks[] = $this->row('PHP version', 'warn', $version.' will run. The host should be PHP 8.4.');
        } else {
            $checks[] = $this->row('PHP version', 'ok', $version);
        }

        $loaded = $this->extensions ?? get_loaded_extensions();
        $loaded = array_map('strtolower', $loaded);
        $missing = array_values(array_filter(self::EXTENSIONS, fn (string $name) => ! in_array($name, $loaded, true)));
        $checks[] = $missing === []
            ? $this->row('PHP extensions', 'ok', 'The required extensions are loaded.')
            : $this->row('PHP extensions', 'fail', 'Missing: '.implode(', ', $missing));

        $writable = true;
        $blocked = [];
        foreach (['storage', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $relative) {
            $path = $this->root.'/'.$relative;
            if (! is_dir($path) || ! is_writable($path)) {
                $writable = false;
                $blocked[] = $relative;
            }
        }
        $checks[] = $writable
            ? $this->row('Folders', 'ok', 'Storage and bootstrap/cache are writable.')
            : $this->row('Folders', 'fail', 'Not writable: '.implode(', ', $blocked));

        $key = trim((string) ($this->env['APP_KEY'] ?? ''), " \t\"'");
        $checks[] = $key !== ''
            ? $this->row('Application key', 'ok', 'A key is set. It is not shown.')
            : $this->row('Application key', 'fail', 'APP_KEY is empty. Use the installer or php artisan key:generate.');

        $checks[] = $this->connected
            ? $this->row('Database', 'ok', 'Connected. The password is not shown.')
            : $this->row('Database', 'fail', 'Could not connect with the database settings in .env.');

        $checks = array_merge($checks, $this->legacyTables());
        $checks[] = $this->migrationSafety();
        $checks[] = $this->migrationState();
        $checks[] = $this->mailCheck();
        $checks[] = $this->heartbeat();
        $checks[] = $this->flag('Read only', 'READ_ONLY_LEGACY', 'Live prox_ rows are protected.', 'This site can change live prox_ rows.');
        $checks[] = $this->flag('Safe mode', 'SAFE_MODE', 'Mail and payments stay on this server.', 'Mail and payment providers can be contacted.');

        $debug = filter_var($this->env['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOL);
        $envName = (string) ($this->env['APP_ENV'] ?? 'production');
        if ($debug && $envName === 'production') {
            $checks[] = $this->row('Debug', 'warn', 'APP_DEBUG is on. Turn it off before real customers use the site.');
        } else {
            $checks[] = $this->row('Debug', 'ok', $debug ? 'On, for this environment.' : 'Off.');
        }

        $checks[] = $this->locked()
            ? $this->row('Installer', 'ok', 'The installer has been turned off.')
            : $this->row('Installer', $this->enabled() ? 'ok' : 'warn', $this->enabled() ? 'Waiting to be locked.' : 'INSTALL_TOKEN is empty, so the installer is off.');

        return $checks;
    }

    public function failed(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $post
     * @return array{code: int, headers: array<string, string>, body: string}
     */
    public function handle(array $post): array
    {
        if ($this->locked() || ! $this->enabled()) {
            return ['code' => 404, 'headers' => [], 'body' => $this->page('Installer off', '<p>This installer is off.</p>')];
        }
        if ($this->tooManyFailures()) {
            return ['code' => 429, 'headers' => [], 'body' => $this->page('Wait', '<p>Too many tries. Wait ten minutes.</p>')];
        }
        $token = (string) ($post['token'] ?? '');
        if (! $this->tokenMatches($token)) {
            if ($post !== []) {
                $this->noteFailure();
            }
            $error = $post !== [] ? 'That token does not match.' : null;

            return ['code' => $post !== [] ? 422 : 200, 'headers' => [], 'body' => $this->form($error)];
        }
        $this->clearFailures();
        $action = (string) ($post['action'] ?? 'check');
        if ($action === 'lock') {
            $this->lock();

            return ['code' => 200, 'headers' => [], 'body' => $this->page('Installer off', '<p>The installer is now off. It will not open again unless the lock file is removed by the site owner.</p>')];
        }
        $note = null;
        if ($action === 'key') {
            $note = match ($this->writeKey()) {
                'written' => 'A key was written into .env. It is not shown here. Reload the page.',
                'exists' => 'A key is already set. This page will not replace it.',
                'missing' => 'The .env file was not found.',
                default => 'The .env file could not be updated.',
            };
            $this->env = self::readEnvFile($this->envFile);
            if (($this->env['APP_KEY'] ?? '') !== '') {
                $this->env['APP_KEY'] = 'set';
            }
        }

        return ['code' => 200, 'headers' => [], 'body' => $this->report($token, $note)];
    }

    /**
     * @return array<string, string>
     */
    public static function readEnvFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $env = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value, " \t\"'");
        }

        return $env;
    }

    private function form(?string $error): string
    {
        $message = $error ? '<p role="alert">'.$this->e($error).'</p>' : '<p>Enter the installer token from the server’s .env file.</p>';

        return $this->page('Installer', $message.'
            <form method="post">
                <label for="token">Installer token</label>
                <input id="token" name="token" type="password" autocomplete="off" required>
                <button type="submit" name="action" value="check">Show the checks</button>
            </form>');
    }

    private function report(string $token, ?string $note): string
    {
        $rows = '';
        foreach ($this->checks() as $check) {
            $rows .= '<tr><td>'.$this->e($check['label']).'</td><td>'.$this->e($check['status']).'</td><td>'.$this->e($check['detail']).'</td></tr>';
        }
        $noteHtml = $note ? '<p role="status">'.$this->e($note).'</p>' : '';
        $hidden = $this->e($token);

        return $this->page('Install checks', $noteHtml.'
            <table><tbody>'.$rows.'</tbody></table>
            <form method="post">
                <input type="hidden" name="token" value="'.$hidden.'">
                <button type="submit" name="action" value="key">Write the application key</button>
                <button type="submit" name="action" value="lock">Turn the installer off</button>
            </form>
            <p>Migrations are not run from this page. Use the dry-run command in the deploy notes.</p>');
    }

    private function page(string $title, string $body): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.$this->e($title).'</title></head><body style="font-family:Georgia,serif;max-width:720px;margin:40px auto;padding:0 16px;color:#14182b"><h1>'.$this->e($title).'</h1>'.$body.'</body></html>';
    }

    /**
     * @return array{label: string, status: string, detail: string}
     */
    private function row(string $label, string $status, string $detail): array
    {
        return ['label' => $label, 'status' => $status, 'detail' => $detail];
    }

    /**
     * @return list<array{label: string, status: string, detail: string}>
     */
    private function legacyTables(): array
    {
        if (! $this->connected || ! $this->pdo) {
            return [$this->row('Legacy tables', 'fail', 'Could not look for the prox_ tables because the database did not connect.')];
        }
        $rows = [];
        foreach (self::PROX_TABLES as $table) {
            $present = $this->tableExists($table);
            $rows[] = $present
                ? $this->row($table, 'ok', 'Present.')
                : $this->row($table, 'fail', 'Missing. This site expects the current site’s database.');
        }

        return $rows;
    }

    /**
     * @return array{label: string, status: string, detail: string}
     */
    private function migrationSafety(): array
    {
        $bad = [];
        foreach ($this->migrationFiles() as $file) {
            $text = (string) file_get_contents($file);
            if (stripos($text, 'prox_') !== false) {
                $bad[] = basename($file);
            }
        }

        return $bad === []
            ? $this->row('Migration files', 'ok', 'No migration file names a prox_ table.')
            : $this->row('Migration files', 'fail', 'These files mention a prox_ table: '.implode(', ', $bad));
    }

    /**
     * @return array{label: string, status: string, detail: string}
     */
    private function migrationState(): array
    {
        $files = array_map(fn (string $file) => basename($file, '.php'), $this->migrationFiles());
        if (! $this->connected || ! $this->pdo) {
            return $this->row('Migrations', 'warn', 'Not checked, because the database did not connect.');
        }
        try {
            $ran = $this->pdo->query('select migration from migrations')->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable) {
            return $this->row('Migrations', 'warn', count($files).' files are waiting. The migrations table is not there yet.');
        }
        $pending = array_values(array_diff($files, $ran ?: []));
        if ($pending === []) {
            return $this->row('Migrations', 'ok', 'All '.count($files).' application migrations have run.');
        }

        return $this->row('Migrations', 'warn', count($pending).' still waiting: '.implode(', ', $pending));
    }

    /**
     * @return array{label: string, status: string, detail: string}
     */
    private function mailCheck(): array
    {
        $mailer = strtolower((string) ($this->env['MAIL_MAILER'] ?? 'log'));
        $from = strtolower((string) ($this->env['MAIL_FROM_ADDRESS'] ?? ''));
        if (in_array($mailer, ['log', 'array', ''], true)) {
            return $this->row('Mail', 'warn', 'Mail is set to '.($mailer ?: 'log').', so messages stay on the server.');
        }
        if ($from === '' || str_contains($from, 'example.com') || str_contains($from, 'example.test')) {
            return $this->row('Mail', 'warn', 'The mailer is '.$mailer.', and the from-address still looks like a placeholder.');
        }

        return $this->row('Mail', 'ok', 'The mailer is '.$mailer.'. The password is not shown.');
    }

    /**
     * @return array{label: string, status: string, detail: string}
     */
    private function heartbeat(): array
    {
        if (! $this->connected || ! $this->pdo) {
            return $this->row('Scheduler', 'warn', 'Not checked, because the database did not connect.');
        }
        try {
            $statement = $this->pdo->query("select ran_at from app_job_heartbeats where name = 'alerts:deadlines' limit 1");
            $ran = $statement ? $statement->fetchColumn() : false;
        } catch (Throwable) {
            return $this->row('Scheduler', 'warn', 'No heartbeat yet. It appears after the migrations and the first cron run.');
        }
        if (! $ran) {
            return $this->row('Scheduler', 'warn', 'The deadline check has not run yet.');
        }
        $fresh = strtotime((string) $ran) >= time() - 7200;

        return $fresh
            ? $this->row('Scheduler', 'ok', 'The deadline check ran recently.')
            : $this->row('Scheduler', 'warn', 'The deadline check last ran more than two hours ago.');
    }

    /**
     * @return array{label: string, status: string, detail: string}
     */
    private function flag(string $label, string $key, string $on, string $off): array
    {
        $value = filter_var($this->env[$key] ?? 'true', FILTER_VALIDATE_BOOL);

        return $this->row($label, $value ? 'ok' : 'warn', $value ? $on : $off);
    }

    private function tableExists(string $table): bool
    {
        try {
            $statement = $this->pdo?->prepare('select 1 from information_schema.tables where table_schema = database() and table_name = ? limit 1');
            $statement?->execute([$table]);

            return (bool) $statement?->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob($this->root.'/database/migrations/*.php') ?: [];
        sort($files);

        return $files;
    }

    /**
     * @return array{count: int, until: int}
     */
    private function attempts(): array
    {
        $path = $this->attemptFile();
        if (! is_file($path)) {
            return ['count' => 0, 'until' => 0];
        }
        $decoded = json_decode((string) file_get_contents($path), true);

        return [
            'count' => (int) ($decoded['count'] ?? 0),
            'until' => (int) ($decoded['until'] ?? 0),
        ];
    }

    private function attemptFile(): string
    {
        return dirname($this->lockFile).'/install-attempts.json';
    }

    /**
     * @param  array{count: int, until: int}  $state
     */
    private function writeJson(string $path, array $state): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($path, json_encode($state));
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
