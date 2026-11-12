<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticReportDifferencingRiskInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticReportDifferencingRiskInspectorTest extends TestCase
{
    private static function counts(int $new = 0, int $qualified = 0): array
    {
        return [
            'new' => $new,
            'qualified' => $qualified,
            'proposal' => 0,
            'won' => 0,
            'lost' => 0,
        ];
    }

    public function test_detects_singleton_delta_and_visibility_change_without_returning_counts(): void
    {
        $before = self::counts(2, 5);
        $after = self::counts(3, 4);
        $output = (new SyntheticReportDifferencingRiskInspector())->inspect($before, $after);
        self::assertSame([0, 1], $output['small_difference_stage_positions']);
        self::assertSame([0], $output['visibility_change_stage_positions']);
        self::assertSame([1], $output['shrunk_stage_positions']);
        self::assertSame([
            'small_cross_snapshot_delta',
            'suppression_boundary_changed',
            'bucket_shrank',
            'cross_query_history_unverified',
        ], $output['risk_codes']);
        self::assertSame(0, substr_count(json_encode($output, JSON_THROW_ON_ERROR), '"email"'));
        self::assertSame($before, self::counts(2, 5));
        self::assertFalse($output['can_publish']);
        self::assertFalse($output['can_export']);
        self::assertFalse($output['can_deliver']);
        self::assertFalse($output['can_persist']);
    }

    public function test_equal_or_large_changes_do_not_create_permission(): void
    {
        $inspector = new SyntheticReportDifferencingRiskInspector();
        $equal = $inspector->inspect(self::counts(3), self::counts(3));
        self::assertSame(['cross_query_history_unverified'], $equal['risk_codes']);
        self::assertFalse($equal['authorized']);
        $large = $inspector->inspect(self::counts(0), self::counts(5));
        self::assertSame([], $large['small_difference_stage_positions']);
        self::assertSame([0], $large['visibility_change_stage_positions']);
        self::assertFalse($large['privacy_policy_verified']);
        self::assertSame($large, $inspector->inspect(self::counts(0), self::counts(5)));
    }

    public function test_invalid_shapes_pii_unknown_stages_and_thresholds_fail_closed(): void
    {
        $good = self::counts(3);
        $bad = [
            [[], $good, 3],
            [$good, ['new' => 3], 3],
            [$good + ['email' => 'secret@example.test'], $good, 3],
            [array_replace($good, ['new' => '3']), $good, 3],
            [array_replace($good, ['new' => -1]), $good, 3],
            [array_replace($good, ['new' => 21]), $good, 3],
            [$good, $good, 1],
            [$good, $good, 6],
            [$good, $good, '3'],
            [$good, $good, 3.0],
        ];
        foreach ($bad as [$before, $after, $minimum]) {
            try {
                (new SyntheticReportDifferencingRiskInspector())->inspect($before, $after, $minimum);
                self::fail('Invalid synthetic report accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Comparacao sintetica de relatorios invalida.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_return_contract_contains_only_positions_codes_and_denials(): void
    {
        $output = (new SyntheticReportDifferencingRiskInspector())->inspect(self::counts(), self::counts());
        self::assertSame([
            'small_difference_stage_positions', 'visibility_change_stage_positions',
            'shrunk_stage_positions', 'risk_codes', 'risk_count',
            'minimum_group_size', 'cross_query_history_verified',
            'tenant_scope_verified', 'privacy_policy_verified',
            'informational_only', 'authorized', 'can_publish',
            'can_export', 'can_deliver', 'can_persist',
        ], array_keys($output));
        self::assertTrue($output['informational_only']);
        foreach (['cross_query_history_verified', 'tenant_scope_verified', 'privacy_policy_verified'] as $key) {
            self::assertFalse($output[$key]);
        }
    }
}
