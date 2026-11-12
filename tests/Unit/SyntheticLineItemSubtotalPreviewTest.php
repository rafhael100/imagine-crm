<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticLineItemSubtotalPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLineItemSubtotalPreviewTest extends TestCase
{
    public function test_multiple_fictitious_line_items_use_integer_cents_only(): void
    {
        $preview = (new SyntheticLineItemSubtotalPreview())->preview([
            ['quantity' => 2, 'unit_price_cents' => 12990],
            ['quantity' => 1, 'unit_price_cents' => 7999],
            ['quantity' => 3, 'unit_price_cents' => 0],
        ]);

        self::assertSame('BRL', $preview['currency_code']);
        self::assertSame(3, $preview['line_count']);
        self::assertSame([25980, 7999, 0], $preview['line_subtotals_cents']);
        self::assertSame(33979, $preview['subtotal_cents']);
        self::assertSame(array_sum($preview['line_subtotals_cents']), $preview['subtotal_cents']);
        self::assertFalse($preview['taxes_calculated']);
        self::assertFalse($preview['discounts_calculated']);
        self::assertTrue($preview['informational_only']);
        foreach (['authorized', 'can_issue_proposal', 'can_accept_proposal', 'can_charge', 'can_persist'] as $flag) {
            self::assertFalse($preview[$flag]);
        }
        self::assertSame($preview, (new SyntheticLineItemSubtotalPreview())->preview([
            ['unit_price_cents' => 12990, 'quantity' => 2],
            ['unit_price_cents' => 7999, 'quantity' => 1],
            ['unit_price_cents' => 0, 'quantity' => 3],
        ]));
        self::assertSame(0, (new SyntheticLineItemSubtotalPreview())->preview([])['subtotal_cents']);
    }

    public function test_maximum_safe_integer_boundary_and_addition_are_exact_and_guarded(): void
    {
        $inspector = new SyntheticLineItemSubtotalPreview();
        $max = SyntheticLineItemSubtotalPreview::MAX_EXACT_CENTS;
        self::assertSame($max, $inspector->preview([
            ['quantity' => 1, 'unit_price_cents' => $max - 1],
            ['quantity' => 1, 'unit_price_cents' => 1],
        ])['subtotal_cents']);

        foreach ([
            [['quantity' => 2, 'unit_price_cents' => intdiv($max, 2) + 1]],
            [['quantity' => 1, 'unit_price_cents' => $max], ['quantity' => 1, 'unit_price_cents' => 1]],
            [['quantity' => 1, 'unit_price_cents' => PHP_INT_MAX]],
        ] as $lines) {
            try {
                $inspector->preview($lines);
                self::fail('Unsafe synthetic subtotal was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Previa sintetica de subtotal invalida.', $error->getMessage());
            }
        }
    }

    public function test_invalid_line_shape_types_and_injected_fields_fail_closed(): void
    {
        $inspector = new SyntheticLineItemSubtotalPreview();
        $base = ['quantity' => 1, 'unit_price_cents' => 100];
        $invalid = [
            null, 'test', true, 7,
            [1 => $base],
            [null],
            [['quantity' => 0, 'unit_price_cents' => 1]],
            [['quantity' => 101, 'unit_price_cents' => 1]],
            [['quantity' => -1, 'unit_price_cents' => 1]],
            [['quantity' => true, 'unit_price_cents' => 1]],
            [['quantity' => '1', 'unit_price_cents' => 1]],
            [['quantity' => 1.0, 'unit_price_cents' => 1]],
            [['quantity' => 1, 'unit_price_cents' => -1]],
            [['quantity' => 1, 'unit_price_cents' => 10.2]],
            [['quantity' => 1, 'unit_price_cents' => '100']],
            [['quantity' => 1, 'unit_price_cents' => null]],
            [['quantity' => 1, 'unit_price_cents' => 100, 'email' => 'private@example.test']],
            [['quantity' => 1, 'unit_price_cents' => 100, 'tenant' => 'forged']],
            [['quantity' => 1]],
            array_fill(0, 13, $base),
        ];

        foreach ($invalid as $lines) {
            try {
                $inspector->preview($lines);
                self::fail('An invalid or PII-bearing line was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Previa sintetica de subtotal invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_result_contains_only_numeric_arithmetic_and_fixed_denials(): void
    {
        $inspector = new SyntheticLineItemSubtotalPreview();
        $report = $inspector->preview([['quantity' => 100, 'unit_price_cents' => 199]]);
        self::assertSame([
            'currency_code', 'line_count', 'line_subtotals_cents', 'subtotal_cents',
            'taxes_calculated', 'discounts_calculated', 'informational_only',
            'authorized', 'can_issue_proposal', 'can_accept_proposal',
            'can_charge', 'can_persist',
        ], array_keys($report));
        self::assertSame(19900, $report['subtotal_cents']);
        $serialized = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'name', 'email', 'address', 'product_id', 'phone', 'tax_rate', 'discount_percent'] as $forbidden) {
            self::assertStringNotContainsString('"'.$forbidden.'"', $serialized);
        }
    }
}
