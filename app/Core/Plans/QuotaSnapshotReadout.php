<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Display-only aggregate of ALREADY PROVIDED integer quota snapshots.
 *
 * Every input must be a zero-increase QuotaUsageProjection. The caller is
 * responsible for verifying one trusted tenant, plan, measurement source,
 * membership, entitlements, effective dates, and database consistency.
 *
 * No tenant identifiers, account data, operations or decisions are exposed.
 * This class never determines access, quota enforcement, billing or overages.
 */
final readonly class QuotaSnapshotReadout
{
    /** @var list<QuotaUsageProjection> */
    private array $snapshots;

    /**
     * @param array<mixed> $snapshots
     */
    public function __construct(array $snapshots)
    {
        // Cap the display-only aggregate, not any business plan or tenant quota.
        if (!array_is_list($snapshots) || count($snapshots) < 1 || count($snapshots) > 12) {
            self::invalid();
        }

        $meters = [];
        foreach ($snapshots as $snapshot) {
            if (!$snapshot instanceof QuotaUsageProjection
                || $snapshot->requestedIncrease !== 0
                || isset($meters[$snapshot->meter])) {
                self::invalid();
            }
            $meters[$snapshot->meter] = true;
        }

        $this->snapshots = $snapshots;
    }

    /**
     * Returns only arithmetic metadata and informational bands.
     *
     * @return list<array{meter: string, limit: int, used: int, remaining: int, excess: int, band: QuotaSnapshotBand}>
     */
    public function cards(): array
    {
        $cards = [];
        foreach ($this->snapshots as $snapshot) {
            $limit = $snapshot->configuredLimit;
            $used = $snapshot->currentlyUsed;

            $band = match (true) {
                $limit === 0 && $used === 0 => QuotaSnapshotBand::ZeroCapacity,
                $used > $limit => QuotaSnapshotBand::OverLimit,
                $used === $limit => QuotaSnapshotBand::AtLimit,
                default => QuotaSnapshotBand::InRange,
            };

            $cards[] = [
                'meter' => $snapshot->meter,
                'limit' => $limit,
                'used' => $used,
                'remaining' => $snapshot->remainingBefore(),
                'excess' => $snapshot->existingExcess(),
                'band' => $band,
            ];
        }

        return $cards;
    }

    private static function invalid(): never
    {
        // No meter identifier, input or cross-tenant state is reflected.
        throw new InvalidArgumentException('Resumo informativo de franquias invalido.');
    }
}
