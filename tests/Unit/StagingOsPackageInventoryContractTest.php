<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** F3-274: OS package inventory remains read-only in the ephemeral PR runner. */
final class StagingOsPackageInventoryContractTest extends TestCase
{
    public function test_os_inventory_requires_real_runner_and_exports_only_sanitized_packages(): void
    {
        $script = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/emit-private-staging-os-inventory.sh',
        );
        self::assertIsString($script);
        foreach ([
            'set -euo pipefail',
            'GITHUB_EVENT_NAME',
            'RUNNER_ENVIRONMENT',
            'github-hosted',
            'COMPOSE_PROJECT_NAME',
            'COMPOSE_FILE',
            'RUNNER_TEMP',
            'docker compose exec -T app dpkg-query -W',
            'docker compose exec -T web apk info -v',
            'debian-system-packages.tsv',
            'alpine-system-packages.txt',
            'sha256sum --check SHA256SUMS',
            'F3-274 PASS:',
        ] as $token) {
            self::assertStringContainsString($token, $script);
        }
        foreach ([
            'docker cp', 'docker export', 'docker save',
            'docker push', 'docker login', 'artisan migrate', 'db:seed',
            'ssh ', 'scp ', 'rsync ', 'kubectl ', 'curl ', 'wget ',
            'https://', 'APP_KEY=', 'DB_PASSWORD',
            '.env', 'secrets.', 'docker compose down',
            'apk add', 'apt install',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $script);
        }
    }

    public function test_os_package_inventory_runs_after_php_hash_manifest_and_before_artifact(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-private-staging-runtime-smoke.yml',
        );
        self::assertIsString($workflow);
        foreach ([
            "'scripts/ci/emit-private-staging-os-inventory.sh'",
            "'tests/Unit/StagingOsPackageInventoryContractTest.php'",
            'bash -n scripts/ci/emit-private-staging-os-inventory.sh',
            'bash scripts/ci/emit-private-staging-os-inventory.sh',
            'actions/upload-artifact@v4',
            'retention-days: 7',
            'include-hidden-files: false',
        ] as $token) {
            self::assertStringContainsString($token, $workflow);
        }
        $phpInventory = strpos($workflow, 'bash scripts/ci/emit-private-staging-inventory.sh');
        $osInventory = strpos($workflow, 'bash scripts/ci/emit-private-staging-os-inventory.sh');
        $artifact = strpos($workflow, 'actions/upload-artifact@v4');
        $cleanup = strpos($workflow, 'docker compose down --remove-orphans');
        self::assertIsInt($phpInventory);
        self::assertIsInt($osInventory);
        self::assertIsInt($artifact);
        self::assertIsInt($cleanup);
        self::assertTrue(
            $phpInventory < $osInventory && $osInventory < $artifact
            && $artifact < $cleanup,
        );
    }

    public function test_non_github_runner_is_denied_before_docker_commands(): void
    {
        $script = dirname(__DIR__, 2).'/scripts/ci/emit-private-staging-os-inventory.sh';
        $process = new \Symfony\Component\Process\Process(['bash', $script]);
        $process->setEnv([
            'GITHUB_EVENT_NAME' => 'push',
            'RUNNER_ENVIRONMENT' => 'untrusted',
            'COMPOSE_PROJECT_NAME' => 'unapproved-project',
            'COMPOSE_FILE' => 'unapproved.yaml',
            'RUNNER_TEMP' => '/tmp',
        ]);
        $process->run();
        self::assertSame(2, $process->getExitCode());
        self::assertStringContainsString('F3-274 NO-GO', $process->getErrorOutput());
    }
}
