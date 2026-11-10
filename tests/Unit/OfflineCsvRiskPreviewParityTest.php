<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvStructurePreview;
use PHPUnit\Framework\TestCase;

final class OfflineCsvRiskPreviewParityTest extends TestCase
{
    private static function html(): string
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
        self::assertIsString($content);

        return $content;
    }

    /** @return list<array<string,mixed>> */
    private static function browserReports(): array
    {
        $html = self::html();
        self::assertSame(1, preg_match(
            '/const REPORTS = Object\.freeze\((\[[\s\S]*?\])\);/',
            $html,
            $matches,
        ));

        $reports = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($reports);
        self::assertCount(2, $reports);

        return $reports;
    }

    private static function cleanFixture(): array
    {
        return [
            ['id', 'name', 'origin'],
            ['fiction-1', 'Sample One', 'sample-site'],
            ['fiction-2', 'Sample Two', 'sample-manual'],
        ];
    }

    private static function alertFixture(): array
    {
        return [
            ['id', 'contact', 'id', ''],
            ['fiction-1', 'safe', '=SUM(A1:A2)', 'unused'],
            ['fiction-2', "'=HYPERLINK(\"https://invalid.example\")", 'other'],
            ['fiction-3', 'safe', 'ordinary', 'ok'],
        ];
    }

    public function test_browser_reports_equal_the_synthetic_php_analysis(): void
    {
        $reports = self::browserReports();
        self::assertSame(CsvStructurePreview::summarize(self::cleanFixture()), $reports[0]);
        self::assertSame(CsvStructurePreview::summarize(self::alertFixture()), $reports[1]);
    }

    public function test_clean_preview_is_not_mistaken_for_authorization_to_import(): void
    {
        $reports = self::browserReports();
        self::assertSame(0, $reports[0]['flagged_cell_count']);
        self::assertSame([], $reports[0]['duplicate_header_groups']);
        $html = self::html();
        self::assertStringContainsString('Isso não é aprovação de importação.', $html);
        self::assertStringContainsString('O resultado não certifica segurança de um CSV real.', $html);
    }

    public function test_alert_report_contains_only_positions_and_codes_not_values(): void
    {
        $report = self::browserReports()[1];
        self::assertSame(2, $report['flagged_cell_count']);
        self::assertSame([[1, 3]], $report['duplicate_header_groups']);
        self::assertSame([3], $report['ragged_data_rows']);
        self::assertSame([4], $report['empty_header_columns']);
        self::assertSame([
            ['row' => 2, 'column' => 3, 'code' => 'formula_prefix'],
            ['row' => 3, 'column' => 2, 'code' => 'apostrophe_formula_ambiguous'],
        ], $report['cell_findings']);

        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['fiction-1', 'contact', 'HYPERLINK', 'SUM(A1', 'invalid.example'] as $raw) {
            self::assertStringNotContainsString($raw, $encoded);
        }
    }

    public function test_browser_has_two_keyboard_accessible_fiction_only_examples(): void
    {
        $html = self::html();
        self::assertSame(2, preg_match_all(
            '/<button type="button" data-sample="[01]" aria-pressed="(?:true|false)">/',
            $html,
        ));
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        self::assertStringContainsString('role="status" aria-live="polite"', $html);
        self::assertStringContainsString('button:focus-visible', $html);
        self::assertStringContainsString('table.replaceChildren()', $html);
        self::assertStringContainsString('cell.textContent = text;', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }

    public function test_browser_has_no_network_form_storage_or_remote_resource(): void
    {
        $html = self::html();
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource', 'localStorage',
            'sessionStorage', '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img', '<script src=',
            '<link', 'onload=', 'onclick=', '@import', 'url(', 'download=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }

        preg_match_all('/\bhref="([^"]+)"/', $html, $links);
        self::assertSame(
            ['#main', '../s01-prot-01/index.html'],
            $links[1],
        );
    }
}
