<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductionAuthMisconfigBoundaryTest extends TestCase
{
    public function test_cli_probe_covers_production_flag_override_and_real_container_denials(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/verify-production-auth-misconfig-deny.php',
        );
        self::assertIsString($source);
        foreach ([
            "getenv('APP_ENV') !== 'production'",
            "getenv('APP_DEBUG') !== 'false'",
            "getenv('CRM_AUTH_EXPERIMENTAL') !== 'true'",
            "getenv('DB_CONNECTION') !== 'sqlite'",
            "getenv('DB_DATABASE') !== ':memory:'",
            "getenv('MAIL_MAILER') !== 'array'",
            "getenv('DATABASE_URL') !== false",
            "getenv('DB_URL') !== false",
            "config('identity.experimental') !== true",
            "config('auth.guards.crm_identity') !== null",
            "config('auth.providers.crm_global_identities') !== null",
            'DenyByDefaultAuthorizer::class',
            'GuardPrincipalSource::class',
            'AuthenticationException',
            'ExperimentalAuthenticator::class',
            'LaboratoryProvisioner::class',
            'LogicException',
            'NO-GO',
        ] as $required) {
            self::assertStringContainsString($required, $source, $required);
        }
        foreach ([
            'file_put_contents(', 'Mail::', 'Http::', 'DB::', 'Redis::',
            'Cache::', 'curl_', 'fsockopen(', 'stream_socket_client(',
            'migrate:', 'artisan serve', 'createTenant(', 'createUser(',
            'getenv($_', 'print_r(', 'var_dump(', 'getenv("APP_KEY")',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_no_dev_workflow_executes_production_flag_probe_with_safe_services(): void
    {
        $yaml = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/crm-f3-255-no-dev-isolation.yml',
        );
        self::assertIsString($yaml);

        foreach ([
            'contents: read',
            "DB_DATABASE: ':memory:'",
            'DB_CONNECTION: sqlite',
            'MAIL_MAILER: array',
            'CACHE_STORE: array',
            'SESSION_DRIVER: array',
            'APP_ENV: production',
            "CRM_AUTH_EXPERIMENTAL: 'true'",
            'php -l scripts/ci/verify-production-auth-misconfig-deny.php',
            'php scripts/ci/verify-production-auth-misconfig-deny.php',
            'composer install --no-dev --classmap-authoritative',
            "      - 'scripts/ci/verify-production-auth-misconfig-deny.php'",
            "      - 'tests/Unit/ProductionAuthMisconfigBoundaryTest.php'",
        ] as $required) {
            self::assertStringContainsString($required, $yaml, $required);
        }
        foreach ([
            'secrets.', 'ssh ', 'SSH_', 'git push', 'curl ',
            'DB_CONNECTION: pgsql', 'php artisan migrate',
            'permissions: write', 'deploy:', 'schedule:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $yaml);
        }
    }
}
