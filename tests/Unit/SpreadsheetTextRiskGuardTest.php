<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SpreadsheetTextRiskGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SpreadsheetTextRiskGuardTest extends TestCase
{
    public static function suspiciousCells(): array
    {
        return [
            'equals' => ['=1+1'],
            'plus' => ['+SUM(1,2)'],
            'minus' => ['-CMD'],
            'at' => ['@SUM(A1:A2)'],
            'leading spaces' => ['   =2+2'],
            'leading tab' => ["\tplain"],
            'leading newline' => ["\nplain"],
            'leading return' => ["\rplain"],
            'leading null' => ["\x00text"],
            'leading bom' => ["\u{FEFF}=1"],
            'leading no-break space' => ["\u{00A0}=1"],
            'leading zero-width space' => ["\u{200B}=1"],
            'leading Unicode word joiner' => ["\u{2060}=1"],
            'leading Arabic formatting mark' => ["\u{061C}=1"],
            'leading Mongolian separator' => ["\u{180E}=1"],
            'leading combining grapheme joiner' => ["\u{034F}=1"],
            'leading Unicode soft hyphen' => ["\u{00AD}+SUM(A1:A2)"],
            'mixed invisible whitespace before formula' => ["  \u{200B}\u{2060} =1"],
            'leading format mark before negative formula' => ["\u{2060}-CMD"],

            'leading ideographic space' => ["\u{3000}=1"],
            'non-utf8' => ["\xff=1"],
            'numeric text begins minus' => ['-10'],
        ];
    }

    #[DataProvider('suspiciousCells')]
    public function test_suspicious_strings_fail_closed(string $cell): void
    {
        self::assertTrue(SpreadsheetTextRiskGuard::isPotentiallyExecutable($cell));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Celula de planilha requer tratamento seguro.');
        SpreadsheetTextRiskGuard::assertPlainText($cell);
    }

    public static function plainCells(): array
    {
        return [
            'empty' => [''],
            'simple label' => ['Empresa Exemplo'],
            'neutral numeric text' => ['10'],
            'email-like neutral text' => ['lead@example.invalid'],
            'punctuation inside text' => ['Texto = 2+2'],
            'safe leading spaces' => ['  Lead Exemplo'],
            'plus inside text' => ['Lead + Equipe'],
            'word joiner inside neutral text' => ["Lead\u{2060}= nota"],
            'soft hyphen inside neutral text' => ["Primeiro\u{00AD}+ segundo"],

        ];
    }

    #[DataProvider('plainCells')]
    public function test_plain_text_is_not_misclassified_as_a_formula(string $cell): void
    {
        self::assertFalse(SpreadsheetTextRiskGuard::isPotentiallyExecutable($cell));
        SpreadsheetTextRiskGuard::assertPlainText($cell);
    }

    public function test_guard_never_sanitizes_or_mutates_original_text(): void
    {
        $cell = 'Texto = 2+2';
        SpreadsheetTextRiskGuard::assertPlainText($cell);
        self::assertSame('Texto = 2+2', $cell);
    }

    public function test_negative_number_as_text_is_conservatively_rejected(): void
    {
        self::assertTrue(SpreadsheetTextRiskGuard::isPotentiallyExecutable('-19.90'));
        // Typed numeric export is a separate contract; this class handles text.
    }
}
