<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticSpreadsheetFormulaSignalInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSpreadsheetFormulaSignalInspectorTest extends TestCase
{
    public function test_formula_prefix_and_hidden_unicode_controls_are_flagged_without_echoing_cells(): void
    {
        $cells = [
            '=1+1',
            "\t=HYPERLINK(1)",
            "\u{FEFF}@SUM(1)",
            "\u{200B}＋42",
            "'=1+2",
            "linha\nquebra",
            'Texto ficticio',
        ];
        $before = $cells;
        $inspector = new SyntheticSpreadsheetFormulaSignalInspector();
        $out = $inspector->inspect($cells);
        self::assertSame($before, $cells);
        self::assertSame($out, $inspector->inspect($cells));
        self::assertSame(7, $out['inspected_fictional_cell_count']);
        self::assertSame(6, $out['flagged_fictional_cell_count']);
        self::assertSame(3, $out['formula_prefix_count']);
        self::assertSame(3, $out['formatting_prefix_count']);
        self::assertSame(1, $out['unicode_compatibility_prefix_count']);
        self::assertSame(1, $out['apostrophe_escape_claim_count']);
        self::assertSame(1, $out['multiline_cell_count']);
        self::assertSame([
            'FORMULA_PREFIX_SIGNAL',
            'LEADING_WHITESPACE_OR_FORMAT_SIGNAL',
            'UNICODE_COMPATIBILITY_PREFIX_REVIEW',
            'SPREADSHEET_ESCAPE_SEMANTICS_UNVERIFIED',
            'MULTILINE_CELL_REVIEW',
        ], $out['warning_codes']);
        self::assertSame(5, $out['warning_count']);
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['HYPERLINK', 'SUM(', 'linha', 'Texto ficticio', '1+1'] as $source) {
            self::assertStringNotContainsString($source, $json);
        }
    }

    public function test_negative_numbers_and_at_prefix_are_conservatively_flagged(): void
    {
        $out = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect([
            '-10', '+123', '@foo', '  =3*5', "\r\n@evil",
        ]);
        self::assertSame(5, $out['formula_prefix_count']);
        self::assertSame(2, $out['formatting_prefix_count']);
        self::assertSame(1, $out['multiline_cell_count']);
        self::assertSame(5, $out['flagged_fictional_cell_count']);
        self::assertFalse($out['formula_safety_verified']);
        self::assertFalse($out['can_sanitize']);
        self::assertFalse($out['can_export']);
    }

    public function test_plain_text_is_not_authoritative_security_clearance(): void
    {
        $out = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect([
            'Exemplo um', '2026', '123', '', 'Lideranca',
        ]);
        self::assertSame([], $out['warning_codes']);
        self::assertSame(0, $out['flagged_fictional_cell_count']);
        self::assertFalse($out['csv_parser_verified']);
        self::assertFalse($out['unicode_normalization_verified']);
        self::assertFalse($out['spreadsheet_client_behavior_verified']);
        self::assertFalse($out['formula_safety_verified']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_write_file']);
    }

    public function test_empty_input_is_review_required_and_not_an_empty_file(): void
    {
        $out = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect([]);
        self::assertSame(0, $out['inspected_fictional_cell_count']);
        self::assertSame(['NO_FICTIONAL_CELLS_INSPECTED'], $out['warning_codes']);
        self::assertFalse($out['can_import']);
        self::assertFalse($out['can_export']);
    }

    public function test_maximum_twenty_cells_and_512_utf8_bytes_per_cell_are_bounded(): void
    {
        $cells = array_fill(0, 20, str_repeat('A', 512));
        $out = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect($cells);
        self::assertSame(20, $out['inspected_fictional_cell_count']);
        self::assertSame(0, $out['flagged_fictional_cell_count']);
        self::assertFalse($out['customer_content_verified']);
        self::assertFalse($out['can_persist']);
    }

    public function test_rejects_invalid_input_types_invalid_utf8_nul_and_excess_bytes_without_echo(): void
    {
        $cases = [
            null, 'not-a-list', false, [1 => 'a'], [123], [null],
            [true], [["not" => "a cell"]], array_fill(0, 21, 'a'),
            [str_repeat('A', 513)],
            ["\x00=1+1"],
            ["\xC3\x28"],
            ["\xED\xA0\x80"],
            ["\xFF"],
        ];
        foreach ($cases as $case) {
            try {
                (new SyntheticSpreadsheetFormulaSignalInspector())->inspect($case);
                self::fail('Invalid cell metadata accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Sinais sinteticos de formula invalidos.', $e->getMessage());
            }
        }
    }

    public function test_directionality_marks_and_nonbreaking_spaces_are_reviewed(): void
    {
        $out = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect([
            "\u{200F}=1+2", "\u{00A0}@cmd",
            "\u{202E}-4", "\u{2066}＠", "\u{200C}texto",
        ]);
        self::assertSame(5, $out['formatting_prefix_count']);
        self::assertSame(3, $out['formula_prefix_count']);
        self::assertSame(1, $out['unicode_compatibility_prefix_count']);
        self::assertSame(5, $out['flagged_fictional_cell_count']);
        self::assertFalse($out['authorization_verified']);
    }

    public function test_no_aggregate_result_can_authorize_export_import_or_file_creation(): void
    {
        $out = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect(['=1', 'plain']);
        self::assertTrue($out['informational_only']);
        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
    }
}
