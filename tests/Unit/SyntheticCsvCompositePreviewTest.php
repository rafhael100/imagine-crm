<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvCompositePreviewTest extends TestCase
{
    /** @return list<array{string, list<string>, list<string>|null, string}> */
    private static function fixtures(): array
    {
        return [
            [
                "name,stage\nSYNTHETIC_A,ONE\nSYNTHETIC_A,ONE\nSYNTHETIC_B,\n",
                ['stage', 'name'], ['name', 'stage'], ',',
            ],
            [
                "\xEF\xBB\xBFname;amount;note\r\n"
                    . "\"SYNTHETIC_A;B\";\"2,50\";\"=1+1\"\r\n"
                    . "\"SYNTHETIC_A;B\";\"2,50\";\"=1+1\"\r\n"
                    . "SYNTHETIC_C;;ok\r\n",
                ['note', 'name', 'amount'], ['name', 'note'], ';',
            ],
            [
                "name,stage\n",
                ['name', 'stage'], null, ',',
            ],
            [
                "name;stage\nSYNTHETIC_A;STAGE_1\n",
                ['name', 'stage'], ['missing'], ';',
            ],
            [
                "name,stage\n".str_repeat("SYNTHETIC_A,STAGE_1\n", 50),
                ['name', 'stage'], ['name', 'missing'], ',',
            ],
            [
                "stage;note;name\n"
                    . "SYNTHETIC_STAGE;\"First\nSecond\";\n"
                    . "SYNTHETIC_STAGE;\"First\nSecond\";\n",
                ['name', 'stage', 'note'], ['stage', 'name'], ';',
            ],
        ];
    }

    public function test_single_decoded_snapshot_exactly_matches_all_prior_individual_apis(): void
    {
        $parser = new SyntheticCsvImportPreview();
        foreach (self::fixtures() as [$csv, $targets, $required, $delimiter]) {
            $bundle = $parser->inspectComposite($csv, $targets, $required, $delimiter);
            $mapped = $parser->inspectSuggested($csv, $targets, $delimiter);
            $repeated = $parser->inspectRepeatedRows($csv, $delimiter);
            $requiredReport = $required === null
                ? null
                : $parser->inspectRequiredCells($csv, $required, $delimiter);

            self::assertEquals($mapped, $bundle->mappedPreview);
            self::assertEquals($repeated, $bundle->repeatPreview);
            self::assertEquals($requiredReport, $bundle->requiredCellsPreview);
            self::assertEquals($mapped->csvPreview, $bundle->repeatPreview->csvPreview);
            if ($bundle->requiredCellsPreview !== null) {
                self::assertEquals($mapped->csvPreview, $bundle->requiredCellsPreview->csvPreview);
            }
            self::assertFalse($bundle->canImport());
            self::assertFalse($bundle->canPersistLead());

            $serialized = json_encode($bundle, JSON_THROW_ON_ERROR);
            foreach (['SYNTHETIC_A', 'SYNTHETIC_C', 'SYNTHETIC_STAGE',
                '"name"', '"stage"', '"note"', '"amount"', '"missing"', '2,50',
                'First', 'Second', 'STAGE_1'] as $original) {
                self::assertStringNotContainsString($original, $serialized);
            }
        }
    }

    public function test_reordered_csv_and_three_diagnostics_still_produce_permanent_deny(): void
    {
        $csv = "stage;name;note\n"
            . ";SYNTHETIC_A;safe\n"
            . "SYNTHETIC_STAGE;;safe\n"
            . "SYNTHETIC_STAGE;;safe\n";
        $context = new TenantContext(
            '11111111-1111-4111-8111-111111111111',
            '22222222-2222-4222-8222-222222222222',
            '33333333-3333-4333-8333-333333333333',
        );
        $result = (new SyntheticCsvImportPreflight())->inspect(
            $csv,
            ['stage', 'name', 'note'],
            $context,
            '11111111-1111-4111-8111-111111111111',
            ['stage', 'name'],
            ';',
            ['name', 'stage', 'missing'],
        );

        self::assertTrue($result->hasRepeatWarnings());
        self::assertTrue($result->hasMissingRequiredHeaders());
        self::assertTrue($result->hasEmptyRequiredCells());
        self::assertSame('SCOPE_NOT_VERIFIED', $result->decision->reasonCode);
        self::assertFalse($result->decision->allowed);
        self::assertFalse($result->canImport());
        self::assertFalse($result->canPersistLead());
        self::assertFalse($result->canEnableImportRoute());
    }

    public function test_invalid_delimiters_and_technical_labels_still_fail_closed(): void
    {
        $invalid = [
            ["name,stage\nSYNTHETIC_A,B\n", ['name'], null, '|'],
            ["name,stage\nSYNTHETIC_A,B\n", [], null, ','],
            ["name,stage\nSYNTHETIC_A,B\n", ['Name'], null, ','],
            ["name,stage\nSYNTHETIC_A,B\n", ['name'], [], ','],
            ["name,stage\nSYNTHETIC_A,B\n", ['name'], ['name', 'name'], ','],
            ["name,stage\n\"unclosed\n", ['name'], null, ','],
            ["name;stage\nSYNTHETIC_A;B;extra\n", ['name'], null, ';'],
        ];

        foreach ($invalid as [$csv, $targets, $required, $delimiter]) {
            try {
                (new SyntheticCsvImportPreview())->inspectComposite(
                    $csv, $targets, $required, $delimiter,
                );
                self::fail('Unsafe composite preview unexpectedly accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Estrutura CSV de pre-visualizacao invalida.', $e->getMessage());
                self::assertStringNotContainsString('SYNTHETIC_A', $e->getMessage());
            }
        }
    }
}
