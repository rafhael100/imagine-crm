<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvRequiredCellsPreviewTest extends TestCase
{
    public function test_brazilian_semicolon_csv_reports_blank_required_cells_without_values(): void
    {
        $csv = "name;stage;note\n"
            . "SYNTHETIC_A; ;ok\n"
            . "SYNTHETIC_B;SYNTHETIC_STAGE;\n"
            . ";SYNTHETIC_STAGE;ok\n";

        $report = (new SyntheticCsvImportPreview())->inspectRequiredCells(
            $csv, ['name', 'stage'], ';',
        );

        self::assertSame(3, $report->csvPreview->rowsInspected);
        self::assertSame(3, $report->csvPreview->columnsInspected);
        self::assertSame(3, $report->csvPreview->emptyCells);
        self::assertSame([1, 2], $report->requiredSourcePositions);
        self::assertSame([], $report->missingRequiredTargetPositions);
        self::assertSame([
            ['row' => 1, 'column' => 2],
            ['row' => 3, 'column' => 1],
        ], $report->emptyRequiredCellPositions);
        self::assertTrue($report->hasEmptyRequiredCells());
        self::assertFalse($report->hasMissingRequiredHeaders());
        self::assertFalse($report->canImport());
        self::assertFalse($report->canPersistLead());
        self::assertEquals(
            $report,
            (new SyntheticCsvPreviewDecoder())->previewRequiredCells($csv, ['name', 'stage'], ';'),
        );

        $serialized = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_A', 'SYNTHETIC_B', 'SYNTHETIC_STAGE',
            'name', 'stage', 'note'] as $raw) {
            self::assertStringNotContainsString('"'.$raw.'"', $serialized);
        }
    }

    public function test_missing_required_header_reports_only_target_position_without_guessing(): void
    {
        $csv = "name;stage\nSYNTHETIC_A;SYNTHETIC_B\n";
        $report = (new SyntheticCsvImportPreview())->inspectRequiredCells(
            $csv, ['name', 'email', 'stage'], ';',
        );
        self::assertSame([1, 2], $report->requiredSourcePositions);
        self::assertSame([2], $report->missingRequiredTargetPositions);
        self::assertSame([], $report->emptyRequiredCellPositions);
        self::assertTrue($report->hasMissingRequiredHeaders());
        self::assertFalse($report->hasEmptyRequiredCells());
        self::assertFalse($report->canImport());
        self::assertStringNotContainsString('email', json_encode($report, JSON_THROW_ON_ERROR));
    }

    public function test_no_common_required_headers_is_warning_not_silent_mapping(): void
    {
        $report = (new SyntheticCsvImportPreview())->inspectRequiredCells(
            "source,stage\nSYNTHETIC_A,SYNTHETIC_B\n",
            ['name', 'email'],
        );
        self::assertSame([], $report->requiredSourcePositions);
        self::assertSame([1, 2], $report->missingRequiredTargetPositions);
        self::assertSame([], $report->emptyRequiredCellPositions);
        self::assertTrue($report->hasMissingRequiredHeaders());
        self::assertFalse($report->canPersistLead());
    }

    public function test_reordered_headers_use_original_one_based_source_positions(): void
    {
        $report = (new SyntheticCsvImportPreview())->inspectRequiredCells(
            "note,stage,name\nok,SYNTHETIC_STAGE,\nblank, ,SYNTHETIC_A\n",
            ['name', 'stage'],
        );
        self::assertSame([2, 3], $report->requiredSourcePositions);
        self::assertSame([
            ['row' => 1, 'column' => 3],
            ['row' => 2, 'column' => 2],
        ], $report->emptyRequiredCellPositions);
        self::assertFalse($report->canImport());
    }

    public function test_header_only_and_complete_synthetic_rows_do_not_create_false_warnings(): void
    {
        foreach ([
            "name,stage\n",
            "name,stage\nSYNTHETIC_A,SYNTHETIC_STAGE\n",
        ] as $csv) {
            $report = (new SyntheticCsvImportPreview())->inspectRequiredCells(
                $csv, ['name', 'stage'],
            );
            self::assertSame([], $report->missingRequiredTargetPositions);
            self::assertSame([], $report->emptyRequiredCellPositions);
            self::assertFalse($report->hasEmptyRequiredCells());
            self::assertFalse($report->hasMissingRequiredHeaders());
            self::assertFalse($report->canImport());
        }
    }

    public function test_formula_warning_and_required_cell_warning_are_independent(): void
    {
        $report = (new SyntheticCsvImportPreview())->inspectRequiredCells(
            "name,note\n"
            . "SYNTHETIC_A,\"=1+1\"\n"
            . ",\" \\u{2060}=1+1\"\n",
            ['name'],
        );
        self::assertSame([['row' => 2, 'column' => 1]],
            $report->emptyRequiredCellPositions);
        self::assertSame([
            ['row' => 1, 'column' => 2],
            ['row' => 2, 'column' => 2],
        ], $report->csvPreview->potentialFormulaPositions);
        self::assertFalse($report->canImport());
    }

    public function test_bad_required_names_and_bad_csv_fail_with_generic_non_leaking_errors(): void
    {
        $invalidRequirements = [
            [], ['Name'], ['name', 'name'], ['=HYPERLINK'],
            ['name;email'], ['name', true],
            ['name' => 'stage'], array_fill(0, 9, 'name'),
        ];
        foreach ($invalidRequirements as $requirements) {
            try {
                (new SyntheticCsvImportPreview())->inspectRequiredCells(
                    "name,stage\nSYNTHETIC_A,SYNTHETIC_B\n", $requirements,
                );
                self::fail('Unapproved required fields accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Estrutura CSV de pre-visualizacao invalida.', $e->getMessage());
                self::assertStringNotContainsString('HYPERLINK', $e->getMessage());
            }
        }
        foreach ([
            ["name;stage\nSYNTHETIC_A\n", ';'],
            ["name;stage\n\"bad quote\n", ';'],
            ["name;stage\nSYNTHETIC_A;SYNTHETIC_B\n", '|'],
        ] as [$csv, $delimiter]) {
            try {
                (new SyntheticCsvPreviewDecoder())->previewRequiredCells(
                    $csv, ['name'], $delimiter,
                );
                self::fail('Invalid data accepted through legacy facade');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Pre-visualizacao CSV sintetica invalida.', $e->getMessage());
                self::assertStringNotContainsString('SYNTHETIC_A', $e->getMessage());
            }
        }
    }
}
