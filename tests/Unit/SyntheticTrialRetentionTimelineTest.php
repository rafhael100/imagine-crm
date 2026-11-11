<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\SyntheticTrialRetentionTimeline;
use App\Core\Plans\TrialWindowAdvisory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticTrialRetentionTimelineTest extends TestCase
{
    private const START = 2_000_000;
    private const TRIAL_SECONDS = 14 * 86400;
    private const REVIEW_SECONDS = 60 * 86400;

    public function test_all_trial_and_post_trial_review_boundaries_are_exact_utc_instants(): void
    {
        $end = self::START + self::TRIAL_SECONDS;
        $expires = $end + self::REVIEW_SECONDS;
        $cases = [
            [self::START - 1, 'before_review_window', 'before_start', self::REVIEW_SECONDS, 60],
            [self::START, 'before_review_window', 'within_window', self::REVIEW_SECONDS, 60],
            [$end - 1, 'before_review_window', 'within_window', self::REVIEW_SECONDS, 60],
            [$end, 'within_review_window', 'after_window', self::REVIEW_SECONDS, 60],
            [$end + 1, 'within_review_window', 'after_window', self::REVIEW_SECONDS - 1, 60],
            [$expires - 1, 'within_review_window', 'after_window', 1, 1],
            [$expires, 'review_window_elapsed', 'after_window', 0, 0],
            [$expires + 1, 'review_window_elapsed', 'after_window', 0, 0],
        ];

        foreach ($cases as [$now, $expectedPhase, $expectedTrialPhase, $remaining, $days]) {
            $trial = new TrialWindowAdvisory(self::START, $now);
            $timeline = new SyntheticTrialRetentionTimeline($trial);
            $view = $timeline->readout();

            self::assertSame($expectedPhase, $timeline->phase());
            self::assertSame($expectedPhase, $view['review_phase']);
            self::assertSame($expectedTrialPhase, $view['trial_phase']);
            self::assertSame($end, $view['trial_ends_at_epoch']);
            self::assertSame($expires, $timeline->reviewWindowEndsAtEpochSeconds());
            self::assertSame($expires, $view['review_window_ends_at_epoch']);
            self::assertSame($remaining, $view['remaining_review_seconds']);
            self::assertSame($days, $view['remaining_review_days_rounded_up']);
            self::assertSame($view, $timeline->readout());
        }
    }

    public function test_readout_cannot_grant_entitlement_charge_or_deletion(): void
    {
        $view = (new SyntheticTrialRetentionTimeline(
            new TrialWindowAdvisory(self::START, self::START),
        ))->readout();

        self::assertSame([
            'trial_phase',
            'review_phase',
            'trial_ends_at_epoch',
            'review_window_ends_at_epoch',
            'remaining_review_seconds',
            'remaining_review_days_rounded_up',
            'informational_only',
            'can_delete_data',
            'can_restore_data',
            'can_grant_access',
            'can_charge',
        ], array_keys($view));

        self::assertTrue($view['informational_only']);
        foreach (['can_delete_data', 'can_restore_data', 'can_grant_access', 'can_charge'] as $flag) {
            self::assertFalse($view[$flag]);
        }
        $json = json_encode($view, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'user_id', 'cnpj', 'email', 'phone', 'principal', 'billing'] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
    }

    public function test_near_integer_max_timestamp_is_rejected_without_overflow(): void
    {
        $start = PHP_INT_MAX - self::TRIAL_SECONDS;
        $trial = new TrialWindowAdvisory($start, $start);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Janela demonstrativa de retencao invalida.');
        new SyntheticTrialRetentionTimeline($trial);
    }
}
