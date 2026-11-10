<?php

declare(strict_types=1);

namespace App\Core\Plans;

/**
 * REV-F5-01: compact display-only view combining supplied trial clock and quotas.
 *
 * Inputs are already-constructed, untrusted laboratory readouts. This class
 * does not determine eligibility, CNPJ reuse, plan ownership, membership,
 * billing, credits, enforcement, invitation or application access.
 *
 * For all phases and quota bands, no business effects or rights are implied.
 */
final readonly class SyntheticTrialQuotaOverview
{
    public function __construct(
        private TrialWindowAdvisory $trial,
        private QuotaSnapshotReadout $quotas,
    ) {}

    /**
     * @return array{
     *   trial:array{phase:string,ends_at_epoch:int,remaining_seconds:int,remaining_days_rounded_up:int},
     *   quotas:list<array{meter:string,limit:int,used:int,remaining:int,excess:int,band:QuotaSnapshotBand}>,
     *   quota_band_counts:array{in_range:int,at_limit:int,over_limit:int,zero_capacity:int},
     *   informational_only:true,authorized:false,can_activate:false,can_charge:false,can_enforce:false
     * }
     */
    public function readout(): array
    {
        $cards = $this->quotas->cards();
        $counts = [
            'in_range' => 0,
            'at_limit' => 0,
            'over_limit' => 0,
            'zero_capacity' => 0,
        ];

        foreach ($cards as $card) {
            $category = match ($card['band']) {
                QuotaSnapshotBand::InRange => 'in_range',
                QuotaSnapshotBand::AtLimit => 'at_limit',
                QuotaSnapshotBand::OverLimit => 'over_limit',
                QuotaSnapshotBand::ZeroCapacity => 'zero_capacity',
            };
            $counts[$category]++;
        }

        return [
            'trial' => [
                'phase' => $this->trial->phase()->value,
                'ends_at_epoch' => $this->trial->endsAtEpochSeconds(),
                'remaining_seconds' => $this->trial->remainingSeconds(),
                'remaining_days_rounded_up' => $this->trial->remainingDaysRoundedUp(),
            ],
            'quotas' => $cards,
            'quota_band_counts' => $counts,
            'informational_only' => true,
            'authorized' => false,
            'can_activate' => false,
            'can_charge' => false,
            'can_enforce' => false,
        ];
    }
}
