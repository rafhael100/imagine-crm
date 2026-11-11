<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Synthetic, DISPLAY-ONLY timeline for the candidate post-trial review window.
 *
 * The illustrative retention starts at the end of a 14-day trial and lasts
 * 60 x 24h. This DOES NOT choose or verify a real tenant's trial clock, legal
 * deletion date, LGPD lawful basis, backup retention, entitlement or billing.
 * No data, documents or accounts can be read or changed through this class.
 */
final readonly class SyntheticTrialRetentionTimeline
{
    public const REVIEW_WINDOW_DAYS = 60;

    private const DAY_SECONDS = 86400;
    private const REVIEW_WINDOW_SECONDS = self::REVIEW_WINDOW_DAYS * self::DAY_SECONDS;

    public function __construct(private TrialWindowAdvisory $trial)
    {
        if ($this->trial->endsAtEpochSeconds() > PHP_INT_MAX - self::REVIEW_WINDOW_SECONDS) {
            throw new InvalidArgumentException('Janela demonstrativa de retencao invalida.');
        }
    }

    public function reviewWindowEndsAtEpochSeconds(): int
    {
        return $this->trial->endsAtEpochSeconds() + self::REVIEW_WINDOW_SECONDS;
    }

    public function phase(): string
    {
        if ($this->trial->observedAtEpochSeconds < $this->trial->endsAtEpochSeconds()) {
            return 'before_review_window';
        }

        return $this->trial->observedAtEpochSeconds < $this->reviewWindowEndsAtEpochSeconds()
            ? 'within_review_window' : 'review_window_elapsed';
    }

    public function remainingReviewSeconds(): int
    {
        if ($this->trial->observedAtEpochSeconds < $this->trial->endsAtEpochSeconds()) {
            return self::REVIEW_WINDOW_SECONDS;
        }

        return $this->trial->observedAtEpochSeconds < $this->reviewWindowEndsAtEpochSeconds()
            ? $this->reviewWindowEndsAtEpochSeconds() - $this->trial->observedAtEpochSeconds : 0;
    }

    public function remainingReviewDaysRoundedUp(): int
    {
        return intdiv($this->remainingReviewSeconds() + self::DAY_SECONDS - 1, self::DAY_SECONDS);
    }

    /**
     * @return array{
     *   trial_phase:string,review_phase:string,trial_ends_at_epoch:int,
     *   review_window_ends_at_epoch:int,remaining_review_seconds:int,
     *   remaining_review_days_rounded_up:int,informational_only:true,
     *   can_delete_data:false,can_restore_data:false,can_grant_access:false,
     *   can_charge:false
     * }
     */
    public function readout(): array
    {
        return [
            'trial_phase' => $this->trial->phase()->value,
            'review_phase' => $this->phase(),
            'trial_ends_at_epoch' => $this->trial->endsAtEpochSeconds(),
            'review_window_ends_at_epoch' => $this->reviewWindowEndsAtEpochSeconds(),
            'remaining_review_seconds' => $this->remainingReviewSeconds(),
            'remaining_review_days_rounded_up' => $this->remainingReviewDaysRoundedUp(),
            'informational_only' => true,
            'can_delete_data' => false,
            'can_restore_data' => false,
            'can_grant_access' => false,
            'can_charge' => false,
        ];
    }
}
