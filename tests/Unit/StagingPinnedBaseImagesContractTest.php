<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-272: source tripwire for the three exact OCI image digests recorded
 * in the previously passing, disposable Actions build #43547265544.
 *
 * Registry tags remain for readability but must never select mutable content.
 * SHA pinning is not a signature, vulnerability scan or release approval.
 */
final class StagingPinnedBaseImagesContractTest extends TestCase
{
    private const PHP_BASE = 'php:8.4-fpm-bookworm@sha256:b75642e8c89c321039f351466134df951576fb0f7017117b3931e9a19b908d6c';
    private const COMPOSER_BASE = 'composer:2@sha256:ca11f643d7791ebdd90a34fc20caf11380d434007ae539a9ea75cd9212e13dca';
    private const NGINX_BASE = 'nginx:1.29-alpine@sha256:5616878291a2eed594aee8db4dade5878cf7edcb475e59193904b198d9b830de';

    public function test_all_external_images_are_fixed_to_reviewed_sha256_digests(): void
    {
        $base = dirname(__DIR__, 2).'/infra/staging/';
        $php = file_get_contents($base.'Dockerfile');
        $nginx = file_get_contents($base.'Dockerfile.web');
        self::assertIsString($php);
        self::assertIsString($nginx);

        self::assertSame(1, preg_match_all('/^FROM\s+\S+$/m', $php));
        self::assertSame(1, preg_match_all('/^FROM\s+\S+$/m', $nginx));
        self::assertStringContainsString('FROM '.self::PHP_BASE."\n", $php);
        self::assertStringContainsString('FROM '.self::NGINX_BASE."\n", $nginx);
        self::assertStringContainsString(
            'COPY --from='.self::COMPOSER_BASE.' /usr/bin/composer /usr/local/bin/composer',
            $php,
        );

        foreach ([self::PHP_BASE, self::NGINX_BASE, self::COMPOSER_BASE] as $image) {
            self::assertMatchesRegularExpression(
                '/@sha256:[a-f0-9]{64}$/',
                $image,
            );
        }
        self::assertSame(
            1,
            preg_match_all('/^COPY --from=\S+ /m', $php),
        );

        foreach ([$php, $nginx] as $dockerfile) {
            self::assertStringNotContainsString('FROM php:latest', $dockerfile);
            self::assertStringNotContainsString('FROM nginx:latest', $dockerfile);
            self::assertStringNotContainsString('FROM composer:latest', $dockerfile);
            self::assertStringNotContainsString('COPY --from=composer:2 /', $dockerfile);
        }
    }

    public function test_image_digest_changes_trigger_review_and_runtime_regressions(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            '.github/workflows/ci-crm-private-staging-blueprint.yml',
            '.github/workflows/ci-crm-private-staging-runtime-smoke.yml',
        ] as $file) {
            $workflow = file_get_contents($root.'/'.$file);
            self::assertIsString($workflow);
            self::assertStringContainsString("'infra/staging/**'", $workflow);
            self::assertStringContainsString(
                "'tests/Unit/StagingPinnedBaseImagesContractTest.php'",
                $workflow,
            );
        }
    }
}
