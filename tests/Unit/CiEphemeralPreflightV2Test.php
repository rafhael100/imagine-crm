<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * All subprocesses run --static-only: never open Docker, SQL or a network.
 * --verify executes separately in the ephemeral PostgreSQL CI service.
 */
final class CiEphemeralPreflightV2Test extends TestCase
{
    private static function profile(): array
    {
        return [
            'CI' => 'true',
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
            'GITHUB_EVENT_NAME' => 'pull_request',
            'GITHUB_REF' => 'refs/pull/55/merge',
            'GITHUB_BASE_REF' => 'dev/fast-track-crm-2026-11-04',
            'RUNNER_ENVIRONMENT' => 'github-hosted',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'imagine_crm_ci',
            'DB_USERNAME' => 'postgres',
            'DB_PASSWORD' => 'fake-ephemeral-password',
            'PGPASSWORD' => 'fake-ephemeral-password',
            'DB_URL' => false,
            'DATABASE_URL' => false,
            'PGHOST' => false,
            'PGHOSTADDR' => false,
            'PGPORT' => false,
            'PGDATABASE' => false,
            'PGUSER' => false,
            'PGSERVICE' => false,
            'PGSERVICEFILE' => false,
            'PGOPTIONS' => false,
            'PGSSLMODE' => false,
            'PGPASSFILE' => false,
        ];
    }

    private static function runPreflight(array $changes = [], string $mode = '--static-only'): Process
    {
        $path = dirname(__DIR__, 2).'/scripts/ci/verify-ephemeral-postgres.php';
        $process = new Process(
            [PHP_BINARY, $path, $mode],
            dirname(__DIR__, 2),
            array_replace(self::profile(), $changes),
        );
        $process->run();
        return $process;
    }

    public static function approvedTriggers(): array
    {
        return [
            'PR targeting DEV' => [['GITHUB_EVENT_NAME' => 'pull_request',
                'GITHUB_REF' => 'refs/pull/55/merge', 'GITHUB_BASE_REF' => 'dev/fast-track-crm-2026-11-04']],
            'PR targeting main' => [['GITHUB_EVENT_NAME' => 'pull_request',
                'GITHUB_REF' => 'refs/pull/55/merge', 'GITHUB_BASE_REF' => 'main']],
            'push to the exact DEV branch' => [['GITHUB_EVENT_NAME' => 'push',
                'GITHUB_REF' => 'refs/heads/dev/fast-track-crm-2026-11-04', 'GITHUB_BASE_REF' => false]],
        ];
    }

    #[DataProvider('approvedTriggers')]
    public function test_exact_existing_workflow_triggers_pass_only_static_checks(array $changes): void
    {
        $result = self::runPreflight($changes);
        self::assertSame(0, $result->getExitCode(), $result->getErrorOutput());
        self::assertStringContainsString('database NOT verified', $result->getOutput());
    }

    public static function rejectedConfigurations(): array
    {
        return [
            'non GitHub CI' => [['CI' => 'false']],
            'non GitHub Actions' => [['GITHUB_ACTIONS' => 'false']],
            'another repository' => [['GITHUB_REPOSITORY' => 'someone/fork']],
            'unsupported event' => [['GITHUB_EVENT_NAME' => 'workflow_dispatch']],
            'push to main' => [['GITHUB_EVENT_NAME' => 'push',
                'GITHUB_REF' => 'refs/heads/main', 'GITHUB_BASE_REF' => false]],
            'push to another DEV' => [['GITHUB_EVENT_NAME' => 'push',
                'GITHUB_REF' => 'refs/heads/dev/experimental', 'GITHUB_BASE_REF' => false]],
            'push with invented base' => [['GITHUB_EVENT_NAME' => 'push',
                'GITHUB_REF' => 'refs/heads/dev/fast-track-crm-2026-11-04', 'GITHUB_BASE_REF' => 'main']],
            'PR to unknown base' => [['GITHUB_BASE_REF' => 'release']],
            'PR masquerading as branch' => [['GITHUB_REF' => 'refs/heads/dev/fast-track-crm-2026-11-04']],
            'PR with malformed ref' => [['GITHUB_REF' => 'refs/pull/abc/merge']],
            'self hosted runner' => [['RUNNER_ENVIRONMENT' => 'self-hosted']],
            'production app' => [['APP_ENV' => 'production']],
            'SQLite instead of PostgreSQL' => [['DB_CONNECTION' => 'sqlite']],
            'remote DB host' => [['DB_HOST' => 'db.example.invalid']],
            'nonstandard port' => [['DB_PORT' => '5433']],
            'persistent DB name' => [['DB_DATABASE' => 'imagine_crm_production']],
            'unexpected DB user' => [['DB_USERNAME' => 'root']],
            'missing password' => [['DB_PASSWORD' => false]],
            'password mismatch' => [['PGPASSWORD' => 'another-password']],
            'alternate database URL' => [['DB_URL' => 'postgresql://production.invalid/db']],
            'alternate libpq host' => [['PGHOST' => 'production.invalid']],
            'alternate libpq port' => [['PGPORT' => '7543']],
            'alternate service config' => [['PGSERVICE' => 'remote']],
            'alternate SSL override' => [['PGSSLMODE' => 'require']],
            'alternate passfile' => [['PGPASSFILE' => '/tmp/secret']],
        ];
    }

    #[DataProvider('rejectedConfigurations')]
    public function test_unsafe_settings_fail_before_opening_database_or_docker(array $changes): void
    {
        $result = self::runPreflight($changes);
        self::assertSame(42, $result->getExitCode(), $result->getOutput().$result->getErrorOutput());
        self::assertStringContainsString('SEC-CI-02 DENIED:', $result->getErrorOutput());
    }

    public function test_unknown_mode_is_denied(): void
    {
        $result = self::runPreflight([], '--wipe');
        self::assertSame(42, $result->getExitCode());
        self::assertStringContainsString('unrecognized preflight option', $result->getErrorOutput());
    }

    public function test_workflow_runs_full_guard_before_all_existing_schema_migrations(): void
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/ci-postgresql-rls.yml');
        self::assertIsString($content);

        $lines = explode("\n", $content);
        $guarded = 0;
        $lastGuard = -1;
        $lastMigrate = -1;

        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, 'php scripts/ci/verify-ephemeral-postgres.php --verify')) {
                $lastGuard = $lineNumber;
                $guarded++;
            }
            if (str_contains($line, 'php artisan migrate ')) {
                self::assertGreaterThan($lastMigrate, $lastGuard, 'Each migration needs a NEW full guard.');
                self::assertLessThan($lineNumber, $lastGuard, 'Guard must precede migration.');
                $lastMigrate = $lineNumber;
            }
        }
        self::assertSame(3, $guarded, 'Three existing migration steps must each be guarded.');
        self::assertGreaterThanOrEqual(3, $lastMigrate);
        foreach (['migrate:fresh', 'migrate:refresh', 'db:wipe'] as $dangerous) {
            self::assertStringNotContainsString($dangerous, $content);
        }
    }
}
