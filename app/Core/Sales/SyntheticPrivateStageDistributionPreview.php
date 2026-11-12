<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * F3-145: distribution for a five-stage fictional funnel dashboard.
 *
 * Ratios use ONLY validated non-sensitive bucket counts. If any nonempty
 * bucket is suppressed, ALL ratios are withheld to avoid publishing a
 * denominator that could undo suppression by subtraction. This is NOT an
 * authorization, analytics access or privacy guarantee.
 */
final class SyntheticPrivateStageDistributionPreview
{
    /** @param mixed $rows Closed fictional stage and integer cents rows */
    public function preview(mixed $rows, mixed $minimumGroupSize = 3): array
    {
        try {
            $aggregate = (new SyntheticSalesAggregatePrivacyPreview())
                ->summarize($rows, $minimumGroupSize);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Distribuicao sintetica de funil invalida.');
        }

        $ratiosWithheld = $aggregate['suppressed_bucket_count'] > 0;
        $denominator = 0;
        if (!$ratiosWithheld) {
            foreach ($aggregate['cells'] as $cell) {
                $denominator += $cell['count'];
            }
        }

        $stages = [];
        foreach ($aggregate['cells'] as $cell) {
            $count = $cell['count'];
            $stages[] = [
                'stage' => $cell['stage'],
                'count' => $count,
                'suppressed' => $cell['suppressed'],
                // Avoid division by zero and never publish percentages
                // based on an incomplete denominator.
                'share_basis_points' => $ratiosWithheld || $denominator === 0
                    ? null
                    : intdiv($count * 10000, $denominator),
            ];
        }

        return [
            'stages' => $stages,
            'ratios_withheld' => $ratiosWithheld || $denominator === 0,
            'suppressed_bucket_count' => $aggregate['suppressed_bucket_count'],
            'minimum_group_size' => $minimumGroupSize,
            'recognized_revenue_cents' => 0,
            'privacy_policy_verified' => false,
            'tenant_scope_verified' => false,
            'source_data_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_customers' => false,
            'can_export' => false,
            'can_download' => false,
            'can_persist' => false,
        ];
    }
}
