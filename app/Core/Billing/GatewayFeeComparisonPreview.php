<?php

declare(strict_types=1);

namespace App\Core\Billing;

use InvalidArgumentException;

/**
 * CRM-BILL-PROVIDER-01: comparison-only candidate for Asaas vs C6 Bank.
 *
 * No gateway SDK, merchant rate, payment credentials, HTTP, database, CNPJ,
 * approval, checkout or provider switch. Rates are explicitly TEST INPUTS.
 * A selection result is never evidence that the gateway can charge.
 */
final class GatewayFeeComparisonPreview
{
    private const PROVIDERS = ['asaas', 'c6'];
    private const MAX_AMOUNT_CENTS = 1_000_000_000;
    private const MAX_FIXED_FEE_CENTS = 100_000;
    private const MAX_BASIS_POINTS = 5_000;

    /**
     * @param array<string,array{enabled_for_comparison:bool,fixed_fee_cents:int,variable_fee_basis_points:int}> $quotes
     * @return array<string,mixed>
     */
    public static function compare(
        string $currency,
        int $amountCents,
        array $quotes,
        ?string $existingProvider = null,
    ): array {
        if ($currency !== 'BRL' || $amountCents < 100
            || $amountCents > self::MAX_AMOUNT_CENTS) {
            throw new InvalidArgumentException('Faixa e moeda não suportadas no laboratório.');
        }
        if ($existingProvider !== null && !in_array($existingProvider, self::PROVIDERS, true)) {
            throw new InvalidArgumentException('Provedor vinculado desconhecido.');
        }
        if (count($quotes) < 1 || count($quotes) > count(self::PROVIDERS)
            || !array_key_exists('asaas', $quotes)) {
            throw new InvalidArgumentException('Asaas obrigatório como provedor candidato inicial.');
        }

        $entries = [];
        foreach ($quotes as $provider => $quote) {
            if (!is_string($provider) || !in_array($provider, self::PROVIDERS, true)
                || !is_array($quote)
                || array_keys($quote) !== [
                    'enabled_for_comparison',
                    'fixed_fee_cents',
                    'variable_fee_basis_points',
                ]
                || !is_bool($quote['enabled_for_comparison'])
                || !is_int($quote['fixed_fee_cents'])
                || !is_int($quote['variable_fee_basis_points'])
                || $quote['fixed_fee_cents'] < 0
                || $quote['fixed_fee_cents'] > self::MAX_FIXED_FEE_CENTS
                || $quote['variable_fee_basis_points'] < 0
                || $quote['variable_fee_basis_points'] > self::MAX_BASIS_POINTS) {
                throw new InvalidArgumentException('Candidatura de taxa inválida ou incompleta.');
            }

            // Integer-only BRL arithmetic: round VARIABLE fee UP, never
            // silently truncate fractions of a cent or use float inputs.
            $variableCents = intdiv(
                $amountCents * $quote['variable_fee_basis_points'] + 9_999,
                10_000,
            );
            $estimatedFee = $quote['fixed_fee_cents'] + $variableCents;
            if ($estimatedFee > $amountCents) {
                throw new InvalidArgumentException('Taxa estimada maior que o valor da cobrança.');
            }
            $entries[$provider] = [
                'provider' => $provider,
                'enabled_for_comparison' => $quote['enabled_for_comparison'],
                'estimated_fee_cents' => $estimatedFee,
                'estimated_net_cents' => $amountCents - $estimatedFee,
                'is_real_merchant_rate' => false,
                'can_route_payment' => false,
            ];
        }

        // Existing orders/subscriptions must NEVER silently change gateway,
        // even if another simulated fee is cheaper.
        if ($existingProvider !== null) {
            return self::response(
                'existing_provider_pinned',
                $existingProvider,
                $entries,
                $amountCents,
            );
        }

        $eligible = array_filter(
            $entries,
            static fn (array $item): bool => $item['enabled_for_comparison'],
        );
        if ($eligible === []) {
            return self::response('none_enabled', null, $entries, $amountCents);
        }

        // Asaas comes first in a tie: deterministic without asserting that
        // either provider is actually approved or integrated.
        uasort($eligible, static function (array $a, array $b): int {
            $byFee = $a['estimated_fee_cents'] <=> $b['estimated_fee_cents'];

            return $byFee !== 0 ? $byFee
                : array_search($a['provider'], self::PROVIDERS, true)
                    <=> array_search($b['provider'], self::PROVIDERS, true);
        });
        $proposed = array_key_first($eligible);

        return self::response(
            count($eligible) === 1 ? 'single_candidate_only' : 'fictional_fee_comparison',
            $proposed,
            $entries,
            $amountCents,
        );
    }

    /**
     * @param array<string,array<string,mixed>> $entries
     * @return array<string,mixed>
     */
    private static function response(
        string $reason,
        ?string $suggestedProvider,
        array $entries,
        int $amountCents,
    ): array {
        return [
            'currency' => 'BRL',
            'amount_cents' => $amountCents,
            'reason' => $reason,
            'suggested_provider' => $suggestedProvider,
            'quotes' => $entries,
            'requires_human_commercial_review' => true,
            'fees_verified_with_provider' => false,
            'can_create_customer' => false,
            'can_create_order' => false,
            'can_issue_invoice' => false,
            'can_create_charge' => false,
            'can_switch_existing_provider' => false,
            'can_activate_subscription' => false,
        ];
    }
}
