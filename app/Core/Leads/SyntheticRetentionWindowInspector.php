<?php

declare(strict_types=1);

namespace App\Core\Leads;

use DateTimeImmutable;

/**
 * Inert wall-clock calculation based on a CLAIMED first durable receipt.
 *
 * IN-127/147: 72h normal + 360h exceptional = 432h continuous since T0.
 * Warning review begins 72h before 432h (i.e. after 360h / day 15).
 * IN-128: claimed CRM-delivered payload cleanup window is 24h from
 * separately claimed confirmation; there is no destructive action here.
 *
 * These are synthetic calendar descriptions, not verified persistence,
 * real-time scheduling, operational expiry or disposal eligibility.
 */
final class SyntheticRetentionWindowInspector
{
    private const NORMAL_SECONDS = 72 * 3600;
    private const TOTAL_SECONDS = 432 * 3600;
    private const WARNING_SECONDS = 72 * 3600;
    private const DELIVERED_CLEANUP_SECONDS = 24 * 3600;

    public function inspect(
        DateTimeImmutable $claimedFirstDurableAt,
        DateTimeImmutable $now,
        ?DateTimeImmutable $claimedCrmConfirmedAt = null,
    ): SyntheticRetentionWindowAssessment {
        $t0 = $claimedFirstDurableAt->getTimestamp();
        $current = $now->getTimestamp();

        if ($t0 > $current || ($claimedCrmConfirmedAt !== null
            && ($claimedCrmConfirmedAt->getTimestamp() < $t0
                || $claimedCrmConfirmedAt->getTimestamp() > $current))) {
            return new SyntheticRetentionWindowAssessment('INVALID_TIMELINE', false, false, false);
        }

        // Presence of a claim does not establish delivery or authority.
        if ($claimedCrmConfirmedAt !== null) {
            $sinceClaim = $current - $claimedCrmConfirmedAt->getTimestamp();

            return new SyntheticRetentionWindowAssessment(
                'DELIVERY_CLAIM_UNVERIFIED',
                false,
                false,
                $sinceClaim >= self::DELIVERED_CLEANUP_SECONDS,
            );
        }

        $elapsed = $current - $t0;
        if ($elapsed >= self::TOTAL_SECONDS) {
            return new SyntheticRetentionWindowAssessment('EXPIRY_RECONCILIATION_REQUIRED', false, true, false);
        }

        if ($elapsed >= self::TOTAL_SECONDS - self::WARNING_SECONDS) {
            return new SyntheticRetentionWindowAssessment('FAILURE_WINDOW_WARNING_REVIEW', true, false, false);
        }

        if ($elapsed >= self::NORMAL_SECONDS) {
            return new SyntheticRetentionWindowAssessment('FAILURE_WINDOW', false, false, false);
        }

        return new SyntheticRetentionWindowAssessment('NORMAL_WINDOW', false, false, false);
    }
}
