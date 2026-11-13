<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvTableCodec;
use App\Core\Portability\PortableCsvImportPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvUnicodePrefixHardeningTest extends TestCase
{
    public function test_spreadsheet_formula_with_unicode_and_repeated_apostrophe_prefix_is_denied(): void
    {
        $codec = new PortableCsvTableCodec();
        $cases = [
            "\u{200A}=1",     // hair space, Unicode separator
            "\u{2028}+1",     // line separator
            "\u{034F}@test",  // combining grapheme joiner
            "\u{180E}-10",    // Mongolian vowel separator
            "\u{FE0F}＝1",   // variation selector + fullwidth equals
            "''\u{200D}@cmd", // apostrophes and zero width joiner
            "\u{0085} ＠1",  // Unicode control and compatibility operator
            "\u{200A}'\u{034F}=1",
        ];

        foreach ($cases as $value) {
            try {
                $codec->encode([[$value]]);
                self::fail('Formula-bearing synthetic text was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Tabela CSV em memoria invalida.', $exception->getMessage());
                self::assertStringNotContainsString($value, $exception->getMessage());
            }
        }
    }

    public function test_safe_plain_text_with_unicode_prefix_retains_round_trip(): void
    {
        $codec = new PortableCsvTableCodec();
        $rows = [
            ["\u{200A}plain", "'ordinary"],
            ["\u{034F}note", "''text"],
            ['Café', 'safe body'],
        ];
        $csv = $codec->encode($rows);
        self::assertSame($rows, $codec->decode($csv));
    }

    public function test_aggregate_inspector_counts_hidden_formula_in_omitted_source_column(): void
    {
        $preview = (new PortableCsvImportPreview())->inspect(
            "name,ignored\nPUBLIC_SAMPLE,\u{200A}=HYPERLINK(1)",
            'comma',
            [['source_position' => 0, 'target_field' => 'name']],
            true,
        );
        self::assertSame(1, $preview['local_source_formula_refused_cell_count']);
        self::assertSame(0, $preview['local_mapped_formula_refused_cell_count']);
        self::assertFalse($preview['can_export']);
        self::assertFalse($preview['can_import']);
        self::assertStringNotContainsString('HYPERLINK', json_encode($preview, JSON_THROW_ON_ERROR));
    }
}
