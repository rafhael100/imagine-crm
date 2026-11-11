<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticColumnMappingInspector;
use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvAutoMappingTest extends TestCase
{
    public function test_exact_label_matches_return_only_reordered_one_based_positions(): void
    {
        $result = (new SyntheticColumnMappingInspector())->suggestExactMatches(
            ['name', 'stage', 'note'],
            ['note', 'name', 'other'],
        );

        self::assertSame([['source' => 1, 'target' => 2], ['source' => 3, 'target' => 1]],
            $result->positions);
        self::assertSame([2], $result->unmappedSourcePositions);
        self::assertSame([3], $result->unmappedTargetPositions);
        self::assertSame(2, $result->mappedColumns());
        self::assertFalse($result->isCompleteOneToOne());
        self::assertFalse($result->canImport());

        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['name', 'stage', 'note', 'other'] as $label) {
            self::assertStringNotContainsString('"'.$label.'"', $encoded);
        }
    }

    public function test_no_common_columns_yields_an_empty_draft_not_a_guessed_mapping(): void
    {
        $result = (new SyntheticColumnMappingInspector())->suggestExactMatches(
            ['name', 'phone'],
            ['label', 'contact'],
        );

        self::assertSame([], $result->positions);
        self::assertSame([1, 2], $result->unmappedSourcePositions);
        self::assertSame([1, 2], $result->unmappedTargetPositions);
        self::assertFalse($result->isCompleteOneToOne());
        self::assertFalse($result->canImport());
    }

    public function test_complete_matching_is_still_only_a_nonauthoritative_proposal(): void
    {
        $result = (new SyntheticCsvImportPreview())->inspectSuggested(
            "stage,name\nSYNTHETIC_STAGE,SYNTHETIC_LABEL\n",
            ['name', 'stage'],
        );
        self::assertSame([
            ['source' => 1, 'target' => 2],
            ['source' => 2, 'target' => 1],
        ], $result->mappingPreview->positions);
        self::assertTrue($result->isMappingComplete());
        self::assertFalse($result->hasFormulaWarnings());
        self::assertFalse($result->canImport());
        self::assertFalse($result->mappingPreview->canImport());
        self::assertFalse($result->csvPreview->canImport());

        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_LABEL', 'SYNTHETIC_STAGE', '"stage"', '"name"'] as $value) {
            self::assertStringNotContainsString($value, $serialized);
        }
    }

    public function test_legacy_and_canonical_suggested_previews_share_identical_results(): void
    {
        $csv = "\xEF\xBB\xBFname,note\nSYNTHETIC_A,\"=1+1\"\n";
        $target = ['note', 'name', 'source'];
        $canonical = (new SyntheticCsvImportPreview())->inspectSuggested($csv, $target);
        $legacy = (new SyntheticCsvPreviewDecoder())->previewSuggested($csv, $target);

        self::assertEquals($canonical, $legacy);
        self::assertSame(2, $canonical->mappingPreview->mappedColumns());
        self::assertSame(1, $canonical->csvPreview->potentiallyExecutableCellCount());
        self::assertSame([3], $canonical->mappingPreview->unmappedTargetPositions);
        self::assertFalse($canonical->canImport());
    }

    public function test_suggestions_never_guess_similar_or_reformatted_labels(): void
    {
        $result = (new SyntheticColumnMappingInspector())->suggestExactMatches(
            ['email', 'source_field'],
            ['email_address', 'source'],
        );
        self::assertSame([], $result->positions);
        self::assertFalse($result->canImport());
    }

    public function test_invalid_source_or_target_headers_fail_without_leaking_labels(): void
    {
        $cases = [
            [['Name'], ['name']],
            [['name'], ['Name']],
            [['name'], ['name', 'name']],
            [['name'], []],
            [['name'], ['email.address']],
            [['name'], ['=HYPERLINK']],
            [['name'], [true]],
            [['name'], [new \stdClass()]],
            [['name'], ['x' => 'name']],
            [['name', 'name'], ['name']],
            [array_fill(0, 9, 'name'), ['name']],
        ];
        foreach ($cases as [$source, $target]) {
            try {
                (new SyntheticColumnMappingInspector())->suggestExactMatches($source, $target);
                self::fail('Malformed header accepted as auto-mapping');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Mapeamento sintetico de colunas invalido.', $error->getMessage());
                self::assertStringNotContainsString('HYPERLINK', $error->getMessage());
            }
        }
    }

    public function test_bad_csv_in_legacy_entrypoint_stays_a_generic_failure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pre-visualizacao CSV sintetica invalida.');
        (new SyntheticCsvPreviewDecoder())->previewSuggested(
            "name\n\"unterminated\n",
            ['name'],
        );
    }
}
