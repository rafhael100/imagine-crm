<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvRowDispositionPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvRowDispositionPreviewTest extends TestCase
{
    private static function rows(): array
    {
        return [
            ['header_a', 'header_b'],
            ['fixture-one', 'safe'],
            ['fixture-two', '=SUM(A1:A2)'],
            ['fixture-three'],
            ['fixture-four', 'safe'],
        ];
    }

    public function test_default_is_pending_even_for_unflagged_rows(): void
    {
        $r = CsvRowDispositionPreview::summarize(self::rows());
        self::assertSame(4, $r['data_rows']);
        self::assertSame(0, $r['submitted_decision_count']);
        self::assertSame(4, $r['pending_count']);
        self::assertSame(2, $r['rows_with_detected_findings']);
        self::assertSame(0, $r['reviewed_no_findings_count']);
        self::assertSame(0, $r['needs_correction_count']);
        self::assertSame(['ragged_data_rows', 'risky_cells'], $r['global_issue_codes']);
        self::assertSame([2, 3, 4, 5], array_column($r['row_dispositions'], 'row'));
        foreach ($r['row_dispositions'] as $row) {
            self::assertSame('fixture_pending', $row['status']);
        }
        self::assertFalse($r['human_verified']);
        self::assertTrue($r['review_required']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_simulated_review_and_correction_keep_import_prohibited(): void
    {
        $r = CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 3, 'status' => 'fixture_needs_correction'],
            ['row' => 4, 'status' => 'fixture_needs_correction'],
        ]);
        self::assertSame(3, $r['submitted_decision_count']);
        self::assertSame(1, $r['pending_count']);
        self::assertSame(1, $r['reviewed_no_findings_count']);
        self::assertSame(2, $r['needs_correction_count']);
        self::assertSame([
            ['row' => 2, 'has_detected_findings' => false, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 3, 'has_detected_findings' => true, 'status' => 'fixture_needs_correction'],
            ['row' => 4, 'has_detected_findings' => true, 'status' => 'fixture_needs_correction'],
            ['row' => 5, 'has_detected_findings' => false, 'status' => 'fixture_pending'],
        ], $r['row_dispositions']);
        self::assertTrue($r['review_required']);
        self::assertFalse($r['import_permitted']);
        self::assertFalse($r['human_verified']);
    }

    public function test_permuting_decision_input_does_not_change_row_order(): void
    {
        $choices = [
            ['row' => 5, 'status' => 'fixture_needs_correction'],
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 4, 'status' => 'fixture_needs_correction'],
        ];
        self::assertSame(
            CsvRowDispositionPreview::summarize(self::rows(), [], $choices),
            CsvRowDispositionPreview::summarize(self::rows(), [], array_reverse($choices)),
        );
    }

    public function test_even_all_clean_rows_mock_reviewed_does_not_grant_any_authority(): void
    {
        $r = CsvRowDispositionPreview::summarize([
            ['id'],
            ['fiction-a'],
            ['fiction-b'],
        ], [], [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 3, 'status' => 'fixture_reviewed_no_findings'],
        ]);
        self::assertSame(0, $r['pending_count']);
        self::assertSame(2, $r['reviewed_no_findings_count']);
        self::assertSame([], $r['global_issue_codes']);
        self::assertFalse($r['human_verified']);
        self::assertTrue($r['review_required']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_global_header_problems_cannot_be_cleared_by_mock_row_review(): void
    {
        $r = CsvRowDispositionPreview::summarize([
            ['same', 'same'],
            ['fiction', 'safe'],
        ], [], [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ]);
        self::assertSame(['duplicate_header'], $r['global_issue_codes']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_unmapped_columns_stay_underlying_review_and_are_not_automatically_removed(): void
    {
        $r = CsvRowDispositionPreview::summarize([
            ['id', 'note'],
            ['fiction', 'safe'],
        ], [
            ['column' => 1, 'target' => 'fixture_identifier'],
        ], [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ]);
        self::assertSame(1, $r['reviewed_no_findings_count']);
        self::assertFalse($r['import_permitted']);
        self::assertFalse($r['human_verified']);
    }

    public function test_flagged_row_cannot_be_described_as_reviewed_with_no_findings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 3, 'status' => 'fixture_reviewed_no_findings'],
        ]);
    }

    public function test_ragged_row_cannot_be_described_as_reviewed_with_no_findings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 4, 'status' => 'fixture_reviewed_no_findings'],
        ]);
    }

    public function test_can_mark_clean_row_as_needing_correction(): void
    {
        $r = CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 5, 'status' => 'fixture_needs_correction'],
        ]);
        self::assertSame(1, $r['needs_correction_count']);
        self::assertFalse($r['row_dispositions'][3]['has_detected_findings']);
    }

    public function test_rejects_duplicate_row_decisions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 2, 'status' => 'fixture_needs_correction'],
        ]);
    }

    public function test_rejects_header_as_a_data_row(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 1, 'status' => 'fixture_needs_correction'],
        ]);
    }

    public function test_rejects_unknown_row_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 6, 'status' => 'fixture_needs_correction'],
        ]);
    }

    public function test_rejects_non_integer_row_index(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => '2', 'status' => 'fixture_needs_correction'],
        ]);
    }

    public function test_rejects_unapproved_or_authorizing_disposition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            ['row' => 2, 'status' => 'import_approved'],
        ]);
    }

    public function test_rejects_extra_actor_tenant_or_attestation_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [[
            'row' => 2, 'status' => 'fixture_reviewed_no_findings',
            'approved_by' => 'forged-human',
        ]]);
    }

    public function test_rejects_non_list_decision_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize(self::rows(), [], [
            'external' => ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ]);
    }

    public function test_rejects_decisions_when_there_is_no_data_row(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize([['id']], [], [
            ['row' => 2, 'status' => 'fixture_needs_correction'],
        ]);
    }

    public function test_boundary_allows_ninety_nine_synthetic_rows(): void
    {
        $rows = [['id']];
        $choices = [];
        for ($i = 2; $i <= 100; ++$i) {
            $rows[] = ['fiction-'.$i];
            $choices[] = ['row' => $i, 'status' => 'fixture_reviewed_no_findings'];
        }
        $r = CsvRowDispositionPreview::summarize($rows, [], $choices);
        self::assertSame(99, $r['reviewed_no_findings_count']);
        self::assertSame(0, $r['pending_count']);
        self::assertSame(99, $r['submitted_decision_count']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_rejects_oversized_decision_list_before_reporting_any_raw_content(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize([['id'], ['fiction']], [],
            array_fill(0, 100, ['row' => 2, 'status' => 'fixture_needs_correction']));
    }

    public function test_rejects_malformed_underlying_matrix_or_mapping(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CsvRowDispositionPreview::summarize([
            ['id', 'test'],
            ['fiction', 123],
        ]);
    }

    public function test_report_never_exposes_header_cell_or_target_string(): void
    {
        $r = CsvRowDispositionPreview::summarize([
            ['secret_header', 'other_secret_header'],
            ['invented-person-name', '=HYPERLINK("https://invalid.example")'],
        ], [
            ['column' => 1, 'target' => 'fixture_hidden_label'],
        ], [
            ['row' => 2, 'status' => 'fixture_needs_correction'],
        ]);

        $json = json_encode($r, JSON_THROW_ON_ERROR);
        foreach (['secret_header', 'invented-person-name', 'HYPERLINK',
            'invalid.example', 'fixture_hidden_label'] as $raw) {
            self::assertStringNotContainsString($raw, $json);
        }
        self::assertSame([
            'data_rows', 'submitted_decision_count', 'pending_count',
            'reviewed_no_findings_count', 'needs_correction_count',
            'rows_with_detected_findings', 'global_issue_codes',
            'row_dispositions', 'human_verified', 'review_required', 'import_permitted',
        ], array_keys($r));
    }
}
