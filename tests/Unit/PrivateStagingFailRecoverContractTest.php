<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** F3-269 source gate. Real failure/recovery belongs to ephemeral CI only. */
final class PrivateStagingFailRecoverContractTest extends TestCase
{
    public function test_fault_injection_is_runner_scoped_and_cannot_target_real_services(): void
    {
        $script = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/verify-private-staging-fail-recover.sh',
        );
        self::assertIsString($script);

        foreach ([
            'set -euo pipefail',
            'GITHUB_EVENT_NAME',
            'RUNNER_ENVIRONMENT',
            'github-hosted',
            'COMPOSE_PROJECT_NAME',
            'COMPOSE_FILE',
            "origin='http://127.0.0.1:18080'",
            'docker pause "$app_cid"',
            'docker unpause "$app_cid"',
            '502', '503', '504',
            'x-content-type-options: nosniff',
            'x-frame-options: DENY',
            'cache-control: no-store',
            'set-cookie:',
            'recovered=true',
            'trap restore_backend EXIT',
            'paused=true',
            'paused=false',
            'DRAFT/NO-DEPLOY',
        ] as $token) {
            self::assertStringContainsString($token, $script, $token);
        }
        foreach ([
            'ssh ', 'scp ', 'rsync ', 'kubectl ',
            'docker compose down', 'docker compose rm',
            'docker compose stop web',
            'docker compose stop -t 5 app',
            'docker compose start app', 'docker compose stop db',
            'docker compose stop postgres', 'docker push',
            'artisan migrate', 'artisan db:seed', 'DB_PASSWORD',
            'APP_KEY=', 'curl -X POST', 'https://',
            '0.0.0.0:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $script, $forbidden);
        }
    }

    public function test_failure_proof_is_sequenced_after_smoke_isolation_and_before_cleanup(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-private-staging-runtime-smoke.yml',
        );
        self::assertIsString($workflow);
        foreach ([
            "'scripts/ci/verify-private-staging-fail-recover.sh'",
            "'tests/Unit/PrivateStagingFailRecoverContractTest.php'",
            'bash scripts/ci/verify-private-staging-fail-recover.sh',
            "github.event.pull_request.number == 517",
        ] as $token) {
            self::assertStringContainsString($token, $workflow);
        }
        $smoke = strpos($workflow, 'bash scripts/ci/verify-private-staging-runtime.sh');
        $isolation = strpos($workflow, 'bash scripts/ci/verify-private-staging-isolation.sh');
        $failure = strpos($workflow, 'bash scripts/ci/verify-private-staging-fail-recover.sh');
        $cleanup = strpos($workflow, 'docker compose down --remove-orphans');
        self::assertIsInt($smoke);
        self::assertIsInt($isolation);
        self::assertIsInt($failure);
        self::assertIsInt($cleanup);
        self::assertTrue($smoke < $isolation && $isolation < $failure && $failure < $cleanup);
    }
}
