<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommercialPlanReferenceCatalogTest extends TestCase
{
    public function test_catalog_references_exactly_four_plans_without_sellable_flags(): void
    {
        $plans = CommercialPlanReferenceCatalog::plans();

        self::assertSame(['basico', 'essencial', 'profissional', 'empresarial'],
            array_column($plans, 'code'));
        self::assertSame([6900, 8900, 14900, 29900], array_column($plans, 'monthly_cents'));
        self::assertSame(['1 GB', '5 GB', '20 GB', '50 GB'], array_column($plans, 'storage_label'));
        self::assertSame([1, 2, 5, 10], array_column($plans, 'seats_reference'));

        foreach ($plans as $plan) {
            self::assertSame('BRL', $plan['currency']);
            self::assertTrue($plan['informational_only']);
            self::assertFalse($plan['price_homologated']);
            self::assertFalse($plan['entitlements_homologated']);
            self::assertFalse($plan['can_checkout']);
            self::assertIsInt($plan['monthly_cents']);
        }
    }

    public static function monthSnapshots(): array
    {
        return [
            'basico one month' => ['basico', 1, 6900],
            'essencial three months' => ['essencial', 3, 26700],
            'profissional twelve months' => ['profissional', 12, 178800],
            'empresarial twelve months' => ['empresarial', 12, 358800],
        ];
    }

    #[DataProvider('monthSnapshots')]
    public function test_arithmetic_is_exact_and_never_payment(string $code, int $cycles, int $cents): void
    {
        $quote = CommercialPlanReferenceCatalog::preview($code, $cycles);

        self::assertSame($code, $quote['code']);
        self::assertSame($cycles, $quote['cycles']);
        self::assertSame($cents, $quote['total_cents']);
        self::assertSame('BRL', $quote['currency']);
        self::assertTrue($quote['informational_only']);
        self::assertFalse($quote['price_homologated']);
        self::assertFalse($quote['can_checkout']);
        self::assertFalse($quote['can_charge']);
        self::assertFalse($quote['can_activate']);
        self::assertTrue($quote['no_discount_applied']);
        self::assertSame($cents, $quote['monthly_cents'] * $cycles);
    }

    public static function rejectedInputs(): array
    {
        return [
            'blank code' => ['', 1],
            'unknown code' => ['premium', 1],
            'faked price in code' => ['essencial:100', 1],
            'case variation denied' => ['Essencial', 1],
            'leading/trailing spaces' => [' basico ', 1],
            'zero cycles' => ['basico', 0],
            'negative cycles' => ['basico', -1],
            'more than twelve cycles' => ['basico', 13],
            'huge cycle count' => ['empresarial', PHP_INT_MAX],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_invalid_input_denied_without_echoing_data(string $code, int $cycles): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Simulacao comercial do CRM invalida.');

        CommercialPlanReferenceCatalog::preview($code, $cycles);
    }

    public function test_no_checkout_or_billing_side_effect_api_exists(): void
    {
        foreach (['purchase', 'subscribe', 'charge', 'capture', 'authorize', 'createAccount',
            'createTrial', 'persist', 'grant', 'issueInvoice', 'sendEmail', 'verifyCnpj',
            'upgrade', 'changeSubscription', 'discount'] as $method) {
            self::assertFalse(method_exists(CommercialPlanReferenceCatalog::class, $method), $method);
        }

        $before = CommercialPlanReferenceCatalog::plans();
        $copy = $before;
        $copy[0]['monthly_cents'] = 1;

        self::assertSame($before, CommercialPlanReferenceCatalog::plans(),
            'Clients cannot mutate the canonical reference records');
    }
}
