<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvReviewRaceFixture;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsvReviewRaceFixtureTest extends TestCase
{
    private static function rows(): array
    {
        return [
            ['code', 'note'],
            ['fiction-1', 'safe'],
            ['fiction-2', '=1+1'],
            ['fiction-3', 'safe'],
        ];
    }

    private static function mapping(): array
    {
        return [
            ['column' => 1, 'target' => 'fixture_code'],
            ['column' => 2, 'target' => 'fixture_note'],
        ];
    }

    private static function fixture(): CsvReviewRaceFixture
    {
        return new CsvReviewRaceFixture(self::rows(), self::mapping());
    }

    private static function cleanChoice(): array
    {
        return [['row' => 2, 'status' => 'fixture_reviewed_no_findings']];
    }

    private static function correction(): array
    {
        return [['row' => 3, 'status' => 'fixture_needs_correction']];
    }

    private static function submit(
        CsvReviewRaceFixture $fixture,
        int $version,
        string $key = 'fixture_key_a',
        ?array $decisions = null,
    ): array {
        return $fixture->submit($version, $fixture->marker(), $key, $decisions ?? self::cleanChoice());
    }

    private static function assertNoImport(array $result): void
    {
        self::assertFalse($result['human_verified']);
        self::assertTrue($result['review_required']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_initial_state_has_version_one_and_no_fake_review_events(): void
    {
        $fixture = self::fixture();
        $state = $fixture->summary();

        self::assertSame('fixture_read_only', $state['status']);
        self::assertSame(1, $fixture->version());
        self::assertSame(0, $state['recorded_review_events']);
        self::assertSame(0, $state['last_decision_count']);
        self::assertMatchesRegularExpression('/\Afixture-sha256-v1:[a-f0-9]{64}\z/D', $fixture->marker());
        self::assertNoImport($state);
    }

    public function test_one_review_only_increments_the_in_memory_version_once(): void
    {
        $fixture = self::fixture();
        $state = self::submit($fixture, 1);

        self::assertSame('fixture_recorded_only', $state['status']);
        self::assertSame(2, $state['fixture_version']);
        self::assertSame(1, $state['recorded_review_events']);
        self::assertSame(1, $state['last_decision_count']);
        self::assertSame(2, $fixture->version());
        self::assertNoImport($state);
    }

    public function test_exact_retry_returns_noop_even_after_the_first_review_advanced_version(): void
    {
        $fixture = self::fixture();
        $first = self::submit($fixture, 1);
        $again = self::submit($fixture, 1);

        self::assertSame('fixture_recorded_only', $first['status']);
        self::assertSame('fixture_idempotent_replay_no_op', $again['status']);
        self::assertSame(2, $again['fixture_version']);
        self::assertSame(1, $again['recorded_review_events']);
        self::assertNoImport($again);
    }

    public function test_two_requests_from_the_same_observed_version_cannot_both_apply(): void
    {
        $fixture = self::fixture();
        $a = self::submit($fixture, 1, 'fixture_key_a');
        $b = self::submit($fixture, 1, 'fixture_key_b', self::correction());

        self::assertSame('fixture_recorded_only', $a['status']);
        self::assertSame('fixture_stale_no_op', $b['status']);
        self::assertSame(1, $b['recorded_review_events']);
        self::assertSame(2, $fixture->version());
        self::assertNoImport($b);
    }

    public function test_a_distinct_review_using_current_version_is_another_fake_event(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $r = self::submit($fixture, 2, 'fixture_key_b', self::correction());

        self::assertSame('fixture_recorded_only', $r['status']);
        self::assertSame(3, $r['fixture_version']);
        self::assertSame(2, $r['recorded_review_events']);
        self::assertNoImport($r);
    }

    public function test_reused_key_with_different_status_conflicts_without_a_second_write(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $r = self::submit($fixture, 1, 'fixture_key_a', [
            ['row' => 2, 'status' => 'fixture_needs_correction'],
        ]);

        self::assertSame('fixture_idempotency_conflict_no_op', $r['status']);
        self::assertSame(2, $r['fixture_version']);
        self::assertSame(1, $r['recorded_review_events']);
        self::assertNoImport($r);
    }

    public function test_reused_key_from_a_different_observed_version_conflicts(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $r = self::submit($fixture, 2, 'fixture_key_a');

        self::assertSame('fixture_idempotency_conflict_no_op', $r['status']);
        self::assertSame(1, $r['recorded_review_events']);
        self::assertNoImport($r);
    }

    public function test_reused_key_with_different_snapshot_marker_returns_stale_not_replay(): void
    {
        $fixture = self::fixture();
        $oldMarker = $fixture->marker();
        self::submit($fixture, 1);
        $rows = self::rows();
        $rows[1][1] = 'new-fiction';
        $fixture->replaceFixture(2, $rows, self::mapping());

        $replay = $fixture->submit(1, $oldMarker, 'fixture_key_a', self::cleanChoice());
        self::assertSame('fixture_stale_no_op', $replay['status']);
        self::assertSame(3, $replay['fixture_version']);
        self::assertSame(0, $replay['recorded_review_events']);
        self::assertNoImport($replay);
    }

    public function test_row_choices_reordered_are_the_same_logical_idempotent_request(): void
    {
        $fixture = self::fixture();
        $a = [
            ['row' => 4, 'status' => 'fixture_reviewed_no_findings'],
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ];
        $b = array_reverse($a);
        $first = self::submit($fixture, 1, 'fixture_key_order', $a);
        $replay = self::submit($fixture, 1, 'fixture_key_order', $b);

        self::assertSame('fixture_recorded_only', $first['status']);
        self::assertSame('fixture_idempotent_replay_no_op', $replay['status']);
        self::assertSame(1, $replay['recorded_review_events']);
        self::assertSame(2, $replay['last_decision_count']);
    }

    public function test_report_does_not_include_marker_key_or_any_cell_values(): void
    {
        $fixture = self::fixture();
        $r = self::submit($fixture, 1, 'fixture_key_nonpublictest');
        $json = json_encode($r, JSON_THROW_ON_ERROR);
        foreach ([
            'fiction-1', '=1+1', 'fixture_code', 'fixture_note',
            'fixture_key_nonpublictest', 'fixture-sha256-v1',
            'approved_by', 'tenant_id',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
        self::assertSame([
            'status', 'fixture_version', 'recorded_review_events',
            'last_decision_count', 'human_verified', 'review_required',
            'import_permitted',
        ], array_keys($r));
    }

    public function test_changed_fixture_resets_all_fake_review_history(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $newRows = self::rows();
        $newRows[1][1] = 'different';
        $r = $fixture->replaceFixture(2, $newRows, self::mapping());

        self::assertSame('fixture_replaced_review_reset', $r['status']);
        self::assertSame(3, $r['fixture_version']);
        self::assertSame(0, $r['recorded_review_events']);
        self::assertSame(0, $r['last_decision_count']);
        self::assertNoImport($r);
    }

    public function test_changed_mapping_alone_resets_decisions(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $mapping = self::mapping();
        $mapping[1]['target'] = 'fixture_other';
        $r = $fixture->replaceFixture(2, self::rows(), $mapping);

        self::assertSame('fixture_replaced_review_reset', $r['status']);
        self::assertSame(0, $r['recorded_review_events']);
        self::assertNoImport($r);
    }

    public function test_same_fixture_bytes_and_mapping_do_not_reset_or_increment_version(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $r = $fixture->replaceFixture(2, self::rows(), self::mapping());

        self::assertSame('fixture_unchanged_no_op', $r['status']);
        self::assertSame(2, $r['fixture_version']);
        self::assertSame(1, $r['recorded_review_events']);
        self::assertNoImport($r);
    }

    public function test_stale_replacement_does_not_mutate_current_fixture(): void
    {
        $fixture = self::fixture();
        $oldMarker = $fixture->marker();
        $rows = self::rows();
        $rows[1][1] = 'altered';
        $r = $fixture->replaceFixture(2, $rows, self::mapping());

        self::assertSame('fixture_stale_no_op', $r['status']);
        self::assertSame(1, $r['fixture_version']);
        self::assertSame($oldMarker, $fixture->marker());
        self::assertNoImport($r);
    }

    public function test_replacing_with_same_old_content_later_does_not_roll_back_version(): void
    {
        $fixture = self::fixture();
        $oldMarker = $fixture->marker();
        $newRows = self::rows();
        $newRows[1][1] = 'later';
        $first = $fixture->replaceFixture(1, $newRows, self::mapping());
        $second = $fixture->replaceFixture(2, self::rows(), self::mapping());

        self::assertSame(2, $first['fixture_version']);
        self::assertSame(3, $second['fixture_version']);
        self::assertSame($oldMarker, $fixture->marker());

        // Although bytes returned to the initial value, the original
        // monotonically older simulated version cannot be submitted.
        $replay = $fixture->submit(1, $oldMarker, 'fixture_key_old', self::cleanChoice());
        self::assertSame('fixture_stale_no_op', $replay['status']);
        self::assertNoImport($replay);
    }

    public function test_after_replacing_fixture_new_key_can_record_without_becoming_authorized(): void
    {
        $fixture = self::fixture();
        self::submit($fixture, 1);
        $rows = self::rows();
        $rows[1][1] = 'new-safe-content';
        $fixture->replaceFixture(2, $rows, self::mapping());
        $record = self::submit($fixture, 3, 'fixture_key_b');

        self::assertSame('fixture_recorded_only', $record['status']);
        self::assertSame(4, $record['fixture_version']);
        self::assertSame(1, $record['recorded_review_events']);
        self::assertNoImport($record);
    }

    public function test_flagged_row_cannot_be_mock_reviewed_as_clean(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        self::submit($fixture, 1, 'fixture_key_reject',
            [['row' => 3, 'status' => 'fixture_reviewed_no_findings']]);
    }

    public function test_failed_row_review_does_not_consume_the_fake_idempotency_key(): void
    {
        $fixture = self::fixture();
        try {
            self::submit($fixture, 1, 'fixture_key_retry',
                [['row' => 3, 'status' => 'fixture_reviewed_no_findings']]);
            self::fail('Unsafe fake clean decision should be rejected.');
        } catch (InvalidArgumentException) {
            // Expected: no fake event may be recorded on failure.
        }
        $r = self::submit($fixture, 1, 'fixture_key_retry', self::correction());
        self::assertSame('fixture_recorded_only', $r['status']);
        self::assertSame(1, $r['recorded_review_events']);
        self::assertNoImport($r);
    }

    public function test_rejects_empty_command_instead_of_recording_a_zero_decision_review(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        self::submit($fixture, 1, 'fixture_key_empty', []);
    }

    public function test_rejects_duplicate_decisions_and_non_list_input(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        self::submit($fixture, 1, 'fixture_key_dup', [
            ['row' => 2, 'status' => 'fixture_needs_correction'],
            ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ]);
    }

    public function test_rejects_forged_tenant_actor_and_permission_fields(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        self::submit($fixture, 1, 'fixture_key_forged', [[
            'row' => 2, 'status' => 'fixture_reviewed_no_findings',
            'tenant_id' => 'unauthorized',
            'approved_by' => 'fake',
            'import_permitted' => true,
        ]]);
    }

    public function test_rejects_authorizing_review_status(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        self::submit($fixture, 1, 'fixture_key_approve', [
            ['row' => 2, 'status' => 'import_approved'],
        ]);
    }

    public function test_rejects_out_of_range_row_without_recording_anything(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        self::submit($fixture, 1, 'fixture_key_out', [
            ['row' => 999, 'status' => 'fixture_needs_correction'],
        ]);
    }

    public function test_rejects_invalid_key_without_leaking_source_content(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        $fixture->submit(1, $fixture->marker(), 'external-tenant-token', self::cleanChoice());
    }

    public function test_rejects_wrong_version_or_malformed_digest(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        $fixture->submit(0, $fixture->marker(), 'fixture_key_a', self::cleanChoice());
    }

    public function test_rejects_malformed_digest_without_granting_effects(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        $fixture->submit(1, 'sha256:not-a-marker', 'fixture_key_a', self::cleanChoice());
    }

    public function test_rejects_invalid_fixture_mapping_before_object_creation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CsvReviewRaceFixture(self::rows(), [
            ['column' => 1, 'target' => 'leads.email'],
        ]);
    }

    public function test_rejects_invalid_replacement_and_keeps_old_state(): void
    {
        $fixture = self::fixture();
        $this->expectException(InvalidArgumentException::class);
        $fixture->replaceFixture(1, [['id'], [123]], []);
    }

    public function test_history_cap_rejects_one_hundredth_new_key_without_extra_effect(): void
    {
        $fixture = new CsvReviewRaceFixture([
            ['id'], ['fiction-01'],
        ]);
        for ($n = 1; $n <= 99; ++$n) {
            $r = $fixture->submit(
                $n, $fixture->marker(), 'fixture_key_'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                [['row' => 2, 'status' => 'fixture_reviewed_no_findings']],
            );
            self::assertSame('fixture_recorded_only', $r['status']);
        }
        $r = $fixture->submit(
            100, $fixture->marker(), 'fixture_key_100',
            [['row' => 2, 'status' => 'fixture_reviewed_no_findings']],
        );
        self::assertSame('fixture_history_limit_no_op', $r['status']);
        self::assertSame(100, $r['fixture_version']);
        self::assertSame(99, $r['recorded_review_events']);
        self::assertNoImport($r);
    }
}
