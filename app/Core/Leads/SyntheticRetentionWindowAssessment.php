<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * A time-window descriptor for SYNTHETIC queue samples, not an action.
 * No authority, queue mutation or actual retention compliance is established.
 */
final readonly class SyntheticRetentionWindowAssessment
{
    public function __construct(
        public string $phase,
        public bool $warningReviewDue,
        public bool $expirationReviewDue,
        public bool $deliveryCleanupReviewDue,
    ) {}

    public function canDeleteTemporaryPayload(): bool
    {
        // Requires verified reconciliation, policy exceptions and audit.
        return false;
    }

    public function canRetryAutomatically(): bool
    {
        // No trusted state, throttle, signature or idempotency ledger.
        return false;
    }

    public function canSendNotification(): bool
    {
        // This diagnostic is not a notification dispatcher.
        return false;
    }
}
