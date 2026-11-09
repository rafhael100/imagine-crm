<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvPreviewDecoder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvPreviewDecoderTest extends TestCase
{
    public function test_csv_fields_and_formula_positions_return_only_aggregate_metadata(): void
    {
        $csv = "name,source,note\n"
            . "\"Lead Exemplo A\",site-demo,\"=SUM(1,2)\"\n"
            . "\"Lead Exemplo B\",site-demo,\"\"\n"
            . "\"Lead Exemplo C\",manual,\"  +1\"\n";

        $report = (new SyntheticCsvPreviewDecoder())->preview($csv);

        self::assertSame(3, $report->rowsInspected);
        self::assertSame(3, $report->columnsInspected);
        self::assertSame(1, $report->emptyCells);
        self::assertSame([
            ['row' => 1, 'column' => 3],
            ['row' => 3, 'column' => 3],
        ], $report->potentialFormulaPositions);
        self::assertSame(2, $report->potentiallyExecutableCellCount());
        self::assertFalse($report->canImport());

        $serialized = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['Lead Exemplo', 'site-demo', '=SUM', 'name', '+1'] as $cell) {
            self::assertStringNotContainsString($cell, $serialized);
        }
    }

    public function test_quoted_comma_crlf_and_multiline_cell_do_not_create_extra_rows(): void
    {
        $csv = "label,note\r\n\"Example, A\",\"line 1\r\nline 2\"\r\nExample B,ok\r\n";
        $report = (new SyntheticCsvPreviewDecoder())->preview($csv);

        self::assertSame(2, $report->rowsInspected);
        self::assertSame(2, $report->columnsInspected);
        self::assertSame(0, $report->potentiallyExecutableCellCount());
        self::assertFalse($report->canImport());
    }

    public function test_identical_rows_remain_distinct_and_never_become_imports(): void
    {
        $report = (new SyntheticCsvPreviewDecoder())->preview("label\nExample\nExample\n");
        self::assertSame(2, $report->rowsInspected);
        self::assertSame(0, $report->emptyCells);
        self::assertFalse($report->canImport());
    }

    public function test_header_with_no_data_rows_is_a_nonimportable_preview(): void
    {
        $report = (new SyntheticCsvPreviewDecoder())->preview("label,origin\n");
        self::assertSame(0, $report->rowsInspected);
        self::assertSame(2, $report->columnsInspected);
        self::assertFalse($report->canImport());
    }

    public function test_fifty_rows_and_eight_columns_are_the_exact_supported_boundary(): void
    {
        $csv = "a,b,c,d,e,f,g,h\n".str_repeat("1,2,3,4,5,6,7,8\n", 50);
        $report = (new SyntheticCsvPreviewDecoder())->preview($csv);
        self::assertSame(50, $report->rowsInspected);
        self::assertSame(8, $report->columnsInspected);
        self::assertFalse($report->canImport());
    }

    public function test_invalid_utf8_or_control_prefix_is_never_silently_repaired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SyntheticCsvPreviewDecoder())->preview("label\n\xff\n");
    }

    public function test_control_characters_are_flagged_by_existing_formula_guard(): void
    {
        $report = (new SyntheticCsvPreviewDecoder())->preview("label\n\"\t=1+1\"\n");
        self::assertSame([['row' => 1, 'column' => 1]], $report->potentialFormulaPositions);
        self::assertFalse($report->canImport());
    }

    public static function malformedInputs(): array
    {
        return [
            'empty data' => [''],
            'only an empty header' => ["\n"],
            'more than 32768 bytes' => [str_repeat('A', 32769)],
            'NUL control byte' => ["label\nbad\0cell\n"],
            'UTF-8 BOM not normalized' => ["\u{FEFF}label\nExample\n"],
            'invalid header syntax' => ["display name\nExample\n"],
            'header starts with number' => ["1label\nExample\n"],
            'duplicate columns' => ["label,label\nA,B\n"],
            'uppercase field name not normalized' => ["Label\nExample\n"],
            'nine columns' => ["a,b,c,d,e,f,g,h,i\n"],
            'row too short' => ["name,contact\nExample\n"],
            'row too wide' => ["name\nExample,Other\n"],
            'empty line in data' => ["label\n\nExample\n"],
            '51 rows' => ["label\n".str_repeat("example\n", 51)],
            'overlong cell' => ["label\n".str_repeat('a', 513)."\n"],
        ];
    }

    #[DataProvider('malformedInputs')]
    public function test_unrecognized_or_excessive_synthetic_csv_is_rejected_without_echo(string $csv): void
    {
        try {
            (new SyntheticCsvPreviewDecoder())->preview($csv);
            self::fail('Unsafe CSV did not fail closed.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Pre-visualizacao CSV sintetica invalida.', $e->getMessage());
            self::assertStringNotContainsString('Example', $e->getMessage());
            self::assertStringNotContainsString('contact', $e->getMessage());
        }
    }
}
