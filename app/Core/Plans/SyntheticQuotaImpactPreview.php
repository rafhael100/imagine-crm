<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * REV-F5-02: bounded arithmetic "what if" over supplied fictitious quotas.
 *
 * No trusted plan/tenant, current usage, entitlement or reservation is
 * derived here. Every counter comes from a caller-owned, untrusted snapshot.
 * No effects, billing decision, storage, quota grant or permission are made.
 */
final readonly class SyntheticQuotaImpactPreview
{
    /** @var list<QuotaUsageProjection> */
    private array $projections;

    /**
     * @param array<mixed> $projections
     */
    public function __construct(array $projections)
    {
        if (!array_is_list($projections) || count($projections) < 1 || count($projections) > 12) {
            self::invalid();
        }

        $seen = [];
        foreach ($projections as $projection) {
            if (!$projection instanceof QuotaUsageProjection || isset($seen[$projection->meter])) {
                self::invalid();
            }
            $seen[$projection->meter] = true;
        }

        $this->projections = $projections;
    }

    /**
     * @return array{
     *  rows:list<array{
     *    meter:string,limit:int,used:int,requested:int,projected:int,
     *    remaining_before:int,remaining_after:int,excess_before:int,
     *    excess_after:int,impact:string
     *  }>,
     *  impact_counts:array{
     *    in_range:int,at_limit:int,would_exceed:int,already_over:int,zero_capacity:int
     *  },
     *  informational_only:true,authorized:false,can_reserve:false,
     *  can_increase:false,can_charge:false
     * }
     */
    public function preview(): array
    {
        $rows = [];
        $counts = [
            'in_range' => 0,
            'at_limit' => 0,
            'would_exceed' => 0,
            'already_over' => 0,
            'zero_capacity' => 0,
        ];

        foreach ($this->projections as $projection) {
            $projected = $projection->projectedUsed();
            $impact = match (true) {
                $projection->isAlreadyOverLimit() => 'already_over',
                $projected > $projection->configuredLimit => 'would_exceed',
                $projection->configuredLimit === 0 => 'zero_capacity',
                $projected === $projection->configuredLimit => 'at_limit',
                default => 'in_range',
            };

            $counts[$impact]++;
            $rows[] = [
                'meter' => $projection->meter,
                'limit' => $projection->configuredLimit,
                'used' => $projection->currentlyUsed,
                'requested' => $projection->requestedIncrease,
                'projected' => $projected,
                'remaining_before' => $projection->remainingBefore(),
                'remaining_after' => $projection->remainingAfter(),
                'excess_before' => $projection->existingExcess(),
                'excess_after' => $projection->projectedExcess(),
                'impact' => $impact,
            ];
        }

        return [
            'rows' => $rows,
            'impact_counts' => $counts,
            'informational_only' => true,
            'authorized' => false,
            'can_reserve' => false,
            'can_increase' => false,
            'can_charge' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Projecao sintetica de franquia invalida.');
    }
}
