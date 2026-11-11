<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvReviewRaceFixture;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Synthetic, single-object fault windows. Not transaction, audit or RLS tests.
 */
final class CsvReviewRaceFaultWindowsTest extends TestCase
{
    private static function fixture(): CsvReviewRaceFixture
    {
        return new CsvReviewRaceFixture([
            ['fake_id', 'fake_note'],
            ['invented-one', 'safe'],
            ['invented-two', '=SUM(A1:A2)'],
        ], [
            ['column' => 1, 'target' => 'fixture_identifier'],
            ['column' => 2, 'target' => 'fixture_note'],
        ]);
    }

    private static function clean(): array
    {
        return [['row' => 2, 'status' => 'fixture_reviewed_no_findings']];
    }

    private static function correction(): array
    {
        return [['row' => 3, 'status' => 'fixture_needs_correction']];
    }

    private static function assertDenyOnly(array $state): void
    {
        self::assertFalse($state['human_verified']);
        self::assertTrue($state['review_required']);
        self::assertFalse($state['import_permitted']);
    }

    public function test_pre_record_abort_has_no_event_version_or_consumed_key(): void
    {
        $fixture = self::fixture();
        $marker = $fixture->marker();

        $aborted = $fixture->submit(
            1, $marker, 'fixture_key_retry', self::clean(),
            'fixture_abort_before_record',
        );
        self::assertSame('fixture_abort_no_op', $aborted['status']);
        self::assertSame(1, $aborted['fixture_version']);
        self::assertSame(0, $aborted['recorded_review_events']);
        self::assertSame(0, $aborted['last_decision_count']);
        self::assertSame($marker, $fixture->marker());
        self::assertDenyOnly($aborted);

        $retried = $fixture->submit(1, $marker, 'fixture_key_retry', self::clean());
        self::assertSame('fixture_recorded_only', $retried['status']);
        self::assertSame(2, $retried['fixture_version']);
        self::assertSame(1, $retried['recorded_review_events']);
        self::assertDenyOnly($retried);
    }

    public function test_repeating_pre_record_abort_does_not_accumulate_events(): void
    {
        $fixture = self::fixture();
        for ($n = 0; $n < 5; ++$n) {
            $aborted = $fixture->submit(
                1, $fixture->marker(), 'fixture_key_repeat', self::clean(),
                'fixture_abort_before_record',
            );
            self::assertSame('fixture_abort_no_op', $aborted['status']);
            self::assertSame(1, $fixture->version());
            self::assertSame(0, $aborted['recorded_review_events']);
            self::assertDenyOnly($aborted);
        }
    }

    public function test_fault_mode_is_validated_before_any_state_changes(): void
    {
        $fixture = self::fixture();
        try {
            $fixture->submit(1, $fixture->marker(), 'fixture_key_a', self::clean(), 'release_all');
            self::fail('Forged failure mode must be rejected.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Invalid synthetic failure window.', $e->getMessage());
        }

        self::assertSame(1, $fixture->version());
        self::assertSame(0, $fixture->summary()['recorded_review_events']);
        self::assertDenyOnly($fixture->summary());
    }

    public function test_pre_record_abort_still_rejects_flagged_row_as_clean(): void
    {
        $fixture = self::fixture();
        try {
            $fixture->submit(
                1, $fixture->marker(), 'fixture_key_bad',
                [['row' => 3, 'status' => 'fixture_reviewed_no_findings']],
                'fixture_abort_before_record',
            );
            self::fail('Pre-record fault must not bypass validation.');
        } catch (InvalidArgumentException $e) {
            self::assertStringNotContainsString('invented', $e->getMessage());
        }

        self::assertSame(0, $fixture->summary()['recorded_review_events']);
        self::assertSame(1, $fixture->version());
        self::assertSame(
            'fixture_recorded_only',
            $fixture->submit(1, $fixture->marker(), 'fixture_key_bad', self::correction())['status'],
        );
    }

    public function test_stale_version_does_not_become_a_successful_abort(): void
    {
        $fixture = self::fixture();
        $marker = $fixture->marker();
        $fixture->submit(1, $marker, 'fixture_key_a', self::clean());

        $stale = $fixture->submit(
            1, $marker, 'fixture_key_other', self::correction(), 'fixture_abort_before_record',
        );
        self::assertSame('fixture_stale_no_op', $stale['status']);
        self::assertSame(1, $stale['recorded_review_events']);
        self::assertSame(2, $fixture->version());
        self::assertDenyOnly($stale);
    }

    public function test_lost_ack_is_not_rollback_and_exact_retry_is_deduplicated(): void
    {
        $fixture = self::fixture();
        $marker = $fixture->marker();
        try {
            $fixture->submit(
                1, $marker, 'fixture_key_ack', self::clean(),
                'fixture_lost_ack_after_record',
            );
            self::fail('The fake acknowledgement must be missing.');
        } catch (RuntimeException $e) {
            self::assertSame(
                'Synthetic acknowledgement unavailable; outcome unknown.',
                $e->getMessage(),
            );
        }

        self::assertSame(2, $fixture->version());
        self::assertSame(1, $fixture->summary()['recorded_review_events']);
        self::assertSame(1, $fixture->summary()['last_decision_count']);
        self::assertDenyOnly($fixture->summary());

        $repeat = $fixture->submit(1, $marker, 'fixture_key_ack', self::clean());
        self::assertSame('fixture_idempotent_replay_no_op', $repeat['status']);
        self::assertSame(1, $repeat['recorded_review_events']);
        self::assertSame(2, $repeat['fixture_version']);
        self::assertDenyOnly($repeat);
    }

    public function test_lost_ack_with_different_payload_on_same_key_conflicts(): void
    {
        $fixture = self::fixture();
        try {
            $fixture->submit(
                1, $fixture->marker(), 'fixture_key_collision', self::clean(),
                'fixture_lost_ack_after_record',
            );
            self::fail('Expected simulated lost acknowledgement.');
        } catch (RuntimeException) {
            // Deliberate unknown response, but one local event occurred.
        }

        $conflict = $fixture->submit(
            1, $fixture->marker(), 'fixture_key_collision',
            [['row' => 2, 'status' => 'fixture_needs_correction']],
        );
        self::assertSame('fixture_idempotency_conflict_no_op', $conflict['status']);
        self::assertSame(1, $conflict['recorded_review_events']);
        self::assertDenyOnly($conflict);
    }

    public function test_lost_ack_does_not_allow_a_different_request_with_stale_version(): void
    {
        $fixture = self::fixture();
        try {
            $fixture->submit(
                1, $fixture->marker(), 'fixture_key_first', self::clean(),
                'fixture_lost_ack_after_record',
            );
            self::fail('Expected simulated lost acknowledgement.');
        } catch (RuntimeException) {
            // No outward acknowledgement of accepted fake event.
        }

        $stale = $fixture->submit(1, $fixture->marker(), 'fixture_key_second', self::correction());
        self::assertSame('fixture_stale_no_op', $stale['status']);
        self::assertSame(1, $stale['recorded_review_events']);
        self::assertDenyOnly($stale);

        $newVersion = $fixture->submit(2, $fixture->marker(), 'fixture_key_second', self::correction());
        self::assertSame('fixture_recorded_only', $newVersion['status']);
        self::assertSame(2, $newVersion['recorded_review_events']);
        self::assertSame(3, $newVersion['fixture_version']);
        self::assertDenyOnly($newVersion);
    }

    public function test_duplicate_with_lost_ack_mode_does_not_create_another_event(): void
    {
        $fixture = self::fixture();
        $marker = $fixture->marker();
        $fixture->submit(1, $marker, 'fixture_key_once', self::clean());
        $duplicate = $fixture->submit(
            1, $marker, 'fixture_key_once', self::clean(), 'fixture_lost_ack_after_record',
        );
        self::assertSame('fixture_idempotent_replay_no_op', $duplicate['status']);
        self::assertSame(1, $duplicate['recorded_review_events']);
        self::assertDenyOnly($duplicate);
    }

    public function test_snapshot_change_after_lost_ack_resets_fake_history_and_rejects_old_marker(): void
    {
        $fixture = self::fixture();
        $old = $fixture->marker();
        try {
            $fixture->submit(
                1, $old, 'fixture_key_earlier', self::clean(),
                'fixture_lost_ack_after_record',
            );
            self::fail('Expected simulated lost acknowledgement.');
        } catch (RuntimeException) {
            // The local version has advanced.
        }

        $changed = [
            ['fake_id', 'fake_note'],
            ['invented-one', 'modified'],
            ['invented-two', '=SUM(A1:A2)'],
        ];
        $replace = $fixture->replaceFixture(2, $changed, [
            ['column' => 1, 'target' => 'fixture_identifier'],
            ['column' => 2, 'target' => 'fixture_note'],
        ]);
        self::assertSame('fixture_replaced_review_reset', $replace['status']);
        self::assertSame(3, $replace['fixture_version']);
        self::assertSame(0, $replace['recorded_review_events']);
        self::assertDenyOnly($replace);

        $oldReplay = $fixture->submit(1, $old, 'fixture_key_earlier', self::clean());
        self::assertSame('fixture_stale_no_op', $oldReplay['status']);
        self::assertSame(0, $oldReplay['recorded_review_events']);
        self::assertDenyOnly($oldReplay);
    }

    public function test_bad_fault_enum_does_not_expose_any_fake_data_in_error(): void
    {
        $fixture = self::fixture();
        try {
            $fixture->submit(1, $fixture->marker(), 'fixture_key_any', self::clean(), 'bad');
            self::fail('Unrecognized mode must throw.');
        } catch (InvalidArgumentException $e) {
            foreach (['invented', '=SUM', 'fixture_identifier', 'fixture_key_any'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $e->getMessage());
            }
        }
        self::assertSame(0, $fixture->summary()['recorded_review_events']);
        self::assertDenyOnly($fixture->summary());
    }

    public function test_all_simulated_faults_leave_business_permissions_denied(): void
    {
        $fixture = self::fixture();
        $marker = $fixture->marker();

        $abort = $fixture->submit(
            1, $marker, 'fixture_key_x', self::clean(), 'fixture_abort_before_record',
        );
        self::assertDenyOnly($abort);
        try {
            $fixture->submit(
                1, $marker, 'fixture_key_x', self::clean(), 'fixture_lost_ack_after_record',
            );
            self::fail('Expected simulated lost acknowledgement.');
        } catch (RuntimeException) {
            // Not a success response, and definitely not permission.
        }

        $retry = $fixture->submit(1, $marker, 'fixture_key_x', self::clean());
        self::assertDenyOnly($retry);
        self::assertSame('fixture_idempotent_replay_no_op', $retry['status']);
    }
}
