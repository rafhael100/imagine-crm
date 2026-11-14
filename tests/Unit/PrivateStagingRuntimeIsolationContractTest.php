<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-268: source guard for disposable-runner isolation assertions only.
 * The actual read-only/rootless/network state is tested by the CI script.
 */
final class PrivateStagingRuntimeIsolationContractTest extends TestCase
{
    public function test_runtime_isolation_script_checks_containers_not_only_yaml(): void
    {
        $root = dirname(__DIR__, 2);
        $script = file_get_contents($root.'/scripts/ci/verify-private-staging-isolation.sh');
        self::assertIsString($script);
        foreach ([
            'set -euo pipefail',
            'GITHUB_EVENT_NAME',
            'RUNNER_ENVIRONMENT',
            'COMPOSE_PROJECT_NAME',
            'docker compose ps -q',
            "docker inspect --format '{{.HostConfig.ReadonlyRootfs}}'",
            "docker inspect --format '{{json .HostConfig.CapDrop}}'",
            "docker inspect --format '{{json .HostConfig.SecurityOpt}}'",
            'docker inspect --format',
            'NetworkSettings.Networks',
            'docker compose exec -T app id -u',
            'docker compose exec -T web id -u',
            '/var/www/html/.env',
            '/var/www/html/database/experimental-migrations',
            '/var/www/html/vendor/bin/phpunit',
            '/var/www/html/composer.lock',
            '/proc/net/route',
            '"00000000"',
            'docker compose port web 8080',
            'docker compose port app 9000',
            'docker network inspect imagine-crm-stage-review_crm_loopback',
            'com.docker.network.bridge.enable_ip_masquerade',
            'com.docker.network.bridge.host_binding_ipv4',
            '127.0.0.1:18080',
            'PASS:',
        ] as $needle) {
            self::assertStringContainsString($needle, $script, $needle);
        }
        foreach ([
            'artisan migrate', 'db:seed', 'docker push',
            'docker compose build', 'docker compose up',
            'ssh ', 'scp ', 'https://', 'APP_KEY=', 'DB_PASSWORD',
            'curl ', 'wget ', 'chmod 777',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $script, $forbidden);
        }
    }

    public function test_private_runtime_workflow_keeps_isolation_gate_before_teardown(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-private-staging-runtime-smoke.yml',
        );
        self::assertIsString($workflow);
        self::assertStringContainsString(
            "github.event.pull_request.number == 517", $workflow,
        );
        self::assertStringContainsString(
            'bash scripts/ci/verify-private-staging-isolation.sh', $workflow,
        );
        self::assertStringContainsString(
            "'scripts/ci/verify-private-staging-isolation.sh'", $workflow,
        );
        self::assertStringContainsString(
            'docker compose down --remove-orphans --volumes --rmi local', $workflow,
        );
        $smokeAt = strpos($workflow, 'bash scripts/ci/verify-private-staging-runtime.sh');
        $isolateAt = strpos($workflow, 'bash scripts/ci/verify-private-staging-isolation.sh');
        $cleanupAt = strpos($workflow, 'docker compose down --remove-orphans');
        self::assertIsInt($smokeAt);
        self::assertIsInt($isolateAt);
        self::assertIsInt($cleanupAt);
        self::assertTrue($smokeAt < $isolateAt && $isolateAt < $cleanupAt);
    }
}
