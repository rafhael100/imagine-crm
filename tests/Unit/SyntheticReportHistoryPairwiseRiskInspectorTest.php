<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticReportHistoryPairwiseRiskInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticReportHistoryPairwiseRiskInspectorTest extends TestCase
{
    private static function counts(int $new = 0, int $won = 0): array
    {
        return ['new' => $new, 'qualified' => 0, 'proposal' => 0, 'won' => $won, 'lost' => 0];
    }

    public function test_detects_nonadjacent_small_change_over_entire_history(): void
    {
        $history = [self::counts(3), self::counts(8), self::counts(4)];
        $out = (new SyntheticReportHistoryPairwiseRiskInspector())->inspect($history);
        self::assertSame(3, $out['snapshot_count']);
        self::assertSame(3, $out['pair_count_checked']);
        self::assertSame([[0, 2]], $out['risk_pair_positions']);
        self::assertSame(1, $out['risk_pair_count']);
        self::assertContains('small_delta_across_history', $out['risk_codes']);
        self::assertFalse($out['complete_query_history_verified']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_export']);
        self::assertSame($history, [self::counts(3), self::counts(8), self::counts(4)]);
    }

    public function test_boundary_change_is_flagged_even_when_snapshots_are_not_adjacent(): void
    {
        $out = (new SyntheticReportHistoryPairwiseRiskInspector())->inspect([
            self::counts(0), self::counts(6), self::counts(2),
        ], 3);
        self::assertSame([[0, 2], [1, 2]], $out['risk_pair_positions']);
        self::assertContains('suppression_boundary_across_history', $out['risk_codes']);
        self::assertFalse($out['can_publish']);
        self::assertFalse($out['can_deliver']);
    }

    public function test_no_detected_small_pair_still_produces_no_release_permission(): void
    {
        $out = (new SyntheticReportHistoryPairwiseRiskInspector())->inspect([
            self::counts(5), self::counts(10), self::counts(15), self::counts(20),
        ]);
        self::assertSame(6, $out['pair_count_checked']);
        self::assertSame([], $out['risk_pair_positions']);
        self::assertSame(['complete_query_history_unverified'], $out['risk_codes']);
        self::assertFalse($out['recipient_permissions_verified']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['can_persist']);
    }

    public function test_all_eight_snapshots_are_bounded_to_twenty_eight_pairs(): void
    {
        $history = array_fill(0, 8, self::counts(3, 3));
        $out = (new SyntheticReportHistoryPairwiseRiskInspector())->inspect($history);
        self::assertSame(28, $out['pair_count_checked']);
        self::assertSame(0, $out['risk_pair_count']);
        self::assertSame($out, (new SyntheticReportHistoryPairwiseRiskInspector())->inspect($history));
    }

    public function test_malformed_oversize_or_private_snapshots_fail_closed(): void
    {
        $good = self::counts(3);
        $cases = [
            [null, 3], [[$good], 3], [array_fill(0, 9, $good), 3],
            [['bad', $good], 3], [[$good, ['new' => 3]], 3],
            [[$good, array_merge($good, ['email' => 'secret@example.test'])], 3],
            [[$good, array_replace($good, ['new' => -1])], 3],
            [[$good, array_replace($good, ['new' => 21])], 3],
            [[$good, $good], '3'], [[$good, $good], 1], [[$good, $good], 6],
            [['first' => $good, 'second' => $good], 3],
        ];
        foreach ($cases as [$snapshots, $k]) {
            try {
                (new SyntheticReportHistoryPairwiseRiskInspector())->inspect($snapshots, $k);
                self::fail('Unsafe query history accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Historico sintetico de relatorios invalido.', $e->getMessage());
                self::assertStringNotContainsString('secret@example.test', $e->getMessage());
            }
        }
    }

    public function test_output_is_anonymous_closed_and_deny_only(): void
    {
        $out = (new SyntheticReportHistoryPairwiseRiskInspector())->inspect([
            self::counts(3, 4), self::counts(4, 5),
        ]);
        self::assertSame([
            'snapshot_count', 'pair_count_checked', 'risk_pair_positions',
            'risk_pair_count', 'risk_codes', 'minimum_group_size',
            'complete_query_history_verified', 'tenant_scope_verified',
            'recipient_permissions_verified', 'privacy_policy_verified',
            'informational_only', 'authorized', 'can_publish', 'can_export',
            'can_deliver', 'can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        foreach (['complete_query_history_verified', 'tenant_scope_verified',
            'recipient_permissions_verified','privacy_policy_verified',
            'authorized','can_publish','can_export','can_deliver','can_persist'] as $key) {
            self::assertFalse($out[$key]);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['email', 'tenant_id', 'recipient', 'timestamp',
            'query_id', 'report_id', 'customer', 'count_values'] as $secret) {
            self::assertStringNotContainsString('"'.$secret.'"', $json);
        }
    }
}
