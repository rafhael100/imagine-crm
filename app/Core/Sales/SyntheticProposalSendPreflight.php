<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R09/R11/R12/R13/R14 / VEN-T05/T06: composite preflight of invented
 * proposal events and discount lines. A descriptive "draft" or apparent
 * in-policy discount NEVER grants authority to send a real proposal.
 *
 * This orchestrates two existing Core validators; never accesses accounts,
 * catalog, pricing policy, document versions, approvals, recipients or I/O.
 */
final class SyntheticProposalSendPreflight
{
    /**
     * @param mixed $lifecycleEvents Strict fictional lifecycle event sequence
     * @param mixed $discountLines Strict fictional unit/quantity/discount rows
     * @param mixed $maximumDiscountBp Illustrative tenant policy (unverified)
     * @param mixed $approvalClaimed Untrusted manager-approval claim
     * @return array<string,mixed> Fixed bounded review without identifiers
     */
    public function inspect(
        mixed $lifecycleEvents,
        mixed $discountLines,
        mixed $maximumDiscountBp,
        mixed $approvalClaimed = false,
    ): array {
        try {
            // Validate the COMPLETE supplied sequences before considering
            // a single readiness condition. No invalid hidden line can
            // bypass validation because an early blocker was present.
            $lifecycle = (new SyntheticProposalLifecycleInspector())
                ->inspect($lifecycleEvents);
            $discount = (new SyntheticCommercialDiscountReview())
                ->inspect($discountLines, $maximumDiscountBp, $approvalClaimed);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $hasDraft = $lifecycle['latest_claimed_status'] === 'draft';
        $requiresReview = $discount['requires_manager_review'];
        $claimedApproval = $discount['manager_approval_claimed'];

        $blockers = [];
        if (!$hasDraft) {
            $blockers[] = 'current_draft_required';
        }
        if ($requiresReview) {
            $blockers[] = 'manager_review_required';
        }
        if ($claimedApproval) {
            $blockers[] = 'manager_approval_unverified';
        }

        // These independent authorities have not been established by any
        // synthetic event, discount percent or UI confirmation.
        $blockers[] = 'discount_policy_unverified';
        $blockers[] = 'sender_identity_unverified';
        $blockers[] = 'proposal_version_unverified';
        $blockers[] = 'recipient_access_unverified';

        return [
            'latest_version' => $lifecycle['latest_version'],
            'latest_claimed_status' => $lifecycle['latest_claimed_status'],
            'has_current_draft' => $hasDraft,
            'discount_line_count' => $discount['line_count'],
            'gross_total_cents' => $discount['gross_total_cents'],
            'discount_total_cents' => $discount['discount_total_cents'],
            'discounted_total_cents' => $discount['discounted_total_cents'],
            'requires_manager_review' => $requiresReview,
            'manager_approval_claimed' => $claimedApproval,
            'blocker_codes' => $blockers,
            'blocker_count' => count($blockers),
            'policy_verified' => false,
            'identity_verified' => false,
            'approval_verified' => false,
            'document_verified' => false,
            'recipient_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_send' => false,
            'can_issue_proposal' => false,
            'can_accept' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        // Do not leak customer data, source input, tenant or policy.
        throw new InvalidArgumentException('Preflight sintetico de proposta invalido.');
    }
}
