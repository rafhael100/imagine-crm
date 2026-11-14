<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Billing\AsaasSubscriptionDraftSpec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AsaasSubscriptionDraftSpecTest extends TestCase
{
    private const REF = 'eeeeeeee-aaaa-4444-8888-111111111111';

    public function test_four_current_reference_prices_are_cents_and_never_approved(): void
    {
        $prices = [
            'basico' => 6900,
            'essencial' => 8900,
            'profissional' => 14900,
            'empresarial' => 29900,
        ];

        foreach ($prices as $code => $cents) {
            $spec = AsaasSubscriptionDraftSpec::preview(
                $code, '2026-11-14', '2026-11-28', 'PIX', self::REF,
            );
            self::assertSame($code, $spec['plan_code']);
            self::assertSame($cents, $spec['monthly_cents']);
            self::assertIsInt($spec['monthly_cents']);
            self::assertSame('BRL', $spec['currency']);
            self::assertSame('MONTHLY', $spec['cycle']);
            self::assertSame('2026-11-28', $spec['trial_ends_on']);

            foreach (['price_homologated', 'terms_accepted',
                'company_owner_verified', 'can_create_subscription',
                'can_charge', 'can_activate', 'can_invoice'] as $flag) {
                self::assertFalse($spec[$flag], $code.':'.$flag);
            }
            self::assertTrue($spec['informational_only']);
            self::assertArrayNotHasKey('provider_customer_id', $spec);
            self::assertArrayNotHasKey('access_token', $spec);
        }
    }

    public function test_boleto_and_pix_are_explicitly_selected_without_card_collection(): void
    {
        $pix = AsaasSubscriptionDraftSpec::preview(
            'basico', '2026-11-14', '2026-11-28', 'PIX', self::REF,
        );
        $boleto = AsaasSubscriptionDraftSpec::preview(
            'basico', '2026-11-14', '2026-12-01', 'BOLETO', self::REF,
        );
        self::assertSame('PIX', $pix['billing_type']);
        self::assertSame('BOLETO', $boleto['billing_type']);
        self::assertSame('2026-12-01', $boleto['first_due_on']);
    }

    public function test_trial_window_rolls_over_month_and_leap_year_without_clock_dependency(): void
    {
        self::assertSame('2028-03-08', AsaasSubscriptionDraftSpec::preview(
            'essencial', '2028-02-23', '2028-03-08', 'BOLETO', self::REF,
        )['trial_ends_on']);
        self::assertSame('2027-01-07', AsaasSubscriptionDraftSpec::preview(
            'essencial', '2026-12-24', '2027-01-07', 'PIX', self::REF,
        )['trial_ends_on']);
    }

    public function test_unapproved_or_unsafe_inputs_are_denied_before_draft(): void
    {
        $cases = [
            ['unlisted', '2026-11-14', '2026-11-28', 'PIX', self::REF],
            ['basico', '2026-11-14', '2026-11-27', 'PIX', self::REF],
            ['basico', '2026-11-14', '2026-11-28', 'CREDIT_CARD', self::REF],
            ['basico', '2026-11-14', '2026-11-28', 'UNDEFINED', self::REF],
            ['basico', '2026-02-30', '2026-03-30', 'PIX', self::REF],
            ['basico', '2026-11-14', '2026-11-28', 'PIX', 'untrusted'],
            ['basico', '2026-11-14T00:00:00', '2026-11-28', 'PIX', self::REF],
            ['basico', '2026-11-14', '2026-11-99', 'PIX', self::REF],
        ];
        foreach ($cases as $case) {
            try {
                AsaasSubscriptionDraftSpec::preview(...$case);
                self::fail('Unsafe subscription draft accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertStringNotContainsString(self::REF, $e->getMessage());
            }
        }
    }
}
