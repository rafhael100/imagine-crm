<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticKanbanBatchStagePreview;
use PHPUnit\Framework\TestCase;

/**
 * Exercise the actual static Kanban fixture IDs/stages against the pure PHP
 * preview. No browser networking, customer records, routes or persistence.
 */
final class SyntheticKanbanBatchFixtureParityTest extends TestCase
{
    /** @return list<array<string,mixed>> */
    private static function htmlFixtureClaims(): array
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html',
        );
        self::assertIsString($html);
        preg_match_all(
            "/Object\\.freeze\\(\\{id:'(F(?:0[1-9]|1[0-2]))',label:'[^']+',stage:([0-3]),priority:/u",
            $html,
            $matches,
            PREG_SET_ORDER,
        );
        self::assertCount(8, $matches, 'Only eight invented initial Kanban fixtures may be read.');

        return array_map(
            static function (array $row): array {
                return [
                    'ref' => $row[1],
                    'events' => [[
                        'revision' => 1, 'action' => 'create',
                        'stage' => (int) $row[2],
                        'confirmed' => false, 'loss_reason_code' => null,
                        'reopen_reason_code' => null,
                        'occurred_at_utc' => '2026-11-01T10:00:00Z',
                    ]],
                    'expected_revision' => 1,
                    'selected' => in_array($row[1], ['F01', 'F02', 'F03'], true),
                    'visible' => $row[1] !== 'F02',
                    'field_presence' => [
                        'amount' => true, 'assignee' => true, 'contact' => true,
                        'expected_close' => true, 'source' => true,
                    ],
                    'requirements' => [[], [], [], ['contact', 'amount']],
                ];
            },
            $matches,
        );
    }

    public function test_group_preview_uses_exactly_the_selected_and_visible_original_kanban_cards(): void
    {
        $fixtures = self::htmlFixtureClaims();
        self::assertSame(
            ['F01', 'F02', 'F03', 'F04', 'F05', 'F06', 'F07', 'F08'],
            array_column($fixtures, 'ref'),
        );
        $copy = $fixtures;
        $out = (new SyntheticKanbanBatchStagePreview())
            ->inspect($fixtures, 'F01', 3);

        self::assertSame('selected_group', $out['mode']);
        self::assertSame([0, 2], $out['scope_positions']);
        self::assertSame([0, 2], $out['criteria_positions']);
        self::assertSame([], $out['blocked']);
        self::assertSame(2, $out['criteria_count']);
        self::assertFalse($out['visibility_verified']);
        self::assertFalse($out['can_bulk_move']);
        self::assertFalse($out['can_persist']);
        self::assertSame($copy, $fixtures);
    }

    public function test_unselected_drag_and_hidden_drag_never_convert_into_bulk_authority(): void
    {
        $fixtures = self::htmlFixtureClaims();
        $preview = new SyntheticKanbanBatchStagePreview();
        $single = $preview->inspect($fixtures, 'F04', 3);
        self::assertSame('single_card', $single['mode']);
        self::assertSame([3], $single['scope_positions']);
        self::assertSame([3], $single['criteria_positions']);
        self::assertFalse($single['can_move']);

        $hidden = $preview->inspect($fixtures, 'F02', 3);
        self::assertSame('selected_group', $hidden['mode']);
        self::assertSame([], $hidden['scope_positions']);
        self::assertSame(0, $hidden['criteria_count']);
        self::assertFalse($hidden['can_persist']);

        $fixtures[6]['selected'] = true; // F07 is already in stage 3.
        $same = $preview->inspect($fixtures, 'F07', 3);
        self::assertSame([0, 2, 6], $same['scope_positions']);
        self::assertSame(['SAME_STAGE'], array_column($same['blocked'], 'reason_code'));
        self::assertFalse($same['authorized']);
    }
}
