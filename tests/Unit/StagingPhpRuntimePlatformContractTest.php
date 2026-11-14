<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-266: static source gate against a broken PHP runtime image.
 *
 * This checks the production dependency manifest against explicit extension
 * compilation and a deliberately reviewed PHP official-image baseline.
 * It DOES NOT build any image, pull any tag or claim runtime readiness.
 */
final class StagingPhpRuntimePlatformContractTest extends TestCase
{
    private const BUILTIN_EXTENSIONS = [
        // Expected extensions from the official PHP 8.4 FPM base image.
        // Reassess against actual php -m during the future image-build gate.
        'ctype', 'dom', 'fileinfo', 'filter', 'hash', 'iconv',
        'json', 'libxml', 'openssl', 'pcre', 'session', 'tokenizer',
    ];

    public function test_production_composer_extensions_are_provided_or_explicitly_reviewed(): void
    {
        $root = dirname(__DIR__, 2);
        $raw = file_get_contents($root.'/composer.lock');
        self::assertIsString($raw);
        $lock = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        self::assertIsArray($lock);
        self::assertIsArray($lock['packages'] ?? null);

        $needed = [];
        $frameworkHasMbstring = false;
        foreach ($lock['packages'] as $package) {
            foreach (($package['require'] ?? []) as $key => $_constraint) {
                if (!is_string($key) || !str_starts_with($key, 'ext-')) {
                    continue;
                }
                $extension = substr($key, 4);
                $needed[$extension] = true;
                if (($package['name'] ?? null) === 'laravel/framework'
                    && $extension === 'mbstring') {
                    $frameworkHasMbstring = true;
                }
            }
        }
        self::assertTrue($frameworkHasMbstring, 'Laravel in composer.lock requires ext-mbstring.');
        self::assertArrayHasKey('mbstring', $needed);

        $dockerfile = file_get_contents($root.'/infra/staging/Dockerfile');
        self::assertIsString($dockerfile);
        self::assertStringContainsString('FROM php:8.4-fpm-bookworm', $dockerfile);
        self::assertStringContainsString('libonig-dev', $dockerfile);
        self::assertStringContainsString('libsqlite3-dev', $dockerfile);
        self::assertStringContainsString('pdo_sqlite', $dockerfile);

        $explicit = [];
        $commandPrefix = 'docker-php-ext-install -j"$(nproc)" ';
        foreach (explode("\n", $dockerfile) as $line) {
            $at = strpos($line, $commandPrefix);
            if ($at === false) {
                continue;
            }
            $arguments = trim(rtrim(substr($line, $at + strlen($commandPrefix)), " \\"));
            foreach (preg_split('/\\s+/', $arguments) ?: [] as $extension) {
                if ($extension !== '') {
                    $explicit[] = $extension;
                }
            }
        }

        self::assertNotEmpty($explicit, 'No explicit docker-php-ext-install found.');
        self::assertContains('mbstring', $explicit, 'Composer requires mbstring in image, not only on CI host.');

        foreach (array_keys($needed) as $requiredExtension) {
            self::assertTrue(
                in_array($requiredExtension, self::BUILTIN_EXTENSIONS, true)
                || in_array($requiredExtension, $explicit, true),
                'Unreviewed production PHP platform requirement: ext-'.$requiredExtension,
            );
        }
    }

    public function test_dependency_or_dockerfile_changes_are_covered_by_static_workflow(): void
    {
        $workflow = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/ci-crm-private-staging-blueprint.yml',
        );
        self::assertIsString($workflow);
        foreach ([
            "'composer.lock'",
            "'composer.json'",
            "'infra/staging/**'",
            "'tests/Unit/StagingPhpRuntimePlatformContractTest.php'",
            'docker compose -f infra/staging/compose.yaml config --format json',
        ] as $needle) {
            self::assertStringContainsString($needle, $workflow);
        }
        foreach (['docker compose build', 'docker compose up ', 'docker compose pull',
            'docker run ', 'docker push ', 'ssh ', 'secrets.'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $workflow);
        }
    }
}
