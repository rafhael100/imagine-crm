<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * F3-150: exhaustive pairwise differencing check over an invented sequence
 * of report count snapshots, including nonadjacent comparisons.
 *
 * No tenant IDs, report IDs, count values, timing, contacts or business
 * capabilities leave this component. These snapshots are self-asserted
 * fictional inputs; they DO NOT prove complete query history.
 */
final class SyntheticReportHistoryPairwiseRiskInspector
{
    private const MAX_SNAPSHOTS = 8;

    /** @param mixed $snapshots list of 2..8 closed five-stage counter maps */
    public function inspect(mixed $snapshots, mixed $minimumGroupSize = 3): array
    {
        if (!is_array($snapshots) || !array_is_list($snapshots)
            || count($snapshots) < 2 || count($snapshots) > self::MAX_SNAPSHOTS) {
            self::invalid();
        }

        $diff = new SyntheticReportDifferencingRiskInspector();
        try {
            // Validate every snapshot even if it is not part of a risky pair.
            foreach ($snapshots as $snapshot) {
                $diff->inspect($snapshot, $snapshot, $minimumGroupSize);
            }

            $riskPairs = [];
            $checkedPairs = 0;
            $hasBoundaryChange = false;
            $hasSmallDelta = false;
            for ($i = 0, $n = count($snapshots); $i < $n; ++$i) {
                for ($j = $i + 1; $j < $n; ++$j) {
                    $out = $diff->inspect($snapshots[$i], $snapshots[$j], $minimumGroupSize);
                    ++$checkedPairs;
                    $smallDelta = $out['small_difference_stage_positions'] !== [];
                    $boundary = $out['visibility_change_stage_positions'] !== [];
                    if ($smallDelta || $boundary) {
                        $riskPairs[] = [$i, $j];
                    }
                    $hasSmallDelta = $hasSmallDelta || $smallDelta;
                    $hasBoundaryChange = $hasBoundaryChange || $boundary;
                }
            }
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $riskCodes = [];
        if ($hasSmallDelta) {
            $riskCodes[] = 'small_delta_across_history';
        }
        if ($hasBoundaryChange) {
            $riskCodes[] = 'suppression_boundary_across_history';
        }
        $riskCodes[] = 'complete_query_history_unverified';

        return [
            'snapshot_count' => count($snapshots),
            'pair_count_checked' => $checkedPairs,
            'risk_pair_positions' => $riskPairs,
            'risk_pair_count' => count($riskPairs),
            'risk_codes' => $riskCodes,
            'minimum_group_size' => $minimumGroupSize,
            'complete_query_history_verified' => false,
            'tenant_scope_verified' => false,
            'recipient_permissions_verified' => false,
            'privacy_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_publish' => false,
            'can_export' => false,
            'can_deliver' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Historico sintetico de relatorios invalido.');
    }
}
