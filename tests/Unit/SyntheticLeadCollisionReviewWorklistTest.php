<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadCollisionReviewWorklist;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadCollisionReviewWorklistTest extends TestCase
{
    private static function pair(
        int $left,
        int $right,
        string $submission = 'different',
        string $email = 'unavailable',
        string $phone = 'unavailable',
    ): array {
        return [
            'left' => $left,
            'right' => $right,
            'submission_relation' => $submission,
            'email_relation' => $email,
            'phone_relation' => $phone,
        ];
    }

    private static function fixture(): array
    {
        return [
            self::pair(0, 1, 'different'),
            self::pair(0, 2, 'same'),
            self::pair(0, 3, 'different', 'same'),
            self::pair(0, 4, 'same', 'different'),
            self::pair(0, 5, 'unavailable', 'unavailable', 'same'),
            self::pair(1, 2, 'same', 'same', 'different'),
            self::pair(1, 3, 'different'),
        ];
    }

    public function test_prioritizes_contradictions_before_person_matches_before_retry_before_other(): void
    {
        $fixture = self::fixture();
        $original = $fixture;
        $inspector = new SyntheticLeadCollisionReviewWorklist();
        $result = $inspector->inspect(6, $fixture, 10, 0);
        self::assertSame($original, $fixture);
        self::assertSame($result, $inspector->inspect(6, $fixture, 10, 0));
        self::assertSame(7, $result['items_on_page']);
        self::assertSame(1, $result['page_count']);
        self::assertSame(4, $result['priority_review_count']);
        self::assertSame([3, 5, 2, 4, 1, 0, 6],
            array_column($result['page_items'], 'pair_position'));
        self::assertSame([
            'CONFLICTING_RETRY_CLAIM', 'CONFLICTING_RETRY_CLAIM',
            'POSSIBLE_PERSON_MATCH', 'POSSIBLE_PERSON_MATCH',
            'RETRY_CLAIM_UNVERIFIED',
            'INSUFFICIENT_EVIDENCE', 'INSUFFICIENT_EVIDENCE',
        ], array_column($result['page_items'], 'reason_code'));
        self::assertSame(0, $result['unshown_pair_count']);
        self::assertFalse($result['has_next_page']);
        self::assertFalse($result['can_merge']);
        self::assertFalse($result['can_acknowledge_delivery']);
    }

    public function test_paginated_review_is_stable_with_explicit_remainder(): void
    {
        $inspector = new SyntheticLeadCollisionReviewWorklist();
        $a = $inspector->inspect(6, self::fixture(), 3, 0);
        $b = $inspector->inspect(6, self::fixture(), 3, 1);
        $c = $inspector->inspect(6, self::fixture(), 3, 2);
        self::assertSame(3, $a['page_count']);
        self::assertSame(4, $a['unshown_pair_count']);
        self::assertSame(1, $c['items_on_page']);
        self::assertSame(0, $c['unshown_pair_count']);
        self::assertTrue($a['has_next_page']);
        self::assertTrue($b['has_next_page']);
        self::assertFalse($c['has_next_page']);
        self::assertSame([3,5,2,4,1,0,6], array_merge(
            array_column($a['page_items'], 'pair_position'),
            array_column($b['page_items'], 'pair_position'),
            array_column($c['page_items'], 'pair_position'),
        ));
        self::assertSame($a['reason_counts'], $c['reason_counts'],
            'All global counts must come from the same complete fixture.');
    }

    public function test_empty_queue_has_no_fictional_reviewer_or_effects(): void
    {
        $result = (new SyntheticLeadCollisionReviewWorklist())->inspect(0, [], 5, 0);
        self::assertSame(0, $result['pair_count']);
        self::assertSame(0, $result['page_count']);
        self::assertSame([], $result['page_items']);
        self::assertSame(0, $result['unshown_pair_count']);
        self::assertFalse($result['can_assign_reviewer']);
        self::assertFalse($result['can_export']);
    }

    public function test_alleged_all_contact_matches_still_does_not_grant_real_identity_or_action(): void
    {
        $result = (new SyntheticLeadCollisionReviewWorklist())->inspect(2, [
            self::pair(0, 1, 'different', 'same', 'same'),
        ]);
        self::assertSame('POSSIBLE_PERSON_MATCH', $result['page_items'][0]['reason_code']);
        foreach ($result as $key => $value) {
            if ($key === 'authorized'
                || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
        self::assertTrue($result['informational_only']);
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['"email"', '"phone"', '"lead_id"', '"person_id"', '"tenant"', '"token"'] as $secret) {
            self::assertStringNotContainsString($secret, $encoded);
        }
    }

    public function test_entire_fixture_is_validated_before_page_is_selected(): void
    {
        $original = self::fixture();
        $invalid = $original;
        $invalid[6]['email_relation'] = 'secret@example.test';
        $cases = [
            [6, $invalid, 1, 0],
            [6, [self::pair(0, 1), self::pair(0, 1)], 1, 0],
            [6, [self::pair(0, 7)], 1, 0],
            [6, self::fixture(), 0, 0],
            [6, self::fixture(), 11, 0],
            [6, self::fixture(), 1.0, 0],
            [6, self::fixture(), 5, 2],
            [6, self::fixture(), 5, -1],
            [6, self::fixture(), 5, 64],
            [0, [], 5, 1],
            [6, self::fixture(), 5, '0'],
        ];
        foreach ($cases as [$leads, $pairs, $pageSize, $page]) {
            try {
                (new SyntheticLeadCollisionReviewWorklist())
                    ->inspect($leads, $pairs, $pageSize, $page);
                self::fail('Untrusted review queue was incorrectly accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_maximum_sixty_four_pairs_fit_seven_ten_item_pages(): void
    {
        $pairs = [];
        for ($a = 0; $a < 20 && count($pairs) < 64; ++$a) {
            for ($b = $a + 1; $b < 20 && count($pairs) < 64; ++$b) {
                $pairs[] = self::pair($a, $b);
            }
        }
        $out = (new SyntheticLeadCollisionReviewWorklist())->inspect(20, $pairs, 10, 6);
        self::assertSame(7, $out['page_count']);
        self::assertSame(4, $out['items_on_page']);
        self::assertSame([60, 61, 62, 63], array_column($out['page_items'], 'pair_position'));
        self::assertFalse($out['can_persist']);
    }
}
