<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticImportColumnMappingInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticImportColumnMappingInspectorTest extends TestCase
{
    private static function column(
        int $position,
        string $target,
        int $nonempty = 3,
        int $formula = 0,
    ): array {
        return [
            'source_position' => $position,
            'target_code' => $target,
            'nonempty_count' => $nonempty,
            'formula_warning_count' => $formula,
        ];
    }

    private static function fixture(): array
    {
        return [
            'entity_kind' => 'leads',
            'declared_row_count' => 4,
            'declared_duplicate_candidates' => 2,
            'target_state' => 'populated',
            'review_claimed' => true,
            'columns' => [
                self::column(0, 'name', 4),
                self::column(1, 'email', 4, 1),
                self::column(2, 'email', 3, 2),
                self::column(3, 'consent', 2),
                self::column(4, 'ignore', 1),
            ],
        ];
    }

    public function test_conflicting_mapping_is_informational_and_never_authorizes_import(): void
    {
        $request = self::fixture();
        $before = $request;
        $inspector = new SyntheticImportColumnMappingInspector();
        $out = $inspector->inspect($request);
        self::assertSame($before, $request);
        self::assertSame($out, $inspector->inspect($request));
        self::assertSame('leads', $out['entity_kind']);
        self::assertSame(4, $out['declared_row_count']);
        self::assertSame(5, $out['declared_source_column_count']);
        self::assertSame(4, $out['mapped_column_count']);
        self::assertSame(1, $out['ignored_column_count']);
        self::assertSame(1, $out['target_collision_count']);
        self::assertSame(3, $out['declared_formula_warning_count']);
        self::assertSame(2, $out['declared_duplicate_candidate_count']);
        self::assertTrue($out['consent_column_present_claimed']);
        self::assertSame([
            'SOURCE_COLUMNS_INTENTIONALLY_UNMAPPED',
            'MULTIPLE_SOURCES_SHARE_ONE_TARGET',
            'SPREADSHEET_FORMULA_RISK_REQUIRES_REVIEW',
            'CONSENT_EVIDENCE_CANNOT_BE_IMPORTED_AS_AUTHORITY',
            'DUPLICATE_CANDIDATES_REQUIRE_REVIEW',
            'EXISTING_TARGET_NEEDS_CONFLICT_DECISIONS',
        ], $out['warning_codes']);
        self::assertSame(6, $out['warning_count']);
        self::assertFalse($out['can_confirm_consent']);
        self::assertFalse($out['can_merge']);
        self::assertFalse($out['can_import']);
    }

    public function test_empty_table_does_not_invent_required_lead_fields(): void
    {
        $input = self::fixture();
        $input['declared_row_count'] = 0;
        $input['declared_duplicate_candidates'] = 0;
        $input['target_state'] = 'empty';
        $input['review_claimed'] = false;
        $input['columns'] = [];
        $out = (new SyntheticImportColumnMappingInspector())->inspect($input);
        self::assertSame(0, $out['mapped_column_count']);
        self::assertSame(0, $out['declared_source_column_count']);
        self::assertSame([
            'NO_SOURCE_COLUMNS_DECLARED',
            'REVIEW_CONFIRMATION_NOT_CLAIMED',
        ], $out['warning_codes']);
        self::assertFalse($out['field_mapping_verified']);
    }

    public function test_contact_only_phone_mapping_is_allowed_without_universal_name_requirement(): void
    {
        $input = self::fixture();
        $input['entity_kind'] = 'contacts';
        $input['columns'] = [self::column(0, 'phone', 4)];
        $input['declared_duplicate_candidates'] = 0;
        $input['target_state'] = 'empty';
        $out = (new SyntheticImportColumnMappingInspector())->inspect($input);
        self::assertSame([], $out['warning_codes']);
        self::assertFalse($out['row_content_verified']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_import']);
        self::assertFalse($out['target_schema_verified']);
    }

    public function test_a_claimed_review_never_verifies_consent_or_tenant_scope(): void
    {
        $input = self::fixture();
        $input['target_state'] = 'unknown';
        $out = (new SyntheticImportColumnMappingInspector())->inspect($input);
        self::assertContains('TARGET_STATE_NOT_VERIFIED', $out['warning_codes']);
        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
        self::assertTrue($out['informational_only']);
    }

    public function test_maximum_twelve_columns_twenty_rows_is_bounded_and_deterministic(): void
    {
        $input = self::fixture();
        $input['declared_row_count'] = 20;
        $input['declared_duplicate_candidates'] = 20;
        $input['columns'] = [];
        for ($i = 0; $i < 12; ++$i) {
            $input['columns'][] = self::column($i, 'name', 20, 20);
        }
        $out = (new SyntheticImportColumnMappingInspector())->inspect($input);
        self::assertSame(12, $out['mapped_column_count']);
        self::assertSame(11, $out['target_collision_count']);
        self::assertSame(240, $out['declared_formula_warning_count']);
        self::assertSame(12, $out['declared_source_column_count']);
        self::assertFalse($out['can_persist']);
    }

    public function test_rejects_all_extra_data_wrong_types_sparse_indices_and_fake_headers(): void
    {
        $good = self::fixture();
        $invalids = [
            null, 'table', [],
            array_merge($good, ['email' => 'private@example.test']),
            array_replace($good, ['entity_kind' => 'accounts']),
            array_replace($good, ['entity_kind' => null]),
            array_replace($good, ['declared_row_count' => -1]),
            array_replace($good, ['declared_row_count' => 21]),
            array_replace($good, ['declared_row_count' => '4']),
            array_replace($good, ['declared_duplicate_candidates' => 5]),
            array_replace($good, ['declared_duplicate_candidates' => -1]),
            array_replace($good, ['target_state' => 'verified']),
            array_replace($good, ['review_claimed' => 1]),
            array_replace($good, ['columns' => 'CSV']),
            array_replace($good, ['columns' => [1 => self::column(0, 'name')]]),
            array_replace($good, ['columns' => [
                self::column(0, 'name'), self::column(0, 'email'),
            ]]),
            array_replace($good, ['columns' => [self::column(4, 'name')]]),
            array_replace($good, ['columns' => [self::column(-1, 'name')]]),
            array_replace($good, ['columns' => [self::column(0, 'password')]]),
            array_replace($good, ['columns' => [[...self::column(0, 'name'),
                'header' => 'private@example.test']]]),
            array_replace($good, ['columns' => [array_replace(
                self::column(0, 'name'), ['nonempty_count' => 5],
            )]]),
            array_replace($good, ['columns' => [array_replace(
                self::column(0, 'name'), ['formula_warning_count' => 4],
            )]]),
            array_replace($good, ['columns' => [array_replace(
                self::column(0, 'name'), ['source_position' => '0'],
            )]]),
            array_replace($good, ['columns' => [array_replace(
                self::column(0, 'name'), ['formula_warning_count' => true],
            )]]),
            array_replace($good, ['columns' => array_fill(0, 13, self::column(0, 'name'))]),
        ];
        foreach ($invalids as $candidate) {
            try {
                (new SyntheticImportColumnMappingInspector())->inspect($candidate);
                self::fail('Unsafe column metadata accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Mapeamento sintetico de colunas invalido.', $e->getMessage());
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
            }
        }
    }

    public function test_result_only_includes_aggregate_counts_and_fixed_codes(): void
    {
        $out = (new SyntheticImportColumnMappingInspector())->inspect(self::fixture());
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            '"email"', '"phone"', '"header"', '"rows"', '"columns"',
            '"payload"', '"person_id"', '"tenant_id"', '"credential"',
            '"source_name"', '"token"', '"user_id"', '"file_path"',
        ] as $sensitive) {
            self::assertStringNotContainsString($sensitive, $json);
        }
    }
}
