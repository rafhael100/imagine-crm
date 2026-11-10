<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpFixtureWorklistPage;
use PHPUnit\Framework\TestCase;

/**
 * Parity between a fixed browser fixture and the offline PHP projection.
 * This is NOT authorization or proof of safe handling of real records.
 */
final class OfflineFollowUpWorklistParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f4-28-followup-worklist/index.html');
        self::assertIsString($html);

        return $html;
    }

    private static function sample(): array
    {
        preg_match_all(
            '/Object\.freeze\(\{id:"([^"]+)",due_at:"([^"]+)",scope:"([^"]+)"\}\)/',
            self::html(),
            $matches,
            PREG_SET_ORDER,
        );
        self::assertCount(5, $matches, 'The worklist demonstration must contain five explicit synthetic records.');

        return array_map(
            static fn (array $match): array => [
                'id' => $match[1],
                'due_at' => $match[2],
                'scope' => $match[3],
            ],
            $matches,
        );
    }

    public function test_js_records_match_php_sorted_pages_and_bucket_counts(): void
    {
        $records = self::sample();
        $pages = [];
        foreach ([1, 2, 3] as $page) {
            $pages[] = FollowUpFixtureWorklistPage::paginate(
                'fixture-sales-a', $records, '2026-11-10T10:00:00Z', 'all', $page, 2,
            );
        }

        self::assertSame([
            ['fixture-overdue', 'fixture-now'],
            ['fixture-a', 'fixture-b'],
            ['fixture-late'],
        ], array_map(
            static fn (array $result): array => array_column($result['items'], 'id'),
            $pages,
        ));
        self::assertSame([
            'overdue' => 1, 'due_24h' => 3, 'due_later' => 1, 'total' => 5,
        ], $pages[0]['counts']);

        self::assertSame(3, $pages[0]['pages']);
        self::assertSame(5, $pages[0]['selected_total']);
        self::assertFalse($pages[0]['has_previous']);
        self::assertTrue($pages[0]['has_next']);
        self::assertTrue($pages[2]['has_previous']);
        self::assertFalse($pages[2]['has_next']);
    }

    public function test_each_fixture_filter_matches_php_counts_and_resets_to_first_page(): void
    {
        $records = self::sample();
        foreach ([
            'all' => ['fixture-overdue', 'fixture-now'],
            'overdue' => ['fixture-overdue'],
            'due_24h' => ['fixture-now', 'fixture-a'],
            'due_later' => ['fixture-late'],
        ] as $bucket => $expected) {
            $result = FollowUpFixtureWorklistPage::paginate(
                'fixture-sales-a', $records, '2026-11-10T10:00:00Z', $bucket, 1, 2,
            );
            self::assertSame($expected, array_column($result['items'], 'id'));
            self::assertSame(1, $result['page']);
            self::assertSame(5, $result['counts']['total']);
        }

        $empty = FollowUpFixtureWorklistPage::paginate(
            'fixture-sales-a', [], '2026-11-10T10:00:00Z', 'all', 1, 2,
        );
        self::assertSame(0, $empty['selected_total']);
        self::assertSame(1, $empty['pages']);
        self::assertSame([], $empty['items']);
    }

    public function test_ui_is_explicitly_fictional_local_only_and_keyboard_operable(): void
    {
        $html = self::html();
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        self::assertStringContainsString("default-src 'none'", $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringContainsString('button:focus-visible', $html);
        self::assertStringContainsString('aria-live="polite"', $html);
        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('rows.replaceChildren()', $html);
        self::assertStringContainsString('cell.textContent = value;', $html);
        self::assertSame(2, preg_match_all('/<button type="button" data-sample="(?:a|empty)" aria-pressed="(?:true|false)">/', $html));
        self::assertSame(4, preg_match_all('/<button type="button" data-filter="(?:all|overdue|due_24h|due_later)" aria-pressed="(?:true|false)">/', $html));
    }

    public function test_no_network_browser_storage_html_injection_or_data_capture_surface(): void
    {
        $html = self::html();
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource', 'localStorage',
            'sessionStorage', 'navigator.sendBeacon', '.innerHTML', '.outerHTML',
            'insertAdjacentHTML', '<form', '<input', '<textarea', '<iframe',
            '<script src=', '<link rel="stylesheet"', '<img',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html, $forbidden.' is forbidden offline');
        }

        preg_match_all('/\bhref="([^"]+)"/', $html, $links);
        self::assertSame(
            ['#main', '../f4-15-followup-dashboard/index.html'],
            $links[1],
        );
    }
}
