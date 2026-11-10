<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvSnapshotRevisionDeltaPreview;
use PHPUnit\Framework\TestCase;

final class OfflineCsvSnapshotComparisonParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string, mixed>> */
    private static function reports(): array
    {
        self::assertSame(1, preg_match(
            '/const SNAPSHOT_REPORTS = Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $matches,
        ));
        $reports = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($reports);
        self::assertCount(4, $reports);

        return $reports;
    }

    private static function rows(): array
    {
        return [
            ['id', 'name', 'origin'],
            ['fiction-1', 'Sample One', 'sample-site'],
            ['fiction-2', 'Sample Two', 'sample-manual'],
        ];
    }

    private static function mapping(): array
    {
        return [
            ['column' => 1, 'target' => 'fixture_one'],
            ['column' => 2, 'target' => 'fixture_two'],
            ['column' => 3, 'target' => 'fixture_three'],
        ];
    }

    public function test_four_browser_reports_match_real_php_fixture_comparisons(): void
    {
        $rows = self::rows();
        $mapping = self::mapping();

        $alteredRows = $rows;
        $alteredRows[1][1] = 'Sample One changed after mock review';

        $alteredMapping = $mapping;
        $alteredMapping[1]['target'] = 'fixture_four';

        $appendedRows = $rows;
        $appendedRows[] = ['fiction-3', 'Sample Three', 'sample-manual'];

        $actual = [
            CsvSnapshotRevisionDeltaPreview::compare($rows, $mapping, $rows, $mapping),
            CsvSnapshotRevisionDeltaPreview::compare($rows, $mapping, $alteredRows, $mapping),
            CsvSnapshotRevisionDeltaPreview::compare($rows, $mapping, $rows, $alteredMapping),
            CsvSnapshotRevisionDeltaPreview::compare($rows, $mapping, $appendedRows, $mapping),
        ];

        self::assertSame($actual, self::reports());
    }

    public function test_clean_unchanged_version_is_never_a_human_approval(): void
    {
        $clean = self::reports()[0];
        self::assertTrue($clean['same_snapshot']);
        self::assertFalse($clean['review_invalidated']);
        self::assertTrue($clean['review_required']);
        self::assertFalse($clean['human_verified']);
        self::assertFalse($clean['import_permitted']);
        self::assertStringContainsString('não comprova revisão humana', self::html());
    }

    public function test_modified_variants_are_always_invalidated_and_never_importable(): void
    {
        $reports = self::reports();
        self::assertSame([2], $reports[1]['changed_row_positions']);
        self::assertSame([2], $reports[2]['changed_mapping_positions']);
        self::assertSame([4], $reports[3]['added_row_positions']);
        self::assertSame(4, $reports[3]['after_row_count']);

        foreach (array_slice($reports, 1) as $report) {
            self::assertFalse($report['same_snapshot']);
            self::assertTrue($report['review_invalidated']);
            self::assertFalse($report['human_verified']);
            self::assertTrue($report['review_required']);
            self::assertFalse($report['import_permitted']);
        }
    }

    public function test_displays_metadata_without_cell_values_or_names(): void
    {
        $encoded = json_encode(self::reports(), JSON_THROW_ON_ERROR);
        foreach ([
            'Sample One', 'Sample Two', 'Sample Three',
            'fixture_one', 'fixture_two', 'fixture_three', 'fixture_four',
            'fiction-1', 'fiction-2', 'fiction-3',
            'sample-manual', 'sample-site',
        ] as $raw) {
            self::assertStringNotContainsString($raw, $encoded);
        }
        self::assertStringContainsString('td.textContent = value;', self::html());
        self::assertStringContainsString('snapshotRows.replaceChildren();', self::html());
    }

    public function test_browser_controls_and_disclaimers_keep_local_offline_boundary(): void
    {
        $html = self::html();
        self::assertSame(4, preg_match_all('/<button type="button" data-snapshot="[0-3]" aria-pressed="(?:true|false)">/', $html));
        self::assertStringContainsString('id="snapshot-status" role="status" aria-live="polite"', $html);
        self::assertStringContainsString('id="snapshot-diff-rows"', $html);
        self::assertStringContainsString('checksum público', strtolower($html));
        self::assertStringContainsString('Importação', $html);
        self::assertStringContainsString('Revisão humana continua necessária; importação proibida.', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', '.innerHTML',
            'insertAdjacentHTML', '<form', '<input', '<textarea',
            '<iframe', '<img', '<script src=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
