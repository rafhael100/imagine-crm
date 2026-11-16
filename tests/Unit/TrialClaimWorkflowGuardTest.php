<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * SEC-TRIAL-CI-01: static source inventory of the isolated trial CI.
 *
 * This is defense-in-depth against accidental workflow drift, NOT a proof
 * that GitHub infrastructure, reviewers or production are safe.
 * No network, container, migration, credentials or SQL execution.
 */
final class TrialClaimWorkflowGuardTest extends TestCase
{
    private static function source(): string
    {
        $path = dirname(__DIR__, 2).'/.github/workflows/ci-crm-f3-trial-postgres.yml';
        $source = file_get_contents($path);
        self::assertIsString($source);
        return $source;
    }

    /** @return list<string> Stable diagnostic codes, never source or secrets. */
    private static function defects(string $workflow): array
    {
        $errors = [];

        $trigger = "on:\n  pull_request:\n    branches:\n      - dev/fast-track-crm-2026-11-04\n";
        if (substr_count($workflow, $trigger) !== 1
            || substr_count($workflow, "on:\n") !== 1
            || preg_match('/(?m)^\s*(?:push|schedule|workflow_dispatch|pull_request_target|workflow_call):/', $workflow)
            || substr_count($workflow, "permissions:\n  contents: read\n") !== 1) {
            $errors[] = 'trigger_scope';
        }

        if (preg_match('/(?m)^\s*(?:contents|actions|checks|id-token|issues|packages|pull-requests):\s*write\s*$/', $workflow)
            || str_contains($workflow, 'self-hosted')
            || substr_count($workflow, 'runs-on: ubuntu-latest') !== 1
            || preg_match('/\$\{\{\s*(?:secrets|vars)\./', $workflow)) {
            $errors[] = 'runner_token_scope';
        }

        foreach ([
            'image: postgres:16',
            'POSTGRES_DB: imagine_crm_ci',
            'POSTGRES_USER: postgres',
            'DB_CONNECTION: pgsql',
            'DB_HOST: 127.0.0.1',
            'DB_PORT: 5432',
            'DB_DATABASE: imagine_crm_ci',
            'DB_USERNAME: postgres',
            'APP_ENV: testing',
            'CRM_TRIAL_LEDGER_LAB: synthetic-only',
            'TRIAL_TEST_USERNAME: crm_trial_claim_ci',
            'TRIAL_TEST_PASSWORD: ephemeral-trial-insert-only',
        ] as $expected) {
            if (substr_count($workflow, $expected) !== 1) {
                $errors[] = 'disposable_target';
            }
        }

        if (preg_match('/\b(?:migrate:fresh|migrate:refresh|migrate:rollback|db:wipe|DROP\s+(?:TABLE|SCHEMA|DATABASE)|TRUNCATE\s+TABLE)\b/i', $workflow)) {
            $errors[] = 'destructive_command';
        }

        $preflight = strpos($workflow, 'php scripts/ci/verify-ephemeral-postgres.php --verify');
        $migration = strpos($workflow, 'php artisan migrate --path=database/experimental-migrations/trial --force --no-interaction');
        $role = strpos($workflow, 'CREATE ROLE crm_trial_claim_ci LOGIN PASSWORD');
        $specific = strpos($workflow, 'php artisan test --filter=PostgresLabTrialClaimUniqueTest --no-ansi');
        $rotation = strpos($workflow, 'php artisan test --filter=PostgresLabTrialKeyRingTest --no-ansi');

        if ($preflight === false || $migration === false || $role === false
            || $specific === false || $rotation === false
            || !($preflight < $migration && $migration < $role
                && $role < $specific && $specific < $rotation)
            || substr_count($workflow, 'php artisan migrate ') !== 1) {
            $errors[] = 'migration_order';
        }

        // Grant inventory is exact; no broadened claims permissions.
        $expectedRole = "CREATE ROLE crm_trial_claim_ci LOGIN PASSWORD 'ephemeral-trial-insert-only'\n"
            ."            NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;";
        $expectedClaims = "GRANT INSERT (cnpj_hmac, key_version, started_at, expires_at, state)\n"
            ."            ON TABLE crm_lab_trial_claims TO crm_trial_claim_ci;";
        $expectedEpochs = 'GRANT SELECT ON TABLE crm_lab_trial_key_epochs TO crm_trial_claim_ci;';
        if (!str_contains($workflow, $expectedRole)
            || !str_contains($workflow, $expectedClaims)
            || !str_contains($workflow, $expectedEpochs)
            || preg_match('/GRANT\s+(?:ALL|SELECT|UPDATE|DELETE|TRUNCATE)\b[^;]*ON TABLE crm_lab_trial_claims/i', $workflow)
            || preg_match('/GRANT\s+(?:ALL|INSERT|UPDATE|DELETE|TRUNCATE)\b[^;]*ON TABLE crm_lab_trial_key_epochs/i', $workflow)) {
            $errors[] = 'role_privileges';
        }

        if (!str_contains($workflow, "INSERT INTO crm_lab_trial_key_epochs (version, secret_tag)")
            || !str_contains($workflow, "VALUES (1, 'a4b491da4d5ea375653e3b76a6404b921729157c7cd6560c46940a9816a05ae5')")
            || !str_contains($workflow, "GITHUB_STEP_SUMMARY")) {
            $errors[] = 'key_policy_pin';
        }

        return array_values(array_unique($errors));
    }

    public function test_ci_workflow_scope_matches_approved_disposable_contract(): void
    {
        self::assertSame([], self::defects(self::source()));
    }

    public function test_mutations_to_production_or_privileges_fail_closed(): void
    {
        $original = self::source();

        $mutations = [
            ['pull_request:', 'pull_request_target:', 'trigger_scope'],
            ['contents: read', 'contents: write', 'trigger_scope'],
            ['runs-on: ubuntu-latest', 'runs-on: self-hosted', 'runner_token_scope'],
            ['DB_HOST: 127.0.0.1', 'DB_HOST: database.example.invalid', 'disposable_target'],
            ['APP_ENV: testing', 'APP_ENV: production', 'disposable_target'],
            ['CRM_TRIAL_LEDGER_LAB: synthetic-only', 'CRM_TRIAL_LEDGER_LAB: enabled', 'disposable_target'],
            ['php scripts/ci/verify-ephemeral-postgres.php --verify', 'echo bypassed', 'migration_order'],
            ['GRANT INSERT (cnpj_hmac, key_version, started_at, expires_at, state)',
                'GRANT ALL', 'role_privileges'],
            ['GRANT SELECT ON TABLE crm_lab_trial_key_epochs',
                'GRANT UPDATE ON TABLE crm_lab_trial_key_epochs', 'role_privileges'],
            ['NOBYPASSRLS', 'BYPASSRLS', 'role_privileges'],
            ['php artisan migrate --path=database/experimental-migrations/trial --force --no-interaction',
                'php artisan migrate:fresh --force --no-interaction', 'destructive_command'],
            ["VALUES (1, 'a4b491da4d5ea375653e3b76a6404b921729157c7cd6560c46940a9816a05ae5')",
                "VALUES (1, 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff')",
                'key_policy_pin'],
        ];

        foreach ($mutations as [$from, $to, $expected]) {
            self::assertGreaterThan(0, substr_count($original, $from), 'Mutation source drift');
            $tampered = preg_replace('/'.preg_quote($from, '/').'/', $to, $original, 1);
            self::assertIsString($tampered);
            self::assertContains($expected, self::defects($tampered));
        }
    }
}
