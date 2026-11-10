<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvRowDispositionPreview;
use PHPUnit\Framework\TestCase;

final class OfflineCsvReviewChoicesParityTest extends TestCase
{
    private static function html(): string
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
        self::assertIsString($source);

        return $source;
    }

    private static function rowReports(): array
    {
        self::assertSame(1, preg_match(
            '/const ROW_REPORTS = Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $m,
        ));
        $reports = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $reports);

        return $reports;
    }

    private static function fixture(int $choice): array
    {
        return $choice === 0
            ? [
                ['id', 'name', 'origin'],
                ['fiction-1', 'Sample One', 'sample-site'],
                ['fiction-2', 'Sample Two', 'sample-manual'],
            ]
            : [
                ['id', 'contact', 'id', ''],
                ['fiction-1', 'safe', '=SUM(A1:A2)', 'unused'],
                ['fiction-2', "'=HYPERLINK(\"https://invalid.example\")", 'other'],
                ['fiction-3', 'safe', 'ordinary', 'ok'],
            ];
    }

    private static function mapping(int $columns): array
    {
        $items = [];
        for ($i = 1; $i <= $columns; ++$i) {
            $items[] = ['column' => $i, 'target' => 'fixture_field_'.$i];
        }

        return $items;
    }

    public function test_both_initial_samples_start_with_all_rows_pending_and_no_import(): void
    {
        foreach (self::rowReports() as $index => $report) {
            $result = CsvRowDispositionPreview::summarize(
                self::fixture($index), self::mapping($report['expected_columns']),
            );
            self::assertSame($report['data_rows'], $result['pending_count']);
            self::assertSame(0, $result['reviewed_no_findings_count']);
            self::assertSame(0, $result['needs_correction_count']);
            self::assertSame($report['rows_with_findings'], $result['rows_with_detected_findings']);
            self::assertSame($report['global_issue_codes'], $result['global_issue_codes']);
            self::assertTrue($result['review_required']);
            self::assertFalse($result['human_verified']);
            self::assertFalse($result['import_permitted']);
        }
    }

    public function test_simulated_buttons_can_cover_all_clean_rows_without_authorizing_import(): void
    {
        $result = CsvRowDispositionPreview::summarize(
            self::fixture(0), self::mapping(3), [
                ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
                ['row' => 3, 'status' => 'fixture_reviewed_no_findings'],
            ],
        );
        self::assertSame(0, $result['pending_count']);
        self::assertSame(2, $result['reviewed_no_findings_count']);
        self::assertSame(0, $result['needs_correction_count']);
        self::assertFalse($result['human_verified']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_simulated_correction_for_flagged_rows_and_clean_row_review_reconcile(): void
    {
        $result = CsvRowDispositionPreview::summarize(
            self::fixture(1), self::mapping(4), [
                ['row' => 2, 'status' => 'fixture_needs_correction'],
                ['row' => 3, 'status' => 'fixture_needs_correction'],
                ['row' => 4, 'status' => 'fixture_reviewed_no_findings'],
            ],
        );
        self::assertSame(0, $result['pending_count']);
        self::assertSame(1, $result['reviewed_no_findings_count']);
        self::assertSame(2, $result['needs_correction_count']);
        self::assertSame(2, $result['rows_with_detected_findings']);
        self::assertSame(
            ['empty_header', 'duplicate_header', 'ragged_data_rows', 'risky_cells'],
            $result['global_issue_codes'],
        );
        self::assertTrue($result['review_required']);
        self::assertFalse($result['human_verified']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_browser_declares_non_persistent_untrusted_review_choices_and_kills_stale_state(): void
    {
        $html = self::html();
        foreach ([
            'id="disposition-title"',
            'id="decision-pending"',
            'id="decision-reviewed"',
            'id="decision-correction"',
            'id="review-decision-rows"',
            'id="decision-status" role="status" aria-live="polite"',
            'const reviewSelections = new Map();',
            'reviewSelections.clear();',
            'button.disabled = next === "fixture_reviewed_no_findings" && flagged;',
            'button.setAttribute("aria-pressed", String(next === choice));',
            'button.setAttribute("aria-label", label + " — linha fictícia " + info.row);',
            'cell.textContent = value;',
            'resetReviewChoices(index);',
            'Importação sempre proibida',
            'identidade não verificada.',
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }

        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', '.innerHTML',
            'insertAdjacentHTML', '<form', '<input', '<textarea',
            'localStorage', 'sessionStorage'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }
}
