<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R23/R24 and VEN-T14: ratio of invented unique event-claim amounts
 * against an invented target. This is NOT recognized sales, revenue, payout,
 * performance appraisal or evidence of achieved business goals.
 *
 * Rejects duplicate references using the existing sales-event inspector.
 * Uses integer-only 2x100 division to avoid unsafe 2^53 * 10,000 products.
 */
final class SyntheticSalesGoalProgressPreview
{
    /**
     * @param mixed $claims SyntheticSalesEventUniquenessInspector event list
     * @param mixed $goalCents positive integer <= Number.MAX_SAFE_INTEGER
     * @return array<string,mixed> Deny-only aggregate, no IDs or amounts by event
     */
    public function preview(mixed $claims, mixed $goalCents): array
    {
        if (!is_int($goalCents)
            || $goalCents <= 0
            || $goalCents > SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS) {
            self::invalid();
        }

        try {
            $events = (new SyntheticSalesEventUniquenessInspector())->inspect($claims);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $claimed = $events['claimed_sum_cents'];
        if ($claimed >= $goalCents) {
            $progressBp = 10000;
        } else {
            // Each partial product is <= (2^53 - 1)*100, fitting PHP 64-bit.
            $scaled = $claimed * 100;
            $major = intdiv($scaled, $goalCents);
            $rem = $scaled % $goalCents;
            $scaledRem = $rem * 100;
            $minor = intdiv($scaledRem, $goalCents);
            $lastRem = $scaledRem % $goalCents;
            $progressBp = $major * 100 + $minor
                + ($lastRem * 2 >= $goalCents ? 1 : 0);
            $progressBp = min($progressBp, 10000);
        }

        return [
            'currency_code' => 'BRL',
            'claimed_event_count' => $events['claim_count'],
            'unique_reference_count' => $events['unique_reference_count'],
            'illustrative_goal_cents' => $goalCents,
            'claimed_event_total_cents' => $claimed,
            'claimed_progress_bp_capped' => $progressBp,
            'claimed_shortfall_cents' => max(0, $goalCents - $claimed),
            'claimed_over_goal_cents' => max(0, $claimed - $goalCents),
            'recognized_sales_count' => 0,
            'recognized_revenue_cents' => 0,
            'recognized_progress_bp' => 0,
            'goal_policy_verified' => false,
            'event_provenance_verified' => false,
            'period_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_update_targets' => false,
            'can_pay_commissions' => false,
            'can_notify' => false,
            'can_export' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Meta sintetica de vendas invalida.');
    }
}
