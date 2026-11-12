<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R25/R26/R27: end-to-end fictional report hand-off review combining
 * aggregate redaction, CSV text safety and recipient revalidation.
 *
 * Deliberately DOES NOT return the CSV string. A consumer who has not
 * independently proved its recipient/tenant privileges receives only
 * numeric counts and closed warning codes. No download or delivery token.
 */
final class SyntheticReportDeliveryCompositeInspector
{
    /**
     * @param mixed $rows Fictional closed stage+claimed cents list
     * @param mixed $minimumGroupSize 2..5 only
     * @param mixed $schedule Fictional scheduling claim
     * @param mixed $delivery Fictional delivery-time access claim
     * @return array<string,mixed> Metadata-only, deny-all report
     */
    public function inspect(
        mixed $rows,
        mixed $minimumGroupSize,
        mixed $schedule,
        mixed $delivery,
    ): array {
        try {
            // Even if access has already been claimed revoked, validate all
            // report rows and the full access snapshot before summarizing.
            $aggregate = (new SyntheticStageAggregateCsvPreview())
                ->preview($rows, $minimumGroupSize);
            $recipient = (new SyntheticScheduledReportDeliveryPreflight())
                ->inspect($schedule, $delivery);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $issues = [];
        if ($aggregate['suppressed_bucket_count'] > 0) {
            $issues[] = 'small_groups_suppressed';
        }
        if ($recipient['access_revoked_since_schedule']) {
            $issues[] = 'recipient_access_revoked_claim';
        }
        if ($recipient['policy_revision_changed']) {
            $issues[] = 'recipient_policy_changed_claim';
        }
        if ($recipient['claimed_report_state'] !== 'ready') {
            $issues[] = 'report_not_ready_claim';
        }

        return [
            'csv_format' => 'stage,count,claimed_sum_cents',
            'csv_text_returned' => false,
            'visible_aggregate_lines' => $aggregate['visible_line_count'],
            'suppressed_bucket_count' => $aggregate['suppressed_bucket_count'],
            'requested_field_count' => $recipient['requested_field_count'],
            'claimed_report_state' => $recipient['claimed_report_state'],
            'recipient_access_revoked_claim' => $recipient['access_revoked_since_schedule'],
            'policy_revision_changed_claim' => $recipient['policy_revision_changed'],
            'review_issue_codes' => $issues,
            'review_issue_count' => count($issues),
            'recipient_blocker_codes' => $recipient['blocker_codes'],
            'recognized_revenue_cents' => 0,
            'report_data_verified' => false,
            'tenant_scope_verified' => false,
            'privacy_policy_verified' => false,
            'recipient_permissions_verified' => false,
            'transport_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_report' => false,
            'can_export' => false,
            'can_generate_link' => false,
            'can_download' => false,
            'can_deliver' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Pacote sintetico de relatorio invalido.');
    }
}
