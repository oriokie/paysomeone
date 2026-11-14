<?php

namespace App\Console\Commands;

use App\Support\InstallGate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class DeployCheck extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Check PHP, folders, the database, legacy tables, and that migrations do not touch prox_ tables';

    public function handle(): int
    {
        $pdo = null;
        try {
            $connection = DB::connection()->getPdo();
            if ($connection instanceof PDO) {
                $pdo = $connection;
            }
        } catch (Throwable) {
            $pdo = null;
        }

        $gate = new InstallGate(
            base_path(),
            (string) config('deploy.env_file'),
            (string) config('deploy.lock'),
            [
                'APP_KEY' => (string) config('app.key'),
                'APP_ENV' => (string) config('app.env'),
                'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
                'INSTALL_TOKEN' => (string) config('deploy.token'),
                'MAIL_MAILER' => (string) config('mail.default'),
                'MAIL_FROM_ADDRESS' => (string) config('mail.from.address'),
                'READ_ONLY_LEGACY' => config('legacy.read_only') ? 'true' : 'false',
                'SAFE_MODE' => config('legacy.safe_mode') ? 'true' : 'false',
            ],
            $pdo,
            null,
            null,
            $pdo instanceof PDO,
        );

        $checks = $gate->checks();
        foreach ($checks as $check) {
            $this->line(str_pad($check['label'], 22).str_pad($check['status'], 6).$check['detail']);
        }

        return $gate->failed($checks) ? self::FAILURE : self::SUCCESS;
    }
}
