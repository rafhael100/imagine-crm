<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvStructurePreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvStructurePreviewTest extends TestCase
{
    public function test_empty_matrix_has_explicitly_missing_header(): void
    {
        self::assertSame([
            'row_count' => 0,
            'data_row_count' => 0,
            'header_columns' => 0,
            'header_missing' => true,
            'ragged_data_rows' => [],
            'empty_header_columns' => [],
            'duplicate_header_groups' => [],
            'flagged_cell_count' => 0,
            'cell_findings' => [],
        ], CsvStructurePreview::summarize([]));
    }

    public function test_valid_synthetic_table_reports_only_counts(): void
    {
        $result = CsvStructurePreview::summarize([
            ['id', 'name', 'origin'],
            ['fiction-1', 'Sample One', 'sample-site'],
            ['fiction-2', 'Sample Two', 'sample-manual'],
        ]);

        self::assertSame(3, $result['row_count']);
        self::assertSame(2, $result['data_row_count']);
        self::assertSame(3, $result['header_columns']);
        self::assertFalse($result['header_missing']);
        self::assertSame([], $result['ragged_data_rows']);
        self::assertSame([], $result['empty_header_columns']);
        self::assertSame([], $result['duplicate_header_groups']);
        self::assertSame(0, $result['flagged_cell_count']);

        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['Sample One', 'sample-site', 'fiction-2', 'origin', 'name'] as $raw) {
            self::assertStringNotContainsString($raw, $encoded);
        }
    }

    public function test_reconciles_header_problems_ragged_rows_and_formula_findings(): void
    {
        $result = CsvStructurePreview::summarize([
            ['id', 'contact', 'id', '', ''],
            ['fiction-1', 'safe', '=SUM(A1:A2)', 'unused', 'unused'],
            ['fiction-2', 'safe', 'ordinary', 'short'],
            ['fiction-3', 'safe', 'ordinary', 'extra', 'extra', 'extra'],
        ]);

        self::assertSame(4, $result['row_count']);
        self::assertSame(3, $result['data_row_count']);
        self::assertSame(5, $result['header_columns']);
        self::assertSame([3, 4], $result['ragged_data_rows']);
        self::assertSame([4, 5], $result['empty_header_columns']);
        self::assertSame([[1, 3], [4, 5]], $result['duplicate_header_groups']);
        self::assertSame(1, $result['flagged_cell_count']);
        self::assertSame([
            ['row' => 2, 'column' => 3, 'code' => 'formula_prefix'],
        ], $result['cell_findings']);
        self::assertStringNotContainsString('SUM', json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function test_duplicate_groups_report_all_positions_once_in_column_order(): void
    {
        $result = CsvStructurePreview::summarize([
            ['A', 'B', 'A', 'A', 'B'],
        ]);

        self::assertSame([[1, 3, 4], [2, 5]], $result['duplicate_header_groups']);
        self::assertSame([], $result['ragged_data_rows']);
    }

    public function test_header_case_is_not_silently_normalized(): void
    {
        $result = CsvStructurePreview::summarize([
            ['Name', 'name', 'NAME'],
        ]);
        self::assertSame([], $result['duplicate_header_groups']);
        self::assertSame(3, $result['header_columns']);
    }

    public function test_whitespace_header_is_not_silently_trimmed(): void
    {
        $result = CsvStructurePreview::summarize([['', ' ', "\t", 'id']]);
        self::assertSame([1], $result['empty_header_columns']);
        self::assertSame(1, $result['flagged_cell_count']);
        self::assertSame([['row' => 1, 'column' => 3, 'code' => 'control_byte']], $result['cell_findings']);
    }

    public function test_zero_column_header_cannot_be_silently_treated_as_valid(): void
    {
        $result = CsvStructurePreview::summarize([
            [],
            [],
            ['late'],
        ]);
        self::assertTrue($result['header_missing']);
        self::assertSame(0, $result['header_columns']);
        self::assertSame(2, $result['data_row_count']);
        self::assertSame([3], $result['ragged_data_rows']);
    }

    public function test_single_header_row_counts_no_data_rows(): void
    {
        $result = CsvStructurePreview::summarize([['id', 'due_at']]);
        self::assertSame(1, $result['row_count']);
        self::assertSame(0, $result['data_row_count']);
        self::assertFalse($result['header_missing']);
    }

    public function test_propagates_rejection_of_non_list_cells(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvStructurePreview::summarize([['email' => 'fiction']]);
    }

    public function test_propagates_rejection_of_non_string_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvStructurePreview::summarize([['header'], [123]]);
    }

    public function test_propagates_rejection_of_oversized_cell(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvStructurePreview::summarize([['header'], [str_repeat('x', 4097)]]);
    }

    public function test_accepts_exactly_one_hundred_rows_without_importing_anything(): void
    {
        $rows = [['id', 'content']];
        for ($i = 1; $i < 100; ++$i) {
            $rows[] = ['fiction-'.$i, 'safe'];
        }
        $result = CsvStructurePreview::summarize($rows);
        self::assertSame(100, $result['row_count']);
        self::assertSame(99, $result['data_row_count']);
        self::assertSame([], $result['ragged_data_rows']);
        self::assertSame(0, $result['flagged_cell_count']);
    }

    public function test_rejects_more_than_one_hundred_rows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvStructurePreview::summarize(array_fill(0, 101, ['fiction']));
    }
}
