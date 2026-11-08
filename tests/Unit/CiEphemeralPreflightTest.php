<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * CRM-SEC-CI-01: deny before Docker/SQL for unsafe synthetic CI configuration.
 * Tests --static-only only. Integration --verify executes in the PostgreSQL job.
 */
final class CiEphemeralPreflightTest extends TestCase
{
    private static function validEnvironment(): array
    {
        return [
            'CI' => 'true',
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
            'GITHUB_EVENT_NAME' => 'pull_request',
            'RUNNER_ENVIRONMENT' => 'github-hosted',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'imagine_crm_ci',
            'CRM_TENANT_SCHEMA_LAB_DATABASE' => 'imagine_crm_ci',
            'DB_USERNAME' => 'postgres',
            'DB_PASSWORD' => 'synthetic-ci-only',
            // Nullify inherited overrides, if present.
            'DB_URL' => false,
            'DATABASE_URL' => false,
            'PGHOST' => false,
            'PGSERVICE' => false,
            'PGSERVICEFILE' => false,
        ];
    }

    private static function runPreflight(array $changes = [], string $mode = '--static-only'): Process
    {
        $env = array_replace(self::validEnvironment(), $changes);
        $path = dirname(__DIR__, 2).'/scripts/ci/verify-ephemeral-postgres.php';
        $process = new Process([PHP_BINARY, $path, $mode], dirname(__DIR__, 2), $env);
        $process->run();

        return $process;
    }

    public function test_expected_synthetic_profile_passes_static_checks_without_connecting_to_db(): void
    {
        $process = self::runPreflight();
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        self::assertStringContainsString('database NOT verified', $process->getOutput());
    }

    public static function rejectedConfigurations(): array
    {
        return [
            'CI disabled' => [['CI' => 'false']],
            'not GitHub Actions' => [['GITHUB_ACTIONS' => 'false']],
            'other repository' => [['GITHUB_REPOSITORY' => 'attacker/fork']],
            'push event' => [['GITHUB_EVENT_NAME' => 'push']],
            'self hosted runner' => [['RUNNER_ENVIRONMENT' => 'self-hosted']],
            'production' => [['APP_ENV' => 'production']],
            'different DB driver' => [['DB_CONNECTION' => 'sqlite']],
            'external host' => [['DB_HOST' => 'db.example.invalid']],
            'other port' => [['DB_PORT' => '5433']],
            'persistent database name' => [['DB_DATABASE' => 'imagine_crm_prod']],
            'wrong laboratory declaration' => [['CRM_TENANT_SCHEMA_LAB_DATABASE' => 'imagine_crm_lab_legacy']],
            'privileged unexpected user' => [['DB_USERNAME' => 'root']],
            'missing password' => [['DB_PASSWORD' => false]],
            'DSN override' => [['DB_URL' => 'postgresql://remote.example.invalid/prod']],
            'alternate DSN' => [['DATABASE_URL' => 'postgres://remote.example.invalid/prod']],
            'PGHOST override' => [['PGHOST' => 'remote.example.invalid']],
            'service override' => [['PGSERVICE' => 'prod']],
        ];
    }

    #[DataProvider('rejectedConfigurations')]
    public function test_preflight_rejects_unsafe_profile_before_any_connection(array $changes): void
    {
        $process = self::runPreflight($changes);
        self::assertSame(42, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        self::assertStringContainsString('CRM-SEC-CI-01 DENIED:', $process->getErrorOutput());
    }

    public function test_unknown_preflight_mode_fails_closed(): void
    {
        $process = self::runPreflight([], '--wipe');
        self::assertSame(42, $process->getExitCode());
        self::assertStringContainsString('unknown preflight operation', $process->getErrorOutput());
    }

    public function test_postgresql_workflow_requires_full_preflight_before_any_migration(): void
    {
        $workflow = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/ci-postgresql-rls.yml');
        self::assertIsString($workflow);
        $first = strpos($workflow, 'php scripts/ci/verify-ephemeral-postgres.php --verify');
        $migrate = strpos($workflow, 'php artisan migrate ');
        self::assertNotFalse($first);
        self::assertNotFalse($migrate);
        self::assertLessThan($migrate, $first);
        self::assertGreaterThanOrEqual(4, substr_count($workflow, 'php scripts/ci/verify-ephemeral-postgres.php --verify'));
        foreach (['migrate:fresh', 'db:wipe', 'migrate:refresh'] as $command) {
            self::assertStringNotContainsString($command, $workflow);
        }
    }
}
