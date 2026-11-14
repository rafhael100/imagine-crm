<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-266 source check for Dockerfile-specific context exclusion.
 * Validating text is not the same as exercising a Docker builder.
 */
final class StagingBuildContextIsolationTest extends TestCase
{
    private const GUARDED_EXCLUSIONS = [
        '**/.env', '**/.env.*', '**/*.pem', '**/*.key',
        '**/*.p12', '**/*.pfx', '**/*.sqlite', '**/*.sqlite3',
        '**/*.db', '**/*.log', '**/.git/**', '**/vendor/**',
        '**/node_modules/**', '**/storage/**',
        'bootstrap/cache/**', 'public/storage/**',
    ];

    private const APP_ALLOW = [
        '!composer.json', '!composer.lock', '!artisan',
        '!app/', '!app/**',
        '!bootstrap/', '!bootstrap/**',
        '!config/', '!config/**',
        '!database/', '!database/migrations/', '!database/migrations/**',
        '!public/', '!public/**',
        '!resources/', '!resources/**',
        '!routes/', '!routes/**',
    ];

    private const WEB_ALLOW = [
        '!infra/', '!infra/staging/', '!infra/staging/nginx.conf',
        '!public/', '!public/**',
    ];

    /** @return list<string> */
    private static function policyLines(string $file): array
    {
        $lines = preg_split('/\R/', $file) ?: [];
        return array_values(array_filter(array_map('trim', $lines),
            static fn (string $line): bool => $line !== '' && !str_starts_with($line, '#')));
    }

    /** @return list<string> */
    private static function deviations(string $contents, array $allow): array
    {
        $actual = self::policyLines($contents);
        $expected = ['**', ...$allow, ...self::GUARDED_EXCLUSIONS];
        if ($actual === $expected) {
            return [];
        }
        return ['unsafe_or_missing_build_context_policy'];
    }

    public function test_app_dockerfile_context_is_explicit_allowlist_and_sensitive_denies(): void
    {
        $file = file_get_contents(
            dirname(__DIR__, 2).'/infra/staging/Dockerfile.dockerignore',
        );
        self::assertIsString($file);
        self::assertSame([], self::deviations($file, self::APP_ALLOW));
        self::assertNotContains('!database/experimental-migrations/**', self::policyLines($file));
        self::assertNotContains('!tests/**', self::policyLines($file));
        self::assertNotContains('!vendor/**', self::policyLines($file));
    }

    public function test_nginx_dockerfile_context_never_includes_backend_or_secrets(): void
    {
        $file = file_get_contents(
            dirname(__DIR__, 2).'/infra/staging/Dockerfile.web.dockerignore',
        );
        self::assertIsString($file);
        self::assertSame([], self::deviations($file, self::WEB_ALLOW));
        foreach ([
            '!composer.json', '!composer.lock', '!artisan',
            '!app/**', '!config/**', '!database/**', '!routes/**',
            '!tests/**', '!infra/staging/Dockerfile',
        ] as $forbidden) {
            self::assertNotContains($forbidden, self::policyLines($file));
        }
    }

    public function test_security_context_policy_rejects_unsafe_allow_or_missing_deny(): void
    {
        $clean = implode("\n", ['**', ...self::APP_ALLOW, ...self::GUARDED_EXCLUSIONS])."\n";
        self::assertSame([], self::deviations($clean, self::APP_ALLOW));
        self::assertNotSame([], self::deviations(
            str_replace('**/.env.*', '', $clean), self::APP_ALLOW,
        ));
        self::assertNotSame([], self::deviations(
            $clean."!tests/**\n", self::APP_ALLOW,
        ));
        self::assertNotSame([], self::deviations(
            str_replace('**'."\n", '', $clean), self::APP_ALLOW,
        ));
        self::assertNotSame([], self::deviations(
            str_replace('!database/migrations/**', '!database/**', $clean),
            self::APP_ALLOW,
        ));
    }

    public function test_source_dockerfiles_only_copy_approved_context_paths(): void
    {
        $root = dirname(__DIR__, 2).'/infra/staging/';
        $php = file_get_contents($root.'Dockerfile');
        $web = file_get_contents($root.'Dockerfile.web');
        self::assertIsString($php);
        self::assertIsString($web);

        foreach ([
            'COPY composer.json composer.lock ./',
            'COPY app/ app/',
            'COPY bootstrap/ bootstrap/',
            'COPY config/ config/',
            'COPY database/migrations/ database/migrations/',
            'COPY public/ public/',
            'COPY resources/ resources/',
            'COPY routes/ routes/',
            'COPY artisan artisan',
        ] as $required) {
            self::assertStringContainsString($required, $php, $required);
        }

        foreach ([
            'COPY infra/staging/nginx.conf /etc/nginx/nginx.conf',
            'COPY public/ /var/www/html/public/',
        ] as $required) {
            self::assertStringContainsString($required, $web, $required);
        }
        foreach ([$php, $web] as $dockerfile) {
            foreach ([
                'COPY . .', 'COPY .env', 'COPY tests/',
                'COPY storage/', 'COPY database/experimental-migrations',
                'ADD https://', 'ADD git@',
            ] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $dockerfile);
            }
        }
    }
}
