<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-271: source gate for review inventory only.
 * Never claims registry provenance, SBOM or compliance approval.
 */
final class StagingEvidenceInventoryContractTest extends TestCase
{
    public function test_inventory_is_ephemeral_secret_free_and_content_limited(): void
    {
        $script = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/emit-private-staging-inventory.sh',
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
            'docker compose exec -T app composer show --no-dev --format=json',
            'docker compose exec -T app php -m',
            "docker inspect --format '{{.Image}}'",
            'git rev-parse HEAD',
            'sha256sum composer.lock',
            'sha256sum infra/staging/Dockerfile',
            'sha256sum infra/staging/Dockerfile.web',
            'sha256sum infra/staging/compose.yaml',
            'composer-packages.tsv',
            'php-modules.txt',
            'manifest.json',
            'SHA256SUMS',
            'sha256sum --check SHA256SUMS',
            '"customer_data" => false',
            '"production_approval" => false',
            'F3-271 PASS:',
        ] as $required) {
            self::assertStringContainsString($required, $script, $required);
        }

        foreach ([
            'ssh ', 'scp ', 'rsync ', 'docker push', 'docker login',
            'composer config --auth', 'artisan migrate', 'artisan db:seed',
            '.env', 'DB_PASSWORD', 'APP_KEY=', 'secrets.',
            'curl ', 'wget ', 'https://', 'docker save',
            'docker export', 'docker logs', 'docker cp',
            'chmod 777', 'GITHUB_TOKEN',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $script, $forbidden);
        }
    }

    public function test_workflow_archives_inventory_after_runtime_evidence_before_cleanup(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-private-staging-runtime-smoke.yml',
        );
        self::assertIsString($workflow);
        foreach ([
            "'scripts/ci/emit-private-staging-inventory.sh'",
            "'tests/Unit/StagingEvidenceInventoryContractTest.php'",
            'bash -n scripts/ci/emit-private-staging-inventory.sh',
            'bash scripts/ci/emit-private-staging-inventory.sh',
            'actions/upload-artifact@v4',
            'name: crm-f3-271-review-inventory',
            'retention-days: 7',
            'if-no-files-found: error',
            'include-hidden-files: false',
            'permissions:',
            'contents: read',
            'persist-credentials: false',
        ] as $required) {
            self::assertStringContainsString($required, $workflow);
        }
        $smoke = strpos($workflow, 'bash scripts/ci/verify-private-staging-runtime.sh');
        $recovery = strpos($workflow, 'bash scripts/ci/verify-private-staging-fail-recover.sh');
        $inventory = strpos($workflow, 'bash scripts/ci/emit-private-staging-inventory.sh');
        $upload = strpos($workflow, 'actions/upload-artifact@v4');
        $cleanup = strpos($workflow, 'docker compose down --remove-orphans');
        self::assertIsInt($smoke);
        self::assertIsInt($recovery);
        self::assertIsInt($inventory);
        self::assertIsInt($upload);
        self::assertIsInt($cleanup);
        self::assertTrue(
            $smoke < $recovery && $recovery < $inventory
            && $inventory < $upload && $upload < $cleanup,
        );
    }
}
