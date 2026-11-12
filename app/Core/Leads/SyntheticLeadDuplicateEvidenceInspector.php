<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-195 / DAT-R08/R10: bounded, identity-free rehearsal for lead collision review.
 *
 * A logical submission retried across transport endpoints is NOT the same
 * question as whether two distinct submissions refer to the same person.
 * Pairwise caller claims are not trusted contact verification, identity,
 * permission, an idempotency lock, or evidence to merge CRM records.
 *
 * Inputs and outputs contain only local list positions and fixed enum codes;
 * names, addresses, e-mails, phones, tokens and hashes are deliberately
 * forbidden. Real reconciliation/merge stays NO-GO (#27, #259).
 */
final class SyntheticLeadDuplicateEvidenceInspector
{
    private const MAX_LEADS = 20;
    private const MAX_PAIRS = 64;

    private const RELATIONS = ['same', 'different', 'unavailable'];

    /**
     * @param mixed $leadCount Count of fictional, identity-free rows (0..20)
     * @param mixed $pairs Up to 64 caller-supplied positional comparisons.
     * @return array<string,mixed> Closed metadata-only review; no authority.
     */
    public function inspect(mixed $leadCount, mixed $pairs): array
    {
        if (!is_int($leadCount) || $leadCount < 0
            || $leadCount > self::MAX_LEADS
            || !is_array($pairs) || !array_is_list($pairs)
            || count($pairs) > self::MAX_PAIRS) {
            self::invalid();
        }

        $seen = [];
        $review = [];
        $counts = [
            'RETRY_CLAIM_UNVERIFIED' => 0,
            'CONFLICTING_RETRY_CLAIM' => 0,
            'POSSIBLE_PERSON_MATCH' => 0,
            'INSUFFICIENT_EVIDENCE' => 0,
        ];

        foreach ($pairs as $pair) {
            if (!is_array($pair) || count($pair) !== 5) {
                self::invalid();
            }
            $keys = array_keys($pair);
            sort($keys);
            if ($keys !== [
                'email_relation', 'left', 'phone_relation',
                'right', 'submission_relation',
            ]
                || !is_int($pair['left']) || !is_int($pair['right'])
                || $pair['left'] < 0 || $pair['left'] >= $leadCount
                || $pair['right'] <= $pair['left']
                || $pair['right'] >= $leadCount
                || !self::validRelation($pair['submission_relation'])
                || !self::validRelation($pair['email_relation'])
                || !self::validRelation($pair['phone_relation'])) {
                self::invalid();
            }

            $positionKey = $pair['left'].':'.$pair['right'];
            if (isset($seen[$positionKey])) {
                self::invalid();
            }
            $seen[$positionKey] = true;

            if ($pair['submission_relation'] === 'same') {
                $code = $pair['email_relation'] === 'different'
                    || $pair['phone_relation'] === 'different'
                    ? 'CONFLICTING_RETRY_CLAIM'
                    : 'RETRY_CLAIM_UNVERIFIED';
            } elseif ($pair['email_relation'] === 'same'
                || $pair['phone_relation'] === 'same') {
                $code = 'POSSIBLE_PERSON_MATCH';
            } else {
                // Even two "different" claims do not prove two identities.
                $code = 'INSUFFICIENT_EVIDENCE';
            }

            ++$counts[$code];
            $review[] = [
                'left_position' => $pair['left'],
                'right_position' => $pair['right'],
                'reason_code' => $code,
            ];
        }

        return [
            'lead_count' => $leadCount,
            'pair_count' => count($pairs),
            'review_pairs' => $review,
            'reason_counts' => $counts,
            'logical_submission_verified' => false,
            'contact_evidence_verified' => false,
            'person_identity_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'concurrency_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_deduplicate' => false,
            'can_reconcile' => false,
            'can_merge' => false,
            'can_acknowledge_delivery' => false,
            'can_update_lead' => false,
            'can_persist' => false,
        ];
    }

    private static function validRelation(mixed $relation): bool
    {
        return is_string($relation)
            && in_array($relation, self::RELATIONS, true);
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Comparacao sintetica de duplicidade invalida.');
    }
}
