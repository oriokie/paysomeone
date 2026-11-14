<?php

namespace App\Console;

use Closure;
use Symfony\Component\Process\Process;

/**
 * Runs the legacy structure file through the mysql client.
 * Tests pass a process factory so the suite never imports SQL.
 */
class SchemaMysql
{
    public function __construct(private ?Closure $processFactory = null) {}

    /**
     * @return array{ok: bool, error: string}
     */
    public function import(string $database, string $sql): array
    {
        $command = [
            'mysql',
            '--user='.config('database.connections.mysql.username'),
            '--password='.config('database.connections.mysql.password'),
            '--host='.config('database.connections.mysql.host'),
            '--port='.config('database.connections.mysql.port'),
            $database,
        ];

        $factory = $this->processFactory ?? static fn (array $command): Process => new Process($command);
        $process = $factory($command);
        $process->setInput($sql);
        $process->setTimeout(120);
        $process->run();

        return [
            'ok' => $process->isSuccessful(),
            'error' => trim($process->getErrorOutput()),
        ];
    }
}
