<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R08/R09/R12/R13: pure illustrative line discounts, not an approved
 * catalog, tenant policy, manager identity, quote, invoice or authorization.
 *
 * Reuses the exact subtotal arithmetic from F3-108; never uses float,
 * counts a claimed approval as verified, or enables sending/billing.
 */
final class SyntheticCommercialDiscountReview
{
    private const BASIS_POINTS = 10000;

    /**
     * @param mixed $lines strict list<array{
     *   quantity:int,unit_price_cents:int,discount_bp:int
     * }>
     * @param mixed $policyMaximumBp 0..10000, illustrative only
     * @param mixed $managerApprovalClaimed bool, untrusted claim
     * @return array<string,mixed> Summary only; no line customer metadata
     */
    public function inspect(
        mixed $lines,
        mixed $policyMaximumBp,
        mixed $managerApprovalClaimed = false,
    ): array {
        if (!is_array($lines) || !array_is_list($lines)
            || count($lines) > 12
            || !is_int($policyMaximumBp)
            || $policyMaximumBp < 0
            || $policyMaximumBp > self::BASIS_POINTS
            || !is_bool($managerApprovalClaimed)) {
            self::invalid();
        }

        $bareLines = [];
        $bps = [];
        foreach ($lines as $line) {
            if (!is_array($line) || count($line) !== 3) {
                self::invalid();
            }
            $keys = array_keys($line);
            sort($keys);
            if ($keys !== ['discount_bp', 'quantity', 'unit_price_cents']
                || !is_int($line['discount_bp'])
                || $line['discount_bp'] < 0
                || $line['discount_bp'] > self::BASIS_POINTS) {
                self::invalid();
            }
            $bareLines[] = [
                'quantity' => $line['quantity'],
                'unit_price_cents' => $line['unit_price_cents'],
            ];
            $bps[] = $line['discount_bp'];
        }

        try {
            $base = (new SyntheticLineItemSubtotalPreview())->preview($bareLines);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $discounts = [];
        $outOfPolicy = [];
        $discountTotal = 0;
        foreach ($base['line_subtotals_cents'] as $index => $gross) {
            $bp = $bps[$index];

            // Safely split by 10,000 BEFORE multiplying. Even the largest
            // JS-safe cent amount stays within PHP's 64-bit integer range.
            $whole = intdiv($gross, self::BASIS_POINTS);
            $remainder = $gross % self::BASIS_POINTS;
            $discount = $whole * $bp
                + intdiv(
                    $remainder * $bp + intdiv(self::BASIS_POINTS, 2),
                    self::BASIS_POINTS,
                );

            if ($discount > $gross
                || $discount > SyntheticLineItemSubtotalPreview::MAX_EXACT_CENTS - $discountTotal) {
                self::invalid();
            }

            $discounts[] = $discount;
            $discountTotal += $discount;
            if ($bp > $policyMaximumBp) {
                $outOfPolicy[] = $index;
            }
        }

        $grossTotal = $base['subtotal_cents'];
        if ($discountTotal > $grossTotal) {
            self::invalid();
        }

        return [
            'currency_code' => 'BRL',
            'line_count' => count($lines),
            'gross_total_cents' => $grossTotal,
            'line_discount_cents' => $discounts,
            'discount_total_cents' => $discountTotal,
            'discounted_total_cents' => $grossTotal - $discountTotal,
            'out_of_policy_positions' => $outOfPolicy,
            'requires_manager_review' => count($outOfPolicy) > 0,
            'manager_approval_claimed' => $managerApprovalClaimed,
            'manager_approval_verified' => false,
            'discount_policy_verified' => false,
            'taxes_calculated' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_apply_discount' => false,
            'can_issue_proposal' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao sintetica de desconto invalida.');
    }
}
