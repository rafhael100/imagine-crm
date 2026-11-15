<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * CRM-COM-P0: read-only commercial reference prices, NOT a checkout.
 *
 * Based on CRM-REV-02 (03/11/2026). All monthly prices are planning references;
 * the full resources, seats and price list have NOT been commercially ratified.
 *
 * This type cannot persist an order, activate a trial, grant entitlements, create
 * an account, select a provider, apply a discount or charge a person.
 *
 * Money is integer BRL cents. No float conversion or externally supplied price.
 */
final class CommercialPlanReferenceCatalog
{
    /**
     * @var array<string,array{name:string,monthly_cents:int,storage_label:string,seats_reference:int,discounts_approved:false}>
     */
    private const REFERENCE = [
        'basico' => [
            'name' => 'Básico',
            'monthly_cents' => 6900,
            'storage_label' => '1 GB',
            'seats_reference' => 1,
            'discounts_approved' => false,
        ],
        'essencial' => [
            'name' => 'Essencial',
            'monthly_cents' => 8900,
            'storage_label' => '5 GB',
            'seats_reference' => 2,
            'discounts_approved' => false,
        ],
        'profissional' => [
            'name' => 'Profissional',
            'monthly_cents' => 14900,
            'storage_label' => '20 GB',
            'seats_reference' => 5,
            'discounts_approved' => false,
        ],
        'empresarial' => [
            'name' => 'Empresarial',
            'monthly_cents' => 29900,
            'storage_label' => '50 GB',
            'seats_reference' => 10,
            'discounts_approved' => false,
        ],
    ];

    /**
     * @return list<array{
     *  code:string,name:string,monthly_cents:int,storage_label:string,
     *  seats_reference:int,currency:'BRL',informational_only:true,
     *  price_homologated:false,entitlements_homologated:false,can_checkout:false
     * }>
     */
    public static function plans(): array
    {
        $out = [];
        foreach (self::REFERENCE as $code => $record) {
            $out[] = [
                'code' => $code,
                'name' => $record['name'],
                'monthly_cents' => $record['monthly_cents'],
                'storage_label' => $record['storage_label'],
                'seats_reference' => $record['seats_reference'],
                'currency' => 'BRL',
                'informational_only' => true,
                'price_homologated' => false,
                'entitlements_homologated' => false,
                'can_checkout' => false,
            ];
        }

        return $out;
    }

    /**
     * Produces an arithmetic illustration for 1-12 monthly reference cycles.
     * Not a financial promise, provider quote, invoice, subscription or payment.
     *
     * @return array{
     *  code:string,name:string,currency:'BRL',cycles:int,
     *  monthly_cents:int,total_cents:int,
     *  informational_only:true,price_homologated:false,
     *  can_checkout:false,can_charge:false,can_activate:false,
     *  no_discount_applied:true
     * }
     */
    public static function preview(string $code, int $cycles): array
    {
        if (!isset(self::REFERENCE[$code]) || $cycles < 1 || $cycles > 12) {
            throw new InvalidArgumentException('Simulacao comercial do CRM invalida.');
        }

        $plan = self::REFERENCE[$code];

        return [
            'code' => $code,
            'name' => $plan['name'],
            'currency' => 'BRL',
            'cycles' => $cycles,
            'monthly_cents' => $plan['monthly_cents'],
            'total_cents' => $plan['monthly_cents'] * $cycles,
            'informational_only' => true,
            'price_homologated' => false,
            'can_checkout' => false,
            'can_charge' => false,
            'can_activate' => false,
            'no_discount_applied' => true,
        ];
    }
}
