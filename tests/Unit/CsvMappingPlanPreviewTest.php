<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvMappingPlanPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvMappingPlanPreviewTest extends TestCase
{
    private static function fixture(): array
    {
        return [
            ['header_a', 'header_b', 'header_c'],
            ['invented-1', 'text', 'example'],
            ['invented-2', 'text', 'example'],
        ];
    }

    public function test_explicit_unique_positions_have_no_detected_issues_but_require_review(): void
    {
        $result = CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 1, 'target' => 'fixture_alpha'],
            ['column' => 2, 'target' => 'fixture_beta'],
            ['column' => 3, 'target' => 'fixture_gamma'],
        ]);

        self::assertSame([
            'header_columns' => 3,
            'data_rows' => 2,
            'mapping_count' => 3,
            'unmapped_columns' => [],
            'duplicate_source_columns' => [],
            'duplicate_target_mapping_positions' => [],
            'issue_codes' => [],
            'cell_warning_count' => 0,
            'review_required' => true,
        ], $result);
        self::assertArrayNotHasKey('allow_import', $result);
        self::assertArrayNotHasKey('records', $result);
    }

    public function test_unmapped_columns_are_informational_without_implicit_dropping_policy(): void
    {
        $result = CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 2, 'target' => 'fixture_beta'],
        ]);
        self::assertSame([1, 3], $result['unmapped_columns']);
        self::assertSame([], $result['issue_codes']);
        self::assertTrue($result['review_required']);
    }

    public function test_duplicate_source_reports_only_column_index(): void
    {
        $result = CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 1, 'target' => 'fixture_alpha'],
            ['column' => 1, 'target' => 'fixture_beta'],
            ['column' => 2, 'target' => 'fixture_gamma'],
        ]);

        self::assertSame([1], $result['duplicate_source_columns']);
        self::assertSame(['duplicate_source_mapping'], $result['issue_codes']);
        self::assertSame([], $result['duplicate_target_mapping_positions']);
    }

    public function test_duplicate_target_reports_only_mapping_entry_positions(): void
    {
        $result = CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 1, 'target' => 'fixture_same'],
            ['column' => 2, 'target' => 'fixture_other'],
            ['column' => 3, 'target' => 'fixture_same'],
        ]);

        self::assertSame([[1, 3]], $result['duplicate_target_mapping_positions']);
        self::assertSame(['duplicate_target_mapping'], $result['issue_codes']);
        self::assertSame([], $result['duplicate_source_columns']);
    }

    public function test_both_types_of_mapping_collision_are_detected(): void
    {
        $result = CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 2, 'target' => 'fixture_same'],
            ['column' => 2, 'target' => 'fixture_same'],
        ]);
        self::assertSame([2], $result['duplicate_source_columns']);
        self::assertSame([[1, 2]], $result['duplicate_target_mapping_positions']);
        self::assertSame(['duplicate_source_mapping', 'duplicate_target_mapping'], $result['issue_codes']);
    }

    public function test_missing_header_is_reported_not_assumed(): void
    {
        $result = CsvMappingPlanPreview::inspect([], []);
        self::assertSame(0, $result['header_columns']);
        self::assertSame(0, $result['mapping_count']);
        self::assertSame(['missing_header'], $result['issue_codes']);
        self::assertTrue($result['review_required']);
    }

    public function test_empty_and_duplicate_headers_are_reported_without_names(): void
    {
        $result = CsvMappingPlanPreview::inspect([
            ['repeated', '', 'repeated'],
            ['fiction-1', 'something', 'other'],
        ], [
            ['column' => 1, 'target' => 'fixture_a'],
            ['column' => 2, 'target' => 'fixture_b'],
            ['column' => 3, 'target' => 'fixture_c'],
        ]);

        self::assertSame(['empty_header', 'duplicate_header'], $result['issue_codes']);
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('repeated', $json);
        self::assertStringNotContainsString('something', $json);
    }

    public function test_ragged_rows_and_formula_like_cells_are_flagged_together(): void
    {
        $result = CsvMappingPlanPreview::inspect([
            ['header_a', 'header_b'],
            ['fiction', '=1+1'],
            ['fiction'],
        ], [
            ['column' => 1, 'target' => 'fixture_a'],
            ['column' => 2, 'target' => 'fixture_b'],
        ]);

        self::assertSame(['ragged_data_rows', 'risky_cells'], $result['issue_codes']);
        self::assertSame(1, $result['cell_warning_count']);
        self::assertSame(2, $result['data_rows']);
    }

    public function test_report_does_not_reveal_either_target_labels_or_cells(): void
    {
        $result = CsvMappingPlanPreview::inspect([
            ['secret_header_one', 'secret_header_two'],
            ['invented-person-record', '=HYPERLINK("https://bad.invalid")'],
        ], [
            ['column' => 1, 'target' => 'fixture_confidential_field'],
            ['column' => 2, 'target' => 'fixture_other_field'],
        ]);

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([
            'secret_header_one',
            'secret_header_two',
            'invented-person-record',
            'HYPERLINK',
            'bad.invalid',
            'fixture_confidential_field',
            'fixture_other_field',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
        self::assertSame(['risky_cells'], $result['issue_codes']);
    }

    public function test_rejects_assoc_assignment_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), [
            'first' => ['column' => 1, 'target' => 'fixture_alpha'],
        ]);
    }

    public function test_rejects_unexpected_mapping_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), [[
            'column' => 1,
            'target' => 'fixture_alpha',
            'tenant_id' => 'forged',
        ]]);
    }

    public function test_rejects_untrusted_operational_target_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 1, 'target' => 'lead.email'],
        ]);
    }

    public function test_rejects_out_of_range_source_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 4, 'target' => 'fixture_alpha'],
        ]);
    }

    public function test_rejects_source_column_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => 0, 'target' => 'fixture_alpha'],
        ]);
    }

    public function test_rejects_non_integer_column_index(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), [
            ['column' => '1', 'target' => 'fixture_alpha'],
        ]);
    }

    public function test_rejects_a_mapping_when_header_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect([], [
            ['column' => 1, 'target' => 'fixture_alpha'],
        ]);
    }

    public function test_accepts_exactly_twenty_five_declared_fixture_destinations(): void
    {
        $header = [];
        $mapping = [];
        for ($i = 1; $i <= 25; ++$i) {
            $header[] = 'header_'.$i;
            $mapping[] = ['column' => $i, 'target' => 'fixture_field_'.$i];
        }

        $result = CsvMappingPlanPreview::inspect([$header], $mapping);
        self::assertSame(25, $result['mapping_count']);
        self::assertSame([], $result['unmapped_columns']);
        self::assertSame([], $result['issue_codes']);
    }

    public function test_rejects_more_than_twenty_five_mapping_entries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect(self::fixture(), array_fill(
            0, 26, ['column' => 1, 'target' => 'fixture_alpha'],
        ));
    }

    public function test_invalid_or_oversized_rows_propagate_the_underlying_failure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvMappingPlanPreview::inspect([['one'], [str_repeat('x', 4097)]], []);
    }

    public function test_duplicate_targets_preserve_first_encounter_group_order(): void
    {
        $result = CsvMappingPlanPreview::inspect([
            ['one', 'two', 'three', 'four'],
        ], [
            ['column' => 1, 'target' => 'fixture_z'],
            ['column' => 2, 'target' => 'fixture_a'],
            ['column' => 3, 'target' => 'fixture_z'],
            ['column' => 4, 'target' => 'fixture_a'],
        ]);
        self::assertSame([[1, 3], [2, 4]], $result['duplicate_target_mapping_positions']);
    }
}
