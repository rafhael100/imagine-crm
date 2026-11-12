<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadCapacityScenarioCompare;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadCapacityScenarioCompareTest extends TestCase
{
    private static function batch(): array
    {
        $signal = [
            'profile_complete' => false,
            'recent_engagement' => false,
            'requested_quote' => false,
            'scheduled_demo' => false,
            'stale_response' => false,
        ];
        $high = $signal;
        $high['requested_quote'] = true;
        $medium = $signal;
        $medium['profile_complete'] = true;
        $low = $signal;
        $low['stale_response'] = true;

        return [
            'agents' => [
                ['enabled'=>true, 'has_required_skill'=>true, 'open_leads'=>0, 'capacity'=>1],
                ['enabled'=>true, 'has_required_skill'=>true, 'open_leads'=>0, 'capacity'=>0],
            ],
            'leads' => [
                ['state'=>'unassigned', 'signals'=>$medium],
                ['state'=>'unassigned', 'signals'=>$high],
                ['state'=>'unassigned', 'signals'=>$low],
                ['state'=>'engaged', 'signals'=>$high],
            ],
            'weights' => [
                'profile_complete'=>20, 'recent_engagement'=>10,
                'requested_quote'=>30, 'scheduled_demo'=>25,
                'stale_response'=>-20,
            ],
            'base_score'=>20,
            'start_cursor'=>0,
        ];
    }

    public function test_extra_capacity_clears_backlog_without_changing_existing_assignment(): void
    {
        $batch = self::batch();
        $original = $batch;
        $out = (new SyntheticLeadCapacityScenarioCompare())->compare($batch, [0, 2]);
        self::assertSame(4, $out['fictional_leads_reviewed']);
        self::assertSame(3, $out['fictional_unassigned_leads']);
        self::assertSame(2, $out['fictional_extra_capacity_claimed']);
        self::assertSame(1, $out['baseline_planned_count']);
        self::assertSame(3, $out['scenario_planned_count']);
        self::assertSame(2, $out['baseline_waiting_count']);
        self::assertSame(0, $out['scenario_waiting_count']);
        self::assertSame([0, 2], $out['newly_planned_lead_positions']);
        self::assertSame([], $out['changed_slot_lead_positions']);
        self::assertSame([], $out['remaining_waiting_lead_positions']);
        self::assertSame(['capacity_and_staff_identity_unverified'], $out['warning_codes']);
        self::assertSame($original, $batch);
        self::assertSame($out, (new SyntheticLeadCapacityScenarioCompare())->compare($batch, [0, 2]));
    }

    public function test_extra_capacity_can_change_a_previously_planned_slot_and_must_warn(): void
    {
        $batch = self::batch();
        $batch['agents'][1]['capacity'] = 3;
        $out = (new SyntheticLeadCapacityScenarioCompare())->compare($batch, [1, 0]);
        self::assertSame(3, $out['baseline_planned_count']);
        self::assertSame(3, $out['scenario_planned_count']);
        self::assertSame([], $out['newly_planned_lead_positions']);
        self::assertSame([2], $out['changed_slot_lead_positions']);
        self::assertSame([
            'fictional_slot_changes_between_scenarios',
            'capacity_and_staff_identity_unverified',
        ], $out['warning_codes']);
        self::assertFalse($out['can_reassign']);
    }

    public function test_no_additional_capacity_keeps_backlog_and_all_rights_denied(): void
    {
        $out = (new SyntheticLeadCapacityScenarioCompare())->compare(self::batch(), [0, 0]);
        self::assertSame(1, $out['scenario_planned_count']);
        self::assertSame(2, $out['scenario_waiting_count']);
        self::assertSame([0, 2], $out['remaining_waiting_lead_positions']);
        self::assertContains('fictional_backlog_remains', $out['warning_codes']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_assign']);
        self::assertFalse($out['can_notify']);
        self::assertFalse($out['can_persist']);
    }

    public function test_invalid_schema_or_capacity_never_leaks_private_input(): void
    {
        $good = self::batch();
        $cases = [
            [null, [0, 0]],
            [$good, null],
            [$good, [0]],
            [$good, [0, 0, 0]],
            [$good, [false, 0]],
            [$good, [0, -1]],
            [$good, [21, 0]],
            [$good, ['left'=>0, 'right'=>1]],
            [array_merge($good, ['email'=>'secret@example.test']), [0, 0]],
            [array_replace($good, ['agents'=>[[
                'enabled'=>true, 'has_required_skill'=>true, 'open_leads'=>0,
                'capacity'=>20,
            ]]]), [1]],
            [array_replace($good, ['leads'=>[[
                'state'=>'unassigned', 'signals'=>['email'=>'secret@example.test'],
            ]]]), [0, 0]],
        ];
        foreach ($cases as [$batch, $extra]) {
            try {
                (new SyntheticLeadCapacityScenarioCompare())->compare($batch, $extra);
                self::fail('Untrusted capacity comparison accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Cenario sintetico de capacidade invalido.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_bounded_anonymous_output_has_only_closed_warnings_and_denies(): void
    {
        $out = (new SyntheticLeadCapacityScenarioCompare())->compare(self::batch(), [0, 2]);
        self::assertSame([
            'fictional_leads_reviewed', 'fictional_unassigned_leads',
            'fictional_extra_capacity_claimed', 'baseline_planned_count',
            'scenario_planned_count', 'baseline_waiting_count',
            'scenario_waiting_count', 'newly_planned_lead_positions',
            'changed_slot_lead_positions', 'remaining_waiting_lead_positions',
            'warning_codes', 'warning_count',
            'staff_identity_verified', 'capacity_policy_verified',
            'tenant_scope_verified', 'concurrency_verified',
            'source_history_verified', 'informational_only',
            'authorized', 'can_assign', 'can_reassign',
            'can_notify', 'can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        foreach ([
            'staff_identity_verified', 'capacity_policy_verified',
            'tenant_scope_verified', 'concurrency_verified',
            'source_history_verified', 'authorized', 'can_assign',
            'can_reassign', 'can_notify', 'can_persist',
        ] as $flag) {
            self::assertFalse($out[$flag], $flag);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"email"', '"tenant_id"', '"lead_id"', '"agent_id"', '"phone"',
            '"source_signals"', '"raw_scores"'] as $key) {
            self::assertStringNotContainsString($key, $json);
        }
    }
}
