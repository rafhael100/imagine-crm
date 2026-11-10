<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticQuotaImpactPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticQuotaImpactPreviewTest extends TestCase
{
    public function test_five_impact_states_are_distinct_and_never_grant_quota_or_billing_rights(): void
    {
        $readout = (new SyntheticQuotaImpactPreview([
            new QuotaUsageProjection('leads_in_range', 20, 4, 10),
            new QuotaUsageProjection('leads_at_limit', 20, 4, 16),
            new QuotaUsageProjection('leads_would_exceed', 20, 4, 17),
            new QuotaUsageProjection('leads_already_over', 20, 21, 0),
            new QuotaUsageProjection('leads_zero_capacity', 0, 0, 0),
        ]))->preview();

        self::assertSame([
            'in_range' => 1,
            'at_limit' => 1,
            'would_exceed' => 1,
            'already_over' => 1,
            'zero_capacity' => 1,
        ], $readout['impact_counts']);
        self::assertSame([
            ['meter' => 'leads_in_range', 'limit' => 20, 'used' => 4, 'requested' => 10,
                'projected' => 14, 'remaining_before' => 16, 'remaining_after' => 6,
                'excess_before' => 0, 'excess_after' => 0, 'impact' => 'in_range'],
            ['meter' => 'leads_at_limit', 'limit' => 20, 'used' => 4, 'requested' => 16,
                'projected' => 20, 'remaining_before' => 16, 'remaining_after' => 0,
                'excess_before' => 0, 'excess_after' => 0, 'impact' => 'at_limit'],
            ['meter' => 'leads_would_exceed', 'limit' => 20, 'used' => 4, 'requested' => 17,
                'projected' => 21, 'remaining_before' => 16, 'remaining_after' => 0,
                'excess_before' => 0, 'excess_after' => 1, 'impact' => 'would_exceed'],
            ['meter' => 'leads_already_over', 'limit' => 20, 'used' => 21, 'requested' => 0,
                'projected' => 21, 'remaining_before' => 0, 'remaining_after' => 0,
                'excess_before' => 1, 'excess_after' => 1, 'impact' => 'already_over'],
            ['meter' => 'leads_zero_capacity', 'limit' => 0, 'used' => 0, 'requested' => 0,
                'projected' => 0, 'remaining_before' => 0, 'remaining_after' => 0,
                'excess_before' => 0, 'excess_after' => 0, 'impact' => 'zero_capacity'],
        ], $readout['rows']);
        self::assertSame(count($readout['rows']), array_sum($readout['impact_counts']));
        self::assertTrue($readout['informational_only']);
        self::assertSame(['rows', 'impact_counts', 'informational_only', 'authorized', 'can_reserve', 'can_increase', 'can_charge'], array_keys($readout));
        foreach (['authorized','can_reserve','can_increase','can_charge'] as $capability) {
            self::assertFalse($readout[$capability]);
        }
    }

    public function test_zero_is_never_unlimited_and_excess_is_descriptive_not_a_charge(): void
    {
        $preview = (new SyntheticQuotaImpactPreview([
            new QuotaUsageProjection('zero_requested', 0, 0, 1),
            new QuotaUsageProjection('zero_used', 0, 2, 1),
            new QuotaUsageProjection('exact_big', PHP_INT_MAX, PHP_INT_MAX, 0),
        ]))->preview();

        self::assertSame('would_exceed', $preview['rows'][0]['impact']);
        self::assertSame(1, $preview['rows'][0]['excess_after']);
        self::assertSame('already_over', $preview['rows'][1]['impact']);
        self::assertSame(2, $preview['rows'][1]['excess_before']);
        self::assertSame(3, $preview['rows'][1]['excess_after']);
        self::assertSame('at_limit', $preview['rows'][2]['impact']);
        self::assertSame(PHP_INT_MAX, $preview['rows'][2]['projected']);
        self::assertFalse($preview['can_increase']);
        self::assertFalse($preview['can_charge']);
    }

    public function test_empty_duplicate_forged_and_too_many_projections_fail_without_reflection(): void
    {
        $valid = new QuotaUsageProjection('leads', 20, 1, 0);
        $cases = [
            [],
            ['named' => $valid],
            [$valid, $valid],
            [$valid, new QuotaUsageProjection('leads', 21, 1, 0)],
            ['leads'],
            [['meter' => 'leads', 'limit' => 20, 'used' => 1]],
            array_fill(0, 13, $valid),
        ];
        foreach ($cases as $case) {
            try {
                new SyntheticQuotaImpactPreview($case);
                self::fail('Invalid preview collection was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Projecao sintetica de franquia invalida.', $e->getMessage());
                self::assertStringNotContainsString('leads', $e->getMessage());
            }
        }

        $this->expectException(InvalidArgumentException::class);
        new QuotaUsageProjection('overflow', PHP_INT_MAX, PHP_INT_MAX, 1);
    }

    public function test_all_twelve_distinct_meters_are_bounded_and_ordered(): void
    {
        $meters = [];
        for ($i = 0; $i < 12; ++$i) {
            $meters[] = new QuotaUsageProjection('fixture_'.$i, 12, $i, 1);
        }
        $preview = (new SyntheticQuotaImpactPreview($meters))->preview();
        self::assertSame(12, count($preview['rows']));
        self::assertSame(11, $preview['impact_counts']['in_range']);
        self::assertSame(1, $preview['impact_counts']['at_limit']);
        self::assertSame('fixture_0', $preview['rows'][0]['meter']);
        self::assertSame('fixture_11', $preview['rows'][11]['meter']);
        self::assertFalse($preview['authorized']);
    }

    public function test_no_endpoint_or_side_effect_method_is_exposed_by_what_if_lab(): void
    {
        $lab = new SyntheticQuotaImpactPreview([new QuotaUsageProjection('leads', 20, 4, 0)]);
        self::assertTrue((new \ReflectionClass(SyntheticQuotaImpactPreview::class))->isReadOnly());
        self::assertSame(['preview'], array_values(array_filter(
            get_class_methods($lab),
            static fn (string $method): bool => $method !== '__construct',
        )));
        foreach (['allow', 'authorize', 'canCreate', 'reserve', 'commit', 'charge',
            'bill', 'notify', 'persist', 'save', 'enforce', 'grant', 'activate', 'delete'] as $method) {
            self::assertFalse(method_exists($lab, $method));
        }
        $out = $lab->preview();
        self::assertSame(4, $out['rows'][0]['used']);
        self::assertSame(4, $out['rows'][0]['projected']);
        self::assertFalse($out['can_reserve']);
        self::assertFalse($out['can_increase']);
    }
}
