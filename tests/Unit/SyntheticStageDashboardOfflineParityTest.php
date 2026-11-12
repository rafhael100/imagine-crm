<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticStageDashboardSnapshotReview;
use PHPUnit\Framework\TestCase;

/**
 * F3-149: compare the shipped offline demonstration with actual Core PHP.
 * The page is an inert fixed visual, never a source of identity or access.
 */
final class SyntheticStageDashboardOfflineParityTest extends TestCase
{
    /** @return list<array{stage:string,claimed_value_cents:int}> */
    private static function rows(int $new, int $won = 0): array
    {
        return [
            ...array_fill(0, $new, ['stage' => 'new', 'claimed_value_cents' => 100]),
            ...array_fill(0, $won, ['stage' => 'won', 'claimed_value_cents' => 500]),
        ];
    }

    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-148-private-stage-dashboard/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    public function test_stable_visual_represents_actual_non_authorized_core_shares(): void
    {
        $html = self::html();
        $rows = self::rows(3, 3);
        $out = (new SyntheticStageDashboardSnapshotReview())->inspect($rows, $rows, 3);
        self::assertFalse($out['all_ratios_withheld']);
        self::assertSame([5000, 0, 0, 5000, 0], array_column($out['stages'], 'share_basis_points'));
        self::assertSame(2, substr_count($html, '<span class="pct">50%</span>'));
        self::assertSame(3, substr_count($html, '<span class="pct">0%</span>'));
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['can_deliver']);
    }

    public function test_one_row_delta_is_withheld_in_core_and_visual(): void
    {
        $html = self::html();
        $out = (new SyntheticStageDashboardSnapshotReview())
            ->inspect(self::rows(3, 3), self::rows(4, 3), 3);
        self::assertTrue($out['all_ratios_withheld']);
        self::assertSame([null, null, null, null, null], array_column($out['stages'], 'share_basis_points'));
        self::assertContains('cross_snapshot_disclosure_risk', $out['warning_codes']);
        self::assertContains('small_cross_snapshot_delta', $out['cross_snapshot_risk_codes']);
        self::assertStringContainsString('small_cross_snapshot_delta', $html);
        self::assertStringContainsString('Percentuais</span><strong>Ocultos', $html);
        self::assertFalse($out['can_download']);
    }

    public function test_hidden_group_withholds_all_ratios_in_core_and_visual(): void
    {
        $html = self::html();
        $out = (new SyntheticStageDashboardSnapshotReview())
            ->inspect(self::rows(3), self::rows(3, 1), 3);
        self::assertTrue($out['all_ratios_withheld']);
        self::assertSame([null, null, null, null, null], array_column($out['stages'], 'share_basis_points'));
        self::assertContains('suppression_or_empty_denominator', $out['warning_codes']);
        self::assertStringContainsString('suppression_or_empty_denominator', $html);
        self::assertFalse($out['can_read_customers']);
        self::assertFalse($out['can_persist']);
    }

    public function test_no_operational_rights_can_be_inferred_from_any_demo_case(): void
    {
        $preview = new SyntheticStageDashboardSnapshotReview();
        foreach ([
            [self::rows(3, 3), self::rows(3, 3)],
            [self::rows(3, 3), self::rows(4, 3)],
            [self::rows(3), self::rows(3, 1)],
        ] as [$before, $after]) {
            $out = $preview->inspect($before, $after, 3);
            self::assertTrue($out['informational_only']);
            self::assertSame(0, $out['recognized_revenue_cents']);
            foreach (['authorized', 'can_read_customers', 'can_export',
                'can_download', 'can_deliver', 'can_persist',
                'tenant_scope_verified', 'privacy_policy_verified'] as $key) {
                self::assertFalse($out[$key], $key);
            }
        }
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        self::assertIsString($routes);
        self::assertStringNotContainsString('SyntheticStageDashboardSnapshotReview', $routes);
    }
}
