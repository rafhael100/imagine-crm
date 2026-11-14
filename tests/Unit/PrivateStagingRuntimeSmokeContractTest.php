<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-267 independent source contract: CI build is disposable, never VPS deploy.
 * This test cannot assert that the real container booted; CI smoke does that.
 */
final class PrivateStagingRuntimeSmokeContractTest extends TestCase
{
    public function test_real_runtime_job_is_draft_pr_only_without_secrets_or_deploy(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-private-staging-runtime-smoke.yml',
        );
        self::assertIsString($workflow);
        foreach ([
            "github.event_name == 'pull_request'",
            'github.event.pull_request.draft == true',
            'permissions:',
            'contents: read',
            'runs-on: ubuntu-latest',
            'timeout-minutes: 35',
            'COMPOSE_DISABLE_ENV_FILE:',
            'COMPOSE_PROJECT_NAME: imagine-crm-stage-review',
            'docker compose config --format json | php scripts/ci/verify-staging-rendered-compose.php',
            'docker compose build --progress plain app web',
            'docker compose up -d --no-build --no-recreate',
            'bash scripts/ci/verify-private-staging-runtime.sh',
            'if: always()',
            'docker compose down --remove-orphans --volumes --rmi local',
            'persist-credentials: false',
        ] as $needle) {
            self::assertStringContainsString($needle, $workflow, $needle);
        }
        foreach ([
            'secrets.', 'docker push', 'docker login', 'kubectl ',
            'scp ', 'ssh ', 'rsync ', 'api.github.com',
            'environment: production', 'environment: staging',
            'repository_dispatch', 'deploy:', 'curl -X POST',
            'workflow_run:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $workflow, $forbidden);
        }
    }

    public function test_runtime_smoke_requires_hosted_runner_and_deny_only_http(): void
    {
        $script = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/verify-private-staging-runtime.sh',
        );
        self::assertIsString($script);
        foreach ([
            'set -euo pipefail',
            'GITHUB_EVENT_NAME',
            'RUNNER_ENVIRONMENT',
            'github-hosted',
            'COMPOSE_PROJECT_NAME',
            'composer check-platform-reqs --no-dev',
            'docker compose exec -T app php-fpm -t',
            'docker compose exec -T web nginx -t',
            'origin="http://127.0.0.1:18080"',
            '"/up"',
            'for endpoint in / /login /register',
            'for endpoint in /login /register',
            'for endpoint in /.env',
            'x-content-type-options: nosniff',
            'x-frame-options: DENY',
            'cache-control: no-store',
            'DRAFT/NO-DEPLOY',
        ] as $needle) {
            self::assertStringContainsString($needle, $script, $needle);
        }
        foreach ([
            'artisan migrate', 'db:seed', 'php artisan serve',
            'MAIL_PASSWORD', 'DB_PASSWORD', 'APP_KEY=',
            'docker push', 'ssh ', 'https://', '0.0.0.0:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $script, $forbidden);
        }
    }
}
