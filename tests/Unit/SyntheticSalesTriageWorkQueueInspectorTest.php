<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticSalesTriageWorkQueueInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSalesTriageWorkQueueInspectorTest extends TestCase
{
    private static function signals(?string $active): array
    {
        $out = [
            'profile_complete' => false, 'recent_engagement' => false,
            'requested_quote' => false, 'scheduled_demo' => false,
            'stale_response' => false,
        ];
        if ($active !== null) {
            $out[$active] = true;
        }
        return $out;
    }

    private static function leadBatch(int $capacity = 1): array
    {
        return [
            'agents' => [
                ['enabled' => true, 'has_required_skill' => true,
                    'open_leads' => 0, 'capacity' => $capacity],
                ['enabled' => true, 'has_required_skill' => true,
                    'open_leads' => 0, 'capacity' => $capacity],
            ],
            'leads' => [
                ['state' => 'unassigned', 'signals' => self::signals('profile_complete')],
                ['state' => 'unassigned', 'signals' => self::signals('requested_quote')],
                ['state' => 'unassigned', 'signals' => self::signals('stale_response')],
                ['state' => 'engaged', 'signals' => self::signals('requested_quote')],
            ],
            'weights' => [
                'profile_complete' => 20, 'recent_engagement' => 10,
                'requested_quote' => 30, 'scheduled_demo' => 25,
                'stale_response' => -20,
            ],
            'base_score' => 20,
            'start_cursor' => 0,
        ];
    }

    private static function task(string $due, string $priority = 'high'): array
    {
        return [[
            'revision' => 1, 'action' => 'create',
            'occurred_at_utc' => '2026-11-01T10:00:00Z',
            'due_at_utc' => $due, 'priority' => $priority,
            'reason_code' => null,
        ]];
    }

    private static function scenario(): array
    {
        return [
            'lead_batch' => self::leadBatch(),
            'task_histories' => [
                self::task('2026-11-09T12:00:00Z', 'high'),
                self::task('2026-11-11T12:00:00Z', 'urgent'),
                self::task('2026-11-10T12:00:00Z', 'low'),
            ],
            'observed_at_utc' => '2026-11-10T12:00:00Z',
        ];
    }

    public function test_scores_leads_before_capacity_then_orders_task_backlog(): void
    {
        $scenario = self::scenario();
        $out = (new SyntheticSalesTriageWorkQueueInspector())->inspect($scenario);
        self::assertSame(4, $out['fictional_leads_reviewed']);
        self::assertSame(3, $out['fictional_unassigned_leads']);
        self::assertSame([1, 0, 2], $out['lead_priority_positions']);
        self::assertSame([
            ['lead_position' => 1, 'agent_slot' => 0],
            ['lead_position' => 0, 'agent_slot' => 1],
        ], $out['lead_planned_slots']);
        self::assertSame([2], $out['lead_waiting_positions']);
        self::assertSame(1, $out['fictional_waiting_leads']);
        self::assertSame(3, $out['fictional_tasks_reviewed']);
        self::assertSame([0, 2, 1], $out['task_priority_positions']);
        self::assertSame(1, $out['fictional_overdue_tasks']);
        self::assertSame(1, $out['fictional_due_now_tasks']);
        self::assertSame([
            'fictional_lead_capacity_shortfall',
            'fictional_task_backlog_overdue',
            'fictional_task_due_now',
            'cross_module_authorization_unverified',
        ], $out['warning_codes']);
        self::assertSame($scenario, self::scenario());
        self::assertSame($out, (new SyntheticSalesTriageWorkQueueInspector())->inspect($scenario));
    }

    public function test_empty_task_queue_is_valid_if_observation_is_valid(): void
    {
        $scenario = self::scenario();
        $scenario['lead_batch'] = self::leadBatch(2);
        $scenario['task_histories'] = [];
        $out = (new SyntheticSalesTriageWorkQueueInspector())->inspect($scenario);
        self::assertSame(0, $out['fictional_tasks_reviewed']);
        self::assertSame([], $out['task_priority_positions']);
        self::assertSame(0, $out['fictional_waiting_leads']);
        self::assertSame(['cross_module_authorization_unverified'], $out['warning_codes']);
        self::assertFalse($out['can_assign']);
        self::assertFalse($out['can_notify']);
    }

    public function test_invalid_nested_histories_and_hidden_ids_fail_closed(): void
    {
        $valid = self::scenario();
        $bad = [
            null,
            array_merge($valid, ['customer_email' => 'private@example.test']),
            array_replace($valid, ['task_histories' => array_fill(0, 11, self::task('2026-11-09T12:00:00Z'))]),
            array_replace($valid, ['task_histories' => 'not an array']),
            array_replace($valid, ['task_histories' => [1 => self::task('2026-11-09T12:00:00Z')]]),
            array_replace($valid, ['task_histories' => [[[
                'revision' => 1, 'action' => 'create',
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
                'due_at_utc' => '2026-11-09T12:00:00Z',
                'priority' => 'high', 'reason_code' => null,
                'task_id' => 'private@example.test',
            ]]]]),
            array_replace($valid, ['observed_at_utc' => '2026-02-30T12:00:00Z']),
            array_replace($valid, ['lead_batch' => array_merge(self::leadBatch(), ['tenant_id' => 'private@example.test'])]),
        ];
        foreach ($bad as $candidate) {
            try {
                (new SyntheticSalesTriageWorkQueueInspector())->inspect($candidate);
                self::fail('Invalid queue scenario accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Fila sintetica comercial invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_never_releases_operational_rights_or_source_fields(): void
    {
        $out = (new SyntheticSalesTriageWorkQueueInspector())->inspect(self::scenario());
        self::assertSame([
            'fictional_leads_reviewed', 'fictional_unassigned_leads',
            'fictional_planned_assignments', 'fictional_waiting_leads',
            'lead_priority_positions', 'lead_planned_slots',
            'lead_waiting_positions', 'fictional_tasks_reviewed',
            'fictional_open_tasks', 'fictional_overdue_tasks',
            'fictional_due_now_tasks', 'task_priority_positions',
            'warning_codes', 'warning_count', 'source_history_verified',
            'task_identity_verified', 'agent_identity_verified',
            'tenant_scope_verified', 'scoring_policy_verified',
            'assignment_policy_verified', 'informational_only',
            'authorized', 'can_read_customers', 'can_assign',
            'can_update_task', 'can_notify', 'can_persist',
        ], array_keys($out));
        foreach (['source_history_verified','task_identity_verified',
            'agent_identity_verified','tenant_scope_verified','scoring_policy_verified',
            'assignment_policy_verified','authorized','can_read_customers',
            'can_assign','can_update_task','can_notify','can_persist'] as $field) {
            self::assertFalse($out[$field]);
        }
        self::assertTrue($out['informational_only']);
        $encoded = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"email"', '"phone"', '"tenant_id"', '"lead_id"', '"task_id"',
            '"signals"', '"weights"', '"due_at_utc"', '"observed_at_utc"'] as $key) {
            self::assertStringNotContainsString($key, $encoded);
        }
    }
}
