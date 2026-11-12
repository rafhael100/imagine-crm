<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticCommercialDiscountReview;
use App\Core\Sales\SyntheticLineItemSubtotalPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCommercialDiscountReviewTest extends TestCase
{
    public function test_multiple_lines_reuse_exact_gross_subtotal_and_identify_review_thresholds(): void
    {
        $rows = [
            ['quantity' => 2, 'unit_price_cents' => 12990, 'discount_bp' => 500],
            ['quantity' => 1, 'unit_price_cents' => 7999, 'discount_bp' => 2000],
            ['quantity' => 1, 'unit_price_cents' => 1, 'discount_bp' => 5000],
        ];
        $original = $rows;
        $result = (new SyntheticCommercialDiscountReview())->inspect($rows, 1000);

        self::assertSame('BRL', $result['currency_code']);
        self::assertSame(3, $result['line_count']);
        self::assertSame(33980, $result['gross_total_cents']);
        self::assertSame([1299, 1600, 1], $result['line_discount_cents']);
        self::assertSame(2900, $result['discount_total_cents']);
        self::assertSame(31080, $result['discounted_total_cents']);
        self::assertSame([1, 2], $result['out_of_policy_positions']);
        self::assertTrue($result['requires_manager_review']);
        self::assertFalse($result['manager_approval_claimed']);
        self::assertFalse($result['manager_approval_verified']);
        self::assertFalse($result['discount_policy_verified']);
        self::assertFalse($result['taxes_calculated']);
        self::assertTrue($result['informational_only']);
        self::assertSame(
            $result['gross_total_cents'],
            $result['discount_total_cents'] + $result['discounted_total_cents'],
        );
        self::assertSame($original, $rows);
        self::assertSame($result, (new SyntheticCommercialDiscountReview())->inspect($rows, 1000));
    }

    public function test_manager_approval_claim_can_never_grant_discount_or_issue_authority(): void
    {
        $items = [['quantity' => 1, 'unit_price_cents' => 10000, 'discount_bp' => 4000]];
        $result = (new SyntheticCommercialDiscountReview())->inspect($items, 500, true);
        self::assertTrue($result['requires_manager_review']);
        self::assertTrue($result['manager_approval_claimed']);
        self::assertFalse($result['manager_approval_verified']);
        foreach (['authorized', 'can_apply_discount', 'can_issue_proposal', 'can_charge', 'can_persist'] as $flag) {
            self::assertFalse($result[$flag]);
        }

        $withinIllustrativePolicy = (new SyntheticCommercialDiscountReview())->inspect(
            $items, 5000, true,
        );
        self::assertFalse($withinIllustrativePolicy['requires_manager_review']);
        self::assertSame([], $withinIllustrativePolicy['out_of_policy_positions']);
        self::assertFalse($withinIllustrativePolicy['discount_policy_verified']);
        self::assertFalse($withinIllustrativePolicy['can_issue_proposal']);
    }

    public function test_half_up_per_line_and_maximum_safe_integer_never_use_floating_point(): void
    {
        $discount = new SyntheticCommercialDiscountReview();
        $rounding = $discount->inspect([
            ['quantity' => 1, 'unit_price_cents' => 1, 'discount_bp' => 5000],
            ['quantity' => 1, 'unit_price_cents' => 1, 'discount_bp' => 4999],
            ['quantity' => 1, 'unit_price_cents' => 9999, 'discount_bp' => 2500],
        ], 10000);
        self::assertSame([1, 0, 2500], $rounding['line_discount_cents']);
        self::assertSame(2501, $rounding['discount_total_cents']);
        self::assertSame(10001, $rounding['gross_total_cents']);
        self::assertSame(7500, $rounding['discounted_total_cents']);

        $max = SyntheticLineItemSubtotalPreview::MAX_EXACT_CENTS;
        $full = $discount->inspect([
            ['quantity' => 1, 'unit_price_cents' => $max, 'discount_bp' => 10000],
        ], 10000);
        self::assertSame($max, $full['gross_total_cents']);
        self::assertSame($max, $full['discount_total_cents']);
        self::assertSame(0, $full['discounted_total_cents']);

        $half = $discount->inspect([
            ['quantity' => 1, 'unit_price_cents' => $max, 'discount_bp' => 5000],
        ], 2500);
        self::assertSame(intdiv($max, 2) + 1, $half['discount_total_cents']);
        self::assertSame(intdiv($max, 2), $half['discounted_total_cents']);
        self::assertTrue($half['requires_manager_review']);
        self::assertFalse($half['authorized']);
    }

    public function test_empty_and_twelve_lines_remain_bounded_and_do_not_imply_tax_calculation(): void
    {
        $calculator = new SyntheticCommercialDiscountReview();
        $empty = $calculator->inspect([], 0);
        self::assertSame(0, $empty['line_count']);
        self::assertSame(0, $empty['gross_total_cents']);
        self::assertSame(0, $empty['discounted_total_cents']);
        self::assertSame([], $empty['line_discount_cents']);
        self::assertFalse($empty['requires_manager_review']);
        self::assertFalse($empty['taxes_calculated']);

        $items = array_fill(
            0,
            12,
            ['quantity' => 1, 'unit_price_cents' => 100, 'discount_bp' => 2500],
        );
        $result = $calculator->inspect($items, 2000);
        self::assertSame(12, $result['line_count']);
        self::assertSame(1200, $result['gross_total_cents']);
        self::assertSame(300, $result['discount_total_cents']);
        self::assertCount(12, $result['out_of_policy_positions']);
        self::assertFalse($result['can_charge']);
    }

    public function test_invalid_discounts_policy_types_extras_and_overflow_fail_closed(): void
    {
        $base = ['quantity' => 1, 'unit_price_cents' => 100, 'discount_bp' => 500];
        $max = SyntheticLineItemSubtotalPreview::MAX_EXACT_CENTS;
        $bad = [
            [null, 1000, false],
            ['lines', 1000, false],
            [false, 1000, false],
            [[null], 1000, false],
            [[1 => $base], 1000, false],
            [[$base], null, false],
            [[$base], '1000', false],
            [[$base], 1000.0, false],
            [[$base], -1, false],
            [[$base], 10001, false],
            [[$base], 1000, 'true'],
            [[$base], 1000, 1],
            [[array_merge($base, ['discount_bp' => -1])], 1000, false],
            [[array_merge($base, ['discount_bp' => 10001])], 1000, false],
            [[array_merge($base, ['discount_bp' => '500'])], 1000, false],
            [[array_merge($base, ['discount_bp' => 500.0])], 1000, false],
            [[array_merge($base, ['quantity' => -1])], 1000, false],
            [[array_merge($base, ['quantity' => '1'])], 1000, false],
            [[array_merge($base, ['unit_price_cents' => -1])], 1000, false],
            [[array_merge($base, ['unit_price_cents' => PHP_INT_MAX])], 1000, false],
            [[array_merge($base, ['email' => 'private@example.test'])], 1000, false],
            [[array_merge($base, ['tenant' => 'forged'])], 1000, false],
            [[['quantity' => 1, 'unit_price_cents' => 100]], 1000, false],
            [[['quantity' => 1, 'discount_bp' => 500]], 1000, false],
            [array_fill(0, 13, $base), 1000, false],
            [
                [
                    ['quantity' => 1, 'unit_price_cents' => $max, 'discount_bp' => 0],
                    ['quantity' => 1, 'unit_price_cents' => 1, 'discount_bp' => 0],
                ], 1000, false,
            ],
        ];

        foreach ($bad as [$lines, $policy, $claimed]) {
            try {
                (new SyntheticCommercialDiscountReview())->inspect($lines, $policy, $claimed);
                self::fail('Unsafe illustrative discount was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Revisao sintetica de desconto invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_output_exposes_only_cents_indexes_and_non_authority_flags(): void
    {
        $report = (new SyntheticCommercialDiscountReview())->inspect([
            ['quantity' => 1, 'unit_price_cents' => 10000, 'discount_bp' => 500],
        ], 0);
        self::assertSame([
            'currency_code', 'line_count', 'gross_total_cents',
            'line_discount_cents', 'discount_total_cents',
            'discounted_total_cents', 'out_of_policy_positions',
            'requires_manager_review', 'manager_approval_claimed',
            'manager_approval_verified', 'discount_policy_verified',
            'taxes_calculated', 'informational_only', 'authorized',
            'can_apply_discount', 'can_issue_proposal', 'can_charge', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'email', 'phone', 'customer', 'item_id',
            'catalog_id', 'opportunity_id', 'approver_id', 'actor',
            'tax_rate', 'address', 'name',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
    }
}
