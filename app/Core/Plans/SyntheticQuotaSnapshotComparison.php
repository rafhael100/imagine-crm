<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * REV-F5-03: compare two caller-supplied, anonymous quota snapshots.
 *
 * No tenant identity, trustworthy chronology, billable period, plan ownership,
 * origin or integrity can be established from these synthetic values. An
 * increase or a changed limit is NOT a credit, reservation, upgrade or charge.
 */
final readonly class SyntheticQuotaSnapshotComparison
{
    public function __construct(
        private QuotaSnapshotReadout $earlier,
        private QuotaSnapshotReadout $later,
    ) {}

    /**
     * @return array{
     *   rows:list<array{
     *     meter:string,earlier_limit:int,later_limit:int,limit_delta:int,
     *     earlier_used:int,later_used:int,usage_delta:int,
     *     earlier_remaining:int,later_remaining:int,remaining_delta:int,
     *     earlier_excess:int,later_excess:int,excess_delta:int,
     *     earlier_band:QuotaSnapshotBand,later_band:QuotaSnapshotBand,
     *     usage_trend:string,limit_changed:bool,band_changed:bool
     *   }>,
     *   meters:int,usage_increased:int,usage_decreased:int,usage_unchanged:int,
     *   limits_changed:int,bands_changed:int,
     *   informational_only:true,authorized:false,can_charge:false,
     *   can_enforce:false,can_export:false
     * }
     */
    public function compare(): array
    {
        $earlyCards = $this->earlier->cards();
        $laterCards = $this->later->cards();

        // Both readouts validate uniqueness independently. Still require the
        // very same set of meter names: missing, added or swapped meters are
        // a mismatch, not an implicit zero usage or a cross-account merge.
        $laterByMeter = [];
        foreach ($laterCards as $card) {
            $laterByMeter[$card['meter']] = $card;
        }
        if (count($earlyCards) !== count($laterByMeter)) {
            self::invalid();
        }

        $rows = [];
        $increased = 0;
        $decreased = 0;
        $unchanged = 0;
        $limitChanges = 0;
        $bandChanges = 0;
        foreach ($earlyCards as $early) {
            $meter = $early['meter'];
            if (!array_key_exists($meter, $laterByMeter)) {
                self::invalid();
            }
            $later = $laterByMeter[$meter];
            $usageDelta = $later['used'] - $early['used'];
            $limitChanged = $later['limit'] !== $early['limit'];
            $bandChanged = $later['band'] !== $early['band'];
            $trend = match (true) {
                $usageDelta > 0 => 'increased',
                $usageDelta < 0 => 'decreased',
                default => 'unchanged',
            };

            match ($trend) {
                'increased' => $increased++,
                'decreased' => $decreased++,
                default => $unchanged++,
            };
            if ($limitChanged) {
                ++$limitChanges;
            }
            if ($bandChanged) {
                ++$bandChanges;
            }

            $rows[] = [
                'meter' => $meter,
                'earlier_limit' => $early['limit'],
                'later_limit' => $later['limit'],
                'limit_delta' => $later['limit'] - $early['limit'],
                'earlier_used' => $early['used'],
                'later_used' => $later['used'],
                'usage_delta' => $usageDelta,
                'earlier_remaining' => $early['remaining'],
                'later_remaining' => $later['remaining'],
                'remaining_delta' => $later['remaining'] - $early['remaining'],
                'earlier_excess' => $early['excess'],
                'later_excess' => $later['excess'],
                'excess_delta' => $later['excess'] - $early['excess'],
                'earlier_band' => $early['band'],
                'later_band' => $later['band'],
                'usage_trend' => $trend,
                'limit_changed' => $limitChanged,
                'band_changed' => $bandChanged,
            ];
        }

        return [
            'rows' => $rows,
            'meters' => count($rows),
            'usage_increased' => $increased,
            'usage_decreased' => $decreased,
            'usage_unchanged' => $unchanged,
            'limits_changed' => $limitChanges,
            'bands_changed' => $bandChanges,
            'informational_only' => true,
            'authorized' => false,
            'can_charge' => false,
            'can_enforce' => false,
            'can_export' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Comparacao sintetica de franquias invalida.');
    }
}
