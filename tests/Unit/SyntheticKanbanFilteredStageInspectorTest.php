<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticKanbanFilteredStageInspector;
use App\Core\Portability\SyntheticKanbanStageInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * F3-94: compare 45 UI-equivalent filter combinations with a reference reducer
 * using the eight real, wholly fabricated HTML Kanban fixture definitions.
 */
final class SyntheticKanbanFilteredStageInspectorTest extends TestCase
{
    /** @return list<array{id:string,stage:int,priority:string,followup:string,value:int,score:int}> */
    private static function fixtures(): array
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html');
        self::assertIsString($html);
        self::assertStringContainsString("priority==='all'||item.priority===priority", $html);
        self::assertStringContainsString("followupFilter==='all'||item.followup===followupFilter", $html);
        self::assertStringContainsString("stageFilter==='all'||item.stage===stageFilter", $html);
        self::assertSame(8, preg_match_all(
            "/Object\.freeze\(\{id:'(F\d\d)',label:'[^']*',stage:(\d),priority:'(high|normal)',value:(\d+),score:(\d+),followup:'(pending|done)'\}\)/",
            $html,
            $matches,
            PREG_SET_ORDER,
        ));
        $result = [];
        foreach ($matches as $item) {
            $result[] = [
                'id' => $item[1], 'stage' => (int) $item[2],
                'priority' => $item[3], 'value' => (int) $item[4],
                'score' => (int) $item[5], 'followup' => $item[6],
            ];
        }
        return $result;
    }

    public function test_filters_are_deterministic_and_match_existing_stage_summary_across_all_combinations(): void
    {
        $inspector = new SyntheticKanbanFilteredStageInspector();
        $reference = new SyntheticKanbanStageInspector();
        $rows = self::fixtures();
        $original = $rows;
        $seen = 0;
        foreach (['all', 'high', 'normal'] as $priority) {
            foreach (['all', 'pending', 'done'] as $followup) {
                foreach (['all', 0, 1, 2, 3] as $stage) {
                    $expected = array_values(array_filter(
                        $rows,
                        static fn (array $row): bool =>
                            ($priority === 'all' || $row['priority'] === $priority)
                            && ($followup === 'all' || $row['followup'] === $followup)
                            && ($stage === 'all' || $row['stage'] === $stage),
                    ));
                    $actual = $inspector->inspect($rows, $priority, $followup, $stage);
                    self::assertEquals($reference->inspect($expected), $actual);
                    self::assertSame($actual->total, array_sum($actual->stageCounts));
                    self::assertSame($actual->highPriority, array_sum($actual->highPriorityByStage));
                    self::assertSame($actual->pendingFollowups, array_sum($actual->pendingByStage));
                    self::assertFalse($actual->canReadCustomerData());
                    self::assertFalse($actual->canPersistLead());
                    ++$seen;
                }
            }
        }
        self::assertSame(45, $seen);
        self::assertSame($original, $rows);
        self::assertSame(8, $inspector->inspect($rows)->total);
        self::assertSame(1, $inspector->inspect($rows, 'high', 'pending', 2)->total);
        self::assertSame(0, $inspector->inspect($rows, 'high', 'done', 2)->total);
        self::assertSame(0, $inspector->inspect([])->total);
        self::assertStringNotContainsString('F01', json_encode($inspector->inspect($rows), JSON_THROW_ON_ERROR));
    }

    public function test_invalid_filters_are_generic_denials_without_reflecting_caller_values(): void
    {
        $inspector = new SyntheticKanbanFilteredStageInspector();
        $rows = self::fixtures();
        foreach ([
            ['HIGH', 'all', 'all'], [true, 'all', 'all'], ['all', 'DONE', 'all'],
            ['all', 'all', '1'], ['all', 'all', 4],
            ['all', ['private@example.test'], 'all'], ['all', 'all', null],
            ["\n=1+1", 'all', 'all'],
        ] as [$priority, $followup, $stage]) {
            try {
                $inspector->inspect($rows, $priority, $followup, $stage);
                self::fail('Invalid filter was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Filtro Kanban ficticio invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_invalid_hidden_rows_are_denied_before_any_filter_is_applied(): void
    {
        $inspector = new SyntheticKanbanFilteredStageInspector();
        $rows = self::fixtures();
        $rows[] = [
            'id' => 'F09', 'stage' => 3, 'priority' => 'normal',
            'followup' => 'done', 'value' => 100, 'score' => 50,
            'email' => 'private@example.test',
        ];
        try {
            $inspector->inspect($rows, 'high', 'pending', 0);
            self::fail('Invalid hidden row was ignored.');
        } catch (InvalidArgumentException $error) {
            self::assertSame('Previa CSV ficticia invalida.', $error->getMessage());
            self::assertStringNotContainsString('private@example.test', $error->getMessage());
        }
    }
}
