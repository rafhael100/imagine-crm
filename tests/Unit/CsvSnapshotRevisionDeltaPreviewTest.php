<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvSnapshotRevisionDeltaPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvSnapshotRevisionDeltaPreviewTest extends TestCase
{
    private static function rows(): array
    {
        return [
            ['id', 'name'],
            ['fiction-1', 'sample-a'],
            ['fiction-2', 'sample-b'],
        ];
    }

    private static function mapping(): array
    {
        return [
            ['column' => 1, 'target' => 'fixture_id'],
            ['column' => 2, 'target' => 'fixture_name'],
        ];
    }

    private static function diff(array $rows, array $mapping): array
    {
        return CsvSnapshotRevisionDeltaPreview::compare(
            self::rows(), self::mapping(), $rows, $mapping,
        );
    }

    public function test_identical_fixture_is_not_invalidated_but_never_importable(): void
    {
        $result = self::diff(self::rows(), self::mapping());
        self::assertTrue($result['same_snapshot']);
        self::assertFalse($result['review_invalidated']);
        self::assertSame([], $result['changed_row_positions']);
        self::assertSame([], $result['added_row_positions']);
        self::assertSame([], $result['removed_row_positions']);
        self::assertSame([], $result['changed_mapping_positions']);
        self::assertSame([], $result['added_mapping_positions']);
        self::assertSame([], $result['removed_mapping_positions']);
        self::assertFalse($result['human_verified']);
        self::assertTrue($result['review_required']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_changed_data_cell_is_located_without_echoing_any_content(): void
    {
        $rows = self::rows();
        $rows[1][1] = 'edited-sensitive-fixture';
        $result = self::diff($rows, self::mapping());
        self::assertFalse($result['same_snapshot']);
        self::assertTrue($result['review_invalidated']);
        self::assertSame([2], $result['changed_row_positions']);
        self::assertSame([], $result['changed_mapping_positions']);
        self::assertStringNotContainsString('edited-sensitive-fixture',
            json_encode($result, JSON_THROW_ON_ERROR));
        self::assertFalse($result['import_permitted']);
    }

    public function test_changed_header_is_row_one_not_a_contact_identity(): void
    {
        $rows = self::rows();
        $rows[0][1] = 'renamed';
        self::assertSame([1], self::diff($rows, self::mapping())['changed_row_positions']);
    }

    public function test_append_and_remove_rows_are_reported_by_1_based_positions(): void
    {
        $rows = self::rows();
        $rows[] = ['fiction-3', 'sample-c'];
        $appended = self::diff($rows, self::mapping());
        self::assertSame([4], $appended['added_row_positions']);
        self::assertSame([], $appended['removed_row_positions']);
        self::assertSame([], $appended['changed_row_positions']);

        $removed = CsvSnapshotRevisionDeltaPreview::compare(
            $rows, self::mapping(), self::rows(), self::mapping(),
        );
        self::assertSame([4], $removed['removed_row_positions']);
        self::assertSame([], $removed['added_row_positions']);
        self::assertTrue($removed['review_invalidated']);
    }

    public function test_row_reordering_marks_changed_positions_not_stable_ids(): void
    {
        $rows = self::rows();
        [$rows[1], $rows[2]] = [$rows[2], $rows[1]];
        self::assertSame([2, 3], self::diff($rows, self::mapping())['changed_row_positions']);
    }

    public function test_mapping_target_only_change_is_detected_even_when_rows_equal(): void
    {
        $mapping = self::mapping();
        $mapping[1]['target'] = 'fixture_other';
        $r = self::diff(self::rows(), $mapping);
        self::assertSame([2], $r['changed_mapping_positions']);
        self::assertSame([], $r['changed_row_positions']);
        self::assertTrue($r['review_invalidated']);
    }

    public function test_mapping_reorder_is_not_treated_as_an_identical_snapshot(): void
    {
        $mapping = array_reverse(self::mapping());
        $r = self::diff(self::rows(), $mapping);
        self::assertSame([1, 2], $r['changed_mapping_positions']);
        self::assertTrue($r['review_invalidated']);
    }

    public function test_mapping_additions_and_removals_are_reported_without_targets(): void
    {
        $before = [['column' => 1, 'target' => 'fixture_id']];
        $after = self::mapping();
        $added = CsvSnapshotRevisionDeltaPreview::compare(self::rows(), $before, self::rows(), $after);
        self::assertSame([2], $added['added_mapping_positions']);
        self::assertSame([], $added['removed_mapping_positions']);
        self::assertSame(1, $added['before_mapping_count']);
        self::assertSame(2, $added['after_mapping_count']);

        $removed = CsvSnapshotRevisionDeltaPreview::compare(self::rows(), $after, self::rows(), $before);
        self::assertSame([2], $removed['removed_mapping_positions']);
        self::assertTrue($removed['review_invalidated']);
    }

    public function test_multiple_changed_and_trailing_removed_rows_are_all_reported(): void
    {
        $after = [['id', 'other'], ['fiction-1', 'changed']];
        $r = self::diff($after, self::mapping());
        self::assertSame([1, 2], $r['changed_row_positions']);
        self::assertSame([3], $r['removed_row_positions']);
        self::assertSame(3, $r['before_row_count']);
        self::assertSame(2, $r['after_row_count']);
    }

    public function test_binary_content_and_spacing_are_compared_byte_exactly(): void
    {
        $rows = self::rows();
        $rows[2][1] = "sample-b\x00";
        self::assertSame([3], self::diff($rows, self::mapping())['changed_row_positions']);
        $rows = self::rows();
        $rows[2][1] = 'sample-b ';
        self::assertSame([3], self::diff($rows, self::mapping())['changed_row_positions']);
    }

    public function test_empty_matrix_versions_are_still_not_authorizations(): void
    {
        $r = CsvSnapshotRevisionDeltaPreview::compare([], [], [], []);
        self::assertTrue($r['same_snapshot']);
        self::assertFalse($r['human_verified']);
        self::assertTrue($r['review_required']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_no_sensitive_payload_or_fingerprint_is_returned(): void
    {
        $before = [['secret_header'], ['fiction-secret-one']];
        $after = [['secret_header'], ['fiction-secret-two']];
        $r = CsvSnapshotRevisionDeltaPreview::compare(
            $before, [['column' => 1, 'target' => 'fixture_hidden_a']],
            $after, [['column' => 1, 'target' => 'fixture_hidden_b']],
        );
        $json = json_encode($r, JSON_THROW_ON_ERROR);
        foreach (['secret_header', 'fiction-secret', 'fixture_hidden', 'sha256'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
        self::assertSame([2], $r['changed_row_positions']);
        self::assertSame([1], $r['changed_mapping_positions']);
        self::assertSame([
            'same_snapshot', 'review_invalidated', 'before_row_count', 'after_row_count',
            'changed_row_positions', 'added_row_positions', 'removed_row_positions',
            'before_mapping_count', 'after_mapping_count', 'changed_mapping_positions',
            'added_mapping_positions', 'removed_mapping_positions',
            'human_verified', 'review_required', 'import_permitted',
        ], array_keys($r));
    }

    public function test_rejects_invalid_operational_mapping_before_reporting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvSnapshotRevisionDeltaPreview::compare(
            self::rows(), self::mapping(), self::rows(), [
                ['column' => 1, 'target' => 'lead.email'],
            ],
        );
    }

    public function test_rejects_invalid_matrix_even_when_other_version_is_well_formed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvSnapshotRevisionDeltaPreview::compare(self::rows(), self::mapping(), [
            ['id', 'name'], ['fiction', 123],
        ], []);
    }

    public function test_boundary_of_one_hundred_rows_reports_only_last_position(): void
    {
        $before = [['id']];
        for ($i = 1; $i < 100; ++$i) {
            $before[] = ['fiction-'.$i];
        }
        $after = $before;
        $after[99][0] = 'different-fixture';
        $r = CsvSnapshotRevisionDeltaPreview::compare($before, [], $after, []);
        self::assertSame([100], $r['changed_row_positions']);
        self::assertTrue($r['review_invalidated']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_key_order_in_mapping_record_does_not_create_false_change(): void
    {
        $mapping = [
            ['target' => 'fixture_id', 'column' => 1],
            ['target' => 'fixture_name', 'column' => 2],
        ];
        $r = self::diff(self::rows(), $mapping);
        self::assertSame([], $r['changed_mapping_positions']);
        self::assertTrue($r['same_snapshot']);
    }
}
