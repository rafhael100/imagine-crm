<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvRowReviewPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvRowReviewPreviewTest extends TestCase
{
    private static function fixtureMapping(): array
    {
        return [
            ['column' => 1, 'target' => 'fixture_alpha'],
            ['column' => 2, 'target' => 'fixture_beta'],
            ['column' => 3, 'target' => 'fixture_gamma'],
        ];
    }

    private static function fixtureRows(): array
    {
        return [
            ['header_a', 'header_b', 'header_c'],
            ['fiction-a', 'safe', 'normal'],
            ['fiction-b', '=1+1', 'normal'],
            ['fiction-c', 'only-two'],
            ['fiction-d', "\x00hidden", "'=SUM(1,1)"],
        ];
    }

    public function test_clean_rows_are_not_marked_importable_or_authorized(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['id', 'type', 'source'],
            ['fiction-a', 'normal', 'normal'],
        ], self::fixtureMapping());

        self::assertSame(1, $result['data_rows']);
        self::assertSame(0, $result['rows_with_findings']);
        self::assertSame(1, $result['rows_without_detected_findings']);
        self::assertSame([], $result['global_issue_codes']);
        self::assertTrue($result['review_required']);
        self::assertFalse($result['import_permitted']);
        self::assertSame([[
            'row' => 2,
            'actual_columns' => 3,
            'issue_codes' => [],
            'cell_findings' => [],
            'review_required' => true,
        ]], $result['row_reviews']);
    }

    public function test_rows_with_formula_and_mismatched_width_are_grouped_by_position(): void
    {
        $result = CsvRowReviewPreview::inspect(self::fixtureRows(), self::fixtureMapping());

        self::assertSame(4, $result['data_rows']);
        self::assertSame(3, $result['expected_columns']);
        self::assertSame(3, $result['rows_with_findings']);
        self::assertSame(1, $result['rows_without_detected_findings']);
        self::assertSame(0, $result['header_cell_warning_count']);
        self::assertSame(['ragged_data_rows', 'risky_cells'], $result['global_issue_codes']);

        self::assertSame([
            ['row' => 2, 'actual_columns' => 3, 'issue_codes' => [],
                'cell_findings' => [], 'review_required' => true],
            ['row' => 3, 'actual_columns' => 3, 'issue_codes' => ['risky_cells'],
                'cell_findings' => [['column' => 2, 'code' => 'formula_prefix']],
                'review_required' => true],
            ['row' => 4, 'actual_columns' => 2, 'issue_codes' => ['column_count_mismatch'],
                'cell_findings' => [], 'review_required' => true],
            ['row' => 5, 'actual_columns' => 3, 'issue_codes' => ['risky_cells'],
                'cell_findings' => [
                    ['column' => 2, 'code' => 'nul_byte'],
                    ['column' => 3, 'code' => 'apostrophe_formula_ambiguous'],
                ], 'review_required' => true],
        ], $result['row_reviews']);
    }

    public function test_mismatched_and_unsafe_same_row_are_counted_once(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['first', 'second', 'third'],
            ['fiction', '=SUM(1,1)'],
        ]);

        self::assertSame(1, $result['rows_with_findings']);
        self::assertSame(0, $result['rows_without_detected_findings']);
        self::assertSame(
            ['column_count_mismatch', 'risky_cells'],
            $result['row_reviews'][0]['issue_codes'],
        );
        self::assertSame([['column' => 2, 'code' => 'formula_prefix']],
            $result['row_reviews'][0]['cell_findings']);
    }

    public function test_header_cell_warnings_are_not_mislabeled_as_data_row_warnings(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['=malicious', 'safe'],
            ['fiction', 'normal'],
        ]);

        self::assertSame(1, $result['header_cell_warning_count']);
        self::assertSame(0, $result['rows_with_findings']);
        self::assertSame([], $result['row_reviews'][0]['cell_findings']);
        self::assertSame(['risky_cells'], $result['global_issue_codes']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_zero_rows_report_missing_header_and_no_implicit_success(): void
    {
        $result = CsvRowReviewPreview::inspect([]);
        self::assertSame(0, $result['data_rows']);
        self::assertSame(0, $result['expected_columns']);
        self::assertSame([], $result['row_reviews']);
        self::assertSame(['missing_header'], $result['global_issue_codes']);
        self::assertFalse($result['import_permitted']);
        self::assertTrue($result['review_required']);
    }

    public function test_header_only_has_no_data_rows_but_still_requires_review(): void
    {
        $result = CsvRowReviewPreview::inspect([['id', 'email']]);
        self::assertSame(0, $result['data_rows']);
        self::assertSame([], $result['row_reviews']);
        self::assertSame(2, $result['expected_columns']);
        self::assertTrue($result['review_required']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_duplicate_header_and_mapping_conflicts_remain_global_not_fake_row_issues(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['same', 'same'],
            ['fiction', 'safe'],
        ], [
            ['column' => 1, 'target' => 'fixture_duplicate'],
            ['column' => 1, 'target' => 'fixture_duplicate'],
        ]);

        self::assertSame([
            'duplicate_header', 'duplicate_source_mapping', 'duplicate_target_mapping',
        ], $result['global_issue_codes']);
        self::assertSame([2], $result['unmapped_columns']);
        self::assertSame(0, $result['rows_with_findings']);
        self::assertSame([], $result['row_reviews'][0]['issue_codes']);
    }

    public function test_unmapped_columns_do_not_authorize_column_discard(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['id', 'source', 'note'],
            ['fiction', 'fiction', 'fiction'],
        ], [['column' => 1, 'target' => 'fixture_alpha']]);

        self::assertSame([2, 3], $result['unmapped_columns']);
        self::assertTrue($result['review_required']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_empty_header_cells_are_only_global_warnings(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['id', ''],
            ['fiction', 'safe'],
        ]);
        self::assertSame(['empty_header'], $result['global_issue_codes']);
        self::assertSame(0, $result['rows_with_findings']);
    }

    public function test_report_never_contains_raw_header_cells_or_mapping_targets(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['secret_header_foo', 'secret_header_bar'],
            ['fake-customer-id', '=HYPERLINK("https://bad.invalid")'],
        ], [
            ['column' => 1, 'target' => 'fixture_secret_target'],
            ['column' => 2, 'target' => 'fixture_second_target'],
        ]);

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([
            'secret_header_foo', 'secret_header_bar', 'fake-customer-id',
            'HYPERLINK', 'bad.invalid', 'fixture_secret_target', 'fixture_second_target',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
        self::assertSame(1, $result['rows_with_findings']);
        self::assertSame(2, $result['row_reviews'][0]['row']);
    }

    public function test_one_based_positions_refer_to_original_matrix_even_after_clean_rows(): void
    {
        $result = CsvRowReviewPreview::inspect([
            ['id', 'status'],
            ['fiction-1', 'safe'],
            ['fiction-2', 'safe'],
            ['fiction-3', '@something'],
        ]);
        self::assertSame([2, 3, 4], array_column($result['row_reviews'], 'row'));
        self::assertSame(4, $result['row_reviews'][2]['row']);
        self::assertSame([['column' => 2, 'code' => 'formula_prefix']],
            $result['row_reviews'][2]['cell_findings']);
    }

    public function test_last_allowed_row_index_is_preserved(): void
    {
        $rows = [['id']];
        for ($i = 1; $i < 100; ++$i) {
            $rows[] = ['fiction-'.$i];
        }

        $result = CsvRowReviewPreview::inspect($rows);
        self::assertSame(99, $result['data_rows']);
        self::assertCount(99, $result['row_reviews']);
        self::assertSame(100, $result['row_reviews'][98]['row']);
        self::assertSame(99, $result['rows_without_detected_findings']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_rejects_oversize_matrix_without_echoing_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect(array_fill(0, 101, ['fiction']));
    }

    public function test_rejects_more_than_twenty_five_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect([array_fill(0, 26, 'fiction')]);
    }

    public function test_rejects_oversize_cell(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect([['id'], [str_repeat('s', 4097)]]);
    }

    public function test_rejects_non_string_cell(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect([['id'], [10]]);
    }

    public function test_rejects_mapping_to_real_field_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect([['id']], [['column' => 1, 'target' => 'lead.email']]);
    }

    public function test_rejects_unknown_mapping_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect([['id']], [[
            'column' => 1, 'target' => 'fixture_alpha', 'principal' => 'untrusted',
        ]]);
    }

    public function test_rejects_nonexistent_column_mapping(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowReviewPreview::inspect([['id']], [['column' => 2, 'target' => 'fixture_alpha']]);
    }

    public function test_same_input_is_deterministic(): void
    {
        $first = CsvRowReviewPreview::inspect(self::fixtureRows(), self::fixtureMapping());
        $second = CsvRowReviewPreview::inspect(self::fixtureRows(), self::fixtureMapping());
        self::assertSame($first, $second);
        self::assertSame([
            'data_rows', 'expected_columns', 'rows_with_findings',
            'rows_without_detected_findings', 'header_cell_warning_count',
            'global_issue_codes', 'unmapped_columns', 'row_reviews',
            'review_required', 'import_permitted',
        ], array_keys($first));
    }
}
