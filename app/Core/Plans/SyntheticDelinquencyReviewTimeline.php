<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Lab-only projection of two candidate 7/30-day billing review markers.
 *
 * Both instants are supplied, not verified: this class does not know whether
 * an invoice exists or is unpaid, does not determine grace periods or legal
 * collection deadlines, and cannot suspend, charge, contact, or alter a client.
 */
final readonly class SyntheticDelinquencyReviewTimeline
{
    public const FIRST_MARKER_DAYS = 7;
    public const SECOND_MARKER_DAYS = 30;

    private const DAY_SECONDS = 86400;
    private const FIRST_SECONDS = self::FIRST_MARKER_DAYS * self::DAY_SECONDS;
    private const SECOND_SECONDS = self::SECOND_MARKER_DAYS * self::DAY_SECONDS;

    public function __construct(
        public int $hypotheticalDueAtEpochSeconds,
        public int $observedAtEpochSeconds,
    ) {
        if ($hypotheticalDueAtEpochSeconds < 0 || $observedAtEpochSeconds < 0
            || $hypotheticalDueAtEpochSeconds > PHP_INT_MAX - self::SECOND_SECONDS) {
            throw new InvalidArgumentException('Marcos demonstrativos de vencimento invalidos.');
        }
    }

    public function firstMarkerAtEpochSeconds(): int
    {
        return $this->hypotheticalDueAtEpochSeconds + self::FIRST_SECONDS;
    }

    public function secondMarkerAtEpochSeconds(): int
    {
        return $this->hypotheticalDueAtEpochSeconds + self::SECOND_SECONDS;
    }

    public function phase(): string
    {
        if ($this->observedAtEpochSeconds < $this->hypotheticalDueAtEpochSeconds) {
            return 'before_due';
        }
        if ($this->observedAtEpochSeconds < $this->firstMarkerAtEpochSeconds()) {
            return 'before_first_marker';
        }

        return $this->observedAtEpochSeconds < $this->secondMarkerAtEpochSeconds()
            ? 'between_markers' : 'second_marker_elapsed';
    }

    public function nextMarkerAtEpochSeconds(): ?int
    {
        if ($this->observedAtEpochSeconds < $this->firstMarkerAtEpochSeconds()) {
            return $this->firstMarkerAtEpochSeconds();
        }

        return $this->observedAtEpochSeconds < $this->secondMarkerAtEpochSeconds()
            ? $this->secondMarkerAtEpochSeconds() : null;
    }

    public function remainingSecondsToNextMarker(): int
    {
        $next = $this->nextMarkerAtEpochSeconds();

        return $next === null ? 0 : $next - $this->observedAtEpochSeconds;
    }

    public function remainingDaysRoundedUp(): int
    {
        $seconds = $this->remainingSecondsToNextMarker();

        // Quotient and remainder avoid an intermediate PHP_INT_MAX overflow.
        return intdiv($seconds, self::DAY_SECONDS)
            + ($seconds % self::DAY_SECONDS === 0 ? 0 : 1);
    }

    /**
     * @return array{
     *   phase:string,illustrative_due_at_epoch:int,first_marker_at_epoch:int,
     *   second_marker_at_epoch:int,next_marker_at_epoch:?int,
     *   remaining_seconds_to_next_marker:int,remaining_days_rounded_up:int,
     *   informational_only:true,unpaid_status_verified:false,
     *   can_send_collection:false,can_suspend_service:false,
     *   can_charge:false,can_grant_access:false,can_delete_customer_data:false
     * }
     */
    public function readout(): array
    {
        return [
            'phase' => $this->phase(),
            'illustrative_due_at_epoch' => $this->hypotheticalDueAtEpochSeconds,
            'first_marker_at_epoch' => $this->firstMarkerAtEpochSeconds(),
            'second_marker_at_epoch' => $this->secondMarkerAtEpochSeconds(),
            'next_marker_at_epoch' => $this->nextMarkerAtEpochSeconds(),
            'remaining_seconds_to_next_marker' => $this->remainingSecondsToNextMarker(),
            'remaining_days_rounded_up' => $this->remainingDaysRoundedUp(),
            'informational_only' => true,
            'unpaid_status_verified' => false,
            'can_send_collection' => false,
            'can_suspend_service' => false,
            'can_charge' => false,
            'can_grant_access' => false,
            'can_delete_customer_data' => false,
        ];
    }
}
