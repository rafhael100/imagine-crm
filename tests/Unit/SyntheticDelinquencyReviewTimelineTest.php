<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\SyntheticDelinquencyReviewTimeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticDelinquencyReviewTimelineTest extends TestCase
{
    private const DUE = 1_794_052_800;
    private const FIRST = self::DUE + 7 * 86400;
    private const SECOND = self::DUE + 30 * 86400;

    public function test_exact_seven_and_thirty_day_marker_boundaries_and_intermediate_phase(): void
    {
        $cases = [
            [self::DUE - 1, 'before_due', self::FIRST, 604801, 8],
            [self::DUE, 'before_first_marker', self::FIRST, 604800, 7],
            [self::FIRST - 1, 'before_first_marker', self::FIRST, 1, 1],
            [self::FIRST, 'between_markers', self::SECOND, 1987200, 23],
            [self::SECOND - 1, 'between_markers', self::SECOND, 1, 1],
            [self::SECOND, 'second_marker_elapsed', null, 0, 0],
            [self::SECOND + 1, 'second_marker_elapsed', null, 0, 0],
        ];

        foreach ($cases as [$now, $phase, $next, $remaining, $days]) {
            $timeline = new SyntheticDelinquencyReviewTimeline(self::DUE, $now);
            $view = $timeline->readout();

            self::assertSame($phase, $timeline->phase());
            self::assertSame($phase, $view['phase']);
            self::assertSame(self::FIRST, $view['first_marker_at_epoch']);
            self::assertSame(self::SECOND, $view['second_marker_at_epoch']);
            self::assertSame($next, $view['next_marker_at_epoch']);
            self::assertSame($remaining, $view['remaining_seconds_to_next_marker']);
            self::assertSame($days, $view['remaining_days_rounded_up']);
            self::assertSame($view, $timeline->readout());
        }
    }

    public function test_all_operational_capabilities_and_unpaid_claim_are_denied(): void
    {
        $view = (new SyntheticDelinquencyReviewTimeline(self::DUE, self::DUE))->readout();
        self::assertSame([
            'phase', 'illustrative_due_at_epoch', 'first_marker_at_epoch',
            'second_marker_at_epoch', 'next_marker_at_epoch',
            'remaining_seconds_to_next_marker', 'remaining_days_rounded_up',
            'informational_only', 'unpaid_status_verified',
            'can_send_collection', 'can_suspend_service', 'can_charge',
            'can_grant_access', 'can_delete_customer_data',
        ], array_keys($view));
        self::assertTrue($view['informational_only']);
        foreach ([
            'unpaid_status_verified', 'can_send_collection',
            'can_suspend_service', 'can_charge',
            'can_grant_access', 'can_delete_customer_data',
        ] as $flag) {
            self::assertFalse($view[$flag]);
        }
        foreach (['tenant', 'cnpj', 'phone', 'email', 'card', 'principal', 'invoice_id'] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', json_encode($view, JSON_THROW_ON_ERROR));
        }
    }

    public function test_invalid_or_overflowing_instants_fail_closed(): void
    {
        foreach ([
            [-1, 0], [0, -1],
            [PHP_INT_MAX, 0],
            [PHP_INT_MAX - 30 * 86400 + 1, 0],
        ] as [$due, $now]) {
            try {
                new SyntheticDelinquencyReviewTimeline($due, $now);
                self::fail('Unsafe illustrative billing clock was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Marcos demonstrativos de vencimento invalidos.', $error->getMessage());
            }
        }
    }

    public function test_large_but_valid_epochs_do_not_overflow_rounded_days(): void
    {
        $due = PHP_INT_MAX - 30 * 86400;
        $timeline = new SyntheticDelinquencyReviewTimeline($due, 0);
        self::assertSame(PHP_INT_MAX, $timeline->secondMarkerAtEpochSeconds());
        self::assertSame($timeline->firstMarkerAtEpochSeconds(), $timeline->remainingSecondsToNextMarker());
        self::assertGreaterThan(0, $timeline->remainingDaysRoundedUp());
        self::assertFalse($timeline->readout()['can_charge']);
    }
}
