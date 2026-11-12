<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R25/R26 and VEN-T12/T13: limited synthetic stage aggregates.
 *
 * Suppresses stages with 1..k-1 invented rows; never emits the all-row
 * grand total because that could reveal a hidden singleton by subtraction.
 * This is not tenant RLS, real k-anonymity or an authorized report export.
 */
final class SyntheticSalesAggregatePrivacyPreview
{
    private const STAGES = ['new', 'qualified', 'proposal', 'won', 'lost'];
    private const MAX_ROWS = 20;

    /**
     * @param mixed $rows list<array{stage:string,claimed_value_cents:int}>
     * @param mixed $minimumGroupSize int 2..5, illustrative only
     * @return array<string,mixed> Suppressed group cells, no personal fields
     */
    public function summarize(mixed $rows, mixed $minimumGroupSize = 3): array
    {
        if (!is_array($rows)
            || !array_is_list($rows)
            || count($rows) > self::MAX_ROWS
            || !is_int($minimumGroupSize)
            || $minimumGroupSize < 2
            || $minimumGroupSize > 5) {
            self::invalid();
        }

        $counts = array_fill_keys(self::STAGES, 0);
        $sums = array_fill_keys(self::STAGES, 0);
        $grandTotal = 0;

        // Validate the full input before suppressing any hidden bucket.
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) !== 2) {
                self::invalid();
            }
            $keys = array_keys($row);
            sort($keys);
            if ($keys !== ['claimed_value_cents', 'stage']
                || !is_string($row['stage'])
                || !in_array($row['stage'], self::STAGES, true)
                || !is_int($row['claimed_value_cents'])
                || $row['claimed_value_cents'] < 0
                || $row['claimed_value_cents'] > SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS
                || $row['claimed_value_cents']
                    > SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS - $grandTotal) {
                self::invalid();
            }
            $stage = $row['stage'];
            ++$counts[$stage];
            $sums[$stage] += $row['claimed_value_cents'];
            $grandTotal += $row['claimed_value_cents'];
        }

        $cells = [];
        $visibleBuckets = 0;
        $suppressedBuckets = 0;
        $visibleAmount = 0;
        foreach (self::STAGES as $stage) {
            $count = $counts[$stage];
            $suppressed = $count > 0 && $count < $minimumGroupSize;
            if ($suppressed) {
                ++$suppressedBuckets;
            } elseif ($count > 0) {
                ++$visibleBuckets;
                $visibleAmount += $sums[$stage];
            }
            $cells[] = [
                'stage' => $stage,
                'count' => $suppressed ? null : $count,
                'claimed_sum_cents' => $suppressed ? null : $sums[$stage],
                'suppressed' => $suppressed,
            ];
        }

        return [
            'currency_code' => 'BRL',
            'minimum_group_size' => $minimumGroupSize,
            'cells' => $cells,
            'visible_bucket_count' => $visibleBuckets,
            'suppressed_bucket_count' => $suppressedBuckets,
            'visible_claimed_sum_cents' => $visibleAmount,
            'recognized_revenue_cents' => 0,
            'privacy_policy_verified' => false,
            'tenant_scope_verified' => false,
            'report_permissions_verified' => false,
            'claims_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_customers' => false,
            'can_export' => false,
            'can_schedule_report' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Agregacao sintetica comercial invalida.');
    }
}
