<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvCellRiskPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvCellRiskPreviewTest extends TestCase
{
    public function test_empty_preview_returns_no_findings(): void
    {
        self::assertSame([
            'rows' => 0,
            'max_columns' => 0,
            'flagged_count' => 0,
            'findings' => [],
        ], CsvCellRiskPreview::inspect([]));
    }

    public function test_benign_cells_and_empty_rows_do_not_trigger_findings(): void
    {
        $result = CsvCellRiskPreview::inspect([
            ['Identificador', 'Exemplo fictício', '123.45'],
            [],
            ['Lead XYZ', 'nome@example.invalid', 'Texto comum'],
        ]);
        self::assertSame(3, $result['rows']);
        self::assertSame(3, $result['max_columns']);
        self::assertSame(0, $result['flagged_count']);
        self::assertSame([], $result['findings']);
    }

    public function test_reports_formula_prefixes_with_only_coordinates_not_values(): void
    {
        $matrix = [
            ['=SUM(A1:A2)', '+HYPERLINK("https://invalid.example")', '-12', '@sample'],
            ['safe', '   =1+1', "\t@run", "\xEF\xBB\xBF +3"],
        ];
        $result = CsvCellRiskPreview::inspect($matrix);
        self::assertSame(8, $result['flagged_count']);
        foreach ($result['findings'] as $i => $finding) {
            self::assertSame(1 + intdiv($i, 4), $finding['row']);
            self::assertSame(1 + ($i % 4), $finding['column']);
            self::assertSame('formula_prefix', $finding['code']);
        }

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['SUM(A1:A2)', 'HYPERLINK', 'https://', 'safe', 'sample', '-12'] as $raw) {
            self::assertStringNotContainsString($raw, $json);
        }
    }

    public function test_marks_quoted_formulas_as_ambiguous_instead_of_safely_escaped(): void
    {
        $result = CsvCellRiskPreview::inspect([["'=SUM(1,1)", "'  @example", "'genuine text"]]);
        self::assertSame(2, $result['flagged_count']);
        self::assertSame([
            ['row' => 1, 'column' => 1, 'code' => 'apostrophe_formula_ambiguous'],
            ['row' => 1, 'column' => 2, 'code' => 'apostrophe_formula_ambiguous'],
        ], $result['findings']);
    }

    public function test_embedded_null_and_control_bytes_are_flagged(): void
    {
        $result = CsvCellRiskPreview::inspect([[
            "abc\x00def",
            "abc\nnew line",
            "ordinary\tinner tab",
            "text\x7fcontrol",
        ]]);
        self::assertSame(4, $result['flagged_count']);
        self::assertSame([
            'nul_byte', 'control_byte', 'control_byte', 'control_byte',
        ], array_column($result['findings'], 'code'));
    }

    public function test_bom_without_formula_is_ambiguous_not_a_clean_import(): void
    {
        $result = CsvCellRiskPreview::inspect([["\xEF\xBB\xBFheader"]]);
        self::assertSame([[
            'row' => 1, 'column' => 1, 'code' => 'bom_prefix',
        ]], $result['findings']);
    }

    public function test_reports_only_one_code_per_cell_in_document_order(): void
    {
        $result = CsvCellRiskPreview::inspect([
            [" \t=1", "ordinary"],
            ["abc\x00=1", "'+2"],
        ]);
        self::assertSame([
            ['row' => 1, 'column' => 1, 'code' => 'formula_prefix'],
            ['row' => 2, 'column' => 1, 'code' => 'nul_byte'],
            ['row' => 2, 'column' => 2, 'code' => 'apostrophe_formula_ambiguous'],
        ], $result['findings']);
    }

    public function test_accepts_exactly_one_hundred_rows_and_twenty_five_cells(): void
    {
        $rows = array_fill(0, 100, array_fill(0, 25, 'synthetic'));
        $result = CsvCellRiskPreview::inspect($rows);
        self::assertSame(100, $result['rows']);
        self::assertSame(25, $result['max_columns']);
        self::assertSame(0, $result['flagged_count']);
    }

    public function test_rejects_one_hundred_and_one_rows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvCellRiskPreview::inspect(array_fill(0, 101, ['synthetic']));
    }

    public function test_rejects_twenty_six_cells_in_one_row(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvCellRiskPreview::inspect([array_fill(0, 26, 'synthetic')]);
    }

    public function test_rejects_non_list_outer_rows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvCellRiskPreview::inspect(['external' => ['synthetic']]);
    }

    public function test_rejects_non_list_inner_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvCellRiskPreview::inspect([['external' => 'synthetic']]);
    }

    public function test_rejects_number_instead_of_exact_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvCellRiskPreview::inspect([[123]]);
    }

    public function test_rejects_more_than_four_thousand_ninety_six_bytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvCellRiskPreview::inspect([[str_repeat('x', 4097)]]);
    }

    public function test_accepts_exactly_four_thousand_ninety_six_bytes(): void
    {
        self::assertSame(0, CsvCellRiskPreview::inspect([[str_repeat('x', 4096)]])['flagged_count']);
    }

    public function test_does_not_claim_a_numeric_minus_is_safe(): void
    {
        $result = CsvCellRiskPreview::inspect([['-42', '42', '+42']]);
        self::assertSame(['formula_prefix', 'formula_prefix'], array_column($result['findings'], 'code'));
    }
}
