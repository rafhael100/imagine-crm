<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\SyntheticCancellationRetentionTimeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCancellationRetentionTimelineTest extends TestCase
{
    private const CANCELLATION = 2_000_000;
    private const REVIEW_SECONDS = 120 * 86400;

    public function test_before_during_and_after_illustrative_window_are_exact(): void
    {
        $end = self::CANCELLATION + self::REVIEW_SECONDS;
        $cases = [
            [self::CANCELLATION - 1, 'before_cancellation', self::REVIEW_SECONDS, 120],
            [self::CANCELLATION, 'within_review_window', self::REVIEW_SECONDS, 120],
            [self::CANCELLATION + 1, 'within_review_window', self::REVIEW_SECONDS - 1, 120],
            [$end - 86400, 'within_review_window', 86400, 1],
            [$end - 1, 'within_review_window', 1, 1],
            [$end, 'review_window_elapsed', 0, 0],
            [$end + 1, 'review_window_elapsed', 0, 0],
        ];

        foreach ($cases as [$now, $phase, $seconds, $days]) {
            $view = new SyntheticCancellationRetentionTimeline(self::CANCELLATION, $now);
            self::assertSame($phase, $view->phase());
            self::assertSame($end, $view->windowEndsAtEpochSeconds());
            self::assertSame($seconds, $view->remainingSeconds());
            self::assertSame($days, $view->remainingDaysRoundedUp());

            $report = $view->readout();
            self::assertSame($phase, $report['phase']);
            self::assertSame(self::CANCELLATION, $report['cancelled_at_epoch']);
            self::assertSame($end, $report['window_ends_at_epoch']);
            self::assertSame($seconds, $report['remaining_seconds']);
            self::assertSame($days, $report['remaining_days_rounded_up']);
            self::assertSame($report, $view->readout());
        }
    }

    public function test_readout_never_grants_deletion_access_restoration_or_billing(): void
    {
        $report = (new SyntheticCancellationRetentionTimeline(
            self::CANCELLATION, self::CANCELLATION,
        ))->readout();

        self::assertSame([
            'phase',
            'cancelled_at_epoch',
            'window_ends_at_epoch',
            'remaining_seconds',
            'remaining_days_rounded_up',
            'informational_only',
            'can_delete_customer_data',
            'can_restore_data',
            'can_reactivate_service',
            'can_grant_access',
            'can_charge',
        ], array_keys($report));

        self::assertTrue($report['informational_only']);
        foreach ([
            'can_delete_customer_data', 'can_restore_data',
            'can_reactivate_service', 'can_grant_access', 'can_charge',
        ] as $capability) {
            self::assertFalse($report[$capability]);
        }

        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'cnpj', 'principal', 'email', 'phone', 'payment'] as $untrusted) {
            self::assertStringNotContainsString('"'.$untrusted.'"', $json);
        }
    }

    public function test_invalid_and_overflowing_times_are_denied(): void
    {
        foreach ([
            [-1, 0],
            [0, -1],
            [PHP_INT_MAX, PHP_INT_MAX],
            [PHP_INT_MAX - self::REVIEW_SECONDS + 1, 0],
        ] as [$cancelled, $now]) {
            try {
                new SyntheticCancellationRetentionTimeline($cancelled, $now);
                self::fail('Invalid cancellation timeline accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame(
                    'Cronologia demonstrativa de cancelamento invalida.',
                    $exception->getMessage(),
                );
            }
        }
    }
}
