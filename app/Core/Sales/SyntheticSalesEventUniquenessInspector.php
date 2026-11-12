<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R07/R24 laboratory: strict uniqueness of fabricated sales event claims.
 *
 * Identifier shape/uniqueness and arithmetic are NOT transaction, tenant,
 * authorization, provenance or revenue evidence. The event references never
 * leave this object; no reconciliation, upsert or rollback is performed.
 */
final class SyntheticSalesEventUniquenessInspector
{
    public const MAX_EXACT_CENTS = 9007199254740991;
    private const MAX_CLAIMS = 20;

    /**
     * @param mixed $claims list<array{event_reference:string,amount_cents:int}>
     * @return array{
     *   claim_count:int,unique_reference_count:int,claimed_sum_cents:int,
     *   recognized_revenue_cents:0,verified_event_count:0,
     *   currency_code:'BRL',provenance_verified:false,tenant_verified:false,
     *   informational_only:true,authorized:false,can_recognize_revenue:false,
     *   can_update_targets:false,can_invoice:false,can_persist:false
     * }
     */
    public function inspect(mixed $claims): array
    {
        if (!is_array($claims)
            || !array_is_list($claims)
            || count($claims) > self::MAX_CLAIMS) {
            self::invalid();
        }

        $seen = [];
        $sum = 0;
        foreach ($claims as $claim) {
            if (!is_array($claim) || count($claim) !== 2) {
                self::invalid();
            }
            $keys = array_keys($claim);
            sort($keys);
            if ($keys !== ['amount_cents', 'event_reference']
                || !is_string($claim['event_reference'])
                || preg_match(
                    '/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD',
                    $claim['event_reference'],
                ) !== 1
                || strtolower($claim['event_reference']) === '00000000-0000-0000-0000-000000000000'
                || !is_int($claim['amount_cents'])
                || $claim['amount_cents'] < 0
                || $claim['amount_cents'] > self::MAX_EXACT_CENTS) {
                self::invalid();
            }

            // Same UUID with different case is still the same untrusted claim.
            $reference = strtolower($claim['event_reference']);
            if (isset($seen[$reference])
                || $claim['amount_cents'] > self::MAX_EXACT_CENTS - $sum) {
                self::invalid();
            }
            $seen[$reference] = true;
            $sum += $claim['amount_cents'];
        }

        return [
            'claim_count' => count($claims),
            'unique_reference_count' => count($seen),
            'claimed_sum_cents' => $sum,
            'recognized_revenue_cents' => 0,
            'verified_event_count' => 0,
            'currency_code' => 'BRL',
            'provenance_verified' => false,
            'tenant_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_recognize_revenue' => false,
            'can_update_targets' => false,
            'can_invoice' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        // Never echo event reference, monetary amount or user-supplied field.
        throw new InvalidArgumentException('Referencias sinteticas de venda invalidas.');
    }
}
