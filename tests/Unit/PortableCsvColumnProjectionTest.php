<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvColumnProjection;
use App\Core\Portability\PortableCsvTableCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Only fictional in-memory values; no export/import/DB business endpoint.
 */
final class PortableCsvColumnProjectionTest extends TestCase
{
    public function test_real_codec_decode_then_project_then_encode_is_deterministic_without_files(): void
    {
        $csv = "name,discard,origin\r\nFictionA,UNSELECTED_ONLY,website\r\nFictionB,UNSELECTED_ONLY,chat\r\n";
        $codec = new PortableCsvTableCodec();
        $input = $codec->decode($csv);
        $copy = $input;
        $out = (new PortableCsvColumnProjection())->project($input, [2, 0], true);
        self::assertSame($copy, $input);
        self::assertSame(3, $out['source_column_count']);
        self::assertSame(2, $out['selected_column_count']);
        self::assertSame(3, $out['source_record_count']);
        self::assertSame(2, $out['data_row_count']);
        self::assertTrue($out['header_claimed']);
        self::assertSame(['origin', 'name'], $out['untrusted_header']);
        self::assertSame([
            ['website', 'FictionA'],
            ['chat', 'FictionB'],
        ], $out['untrusted_data_rows']);
        $encoded = $codec->encode([
            $out['untrusted_header'],
            ...$out['untrusted_data_rows'],
        ]);
        self::assertSame("origin,name\r\nwebsite,FictionA\r\nchat,FictionB\r\n", $encoded);
        self::assertStringNotContainsString('UNSELECTED_ONLY', $encoded);
        self::assertStringNotContainsString(
            'UNSELECTED_ONLY',
            json_encode($out, JSON_THROW_ON_ERROR),
        );
    }

    public function test_headerless_projection_never_invents_header_or_discards_first_record(): void
    {
        $input = [
            ['A', 'secret-A', 'C'],
            ['B', 'secret-B', 'D'],
        ];
        $out = (new PortableCsvColumnProjection())->project($input, [2, 0], false);
        self::assertNull($out['untrusted_header']);
        self::assertFalse($out['header_claimed']);
        self::assertSame(2, $out['data_row_count']);
        self::assertSame([['C', 'A'], ['D', 'B']], $out['untrusted_data_rows']);
    }

    public function test_claimed_header_with_no_data_keeps_header_but_no_business_record(): void
    {
        $out = (new PortableCsvColumnProjection())->project([['name','origin']], [0,1], true);
        self::assertSame(['name','origin'], $out['untrusted_header']);
        self::assertSame([], $out['untrusted_data_rows']);
        self::assertSame(0, $out['data_row_count']);
    }

    public function test_original_order_can_be_preserved_or_permuted_explicitly(): void
    {
        $rows = [['A','B','C'],['D','E','F']];
        $service = new PortableCsvColumnProjection();
        self::assertSame($rows, $service->project($rows, [0,1,2])['untrusted_data_rows']);
        self::assertSame(
            [['C','A','B'],['F','D','E']],
            $service->project($rows, [2,0,1])['untrusted_data_rows'],
        );
    }

    public function test_unsafe_source_cells_are_untrusted_and_encoder_still_denies_formula_exports(): void
    {
        $rows = (new PortableCsvTableCodec())->decode("A,B\r\nordinary,=1+1\r\n");
        $result = (new PortableCsvColumnProjection())->project($rows, [1], true);
        self::assertSame([['=1+1']], $result['untrusted_data_rows']);

        try {
            (new PortableCsvTableCodec())->encode($result['untrusted_data_rows']);
            self::fail('Selecting a formula-bearing field must not authorize its export.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Tabela CSV em memoria invalida.', $e->getMessage());
        }
    }

    public function test_duplicate_out_of_range_and_type_confused_indices_are_rejected(): void
    {
        $table = [['A','B'],['C','D']];
        foreach ([
            [], [0,0], [2], [-1], ['0'], [0,1,2], [false], [0,1.0],
            [1=>0], [0=>1,2=>0], null, '0', true,
        ] as $positions) {
            $this->assertRejects(fn () => (new PortableCsvColumnProjection())
                ->project($table, $positions));
        }
        $this->assertRejects(fn () => (new PortableCsvColumnProjection())
            ->project($table, [0], 'yes'));
    }

    public function test_bad_untrusted_tables_are_rejected_without_echoing_cells(): void
    {
        $bad = [
            null, [], 'csv', [null], [[]], [[null]], [[3]], [[true]],
            [1 => ['a']], [[1 => 'a']], [['a'], ['a','b']],
            [[str_repeat('x',4097)]], [["\xFF"]], [["x\0y"]],
            array_fill(0, 101, ['X']),
            [array_fill(0, 33, 'X')],
        ];
        foreach ($bad as $table) {
            $this->assertRejects(fn () => (new PortableCsvColumnProjection())
                ->project($table, [0]));
        }
    }

    public function test_maximum_rows_width_and_unicode_boundaries_are_honored(): void
    {
        $data = array_fill(0, 100, array_fill(0, 32, 'é'));
        $out = (new PortableCsvColumnProjection())->project($data, [31,0,16]);
        self::assertSame(100, $out['source_record_count']);
        self::assertSame(100, $out['data_row_count']);
        self::assertSame(3, $out['selected_column_count']);
        self::assertSame(['é','é','é'], $out['untrusted_data_rows'][0]);
        self::assertSame(100, count($out['untrusted_data_rows']));
    }

    public function test_projection_is_pure_and_does_not_silently_merge_duplicates(): void
    {
        $rows = [['X','same'],['Y','same'],['Z','same']];
        $inputCopy = $rows;
        $service = new PortableCsvColumnProjection();
        $out = $service->project($rows, [1]);
        self::assertSame([['same'],['same'],['same']], $out['untrusted_data_rows']);
        self::assertSame(3, $out['data_row_count']);
        self::assertSame($rows, $inputCopy);
        self::assertSame($out, $service->project($rows, [1]));
    }

    public function test_module_is_not_publicly_routed(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        self::assertIsString($routes);
        self::assertStringNotContainsString('PortableCsvColumnProjection', $routes);
        self::assertStringNotContainsString('PortableCsvTableCodec', $routes);
    }

    private function assertRejects(callable $action): void
    {
        try {
            $action();
            self::fail('Invalid in-memory projection accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Projecao CSV em memoria invalida.', $e->getMessage());
        }
    }
}
