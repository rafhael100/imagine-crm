<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class NoDevHttpKernelContractTest extends TestCase
{
    public function test_smoke_probe_is_fail_closed_and_contains_no_external_effects(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/scripts/ci/verify-no-dev-http-kernel.php',
        );
        self::assertIsString($source);

        foreach ([
            "getenv('APP_ENV') !== 'production'",
            "getenv('APP_DEBUG') !== 'false'",
            "getenv('DB_CONNECTION') !== 'sqlite'",
            "getenv('DB_DATABASE') !== ':memory:'",
            "getenv('MAIL_MAILER') !== 'array'",
            "getenv('DATABASE_URL') !== false",
            "getenv('DB_URL') !== false",
            'Illuminate\\Contracts\\Http\\Kernel::class',
            'Illuminate\\Http\\Request::create(',
            "'GET', '/up', 200",
            "'HEAD', '/up', 200",
            "'POST', '/signup', 404",
            "'POST', '/api/trial', 404",
            "'POST', '/api/otp/verify', 404",
            "'POST', '/__ci/f3-258/trial', 404",
            "'POST', '/__ci/f3-259/trial', 404",
            'count($checks) !== 11',
            "headers->has('Set-Cookie')",
            "headers->has('Location')",
            'NO-GO',
        ] as $part) {
            self::assertStringContainsString($part, $source, $part);
        }

        foreach ([
            'file_put_contents(', 'Mail::', 'Http::', 'DB::', 'Redis::',
            'Cache::', 'curl_', 'stream_socket_client', 'fsockopen(',
            'createUser(', 'createTenant(', 'migrate:', 'artisan serve',
            'request()->', 'echo $_', 'var_dump(', 'print_r(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_no_dev_workflow_executes_http_kernel_only_in_simulated_production(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/crm-f3-255-no-dev-isolation.yml',
        );
        self::assertIsString($workflow);
        self::assertStringContainsString(
            "      - 'scripts/ci/verify-no-dev-http-kernel.php'", $workflow,
        );
        self::assertStringContainsString(
            'Exercise PHP HTTP kernel without starting a server (production env simulated)',
            $workflow,
        );
        self::assertStringContainsString('APP_ENV: production', $workflow);
        self::assertStringContainsString("APP_DEBUG: 'false'", $workflow);
        self::assertStringContainsString(
            'php -l scripts/ci/verify-no-dev-http-kernel.php', $workflow,
        );
        self::assertStringContainsString(
            'php scripts/ci/verify-no-dev-http-kernel.php', $workflow,
        );
        self::assertStringContainsString(
            'composer install --no-dev --classmap-authoritative', $workflow,
        );
        self::assertStringContainsString("DB_DATABASE: ':memory:'", $workflow);
        self::assertStringContainsString('MAIL_MAILER: array', $workflow);
        self::assertStringContainsString('contents: read', $workflow);
        foreach (['php artisan serve', 'secrets.', 'pull_request_target:',
            'SSH_', 'DB_CONNECTION: pgsql', 'php artisan migrate'] as $bad) {
            self::assertStringNotContainsString($bad, $workflow);
        }
    }
}
