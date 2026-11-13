<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvImportPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvImportPreviewTest extends TestCase
{
    private static function bind(int $source, string $target): array
    {
        return ['source_position' => $source, 'target_field' => $target];
    }

    public function test_clean_local_sample_is_counted_but_never_authorized_for_import(): void
    {
        $csv = "name,unused,origin\r\nExampleA,NOT_SELECTED,site\r\nExampleB,NOT_SELECTED,chat\r\n";
        $bindings = [self::bind(0, 'name'), self::bind(2, 'origin')];
        $copy = [$csv, $bindings];
        $inspector = new PortableCsvImportPreview();
        $out = $inspector->inspect($csv, 'comma', $bindings, true);
        self::assertSame($copy, [$csv, $bindings]);
        self::assertSame($out, $inspector->inspect($csv, 'comma', $bindings, true));
        self::assertSame('comma', $out['declared_delimiter']);
        self::assertTrue($out['header_claimed']);
        self::assertSame(3, $out['local_source_column_count']);
        self::assertSame(2, $out['local_selected_field_count']);
        self::assertSame(1, $out['local_omitted_column_count']);
        self::assertSame(2, $out['local_data_row_count']);
        self::assertSame(0, $out['local_empty_mapped_value_count']);
        self::assertSame(0, $out['local_source_formula_refused_cell_count']);
        self::assertSame(0, $out['local_mapped_formula_refused_cell_count']);
        self::assertSame([
            'EXPORT_AND_IMPORT_AUTHORITY_NOT_VERIFIED',
            'HEADER_MEANING_NOT_VERIFIED',
            'SOURCE_COLUMNS_OMITTED_BY_EXPLICIT_MAPPING',
        ], $out['warning_codes']);
        self::assertCount(6, $out['unfulfilled_gate_codes']);
        self::assertStringNotContainsString(
            'NOT_SELECTED', json_encode($out, JSON_THROW_ON_ERROR),
        );
        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
    }

    public function test_risky_unselected_column_is_not_silent_and_does_not_affect_mapped_count(): void
    {
        $out = (new PortableCsvImportPreview())->inspect(
            "name,ignored\nExample,=HYPERLINK(1)",
            'comma',
            [self::bind(0, 'name')],
            true,
        );
        self::assertSame(1, $out['local_source_formula_refused_cell_count']);
        self::assertSame(0, $out['local_mapped_formula_refused_cell_count']);
        self::assertContains(
            'SOURCE_CONTAINS_CELLS_REFUSED_BY_STRICT_CSV_WRITER',
            $out['warning_codes'],
        );
        self::assertNotContains(
            'MAPPED_VALUES_CONTAIN_CELLS_REFUSED_BY_STRICT_CSV_WRITER',
            $out['warning_codes'],
        );
        self::assertFalse($out['can_import']);
    }

    public function test_risky_selected_column_is_detected_by_identical_codec_refusal(): void
    {
        $out = (new PortableCsvImportPreview())->inspect(
            "name,value\nExample,\" \t@SUM(1)\"",
            'comma',
            [self::bind(1, 'notes')],
            true,
        );
        self::assertSame(1, $out['local_source_formula_refused_cell_count']);
        self::assertSame(1, $out['local_mapped_formula_refused_cell_count']);
        self::assertContains(
            'MAPPED_VALUES_CONTAIN_CELLS_REFUSED_BY_STRICT_CSV_WRITER',
            $out['warning_codes'],
        );
        self::assertFalse($out['spreadsheet_client_behavior_verified']);
        self::assertFalse($out['can_export']);
    }

    public function test_header_only_and_empty_mapped_field_are_distinct_warnings(): void
    {
        $one = (new PortableCsvImportPreview())->inspect(
            "name,origin", 'comma', [self::bind(0, 'name')], true,
        );
        self::assertSame(0, $one['local_data_row_count']);
        self::assertContains('NO_DATA_RECORDS_IN_LOCAL_SAMPLE', $one['warning_codes']);
        self::assertSame(0, $one['local_empty_mapped_value_count']);

        $two = (new PortableCsvImportPreview())->inspect(
            "name,origin\n,chat",
            'comma',
            [self::bind(0, 'name')],
            true,
        );
        self::assertSame(1, $two['local_data_row_count']);
        self::assertSame(1, $two['local_empty_mapped_value_count']);
        self::assertContains('MAPPED_FIELDS_HAVE_EMPTY_VALUES', $two['warning_codes']);
    }

    public function test_consent_value_claim_does_not_become_legal_evidence(): void
    {
        $out = (new PortableCsvImportPreview())->inspect(
            "name;consent\nFiction;true",
            'semicolon',
            [self::bind(0, 'name'), self::bind(1, 'consent')],
            true,
        );
        self::assertSame('semicolon', $out['declared_delimiter']);
        self::assertTrue($out['consent_column_present_claimed']);
        self::assertContains('CONSENT_COLUMN_IS_NOT_LEGAL_CONSENT', $out['warning_codes']);
        self::assertFalse($out['consent_evidence_verified']);
        self::assertFalse($out['can_confirm_consent']);
    }

    public function test_headerless_single_record_never_disappears(): void
    {
        $out = (new PortableCsvImportPreview())->inspect(
            "Alpha,Beta",
            'comma',
            [self::bind(0, 'origin')],
            false,
        );
        self::assertSame(1, $out['local_data_row_count']);
        self::assertFalse($out['header_claimed']);
        self::assertNotContains('HEADER_MEANING_NOT_VERIFIED', $out['warning_codes']);
        self::assertFalse($out['tenant_scope_verified']);
    }

    public function test_malformed_csv_or_binding_is_rejected_without_echoing_values(): void
    {
        $invalids = [
            ['', 'comma', [self::bind(0, 'name')], true],
            ['a,"unterminated', 'comma', [self::bind(0, 'name')], false],
            ['PrivateExample', 'pipe', [self::bind(0, 'name')], false],
            ['PrivateExample', 'comma', [], false],
            ['PrivateExample', 'comma', [self::bind(3, 'name')], false],
            ['PrivateExample', 'comma', [self::bind(0, 'password')], false],
            ['PrivateExample', 'comma', [self::bind(0, 'name')], 'true'],
        ];
        foreach ($invalids as $args) {
            try {
                (new PortableCsvImportPreview())->inspect(...$args);
                self::fail('Unsafe import preview accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Previa CSV local invalida.', $e->getMessage());
                self::assertStringNotContainsString('PrivateExample', $e->getMessage());
            }
        }
    }

    public function test_response_never_exposes_cells_headers_or_person_identifiers(): void
    {
        $out = (new PortableCsvImportPreview())->inspect(
            "label,email\nSENSITIVE_FIXTURE,PRIVATE_FIXTURE",
            'comma',
            [self::bind(0, 'name'), self::bind(1, 'email')],
            true,
        );
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            'SENSITIVE_FIXTURE', 'PRIVATE_FIXTURE', '"header_titles"',
            '"rows"', '"cells"', '"email"', '"name"', '"person"',
            '"tenant_id"', '"token"', '"password"', '"file_path"',
        ] as $source) {
            self::assertStringNotContainsString($source, $json);
        }
    }
}
