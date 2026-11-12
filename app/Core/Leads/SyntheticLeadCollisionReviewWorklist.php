<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-196 / DAT-R08/R10: fixed-priority, paginated review of invented pair
 * positions. Reuses F3-195 validation on the FULL input before slicing.
 *
 * This is not a human-review authorization, CRM screen, queue worker,
 * tenant lookup, match decision, retry/delivery ACK or persisted workload.
 * No identifiers, contact data, source payloads, tokens or PII are accepted.
 */
final class SyntheticLeadCollisionReviewWorklist
{
    private const PRIORITY = [
        'CONFLICTING_RETRY_CLAIM' => 0,
        'POSSIBLE_PERSON_MATCH' => 1,
        'RETRY_CLAIM_UNVERIFIED' => 2,
        'INSUFFICIENT_EVIDENCE' => 3,
    ];

    /**
     * @param mixed $leadCount Anonymous count (0..20)
     * @param mixed $pairs Strict, anonymous F3-195 pair observations
     * @param mixed $pageSize integer 1..10
     * @param mixed $page Zero-based page
     * @return array<string,mixed> Pure, deny-only positional review page
     */
    public function inspect(
        mixed $leadCount,
        mixed $pairs,
        mixed $pageSize = 5,
        mixed $page = 0,
    ): array {
        if (!is_int($pageSize) || $pageSize < 1 || $pageSize > 10
            || !is_int($page) || $page < 0 || $page > 63) {
            self::invalid();
        }

        // Importantly validate every observation even when it would not
        // appear on the requested page or would have low priority.
        $analysis = (new SyntheticLeadDuplicateEvidenceInspector())
            ->inspect($leadCount, $pairs);
        $rows = $analysis['review_pairs'];

        $count = count($rows);
        $pages = $count === 0 ? 0 : intdiv($count + $pageSize - 1, $pageSize);
        if (($count === 0 && $page !== 0)
            || ($count !== 0 && $page >= $pages)) {
            self::invalid();
        }

        $ordered = [];
        foreach ($rows as $position => $pair) {
            $ordered[] = [
                'pair_position' => $position,
                'left_position' => $pair['left_position'],
                'right_position' => $pair['right_position'],
                'reason_code' => $pair['reason_code'],
            ];
        }

        // Stable priority with original input position as tie-breaker.
        usort($ordered, static fn (array $a, array $b): int =>
            (self::PRIORITY[$a['reason_code']] <=> self::PRIORITY[$b['reason_code']])
            ?: ($a['pair_position'] <=> $b['pair_position']));

        $offset = $page * $pageSize;
        $current = array_slice($ordered, $offset, $pageSize);
        $priorityCount = $analysis['reason_counts']['CONFLICTING_RETRY_CLAIM']
            + $analysis['reason_counts']['POSSIBLE_PERSON_MATCH'];

        return [
            'lead_count' => $analysis['lead_count'],
            'pair_count' => $count,
            'page_size' => $pageSize,
            'requested_page' => $page,
            'page_count' => $pages,
            'items_on_page' => count($current),
            'unshown_pair_count' => $count - ($offset + count($current)),
            'has_next_page' => $page + 1 < $pages,
            'priority_review_count' => $priorityCount,
            'reason_counts' => $analysis['reason_counts'],
            'page_items' => $current,
            'submission_identity_verified' => false,
            'person_identity_verified' => false,
            'tenant_scope_verified' => false,
            'contact_evidence_verified' => false,
            'reviewer_permissions_verified' => false,
            'reviewer_assignment_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign_reviewer' => false,
            'can_merge' => false,
            'can_reconcile' => false,
            'can_acknowledge_delivery' => false,
            'can_export' => false,
            'can_update_lead' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Fila sintetica de revisao invalida.');
    }
}
