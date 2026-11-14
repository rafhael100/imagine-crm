<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * SEC-CI-SRC-01: fail-closed source regression checks for the two current
 * GitHub-hosted CI workflows. This is an aid to code review, NOT a security
 * boundary for malicious workflow editors, runners or production databases.
 *
 * It never executes shell commands, opens sockets or reads a database.
 */
final class CiWorkflowSourceGuardTest extends TestCase
{
    private const APPROVED_TRIGGERS_AND_PERMISSIONS = <<<'YAML'
on:
  push:
    branches:
      - dev/fast-track-crm-2026-11-04
  pull_request:
    branches:
      - main
      - dev/fast-track-crm-2026-11-04

permissions:
  contents: read
YAML;

    /**
     * @return list<string> diagnostic codes without leaking workflow source.
     */
    private static function problems(string $yaml, bool $postgres): array
    {
        $issues = [];

        if (substr_count($yaml, self::APPROVED_TRIGGERS_AND_PERMISSIONS) !== 1
            || preg_match_all('/^on:$/m', $yaml) !== 1
            || preg_match_all('/^permissions:$/m', $yaml) !== 1) {
            $issues[] = 'trigger_permissions';
        }

        // Deliberately reject any elevated runner/token setting even if an
        // unrelated expected text block remains in the YAML file.
        if (preg_match('/(?m)^\s*(?:contents|actions|checks|id-token|issues|packages|pull-requests):\s*write\s*$/', $yaml)
            || substr_count($yaml, 'permissions:'."\n".'  contents: read') !== 1) {
            $issues[] = 'token_permissions';
        }
        if (preg_match_all('/(?m)^[ \\t]*runs-on:[ \\t]*([^\\r\\n]+)[ \\t]*$/', $yaml, $runnerMatches) !== 1
            || trim($runnerMatches[1][0] ?? '') !== 'ubuntu-latest') {
            $issues[] = 'runner';
        }
        if (preg_match('/\$\{\{\s*(?:secrets|vars)\./', $yaml) === 1) {
            $issues[] = 'unreviewed_external_settings';
        }
        if (str_contains($yaml, 'pull_request_target')
            || str_contains($yaml, 'workflow_dispatch')
            || str_contains($yaml, 'workflow_call')
            || str_contains($yaml, 'schedule:')) {
            $issues[] = 'unreviewed_events';
        }

        // Hard-coded F0 execution must never obtain the PostgreSQL CI
        // credentials or run migrations; the browser smoke stays offline.
        if (!$postgres) {
            if (!str_contains($yaml, "DB_CONNECTION: sqlite\n")
                || !str_contains($yaml, "DB_DATABASE: ':memory:'")
                || !str_contains($yaml, 'php artisan test --no-ansi')
                || !str_contains($yaml, 'php scripts/ci/check-offline-prototype-browser.php')
                || !str_contains($yaml, 'php scripts/ci/check-offline-prototype-320.php')
                || preg_match('/\b(?:artisan\s+(?:migrate|db:)|psql\s|postgres:16|PGPASSWORD|DB_PASSWORD)\b/i', $yaml)) {
                $issues[] = 'f0_scope';
            }

            return $issues;
        }

        if (!str_contains($yaml, 'image: postgres:16')
            || !str_contains($yaml, 'POSTGRES_DB: imagine_crm_ci')
            || !str_contains($yaml, 'DB_DATABASE: imagine_crm_ci')
            || !str_contains($yaml, 'DB_HOST: 127.0.0.1')
            || !str_contains($yaml, 'DB_PORT: 5432')
            || !str_contains($yaml, 'DB_USERNAME: postgres')
            || !str_contains($yaml, 'APP_ENV: testing')
            || substr_count($yaml, 'NOBYPASSRLS') !== 6
            || preg_match('/(?<!NO)\bBYPASSRLS\b/', $yaml)) {
            $issues[] = 'disposable_pg_roles';
        }

        $approvedCommands = [
            'php artisan migrate --force --no-interaction',
            'php artisan migrate --path=database/experimental-migrations/auth --force --no-interaction',
            'php artisan migrate --path=database/experimental-migrations/async --force --no-interaction',
            'php artisan migrate --path=database/experimental-migrations/trial --force --no-interaction',
        ];
        $actual = [];
        $guardCount = 0;
        $lines = explode("\n", $yaml);
        foreach ($lines as $index => $line) {
            $command = trim($line);
            if ($command === 'php scripts/ci/verify-ephemeral-postgres.php --verify') {
                ++$guardCount;
            }
            if (preg_match('/\bphp\s+artisan\s+(?:migrate|db:)/i', $command)) {
                $actual[] = $command;
                if (($lines[$index - 1] ?? '') !== '          php scripts/ci/verify-ephemeral-postgres.php --verify') {
                    $issues[] = 'unguarded_migration';
                }
            }
        }
        if ($guardCount !== 4 || $actual !== $approvedCommands) {
            $issues[] = 'migration_inventory';
        }
        if (preg_match('/\b(?:migrate:(?:fresh|refresh|reset|rollback)|db:wipe|DROP\s+(?:DATABASE|TABLE|SCHEMA)|TRUNCATE\s+TABLE)\b/i', $yaml)) {
            $issues[] = 'destructive_command';
        }

        return array_values(array_unique($issues));
    }

    private static function workflow(bool $postgres): string
    {
        $file = $postgres ? 'ci-postgresql-rls.yml' : 'ci-f0.yml';
        $source = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/'.$file);
        self::assertIsString($source);

        return $source;
    }

    public function test_real_workflow_sources_preserve_approved_ephemeral_scope(): void
    {
        self::assertSame([], self::problems(self::workflow(false), false));
        self::assertSame([], self::problems(self::workflow(true), true));
    }

    public function test_negative_source_mutations_are_rejected_without_running_migrations(): void
    {
        $pg = self::workflow(true);
        $f0 = self::workflow(false);

        $mutations = [
            [$pg, '  pull_request:', '  pull_request_target:', true, 'trigger_permissions'],
            [$pg, '  contents: read', '  contents: write', true, 'token_permissions'],
            [$pg, 'runs-on: ubuntu-latest', 'runs-on: self-hosted', true, 'runner'],
            [$pg, 'DB_HOST: 127.0.0.1', 'DB_HOST: remote.invalid', true, 'disposable_pg_roles'],
            [$pg, 'APP_ENV: testing', 'APP_ENV: production', true, 'disposable_pg_roles'],
            [$pg, 'NOBYPASSRLS', 'BYPASSRLS', true, 'disposable_pg_roles'],
            [$pg, 'php artisan migrate --force --no-interaction',
                'php artisan migrate:fresh --force --no-interaction', true, 'migration_inventory'],
            [$pg, 'php artisan migrate --path=database/experimental-migrations/async --force --no-interaction',
                'php artisan migrate --path=database/migrations --force --no-interaction', true, 'migration_inventory'],
            [$pg, 'php artisan migrate --path=database/experimental-migrations/trial --force --no-interaction',
                'php artisan migrate --path=database/migrations --force --no-interaction', true, 'migration_inventory'],
            [$pg, 'php scripts/ci/verify-ephemeral-postgres.php --verify'."\n".'          php artisan migrate --force --no-interaction',
                'php artisan migrate --force --no-interaction', true, 'unguarded_migration'],
            [$f0, "DB_CONNECTION: sqlite", "DB_CONNECTION: pgsql", false, 'f0_scope'],
            [$f0, 'php artisan test --no-ansi', 'php artisan migrate:fresh --force', false, 'f0_scope'],
            [$f0, 'runs-on: ubuntu-latest', 'runs-on: self-hosted', false, 'runner'],
        ];

        foreach ($mutations as [$original, $from, $to, $isPg, $expected]) {
            self::assertGreaterThan(0, substr_count($original, $from), 'Test drift: mutation source not found.');
            $tampered = preg_replace('/'.preg_quote($from, '/').'/', $to, $original, 1);
            self::assertIsString($tampered);
            self::assertContains(
                $expected,
                self::problems($tampered, $isPg),
                'A source-only CI regression mutation was accepted: '.$expected,
            );
        }
    }

    public function test_injecting_an_extra_migration_cannot_reuse_previous_guard(): void
    {
        $pg = self::workflow(true);
        $needle = '          php artisan migrate --force --no-interaction';
        self::assertSame(1, substr_count($pg, $needle));
        $tampered = str_replace($needle, $needle."\n".$needle, $pg);
        $problems = self::problems($tampered, true);
        self::assertContains('unguarded_migration', $problems);
        self::assertContains('migration_inventory', $problems);
    }
}
