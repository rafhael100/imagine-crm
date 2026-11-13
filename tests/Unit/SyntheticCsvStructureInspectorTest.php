<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvStructureInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvStructureInspectorTest extends TestCase
{
    public function test_quoted_commas_doubled_quotes_and_crlf_are_structurally_counted(): void
    {
        $csv = "kind,description\r\n1,\"Alpha, Beta\"\r\n2,\"A \"\"quoted\"\" example\"\r\n";
        $original = $csv;
        $inspector = new SyntheticCsvStructureInspector();
        $out = $inspector->inspect($csv);
        self::assertSame($original, $csv);
        self::assertSame($out, $inspector->inspect($csv));
        self::assertSame('comma', $out['declared_delimiter']);
        self::assertSame(3, $out['synthetic_record_count']);
        self::assertSame(6, $out['synthetic_total_cell_count']);
        self::assertSame(2, $out['synthetic_expected_column_count']);
        self::assertSame(2, $out['synthetic_quoted_field_count']);
        self::assertSame(3, $out['synthetic_crlf_terminator_count']);
        self::assertSame(0, $out['synthetic_lf_terminator_count']);
        self::assertSame([], $out['warning_codes']);
        self::assertFalse($out['can_import']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['schema_verified']);
    }

    public function test_semicolon_mode_handles_unicode_and_quoted_separators(): void
    {
        $out = (new SyntheticCsvStructureInspector())->inspect(
            "tipo;detalhe\nA;\"Café; exemplo\"\nB;ok",
            'semicolon',
        );
        self::assertSame('semicolon', $out['declared_delimiter']);
        self::assertSame(3, $out['synthetic_record_count']);
        self::assertSame(6, $out['synthetic_total_cell_count']);
        self::assertSame(1, $out['synthetic_quoted_field_count']);
        self::assertSame([], $out['warning_codes']);
        self::assertFalse($out['customer_content_verified']);
    }

    public function test_synthetic_bom_formula_prefixes_and_multiline_fields_get_warned(): void
    {
        $csv = "\xEF\xBB\xBFkind,value\none,\"=HYPERLINK(1)\nignore\"\ntwo,\" \t@SUM(2)\"";
        $out = (new SyntheticCsvStructureInspector())->inspect($csv);
        self::assertSame(3, $out['synthetic_record_count']);
        self::assertSame(2, $out['synthetic_flagged_formula_cell_count']);
        self::assertContains('UTF8_BOM_REVIEW_REQUIRED', $out['warning_codes']);
        self::assertContains('CELL_FORMULA_PREFIX_SIGNAL', $out['warning_codes']);
        self::assertContains('CELL_LEADING_WHITESPACE_OR_FORMAT_SIGNAL', $out['warning_codes']);
        self::assertContains('CELL_MULTILINE_CELL_REVIEW', $out['warning_codes']);
        self::assertFalse($out['can_sanitize']);
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['HYPERLINK', 'SUM(', 'ignore', 'one', 'two'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
    }

    public function test_uneven_columns_blank_record_and_mixed_line_endings_are_not_silent(): void
    {
        $out = (new SyntheticCsvStructureInspector())->inspect(
            "A,B\r\n1,2\n\n3",
        );
        self::assertSame(4, $out['synthetic_record_count']);
        self::assertSame(6, $out['synthetic_total_cell_count']);
        self::assertSame(2, $out['synthetic_expected_column_count']);
        self::assertSame(2, $out['synthetic_irregular_record_count']);
        self::assertSame(1, $out['synthetic_empty_record_count']);
        self::assertSame(1, $out['synthetic_crlf_terminator_count']);
        self::assertSame(2, $out['synthetic_lf_terminator_count']);
        self::assertSame([
            'MIXED_RECORD_LINE_ENDINGS',
            'INCONSISTENT_COLUMN_COUNT',
            'EMPTY_RECORD_REVIEW_REQUIRED',
        ], $out['warning_codes']);
    }

    public function test_empty_source_or_bom_only_cannot_be_treated_as_a_real_import(): void
    {
        foreach (['', "\xEF\xBB\xBF"] as $input) {
            $out = (new SyntheticCsvStructureInspector())->inspect($input);
            self::assertSame(0, $out['synthetic_record_count']);
            self::assertSame(0, $out['synthetic_total_cell_count']);
            self::assertContains('NO_FICTIONAL_RECORDS', $out['warning_codes']);
            self::assertFalse($out['real_file_verified']);
            self::assertFalse($out['can_import']);
        }
    }

    public function test_trailing_delimiter_and_empty_quoted_field_are_counted(): void
    {
        $out = (new SyntheticCsvStructureInspector())->inspect("a,\"\",");
        self::assertSame(1, $out['synthetic_record_count']);
        self::assertSame(3, $out['synthetic_total_cell_count']);
        self::assertSame(3, $out['synthetic_expected_column_count']);
        self::assertSame(1, $out['synthetic_quoted_field_count']);
        self::assertSame([], $out['warning_codes']);
    }

    public function test_bad_quoting_utf8_nul_bare_cr_and_limits_fail_closed(): void
    {
        $long = str_repeat('a', 513);
        $tooManyCells = implode(',', array_fill(0, 13, 'x'));
        $tooManyRows = implode("\n", array_fill(0, 21, 'x'));
        $invalid = [
            [null, 'comma'], [[], 'comma'], [1, 'comma'],
            ['a,b', 1], ['a,b', 'pipe'], ['a,b', 'COMMA'],
            ['a,"unclosed', 'comma'],
            ['a"b",c', 'comma'],
            ["a,\"b\"x\n", 'comma'],
            ["a,\"b\"  \n", 'comma'],
            ["a,b\rc,d", 'comma'],
            ["a,\0b", 'comma'],
            ["a,\xFF", 'comma'],
            [$long, 'comma'],
            [$tooManyCells, 'comma'],
            [$tooManyRows, 'comma'],
            [str_repeat('x', 8193), 'comma'],
        ];
        foreach ($invalid as [$csv, $separator]) {
            try {
                (new SyntheticCsvStructureInspector())->inspect($csv, $separator);
                self::fail('Unsafe fictional CSV input incorrectly accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Estrutura CSV sintetica invalida.', $e->getMessage());
            }
        }
    }

    public function test_twenty_cell_boundary_and_deny_matrix_survive_a_clean_preview(): void
    {
        $csv = implode("\n", array_fill(0, 20, 'F'));
        $out = (new SyntheticCsvStructureInspector())->inspect($csv);
        self::assertSame(20, $out['synthetic_record_count']);
        self::assertSame(20, $out['synthetic_total_cell_count']);
        self::assertSame([], $out['warning_codes']);
        self::assertTrue($out['informational_only']);
        foreach ($out as $name => $value) {
            if ($name === 'authorized' || str_starts_with($name, 'can_')
                || str_ends_with($name, '_verified')) {
                self::assertFalse($value, $name);
            }
        }
    }
}
