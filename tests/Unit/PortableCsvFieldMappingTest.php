<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvFieldMapping;
use App\Core\Portability\PortableCsvTableCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvFieldMappingTest extends TestCase
{
    public function test_exact_ordered_field_mapping_excludes_unselected_columns_from_result(): void
    {
        $codec = new PortableCsvTableCodec();
        $csv = "name,internal,origin\r\nExemploA,NEVER_COPY_FIELD,site\r\nExemploB,NEVER_COPY_FIELD,chat\r\n";
        $data = $codec->decode($csv);
        $copy = $data;
        $bindings = [
            ['source_position' => 2, 'target_field' => 'origin'],
            ['source_position' => 0, 'target_field' => 'name'],
        ];
        $mapped = (new PortableCsvFieldMapping())->map($data, $bindings, true);
        self::assertSame($copy, $data);
        self::assertSame(['origin','name'], $mapped['target_field_codes']);
        self::assertSame(3, $mapped['source_column_count']);
        self::assertSame(2, $mapped['mapped_field_count']);
        self::assertSame(2, $mapped['data_row_count']);
        self::assertSame(['origin'=>'origin', 'name'=>'name'], $mapped['untrusted_header_titles']);
        self::assertSame([
            ['origin'=>'site', 'name'=>'ExemploA'],
            ['origin'=>'chat', 'name'=>'ExemploB'],
        ], $mapped['untrusted_mapped_rows']);
        self::assertSame([], $mapped['warning_codes']);
        self::assertStringNotContainsString(
            'NEVER_COPY_FIELD',
            json_encode($mapped, JSON_THROW_ON_ERROR),
        );
    }

    public function test_headerless_input_preserves_first_row_and_does_not_make_schema_claims(): void
    {
        $mapped = (new PortableCsvFieldMapping())->map(
            [['Example1','web'], ['Example2','chat']],
            [
                ['source_position'=>0, 'target_field'=>'name'],
                ['source_position'=>1, 'target_field'=>'origin'],
            ],
        );
        self::assertNull($mapped['untrusted_header_titles']);
        self::assertFalse($mapped['header_claimed']);
        self::assertSame(2, $mapped['data_row_count']);
        self::assertSame([
            ['name'=>'Example1', 'origin'=>'web'],
            ['name'=>'Example2', 'origin'=>'chat'],
        ], $mapped['untrusted_mapped_rows']);
        self::assertFalse($mapped['schema_verified']);
        self::assertFalse($mapped['can_import']);
    }

    public function test_consent_column_preserves_claim_but_grants_no_authority(): void
    {
        $out = (new PortableCsvFieldMapping())->map(
            [['consent','name'], ['true','Exemplo']],
            [
                ['source_position'=>0,'target_field'=>'consent'],
                ['source_position'=>1,'target_field'=>'name'],
            ],
            true,
        );
        self::assertTrue($out['consent_field_present_claimed']);
        self::assertSame(
            ['CONSENT_COLUMN_DOES_NOT_VERIFY_LEGAL_CONSENT'],
            $out['warning_codes'],
        );
        self::assertSame('true', $out['untrusted_mapped_rows'][0]['consent']);
        self::assertFalse($out['consent_evidence_verified']);
        self::assertFalse($out['can_confirm_consent']);
        self::assertFalse($out['can_import']);
    }

    public function test_duplicate_person_like_rows_are_not_automatically_merged_or_deduplicated(): void
    {
        $out = (new PortableCsvFieldMapping())->map(
            [['same','A'],['same','A'],['same','A']],
            [['source_position'=>0,'target_field'=>'name']],
        );
        self::assertSame(3, $out['data_row_count']);
        self::assertSame(
            [['name'=>'same'], ['name'=>'same'], ['name'=>'same']],
            $out['untrusted_mapped_rows'],
        );
        self::assertFalse($out['can_merge']);
    }

    public function test_empty_header_only_table_does_not_invent_a_record(): void
    {
        $out = (new PortableCsvFieldMapping())->map(
            [['name']], [['source_position'=>0,'target_field'=>'name']], true,
        );
        self::assertSame(['name'=>'name'], $out['untrusted_header_titles']);
        self::assertSame([], $out['untrusted_mapped_rows']);
        self::assertSame(0, $out['data_row_count']);
    }

    public function test_all_seven_candidate_fields_are_explicit_and_untrusted(): void
    {
        $names = ['name','email','phone','origin','consent','notes','status'];
        $bindings = [];
        foreach ($names as $i=>$field) {
            $bindings[] = ['source_position'=>$i,'target_field'=>$field];
        }
        $out = (new PortableCsvFieldMapping())->map(
            [array_fill(0,7,'fictional')], $bindings,
        );
        self::assertSame($names, $out['target_field_codes']);
        self::assertCount(7, $out['untrusted_mapped_rows'][0]);
        self::assertTrue($out['consent_field_present_claimed']);
        self::assertFalse($out['authorization_verified']);
        self::assertFalse($out['can_persist']);
    }

    public function test_unapproved_or_duplicate_bindings_fail_closed_without_content_echo(): void
    {
        $good = [
            ['source_position'=>0,'target_field'=>'name'],
            ['source_position'=>1,'target_field'=>'origin'],
        ];
        $bad = [
            [],
            [['source_position'=>0,'target_field'=>'password']],
            [['source_position'=>'0','target_field'=>'name']],
            [['source_position'=>0,'target_field'=>1]],
            [['source_position'=>0,'target_field'=>'name', 'private'=>'secret']],
            [['target_field'=>'name']],
            [['source_position'=>-1,'target_field'=>'name']],
            [['source_position'=>9,'target_field'=>'name']],
            [$good[0], $good[0]],
            [$good[0], ['source_position'=>1,'target_field'=>'name']],
            [1=>$good[0]],
            array_fill(0, 8, $good[0]),
            null, 'data',
        ];
        foreach ($bad as $bindings) {
            $this->assertRejects(fn () => (new PortableCsvFieldMapping())
                ->map([['a','b']], $bindings));
        }
    }

    public function test_invalid_source_table_does_not_skip_strict_projection_validation(): void
    {
        foreach ([
            null, [], [['a'],['a','b']], [[null]], [["\0"]],
            [1=>['a']], [[str_repeat('x', 4097)]],
        ] as $table) {
            $this->assertRejects(fn () => (new PortableCsvFieldMapping())
                ->map($table, [['source_position'=>0,'target_field'=>'name']]));
        }
    }

    public function test_source_formula_data_stays_untrusted_and_cannot_be_re_exported_by_codec(): void
    {
        $codec = new PortableCsvTableCodec();
        $rows = $codec->decode("=cmd,example");
        $out = (new PortableCsvFieldMapping())->map(
            $rows, [['source_position'=>0,'target_field'=>'name']],
        );
        self::assertSame('=cmd', $out['untrusted_mapped_rows'][0]['name']);
        try {
            $codec->encode([array_values($out['untrusted_mapped_rows'][0])]);
            self::fail('Formula value cannot be silently exported.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Tabela CSV em memoria invalida.', $e->getMessage());
        }
    }

    public function test_no_business_authority_or_web_route_is_created_by_field_projection(): void
    {
        $out = (new PortableCsvFieldMapping())->map(
            [['A','B']], [['source_position'=>0,'target_field'=>'origin']],
        );
        foreach ($out as $key=>$value) {
            if ($key==='authorized' || str_starts_with($key,'can_')
                || str_ends_with($key,'_verified')) {
                self::assertFalse($value,$key);
            }
        }
        self::assertTrue($out['informational_only']);
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        self::assertIsString($routes);
        self::assertStringNotContainsString('PortableCsvFieldMapping', $routes);
    }

    private function assertRejects(callable $operation): void
    {
        try {
            $operation();
            self::fail('Untrusted mapping incorrectly accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Mapeamento de campos CSV em memoria invalido.', $e->getMessage());
            self::assertStringNotContainsString('secret', $e->getMessage());
        }
    }
}
