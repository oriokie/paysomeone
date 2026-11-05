<?php

namespace Tests\Feature;

use App\Console\SchemaMysql;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SchemaImportTest extends TestCase
{
    #[Test]
    public function the_importer_refuses_the_live_database_and_does_not_import_twice(): void
    {
        config(['legacy.live_database' => config('database.connections.mysql.database')]);
        $this->artisan('legacy:import-schema')->assertFailed();

        config(['legacy.live_database' => 'tutoress_paysomeone']);
        $this->artisan('legacy:import-schema')
            ->expectsOutputToContain('already exists')
            ->assertSuccessful();
    }

    #[Test]
    public function a_schema_check_that_throws_is_reported_and_a_missing_file_is_refused(): void
    {
        Schema::partialMock()->shouldReceive('hasTable')->once()->andThrow(new \RuntimeException('connection refused'));
        $this->artisan('legacy:import-schema')->expectsOutputToContain('connection refused')->assertFailed();

        $path = database_path('schema/legacy-structure.sql');
        $aside = $path.'.aside';
        rename($path, $aside);
        try {
            $this->artisan('legacy:import-schema', ['--force' => true])
                ->expectsOutputToContain('Missing')
                ->assertFailed();
        } finally {
            rename($aside, $path);
        }
    }

    #[Test]
    public function mysql_success_and_failure_are_reported_without_importing_sql(): void
    {
        $this->app->instance(SchemaMysql::class, new SchemaMysql(function (array $command): Process {
            $this->assertSame('mysql', $command[0]);
            $this->assertContains(config('database.connections.mysql.database'), $command);
            $process = \Mockery::mock(Process::class);
            $process->shouldReceive('setInput')->once()->withArgs(fn ($sql) => str_contains($sql, 'prox_customer'));
            $process->shouldReceive('setTimeout')->once()->with(120);
            $process->shouldReceive('run')->once();
            $process->shouldReceive('isSuccessful')->once()->andReturn(true);
            $process->shouldReceive('getErrorOutput')->once()->andReturn('');

            return $process;
        }));

        $this->artisan('legacy:import-schema', ['--force' => true])
            ->expectsOutputToContain('imported into')
            ->assertSuccessful();

        $this->app->instance(SchemaMysql::class, new SchemaMysql(function (): Process {
            $process = \Mockery::mock(Process::class);
            $process->shouldReceive('setInput')->once();
            $process->shouldReceive('setTimeout')->once();
            $process->shouldReceive('run')->once();
            $process->shouldReceive('isSuccessful')->once()->andReturn(false);
            $process->shouldReceive('getErrorOutput')->once()->andReturn('  syntax error  ');

            return $process;
        }));

        $this->artisan('legacy:import-schema', ['--force' => true])
            ->expectsOutputToContain('syntax error')
            ->assertFailed();

        $this->app->instance(SchemaMysql::class, new SchemaMysql(function (): Process {
            $process = \Mockery::mock(Process::class);
            $process->shouldReceive('setInput')->once();
            $process->shouldReceive('setTimeout')->once();
            $process->shouldReceive('run')->once();
            $process->shouldReceive('isSuccessful')->once()->andReturn(false);
            $process->shouldReceive('getErrorOutput')->once()->andReturn('   ');

            return $process;
        }));

        $this->artisan('legacy:import-schema', ['--force' => true])
            ->expectsOutputToContain('mysql import failed')
            ->assertFailed();
    }
}
