<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Pure read-only advisory for a trusted, already-collected byte snapshot.
 *
 * Thresholds 80% and 95% are the documentary warning examples in CRM-REV-02.
 * This is NOT the authoritative usage ledger, entitlement decision,
 * upload guard, notification sender, or billing source.
 *
 * "Bytes" is an exact integer unit. No MB/MiB conversion is attempted.
 */
final readonly class AttachmentCapacityAdvisory
{
    public function __construct(
        public int $limitBytes,
        public int $usedBytes,
    ) {
        if ($limitBytes < 0 || $usedBytes < 0) {
            throw new InvalidArgumentException('Medicao de anexos invalida.');
        }
    }

    public function band(): AttachmentCapacityBand
    {
        if ($limitBytes = $this->limitBytes) {
            // Existing files must not be deleted merely for being over quota.
            if ($this->usedBytes > $limitBytes) {
                return AttachmentCapacityBand::OverLimit;
            }

            if ($this->usedBytes === $limitBytes) {
                return AttachmentCapacityBand::AtLimit;
            }

            if ($this->usedBytes >= $this->ceilingPercentOfLimit(95)) {
                return AttachmentCapacityBand::Notice95;
            }

            if ($this->usedBytes >= $this->ceilingPercentOfLimit(80)) {
                return AttachmentCapacityBand::Notice80;
            }

            return AttachmentCapacityBand::BelowNotice;
        }

        return $this->usedBytes === 0
            ? AttachmentCapacityBand::Unavailable
            : AttachmentCapacityBand::OverLimit;
    }

    public function remainingBytes(): int
    {
        return $this->usedBytes < $this->limitBytes
            ? $this->limitBytes - $this->usedBytes
            : 0;
    }

    public function excessBytes(): int
    {
        return $this->usedBytes > $this->limitBytes
            ? $this->usedBytes - $this->limitBytes
            : 0;
    }

    /**
     * ceil(limit * percentage / 100), without integer overflow or floats.
     *
     * Valid percentages are 80 and 95, neither adjustable by an HTTP client.
     */
    private function ceilingPercentOfLimit(int $percentage): int
    {
        $quotient = intdiv($this->limitBytes, 100);
        $remainder = $this->limitBytes % 100;

        return $quotient * $percentage
            + intdiv($remainder * $percentage + 99, 100);
    }
}
