<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticKanbanBatchMoveInspector;
use App\Core\Portability\SyntheticKanbanStageInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanBatchMoveInspectorTest extends TestCase
{
    /** @return list<array{id:string,stage:int,priority:string,followup:string,value:int,score:int}> */
    private static function fixtures(): array
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html');
        self::assertIsString($html);
        self::assertStringContainsString('item.stage+direction>=0&&item.stage+direction<STAGES.length', $html);
        self::assertSame(8, preg_match_all(
            "/Object\.freeze\(\{id:'(F\d\d)',label:'[^']*',stage:(\d),priority:'(high|normal)',value:(\d+),score:(\d+),followup:'(pending|done)'\}\)/",
            $html,
            $matches,
            PREG_SET_ORDER,
        ));
        $rows = [];
        foreach ($matches as $item) {
            $rows[] = [
                'id' => $item[1],
                'stage' => (int) $item[2],
                'priority' => $item[3],
                'value' => (int) $item[4],
                'score' => (int) $item[5],
                'followup' => $item[6],
            ];
        }

        return $rows;
    }

    public function test_all_ninety_filter_direction_combinations_match_reference_movement(): void
    {
        $rows = self::fixtures();
        $original = $rows;
        $inspector = new SyntheticKanbanBatchMoveInspector();
        $counts = new SyntheticKanbanStageInspector();
        $scenarios = 0;

        foreach ([-1, 1] as $direction) {
            foreach (['all', 'high', 'normal'] as $priority) {
                foreach (['all', 'pending', 'done'] as $followup) {
                    foreach (['all', 0, 1, 2, 3] as $stage) {
                        $after = $rows;
                        $expectedMoves = 0;
                        foreach ($rows as $index => $row) {
                            $destination = $row['stage'] + $direction;
                            if (($priority === 'all' || $priority === $row['priority'])
                                && ($followup === 'all' || $followup === $row['followup'])
                                && ($stage === 'all' || $stage === $row['stage'])
                                && $destination >= 0 && $destination < 4) {
                                $after[$index]['stage'] = $destination;
                                ++$expectedMoves;
                            }
                        }
                        $summary = $inspector->inspect($rows, $direction, $priority, $followup, $stage);
                        self::assertSame($expectedMoves, $summary->moved);
                        self::assertEquals($counts->inspect($rows), $summary->before);
                        self::assertEquals($counts->inspect($after), $summary->after);
                        self::assertSame(8, $summary->after->total);
                        self::assertFalse($summary->canChangeRealStages());
                        self::assertFalse($summary->canPersistLead());
                        ++$scenarios;
                    }
                }
            }
        }

        self::assertSame(90, $scenarios);
        self::assertSame($original, $rows);
        $encoded = json_encode($inspector->inspect($rows, 1), JSON_THROW_ON_ERROR);
        foreach (['F01', 'F08', '1800', 'email', 'tenant'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded);
        }
    }

    public function test_all_forward_and_backward_moves_respect_stage_boundaries(): void
    {
        $inspector = new SyntheticKanbanBatchMoveInspector();
        $rows = self::fixtures();

        self::assertSame(6, $inspector->inspect($rows, 1)->moved);
        self::assertSame(6, $inspector->inspect($rows, -1)->moved);
        self::assertSame(0, $inspector->inspect($rows, 1, 'all', 'all', 3)->moved);
        self::assertSame(0, $inspector->inspect($rows, -1, 'all', 'all', 0)->moved);
        self::assertSame(0, $inspector->inspect([] , 1)->moved);
    }

    public function test_invalid_direction_or_filters_are_denied_without_echoing_payload(): void
    {
        $inspector = new SyntheticKanbanBatchMoveInspector();
        $rows = self::fixtures();
        foreach ([0, 2, -2, '1', null, true, ['private@example.test']] as $direction) {
            try {
                $inspector->inspect($rows, $direction);
                self::fail('Invalid stage movement accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Movimentacao Kanban ficticia invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filtro Kanban ficticio invalido.');
        $inspector->inspect($rows, 1, 'ALL');
    }

    public function test_invalid_row_hidden_by_filter_still_fails_closed(): void
    {
        $rows = self::fixtures();
        $rows[] = [
            'id' => 'F09', 'stage' => 3, 'priority' => 'normal',
            'followup' => 'done', 'score' => 60, 'value' => 5,
            'email' => 'private@example.test',
        ];
        try {
            (new SyntheticKanbanBatchMoveInspector())->inspect($rows, 1, 'high', 'pending', 0);
            self::fail('Invalid hidden record bypassed validation.');
        } catch (InvalidArgumentException $error) {
            self::assertSame('Previa CSV ficticia invalida.', $error->getMessage());
            self::assertStringNotContainsString('private@example.test', $error->getMessage());
        }
    }
}
