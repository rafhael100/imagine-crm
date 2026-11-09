<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvPreviewDecoder;
use App\Core\Portability\SyntheticImportMappedPreviewSummary;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticImportMappedPreviewTest extends TestCase
{
    public function test_csv_header_is_used_in_same_decode_and_never_escapes_report(): void
    {
        $csv = "label,source_field,note\n\"Lead Exemplo A\",site-demo,\"=SUM(1,2)\"\n";
        $decoder = new SyntheticCsvPreviewDecoder();

        $mapped = $decoder->previewMapped(
            $csv,
            ['description', 'origin', 'comment'],
            [['source' => 3, 'target' => 2], ['source' => 1, 'target' => 1], ['source' => 2, 'target' => 3]],
        );

        self::assertInstanceOf(SyntheticImportMappedPreviewSummary::class, $mapped);
        self::assertSame(1, $mapped->csvPreview->rowsInspected);
        self::assertSame(3, $mapped->csvPreview->columnsInspected);
        self::assertSame(1, $mapped->csvPreview->potentiallyExecutableCellCount());
        self::assertTrue($mapped->hasFormulaWarnings());
        self::assertTrue($mapped->isMappingComplete());
        self::assertFalse($mapped->canImport());
        self::assertSame([
            ['source' => 1, 'target' => 1],
            ['source' => 2, 'target' => 3],
            ['source' => 3, 'target' => 2],
        ], $mapped->mappingPreview->positions);
        self::assertSame(
            $decoder->preview($csv)->rowsInspected,
            $mapped->csvPreview->rowsInspected,
        );

        $serialized = json_encode($mapped, JSON_THROW_ON_ERROR);
        foreach (['Lead Exemplo', 'site-demo', '=SUM', '"label"', '"source_field"', '"note"', '"description"', '"origin"'] as $sensitive) {
            self::assertStringNotContainsString($sensitive, $serialized);
        }
        self::assertTrue((new \ReflectionClass(SyntheticImportMappedPreviewSummary::class))->isReadOnly());
    }

    public function test_partial_mapping_reports_missing_positions_without_inventing_fields(): void
    {
        $result = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "first,second,third\na,b,c\n",
            ['x', 'y'],
            [['source' => 2, 'target' => 1]],
        );

        self::assertSame(1, $result->csvPreview->rowsInspected);
        self::assertSame(3, $result->mappingPreview->sourceColumns);
        self::assertSame(2, $result->mappingPreview->targetColumns);
        self::assertSame([1, 3], $result->mappingPreview->unmappedSourcePositions);
        self::assertSame([2], $result->mappingPreview->unmappedTargetPositions);
        self::assertFalse($result->isMappingComplete());
        self::assertFalse($result->canImport());
        self::assertFalse($result->hasFormulaWarnings());
    }

    public function test_empty_data_rows_can_be_reviewed_but_not_imported(): void
    {
        $report = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label,source\n",
            ['name'],
            [['source' => 1, 'target' => 1]],
        );
        self::assertSame(0, $report->csvPreview->rowsInspected);
        self::assertSame(1, $report->mappingPreview->mappedColumns());
        self::assertFalse($report->canImport());
    }

    public static function invalidPreflights(): array
    {
        return [
            'invalid actual CSV header' => ["Label,source\na,b\n", ['a'], [['source'=>1,'target'=>1]]],
            'invalid UTF8 CSV' => ["label\n\xff\n", ['a'], [['source'=>1,'target'=>1]]],
            'out of range mapping index' => ["label,source\na,b\n", ['a'], [['source'=>3,'target'=>1]]],
            'wrong order of pair keys' => ["label,source\na,b\n", ['a'], [['target'=>1,'source'=>1]]],
            'forged allow parameter' => ["label\na\n", ['a'], [['source'=>1,'target'=>1,'allow'=>true]]],
            'duplicate destination' => ["a,b\nx,y\n", ['p','q'], [['source'=>1,'target'=>1],['source'=>2,'target'=>1]]],
            'invalid target field' => ["label\na\n", ['=FORMULA'], [['source'=>1,'target'=>1]]],
            'empty target list' => ["label\na\n", [], [['source'=>1,'target'=>1]]],
            'non-list mapping' => ["label\na\n", ['a'], ['entry'=>['source'=>1,'target'=>1]]],
        ];
    }

    #[DataProvider('invalidPreflights')]
    public function test_invalid_synthetic_preflights_fail_closed_without_reflection(
        string $csv,
        array $targets,
        array $pairs,
    ): void {
        try {
            (new SyntheticCsvPreviewDecoder())->previewMapped($csv, $targets, $pairs);
            self::fail('Unsafe preflight was accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Pre-visualizacao CSV sintetica invalida.', $e->getMessage());
            foreach (['source', 'FORMULA', 'label', 'a,b'] as $value) {
                self::assertStringNotContainsString($value, $e->getMessage());
            }
        }
    }

    public function test_merged_report_never_has_any_write_or_authorization_entrypoint(): void
    {
        $report = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label\nexample\n", ['name'], [['source'=>1,'target'=>1]],
        );
        foreach (['allow', 'authorize', 'import', 'export', 'commit', 'apply', 'save',
            'merge', 'delete', 'create', 'persist', 'charge', 'upload', 'notify'] as $method) {
            self::assertFalse(method_exists($report, $method));
        }
        self::assertFalse($report->canImport());
    }
}
