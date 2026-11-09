<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Deterministic, display-only timeline for an ALREADY SUPPLIED trial start.
 *
 * The 14-day illustrative window comes from CRM-REV-02. Inputs are synthetic
 * Unix epoch seconds (UTC instants), not user-local calendar dates.
 *
 * This class never determines trial eligibility (including CNPJ reuse),
 * selects the authoritative activation instant, authorizes an API/session,
 * suspends an account, schedules retention, bills or modifies storage.
 * The 60-day trial retention period is a separate unimplemented policy.
 */
final readonly class TrialWindowAdvisory
{
    public const WINDOW_DAYS = 14;

    private const DAY_SECONDS = 86400;
    private const WINDOW_SECONDS = self::WINDOW_DAYS * self::DAY_SECONDS;

    public function __construct(
        public int $startedAtEpochSeconds,
        public int $observedAtEpochSeconds,
    ) {
        if ($startedAtEpochSeconds < 0 || $observedAtEpochSeconds < 0
            || $startedAtEpochSeconds > PHP_INT_MAX - self::WINDOW_SECONDS) {
            throw new InvalidArgumentException('Cronologia demonstrativa de trial invalida.');
        }
    }

    public function endsAtEpochSeconds(): int
    {
        return $this->startedAtEpochSeconds + self::WINDOW_SECONDS;
    }

    public function phase(): TrialWindowPhase
    {
        if ($this->observedAtEpochSeconds < $this->startedAtEpochSeconds) {
            return TrialWindowPhase::BeforeStart;
        }

        return $this->observedAtEpochSeconds < $this->endsAtEpochSeconds()
            ? TrialWindowPhase::WithinWindow
            : TrialWindowPhase::AfterWindow;
    }

    public function remainingSeconds(): int
    {
        if ($this->observedAtEpochSeconds < $this->startedAtEpochSeconds) {
            return self::WINDOW_SECONDS;
        }

        if ($this->observedAtEpochSeconds >= $this->endsAtEpochSeconds()) {
            return 0;
        }

        return $this->endsAtEpochSeconds() - $this->observedAtEpochSeconds;
    }

    public function remainingDaysRoundedUp(): int
    {
        // Presentational countdown; not calendar-day, SLA or access arithmetic.
        return intdiv($this->remainingSeconds() + self::DAY_SECONDS - 1, self::DAY_SECONDS);
    }
}
