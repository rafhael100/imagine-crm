<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadScoringBands;
use App\Core\Leads\LeadTemperature;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LeadScoringBandsTest extends TestCase
{
    public static function standardCases(): array
    {
        return [
            'minimum cold' => [0, LeadTemperature::Cold],
            'middle cold' => [20, LeadTemperature::Cold],
            'cold upper bound' => [39, LeadTemperature::Cold],
            'warm lower bound' => [40, LeadTemperature::Warm],
            'middle warm' => [55, LeadTemperature::Warm],
            'warm upper bound' => [69, LeadTemperature::Warm],
            'hot lower bound' => [70, LeadTemperature::Hot],
            'middle hot' => [85, LeadTemperature::Hot],
            'maximum hot' => [100, LeadTemperature::Hot],
        ];
    }

    #[DataProvider('standardCases')]
    public function test_default_scoring_bands_use_documented_ranges(int $score, LeadTemperature $expected): void
    {
        self::assertSame($expected, (new LeadScoringBands())->classify($score));
    }

    public static function customCases(): array
    {
        return [
            'custom cold lower' => [0, LeadTemperature::Cold],
            'custom cold upper' => [19, LeadTemperature::Cold],
            'custom warm lower' => [20, LeadTemperature::Warm],
            'custom warm upper' => [79, LeadTemperature::Warm],
            'custom hot lower' => [80, LeadTemperature::Hot],
            'custom hot upper' => [100, LeadTemperature::Hot],
        ];
    }

    #[DataProvider('customCases')]
    public function test_band_boundaries_can_be_customized_without_global_state(int $score, LeadTemperature $expected): void
    {
        self::assertSame($expected, (new LeadScoringBands(19, 79))->classify($score));
    }

    public static function invalidThresholds(): array
    {
        return [
            'negative cold max' => [-1, 69],
            'same thresholds' => [39, 39],
            'inverted thresholds' => [80, 20],
            'warm max at 100' => [39, 100],
            'cold max at 100' => [100, 101],
        ];
    }

    #[DataProvider('invalidThresholds')]
    public function test_invalid_thresholds_fail_closed(int $coldMax, int $warmMax): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LeadScoringBands($coldMax, $warmMax);
    }

    public static function invalidScores(): array
    {
        return [['negative' => -1], ['above maximum' => 101], ['very negative' => -10000], ['very high' => 10000]];
    }

    #[DataProvider('invalidScores')]
    public function test_scores_outside_zero_to_one_hundred_are_rejected(int $score): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LeadScoringBands())->classify($score);
    }

    public function test_policy_is_immutable_and_has_no_customer_or_tenant_state(): void
    {
        $default = new LeadScoringBands();
        $custom = new LeadScoringBands(10, 50);

        self::assertSame(LeadTemperature::Cold, $default->classify(20));
        self::assertSame(LeadTemperature::Warm, $custom->classify(20));
        self::assertSame(39, $default->coldMax);
        self::assertSame(69, $default->warmMax);
    }
}
