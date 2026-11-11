<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvSemicolonDelimiterTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const PRINCIPAL = '22222222-2222-4222-8222-222222222222';
    private const CORRELATION = '33333333-3333-4333-8333-333333333333';

    public function test_brazilian_semicolon_csv_with_bom_decimal_comma_and_quoted_semicolon(): void
    {
        $csv = "\xEF\xBB\xBFname;amount;note\r\n"
            . "\"SYNTHETIC_A;B\";\"2,50\";\"=1+1\"\r\n"
            . "SYNTHETIC_C;\"2,10\";safe\r\n";

        $canonical = (new SyntheticCsvImportPreview())->inspect($csv, ';');
        $compatibility = (new SyntheticCsvPreviewDecoder())->preview($csv, ';');

        self::assertEquals($canonical, $compatibility);
        self::assertSame(2, $canonical->rowsInspected);
        self::assertSame(3, $canonical->columnsInspected);
        self::assertSame([['row' => 1, 'column' => 3]], $canonical->potentialFormulaPositions);
        self::assertFalse($canonical->canImport());

        $summary = json_encode($canonical, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_A', 'SYNTHETIC_C', '2,50', '=1+1', 'amount', 'name', 'note'] as $private) {
            self::assertStringNotContainsString($private, $summary);
        }
    }

    public function test_delimiter_must_be_selected_explicitly_without_automatic_guessing(): void
    {
        $semicolonCsv = "name;stage\nSYNTHETIC_A;SYNTHETIC_B\n";
        $this->assertSame(1, (new SyntheticCsvImportPreview())->inspect($semicolonCsv, ';')->rowsInspected);

        try {
            (new SyntheticCsvImportPreview())->inspect($semicolonCsv);
            self::fail('Semicolon was silently guessed without explicit selection');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Estrutura CSV de pre-visualizacao invalida.', $e->getMessage());
        }

        // Conversely a comma CSV may contain semicolons *inside a cell*.
        $comma = (new SyntheticCsvImportPreview())->inspect(
            "name,note\n\"SYNTHETIC_A;B\",\"C,D\"\n",
        );
        self::assertSame(1, $comma->rowsInspected);
        self::assertSame(2, $comma->columnsInspected);
        self::assertFalse($comma->canImport());
    }

    public function test_semicolon_mapping_and_suggestions_keep_positions_only(): void
    {
        $csv = "stage;name\nSYNTHETIC_STAGE;SYNTHETIC_NAME\n";
        $canonical = new SyntheticCsvImportPreview();
        $legacy = new SyntheticCsvPreviewDecoder();
        $targets = ['name', 'stage'];
        $positions = [
            ['source' => 1, 'target' => 2],
            ['source' => 2, 'target' => 1],
        ];

        $manual = $canonical->inspectMapped($csv, $targets, $positions, ';');
        self::assertEquals($manual, $legacy->previewMapped($csv, $targets, $positions, ';'));
        self::assertEquals(
            $manual,
            $canonical->inspectSuggested($csv, $targets, ';'),
        );
        self::assertEquals(
            $manual,
            $legacy->previewSuggested($csv, $targets, ';'),
        );
        self::assertTrue($manual->isMappingComplete());
        self::assertSame($positions, $manual->mappingPreview->positions);
        self::assertFalse($manual->canImport());
        self::assertFalse($manual->mappingPreview->canImport());
        self::assertStringNotContainsString('SYNTHETIC_STAGE', json_encode($manual, JSON_THROW_ON_ERROR));
    }

    public function test_semicolon_deny_preflight_is_still_non_operational_even_with_complete_mapping(): void
    {
        $context = new TenantContext(self::TENANT, self::PRINCIPAL, self::CORRELATION);
        $report = (new SyntheticCsvImportPreflight())->inspect(
            "name;stage\nSYNTHETIC_NAME;SYNTHETIC_STAGE\n",
            ['stage', 'name'],
            $context,
            self::TENANT,
            ['name', 'stage'],
            ';',
        );

        self::assertTrue($report->preview->isMappingComplete());
        self::assertSame('SCOPE_NOT_VERIFIED', $report->decision->reasonCode);
        self::assertFalse($report->decision->allowed);
        self::assertFalse($report->canImport());
        self::assertFalse($report->canPersistLead());
        self::assertFalse($report->canEnableImportRoute());
        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_NAME', 'SYNTHETIC_STAGE', self::TENANT, self::PRINCIPAL] as $private) {
            self::assertStringNotContainsString($private, $encoded);
        }
    }

    public function test_semicolon_mode_detects_formula_controls_and_multiline_cells(): void
    {
        $csv = "name;note\n"
            . "SYNTHETIC_A;\" \u{2060}=1+1\"\n"
            . "SYNTHETIC_B;\"Line one\nLine two\"\n";
        $summary = (new SyntheticCsvImportPreview())->inspect($csv, ';');
        self::assertSame(2, $summary->rowsInspected);
        self::assertSame([
            ['row' => 1, 'column' => 2],
            ['row' => 2, 'column' => 2],
        ], $summary->potentialFormulaPositions);
        self::assertFalse($summary->canImport());
    }

    public function test_malformed_semicolon_csv_fails_closed_without_echoing_payloads(): void
    {
        $cases = [
            "name;note\nSYNTHETIC_A;B;EXTRA\n",
            "name;note\nSYNTHETIC_A\n",
            "name;note\n\"unterminated\n",
            "name;note\n\"closed\"suffix;other\n",
            "name;note\nSYNTHETIC_A;\"bad\"quote\n",
            "name;note\nSYNTHETIC_A;B\0\n",
            "name;name\nSYNTHETIC_A;B\n",
            "name;note\n\nSYNTHETIC_A;B\n",
            implode(';', array_fill(0, 9, 'name'))."\n",
        ];

        foreach ($cases as $csv) {
            try {
                (new SyntheticCsvImportPreview())->inspect($csv, ';');
                self::fail('Unsafe semicolon format accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Estrutura CSV de pre-visualizacao invalida.', $e->getMessage());
                self::assertStringNotContainsString('SYNTHETIC_A', $e->getMessage());
            }
        }
    }

    public function test_unknown_delimiters_are_denied_in_both_public_apis(): void
    {
        foreach (['', '|', "\t", "\n", ';;', 'comma', ' ,'] as $delimiter) {
            try {
                (new SyntheticCsvImportPreview())->inspect("name\nSYNTHETIC_A\n", $delimiter);
                self::fail('Unapproved delimiter accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Estrutura CSV de pre-visualizacao invalida.', $e->getMessage());
            }
            try {
                (new SyntheticCsvPreviewDecoder())->preview("name\nSYNTHETIC_A\n", $delimiter);
                self::fail('Legacy decoder accepted an unapproved delimiter');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Pre-visualizacao CSV sintetica invalida.', $e->getMessage());
            }
        }
    }
}
