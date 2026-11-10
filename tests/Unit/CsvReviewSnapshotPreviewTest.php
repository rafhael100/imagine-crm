<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvReviewSnapshotPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvReviewSnapshotPreviewTest extends TestCase
{
    private static function rows(): array
    {
        return [
            ['id', 'description'],
            ['fiction-1', 'note-a'],
            ['fiction-2', '=1+1'],
            ['fiction-3', 'note-b'],
        ];
    }

    private static function mapping(): array
    {
        return [
            ['column' => 1, 'target' => 'fixture_id'],
            ['column' => 2, 'target' => 'fixture_description'],
        ];
    }

    public function test_same_fixture_and_mapping_have_deterministic_versioned_digest(): void
    {
        $a = CsvReviewSnapshotPreview::fingerprint(self::rows(), self::mapping());
        $b = CsvReviewSnapshotPreview::fingerprint(self::rows(), self::mapping());
        self::assertSame($a, $b);
        self::assertMatchesRegularExpression('/\Afixture-sha256-v1:[0-9a-f]{64}\z/D', $a);
    }

    public function test_current_fixture_can_reconcile_simulated_corrections_without_import_permission(): void
    {
        $rows = self::rows();
        $mapping = self::mapping();
        $marker = CsvReviewSnapshotPreview::fingerprint($rows, $mapping);
        $report = CsvReviewSnapshotPreview::reconcile($rows, $mapping, $marker, [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 3, 'status' => 'fixture_needs_correction'],
        ]);

        self::assertSame('fixture-sha256-v1', $report['snapshot_version']);
        self::assertTrue($report['snapshot_matches']);
        self::assertSame(3, $report['data_rows']);
        self::assertSame(1, $report['pending_count']);
        self::assertSame(1, $report['reviewed_no_findings_count']);
        self::assertSame(1, $report['needs_correction_count']);
        self::assertFalse($report['human_verified']);
        self::assertTrue($report['review_required']);
        self::assertFalse($report['import_permitted']);
        self::assertArrayNotHasKey('fingerprint', $report);
        self::assertArrayNotHasKey('rows', $report);
        self::assertArrayNotHasKey('mapping', $report);
    }

    public function test_equal_report_without_decisions_never_grants_approval(): void
    {
        $rows = [['id'], ['fiction']];
        $marker = CsvReviewSnapshotPreview::fingerprint($rows);
        $report = CsvReviewSnapshotPreview::reconcile($rows, [], $marker);
        self::assertTrue($report['snapshot_matches']);
        self::assertSame(1, $report['pending_count']);
        self::assertFalse($report['human_verified']);
        self::assertFalse($report['import_permitted']);
    }

    public function test_a_cell_changed_after_review_invalidates_the_marker(): void
    {
        $rows = self::rows();
        $marker = CsvReviewSnapshotPreview::fingerprint($rows, self::mapping());
        $rows[1][1] = 'edited-after-review';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Synthetic batch changed; review decisions are stale.');
        CsvReviewSnapshotPreview::reconcile($rows, self::mapping(), $marker, [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ]);
    }

    public function test_appending_a_row_does_not_allow_old_decisions_to_be_replayed(): void
    {
        $rows = self::rows();
        $marker = CsvReviewSnapshotPreview::fingerprint($rows, self::mapping());
        $rows[] = ['fiction-4', 'note'];
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile($rows, self::mapping(), $marker, []);
    }

    public function test_deleting_a_row_also_invalidates_the_previous_version(): void
    {
        $rows = self::rows();
        $marker = CsvReviewSnapshotPreview::fingerprint($rows, self::mapping());
        array_splice($rows, 1, 1);
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile($rows, self::mapping(), $marker, []);
    }

    public function test_reordering_rows_invalidates_the_previous_version(): void
    {
        $rows = self::rows();
        $marker = CsvReviewSnapshotPreview::fingerprint($rows, self::mapping());
        [$rows[1], $rows[3]] = [$rows[3], $rows[1]];
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile($rows, self::mapping(), $marker, []);
    }

    public function test_editing_a_header_invalidates_previous_review(): void
    {
        $rows = self::rows();
        $marker = CsvReviewSnapshotPreview::fingerprint($rows, self::mapping());
        $rows[0][0] = 'other_header';
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile($rows, self::mapping(), $marker, []);
    }

    public function test_swapping_mapping_columns_invalidates_the_previous_version(): void
    {
        $mapping = self::mapping();
        $marker = CsvReviewSnapshotPreview::fingerprint(self::rows(), $mapping);
        $mapping[0]['column'] = 2;
        $mapping[1]['column'] = 1;
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), $mapping, $marker, []);
    }

    public function test_reordering_mapping_assignments_invalidates_the_previous_version(): void
    {
        $mapping = self::mapping();
        $marker = CsvReviewSnapshotPreview::fingerprint(self::rows(), $mapping);
        $mapping = array_reverse($mapping);
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), $mapping, $marker, []);
    }

    public function test_changing_only_mapping_target_invalidates_the_previous_version(): void
    {
        $mapping = self::mapping();
        $marker = CsvReviewSnapshotPreview::fingerprint(self::rows(), $mapping);
        $mapping[1]['target'] = 'fixture_other';
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), $mapping, $marker, []);
    }

    public function test_length_prefixes_prevent_ambiguous_cell_concatenation(): void
    {
        $one = [['h1', 'h2'], ['ab', 'c']];
        $two = [['h1', 'h2'], ['a', 'bc']];
        self::assertNotSame(
            CsvReviewSnapshotPreview::fingerprint($one),
            CsvReviewSnapshotPreview::fingerprint($two),
        );
    }

    public function test_binary_bytes_are_hashed_without_assuming_utf8_decode_or_trimming(): void
    {
        $base = [['id'], ["x\x00y"]];
        $changed = [['id'], ["x\x00z"]];
        self::assertNotSame(
            CsvReviewSnapshotPreview::fingerprint($base),
            CsvReviewSnapshotPreview::fingerprint($changed),
        );

        $differentSpacing = [['id'], ['x y']];
        self::assertNotSame(
            CsvReviewSnapshotPreview::fingerprint($differentSpacing),
            CsvReviewSnapshotPreview::fingerprint([['id'], ['x  y']]),
        );
    }

    public function test_hash_only_output_does_not_echo_fixture_cell_or_destination(): void
    {
        $rows = [
            ['confidential_fake_header'],
            ['fictionally-sensitive-marker-text'],
        ];
        $fingerprint = CsvReviewSnapshotPreview::fingerprint($rows, [
            ['column' => 1, 'target' => 'fixture_secret'],
        ]);
        foreach (['confidential', 'fictionally', 'fixture_secret'] as $raw) {
            self::assertStringNotContainsString($raw, $fingerprint);
        }
        $result = CsvReviewSnapshotPreview::reconcile($rows, [
            ['column' => 1, 'target' => 'fixture_secret'],
        ], $fingerprint);
        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('fictionally', $serialized);
        self::assertStringNotContainsString('fixture_secret', $serialized);
        self::assertFalse($result['import_permitted']);
    }

    public function test_rejects_a_forged_marker_with_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), self::mapping(), 'approved');
    }

    public function test_rejects_uppercase_hash_to_preserve_one_canonical_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), self::mapping(),
            strtoupper(CsvReviewSnapshotPreview::fingerprint(self::rows(), self::mapping())));
    }

    public function test_rejects_one_changed_digest_character(): void
    {
        $marker = CsvReviewSnapshotPreview::fingerprint(self::rows(), self::mapping());
        $last = substr($marker, -1);
        $forged = substr($marker, 0, -1).($last === 'a' ? 'b' : 'a');
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), self::mapping(), $forged);
    }

    public function test_a_marker_for_other_fixture_even_with_same_counts_is_rejected(): void
    {
        $rows = self::rows();
        $other = self::rows();
        $other[3][0] = 'fiction-X';
        $marker = CsvReviewSnapshotPreview::fingerprint($other, self::mapping());

        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile($rows, self::mapping(), $marker);
    }

    public function test_reconcile_preserves_the_underlying_negative_decision_rules(): void
    {
        $marker = CsvReviewSnapshotPreview::fingerprint(self::rows(), self::mapping());
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::reconcile(self::rows(), self::mapping(), $marker, [
            ['row' => 3, 'status' => 'fixture_reviewed_no_findings'],
        ]);
    }

    public function test_source_validation_rejects_operational_targets_before_hashing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvReviewSnapshotPreview::fingerprint(self::rows(), [['column' => 1, 'target' => 'leads.email']]);
    }

    public function test_empty_fixture_digest_is_still_not_an_import_authorization(): void
    {
        $marker = CsvReviewSnapshotPreview::fingerprint([]);
        $result = CsvReviewSnapshotPreview::reconcile([], [], $marker);
        self::assertSame(['missing_header'], $result['global_issue_codes']);
        self::assertSame(0, $result['data_rows']);
        self::assertFalse($result['human_verified']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_exact_maximum_fixture_size_still_requires_review(): void
    {
        $rows = [['h']];
        for ($i = 1; $i < 100; ++$i) {
            $rows[] = ['fiction-'.$i];
        }
        $fingerprint = CsvReviewSnapshotPreview::fingerprint($rows);
        $result = CsvReviewSnapshotPreview::reconcile($rows, [], $fingerprint);
        self::assertSame(99, $result['pending_count']);
        self::assertFalse($result['import_permitted']);
    }
}
