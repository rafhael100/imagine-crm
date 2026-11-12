<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * F3-146: privacy-aware fictional dashboard snapshot review.
 *
 * Reuses existing validated stage distribution and differencing guards.
 * Comparison and ratios are advisory only and cannot enable authorization.
 * No raw records or CSV bytes are returned.
 */
final class SyntheticStageDashboardSnapshotReview
{
    private const STAGES = ['new', 'qualified', 'proposal', 'won', 'lost'];

    public function inspect(mixed $previousRows, mixed $currentRows, mixed $minimumGroupSize = 3): array
    {
        try {
            $previous = (new SyntheticPrivateStageDistributionPreview())
                ->preview($previousRows, $minimumGroupSize);
            $current = (new SyntheticPrivateStageDistributionPreview())
                ->preview($currentRows, $minimumGroupSize);
            $comparison = (new SyntheticReportDifferencingRiskInspector())
                ->inspect(
                    self::counts($previousRows),
                    self::counts($currentRows),
                    $minimumGroupSize,
                );
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Painel sintetico comparativo invalido.');
        }

        $hasDisclosureRisk = $comparison['small_difference_stage_positions'] !== []
            || $comparison['visibility_change_stage_positions'] !== [];
        $withholdAllRatios = $hasDisclosureRisk || $previous['ratios_withheld']
            || $current['ratios_withheld'];
        $stages = [];
        foreach ($current['stages'] as $cell) {
            $stages[] = [
                'stage' => $cell['stage'],
                'suppressed' => $cell['suppressed'],
                'share_basis_points' => $withholdAllRatios
                    ? null
                    : $cell['share_basis_points'],
            ];
        }

        $warningCodes = [];
        if ($previous['ratios_withheld'] || $current['ratios_withheld']) {
            $warningCodes[] = 'suppression_or_empty_denominator';
        }
        if ($hasDisclosureRisk) {
            $warningCodes[] = 'cross_snapshot_disclosure_risk';
        }
        $warningCodes[] = 'history_and_authorization_unverified';

        return [
            'stages' => $stages,
            'all_ratios_withheld' => $withholdAllRatios,
            'warning_codes' => $warningCodes,
            'warning_count' => count($warningCodes),
            'cross_snapshot_risk_codes' => $comparison['risk_codes'],
            'minimum_group_size' => $minimumGroupSize,
            'recognized_revenue_cents' => 0,
            'data_provenance_verified' => false,
            'cross_query_history_verified' => false,
            'tenant_scope_verified' => false,
            'privacy_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_customers' => false,
            'can_export' => false,
            'can_download' => false,
            'can_deliver' => false,
            'can_persist' => false,
        ];
    }

    /** @return array<string,int> */
    private static function counts(mixed $rows): array
    {
        // The distribution validator is called before this method; repeat
        // only a closed count on the same immutable array, never IDs or PII.
        $counts = array_fill_keys(self::STAGES, 0);
        foreach ($rows as $row) {
            ++$counts[$row['stage']];
        }
        return $counts;
    }
}
