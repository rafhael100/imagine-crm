<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-273: source-only guard for public Composer advisory scan.
 * An audit of known advisories is not proof of supply-chain safety.
 */
final class StagingComposerAdvisoryContractTest extends TestCase
{
    public function test_public_advisory_scan_is_production_only_read_only_and_scope_gated(): void
    {
        $path = dirname(__DIR__, 2).'/.github/workflows/ci-crm-staging-composer-advisory.yml';
        $workflow = file_get_contents($path);
        self::assertIsString($workflow);
        foreach ([
            "'composer.json'",
            "'composer.lock'",
            "github.event_name == 'pull_request'",
            'github.event.pull_request.draft == true',
            'github.event.pull_request.number == 517',
            'github.event.pull_request.head.repo.full_name == github.repository',
            'permissions:',
            'contents: read',
            'timeout-minutes: 8',
            'persist-credentials: false',
            'composer audit --locked --no-dev --format=json',
            'file_get_contents($argv[1])',
            '"advisories"',
            '"abandoned"',
            'F3-273 NO-GO',
            'F3-273 PASS',
        ] as $required) {
            self::assertStringContainsString($required, $workflow, $required);
        }
        foreach ([
            'secrets.', 'composer install', 'composer update',
            'composer config --auth', 'docker compose up',
            'docker compose build', 'docker push', 'docker login',
            'ssh ', 'scp ', 'rsync ', 'kubectl ', 'php artisan',
            'environment: production', 'APP_KEY=', 'DB_PASSWORD',
            'actions/upload-artifact',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $workflow, $forbidden);
        }
    }

    public function test_advisory_workflow_does_not_enable_production_application(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-staging-composer-advisory.yml',
        );
        self::assertIsString($workflow);
        self::assertStringContainsString('No PHP container deployment', $workflow);
        self::assertStringContainsString('no install, no migrations', $workflow);
        self::assertStringContainsString('no credentials', $workflow);
        self::assertStringContainsString('publicly reported advisories', $workflow);
    }
}
