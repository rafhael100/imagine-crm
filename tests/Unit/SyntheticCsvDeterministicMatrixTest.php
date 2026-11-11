<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SpreadsheetTextRiskGuard;
use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * F3-58 deterministic synthetic CSV matrix. No random seed, uploads or real PII.
 * Every case compares both public preview APIs and their deny-only composition.
 */
final class SyntheticCsvDeterministicMatrixTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const PRINCIPAL = '22222222-2222-4222-8222-222222222222';
    private const CORRELATION = '33333333-3333-4333-8333-333333333333';

    private static function encodeCell(string $value): string
    {
        // Strict, conventional RFC-style fixture encoder (not production export).
        if ($value === '' || strpbrk($value, ",\"\r\n") !== false) {
            return '"'.str_replace('"', '""', $value).'"';
        }
        return $value;
    }

    public function test_128_generated_synthetic_grids_have_one_canonical_parse_and_no_import(): void
    {
        $headers = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $dictionary = [
            'SYNTHETIC_A',
            'SYNTHETIC_B,C',
            'SYNTHETIC_"QUOTE"',
            "SYNTHETIC_LINE\nTWO",
            "SYNTHETIC_CRLF\r\nSECOND",
            "PREFIX\t=1",
            " \u{2060}=1+1",
            '-10',
            '@SUM(1)',
            'SYNTHETIC_Δ',
            '',
            'safe = not a leading formula',
        ];
        $count = count($dictionary);
        $legacy = new SyntheticCsvPreviewDecoder();
        $canonical = new SyntheticCsvImportPreview();
        $preflight = new SyntheticCsvImportPreflight();
        $context = new TenantContext(self::TENANT, self::PRINCIPAL, self::CORRELATION);

        for ($case = 0; $case < 128; ++$case) {
            $columnCount = ($case % 8) + 1;
            $rowCount = ($case * 7 + 3) % 17;
            $selectedHeaders = array_slice($headers, 0, $columnCount);
            $csv = implode(',', $selectedHeaders)."\r\n";
            $expectedRisks = [];
            $empty = 0;

            for ($row = 0; $row < $rowCount; ++$row) {
                $cells = [];
                for ($col = 0; $col < $columnCount; ++$col) {
                    $value = $dictionary[($case + $row * 3 + $col * 5) % $count];
                    $cells[] = self::encodeCell($value);
                    if (trim($value) === '') {
                        ++$empty;
                    }
                    if (SpreadsheetTextRiskGuard::isPotentiallyExecutable($value)) {
                        $expectedRisks[] = ['row' => $row + 1, 'column' => $col + 1];
                    }
                }
                $csv .= implode(',', $cells)."\r\n";
            }

            $report = $canonical->inspect($csv);
            self::assertSame($rowCount, $report->rowsInspected, 'row count case '.$case);
            self::assertSame($columnCount, $report->columnsInspected, 'column count case '.$case);
            self::assertSame($empty, $report->emptyCells, 'empty cells case '.$case);
            self::assertSame($expectedRisks, $report->potentialFormulaPositions,
                'formula positions case '.$case);
            self::assertEquals($report, $legacy->preview($csv), 'two public paths case '.$case);
            self::assertFalse($report->canImport());

            $suggested = $canonical->inspectSuggested($csv, $selectedHeaders);
            self::assertTrue($suggested->isMappingComplete());
            self::assertFalse($suggested->canImport());
            self::assertEquals($suggested, $legacy->previewSuggested($csv, $selectedHeaders));

            $bounded = $preflight->inspect(
                $csv, $selectedHeaders, $context, self::TENANT, $selectedHeaders,
            );
            self::assertSame('SCOPE_NOT_VERIFIED', $bounded->decision->reasonCode);
            self::assertFalse($bounded->decision->allowed);
            self::assertFalse($bounded->canImport());
            self::assertFalse($bounded->canPersistLead());

            $encodedSummary = json_encode($bounded, JSON_THROW_ON_ERROR);
            foreach (['SYNTHETIC_A', 'SYNTHETIC_B', 'SYNTHETIC_LINE',
                'PREFIX', 'safe = not', self::TENANT, self::PRINCIPAL] as $sensitive) {
                self::assertStringNotContainsString($sensitive, $encodedSummary);
            }
        }
    }

    public function test_invalid_quote_and_row_break_variants_are_consistently_rejected(): void
    {
        foreach ([
            "a\nSYNTHETIC\"QUOTE\n",
            "a\n\"SYNTHETIC\"trailing\n",
            "a\n\"SYNTHETIC\n",
            "a\rSYNTHETIC\n",
            "a\nSYNTHETIC\rNO\n",
            "a\nSYNTHETIC,EXTRA\n",
            "a\n\nSYNTHETIC\n",
            "\xffa\nSYNTHETIC\n",
            "a\nSYNTHETIC\0X\n",
        ] as $csv) {
            foreach ([
                static fn (): mixed => (new SyntheticCsvImportPreview())->inspect($csv),
                static fn (): mixed => (new SyntheticCsvPreviewDecoder())->preview($csv),
            ] as $parse) {
                try {
                    $parse();
                    self::fail('Invalid synthetic CSV was accepted');
                } catch (InvalidArgumentException $error) {
                    self::assertStringNotContainsString('SYNTHETIC', $error->getMessage());
                }
            }
        }
    }
}
