<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvTableCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Pure codec contract. No API, file, tenant, PII, export permission or IO.
 * All cell contents are invented examples.
 */
final class PortableCsvTableCodecTest extends TestCase
{
    public function test_canonical_serialization_and_round_trip_preserve_quoted_fictional_data(): void
    {
        $rows = [
            ['title', 'details', 'notes', 'blank'],
            ['Alpha', 'value, with comma', 'Café "sample"', ''],
            ['Beta', "line one\r\nline two", "one\nother", 'semi;colon'],
            ['Gamma', ' plain text ', "End\r", 'neutral'],
        ];
        $copy = $rows;
        $codec = new PortableCsvTableCodec();
        $csv = $codec->encode($rows);
        self::assertSame($copy, $rows);
        self::assertStringStartsWith("title,details,notes,blank\r\n", $csv);
        self::assertStringContainsString('"value, with comma"', $csv);
        self::assertStringContainsString('"Café ""sample"""', $csv);
        self::assertStringContainsString(',""', $csv);
        self::assertStringEndsWith("\r\n", $csv);
        self::assertSame($rows, $codec->decode($csv));
        self::assertSame($csv, $codec->encode($codec->decode($csv)));
    }

    public function test_semicolon_mode_quotes_only_delimiters_and_preserves_unicode(): void
    {
        $rows = [
            ['nome', 'resumo'],
            ['Exemplo', 'Café; texto'],
            ['Outra linha', 'vírgula, aqui'],
        ];
        $codec = new PortableCsvTableCodec();
        $csv = $codec->encode($rows, 'semicolon');
        self::assertSame(
            "nome;resumo\r\nExemplo;\"Café; texto\"\r\nOutra linha;vírgula, aqui\r\n",
            $csv,
        );
        self::assertSame($rows, $codec->decode($csv, 'semicolon'));
        self::assertSame([['a', 'b'], ['c', 'd']], $codec->decode("a,b\nc,d\n"));
        self::assertSame([['a', 'b'], ['c', 'd']], $codec->decode("a,b\r\nc,d\r\n"));
    }

    public function test_blank_cell_and_empty_quoted_field_are_round_trippable(): void
    {
        $codec = new PortableCsvTableCodec();
        $csv = $codec->encode([['']]);
        self::assertSame("\"\"\r\n", $csv);
        self::assertSame([['']], $codec->decode($csv));
        self::assertSame([['x', '']], $codec->decode("x,\r\n"));
        self::assertSame([['', 'x']], $codec->decode(",x"));
    }

    public function test_decoding_untrusted_formulas_does_not_make_them_exportable(): void
    {
        $codec = new PortableCsvTableCodec();
        $payload = "=HYPERLINK(1),plain";
        // Parsing untrusted values is not a security clearance.
        self::assertSame([['=HYPERLINK(1)', 'plain']], $codec->decode($payload));
        $this->assertRejects(fn () => $codec->encode($codec->decode($payload)));
    }

    public function test_export_denies_formula_and_lookalike_prefixes_without_mutating_data(): void
    {
        $examples = [
            '=1+1', '+100', '-10', '@formula', "\t=HYPERLINK(1)",
            " \t@SUM(2)", "\u{FEFF}=1+1", "\u{200B}+2",
            "\u{00A0}-1", "\u{202E}@secret", "\u{2066}＝1",
            '＝A1', '＋A1', '－A1', '＠A1', "'=1+2", " '=HYPERLINK(1)",
        ];
        $codec = new PortableCsvTableCodec();
        foreach ($examples as $value) {
            $before = $value;
            $this->assertRejects(fn () => $codec->encode([[$value]]));
            self::assertSame($before, $value);
        }
        // False positives such as legitimate negative numbers are deliberately
        // blocked pending a reviewed spreadsheet client escape policy.
        self::assertSame("safe\r\n", $codec->encode([['safe']]));
    }

    public function test_csv_embedded_quote_and_newline_in_quoted_field_do_not_split_rows(): void
    {
        $codec = new PortableCsvTableCodec();
        $raw = "a,b\r\n\"a,\"\"b\"\"\r\nc\",d\r\n";
        $rows = $codec->decode($raw);
        self::assertSame(2, count($rows));
        self::assertSame("a,\"b\"\r\nc", $rows[1][0]);
        self::assertSame($raw, $codec->encode($rows));
    }

    public function test_decoding_rejects_malformed_quoting_irregular_widths_and_mixed_endings(): void
    {
        $codec = new PortableCsvTableCodec();
        $examples = [
            '', 'a,"unterminated', 'abc"def', 'a,"b"x',
            'a,"b" ', "a,b\rc,d",
            "a,b\r\nc,d\ne,f", "a,b\nc,d\r\ne,f",
            "a,b\nc", "a,b,c\nx,y", "\"a\"\"",
            "\xEF\xBB\xBFa,b", "a,\x00b", "a,\xC3\x28",
        ];
        foreach ($examples as $csv) {
            $this->assertRejects(fn () => $codec->decode($csv));
        }
    }

    public function test_encoding_rejects_invalid_shapes_invalid_utf8_and_binary_content(): void
    {
        $codec = new PortableCsvTableCodec();
        $invalids = [
            null, 'not rows', [], [null], [[]], [['a'], ['a', 'b']],
            [1 => ['a']], [[1 => 'a']], [[null]], [[1]], [[true]],
            [["a\0b"]], [["\xFF"]], [[str_repeat('x', 4097)]],
            array_fill(0, 101, ['a']),
            [array_fill(0, 33, 'a')],
        ];
        foreach ($invalids as $table) {
            $this->assertRejects(fn () => $codec->encode($table));
        }
        foreach (['pipe', 'COMMA', '', 1, null] as $sep) {
            $this->assertRejects(fn () => $codec->encode([['a']], $sep));
            $this->assertRejects(fn () => $codec->decode('a', $sep));
        }
    }

    public function test_decode_enforces_bounded_utf8_input_rows_columns_cells_and_bytes(): void
    {
        $codec = new PortableCsvTableCodec();
        $this->assertRejects(fn () => $codec->decode(str_repeat('x', 262145)));
        $this->assertRejects(fn () => $codec->decode(str_repeat('x', 4097)));
        $this->assertRejects(fn () => $codec->decode(implode(',', array_fill(0, 33, 'a'))));
        $this->assertRejects(fn () => $codec->decode(implode("\n", array_fill(0, 101, 'a'))));
        self::assertCount(100, $codec->decode(implode("\n", array_fill(0, 100, 'a'))));
        self::assertSame(
            [str_repeat('A', 4096)],
            $codec->decode(str_repeat('A', 4096))[0],
        );
    }

    public function test_repeated_fictional_round_trips_and_malformed_data_have_no_side_effects(): void
    {
        $codec = new PortableCsvTableCodec();
        $fragments = [
            'safe', 'Café', '', 'value, comma', 'quote"inside',
            "line\nbreak", 'a;b', "\t text", 'ééé', '0',
        ];

        // Deterministic exhaustive small fixture matrix, no random seed,
        // file IO, real identities, client data or clock dependence.
        foreach (['comma', 'semicolon'] as $mode) {
            for ($i = 0; $i < count($fragments); ++$i) {
                for ($j = 0; $j < count($fragments); ++$j) {
                    $rows = [[$fragments[$i], $fragments[$j]]];
                    $data = $codec->encode($rows, $mode);
                    self::assertSame($rows, $codec->decode($data, $mode));
                    self::assertSame($data, $codec->encode($codec->decode($data, $mode), $mode));
                }
            }
        }
    }

    private function assertRejects(callable $operation): void
    {
        try {
            $operation();
            self::fail('Malformed CSV or formula-bearing export unexpectedly accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Tabela CSV em memoria invalida.', $e->getMessage());
            self::assertStringNotContainsString('HYPERLINK', $e->getMessage());
        }
    }
}
