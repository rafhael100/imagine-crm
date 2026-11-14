<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Billing\GatewayFeeComparisonPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GatewayFeeComparisonPreviewTest extends TestCase
{
    private static function quote(bool $enabled, int $fixed, int $bps): array
    {
        return [
            'enabled_for_comparison' => $enabled,
            'fixed_fee_cents' => $fixed,
            'variable_fee_basis_points' => $bps,
        ];
    }

    public function test_two_synthetic_quotes_propose_lower_cost_without_ever_authorizing_charge(): void
    {
        $result = GatewayFeeComparisonPreview::compare('BRL', 100_000, [
            'asaas' => self::quote(true, 50, 150),
            'c6' => self::quote(true, 20, 100),
        ]);
        $this->assertSame('fictional_fee_comparison', $result['reason']);
        $this->assertSame('c6', $result['suggested_provider']);
        $this->assertSame(1_550, $result['quotes']['asaas']['estimated_fee_cents']);
        $this->assertSame(1_020, $result['quotes']['c6']['estimated_fee_cents']);
        $this->assertSame(98_980, $result['quotes']['c6']['estimated_net_cents']);
        $this->assertFalse($result['fees_verified_with_provider']);
        $this->assertTrue($result['requires_human_commercial_review']);
        foreach ([
            'can_create_customer', 'can_create_order', 'can_issue_invoice',
            'can_create_charge', 'can_switch_existing_provider',
            'can_activate_subscription',
        ] as $flag) {
            $this->assertFalse($result[$flag], $flag);
        }
        foreach ($result['quotes'] as $quote) {
            $this->assertFalse($quote['can_route_payment']);
            $this->assertFalse($quote['is_real_merchant_rate']);
        }
    }

    public function test_existing_customer_charge_or_subscription_provider_is_pinned(): void
    {
        $quotes = [
            'asaas' => self::quote(true, 0, 500),
            'c6' => self::quote(true, 0, 10),
        ];
        $pinned = GatewayFeeComparisonPreview::compare('BRL', 5_000, $quotes, 'asaas');
        $this->assertSame('asaas', $pinned['suggested_provider']);
        $this->assertSame('existing_provider_pinned', $pinned['reason']);
        $this->assertFalse($pinned['can_switch_existing_provider']);

        // Even if a currently configured provider has no live fee quote,
        // the synthetic module cannot move an existing subscription.
        $missingC6 = GatewayFeeComparisonPreview::compare(
            'BRL', 5_000, ['asaas' => self::quote(true, 0, 0)], 'c6',
        );
        $this->assertSame('c6', $missingC6['suggested_provider']);
        $this->assertSame('existing_provider_pinned', $missingC6['reason']);
        $this->assertFalse($missingC6['can_create_charge']);
    }

    public function test_disabled_gateway_is_never_suggested_even_when_fictionally_cheaper(): void
    {
        $quote = GatewayFeeComparisonPreview::compare('BRL', 10_000, [
            'asaas' => self::quote(true, 100, 100),
            'c6' => self::quote(false, 0, 0),
        ]);
        $this->assertSame('asaas', $quote['suggested_provider']);
        $this->assertSame('single_candidate_only', $quote['reason']);
        $none = GatewayFeeComparisonPreview::compare('BRL', 10_000, [
            'asaas' => self::quote(false, 100, 100),
            'c6' => self::quote(false, 0, 0),
        ]);
        $this->assertNull($none['suggested_provider']);
        $this->assertSame('none_enabled', $none['reason']);
        $this->assertFalse($none['can_create_charge']);
    }

    public function test_equal_rates_prefer_stable_asaas_order_and_small_fees_round_up(): void
    {
        $quotes = [
            'asaas' => self::quote(true, 0, 1),
            'c6' => self::quote(true, 0, 1),
        ];
        $result = GatewayFeeComparisonPreview::compare('BRL', 101, $quotes);
        $this->assertSame('asaas', $result['suggested_provider']);
        $this->assertSame(1, $result['quotes']['asaas']['estimated_fee_cents']);
        $this->assertSame(100, $result['quotes']['asaas']['estimated_net_cents']);
    }

    public function test_rejects_invalid_currency_values_and_untrusted_quote_shapes(): void
    {
        $valid = ['asaas' => self::quote(true, 0, 250)];
        $bad = [
            ['USD', 1_000, $valid, null],
            ['BRL', 99, $valid, null],
            ['BRL', 1_000_000_001, $valid, null],
            ['BRL', 1_000, $valid, 'unknown'],
            ['BRL', 1_000, [], null],
            ['BRL', 1_000, ['c6' => self::quote(true, 0, 100)], null],
            ['BRL', 1_000, ['asaas' => self::quote(true, -1, 100)], null],
            ['BRL', 1_000, ['asaas' => self::quote(true, 0, 5001)], null],
            ['BRL', 1_000, ['asaas' => self::quote(true, 1500, 0)], null],
            ['BRL', 1_000, ['asaas' => [
                'enabled_for_comparison' => true,
                'fixed_fee_cents' => 0,
                'variable_fee_basis_points' => 100,
                'api_key' => 'SHOULD_NOT_BE_ACCEPTED',
            ]], null],
            ['BRL', 1_000, ['asaas' => [
                'enabled_for_comparison' => true,
                'fixed_fee_cents' => 0.1,
                'variable_fee_basis_points' => 100,
            ]], null],
        ];
        foreach ($bad as $args) {
            try {
                GatewayFeeComparisonPreview::compare(...$args);
                $this->fail('Invalid quote should always be denied.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
