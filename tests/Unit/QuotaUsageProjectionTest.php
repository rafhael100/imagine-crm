<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaUsageProjection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuotaUsageProjectionTest extends TestCase
{
    /**
     * Illustrative fixture quotas from REV-02's TRIAL planning, not active
     * entitlements. Numbers are injected, never selected by the calculator.
     */
    public static function illustrativePlanningCases(): array
    {
        return [
            'users exactly at trial sample quota' => ['users', 2, 1, 1, 2, 1, 0, 0, 0, true, false],
            'leads exceed a sample trial quota by one' => ['leads', 20, 18, 3, 21, 2, 0, 0, 1, false, false],
            'funnels do not fit over quota' => ['funnels', 2, 2, 1, 3, 0, 0, 0, 1, false, false],
            'automations already over sample quota' => ['automations', 2, 3, 0, 3, 0, 0, 1, 1, false, true],
            'disabled quota with empty request' => ['feature_flag', 0, 0, 0, 0, 0, 0, 0, 0, true, false],
            'disabled quota with new request' => ['feature_flag', 0, 0, 1, 1, 0, 0, 0, 1, false, false],
            'overquota still increasing' => ['leads', 20, 25, 7, 32, 0, 0, 5, 12, false, true],
            'uncapped is not represented as zero' => ['credits', 0, 1, 0, 1, 0, 0, 1, 1, false, true],
            'remaining balance after partial request' => ['ai_credits', 10, 4, 3, 7, 6, 3, 0, 0, true, false],
            'integer maximum exact fit' => ['storage_bytes', PHP_INT_MAX, PHP_INT_MAX - 1, 1,
                PHP_INT_MAX, 1, 0, 0, 0, true, false],
            'integer maximum with zero increment' => ['large_meter', PHP_INT_MAX, PHP_INT_MAX, 0,
                PHP_INT_MAX, 0, 0, 0, 0, true, false],
        ];
    }

    #[DataProvider('illustrativePlanningCases')]
    public function test_quota_arithmetic_does_not_grant_access(
        string $meter,
        int $limit,
        int $used,
        int $increase,
        int $expectedProjected,
        int $expectedBefore,
        int $expectedAfter,
        int $expectedExistingExcess,
        int $expectedProjectedExcess,
        bool $fits,
        bool $alreadyExcess,
    ): void {
        $projection = new QuotaUsageProjection($meter, $limit, $used, $increase);

        self::assertSame($meter, $projection->meter);
        self::assertSame($limit, $projection->configuredLimit);
        self::assertSame($used, $projection->currentlyUsed);
        self::assertSame($increase, $projection->requestedIncrease);
        self::assertSame($expectedProjected, $projection->projectedUsed());
        self::assertSame($expectedBefore, $projection->remainingBefore());
        self::assertSame($expectedAfter, $projection->remainingAfter());
        self::assertSame($expectedExistingExcess, $projection->existingExcess());
        self::assertSame($expectedProjectedExcess, $projection->projectedExcess());
        self::assertSame($fits, $projection->fitsWithinConfiguredLimit());
        self::assertSame($alreadyExcess, $projection->isAlreadyOverLimit());

        // No entitlement, tenant, user ID, permission, feature toggle or payment.
        self::assertSame(
            ['meter', 'configuredLimit', 'currentlyUsed', 'requestedIncrease'],
            array_keys(get_object_vars($projection)),
        );
        self::assertFalse(method_exists($projection, 'canProvision'));
        self::assertFalse(method_exists($projection, 'authorize'));
        self::assertFalse(method_exists($projection, 'charge'));
    }

    public static function invalidMeterCases(): array
    {
        return [
            'empty' => [''],
            'leading digit' => ['2users'],
            'leading underscore' => ['_leads'],
            'uppercase' => ['Leads'],
            'dash' => ['storage-bytes'],
            'space' => ['leads remaining'],
            'new line' => ["leads\n"],
            'unicode' => ['usuários'],
            'overlong' => ['m'.str_repeat('x', 64)],
        ];
    }

    #[DataProvider('invalidMeterCases')]
    public function test_invalid_resource_names_are_rejected(string $meter): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Medidor de franquia invalido.');
        new QuotaUsageProjection($meter, 20, 0, 0);
    }

    public static function invalidQuantities(): array
    {
        return [
            'negative limit' => [-1, 0, 0],
            'negative used' => [20, -1, 0],
            'negative increase' => [20, 0, -1],
            'negative all' => [-1, -1, -1],
        ];
    }

    #[DataProvider('invalidQuantities')]
    public function test_negative_counts_are_rejected(int $limit, int $used, int $increase): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantidade de franquia invalida.');
        new QuotaUsageProjection('leads', $limit, $used, $increase);
    }

    public function test_integer_overflow_is_rejected_without_wraparound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantidade de franquia excede limite inteiro.');
        new QuotaUsageProjection('storage_bytes', PHP_INT_MAX, PHP_INT_MAX, 1);
    }

    public function test_two_projections_are_independent_and_do_not_accumulate_consumption(): void
    {
        $before = new QuotaUsageProjection('leads', 20, 19, 1);
        $after = new QuotaUsageProjection('leads', 20, 19, 2);

        self::assertTrue($before->fitsWithinConfiguredLimit());
        self::assertFalse($after->fitsWithinConfiguredLimit());
        self::assertSame(19, $before->currentlyUsed);
        self::assertSame(19, $after->currentlyUsed);
        self::assertSame(0, $before->remainingAfter());

        // A caller cannot turn a calculated snapshot into committed usage.
        self::assertTrue((new \ReflectionClass(QuotaUsageProjection::class))->isReadOnly());
    }

    public function test_quantity_is_not_a_business_reservation_or_quota_authorization(): void
    {
        // Even when arithmetic fits, authorization, entitlement, durable
        // reservation, concurrency control and subscription state are absent.
        $projection = new QuotaUsageProjection('leads', 20, 0, 1);

        self::assertTrue($projection->fitsWithinConfiguredLimit());
        self::assertFalse(method_exists($projection, 'permit'));
        self::assertFalse(method_exists($projection, 'reserve'));
        self::assertFalse(method_exists($projection, 'commit'));
        self::assertFalse(method_exists($projection, 'activate'));
    }
}
