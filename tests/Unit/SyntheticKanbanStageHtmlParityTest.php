<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticKanbanStageInspector;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanStageHtmlParityTest extends TestCase
{
    public function test_eight_real_offline_fixture_definitions_match_count_only_core_projection(): void
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html',
        );
        self::assertIsString($html);

        self::assertSame(8, preg_match_all(
            "/Object\.freeze\(\{id:'(F\d\d)',label:'[^']*',stage:(\d),priority:'(high|normal)',value:(\d+),score:(\d+),followup:'(pending|done)'\}\)/",
            $html, $matches, PREG_SET_ORDER,
        ));

        $rows = [];
        foreach ($matches as $match) {
            $rows[] = [
                'id' => $match[1],
                'stage' => (int) $match[2],
                'priority' => $match[3],
                'value' => (int) $match[4],
                'score' => (int) $match[5],
                'followup' => $match[6],
            ];
        }

        $summary = (new SyntheticKanbanStageInspector())->inspect($rows);
        self::assertSame(8, $summary->total);
        self::assertSame([2, 2, 2, 2], $summary->stageCounts);
        self::assertSame([1, 1, 1, 1], $summary->highPriorityByStage);
        self::assertSame([1, 1, 2, 2], $summary->pendingByStage);
        self::assertSame(4, $summary->highPriority);
        self::assertSame(6, $summary->pendingFollowups);
        self::assertFalse($summary->canPersistLead());
        self::assertFalse($summary->canReadCustomerData());

        $encoded = json_encode($summary, JSON_THROW_ON_ERROR);
        foreach (['F01', 'F08', 'Oportunidade fictícia', 'stage:', 'label:'] as $private) {
            self::assertStringNotContainsString($private, $encoded);
        }
    }
}
