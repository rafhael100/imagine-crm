<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvRowReviewPreview;
use PHPUnit\Framework\TestCase;

final class OfflineCsvRowReviewParityTest extends TestCase
{
    private static function html(): string
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
        self::assertIsString($content);

        return $content;
    }

    private static function browserRowReports(): array
    {
        self::assertSame(1, preg_match(
            '/const ROW_REPORTS = Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $matches,
        ));
        $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertCount(2, $data);

        return $data;
    }

    private static function cleanMatrix(): array
    {
        return [
            ['id', 'name', 'origin'],
            ['fiction-1', 'Sample One', 'sample-site'],
            ['fiction-2', 'Sample Two', 'sample-manual'],
        ];
    }

    private static function warningMatrix(): array
    {
        return [
            ['id', 'contact', 'id', ''],
            ['fiction-1', 'safe', '=SUM(A1:A2)', 'unused'],
            ['fiction-2', "'=HYPERLINK(\"https://invalid.example\")", 'other'],
            ['fiction-3', 'safe', 'ordinary', 'ok'],
        ];
    }

    private static function fullMapping(int $columns): array
    {
        $mapping = [];
        for ($i = 1; $i <= $columns; ++$i) {
            $mapping[] = [
                'column' => $i,
                'target' => 'fixture_field_'.$i,
            ];
        }

        return $mapping;
    }

    public function test_clean_and_warned_rows_equal_php_metadata_only_contract(): void
    {
        $reports = self::browserRowReports();
        self::assertSame(
            CsvRowReviewPreview::inspect(self::cleanMatrix(), self::fullMapping(3)),
            $reports[0],
        );
        self::assertSame(
            CsvRowReviewPreview::inspect(self::warningMatrix(), self::fullMapping(4)),
            $reports[1],
        );
    }

    public function test_clean_sample_is_never_marked_approved(): void
    {
        $clean = self::browserRowReports()[0];
        self::assertSame(0, $clean['rows_with_findings']);
        self::assertSame(2, $clean['rows_without_detected_findings']);
        self::assertSame([2, 3], array_column($clean['row_reviews'], 'row'));
        self::assertTrue($clean['review_required']);
        self::assertFalse($clean['import_permitted']);
        foreach ($clean['row_reviews'] as $review) {
            self::assertTrue($review['review_required']);
        }
        self::assertStringContainsString('Sem problemas detectados — revisão obrigatória', self::html());
    }

    public function test_warning_sample_exposes_only_row_positions_and_alert_types(): void
    {
        $report = self::browserRowReports()[1];
        self::assertSame(3, $report['data_rows']);
        self::assertSame(2, $report['rows_with_findings']);
        self::assertSame(1, $report['rows_without_detected_findings']);
        self::assertSame([2, 3, 4], array_column($report['row_reviews'], 'row'));
        self::assertSame(
            ['column_count_mismatch', 'risky_cells'],
            $report['row_reviews'][1]['issue_codes'],
        );
        self::assertSame([
            ['column' => 2, 'code' => 'apostrophe_formula_ambiguous'],
        ], $report['row_reviews'][1]['cell_findings']);
        self::assertTrue($report['review_required']);
        self::assertFalse($report['import_permitted']);

        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['fiction-1', 'fiction-2', 'SUM', 'HYPERLINK', 'contact', 'invalid.example', 'fixture_field'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
    }

    public function test_row_view_is_accessible_read_only_and_cannot_claim_import(): void
    {
        $html = self::html();
        self::assertStringContainsString('id="row-review-title"', $html);
        self::assertStringContainsString('id="row-review-status" role="status" aria-live="polite"', $html);
        self::assertStringContainsString('reviewTable.replaceChildren()', $html);
        self::assertStringContainsString('cell.textContent = value;', $html);
        self::assertStringContainsString('showRowReview(index);', $html);
        self::assertStringContainsString('nenhuma importação ou escrita permitida.', $html);
        self::assertStringContainsString('mesmo sem alertas', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach (['fetch(', '.innerHTML', 'insertAdjacentHTML', '<form', '<input', '<textarea', 'localStorage'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
