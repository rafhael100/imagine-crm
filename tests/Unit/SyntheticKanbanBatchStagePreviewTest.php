<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticKanbanBatchStagePreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanBatchStagePreviewTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function card(
        string $id,
        int $stage,
        bool $selected,
        bool $visible,
        bool $hasAssignee = true,
        int $revisionClaim = 1,
    ): array {
        return [
            'ref' => $id,
            'events' => [[
                'revision' => 1, 'action' => 'create', 'stage' => $stage,
                'confirmed' => false, 'loss_reason_code' => null,
                'reopen_reason_code' => null,
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
            ]],
            'expected_revision' => $revisionClaim,
            'selected' => $selected,
            'visible' => $visible,
            'field_presence' => [
                'amount' => true, 'assignee' => $hasAssignee, 'contact' => true,
                'expected_close' => true, 'source' => true,
            ],
            'requirements' => [[], [], ['amount', 'assignee', 'contact'], ['expected_close']],
        ];
    }

    public function test_selected_visible_cards_only_are_reviewed_with_explicit_blocker_positions(): void
    {
        $cards = [
            self::card('F01', 0, true, true),
            self::card('F02', 0, true, false),
            self::card('F03', 1, false, true),
            self::card('F04', 0, true, true, false),
            self::card('F05', 2, true, true),
        ];
        $original = $cards;
        $preview = (new SyntheticKanbanBatchStagePreview())->inspect($cards, 'F01', 2);

        self::assertSame('selected_group', $preview['mode']);
        self::assertSame(5, $preview['card_count']);
        self::assertSame([0, 3, 4], $preview['scope_positions']);
        self::assertSame([0], $preview['criteria_positions']);
        self::assertSame([
            ['position' => 3, 'reason_code' => 'FIELDS_MISSING',
                'missing_count' => 1, 'missing_field_codes' => ['assignee']],
            ['position' => 4, 'reason_code' => 'SAME_STAGE',
                'missing_count' => 0, 'missing_field_codes' => []],
        ], $preview['blocked']);
        self::assertSame(3, $preview['scope_count']);
        self::assertSame(1, $preview['criteria_count']);
        self::assertSame(2, $preview['blocked_count']);
        self::assertTrue($preview['informational_only']);
        foreach ([
            'selection_verified', 'visibility_verified', 'identity_verified',
            'tenant_verified', 'concurrency_verified', 'authorization_verified',
            'authorized', 'can_bulk_move', 'can_move', 'can_persist',
        ] as $flag) {
            self::assertFalse($preview[$flag], $flag);
        }
        self::assertSame($original, $cards);
        self::assertSame($preview, (new SyntheticKanbanBatchStagePreview())->inspect($cards, 'F01', 2));
    }

    public function test_unselected_dragged_card_is_single_and_ignores_other_selected_cards(): void
    {
        $cards = [
            self::card('F01', 0, true, true),
            self::card('F02', 1, false, true),
            self::card('F03', 1, true, true),
        ];
        $preview = (new SyntheticKanbanBatchStagePreview())->inspect($cards, 'F02', 2);
        self::assertSame('single_card', $preview['mode']);
        self::assertSame([1], $preview['scope_positions']);
        self::assertSame([1], $preview['criteria_positions']);
        self::assertSame([], $preview['blocked']);
        self::assertFalse($preview['can_move']);

        $cards[1]['visible'] = false;
        $hidden = (new SyntheticKanbanBatchStagePreview())->inspect($cards, 'F02', 2);
        self::assertFalse($hidden['dragged_visible_claimed']);
        self::assertSame([], $hidden['scope_positions']);
        self::assertSame(0, $hidden['scope_count']);
        self::assertFalse($hidden['can_persist']);
    }

    public function test_stale_version_and_closed_history_block_the_whole_claimed_move(): void
    {
        $cards = [
            self::card('F01', 0, true, true, true, 0),
            self::card('F02', 0, true, true),
        ];
        $cards[1]['events'][] = [
            'revision' => 2, 'action' => 'win', 'stage' => 0,
            'confirmed' => true, 'loss_reason_code' => null,
            'reopen_reason_code' => null,
            'occurred_at_utc' => '2026-11-01T10:00:01Z',
        ];
        $cards[1]['expected_revision'] = 2;
        $out = (new SyntheticKanbanBatchStagePreview())->inspect($cards, 'F01', 2);
        self::assertSame([], $out['criteria_positions']);
        self::assertSame(['STALE_REVISION', 'CLOSED_CLAIM'],
            array_column($out['blocked'], 'reason_code'));
        self::assertFalse($out['authorized']);
    }

    public function test_strict_bounds_duplicate_ids_and_malformed_hidden_cards_fail_closed(): void
    {
        $base = self::card('F01', 0, true, true);
        $hidden = self::card('F02', 0, false, false);
        $bad = [
            [[], 'F01', 2],
            [[...array_fill(0, 13, $base)], 'F01', 2],
            [[$base, $base], 'F01', 2],
            [[$base], 'F13', 2],
            [[$base], 'private@example.test', 2],
            [[$base], 'F01', '2'],
            [[$base], 'F01', 4],
            [[array_merge($base, ['tenant_id' => 'private@example.test'])], 'F01', 2],
            [[array_merge($base, ['selected' => 1])], 'F01', 2],
            [[array_merge($base, ['visible' => null])], 'F01', 2],
            [[$base, array_merge($hidden, ['field_presence' => ['email' => 'private@example.test']])], 'F01', 2],
            [[array_merge($base, ['requirements' => [[], [], ['contact', 'contact'], []]])], 'F01', 2],
            [[array_merge($base, ['events' => 'private@example.test'])], 'F01', 2],
        ];
        foreach ($bad as [$cards, $dragId, $target]) {
            try {
                (new SyntheticKanbanBatchStagePreview())->inspect($cards, $dragId, $target);
                self::fail('Untrusted Kanban batch input accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_public_output_has_only_positions_not_synthetic_ids_or_sensitive_fields(): void
    {
        $report = (new SyntheticKanbanBatchStagePreview())->inspect(
            [self::card('F01', 0, true, true)], 'F01', 2,
        );
        self::assertSame([
            'mode', 'card_count', 'dragged_visible_claimed', 'scope_positions',
            'criteria_positions', 'blocked', 'scope_count', 'criteria_count',
            'blocked_count', 'selection_verified', 'visibility_verified',
            'identity_verified', 'tenant_verified', 'concurrency_verified',
            'authorization_verified', 'informational_only', 'authorized',
            'can_bulk_move', 'can_move', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('F01', $json);
        foreach (['email', 'phone', 'tenant_id', 'principal_id', 'actor',
            'event_reference', 'occurred_at_utc', 'field_presence'] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
        self::assertFalse($report['can_bulk_move']);
    }
}
