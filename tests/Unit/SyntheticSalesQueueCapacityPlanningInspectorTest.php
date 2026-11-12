<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticSalesQueueCapacityPlanningInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSalesQueueCapacityPlanningInspectorTest extends TestCase
{
    private static function scenario(): array
    {
        $blank = [
            'profile_complete'=>false, 'recent_engagement'=>false,
            'requested_quote'=>false, 'scheduled_demo'=>false,
            'stale_response'=>false,
        ];
        $medium = $blank; $medium['profile_complete'] = true;
        $high = $blank; $high['requested_quote'] = true;
        $low = $blank; $low['stale_response'] = true;

        return [
            'lead_batch' => [
                'agents' => [
                    ['enabled'=>true,'has_required_skill'=>true,'open_leads'=>0,'capacity'=>1],
                    ['enabled'=>true,'has_required_skill'=>true,'open_leads'=>0,'capacity'=>0],
                ],
                'leads' => [
                    ['state'=>'unassigned','signals'=>$medium],
                    ['state'=>'unassigned','signals'=>$high],
                    ['state'=>'unassigned','signals'=>$low],
                    ['state'=>'engaged','signals'=>$high],
                ],
                'weights' => [
                    'profile_complete'=>20,'recent_engagement'=>10,
                    'requested_quote'=>30,'scheduled_demo'=>25,'stale_response'=>-20,
                ],
                'base_score'=>20,
                'start_cursor'=>0,
            ],
            'task_histories' => [
                self::task('2026-11-09T12:00:00Z', 'urgent'),
                self::task('2026-11-10T12:00:00Z', 'normal'),
            ],
            'observed_at_utc'=>'2026-11-10T12:00:00Z',
        ];
    }

    private static function task(string $due, string $priority): array
    {
        return [[
            'revision'=>1,'action'=>'create',
            'occurred_at_utc'=>'2026-11-01T10:00:00Z',
            'due_at_utc'=>$due,
            'priority'=>$priority,
            'reason_code'=>null,
        ]];
    }

    public function test_capacity_relieves_lead_backlog_but_does_not_erase_overdue_tasks(): void
    {
        $scenario = self::scenario();
        $original = $scenario;
        $out = (new SyntheticSalesQueueCapacityPlanningInspector())->inspect($scenario, [0, 2]);
        self::assertSame(4, $out['fictional_leads_reviewed']);
        self::assertSame(2, $out['fictional_tasks_reviewed']);
        self::assertSame(2, $out['fictional_extra_capacity_claimed']);
        self::assertSame(1, $out['baseline_leads_planned']);
        self::assertSame(3, $out['scenario_leads_planned']);
        self::assertSame(0, $out['scenario_leads_waiting']);
        self::assertSame([0, 2], $out['newly_planned_lead_positions']);
        self::assertSame([], $out['remaining_waiting_lead_positions']);
        self::assertSame(1, $out['fictional_overdue_tasks']);
        self::assertSame(1, $out['fictional_due_now_tasks']);
        self::assertSame([0, 1], $out['task_priority_positions']);
        self::assertSame([
            'fictional_overdue_task_backlog_remains',
            'fictional_task_due_now',
            'cross_module_scope_and_identity_unverified',
        ], $out['warning_codes']);
        self::assertSame($original, $scenario);
        self::assertSame($out, (new SyntheticSalesQueueCapacityPlanningInspector())->inspect($scenario, [0, 2]));
    }

    public function test_no_added_capacity_warns_of_lead_backlog_with_empty_task_queue(): void
    {
        $scenario = self::scenario();
        $scenario['task_histories'] = [];
        $out = (new SyntheticSalesQueueCapacityPlanningInspector())->inspect($scenario, [0, 0]);
        self::assertSame(2, $out['scenario_leads_waiting']);
        self::assertSame([0, 2], $out['remaining_waiting_lead_positions']);
        self::assertSame(0, $out['fictional_open_tasks']);
        self::assertSame([], $out['task_priority_positions']);
        self::assertSame([
            'fictional_lead_backlog_remains',
            'cross_module_scope_and_identity_unverified',
        ], $out['warning_codes']);
        self::assertFalse($out['can_assign']);
        self::assertFalse($out['can_persist']);
    }

    public function test_reallocation_detected_with_no_claim_of_reassignment_authority(): void
    {
        $scenario = self::scenario();
        $scenario['lead_batch']['agents'][1]['capacity'] = 3;
        $out = (new SyntheticSalesQueueCapacityPlanningInspector())->inspect($scenario, [1, 0]);
        self::assertSame([2], $out['changed_slot_lead_positions']);
        self::assertContains('fictional_assignment_slot_reorder', $out['warning_codes']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_reassign']);
    }

    public function test_nested_invalid_data_and_excess_capacity_fail_closed(): void
    {
        $good = self::scenario();
        $cases = [
            [$good, [0]],
            [$good, [1, -1]],
            [array_merge($good, ['email'=>'secret@example.test']), [0, 2]],
            [array_replace($good, ['task_histories'=>[[
                ['revision'=>1,'action'=>'create',
                 'occurred_at_utc'=>'2026-11-01T10:00:00Z',
                 'due_at_utc'=>'2026-11-09T12:00:00Z',
                 'priority'=>'high','reason_code'=>null,
                 'task_id'=>'secret@example.test'],
            ]]]), [0, 2]],
            [array_replace($good, ['observed_at_utc'=>'2026-02-30T12:00:00Z']), [0, 2]],
            [array_replace($good, ['lead_batch'=>null]), [0, 2]],
        ];
        foreach ($cases as [$scenario, $extras]) {
            try {
                (new SyntheticSalesQueueCapacityPlanningInspector())->inspect($scenario, $extras);
                self::fail('Unsafe sales planning scenario accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Planejamento sintetico de fila comercial invalido.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_closed_output_is_anonymous_deny_only(): void
    {
        $out = (new SyntheticSalesQueueCapacityPlanningInspector())->inspect(self::scenario(), [0, 2]);
        self::assertSame([
            'fictional_leads_reviewed','fictional_tasks_reviewed',
            'fictional_extra_capacity_claimed',
            'baseline_leads_planned','scenario_leads_planned',
            'scenario_leads_waiting','newly_planned_lead_positions',
            'changed_slot_lead_positions','remaining_waiting_lead_positions',
            'fictional_open_tasks','fictional_overdue_tasks','fictional_due_now_tasks',
            'task_priority_positions','warning_codes','warning_count',
            'tenant_scope_verified','staff_identity_verified',
            'task_identity_verified','scoring_policy_verified',
            'capacity_policy_verified','concurrency_verified',
            'informational_only','authorized','can_read_customers',
            'can_assign','can_reassign','can_reschedule_task',
            'can_notify','can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        foreach ([
            'tenant_scope_verified','staff_identity_verified',
            'task_identity_verified','scoring_policy_verified',
            'capacity_policy_verified','concurrency_verified',
            'authorized','can_read_customers','can_assign',
            'can_reassign','can_reschedule_task','can_notify','can_persist',
        ] as $key) {
            self::assertFalse($out[$key], $key);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"tenant_id"','"email"','"lead_id"','"agent_id"',
            '"task_id"','"due_at_utc"','"user_id"'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
    }
}
