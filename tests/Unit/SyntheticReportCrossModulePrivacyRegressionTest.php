<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticReportDifferencingRiskInspector;
use App\Core\Sales\SyntheticReportDeliveryCompositeInspector;
use App\Core\Sales\SyntheticStageAggregateCsvPreview;
use PHPUnit\Framework\TestCase;

/**
 * F3-144: cross-module regression that a synthetic aggregate, snapshot
 * comparison and recipient preflight can never compose into delivery rights.
 */
final class SyntheticReportCrossModulePrivacyRegressionTest extends TestCase
{
    /** @return list<array{stage:string,claimed_value_cents:int}> */
    private static function earlier(): array
    {
        return [
            ['stage' => 'new', 'claimed_value_cents' => 100],
            ['stage' => 'new', 'claimed_value_cents' => 200],
            ['stage' => 'new', 'claimed_value_cents' => 300],
            ['stage' => 'won', 'claimed_value_cents' => 999999],
        ];
    }

    /** @return list<array{stage:string,claimed_value_cents:int}> */
    private static function later(): array
    {
        return [
            ...self::earlier(),
            ['stage' => 'won', 'claimed_value_cents' => 888888],
        ];
    }

    /** @return array<string,int> */
    private static function counts(array $rows): array
    {
        $counts = ['new' => 0, 'qualified' => 0, 'proposal' => 0, 'won' => 0, 'lost' => 0];
        foreach ($rows as $row) {
            ++$counts[$row['stage']];
        }
        return $counts;
    }

    public function test_report_composition_retains_deny_even_with_positive_recipient_claims(): void
    {
        $earlier = self::earlier();
        $later = self::later();
        $snapshot = (new SyntheticReportDifferencingRiskInspector())
            ->inspect(self::counts($earlier), self::counts($later), 3);
        self::assertSame([3], $snapshot['small_difference_stage_positions']);
        self::assertContains('small_cross_snapshot_delta', $snapshot['risk_codes']);

        $preview = (new SyntheticStageAggregateCsvPreview())->preview($later, 3);
        self::assertSame("stage,count,claimed_sum_cents\r\nnew,3,600\r\n", $preview['csv_preview_utf8']);
        self::assertSame(1, $preview['suppressed_bucket_count']);

        $report = (new SyntheticReportDeliveryCompositeInspector())->inspect(
            $later,
            3,
            [
                'at_utc' => '2026-11-01T10:00:00Z',
                'recipient_access' => true,
                'policy_revision' => 1,
                'requested_fields' => ['stage', 'claimed_value_cents'],
            ],
            [
                'at_utc' => '2026-11-02T10:00:00Z',
                'recipient_access' => true,
                'policy_revision' => 1,
                'report_state' => 'ready',
            ],
        );

        self::assertFalse($report['csv_text_returned']);
        self::assertSame(0, $report['recognized_revenue_cents']);
        self::assertSame(1, $report['suppressed_bucket_count']);
        foreach ([$snapshot, $preview, $report] as $output) {
            self::assertFalse($output['authorized']);
            self::assertFalse($output['can_export']);
            self::assertFalse($output['can_persist']);
        }
        self::assertFalse($snapshot['can_deliver']);
        self::assertFalse($report['can_deliver']);
        self::assertFalse($snapshot['can_publish']);
        self::assertFalse($preview['can_download']);
        self::assertFalse($report['can_download']);

        $combined = json_encode(['comparison' => $snapshot, 'delivery' => $report], JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('999999', $combined);
        self::assertStringNotContainsString('888888', $combined);
        self::assertStringNotContainsString('new,3,600', $combined);
        self::assertStringNotContainsString('csv_preview_utf8', $combined);
        self::assertSame(self::earlier(), $earlier);
        self::assertSame(self::later(), $later);
    }

    public function test_unflagged_counter_comparison_still_cannot_override_report_denials(): void
    {
        $rows = [
            ['stage' => 'new', 'claimed_value_cents' => 1],
            ['stage' => 'new', 'claimed_value_cents' => 2],
            ['stage' => 'new', 'claimed_value_cents' => 3],
        ];
        $risk = (new SyntheticReportDifferencingRiskInspector())
            ->inspect(self::counts($rows), self::counts($rows), 3);
        self::assertSame(['cross_query_history_unverified'], $risk['risk_codes']);
        self::assertFalse($risk['authorized']);
        self::assertFalse($risk['can_publish']);

        $csv = (new SyntheticStageAggregateCsvPreview())->preview($rows);
        self::assertSame(0, $csv['suppressed_bucket_count']);
        self::assertFalse($csv['can_export']);
        self::assertFalse($csv['recipient_permissions_verified']);
    }

    public function test_no_new_public_routes_reference_the_three_lab_components(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        self::assertIsString($routes);
        foreach ([
            'SyntheticReportDifferencingRiskInspector',
            'SyntheticStageAggregateCsvPreview',
            'SyntheticReportDeliveryCompositeInspector',
        ] as $class) {
            self::assertStringNotContainsString($class, $routes);
        }
    }
}
