<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R25/R26/R27 & F3-140: small fixed CSV TEXT preview of anonymous,
 * eligible fictional aggregate buckets, without files or download rights.
 *
 * CSV cells contain only the stage enum and nonnegative integers produced
 * by SyntheticSalesAggregatePrivacyPreview (which validates every row).
 * All suppressed groups, empty groups, grand totals, IDs and PII are omitted.
 *
 * Suppression cannot guarantee real anonymity: differencing/cross-query
 * attacks, tenant RLS and recipient authorization remain unreviewed.
 */
final class SyntheticStageAggregateCsvPreview
{
    private const HEADER = "stage,count,claimed_sum_cents\r\n";

    /**
     * @param mixed $rows Closed list of fictional stage+integer rows
     * @param mixed $minimumGroupSize 2..5, illustrative
     * @return array<string,mixed> Deny-only preview, not an export artifact
     */
    public function preview(mixed $rows, mixed $minimumGroupSize = 3): array
    {
        try {
            $summary = (new SyntheticSalesAggregatePrivacyPreview())
                ->summarize($rows, $minimumGroupSize);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $csv = self::HEADER;
        $visibleLines = 0;
        foreach ($summary['cells'] as $cell) {
            if ($cell['suppressed'] || $cell['count'] === 0) {
                continue;
            }

            // The upstream stage is an enum, count a bounded integer, and
            // sum a nonnegative exact-cent integer. These are never
            // untrusted strings. No spreadsheet formula prefixes exist.
            $csv .= $cell['stage'].','.$cell['count'].','
                .$cell['claimed_sum_cents']."\r\n";
            ++$visibleLines;
        }

        // Five fixed stages and no dynamic labels, titles or user values.
        if ($visibleLines > 5 || strlen($csv) > 256) {
            self::invalid();
        }

        return [
            'csv_preview_utf8' => $csv,
            'visible_line_count' => $visibleLines,
            'suppressed_bucket_count' => $summary['suppressed_bucket_count'],
            'minimum_group_size' => $minimumGroupSize,
            'recognized_revenue_cents' => 0,
            'privacy_policy_verified' => false,
            'tenant_scope_verified' => false,
            'recipient_permissions_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_customers' => false,
            'can_export' => false,
            'can_write_file' => false,
            'can_download' => false,
            'can_email' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Previa CSV comercial sintetica invalida.');
    }
}
