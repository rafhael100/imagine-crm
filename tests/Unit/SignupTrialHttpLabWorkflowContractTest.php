<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SignupTrialHttpLabWorkflowContractTest extends TestCase
{
    public function test_http_route_lab_workflow_is_read_only_and_cannot_release_an_endpoint(): void
    {
        $root = dirname(__DIR__, 2);
        $text = file_get_contents($root.'/.github/workflows/crm-f3-253-http-route-seal.yml');
        self::assertIsString($text);
        foreach ([
            'on:', 'pull_request:', 'workflow_dispatch:',
            'permissions:', 'contents: read',
            'APP_ENV: testing', "DB_DATABASE: ':memory:'",
            'MAIL_MAILER: array', "php-version: '8.4'",
            'SignupTrialHttpFixtureFlowTest.php',
            'PublicRouteSurfaceSealTest.php',
            'SignupTrialHttpLabWorkflowContractTest.php',
            'php artisan route:list --json',
            '.uri == "up"', "'__ci/f3-252'",
            'grep -RFq', 'routes app bootstrap',
            'NO-GO', '$GITHUB_STEP_SUMMARY',
        ] as $required) {
            self::assertStringContainsString($required, $text);
        }

        foreach ([
            'secrets.', 'SSH_', 'kubectl', 'docker build',
            'git push', 'pull_request_target:', 'schedule:',
            'MAIL_MAILER: smtp', 'DB_CONNECTION: pgsql',
            'permissions: write', 'curl ', 'wget ',
            'upload-artifact', 'deploy:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $text);
        }
    }

    public function test_runtime_routes_never_include_test_only_http_adapter(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['routes/web.php', 'routes/console.php', 'bootstrap/app.php'] as $path) {
            $source = file_get_contents($root.'/'.$path);
            self::assertIsString($source);
            self::assertStringNotContainsString('SignupTrialHttpFixture', $source);
            self::assertStringNotContainsString('__ci/f3-252', $source);
        }
        $fixture = file_get_contents($root.'/tests/Feature/SignupTrialHttpFixtureFlowTest.php');
        self::assertIsString($fixture);
        self::assertStringContainsString('Route::post', $fixture);
        self::assertStringContainsString("protected function setUp()", $fixture);
    }
}
