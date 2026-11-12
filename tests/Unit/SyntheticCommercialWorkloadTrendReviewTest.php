<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticCommercialWorkloadTrendReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCommercialWorkloadTrendReviewTest extends TestCase
{
    private static function scenario(int $capacity, string $due): array
    {
        return [
            'agents' => [[
                'enabled' => true,
                'has_required_skill' => true,
                'open_leads' => 0,
                'capacity' => $capacity,
            ]],
            'lead_states' => ['unassigned','unassigned'],
            'start_cursor' => 0,
            'task_histories' => [[[
                'revision' => 1,
                'action' => 'create',
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
                'due_at_utc' => $due,
                'priority' => 'urgent',
                'reason_code' => null,
            ]]],
            'observed_at_utc' => '2026-11-10T12:00:00Z',
        ];
    }

    public function test_detects_worsening_capacity_and_overdue_pressure(): void
    {
        $before = self::scenario(2, '2026-11-11T12:00:00Z');
        $after = self::scenario(0, '2026-11-09T12:00:00Z');
        $out = (new SyntheticCommercialWorkloadTrendReview())->inspect($before, $after);
        self::assertSame(2, $out['fictional_capacity_shortfall_delta']);
        self::assertSame(1, $out['fictional_overdue_task_delta']);
        self::assertSame(0, $out['fictional_open_task_delta']);
        self::assertSame('increased_pressure', $out['pressure_trend']);
        self::assertSame([
            'capacity_shortfall_increased_claim',
            'overdue_backlog_increased_claim',
            'snapshot_cohort_and_identity_unverified',
        ], $out['warning_codes']);
        self::assertFalse($out['can_assign']);
        self::assertFalse($out['can_notify']);
        self::assertSame($before, self::scenario(2, '2026-11-11T12:00:00Z'));
        self::assertSame($after, self::scenario(0, '2026-11-09T12:00:00Z'));
    }

    public function test_fictional_improvement_does_not_enable_actions(): void
    {
        $out = (new SyntheticCommercialWorkloadTrendReview())->inspect(
            self::scenario(0, '2026-11-09T12:00:00Z'),
            self::scenario(2, '2026-11-11T12:00:00Z'),
        );
        self::assertSame(-2, $out['fictional_capacity_shortfall_delta']);
        self::assertSame(-1, $out['fictional_overdue_task_delta']);
        self::assertSame('reduced_pressure', $out['pressure_trend']);
        self::assertFalse($out['snapshot_cohort_verified']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_reschedule']);
    }

    public function test_unchanged_composition_remains_unverified(): void
    {
        $scenario = self::scenario(1, '2026-11-11T12:00:00Z');
        $out = (new SyntheticCommercialWorkloadTrendReview())->inspect($scenario, $scenario);
        self::assertSame('no_pressure_change', $out['pressure_trend']);
        self::assertSame(['snapshot_cohort_and_identity_unverified'], $out['warning_codes']);
        self::assertFalse($out['can_persist']);
    }

    public function test_mixed_trend_is_not_falsely_classified_as_improvement(): void
    {
        $out = (new SyntheticCommercialWorkloadTrendReview())->inspect(
            self::scenario(2, '2026-11-09T12:00:00Z'),
            self::scenario(0, '2026-11-11T12:00:00Z'),
        );
        self::assertSame('mixed', $out['pressure_trend']);
        self::assertSame(2, $out['fictional_capacity_shortfall_delta']);
        self::assertSame(-1, $out['fictional_overdue_task_delta']);
    }

    public function test_invalid_nested_content_fails_closed_without_pii_leak(): void
    {
        $valid = self::scenario(1, '2026-11-11T12:00:00Z');
        $bad = [
            [null, $valid],
            [$valid, 'bad'],
            [$valid, array_merge($valid, ['email' => 'secret@example.test'])],
            [$valid, array_replace($valid, ['task_histories' => []])],
            [$valid, array_replace($valid, ['agents' => [[
                'enabled' => true,'has_required_skill' => true,
                'open_leads' => 0,'capacity' => 40,
            ]]])],
        ];
        foreach ($bad as [$earlier, $later]) {
            try {
                (new SyntheticCommercialWorkloadTrendReview())->inspect($earlier, $later);
                self::fail('Unsafe workload trend accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Tendencia sintetica comercial invalida.', $error->getMessage());
                self::assertStringNotContainsString('secret@example.test', $error->getMessage());
            }
        }
    }

    public function test_output_has_closed_metadata_and_no_permissions(): void
    {
        $out = (new SyntheticCommercialWorkloadTrendReview())->inspect(
            self::scenario(1, '2026-11-11T12:00:00Z'),
            self::scenario(0, '2026-11-09T12:00:00Z'),
        );
        self::assertSame([
            'fictional_capacity_shortfall_delta', 'fictional_overdue_task_delta',
            'fictional_due_now_task_delta', 'fictional_open_task_delta',
            'pressure_trend', 'warning_codes', 'warning_count',
            'snapshot_cohort_verified', 'staff_identity_verified',
            'task_identity_verified', 'tenant_scope_verified',
            'business_policy_verified', 'informational_only', 'authorized',
            'can_assign', 'can_reschedule', 'can_notify', 'can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        foreach (['snapshot_cohort_verified', 'staff_identity_verified',
            'task_identity_verified', 'tenant_scope_verified',
            'business_policy_verified', 'authorized', 'can_assign',
            'can_reschedule', 'can_notify','can_persist'] as $key) {
            self::assertFalse($out[$key]);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"tenant_id"', '"lead_id"', '"task_id"', '"email"',
            '"event_reference"', '2026-11-', '"observed_at_utc"'] as $hidden) {
            self::assertStringNotContainsString($hidden, $json);
        }
    }
}
