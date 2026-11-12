<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticCommercialWorkloadInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCommercialWorkloadInspectorTest extends TestCase
{
    private static function task(string $dueAt): array
    {
        return [[
            'revision' => 1,
            'action' => 'create',
            'occurred_at_utc' => '2026-11-01T10:00:00Z',
            'due_at_utc' => $dueAt,
            'priority' => 'high',
            'reason_code' => null,
        ]];
    }

    private static function scenario(): array
    {
        return [
            'agents' => [[
                'enabled' => true,
                'has_required_skill' => true,
                'open_leads' => 0,
                'capacity' => 1,
            ]],
            'lead_states' => ['unassigned', 'unassigned', 'engaged'],
            'start_cursor' => 0,
            'task_histories' => [
                self::task('2026-11-09T12:00:00Z'),
                self::task('2026-11-10T12:00:00Z'),
            ],
            'observed_at_utc' => '2026-11-10T12:00:00Z',
        ];
    }

    public function test_capacity_pressure_and_overdue_tasks_are_reported_without_executing_actions(): void
    {
        $scenario = self::scenario();
        $out = (new SyntheticCommercialWorkloadInspector())->inspect($scenario);
        self::assertSame(1, $out['fictional_agent_slots']);
        self::assertSame(3, $out['fictional_leads_reviewed']);
        self::assertSame(1, $out['fictional_assignments_planned']);
        self::assertSame(1, $out['fictional_leads_waiting_for_capacity']);
        self::assertSame(2, $out['fictional_tasks_reviewed']);
        self::assertSame(1, $out['fictional_overdue_tasks']);
        self::assertSame(1, $out['fictional_due_now_tasks']);
        self::assertSame([
            'fictional_lead_capacity_shortfall',
            'fictional_overdue_tasks',
            'fictional_tasks_due_now',
            'cross_module_identity_unverified',
        ], $out['workload_warning_codes']);
        self::assertFalse($out['can_assign']);
        self::assertFalse($out['can_create_task']);
        self::assertFalse($out['can_notify']);
        self::assertSame($scenario, self::scenario());
        self::assertSame($out, (new SyntheticCommercialWorkloadInspector())->inspect($scenario));
    }

    public function test_healthy_fictional_capacity_does_not_imply_privileges(): void
    {
        $scenario = self::scenario();
        $scenario['agents'][0]['capacity'] = 2;
        $scenario['task_histories'] = [self::task('2026-11-11T12:00:00Z')];
        $out = (new SyntheticCommercialWorkloadInspector())->inspect($scenario);
        self::assertSame(0, $out['fictional_leads_waiting_for_capacity']);
        self::assertSame(0, $out['fictional_overdue_tasks']);
        self::assertSame(['cross_module_identity_unverified'], $out['workload_warning_codes']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_persist']);
    }

    public function test_invalid_nested_fields_and_injected_private_data_fail_closed(): void
    {
        $good = self::scenario();
        $cases = [
            null,
            array_merge($good, ['email' => 'secret@example.test']),
            array_replace($good, ['lead_states' => ['wrong']]),
            array_replace($good, ['start_cursor' => '0']),
            array_replace($good, ['task_histories' => []]),
            array_replace($good, ['task_histories' => [[[
                'revision' => 1, 'action' => 'create',
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
                'due_at_utc' => '2026-11-11T12:00:00Z',
                'priority' => 'high', 'reason_code' => null,
                'email' => 'secret@example.test',
            ]]]]),
            array_replace($good, ['observed_at_utc' => '2026-02-30T12:00:00Z']),
        ];
        foreach ($cases as $bad) {
            try {
                (new SyntheticCommercialWorkloadInspector())->inspect($bad);
                self::fail('Invalid cross-module workload accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Carga comercial sintetica invalida.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_metadata_contract_has_no_ids_or_rights(): void
    {
        $out = (new SyntheticCommercialWorkloadInspector())->inspect(self::scenario());
        self::assertSame([
            'fictional_agent_slots', 'fictional_leads_reviewed',
            'fictional_assignments_planned', 'fictional_leads_waiting_for_capacity',
            'fictional_tasks_reviewed', 'fictional_open_tasks',
            'fictional_overdue_tasks', 'fictional_due_now_tasks',
            'fictional_closed_tasks', 'workload_warning_codes',
            'workload_warning_count', 'staff_identity_verified',
            'task_identity_verified', 'tenant_scope_verified',
            'capacity_policy_verified', 'sla_policy_verified',
            'informational_only', 'authorized', 'can_assign',
            'can_create_task', 'can_update_task', 'can_notify', 'can_persist',
        ], array_keys($out));
        foreach (['staff_identity_verified','task_identity_verified',
            'tenant_scope_verified','capacity_policy_verified','sla_policy_verified',
            'authorized','can_assign','can_create_task','can_update_task',
            'can_notify','can_persist'] as $key) {
            self::assertFalse($out[$key]);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"lead_id"', '"task_id"', '"email"', '"tenant_id"',
            '2026-11-', '"assignment_slots"', '"ordered_open_positions"'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
        self::assertTrue($out['informational_only']);
    }
}
