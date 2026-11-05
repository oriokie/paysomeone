<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->prepareLegacyDatabase();

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => true,
        ]);

        parent::tearDown();
    }

    private function prepareLegacyDatabase(): void
    {
        static $ready = false;

        if ($ready) {
            return;
        }

        $database = (string) config('database.connections.mysql.database');

        if ($database === (string) config('legacy.live_database')) {
            throw new \RuntimeException('Refusing to run tests against the live database.');
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('prox_customer')) {
            $file = database_path('schema/legacy-structure.sql');
            $process = new Process([
                'mysql',
                '--user='.config('database.connections.mysql.username'),
                '--password='.config('database.connections.mysql.password'),
                '--host='.config('database.connections.mysql.host'),
                '--port='.config('database.connections.mysql.port'),
                $database,
            ]);
            $process->setInput(file_get_contents($file));
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'Could not import the legacy schema.');
            }
        }

        Artisan::call('migrate', ['--force' => true]);
        $ready = true;
    }
}
