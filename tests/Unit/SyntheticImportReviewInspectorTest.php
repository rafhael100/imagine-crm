<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticColumnMappingSummary;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use App\Core\Portability\SyntheticImportMappedPreviewSummary;
use App\Core\Portability\SyntheticImportPreviewSummary;
use App\Core\Portability\SyntheticImportReviewInspector;
use App\Core\Portability\SyntheticImportReviewSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticImportReviewInspectorTest extends TestCase
{
    public function test_real_lab_decoder_produces_closed_catalog_findings_without_cell_data(): void
    {
        $preview = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label,channel,note\nLead Exemplo A,site,=SUM(1)\nLead Exemplo B,,hello\n",
            ['name', 'origin', 'annotation', 'metadata'],
            [['source' => 1, 'target' => 1], ['source' => 2, 'target' => 2]],
        );
        $report = (new SyntheticImportReviewInspector())->assess($preview);

        self::assertInstanceOf(SyntheticImportReviewSummary::class, $report);
        self::assertSame(2, $report->rowsInspected);
        self::assertSame(2, $report->mappedColumns);
        self::assertSame(1, $report->unmappedSourceColumns);
        self::assertSame(2, $report->unmappedTargetColumns);
        self::assertSame(1, $report->formulaWarnings);
        self::assertSame(1, $report->emptyCells);
        self::assertSame([
            'UNMAPPED_SOURCE_COLUMNS',
            'UNMAPPED_TARGET_COLUMNS',
            'POTENTIAL_FORMULA_CELLS',
            'EMPTY_CELLS',
        ], $report->findings);
        self::assertTrue($report->needsReview());
        self::assertFalse($report->canImport());

        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['Lead Exemplo', '=SUM', 'channel', 'annotation', 'metadata', '"label"'] as $raw) {
            self::assertStringNotContainsString($raw, $encoded);
        }
        self::assertTrue((new \ReflectionClass(SyntheticImportReviewSummary::class))->isReadOnly());
    }

    public function test_clean_lab_preview_does_not_confer_import_rights(): void
    {
        $preview = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label,channel\nDemo,site\n",
            ['name', 'origin'],
            [['source' => 2, 'target' => 1], ['source' => 1, 'target' => 2]],
        );
        $report = (new SyntheticImportReviewInspector())->assess($preview);
        self::assertSame([], $report->findings);
        self::assertFalse($report->needsReview());
        self::assertFalse($report->canImport());
        self::assertFalse($preview->canImport());
    }

    public function test_header_only_is_flagged_as_empty_dataset_and_denied(): void
    {
        $preview = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label\n",
            ['name'],
            [['source' => 1, 'target' => 1]],
        );
        $report = (new SyntheticImportReviewInspector())->assess($preview);
        self::assertSame(['EMPTY_DATASET'], $report->findings);
        self::assertFalse($report->canImport());
    }

    public function test_forged_or_inconsistent_summaries_fail_closed(): void
    {
        $validPositions = [['source' => 1, 'target' => 1]];
        $cases = [
            [new SyntheticImportPreviewSummary(-1, 1, 0, []), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(51, 1, 0, []), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 0, 0, []), new SyntheticColumnMappingSummary(0, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 2, []), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, -1, []), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, [['row' => 2, 'column' => 1]]), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, [['row' => 1, 'column' => 0]]), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, [['row' => 1, 'column' => 1], ['row' => 1, 'column' => 1]]), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, [['column' => 1, 'row' => 1]]), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, ['x' => ['row' => 1, 'column' => 1]]), new SyntheticColumnMappingSummary(1, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(2, 1, $validPositions, [2], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(1, 0, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(1, 1, [], [1], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(1, 1, $validPositions, [1], [])],
            [new SyntheticImportPreviewSummary(1, 2, 0, []), new SyntheticColumnMappingSummary(2, 1, $validPositions, [], [])],
            [new SyntheticImportPreviewSummary(1, 2, 0, []), new SyntheticColumnMappingSummary(2, 1, $validPositions, [1], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(1, 2, $validPositions, [], [1])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(1, 1, [['source' => true, 'target' => 1]], [], [])],
            [new SyntheticImportPreviewSummary(1, 1, 0, []), new SyntheticColumnMappingSummary(1, 1, [['source' => 1, 'target' => 1, 'allow' => true]], [], [])],
        ];

        foreach ($cases as [$csv, $mapping]) {
            try {
                (new SyntheticImportReviewInspector())->assess(
                    new SyntheticImportMappedPreviewSummary($csv, $mapping),
                );
                self::fail('Forged synthetic report accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Resumo sintetico de revisao invalido.', $exception->getMessage());
            }
        }
    }

    public function test_report_exposes_no_write_or_permission_methods(): void
    {
        $preview = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label\nDemo\n",
            ['name'],
            [['source' => 1, 'target' => 1]],
        );
        $result = (new SyntheticImportReviewInspector())->assess($preview);
        foreach (['allow', 'authorize', 'import', 'export', 'commit', 'apply', 'save',
            'delete', 'merge', 'upload', 'persist', 'activate', 'grant'] as $method) {
            self::assertFalse(method_exists($result, $method));
        }
        self::assertFalse($result->canImport());
    }
}
