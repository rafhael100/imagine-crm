<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * F3-143: conservative, synthetic cross-report differencing risk probe.
 *
 * This is NOT an anonymizer or authorization mechanism. Even a clean result
 * never authorizes publishing, exporting or delivering a report. No raw
 * records, tenant identifiers, recipient attributes or dynamic labels.
 */
final class SyntheticReportDifferencingRiskInspector
{
    private const STAGES = ['new', 'qualified', 'proposal', 'won', 'lost'];

    /**
     * @param mixed $earlier Exactly five integer bucket sizes, keyed by fixed stage enum
     * @param mixed $later Exactly five integer bucket sizes, keyed by fixed stage enum
     * @param mixed $minimumGroupSize Illustrative k in [2, 5]
     * @return array<string, mixed> Closed, metadata-only denial verdict
     */
    public function inspect(mixed $earlier, mixed $later, mixed $minimumGroupSize = 3): array
    {
        if (!is_int($minimumGroupSize) || $minimumGroupSize < 2 || $minimumGroupSize > 5) {
            self::invalid();
        }
        $a = self::validate($earlier);
        $b = self::validate($later);

        $smallDifferencePositions = [];
        $visibilityChangedPositions = [];
        $shrunkPositions = [];
        foreach (self::STAGES as $position => $stage) {
            $before = $a[$stage];
            $after = $b[$stage];
            $delta = abs($after - $before);
            if ($delta > 0 && $delta < $minimumGroupSize) {
                $smallDifferencePositions[] = $position;
            }
            if (($before >= $minimumGroupSize) !== ($after >= $minimumGroupSize)) {
                $visibilityChangedPositions[] = $position;
            }
            if ($after < $before) {
                $shrunkPositions[] = $position;
            }
        }

        $riskCodes = [];
        if ($smallDifferencePositions !== []) {
            $riskCodes[] = 'small_cross_snapshot_delta';
        }
        if ($visibilityChangedPositions !== []) {
            $riskCodes[] = 'suppression_boundary_changed';
        }
        if ($shrunkPositions !== []) {
            $riskCodes[] = 'bucket_shrank';
        }
        // Data access, cohort equivalence and other queries cannot be proven
        // from five synthetic counters. Always require independent review.
        $riskCodes[] = 'cross_query_history_unverified';

        return [
            'small_difference_stage_positions' => $smallDifferencePositions,
            'visibility_change_stage_positions' => $visibilityChangedPositions,
            'shrunk_stage_positions' => $shrunkPositions,
            'risk_codes' => $riskCodes,
            'risk_count' => count($riskCodes),
            'minimum_group_size' => $minimumGroupSize,
            'cross_query_history_verified' => false,
            'tenant_scope_verified' => false,
            'privacy_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_publish' => false,
            'can_export' => false,
            'can_deliver' => false,
            'can_persist' => false,
        ];
    }

    /** @return array<string, int> */
    private static function validate(mixed $snapshot): array
    {
        if (!is_array($snapshot) || count($snapshot) !== count(self::STAGES)) {
            self::invalid();
        }
        foreach (self::STAGES as $stage) {
            if (!array_key_exists($stage, $snapshot)
                || !is_int($snapshot[$stage])
                || $snapshot[$stage] < 0
                || $snapshot[$stage] > 20) {
                self::invalid();
            }
        }
        foreach (array_keys($snapshot) as $key) {
            if (!is_string($key) || !in_array($key, self::STAGES, true)) {
                self::invalid();
            }
        }
        return $snapshot;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Comparacao sintetica de relatorios invalida.');
    }
}
