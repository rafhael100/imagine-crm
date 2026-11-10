<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Static tripwire, not a complete proof of runtime authorization.
 *
 * Fails if experimental follow-up projections are directly wired into
 * application entry points before the independent security gates close.
 */
final class FollowUpRuntimeSeparationTripwireTest extends TestCase
{
    public function test_synthetic_followup_calculators_are_not_wired_into_live_entrypoints(): void
    {
        $root = dirname(__DIR__, 2);
        $entryPoints = [
            'routes',
            'app/Http',
            'app/Providers',
            'app/Jobs',
            'app/Console',
            'bootstrap',
        ];
        $labClasses = [
            'FollowUpObservationSeries',
            'FollowUpFixtureScopeConsistency',
            'FollowUpFixtureWorklistPage',
            'FollowUpAgingProjection',
            'FollowUpSnapshot',
            'FollowUpTimeline',
            'FollowUpAggregateReport',
            'FollowUpBucketTransitions',
            'FollowUpWorkloadDelta',
            'FollowUpWorkloadSummary',
        ];

        foreach ($entryPoints as $directory) {
            $path = $root.'/'.$directory;
            if (!is_dir($path)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                self::assertIsString($source);
                foreach ($labClasses as $className) {
                    self::assertStringNotContainsString(
                        $className,
                        $source,
                        $file->getPathname().' must not connect laboratory follow-up classes to runtime'
                    );
                }
            }
        }
    }

    public function test_no_api_business_routes_are_enabled_in_bootstrap(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrap = file_get_contents($root.'/bootstrap/app.php');
        self::assertIsString($bootstrap);

        // This is only a fragile source-code guard against accidentally
        // adding an API route in the experimental stage; it is NOT a
        // substitute for Laravel route inspection or an authorization test.
        self::assertStringNotContainsString("api: ", $bootstrap);
        self::assertStringNotContainsString("api:", $bootstrap);
    }
}
