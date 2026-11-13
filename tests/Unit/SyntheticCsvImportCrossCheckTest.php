<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportCrossCheck;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvImportCrossCheckTest extends TestCase
{
    private static function operation(string $format = 'import_csv'): array
    {
        return [
            'plan' => 'basic',
            'operation' => $format,
            'attachment_quota_percent' => 100,
            'confirmation_claimed' => true,
            'declared_package' => 'table',
            'duplicate_warning_count' => 0,
            'formula_warning_count' => 0,
            'secret_fields_claimed' => false,
            'entity_kinds' => ['leads'],
            'target_account_state' => 'empty',
        ];
    }

    private static function mapping(int $rows = 2, array $fields = ['name', 'email']): array
    {
        $columns = [];
        foreach ($fields as $index => $target) {
            $columns[] = [
                'source_position' => $index,
                'target_code' => $target,
                'nonempty_count' => min(2, $rows),
                'formula_warning_count' => 0,
            ];
        }

        return [
            'entity_kind' => 'leads',
            'declared_row_count' => $rows,
            'declared_duplicate_candidates' => 0,
            'target_state' => 'empty',
            'review_claimed' => true,
            'columns' => $columns,
        ];
    }

    public function test_even_perfect_matching_csv_and_mapping_never_authorize_import(): void
    {
        $csv = "name,email\nFictionA,fiction-a\nFictionB,fiction-b";
        $op = self::operation();
        $map = self::mapping();
        $before = [$csv, $op, $map];
        $service = new SyntheticCsvImportCrossCheck();
        $out = $service->inspect($csv, 'comma', true, $op, $map);

        self::assertSame($before, [$csv, $op, $map]);
        self::assertSame($out, $service->inspect($csv, 'comma', true, $op, $map));
        self::assertSame('import_csv', $out['claimed_import_format']);
        self::assertTrue($out['header_record_claimed']);
        self::assertSame(3, $out['synthetic_sample_record_count']);
        self::assertSame(2, $out['synthetic_sample_data_row_count']);
        self::assertSame(2, $out['synthetic_sample_column_count']);
        self::assertSame(2, $out['declared_mapping_row_count']);
        self::assertSame(2, $out['declared_mapping_column_count']);
        self::assertSame([], $out['warning_codes']);
        self::assertCount(6, $out['unfulfilled_gate_codes']);
        self::assertTrue($out['informational_only']);

        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
    }

    public function test_ragged_rows_and_mapping_width_or_count_disagreements_are_visible(): void
    {
        $out = (new SyntheticCsvImportCrossCheck())->inspect(
            "name,email\none,two\nthree",
            'comma',
            true,
            self::operation(),
            self::mapping(4, ['name', 'email', 'origin']),
        );
        foreach ([
            'SAMPLE_HAS_IRREGULAR_RECORD_WIDTH',
            'SAMPLE_COLUMN_COUNT_DISAGREES_WITH_MAPPING',
            'SAMPLE_ROW_COUNT_DISAGREES_WITH_MAPPING',
            'CSV_INCONSISTENT_COLUMN_COUNT',
        ] as $reason) {
            self::assertContains($reason, $out['warning_codes']);
        }
        self::assertSame(2, $out['synthetic_sample_data_row_count']);
        self::assertSame(4, $out['declared_mapping_row_count']);
        self::assertSame(3, $out['declared_mapping_column_count']);
        self::assertFalse($out['can_import']);
    }

    public function test_missing_header_on_empty_sample_is_denied_without_guessing(): void
    {
        $out = (new SyntheticCsvImportCrossCheck())->inspect(
            '', 'comma', true, self::operation(), self::mapping(0, []),
        );
        self::assertContains('HEADER_CLAIMED_WITHOUT_RECORD', $out['warning_codes']);
        self::assertContains('CSV_NO_FICTIONAL_RECORDS', $out['warning_codes']);
        self::assertSame(0, $out['synthetic_sample_data_row_count']);
        self::assertFalse($out['header_meaning_verified']);
    }

    public function test_headerless_semicolon_format_has_no_automatic_row_subtraction(): void
    {
        $out = (new SyntheticCsvImportCrossCheck())->inspect(
            "Fictitious;A\nFictitious;B",
            'semicolon',
            false,
            self::operation(),
            self::mapping(),
        );
        self::assertSame('semicolon', $out['declared_delimiter']);
        self::assertFalse($out['header_record_claimed']);
        self::assertSame(2, $out['synthetic_sample_record_count']);
        self::assertSame(2, $out['synthetic_sample_data_row_count']);
        self::assertSame([], $out['warning_codes']);
    }

    public function test_quoted_formula_risk_is_preserved_not_treated_as_escaped(): void
    {
        $out = (new SyntheticCsvImportCrossCheck())->inspect(
            "name,email\nFiction,\"=1+1\"",
            'comma',
            true,
            self::operation(),
            self::mapping(1),
        );
        self::assertSame(1, $out['synthetic_sample_risk_flagged_cells']);
        self::assertContains('SAMPLE_CELL_RISK_REQUIRES_SEPARATE_REVIEW', $out['warning_codes']);
        self::assertContains('CSV_CELL_FORMULA_PREFIX_SIGNAL', $out['warning_codes']);
        self::assertFalse($out['formula_safety_verified']);
        self::assertFalse($out['can_import']);
    }

    public function test_xlsx_and_other_operations_cannot_be_disguised_as_csv(): void
    {
        foreach (['import_xlsx', 'export_csv', 'restore_complete', 'backup_complete'] as $operation) {
            try {
                (new SyntheticCsvImportCrossCheck())->inspect(
                    "name,email\nA,B", 'comma', true,
                    self::operation($operation), self::mapping(1),
                );
                self::fail('Non-CSV import intent unexpectedly accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao cruzada CSV sintetica invalida.', $e->getMessage());
            }
        }
    }

    public function test_invalid_both_contracts_are_rejected_without_echoing_sensitive_values(): void
    {
        $invalids = [
            ['private@example.test', 'pipe', true, self::operation(), self::mapping()],
            ['name,email', 'comma', 'yes', self::operation(), self::mapping()],
            ['a,"unterminated', 'comma', true, self::operation(), self::mapping()],
            ['name,email', 'comma', true, array_merge(self::operation(), ['email' => 'private@example.test']), self::mapping()],
            ['name,email', 'comma', true, self::operation(), array_merge(self::mapping(), ['token' => 'private@example.test'])],
        ];
        foreach ($invalids as $args) {
            try {
                (new SyntheticCsvImportCrossCheck())->inspect(...$args);
                self::fail('Malformed cross-check input accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao cruzada CSV sintetica invalida.', $e->getMessage());
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
            }
        }
    }

    public function test_response_never_contains_file_or_customer_payloads(): void
    {
        $out = (new SyntheticCsvImportCrossCheck())->inspect(
            "name,email\nPersonA,ExampleB", 'comma', true, self::operation(), self::mapping(1),
        );
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            'PersonA', 'ExampleB', '"email"', '"phone"', '"rows"',
            '"columns"', '"file_path"', '"password"', '"token"', '"tenant_id"',
        ] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
        self::assertFalse($out['can_write_file']);
        self::assertFalse($out['can_confirm_consent']);
    }
}
