<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-95: the per-lane summary is DOM text derived exclusively from the
 * already filtered, fictional Kanban array; never a CRM business view.
 */
final class OfflineKanbanLaneSummaryTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    public function test_all_four_lanes_expose_distinct_accessible_text_summaries(): void
    {
        $html = self::html();

        for ($index = 0; $index < 4; ++$index) {
            self::assertSame(1, substr_count(
                $html,
                'id="lane-detail-'.$index.'" class="lane-detail">Alta: 0 · Pendentes: 0',
            ));
            self::assertStringContainsString('id="lane-count-'.$index.'"', $html);
            self::assertStringContainsString('id="lane-value-'.$index.'"', $html);
        }
        self::assertStringContainsString("const highInLane=items.filter(function(item){return item.priority==='high';}).length;", $html);
        self::assertStringContainsString("const pendingInLane=items.filter(function(item){return item.followup==='pending';}).length;", $html);
        self::assertStringContainsString(
            "put('lane-detail-'+index,'Alta: '+highInLane+' · Pendentes: '+pendingInLane);",
            $html,
        );
        self::assertStringContainsString("const items=subset.filter(function(item){return item.stage===index;});", $html);
        self::assertStringContainsString("priority==='all'||item.priority===priority", $html);
        self::assertStringContainsString("followupFilter==='all'||item.followup===followupFilter", $html);
        self::assertStringContainsString("stageFilter==='all'||item.stage===stageFilter", $html);
    }

    public function test_lane_metrics_remain_local_and_do_not_introduce_capture_or_networking(): void
    {
        $html = self::html();
        // The single bounded search control adds ~1.3 KiB; keep a tight total size cap.
        self::assertLessThanOrEqual(40000, strlen($html));
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('sessionStorage', $html);
        self::assertStringNotContainsString('fetch(', $html);
        self::assertStringNotContainsString('<form', $html);
    }
}
