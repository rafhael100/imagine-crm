<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * SEGDBA F3-207: execute the real SEC-CI-02 preflight ONLY in --static-only
 * mode, with hermetic synthetic environment variables.
 *
 * This cannot contact Docker or any database. It checks the documented
 * pull_request and fixed-DEV push triggers and denies untrusted alternatives.
 */
final class EphemeralPostgresStaticPolicyMatrixTest extends TestCase
{
    private const DEV = 'dev/fast-track-crm-2026-11-04';

    /** @return array<string,string|false> */
    private static function safeEnv(array $overrides = []): array
    {
        $variables = [
            'CI' => 'true',
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
            'RUNNER_ENVIRONMENT' => 'github-hosted',
            'GITHUB_EVENT_NAME' => 'pull_request',
            'GITHUB_REF' => 'refs/pull/123/merge',
            'GITHUB_BASE_REF' => self::DEV,
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'imagine_crm_ci',
            'DB_USERNAME' => 'postgres',
            'DB_PASSWORD' => 'ci-only-password',
            'PGPASSWORD' => 'ci-only-password',
            // These must be explicitly UNSET, not inherited from the runner.
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
            // A harmless static-only run must not depend on docker/psql PATH.
            'PATH' => '/intentionally-no-external-binaries',
        ];

        return array_replace($variables, $overrides);
    }

    private static function execute(array $environment, string $flag = '--static-only'): Process
    {
        $script = dirname(__DIR__, 2).'/scripts/ci/verify-ephemeral-postgres.php';
        self::assertFileExists($script);
        $process = new Process([PHP_BINARY, $script, $flag], dirname(__DIR__, 2), $environment);
        $process->setTimeout(10);
        $process->run();

        return $process;
    }

    public function test_only_fixed_dev_push_and_approved_pr_merge_refs_are_syntactically_eligible(): void
    {
        $valid = [
            'pull request into DEV' => [],
            'pull request into main' => ['GITHUB_BASE_REF' => 'main'],
            'push into fixed DEV' => [
                'GITHUB_EVENT_NAME' => 'push',
                'GITHUB_REF' => 'refs/heads/'.self::DEV,
                'GITHUB_BASE_REF' => '',
            ],
        ];

        foreach ($valid as $name => $overrides) {
            $run = self::execute(self::safeEnv($overrides));
            self::assertSame(0, $run->getExitCode(), $name.': '.$run->getErrorOutput());
            self::assertStringContainsString(
                'static checks passed; database NOT verified.',
                $run->getOutput(),
                $name,
            );
            self::assertStringNotContainsString('confirmed matching disposable', $run->getOutput());
        }
    }

    public function test_all_other_events_branches_and_merge_shapes_are_denied(): void
    {
        $invalids = [
            'push into main' => [
                'GITHUB_EVENT_NAME'=>'push', 'GITHUB_REF'=>'refs/heads/main',
                'GITHUB_BASE_REF'=>'',
            ],
            'push into sibling branch' => [
                'GITHUB_EVENT_NAME'=>'push',
                'GITHUB_REF'=>'refs/heads/dev/another-branch',
                'GITHUB_BASE_REF'=>'',
            ],
            'push with stale PR base' => [
                'GITHUB_EVENT_NAME'=>'push',
                'GITHUB_REF'=>'refs/heads/'.self::DEV,
                'GITHUB_BASE_REF'=>'main',
            ],
            'pull_request_target event' => ['GITHUB_EVENT_NAME'=>'pull_request_target'],
            'workflow_dispatch event' => ['GITHUB_EVENT_NAME'=>'workflow_dispatch'],
            'scheduled task' => ['GITHUB_EVENT_NAME'=>'schedule'],
            'unapproved tag build' => ['GITHUB_EVENT_NAME'=>'push', 'GITHUB_REF'=>'refs/tags/v1.0'],
            'pull request wrong target' => ['GITHUB_BASE_REF'=>'release'],
            'pull request wrong ref type' => ['GITHUB_REF'=>'refs/heads/'.self::DEV],
            'pull request head SHA instead of merge' => ['GITHUB_REF'=>'refs/pull/123/head'],
            'pull request zero number' => ['GITHUB_REF'=>'refs/pull/0/merge'],
            'pull request without merge suffix' => ['GITHUB_REF'=>'refs/pull/123'],
            'pull request with extra component' => ['GITHUB_REF'=>'refs/pull/123/merge/unsafe'],
        ];
        foreach ($invalids as $name => $changes) {
            self::denied($name, $changes);
        }
    }

    public function test_fork_self_hosted_production_target_or_alternative_libpq_paths_are_denied(): void
    {
        $invalids = [
            'repository fork' => ['GITHUB_REPOSITORY'=>'unknown/fork'],
            'self-hosted runner' => ['RUNNER_ENVIRONMENT'=>'self-hosted'],
            'not CI' => ['CI'=>'false'],
            'not GitHub Actions' => ['GITHUB_ACTIONS'=>'false'],
            'real environment' => ['APP_ENV'=>'production'],
            'non-PostgreSQL' => ['DB_CONNECTION'=>'sqlite'],
            'other DB host' => ['DB_HOST'=>'database.example.invalid'],
            'other database' => ['DB_DATABASE'=>'imagine_crm_production'],
            'wrong database port' => ['DB_PORT'=>'5433'],
            'other DB username' => ['DB_USERNAME'=>'admin'],
            'empty password' => ['DB_PASSWORD'=>''],
            'mismatched DB password' => ['DB_PASSWORD'=>'fictional-other-password'],
            'mismatched pg password' => ['PGPASSWORD'=>'fictional-other-password'],
            'connection URL' => ['DB_URL'=>'postgres://fake.invalid/other'],
            'fallback database URL' => ['DATABASE_URL'=>'postgres://fake.invalid/other'],
            'host override' => ['PGHOST'=>'fake.invalid'],
            'hostaddr override' => ['PGHOSTADDR'=>'127.0.0.2'],
            'port override' => ['PGPORT'=>'5434'],
            'db override' => ['PGDATABASE'=>'other_db'],
            'user override' => ['PGUSER'=>'another_role'],
            'service override' => ['PGSERVICE'=>'local'],
            'service file override' => ['PGSERVICEFILE'=>'/tmp/fictional.conf'],
            'options override' => ['PGOPTIONS'=>'-c search_path=public'],
            'TLS mode override' => ['PGSSLMODE'=>'disable'],
            'password file override' => ['PGPASSFILE'=>'/tmp/fictional.pgpass'],
        ];
        foreach ($invalids as $name => $changes) {
            self::denied($name, $changes);
        }
    }

    public function test_unrecognized_mode_is_rejected_before_any_database_probe(): void
    {
        $run = self::execute(self::safeEnv(), '--migrate');
        self::assertSame(42, $run->getExitCode());
        self::assertStringContainsString('SEC-CI-02 DENIED:', $run->getErrorOutput());
        self::assertStringNotContainsString('database NOT verified.', $run->getOutput());
    }

    private static function denied(string $name, array $overrides): void
    {
        $run = self::execute(self::safeEnv($overrides));
        self::assertSame(42, $run->getExitCode(), $name.': '.$run->getOutput().' '.$run->getErrorOutput());
        self::assertStringContainsString('SEC-CI-02 DENIED:', $run->getErrorOutput(), $name);
        self::assertStringNotContainsString('static checks passed', $run->getOutput(), $name);
        self::assertStringNotContainsString('fictional-other-password', $run->getErrorOutput(), $name);
    }
}
