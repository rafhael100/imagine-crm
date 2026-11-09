<?php

declare(strict_types=1);

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * CLI-only runner for GitHub-hosted Chrome smoke tests of offline fixtures.
 *
 * An actual executed <output> PASS is mandatory. A browser timeout/crash or
 * missing marker may be retried ONCE in a fresh profile; a functional FAIL
 * marker is returned immediately and is NEVER retried into a false green.
 *
 * No network, web server, user data, cookies or persistent browser profiles.
 */
final class BrowserSmokeRunner
{
    /**
     * @param list<string> $args Chrome flags other than profile/dump-dom
     * @return array{passed: bool, reason: string, attempts: int}
     */
    public static function run(string $binary, array $args, string $url, string $markerAttribute): array
    {
        if (!str_starts_with($url, 'file://')
            || !is_file(substr($url, 7))
            || !in_array($markerAttribute, ['data-imagine-browser-smoke', 'data-imagine-320'], true)) {
            return ['passed' => false, 'reason' => 'invalid offline browser fixture', 'attempts' => 0];
        }

        $lastReason = 'browser did not produce an executed PASS marker';

        for ($attempt = 1; $attempt <= 2; ++$attempt) {
            $profile = sys_get_temp_dir().'/imagine-smoke-chrome-'.bin2hex(random_bytes(10));
            if (!mkdir($profile, 0700)) {
                return ['passed' => false, 'reason' => 'could not create isolated browser profile', 'attempts' => $attempt];
            }

            try {
                $command = array_merge(
                    [$binary, '--headless', '--disable-gpu', '--disable-extensions',
                        '--disable-background-networking', '--disable-dev-shm-usage',
                        '--no-first-run', '--no-default-browser-check',
                        '--user-data-dir='.$profile],
                    $args,
                    ['--dump-dom', $url],
                );
                $process = new Process($command);
                $process->setTimeout(45);
                $timedOut = false;

                try {
                    $process->run();
                } catch (ProcessTimedOutException) {
                    $timedOut = true;
                    if ($process->isRunning()) {
                        $process->stop(0.25);
                    }
                }

                $html = $process->getOutput();
                $match = [];
                $found = preg_match(
                    '/<output\b[^>]*'.preg_quote($markerAttribute, '/').'="(PASS|FAIL)"[^>]*>(.*?)<\/output>/s',
                    $html,
                    $match,
                ) === 1;

                if ($found && $match[1] === 'FAIL') {
                    // Real browser assertions failed. Never hide this by retry.
                    $message = html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    return ['passed' => false, 'reason' => mb_substr($message, 0, 300), 'attempts' => $attempt];
                }

                if (!$timedOut && $process->isSuccessful() && $found && $match[1] === 'PASS') {
                    return ['passed' => true, 'reason' => 'actual Chromium DOM assertions passed', 'attempts' => $attempt];
                }

                $lastReason = $timedOut
                    ? 'headless Chromium timed out before completing the assertions'
                    : 'headless Chromium exited or did not produce an executed PASS marker';
            } finally {
                self::removeTemporaryProfile($profile);
            }

            if ($attempt === 1) {
                fwrite(STDERR, "Browser smoke: no executed PASS on first attempt; retrying once with a new temporary profile.\n");
            }
        }

        return ['passed' => false, 'reason' => $lastReason, 'attempts' => 2];
    }

    private static function removeTemporaryProfile(string $profile): void
    {
        if (!is_dir($profile)) {
            return;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($profile, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $item) {
                if ($item->isDir() && !$item->isLink()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
        } catch (Throwable) {
            // Temporary runner storage is ephemeral. Cleanup errors must NOT
            // change whether a real browser assertion passed or failed.
        }

        @rmdir($profile);
    }
}
