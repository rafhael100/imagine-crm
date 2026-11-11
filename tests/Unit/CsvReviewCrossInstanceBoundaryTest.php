<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvReviewRaceFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Red-flag counterexamples ONLY: two independent PHP objects are not a
 * multi-process transaction or durable idempotency proof. All data invented.
 */
final class CsvReviewCrossInstanceBoundaryTest extends TestCase
{
    private static function rows(): array
    {
        return [
            ['id', 'note'],
            ['fiction-1', 'safe'],
            ['fiction-2', '=1+1'],
        ];
    }

    private static function mapping(): array
    {
        return [
            ['column' => 1, 'target' => 'fixture_id'],
            ['column' => 2, 'target' => 'fixture_note'],
        ];
    }

    private static function fixture(): CsvReviewRaceFixture
    {
        return new CsvReviewRaceFixture(self::rows(), self::mapping());
    }

    private static function clean(): array
    {
        return [['row' => 2, 'status' => 'fixture_reviewed_no_findings']];
    }

    private static function needsCorrection(): array
    {
        return [['row' => 3, 'status' => 'fixture_needs_correction']];
    }

    private static function assertNoBusinessAuthority(array $result): void
    {
        self::assertFalse($result['human_verified']);
        self::assertTrue($result['review_required']);
        self::assertFalse($result['import_permitted']);
    }

    public function test_identical_independent_objects_start_from_the_same_forgeable_version(): void
    {
        $first = self::fixture();
        $second = self::fixture();

        self::assertSame($first->marker(), $second->marker());
        self::assertSame(1, $first->version());
        self::assertSame(1, $second->version());
        self::assertNoBusinessAuthority($first->summary());
        self::assertNoBusinessAuthority($second->summary());
    }

    public function test_same_key_and_payload_can_be_recorded_twice_across_two_objects(): void
    {
        $first = self::fixture();
        $second = self::fixture();
        $key = 'fixture_key_same';
        $a = $first->submit(1, $first->marker(), $key, self::clean());
        $b = $second->submit(1, $second->marker(), $key, self::clean());

        // This is an expected UNSAFE LIMITATION of separate toy objects,
        // not a desired operational behavior or a green real-world concurrency test.
        self::assertSame('fixture_recorded_only', $a['status']);
        self::assertSame('fixture_recorded_only', $b['status']);
        self::assertSame(1, $a['recorded_review_events']);
        self::assertSame(1, $b['recorded_review_events']);
        self::assertSame(2, $first->version());
        self::assertSame(2, $second->version());
        self::assertNoBusinessAuthority($a);
        self::assertNoBusinessAuthority($b);
    }

    public function test_same_key_different_payload_can_be_accepted_by_independent_objects(): void
    {
        $first = self::fixture();
        $second = self::fixture();
        $a = $first->submit(1, $first->marker(), 'fixture_key_collision', self::clean());
        $b = $second->submit(1, $second->marker(), 'fixture_key_collision', self::needsCorrection());

        self::assertSame('fixture_recorded_only', $a['status']);
        self::assertSame('fixture_recorded_only', $b['status']);
        self::assertNoBusinessAuthority($a);
        self::assertNoBusinessAuthority($b);
    }

    public function test_one_objects_version_increase_does_not_make_other_objects_version_stale(): void
    {
        $a = self::fixture();
        $b = self::fixture();
        $first = $a->submit(1, $a->marker(), 'fixture_key_one', self::clean());
        $second = $b->submit(1, $b->marker(), 'fixture_key_two', self::needsCorrection());

        self::assertSame(2, $a->version());
        self::assertSame(2, $b->version());
        self::assertSame('fixture_recorded_only', $first['status']);
        self::assertSame('fixture_recorded_only', $second['status']);
        self::assertNoBusinessAuthority($first);
        self::assertNoBusinessAuthority($second);
    }

    public function test_replacement_in_one_object_does_not_invalidate_another_objects_marker(): void
    {
        $first = self::fixture();
        $second = self::fixture();
        $old = $second->marker();
        $rows = self::rows();
        $rows[1][1] = 'changed fictional field';

        $changed = $first->replaceFixture(1, $rows, self::mapping());
        self::assertSame('fixture_replaced_review_reset', $changed['status']);
        self::assertNotSame($first->marker(), $old);
        self::assertSame(2, $first->version());

        self::assertSame($old, $second->marker());
        self::assertSame(1, $second->version());
        $acceptedByStaleObject = $second->submit(1, $old, 'fixture_key_stale', self::clean());
        self::assertSame('fixture_recorded_only', $acceptedByStaleObject['status']);
        self::assertNoBusinessAuthority($acceptedByStaleObject);
        self::assertNoBusinessAuthority($changed);
    }

    public function test_lost_ack_followed_by_new_instance_does_not_deduplicate(): void
    {
        $first = self::fixture();
        $marker = $first->marker();
        try {
            $first->submit(
                1, $marker, 'fixture_key_duplicated', self::clean(),
                'fixture_lost_ack_after_record',
            );
            self::fail('Expected lost acknowledgement.');
        } catch (RuntimeException $e) {
            self::assertSame(
                'Synthetic acknowledgement unavailable; outcome unknown.',
                $e->getMessage(),
            );
        }

        self::assertSame(1, $first->summary()['recorded_review_events']);

        // New object = no shared ledger, despite identical bytes, marker,
        // idempotency key and observed version.
        $restarted = self::fixture();
        $replay = $restarted->submit(
            1, $restarted->marker(), 'fixture_key_duplicated', self::clean(),
        );

        self::assertSame($marker, $restarted->marker());
        self::assertSame('fixture_recorded_only', $replay['status']);
        self::assertSame(1, $replay['recorded_review_events']);
        self::assertNoBusinessAuthority($replay);
    }

    public function test_repeating_in_same_instance_still_deduplicates_locally(): void
    {
        $first = self::fixture();
        $marker = $first->marker();
        $first->submit(1, $marker, 'fixture_key_repeat', self::clean());
        $retry = $first->submit(1, $marker, 'fixture_key_repeat', self::clean());

        self::assertSame('fixture_idempotent_replay_no_op', $retry['status']);
        self::assertSame(1, $retry['recorded_review_events']);
        self::assertNoBusinessAuthority($retry);
    }

    public function test_fake_version_change_can_be_reversed_only_locally_without_global_rollback_protection(): void
    {
        $first = self::fixture();
        $second = self::fixture();
        $initial = $first->marker();

        $rows = self::rows();
        $rows[1][1] = 'new-version-only-in-first-object';
        $first->replaceFixture(1, $rows, self::mapping());
        $first->replaceFixture(2, self::rows(), self::mapping());
        self::assertSame($initial, $first->marker());
        self::assertSame(3, $first->version());

        // The second instance never observed either change.
        self::assertSame(1, $second->version());
        self::assertSame($initial, $second->marker());
        self::assertNoBusinessAuthority($second->summary());
    }

    public function test_no_independent_object_result_can_claim_authenticated_actor_or_import(): void
    {
        $objects = [self::fixture(), self::fixture(), self::fixture()];
        $results = [];
        foreach ($objects as $index => $object) {
            $results[] = $object->submit(
                1, $object->marker(), 'fixture_key_'.($index + 1), self::clean(),
            );
        }

        foreach ($results as $result) {
            self::assertNoBusinessAuthority($result);
            self::assertArrayNotHasKey('principal_id', $result);
            self::assertArrayNotHasKey('organization_id', $result);
            self::assertArrayNotHasKey('tenant_id', $result);
            self::assertArrayNotHasKey('audit_id', $result);
            self::assertArrayNotHasKey('fingerprint', $result);
            self::assertArrayNotHasKey('idempotency_key', $result);
        }
    }
}
