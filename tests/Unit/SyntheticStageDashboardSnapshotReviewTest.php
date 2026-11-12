<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticStageDashboardSnapshotReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticStageDashboardSnapshotReviewTest extends TestCase
{
    private static function rows(int $new, int $won = 0): array
    {
        return [
            ...array_fill(0, $new, ['stage' => 'new', 'claimed_value_cents' => 100]),
            ...array_fill(0, $won, ['stage' => 'won', 'claimed_value_cents' => 999999]),
        ];
    }

    public function test_same_complete_snapshots_show_bounded_ratio_but_never_authorize(): void
    {
        $rows = self::rows(3, 3);
        $review = (new SyntheticStageDashboardSnapshotReview())->inspect($rows, $rows);
        self::assertFalse($review['all_ratios_withheld']);
        self::assertSame([5000, 0, 0, 5000, 0], array_column($review['stages'], 'share_basis_points'));
        self::assertSame(['history_and_authorization_unverified'], $review['warning_codes']);
        self::assertFalse($review['authorized']);
        self::assertFalse($review['can_deliver']);
        self::assertFalse($review['can_export']);
        self::assertSame(0, $review['recognized_revenue_cents']);
    }

    public function test_a_small_delta_withholds_every_share_even_when_current_snapshot_is_complete(): void
    {
        $before = self::rows(3, 3);
        $after = self::rows(4, 3);
        $out = (new SyntheticStageDashboardSnapshotReview())->inspect($before, $after);
        self::assertTrue($out['all_ratios_withheld']);
        self::assertSame([null, null, null, null, null], array_column($out['stages'], 'share_basis_points'));
        self::assertContains('cross_snapshot_disclosure_risk', $out['warning_codes']);
        self::assertContains('small_cross_snapshot_delta', $out['cross_snapshot_risk_codes']);
        self::assertStringNotContainsString('999999', json_encode($out, JSON_THROW_ON_ERROR));
        self::assertSame(self::rows(3, 3), $before);
        self::assertSame(self::rows(4, 3), $after);
    }

    public function test_suppressed_and_empty_snapshots_withhold_ratios(): void
    {
        $out = (new SyntheticStageDashboardSnapshotReview())->inspect(self::rows(3), self::rows(3, 1));
        self::assertTrue($out['all_ratios_withheld']);
        self::assertSame([null, null, null, null, null], array_column($out['stages'], 'share_basis_points'));
        self::assertContains('suppression_or_empty_denominator', $out['warning_codes']);

        $empty = (new SyntheticStageDashboardSnapshotReview())->inspect([], []);
        self::assertTrue($empty['all_ratios_withheld']);
        self::assertFalse($empty['can_read_customers']);
    }

    public function test_invalid_inputs_fail_closed_without_echoing_sensitive_values(): void
    {
        $valid = self::rows(3);
        $bad = [
            [null, $valid, 3],
            [$valid, 'bad', 3],
            [$valid, $valid, '3'],
            [$valid, $valid, 1],
            [$valid, $valid, 6],
            [$valid, [['stage' => 'new', 'claimed_value_cents' => 1, 'email' => 'secret@example.test']], 3],
            [array_fill(0, 21, ['stage' => 'new', 'claimed_value_cents' => 1]), $valid, 3],
        ];
        foreach ($bad as [$earlier, $later, $k]) {
            try {
                (new SyntheticStageDashboardSnapshotReview())->inspect($earlier, $later, $k);
                self::fail('Unsafe dashboard input accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Painel sintetico comparativo invalido.', $e->getMessage());
                self::assertStringNotContainsString('secret@example.test', $e->getMessage());
            }
        }
    }

    public function test_output_contract_is_closed_metadata_only(): void
    {
        $out = (new SyntheticStageDashboardSnapshotReview())->inspect(self::rows(3), self::rows(3));
        self::assertSame([
            'stages', 'all_ratios_withheld', 'warning_codes', 'warning_count',
            'cross_snapshot_risk_codes', 'minimum_group_size',
            'recognized_revenue_cents', 'data_provenance_verified',
            'cross_query_history_verified', 'tenant_scope_verified',
            'privacy_policy_verified', 'informational_only', 'authorized',
            'can_read_customers', 'can_export', 'can_download', 'can_deliver', 'can_persist',
        ], array_keys($out));
        foreach (['data_provenance_verified', 'cross_query_history_verified',
            'tenant_scope_verified', 'privacy_policy_verified', 'authorized',
            'can_read_customers','can_export','can_download','can_deliver','can_persist'] as $key) {
            self::assertFalse($out[$key]);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['claimed_value_cents', 'email', 'phone', 'tenant_id', 'grand_total',
            'csv_preview_utf8', 'recipient_id'] as $key) {
            self::assertStringNotContainsString('"'.$key.'"', $json);
        }
    }
}
