<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadPriorityCapacityBatchPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadPriorityCapacityBatchPreviewTest extends TestCase
{
    private static function weights(): array
    {
        return [
            'profile_complete' => 20, 'recent_engagement' => 10,
            'requested_quote' => 30, 'scheduled_demo' => 25,
            'stale_response' => -20,
        ];
    }

    private static function lead(string $state, ?string $signal = null): array
    {
        $signals = array_fill_keys(array_keys(self::weights()), false);
        if ($signal !== null) {
            $signals[$signal] = true;
        }
        return ['state' => $state, 'signals' => $signals];
    }

    private static function agent(int $capacity = 1): array
    {
        return [
            'enabled' => true,
            'has_required_skill' => true,
            'open_leads' => 0,
            'capacity' => $capacity,
        ];
    }

    private static function scenario(): array
    {
        return [
            'agents' => [self::agent(), self::agent()],
            'leads' => [
                self::lead('unassigned', 'profile_complete'),  // 40
                self::lead('unassigned', 'requested_quote'),   // 50
                self::lead('unassigned', 'requested_quote'),   // 50
                self::lead('engaged', 'scheduled_demo'),       // excluded
                self::lead('unassigned', 'stale_response'),    // 0
            ],
            'weights' => self::weights(),
            'base_score' => 20,
            'start_cursor' => 1,
        ];
    }

    public function test_high_score_first_with_stable_ties_and_capacity_limits(): void
    {
        $batch = self::scenario();
        $before = $batch;
        $out = (new SyntheticLeadPriorityCapacityBatchPreview())->preview($batch);
        self::assertSame(5, $out['leads_reviewed']);
        self::assertSame(4, $out['unassigned_reviewed']);
        self::assertSame(1, $out['already_owned_or_handled']);
        self::assertSame([1, 2, 0, 4], $out['ranked_unassigned_positions']);
        self::assertSame(['morno', 'morno', 'morno', 'frio'], $out['ranked_score_bands']);
        self::assertSame([
            ['lead_position' => 1, 'agent_slot' => 1],
            ['lead_position' => 2, 'agent_slot' => 0],
        ], $out['planned_slot_pairs']);
        self::assertSame([0, 4], $out['waiting_lead_positions']);
        self::assertSame([1, 1], $out['projected_slot_loads']);
        self::assertSame(2, $out['planned_count']);
        self::assertSame(2, $out['waiting_count']);
        self::assertSame(1, $out['next_cursor']);
        self::assertSame($before, $batch);
        self::assertSame($out, (new SyntheticLeadPriorityCapacityBatchPreview())->preview($batch));
    }

    public function test_never_reassigns_engaged_assigned_or_closed_rows(): void
    {
        $batch = self::scenario();
        $batch['leads'] = [
            self::lead('assigned', 'requested_quote'),
            self::lead('engaged', 'requested_quote'),
            self::lead('closed', 'requested_quote'),
        ];
        $out = (new SyntheticLeadPriorityCapacityBatchPreview())->preview($batch);
        self::assertSame(3, $out['already_owned_or_handled']);
        self::assertSame([], $out['planned_slot_pairs']);
        self::assertSame([], $out['ranked_unassigned_positions']);
        self::assertSame(0, $out['planned_count']);
        self::assertFalse($out['can_redistribute']);
    }

    public function test_no_agents_or_no_leads_is_an_inert_deterministic_result(): void
    {
        $batch = self::scenario();
        $batch['agents'] = [];
        $batch['start_cursor'] = 0;
        $out = (new SyntheticLeadPriorityCapacityBatchPreview())->preview($batch);
        self::assertSame(4, $out['waiting_count']);
        self::assertSame([], $out['projected_slot_loads']);
        self::assertSame([], $out['planned_slot_pairs']);

        $batch['leads'] = [];
        $empty = (new SyntheticLeadPriorityCapacityBatchPreview())->preview($batch);
        self::assertSame([], $empty['waiting_lead_positions']);
        self::assertSame(0, $empty['leads_reviewed']);
        self::assertFalse($empty['can_assign']);
    }

    public function test_invalid_nested_leads_weights_agents_and_cursor_fail_closed(): void
    {
        $valid = self::scenario();
        $cases = [
            null, ['leads' => []],
            array_merge($valid, ['email' => 'secret@example.test']),
            array_replace($valid, ['leads' => array_fill(0, 21, self::lead('unassigned'))]),
            array_replace($valid, ['leads' => [['state' => 'pending', 'signals' => self::lead('unassigned')['signals']]]]),
            array_replace($valid, ['leads' => [[
                'state' => 'unassigned', 'signals' => self::lead('unassigned')['signals'],
                'email' => 'secret@example.test',
            ]]]),
            array_replace($valid, ['weights' => ['profile_complete' => 20]]),
            array_replace($valid, ['base_score' => '20']),
            array_replace($valid, ['agents' => [[
                'enabled' => true, 'has_required_skill' => true,
                'open_leads' => 0, 'capacity' => 1,
                'actor_id' => 'secret@example.test',
            ]]]),
            array_replace($valid, ['start_cursor' => 3]),
            array_replace($valid, ['leads' => [1 => self::lead('unassigned')]]),
        ];
        foreach ($cases as $case) {
            try {
                (new SyntheticLeadPriorityCapacityBatchPreview())->preview($case);
                self::fail('Untrusted lead triage was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Triagem sintetica de leads invalida.', $error->getMessage());
                self::assertStringNotContainsString('secret@example.test', $error->getMessage());
            }
        }
    }

    public function test_all_twenty_leads_have_bounded_projection_without_authority(): void
    {
        $batch = self::scenario();
        $batch['agents'] = array_fill(0, 8, self::agent(20));
        $batch['leads'] = array_fill(0, 20, self::lead('unassigned', 'requested_quote'));
        $batch['start_cursor'] = 0;
        $out = (new SyntheticLeadPriorityCapacityBatchPreview())->preview($batch);
        self::assertSame(range(0, 19), $out['ranked_unassigned_positions']);
        self::assertSame(20, $out['planned_count']);
        self::assertSame(0, $out['waiting_count']);
        self::assertTrue($out['informational_only']);
        foreach (['score_policy_verified','agent_identity_verified','tenant_scope_verified',
            'capacity_policy_verified','concurrency_verified','authorized','can_assign',
            'can_redistribute','can_update_lead','can_notify','can_persist'] as $key) {
            self::assertFalse($out[$key]);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"tenant_id"', '"lead_id"', '"email"', '"phone"', '"signals"',
            '"agent_id"', '"user_id"', '"claimed_value_cents"'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
    }
}
