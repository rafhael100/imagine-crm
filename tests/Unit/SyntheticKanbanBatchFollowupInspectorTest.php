<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticKanbanBatchFollowupInspector;
use App\Core\Portability\SyntheticKanbanStageInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanBatchFollowupInspectorTest extends TestCase
{
    /** @return list<array{id:string,stage:int,priority:string,followup:string,value:int,score:int}> */
    private static function fixtures(): array
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html');
        self::assertIsString($html);
        self::assertStringContainsString("item.followup==='pending'", $html);
        self::assertSame(8, preg_match_all(
            "/Object\.freeze\(\{id:'(F\d\d)',label:'[^']*',stage:(\d),priority:'(high|normal)',value:(\d+),score:(\d+),followup:'(pending|done)'\}\)/",
            $html, $matches, PREG_SET_ORDER,
        ));

        $rows = [];
        foreach ($matches as $item) {
            $rows[] = [
                'id' => $item[1], 'stage' => (int) $item[2],
                'priority' => $item[3], 'value' => (int) $item[4],
                'score' => (int) $item[5], 'followup' => $item[6],
            ];
        }

        return $rows;
    }

    public function test_all_forty_five_filter_combinations_match_independent_reference(): void
    {
        $inspector = new SyntheticKanbanBatchFollowupInspector();
        $counts = new SyntheticKanbanStageInspector();
        $rows = self::fixtures();
        $original = $rows;
        $scenarios = 0;

        foreach (['all', 'high', 'normal'] as $priority) {
            foreach (['all', 'pending', 'done'] as $followup) {
                foreach (['all', 0, 1, 2, 3] as $stage) {
                    $after = $rows;
                    $finished = 0;
                    foreach ($rows as $index => $row) {
                        if (($priority === 'all' || $row['priority'] === $priority)
                            && ($followup === 'all' || $row['followup'] === $followup)
                            && ($stage === 'all' || $row['stage'] === $stage)
                            && $row['followup'] === 'pending') {
                            $after[$index]['followup'] = 'done';
                            ++$finished;
                        }
                    }

                    $result = $inspector->inspect($rows, $priority, $followup, $stage);
                    self::assertSame($finished, $result->completed);
                    self::assertEquals($counts->inspect($rows), $result->before);
                    self::assertEquals($counts->inspect($after), $result->after);
                    self::assertSame(8, $result->after->total);
                    self::assertFalse($result->canCompleteRealFollowups());
                    self::assertFalse($result->canPersistLead());
                    ++$scenarios;
                }
            }
        }

        self::assertSame(45, $scenarios);
        self::assertSame($original, $rows);
        self::assertSame(6, $inspector->inspect($rows)->completed);
        self::assertSame(0, $inspector->inspect($rows)->after->pendingFollowups);
        self::assertSame(0, $inspector->inspect($rows, 'all', 'done')->completed);
        self::assertSame(0, $inspector->inspect([])->completed);
        $encoded = json_encode($inspector->inspect($rows), JSON_THROW_ON_ERROR);
        foreach (['F01', 'F08', '1800', 'private@example.test'] as $secret) {
            self::assertStringNotContainsString($secret, $encoded);
        }
    }

    public function test_invalid_filters_and_hidden_payloads_fail_closed_without_echoing_data(): void
    {
        $inspector = new SyntheticKanbanBatchFollowupInspector();
        $rows = self::fixtures();
        try {
            $inspector->inspect($rows, ['private@example.test']);
            self::fail('Unapproved filter type accepted.');
        } catch (InvalidArgumentException $error) {
            self::assertSame('Filtro Kanban ficticio invalido.', $error->getMessage());
            self::assertStringNotContainsString('private@example.test', $error->getMessage());
        }

        $rows[] = [
            'id' => 'F09', 'stage' => 3, 'priority' => 'normal', 'followup' => 'done',
            'value' => 50, 'score' => 70, 'email' => 'private@example.test',
        ];
        try {
            $inspector->inspect($rows, 'high', 'pending', 0);
            self::fail('Bad hidden record bypassed validation.');
        } catch (InvalidArgumentException $error) {
            self::assertSame('Previa CSV ficticia invalida.', $error->getMessage());
            self::assertStringNotContainsString('private@example.test', $error->getMessage());
        }
    }
}
