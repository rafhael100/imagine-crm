<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadDuplicateEvidenceInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadDuplicateEvidenceInspectorTest extends TestCase
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

    public function test_retry_and_person_collision_are_separate_not_mergeable_claims(): void
    {
        $pairs = [
            self::pair(0, 1, 'same'),
            self::pair(0, 2, 'different', 'same'),
            self::pair(0, 3, 'unknown', 'unavailable', 'same'),
        ];
        // "unknown" is not an allowed relation; malformed claims fail closed.
        $this->expectException(InvalidArgumentException::class);
        (new SyntheticLeadDuplicateEvidenceInspector())->inspect(4, $pairs);
    }

    public function test_four_distinct_review_codes_without_values_or_authority(): void
    {
        $pairs = [
            self::pair(0, 1, 'same'),
            self::pair(0, 2, 'same', 'different'),
            self::pair(0, 3, 'different', 'same'),
            self::pair(0, 4, 'unavailable', 'unavailable', 'same'),
            self::pair(0, 5, 'different', 'different', 'different'),
        ];
        $unchanged = $pairs;
        $result = (new SyntheticLeadDuplicateEvidenceInspector())->inspect(6, $pairs);
        self::assertSame($unchanged, $pairs);
        self::assertSame($result, (new SyntheticLeadDuplicateEvidenceInspector())->inspect(6, $pairs));
        self::assertSame(5, $result['pair_count']);
        self::assertSame([
            'RETRY_CLAIM_UNVERIFIED' => 1,
            'CONFLICTING_RETRY_CLAIM' => 1,
            'POSSIBLE_PERSON_MATCH' => 2,
            'INSUFFICIENT_EVIDENCE' => 1,
        ], $result['reason_counts']);
        self::assertSame([
            ['left_position'=>0,'right_position'=>1,'reason_code'=>'RETRY_CLAIM_UNVERIFIED'],
            ['left_position'=>0,'right_position'=>2,'reason_code'=>'CONFLICTING_RETRY_CLAIM'],
            ['left_position'=>0,'right_position'=>3,'reason_code'=>'POSSIBLE_PERSON_MATCH'],
            ['left_position'=>0,'right_position'=>4,'reason_code'=>'POSSIBLE_PERSON_MATCH'],
            ['left_position'=>0,'right_position'=>5,'reason_code'=>'INSUFFICIENT_EVIDENCE'],
        ], $result['review_pairs']);
        foreach ($result as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
        self::assertTrue($result['informational_only']);
        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['email', 'phone', 'tenant', 'lead_id', 'submission_id',
            'identity', 'person_id', 'token', 'hash', '@'] as $sensitive) {
            self::assertStringNotContainsString('"'.$sensitive.'"', $serialized);
        }
    }

    public function test_a_same_logical_submission_with_different_contact_is_not_a_clean_retry(): void
    {
        $result = (new SyntheticLeadDuplicateEvidenceInspector())->inspect(3, [
            self::pair(0, 1, 'same', 'same', 'different'),
            self::pair(0, 2, 'same', 'different', 'different'),
        ]);
        self::assertSame(2, $result['reason_counts']['CONFLICTING_RETRY_CLAIM']);
        self::assertSame(0, $result['reason_counts']['RETRY_CLAIM_UNVERIFIED']);
        self::assertFalse($result['can_acknowledge_delivery']);
        self::assertFalse($result['can_merge']);
    }

    public function test_identical_contact_claim_for_different_submissions_never_proves_person_identity(): void
    {
        $result = (new SyntheticLeadDuplicateEvidenceInspector())->inspect(2, [
            self::pair(0, 1, 'different', 'same', 'same'),
        ]);
        self::assertSame('POSSIBLE_PERSON_MATCH', $result['review_pairs'][0]['reason_code']);
        self::assertFalse($result['person_identity_verified']);
        self::assertFalse($result['can_deduplicate']);
        self::assertFalse($result['can_reconcile']);
        self::assertFalse($result['can_merge']);
    }

    public function test_empty_and_maximum_bounded_fixtures_produce_no_authority(): void
    {
        $inspector = new SyntheticLeadDuplicateEvidenceInspector();
        $empty = $inspector->inspect(0, []);
        self::assertSame(0, $empty['lead_count']);
        self::assertSame([], $empty['review_pairs']);
        self::assertFalse($empty['can_persist']);

        $pairs = [];
        for ($a = 0; $a < 20 && count($pairs) < 64; ++$a) {
            for ($b = $a + 1; $b < 20 && count($pairs) < 64; ++$b) {
                $pairs[] = self::pair($a, $b);
            }
        }
        $maximum = $inspector->inspect(20, $pairs);
        self::assertSame(64, $maximum['pair_count']);
        self::assertSame(64, $maximum['reason_counts']['INSUFFICIENT_EVIDENCE']);
        self::assertCount(64, $maximum['review_pairs']);
        self::assertFalse($maximum['can_update_lead']);
    }

    public function test_strict_shape_and_sparse_or_duplicate_pairs_fail_closed_without_leaking_inputs(): void
    {
        $good = self::pair(0, 1);
        $cases = [
            [-1, []], [21, []], ['2', []], [2, null], [0, [$good]],
            [1, [$good]], [2, [$good, $good]],
            [2, [self::pair(1, 0)]], [2, [self::pair(0, 0)]],
            [2, [self::pair(-1, 1)]], [2, [self::pair(0, 2)]],
            [2, [1 => $good]], [2, [[...$good, 'email' => 'secret@example.test']]],
            [2, [array_replace($good, ['email_relation' => 'secret@example.test'])]],
            [2, [array_replace($good, ['email_relation' => true])]],
            [2, [array_replace($good, ['submission_relation' => 'unknown'])]],
            [2, [array_replace($good, ['phone_relation' => 'SAME'])]],
            [2, [array_replace($good, ['left' => '0'])]],
            [2, [array_replace($good, ['right' => 1.0])]],
            [2, [array_replace($good, ['phone_relation' => null])]],
            [20, array_fill(0, 65, $good)],
        ];
        foreach ($cases as [$count, $pairs]) {
            try {
                (new SyntheticLeadDuplicateEvidenceInspector())->inspect($count, $pairs);
                self::fail('Untrusted collision evidence was incorrectly accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Comparacao sintetica de duplicidade invalida.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }
}
