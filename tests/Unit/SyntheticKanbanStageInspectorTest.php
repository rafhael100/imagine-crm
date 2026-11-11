<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticKanbanStageInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanStageInspectorTest extends TestCase
{
    /** @return array{id:string,stage:int,priority:string,followup:string,value:int,score:int} */
    private static function fixture(int $id): array
    {
        return [
            'id' => sprintf('F%02d', $id),
            'stage' => ($id - 1) % 4,
            'priority' => $id % 2 ? 'high' : 'normal',
            'followup' => $id % 3 ? 'pending' : 'done',
            'value' => 1000 + $id,
            'score' => 60,
        ];
    }

    public function test_empty_and_full_synthetic_snapshots_have_only_bounded_counts(): void
    {
        $inspector = new SyntheticKanbanStageInspector();
        $empty = $inspector->inspect([]);
        self::assertSame([0, 0, 0, 0], $empty->stageCounts);
        self::assertSame(0, $empty->total);

        $rows = array_map(self::fixture(...), range(1, 12));
        $result = $inspector->inspect($rows);
        self::assertSame([3, 3, 3, 3], $result->stageCounts);
        self::assertSame(12, $result->total);
        self::assertSame(6, $result->highPriority);
        self::assertSame(8, $result->pendingFollowups);
        self::assertSame([3, 0, 3, 0], $result->highPriorityByStage);
        self::assertSame([2, 2, 2, 2], $result->pendingByStage);
        self::assertSame(12, array_sum($result->stageCounts));
        self::assertSame($result->highPriority, array_sum($result->highPriorityByStage));
        self::assertSame($result->pendingFollowups, array_sum($result->pendingByStage));
        self::assertFalse($result->canReadCustomerData());
        self::assertFalse($result->canPersistLead());

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['F01', 'F12', '1001', 'lead', 'email', 'tenant'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
    }

    public function test_stage_change_is_reflected_in_counts_without_mutating_input(): void
    {
        $rows = [self::fixture(1), self::fixture(2), self::fixture(3)];
        $before = $rows;
        $inspector = new SyntheticKanbanStageInspector();
        $report = $inspector->inspect($rows);
        self::assertSame([1, 1, 1, 0], $report->stageCounts);
        $rows[0]['stage'] = 3;
        $updated = $inspector->inspect($rows);
        self::assertSame([0, 1, 1, 1], $updated->stageCounts);
        self::assertSame(3, $updated->total);
        self::assertSame(2, $updated->highPriority);
        self::assertSame(2, $updated->pendingFollowups);
        self::assertSame([0, 0, 1, 1], $updated->highPriorityByStage);
        self::assertSame([0, 1, 0, 1], $updated->pendingByStage);
        self::assertSame(0, $before[0]['stage']);
    }

    public function test_invalid_inputs_are_denied_by_the_existing_csv_validator(): void
    {
        $inspector = new SyntheticKanbanStageInspector();
        $row = self::fixture(1);
        $bad = [
            null,
            'synthetic',
            [array_merge($row, ['email' => 'private@example.test'])],
            [$row, $row],
            [array_merge($row, ['stage' => 4])],
            [array_merge($row, ['id' => 'F13'])],
            array_fill(0, 13, $row),
        ];

        foreach ($bad as $value) {
            try {
                $inspector->inspect($value);
                self::fail('Invalid synthetic stage snapshot accepted');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Previa CSV ficticia invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }
}
