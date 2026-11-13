<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductionAutoloadBoundaryContractTest extends TestCase
{
    public function test_composer_psr4_test_namespace_exists_only_under_autoload_dev(): void
    {
        $json = file_get_contents(dirname(__DIR__, 2).'/composer.json');
        self::assertIsString($json);
        $composer = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('tests/', $composer['autoload-dev']['psr-4']['Tests\\']);
        self::assertArrayNotHasKey('Tests\\', $composer['autoload']['psr-4']);
        self::assertSame('app/', $composer['autoload']['psr-4']['App\\']);
    }

    public function test_workflow_enforces_no_dev_and_up_only_without_write_privileges(): void
    {
        $yaml = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/crm-f3-255-no-dev-isolation.yml',
        );
        self::assertIsString($yaml);
        foreach ([
            'on:', 'pull_request:', 'workflow_dispatch:',
            'permissions:', 'contents: read',
            'APP_ENV: testing', "DB_DATABASE: ':memory:'",
            'MAIL_MAILER: array', "php-version: '8.4'",
            'composer install --no-dev --classmap-authoritative',
            'verify-no-dev-http-fixtures.php',
            'php artisan route:list --json', '.uri == "up"',
            "'__ci/f3-252'", "'__ci/f3-254'", "'__ci/f3-258'", "'__ci/f3-259'", 'SignupTrialSequencedHttpLab',
            'routes app bootstrap', 'NO-GO',
            '$GITHUB_STEP_SUMMARY',
        ] as $part) {
            self::assertStringContainsString($part, $yaml);
        }
        foreach ([
            'secrets.', 'SSH_', 'kubectl', 'docker build',
            'git push', 'pull_request_target:', 'schedule:',
            'MAIL_MAILER: smtp', 'DB_CONNECTION: pgsql',
            'permissions: write', 'curl ', 'wget ',
            'upload-artifact', 'deploy:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $yaml);
        }
    }

    public function test_no_dev_probe_is_read_only_and_uses_only_fixture_class_names(): void
    {
        $src = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/verify-no-dev-http-fixtures.php',
        );
        self::assertIsString($src);
        foreach ([
            'getPrefixesPsr4()', 'class_exists(',
            'SignupTrialHttpFixture', 'SignupOtpLifecycleSandbox',
            'TrialEnrollmentLifecycleSandbox',
            'SignupTrialHttpFixtureResendTest',
            'SignupTrialSequencedHttpLab',
            'SignupTrialSequencedHttpLabTest',
            'SignupTrialWindowQuotaHttpLabTest',
        ] as $required) {
            self::assertStringContainsString($required, $src);
        }
        foreach ([
            'Mail::', 'Http::', 'DB::', 'Redis::',
            'file_put_contents(', 'curl_', 'getenv(', 'env(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $src);
        }
    }
}
