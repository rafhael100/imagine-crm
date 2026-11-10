<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * Guards the static demonstration numbers against drift from the
 * synthetic PHP projection. Not a production or browser integration.
 */
final class OfflineFollowUpDashboardParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f4-15-followup-dashboard/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_js_fixtures_table_and_php_snapshot_counts_agree(): void
    {
        $html = self::html();
        preg_match_all(
            '/Object\.freeze\(\{overdue:(\d+),due24:(\d+),later:(\d+),total:(\d+)\}\)/',
            $html,
            $fixtures,
            PREG_SET_ORDER
        );
        preg_match_all(
            '/<tr><th scope="row">([ABC])<\/th><td>(\d+)<\/td><td>(\d+)<\/td><td>(\d+)<\/td><td>(\d+)<\/td><\/tr>/',
            $html,
            $rows,
            PREG_SET_ORDER
        );

        self::assertCount(3, $fixtures);
        self::assertCount(3, $rows);

        foreach ($fixtures as $index => $match) {
            $overdue = (int) $match[1];
            $soon = (int) $match[2];
            $later = (int) $match[3];
            $total = (int) $match[4];

            self::assertSame($overdue + $soon + $later, $total);
            self::assertSame(chr(65 + $index), $rows[$index][1]);
            self::assertSame(
                [$overdue, $soon, $later, $total],
                array_map('intval', array_slice($rows[$index], 2, 4))
            );

            $items = [];
            $dates = [
                'overdue' => ['count' => $overdue, 'due_at' => '2026-11-10T09:00:00Z'],
                'due_24h' => ['count' => $soon, 'due_at' => '2026-11-11T09:00:00Z'],
                'due_later' => ['count' => $later, 'due_at' => '2026-11-12T10:00:00Z'],
            ];
            foreach ($dates as $bucket => $definition) {
                for ($i = 0; $i < $definition['count']; ++$i) {
                    $items[] = [
                        'id' => sprintf('synthetic-%d-%s-%d', $index, $bucket, $i),
                        'due_at' => $definition['due_at'],
                    ];
                }
            }
            self::assertSame([
                'overdue' => $overdue,
                'due_24h' => $soon,
                'due_later' => $later,
                'total' => $total,
            ], FollowUpSnapshot::build($items, '2026-11-10T10:00:00Z')['counts']);
        }
    }

    public function test_initial_meter_values_match_first_fixture_and_are_labeled(): void
    {
        $html = self::html();
        preg_match_all(
            '/<meter id="meter-(overdue|due24|later)" min="0" max="9" value="(\d+)">/',
            $html,
            $meters,
            PREG_SET_ORDER
        );
        self::assertCount(3, $meters);
        self::assertSame(['overdue', 'due24', 'later'], array_column($meters, 1));
        self::assertSame(['2', '3', '4'], array_column($meters, 2));

        foreach (['overdue', 'due24', 'later'] as $bucket) {
            self::assertStringContainsString('for="meter-'.$bucket.'"', $html);
            self::assertStringContainsString('id="text-'.$bucket.'"', $html);
        }
        self::assertStringContainsString('meter.max = Math.max(current.total, 1);', $html);
        self::assertStringContainsString('meter.value = current[key];', $html);
        self::assertStringContainsString('document.getElementById("text-" + key).textContent', $html);
    }

    public function test_chart_does_not_introduce_network_or_html_injection_sinks(): void
    {
        $html = self::html();
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage',
            '.innerHTML', '.outerHTML', 'insertAdjacentHTML', '<form', '<script src='] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }
}
