<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadCapacityOptionsInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadCapacityOptionsInspectorTest extends TestCase
{
    private static function batch(): array
    {
        $blank = [
            'profile_complete'=>false,'recent_engagement'=>false,
            'requested_quote'=>false,'scheduled_demo'=>false,
            'stale_response'=>false,
        ];
        $quote = $blank;
        $quote['requested_quote'] = true;
        return [
            'agents' => [
                ['enabled'=>true,'has_required_skill'=>true,'open_leads'=>0,'capacity'=>1],
                ['enabled'=>true,'has_required_skill'=>true,'open_leads'=>0,'capacity'=>0],
            ],
            'leads' => [
                ['state'=>'unassigned','signals'=>$quote],
                ['state'=>'unassigned','signals'=>$blank],
                ['state'=>'unassigned','signals'=>$blank],
            ],
            'weights' => [
                'profile_complete'=>20,'recent_engagement'=>10,
                'requested_quote'=>30,'scheduled_demo'=>25,
                'stale_response'=>-20,
            ],
            'base_score'=>20,
            'start_cursor'=>0,
        ];
    }

    public function test_compares_supplied_capacity_options_and_ties_choose_first(): void
    {
        $batch = self::batch();
        $options = [[0, 0], [0, 1], [0, 2], [2, 0]];
        $before = $options;
        $out = (new SyntheticLeadCapacityOptionsInspector())->inspect($batch, $options);
        self::assertSame(4, $out['candidate_count']);
        self::assertSame([
            ['option_position'=>0,'fictional_extra_slots'=>0,
                'fictional_planned_count'=>1,'fictional_waiting_count'=>2,
                'fictional_changed_slot_count'=>0,'fictional_clears_backlog'=>false],
            ['option_position'=>1,'fictional_extra_slots'=>1,
                'fictional_planned_count'=>2,'fictional_waiting_count'=>1,
                'fictional_changed_slot_count'=>0,'fictional_clears_backlog'=>false],
            ['option_position'=>2,'fictional_extra_slots'=>2,
                'fictional_planned_count'=>3,'fictional_waiting_count'=>0,
                'fictional_changed_slot_count'=>0,'fictional_clears_backlog'=>true],
            ['option_position'=>3,'fictional_extra_slots'=>2,
                'fictional_planned_count'=>3,'fictional_waiting_count'=>0,
                'fictional_changed_slot_count'=>0,'fictional_clears_backlog'=>true],
        ], $out['fictional_option_summaries']);
        self::assertSame(2, $out['lowest_extra_slots_clearing_candidate_position']);
        self::assertSame([
            'candidate_set_not_global_optimization',
            'capacity_staff_scope_unverified',
        ], $out['warning_codes']);
        self::assertSame($before, $options);
        self::assertSame($batch, self::batch());
        self::assertSame($out, (new SyntheticLeadCapacityOptionsInspector())->inspect($batch, $options));
    }

    public function test_no_option_clears_backlog_and_disabled_agents_remain_disabled(): void
    {
        $batch = self::batch();
        $batch['agents'][1]['enabled'] = false;
        $out = (new SyntheticLeadCapacityOptionsInspector())->inspect(
            $batch,
            [[0, 0], [0, 2]],
        );
        self::assertNull($out['lowest_extra_slots_clearing_candidate_position']);
        self::assertSame(2, $out['fictional_option_summaries'][1]['fictional_waiting_count']);
        self::assertContains('no_supplied_option_clears_fictional_backlog', $out['warning_codes']);
        self::assertFalse($out['can_hire']);
    }

    public function test_option_comparison_reports_existing_slot_reorder(): void
    {
        $batch = self::batch();
        $batch['agents'][1]['capacity'] = 3;
        $out = (new SyntheticLeadCapacityOptionsInspector())->inspect($batch, [
            [0, 0], [1, 0],
        ]);
        self::assertSame(1, $out['fictional_option_summaries'][1]['fictional_changed_slot_count']);
        self::assertContains('fictional_existing_slot_reorder', $out['warning_codes']);
        self::assertSame(0, $out['lowest_extra_slots_clearing_candidate_position']);
        self::assertFalse($out['can_reassign']);
    }

    public function test_malformed_or_excess_options_fail_closed_without_echoing_input(): void
    {
        $batch = self::batch();
        $cases = [
            [$batch, []],
            [$batch, array_fill(0, 6, [0, 0])],
            [$batch, [0, 0]],
            [$batch, [[1]]],
            [$batch, [[1, '1']]],
            [$batch, [[0, true]]],
            [$batch, [[-1, 0]]],
            [$batch, [[0, 21]]],
            [$batch, ['first'=>[0, 0]]],
            [array_merge($batch, ['email'=>'secret@example.test']), [[0, 0]]],
            [array_replace($batch, ['leads'=>[[
                'state'=>'unassigned','signals'=>['email'=>'secret@example.test'],
            ]]]), [[0, 0]]],
        ];
        foreach ($cases as [$data, $options]) {
            try {
                (new SyntheticLeadCapacityOptionsInspector())->inspect($data, $options);
                self::fail('Invalid scenario options accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Comparacao sintetica de cenarios invalida.', $error->getMessage());
                self::assertStringNotContainsString('secret@example.test', $error->getMessage());
            }
        }
    }

    public function test_output_fields_are_bounded_anonymous_and_deny_all_effects(): void
    {
        $out = (new SyntheticLeadCapacityOptionsInspector())->inspect(
            self::batch(), [[0, 0], [0, 2]],
        );
        self::assertSame([
            'candidate_count', 'fictional_option_summaries',
            'lowest_extra_slots_clearing_candidate_position',
            'warning_codes', 'warning_count',
            'candidate_set_completeness_verified','staff_identity_verified',
            'capacity_policy_verified','tenant_scope_verified',
            'concurrency_verified','informational_only','authorized',
            'can_assign','can_reassign','can_hire','can_notify','can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        foreach ([
            'candidate_set_completeness_verified','staff_identity_verified',
            'capacity_policy_verified','tenant_scope_verified',
            'concurrency_verified','authorized','can_assign',
            'can_reassign','can_hire','can_notify','can_persist',
        ] as $name) {
            self::assertFalse($out[$name], $name);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"tenant_id"','"email"','"lead_id"','"agent_id"',
            '"phone"','"signals"','"raw_scores"'] as $secret) {
            self::assertStringNotContainsString($secret, $json);
        }
    }
}
