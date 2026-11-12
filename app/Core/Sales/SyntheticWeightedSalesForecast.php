<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R24 laboratory: forecast for invented, anonymous and OPEN opportunities.
 *
 * Probabilities are caller-supplied fictional integers in basis points
 * (0..10000). This is not a model or a forecast inferred from CRM data.
 * Never counts won revenue, invoices, commissions or expected cash receipts.
 * No client/tenant/opportunity identity, API, authorization or persistence.
 */
final class SyntheticWeightedSalesForecast
{
    public const MAX_EXACT_CENTS = 9007199254740991;

    private const BASIS_POINTS = 10000;
    private const MAX_ROWS = 20;

    /**
     * @param mixed $opportunities list<array{amount_cents:int,win_probability_bp:int}>
     * @return array{
     *   currency_code:'BRL',opportunity_count:int,
     *   unweighted_total_cents:int,weighted_total_cents:int,
     *   weighted_line_cents:list<int>,rounding:'half_up_per_line',
     *   probabilities_verified:false,forecast_verified:false,
     *   informational_only:true,authorized:false,
     *   can_mark_won:false,can_invoice:false,can_charge:false,can_persist:false
     * }
     */
    public function preview(mixed $opportunities): array
    {
        if (!is_array($opportunities)
            || !array_is_list($opportunities)
            || count($opportunities) > self::MAX_ROWS) {
            self::invalid();
        }

        $rawTotal = 0;
        $weightedTotal = 0;
        $lines = [];

        foreach ($opportunities as $opportunity) {
            if (!is_array($opportunity) || count($opportunity) !== 2) {
                self::invalid();
            }
            $keys = array_keys($opportunity);
            sort($keys);
            if ($keys !== ['amount_cents', 'win_probability_bp']
                || !is_int($opportunity['amount_cents'])
                || $opportunity['amount_cents'] < 0
                || $opportunity['amount_cents'] > self::MAX_EXACT_CENTS
                || !is_int($opportunity['win_probability_bp'])
                || $opportunity['win_probability_bp'] < 0
                || $opportunity['win_probability_bp'] > self::BASIS_POINTS) {
                self::invalid();
            }

            $amount = $opportunity['amount_cents'];
            $probability = $opportunity['win_probability_bp'];

            // Avoid amount * probability, which can overflow PHP int on
            // a value still safe in JavaScript. Split into whole/rem parts
            // before multiplication; rem * probability <= 99,990,000.
            $whole = intdiv($amount, self::BASIS_POINTS);
            $remainder = $amount % self::BASIS_POINTS;
            $weighted = $whole * $probability
                + intdiv($remainder * $probability + intdiv(self::BASIS_POINTS, 2), self::BASIS_POINTS);

            // Keep both cumulative sums exactly representable in JS Number.
            if ($amount > self::MAX_EXACT_CENTS - $rawTotal
                || $weighted > self::MAX_EXACT_CENTS - $weightedTotal) {
                self::invalid();
            }
            $rawTotal += $amount;
            $weightedTotal += $weighted;
            $lines[] = $weighted;
        }

        return [
            'currency_code' => 'BRL',
            'opportunity_count' => count($opportunities),
            'unweighted_total_cents' => $rawTotal,
            'weighted_total_cents' => $weightedTotal,
            'weighted_line_cents' => $lines,
            'rounding' => 'half_up_per_line',
            'probabilities_verified' => false,
            'forecast_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_mark_won' => false,
            'can_invoice' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Previsao ponderada sintetica invalida.');
    }
}
