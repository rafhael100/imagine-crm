<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R08 laboratory: price × quantity and subtotal in BRL minor units ONLY.
 *
 * This is NOT an opportunity, real catalog price, invoice, proposal, taxable
 * value, currency conversion or authorization. Callers own every invented
 * quantity and price. Discounts, tax rules, fees and approval policy are
 * intentionally absent, not implicitly zero-valued.
 */
final class SyntheticLineItemSubtotalPreview
{
    public const MAX_EXACT_CENTS = 9007199254740991;
    private const MAX_ROWS = 12;
    private const MAX_QUANTITY = 100;

    /**
     * @param mixed $items strict list<array{quantity:int,unit_price_cents:int}>
     * @return array{
     *   currency_code:'BRL',line_count:int,line_subtotals_cents:list<int>,
     *   subtotal_cents:int,taxes_calculated:false,discounts_calculated:false,
     *   informational_only:true,authorized:false,can_issue_proposal:false,
     *   can_accept_proposal:false,can_charge:false,can_persist:false
     * }
     */
    public function preview(mixed $items): array
    {
        if (!is_array($items)
            || !array_is_list($items)
            || count($items) > self::MAX_ROWS) {
            self::invalid();
        }

        $subtotals = [];
        $subtotal = 0;

        foreach ($items as $item) {
            if (!is_array($item) || count($item) !== 2) {
                self::invalid();
            }
            $keys = array_keys($item);
            sort($keys);
            if ($keys !== ['quantity', 'unit_price_cents']
                || !is_int($item['quantity'])
                || $item['quantity'] < 1
                || $item['quantity'] > self::MAX_QUANTITY
                || !is_int($item['unit_price_cents'])
                || $item['unit_price_cents'] < 0
                || $item['unit_price_cents'] > self::MAX_EXACT_CENTS) {
                self::invalid();
            }

            // Prevent PHP 64-bit overflow and JS Number precision loss
            // BEFORE multiplying and before accumulating a result.
            if ($item['unit_price_cents'] > intdiv(self::MAX_EXACT_CENTS, $item['quantity'])) {
                self::invalid();
            }
            $line = $item['unit_price_cents'] * $item['quantity'];
            if ($line > self::MAX_EXACT_CENTS - $subtotal) {
                self::invalid();
            }

            $subtotals[] = $line;
            $subtotal += $line;
        }

        return [
            'currency_code' => 'BRL',
            'line_count' => count($items),
            'line_subtotals_cents' => $subtotals,
            'subtotal_cents' => $subtotal,
            'taxes_calculated' => false,
            'discounts_calculated' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_issue_proposal' => false,
            'can_accept_proposal' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        // No caller-owned price, tenant, catalog item or external data echoed.
        throw new InvalidArgumentException('Previa sintetica de subtotal invalida.');
    }
}
