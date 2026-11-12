<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadRoundRobinPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadRoundRobinPreviewTest extends TestCase
{
    /** @return array{enabled:bool,has_required_skill:bool,open_leads:int,capacity:int} */
    private static function agent(
        bool $enabled = true,
        bool $skill = true,
        int $open = 0,
        int $capacity = 5,
    ): array {
        return [
            'enabled' => $enabled,
            'has_required_skill' => $skill,
            'open_leads' => $open,
            'capacity' => $capacity,
        ];
    }

    public function test_fair_round_robin_starts_at_cursor_and_preserves_input(): void
    {
        $agents = [self::agent(), self::agent(), self::agent()];
        $states = array_fill(0, 7, 'unassigned');
        $beforeAgents = $agents;
        $beforeStates = $states;
        $report = (new SyntheticLeadRoundRobinPreview())->preview($agents, $states, 1);

        self::assertSame([1, 2, 0, 1, 2, 0, 1], $report['assignment_slots']);
        self::assertSame([2, 3, 2], $report['projected_loads']);
        self::assertSame(7, $report['planned_assignments']);
        self::assertSame(0, $report['already_owned_or_handled']);
        self::assertSame(0, $report['unassigned_due_to_capacity']);
        self::assertSame(2, $report['next_cursor']);
        self::assertSame(7, $report['lead_count']);
        self::assertSame(3, $report['agent_count']);
        self::assertSame($beforeAgents, $agents);
        self::assertSame($beforeStates, $states);
        self::assertSame($report, (new SyntheticLeadRoundRobinPreview())->preview($agents, $states, 1));
        self::assertTrue($report['informational_only']);
        foreach ([
            'assignment_policy_verified', 'staff_identity_verified',
            'tenant_verified', 'concurrency_verified', 'lead_state_verified',
            'authorized', 'can_assign', 'can_redistribute',
            'can_notify', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag], $flag);
        }
    }

    public function test_existing_engaged_owned_or_closed_leads_are_never_redistributed(): void
    {
        $report = (new SyntheticLeadRoundRobinPreview())->preview(
            [self::agent(), self::agent()],
            ['assigned', 'engaged', 'closed', 'unassigned', 'engaged', 'unassigned'],
        );
        self::assertSame([null, null, null, 0, null, 1], $report['assignment_slots']);
        self::assertSame(4, $report['already_owned_or_handled']);
        self::assertSame(2, $report['planned_assignments']);
        self::assertSame([1, 1], $report['projected_loads']);
        self::assertSame(0, $report['next_cursor']);
        self::assertFalse($report['can_redistribute']);
    }

    public function test_disabled_unskilled_and_at_capacity_slots_are_skipped(): void
    {
        $report = (new SyntheticLeadRoundRobinPreview())->preview(
            [
                self::agent(false, true, 0, 3),
                self::agent(true, false, 0, 3),
                self::agent(true, true, 2, 3),
                self::agent(true, true, 0, 2),
            ],
            ['unassigned', 'unassigned', 'unassigned', 'unassigned', 'unassigned'],
            0,
        );
        self::assertSame([2, 3, 3, null, null], $report['assignment_slots']);
        self::assertSame([0, 0, 3, 2], $report['projected_loads']);
        self::assertSame(3, $report['planned_assignments']);
        self::assertSame(2, $report['unassigned_due_to_capacity']);
        self::assertSame(0, $report['already_owned_or_handled']);
        self::assertSame(0, $report['next_cursor']);
        self::assertFalse($report['concurrency_verified']);
        self::assertFalse($report['can_assign']);
    }

    public function test_empty_agents_or_empty_leads_never_invent_a_staff_identity(): void
    {
        $emptyAgents = (new SyntheticLeadRoundRobinPreview())->preview(
            [], ['unassigned', 'assigned', 'unassigned'],
        );
        self::assertSame([null, null, null], $emptyAgents['assignment_slots']);
        self::assertSame(2, $emptyAgents['unassigned_due_to_capacity']);
        self::assertSame(1, $emptyAgents['already_owned_or_handled']);
        self::assertSame(0, $emptyAgents['next_cursor']);
        self::assertSame([], $emptyAgents['projected_loads']);

        $emptyLeads = (new SyntheticLeadRoundRobinPreview())->preview(
            [self::agent()], [],
        );
        self::assertSame([], $emptyLeads['assignment_slots']);
        self::assertSame(0, $emptyLeads['planned_assignments']);
        self::assertSame([0], $emptyLeads['projected_loads']);
        self::assertFalse($emptyLeads['authorized']);
    }

    public function test_all_work_is_bounded_by_anonymous_capacity_and_twenty_leads(): void
    {
        $agents = array_fill(0, 8, self::agent(true, true, 0, 20));
        $result = (new SyntheticLeadRoundRobinPreview())->preview(
            $agents, array_fill(0, 20, 'unassigned'),
        );
        self::assertSame(20, $result['planned_assignments']);
        self::assertSame([3, 3, 3, 3, 2, 2, 2, 2], $result['projected_loads']);
        self::assertSame(4, $result['next_cursor']);
        self::assertSame(20, $result['lead_count']);
        self::assertFalse($result['can_persist']);
    }

    public function test_invalid_agent_shapes_lead_states_and_cursor_fail_closed(): void
    {
        $agent = self::agent();
        $bad = [
            [null, [], 0], ['agents', [], 0],
            [[null], [], 0], [[1 => $agent], [], 0],
            [array_fill(0, 9, $agent), [], 0],
            [[$agent], null, 0], [[$agent], 'unassigned', 0],
            [[$agent], [1 => 'unassigned'], 0],
            [[$agent], array_fill(0, 21, 'unassigned'), 0],
            [[$agent], ['new'], 0], [[$agent], [null], 0],
            [[$agent], ['UNASSIGNED'], 0],
            [[$agent], [], '0'], [[$agent], [], -1],
            [[$agent], [], 1], [[], [], 1],
            [[array_merge($agent, ['email' => 'private@example.test'])], [], 0],
            [[array_merge($agent, ['open_leads' => '1'])], [], 0],
            [[array_merge($agent, ['open_leads' => -1])], [], 0],
            [[array_merge($agent, ['open_leads' => 6])], [], 0],
            [[array_merge($agent, ['capacity' => -1])], [], 0],
            [[array_merge($agent, ['capacity' => 21])], [], 0],
            [[array_merge($agent, ['enabled' => 1])], [], 0],
            [[array_merge($agent, ['has_required_skill' => 'true'])], [], 0],
            [[[ 'enabled' => true, 'capacity' => 5, 'open_leads' => 0 ]], [], 0],
        ];
        foreach ($bad as [$agents, $states, $cursor]) {
            try {
                (new SyntheticLeadRoundRobinPreview())->preview($agents, $states, $cursor);
                self::fail('Unsafe distribution or PII-bearing agent accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Distribuicao sintetica de leads invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_result_uses_anonymous_local_indices_and_does_not_grant_authority(): void
    {
        $report = (new SyntheticLeadRoundRobinPreview())->preview(
            [self::agent()], ['unassigned', 'engaged'],
        );
        self::assertSame([
            'agent_count', 'lead_count', 'assignment_slots',
            'planned_assignments', 'already_owned_or_handled',
            'unassigned_due_to_capacity', 'projected_loads', 'next_cursor',
            'assignment_policy_verified', 'staff_identity_verified',
            'tenant_verified', 'concurrency_verified', 'lead_state_verified',
            'informational_only', 'authorized', 'can_assign',
            'can_redistribute', 'can_notify', 'can_persist',
        ], array_keys($report));
        self::assertSame([0, null], $report['assignment_slots']);
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'user_id', 'agent_id', 'lead_id', 'actor',
            'email', 'phone', 'customer', 'owner_id', 'token',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        self::assertFalse($report['can_assign']);
    }
}
