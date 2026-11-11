<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Pure illustration of a candidate 120-day window AFTER a subscription's
 * supplied cancellation instant. Not a tenant clock, deletion authority,
 * grace period, billing event, reactivation gate or LGPD retention decision.
 */
final readonly class SyntheticCancellationRetentionTimeline
{
    public const REVIEW_WINDOW_DAYS = 120;

    private const DAY_SECONDS = 86400;
    private const REVIEW_WINDOW_SECONDS = self::REVIEW_WINDOW_DAYS * self::DAY_SECONDS;

    public function __construct(
        public int $cancelledAtEpochSeconds,
        public int $observedAtEpochSeconds,
    ) {
        if ($cancelledAtEpochSeconds < 0 || $observedAtEpochSeconds < 0
            || $cancelledAtEpochSeconds > PHP_INT_MAX - self::REVIEW_WINDOW_SECONDS) {
            throw new InvalidArgumentException('Cronologia demonstrativa de cancelamento invalida.');
        }
    }

    public function windowEndsAtEpochSeconds(): int
    {
        return $this->cancelledAtEpochSeconds + self::REVIEW_WINDOW_SECONDS;
    }

    public function phase(): string
    {
        if ($this->observedAtEpochSeconds < $this->cancelledAtEpochSeconds) {
            return 'before_cancellation';
        }

        return $this->observedAtEpochSeconds < $this->windowEndsAtEpochSeconds()
            ? 'within_review_window'
            : 'review_window_elapsed';
    }

    public function remainingSeconds(): int
    {
        if ($this->observedAtEpochSeconds < $this->cancelledAtEpochSeconds) {
            return self::REVIEW_WINDOW_SECONDS;
        }

        return $this->observedAtEpochSeconds < $this->windowEndsAtEpochSeconds()
            ? $this->windowEndsAtEpochSeconds() - $this->observedAtEpochSeconds
            : 0;
    }

    public function remainingDaysRoundedUp(): int
    {
        return intdiv($this->remainingSeconds() + self::DAY_SECONDS - 1, self::DAY_SECONDS);
    }

    /**
     * @return array{
     *   phase:string,cancelled_at_epoch:int,window_ends_at_epoch:int,
     *   remaining_seconds:int,remaining_days_rounded_up:int,informational_only:true,
     *   can_delete_customer_data:false,can_restore_data:false,
     *   can_reactivate_service:false,can_grant_access:false,can_charge:false
     * }
     */
    public function readout(): array
    {
        return [
            'phase' => $this->phase(),
            'cancelled_at_epoch' => $this->cancelledAtEpochSeconds,
            'window_ends_at_epoch' => $this->windowEndsAtEpochSeconds(),
            'remaining_seconds' => $this->remainingSeconds(),
            'remaining_days_rounded_up' => $this->remainingDaysRoundedUp(),
            'informational_only' => true,
            'can_delete_customer_data' => false,
            'can_restore_data' => false,
            'can_reactivate_service' => false,
            'can_grant_access' => false,
            'can_charge' => false,
        ];
    }
}
