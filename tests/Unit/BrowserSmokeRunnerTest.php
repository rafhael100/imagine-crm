<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/scripts/ci/BrowserSmokeRunner.php';

final class BrowserSmokeRunnerTest extends TestCase
{
    public function test_network_url_is_rejected_without_running_any_browser(): void
    {
        $result = \BrowserSmokeRunner::run(
            'nonexistent-browser',
            [],
            'https://example.invalid/offline-demo',
            'data-imagine-browser-smoke',
        );

        self::assertFalse($result['passed']);
        self::assertSame(0, $result['attempts']);
        self::assertSame('invalid offline browser fixture', $result['reason']);
    }

    public function test_unknown_result_marker_is_rejected_before_launch(): void
    {
        $fixture = tempnam(sys_get_temp_dir(), 'imagine-smoke-test-');
        self::assertIsString($fixture);
        try {
            file_put_contents($fixture, '<html><body>fake fixture</body></html>');
            $result = \BrowserSmokeRunner::run(
                'nonexistent-browser',
                [],
                'file://'.$fixture,
                'data-untrusted-result',
            );
            self::assertFalse($result['passed']);
            self::assertSame(0, $result['attempts']);
        } finally {
            @unlink($fixture);
        }
    }

    public function test_nonbrowser_process_never_gets_a_synthetic_pass(): void
    {
        $fixture = tempnam(sys_get_temp_dir(), 'imagine-smoke-test-');
        self::assertIsString($fixture);
        try {
            file_put_contents($fixture, <<<'HTML'
<html><body>
<script>const fake = '<output data-imagine-browser-smoke="PASS">fake source</output>';</script>
</body></html>
HTML);
            // PHP CLI cannot execute Chrome flags; the runner must not treat
            // PASS text in a source file as a successful Chromium DOM test.
            $result = \BrowserSmokeRunner::run(
                PHP_BINARY,
                ['--window-size=520,780'],
                'file://'.$fixture,
                'data-imagine-browser-smoke',
            );
            self::assertFalse($result['passed']);
            self::assertSame(2, $result['attempts']);
            self::assertStringContainsString('did not produce', $result['reason']);
        } finally {
            @unlink($fixture);
        }
    }

    public function test_failure_and_retry_are_fail_closed_not_fake_browser_validation(): void
    {
        $helper = file_get_contents(dirname(__DIR__, 2).'/scripts/ci/BrowserSmokeRunner.php');
        self::assertIsString($helper);
        self::assertStringContainsString('ProcessTimedOutException', $helper);
        self::assertStringContainsString("if ($" . "found && $" . "match[1] === 'FAIL')", $helper);
        self::assertStringContainsString("return ['passed' => false", $helper);
        self::assertStringContainsString("if (!$" . "timedOut && $" . "process->isSuccessful()", $helper);
        self::assertStringContainsString('for ($attempt = 1; $attempt <= 2; ++$attempt)', $helper);
        self::assertStringContainsString("'--user-data-dir='.$profile", $helper);
        self::assertStringContainsString("'--disable-dev-shm-usage'", $helper);
        self::assertStringContainsString('self::removeTemporaryProfile($profile)', $helper);
    }
}
