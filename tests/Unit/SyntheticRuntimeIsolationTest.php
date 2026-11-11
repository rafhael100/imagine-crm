<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Fail-closed source-code tripwire for the entire App\Support\Synthetic lab.
 *
 * This is not authorization, taint analysis or runtime isolation proof.
 * It prevents accidental direct dependencies from application code/entrypoints,
 * including classes added after this test was written.
 */
final class SyntheticRuntimeIsolationTest extends TestCase
{
    /**
     * Discover filenames instead of manually listing laboratory classes.
     *
     * @return list<string>
     */
    private static function labClasses(): array
    {
        $root = dirname(__DIR__, 2);
        $files = glob($root.'/app/Support/Synthetic/*.php');
        self::assertIsArray($files);
        self::assertNotEmpty($files, 'The synthetic lab must exist and be monitored.');

        $classes = [];
        foreach ($files as $file) {
            $class = pathinfo($file, PATHINFO_FILENAME);
            self::assertMatchesRegularExpression(
                '/\A[A-Z][A-Za-z0-9_]*\z/D',
                $class,
                'Unexpected synthetic PHP filename: '.$file,
            );
            $classes[] = $class;
        }

        self::assertSame(count($classes), count(array_unique($classes)));

        return $classes;
    }

    /**
     * @param list<string> $classes
     */
    private static function forbiddenReference(string $source, array $classes): ?string
    {
        // Detect direct/aliased namespace access and both literal and
        // PHP-escaped string paths (class_alias, require, etc.).
        foreach ([
            'Support\\Synthetic',
            'Support\\\\Synthetic',
            'Support/Synthetic',
        ] as $namespace) {
            if (str_contains($source, $namespace)) {
                return 'synthetic namespace/path';
            }
        }

        // Even a bare class reference or an indirect string-reflection
        // reference should not occur in application runtime code.
        foreach ($classes as $class) {
            if (preg_match(
                '/(?<![A-Za-z0-9_])'.preg_quote($class, '/').'(?![A-Za-z0-9_])/',
                $source,
            ) === 1) {
                return 'synthetic class '.$class;
            }
        }

        return null;
    }

    public function test_discovers_future_laboratory_classes_automatically(): void
    {
        $classes = self::labClasses();

        self::assertContains('CsvReviewRaceFixture', $classes);
        self::assertContains('CsvReviewSnapshotPreview', $classes);
        self::assertContains('FollowUpObservationSeries', $classes);
        self::assertGreaterThanOrEqual(15, count($classes));
    }

    public function test_catches_direct_imports_aliases_file_includes_and_new_future_classes(): void
    {
        $classes = self::labClasses();

        foreach ([
            'use App\\Support\\Synthetic\\CsvReviewRaceFixture;',
            "new \\App\\Support\\Synthetic\\CsvReviewRaceFixture();",
            "class_alias('App\\\\Support\\\\Synthetic\\\\CsvReviewRaceFixture', 'Alias');",
            "require 'app/Support/Synthetic/CsvReviewRaceFixture.php';",
            'new CsvReviewRaceFixture();',
            // This class does not exist yet. The detector is generic.
            'new FutureCsvLabClass();',
        ] as $source) {
            $discoveredOrFuture = [...$classes, 'FutureCsvLabClass'];
            self::assertNotNull(
                self::forbiddenReference($source, $discoveredOrFuture),
                'An indirect synthetic runtime reference was missed: '.$source,
            );
        }
    }

    public function test_does_not_flag_unrelated_normal_application_code(): void
    {
        $normalCode = <<<'PHP'
<?php
namespace App\Services;
final class CustomerFormatter {
    public function format(string $value): string { return trim($value); }
}
PHP;

        self::assertNull(self::forbiddenReference($normalCode, self::labClasses()));
    }

    public function test_php_runtime_source_is_not_coupled_to_any_synthetic_laboratory_class(): void
    {
        $root = dirname(__DIR__, 2);
        $classes = self::labClasses();
        $checked = 0;

        // Monitor all application PHP, not only routes or HTTP, but exclude
        // the synthetic classes' own folder (where they legitimately refer
        // to each other). PHPUnit/CI experiments are outside these paths.
        foreach (['app', 'routes', 'bootstrap'] as $directory) {
            $path = $root.'/'.$directory;
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $relativePath = str_replace(
                    '\\', '/', substr($file->getPathname(), strlen($root) + 1),
                );
                if (str_starts_with($relativePath, 'app/Support/Synthetic/')) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                self::assertIsString($source);
                self::assertNull(
                    self::forbiddenReference($source, $classes),
                    $relativePath.' must not wire experimental fixture code to runtime.',
                );
                ++$checked;
            }
        }

        self::assertGreaterThan(0, $checked, 'Application source must be scanned.');
    }
}
