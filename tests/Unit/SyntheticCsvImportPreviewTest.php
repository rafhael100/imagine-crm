<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvImportPreviewTest extends TestCase
{
    private static function inspect(string $csv): \App\Core\Portability\SyntheticImportPreviewSummary
    {
        return (new SyntheticCsvImportPreview())->inspect($csv);
    }

    public function test_csv_text_parses_into_existing_aggregate_only_preview(): void
    {
        $summary = self::inspect("name,email\nSYNTHETIC_A,person@example.test\nSYNTHETIC_B,\n");

        self::assertSame(2, $summary->rowsInspected);
        self::assertSame(2, $summary->columnsInspected);
        self::assertSame(1, $summary->emptyCells);
        self::assertSame([], $summary->potentialFormulaPositions);
        self::assertFalse($summary->canImport());

        $serialized = json_encode($summary, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_A', 'SYNTHETIC_B', 'person@example.test', 'name', 'email'] as $private) {
            self::assertStringNotContainsString($private, $serialized);
        }
    }

    public function test_quoted_comma_doubled_quotes_multiline_and_crlf_are_supported(): void
    {
        $csv = "\xEF\xBB\xBFname,note\r\n"
            . "\"SYNTHETIC_A\",\"Hello, \"\"world\"\"\"\r\n"
            . "\"SYNTHETIC_B\",\"Two\nlines\"\r\n";
        $summary = self::inspect($csv);
        self::assertSame(2, $summary->rowsInspected);
        self::assertSame(2, $summary->columnsInspected);
        self::assertSame(0, $summary->potentiallyExecutableCellCount());
        self::assertFalse($summary->canImport());
    }

    public function test_dangerous_formula_positions_are_returned_without_payload(): void
    {
        $summary = self::inspect("name,note\n"
            . "SYNTHETIC_A,\"=HYPERLINK(\"\"x\"\",\"\"y\"\")\"\n"
            . "SYNTHETIC_B,\" \u{2060}=1+1\"\n"
            . "SYNTHETIC_C,-10");
        self::assertSame(3, $summary->rowsInspected);
        self::assertSame(3, $summary->potentiallyExecutableCellCount());
        self::assertSame([
            ['row' => 1, 'column' => 2],
            ['row' => 2, 'column' => 2],
            ['row' => 3, 'column' => 2],
        ], $summary->potentialFormulaPositions);
        self::assertStringNotContainsString('HYPERLINK', json_encode($summary, JSON_THROW_ON_ERROR));
        self::assertFalse($summary->canImport());
    }

    public function test_header_only_and_maximum_bounded_csv_are_supported(): void
    {
        $only = self::inspect("col\n");
        self::assertSame(0, $only->rowsInspected);
        self::assertSame(1, $only->columnsInspected);

        $rows = "col\n".str_repeat("SYNTHETIC_A\n", 50);
        $maximum = self::inspect($rows);
        self::assertSame(50, $maximum->rowsInspected);
        self::assertFalse($maximum->canImport());

        $quotedEmpty = self::inspect("col\n\"\"\n");
        self::assertSame(1, $quotedEmpty->emptyCells);
    }

    public function test_malformed_or_oversized_csv_is_rejected_without_echoing_input(): void
    {
        $invalid = [
            '', "\xEF\xBB\xBF", "\xffcol\nvalue", "col\nSYNTHETIC_\0A",
            "col\rvalue", "col\n\"unclosed", "col\n\"closed\"oops",
            "col\nbare\"quote", "col,col\none,two", "INVALID HEADER\nvalue",
            "col\none,two", "col,other\none", "col,other\none,two,three",
            "col\n".str_repeat('A', 513),
            "col\n".str_repeat('SYNTHETIC_A'."\n", 51),
            implode(',', array_fill(0, 9, 'col'))."\n",
            str_repeat('A', 32769),
        ];
        foreach ($invalid as $csv) {
            try {
                self::inspect($csv);
                self::fail('Malformed preview unexpectedly accepted');
            } catch (InvalidArgumentException $error) {
                self::assertContains($error->getMessage(), [
                    'Estrutura CSV de pre-visualizacao invalida.',
                    'Estrutura de pre-visualizacao invalida.',
                ]);
                self::assertStringNotContainsString('SYNTHETIC_A', $error->getMessage());
            }
        }
    }

    public function test_delimiters_and_formula_looking_text_inside_quoted_cell_are_data_only(): void
    {
        $summary = self::inspect("col1,col2\n\"SYNTHETIC_A,B\",\"safe = value\"\n");
        self::assertSame(1, $summary->rowsInspected);
        self::assertSame([], $summary->potentialFormulaPositions);
        self::assertFalse($summary->canImport());
    }
}
