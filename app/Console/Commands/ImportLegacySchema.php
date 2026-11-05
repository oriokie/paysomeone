<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ImportLegacySchema extends Command
{
    protected $signature = 'legacy:import-schema {--force : Import even if prox_customer already exists}';

    protected $description = 'Load the structure-only legacy tables into the configured database';

    public function handle(): int
    {
        $database = (string) config('database.connections.mysql.database');

        if ($database === (string) config('legacy.live_database')) {
            $this->error('Refusing to import a schema into the live database name.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            try {
                $exists = \Illuminate\Support\Facades\Schema::hasTable('prox_customer');
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            if ($exists) {
                $this->info('prox_customer already exists. Nothing imported.');

                return self::SUCCESS;
            }
        }

        $file = database_path('schema/legacy-structure.sql');
        if (! is_file($file)) {
            $this->error('Missing '.$file);

            return self::FAILURE;
        }

        $command = [
            'mysql',
            '--user='.config('database.connections.mysql.username'),
            '--password='.config('database.connections.mysql.password'),
            '--host='.config('database.connections.mysql.host'),
            '--port='.config('database.connections.mysql.port'),
            $database,
        ];

        $process = new Process($command);
        $process->setInput(file_get_contents($file));
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput()) ?: 'mysql import failed.');

            return self::FAILURE;
        }

        $this->info('Legacy table structure imported into '.$database.'.');

        return self::SUCCESS;
    }
}
