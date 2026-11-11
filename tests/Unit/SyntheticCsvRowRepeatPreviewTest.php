<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvRowRepeatPreviewTest extends TestCase
{
    public function test_exact_repeated_rows_return_only_one_based_first_and_repeated_positions(): void
    {
        $csv = "name;stage\n"
            . "\"SYNTHETIC_A;B\";SYNTHETIC_STAGE\n"
            . "SYNTHETIC_OTHER;SYNTHETIC_STAGE\n"
            . "\"SYNTHETIC_A;B\";SYNTHETIC_STAGE\n"
            . "synthetic_a;SYNTHETIC_STAGE\n"
            . "\"SYNTHETIC_A;B\";SYNTHETIC_STAGE\n";

        $report = (new SyntheticCsvImportPreview())->inspectRepeatedRows($csv, ';');

        self::assertSame(5, $report->csvPreview->rowsInspected);
        self::assertSame(2, $report->csvPreview->columnsInspected);
        self::assertSame([
            ['firstRow' => 1, 'repeatedRow' => 3],
            ['firstRow' => 1, 'repeatedRow' => 5],
        ], $report->repeatedRowPositions);
        self::assertSame(2, $report->repeatedRowCount());
        self::assertTrue($report->hasRepeatedRows());
        self::assertFalse($report->canImport());
        self::assertFalse($report->canDeduplicateContacts());
        self::assertEquals(
            $report,
            (new SyntheticCsvPreviewDecoder())->previewRepeatedRows($csv, ';'),
        );

        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_A', 'SYNTHETIC_STAGE', 'SYNTHETIC_OTHER', 'name', 'stage'] as $value) {
            self::assertStringNotContainsString($value, $encoded);
        }
    }

    public function test_quote_representation_changes_do_not_hide_exact_duplicate_content(): void
    {
        $csv = "name,note\n"
            . "SYNTHETIC_A,\"Hello, world\"\n"
            . "\"SYNTHETIC_A\",\"Hello, world\"\n"
            . "SYNTHETIC_A,\"Hello, world \"\n";

        $report = (new SyntheticCsvImportPreview())->inspectRepeatedRows($csv);
        self::assertSame([['firstRow' => 1, 'repeatedRow' => 2]], $report->repeatedRowPositions);
        self::assertSame(1, $report->repeatedRowCount());
        self::assertFalse($report->canDeduplicateContacts());
    }

    public function test_header_only_and_all_unique_rows_yield_zero_repeats_without_authority(): void
    {
        $decoder = new SyntheticCsvImportPreview();
        foreach (["name;note\n", "name;note\nSYNTHETIC_A;A\nSYNTHETIC_B;B\n"] as $csv) {
            $report = $decoder->inspectRepeatedRows($csv, ';');
            self::assertSame([], $report->repeatedRowPositions);
            self::assertSame(0, $report->repeatedRowCount());
            self::assertFalse($report->hasRepeatedRows());
            self::assertFalse($report->canImport());
            self::assertFalse($report->canDeduplicateContacts());
        }
    }

    public function test_identical_formula_like_cells_are_reported_as_both_risk_and_repeated(): void
    {
        $report = (new SyntheticCsvImportPreview())->inspectRepeatedRows(
            "name;note\n"
            . "SYNTHETIC_A;\" \u{2060}=1+1\"\n"
            . "SYNTHETIC_A;\" \u{2060}=1+1\"\n",
            ';',
        );
        self::assertSame([['row' => 1, 'column' => 2], ['row' => 2, 'column' => 2]],
            $report->csvPreview->potentialFormulaPositions);
        self::assertSame([['firstRow' => 1, 'repeatedRow' => 2]],
            $report->repeatedRowPositions);
        self::assertFalse($report->canImport());
    }

    public function test_multiline_and_empty_cells_compare_by_canonical_decoded_text(): void
    {
        $report = (new SyntheticCsvImportPreview())->inspectRepeatedRows(
            "name;note\n"
            . "SYNTHETIC_A;\"Line 1\nLine 2\"\n"
            . "SYNTHETIC_B;\"\"\n"
            . "SYNTHETIC_A;\"Line 1\nLine 2\"\n"
            . "SYNTHETIC_B;\n",
            ';',
        );
        self::assertSame([
            ['firstRow' => 1, 'repeatedRow' => 3],
            ['firstRow' => 2, 'repeatedRow' => 4],
        ], $report->repeatedRowPositions);
        self::assertSame(2, $report->csvPreview->emptyCells);
    }

    public function test_bounded_fifty_identical_rows_annotate_forty_nine_repeats_without_values(): void
    {
        $csv = "name,note\n".str_repeat("SYNTHETIC_A,SYNTHETIC_B\n", 50);
        $report = (new SyntheticCsvImportPreview())->inspectRepeatedRows($csv);
        self::assertSame(50, $report->csvPreview->rowsInspected);
        self::assertSame(49, $report->repeatedRowCount());
        self::assertSame(['firstRow' => 1, 'repeatedRow' => 2], $report->repeatedRowPositions[0]);
        self::assertSame(['firstRow' => 1, 'repeatedRow' => 50], $report->repeatedRowPositions[48]);
        self::assertFalse($report->canImport());
        self::assertStringNotContainsString('SYNTHETIC_A', json_encode($report, JSON_THROW_ON_ERROR));
    }

    public function test_bad_csv_or_unknown_delimiter_uses_generic_error_in_legacy_api(): void
    {
        foreach ([
            ["name;note\nSYNTHETIC_A;B;EXTRA", ';'],
            ["name;note\n\"unclosed", ';'],
            ["name\nSYNTHETIC_A\n", '|'],
        ] as [$csv, $delimiter]) {
            try {
                (new SyntheticCsvPreviewDecoder())->previewRepeatedRows($csv, $delimiter);
                self::fail('Unsafe row repeat preview unexpectedly accepted input');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Pre-visualizacao CSV sintetica invalida.', $e->getMessage());
                self::assertStringNotContainsString('SYNTHETIC_A', $e->getMessage());
            }
        }
    }
}
