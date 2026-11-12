<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticProposalSendPreflight;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticProposalSendPreflightTest extends TestCase
{
    /** @return list<array<string,mixed>> */
    private static function draftEvents(): array
    {
        return [[
            'version' => 1,
            'action' => 'create',
            'occurred_at_utc' => '2026-11-01T10:00:00Z',
            'expires_at_utc' => null,
        ]];
    }

    /** @return list<array<string,mixed>> */
    private static function sentAcceptedEvents(): array
    {
        return [
            ...self::draftEvents(),
            [
                'version' => 1, 'action' => 'send',
                'occurred_at_utc' => '2026-11-01T11:00:00Z',
                'expires_at_utc' => '2026-11-08T11:00:00Z',
            ],
            [
                'version' => 1, 'action' => 'accept',
                'occurred_at_utc' => '2026-11-01T12:00:00Z',
                'expires_at_utc' => null,
            ],
        ];
    }

    /** @return list<array<string,mixed>> */
    private static function discountLines(): array
    {
        return [[
            'quantity' => 1,
            'unit_price_cents' => 10000,
            'discount_bp' => 500,
        ]];
    }

    public function test_fictitious_draft_and_within_discount_limit_still_require_independent_authority(): void
    {
        $inputEvents = self::draftEvents();
        $inputLines = self::discountLines();
        $report = (new SyntheticProposalSendPreflight())
            ->inspect($inputEvents, $inputLines, 1000);

        self::assertSame(1, $report['latest_version']);
        self::assertSame('draft', $report['latest_claimed_status']);
        self::assertTrue($report['has_current_draft']);
        self::assertSame(1, $report['discount_line_count']);
        self::assertSame(10000, $report['gross_total_cents']);
        self::assertSame(500, $report['discount_total_cents']);
        self::assertSame(9500, $report['discounted_total_cents']);
        self::assertFalse($report['requires_manager_review']);
        self::assertFalse($report['manager_approval_claimed']);
        self::assertSame([
            'discount_policy_unverified',
            'sender_identity_unverified',
            'proposal_version_unverified',
            'recipient_access_unverified',
        ], $report['blocker_codes']);
        self::assertSame(4, $report['blocker_count']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'policy_verified', 'identity_verified', 'approval_verified',
            'document_verified', 'recipient_verified', 'authorized',
            'can_send', 'can_issue_proposal', 'can_accept', 'can_charge',
            'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }
        self::assertSame(self::draftEvents(), $inputEvents);
        self::assertSame(self::discountLines(), $inputLines);
        self::assertSame(
            $report,
            (new SyntheticProposalSendPreflight())->inspect($inputEvents, $inputLines, 1000),
        );
    }

    public function test_out_of_policy_discount_always_requires_review_even_if_manager_claimed_approval(): void
    {
        $rows = [[
            'quantity' => 2, 'unit_price_cents' => 10000, 'discount_bp' => 4000,
        ]];

        $unapproved = (new SyntheticProposalSendPreflight())
            ->inspect(self::draftEvents(), $rows, 1000);
        self::assertTrue($unapproved['requires_manager_review']);
        self::assertSame('manager_review_required', $unapproved['blocker_codes'][0]);
        self::assertSame(5, $unapproved['blocker_count']);
        self::assertSame(8000, $unapproved['discount_total_cents']);
        self::assertSame(12000, $unapproved['discounted_total_cents']);

        $approvalClaimed = (new SyntheticProposalSendPreflight())
            ->inspect(self::draftEvents(), $rows, 1000, true);
        self::assertTrue($approvalClaimed['requires_manager_review']);
        self::assertTrue($approvalClaimed['manager_approval_claimed']);
        self::assertSame([
            'manager_review_required',
            'manager_approval_unverified',
            'discount_policy_unverified',
            'sender_identity_unverified',
            'proposal_version_unverified',
            'recipient_access_unverified',
        ], $approvalClaimed['blocker_codes']);
        self::assertSame(6, $approvalClaimed['blocker_count']);
        self::assertFalse($approvalClaimed['approval_verified']);
        self::assertFalse($approvalClaimed['can_send']);
        self::assertFalse($approvalClaimed['can_issue_proposal']);

        $falseApprovalEvenInPolicy = (new SyntheticProposalSendPreflight())
            ->inspect(self::draftEvents(), $rows, 5000, true);
        self::assertFalse($falseApprovalEvenInPolicy['requires_manager_review']);
        self::assertContains('manager_approval_unverified', $falseApprovalEvenInPolicy['blocker_codes']);
        self::assertFalse($falseApprovalEvenInPolicy['authorized']);
    }

    public function test_claimed_acceptance_or_missing_proposal_version_cannot_be_sent_as_draft(): void
    {
        $accepted = (new SyntheticProposalSendPreflight())
            ->inspect(self::sentAcceptedEvents(), self::discountLines(), 1000);
        self::assertSame('accepted', $accepted['latest_claimed_status']);
        self::assertFalse($accepted['has_current_draft']);
        self::assertSame('current_draft_required', $accepted['blocker_codes'][0]);
        self::assertSame(5, $accepted['blocker_count']);
        self::assertFalse($accepted['can_send']);
        self::assertFalse($accepted['can_charge']);

        $missing = (new SyntheticProposalSendPreflight())->inspect(
            [], [], 0,
        );
        self::assertNull($missing['latest_version']);
        self::assertNull($missing['latest_claimed_status']);
        self::assertSame(0, $missing['gross_total_cents']);
        self::assertSame(0, $missing['discounted_total_cents']);
        self::assertFalse($missing['has_current_draft']);
        self::assertContains('current_draft_required', $missing['blocker_codes']);
        self::assertFalse($missing['can_issue_proposal']);
    }

    public function test_invalid_hidden_discount_or_event_payloads_fail_closed_before_review(): void
    {
        $bad = [
            [null, self::discountLines(), 1000, false],
            ['draft', self::discountLines(), 1000, false],
            [self::draftEvents(), null, 1000, false],
            [self::draftEvents(), ['bad'], 1000, false],
            [self::draftEvents(), self::discountLines(), '1000', false],
            [self::draftEvents(), self::discountLines(), -1, false],
            [self::draftEvents(), self::discountLines(), 10001, false],
            [self::draftEvents(), self::discountLines(), 1000, 'true'],
            [self::draftEvents(), self::discountLines(), 1000, 1],
            [
                self::draftEvents(),
                [['quantity' => 1, 'unit_price_cents' => 10000, 'discount_bp' => 500, 'email' => 'private@example.test']],
                1000, false,
            ],
            [
                [array_merge(self::draftEvents()[0], ['tenant' => 'private@example.test'])],
                self::discountLines(), 1000, false,
            ],
            [
                [...self::draftEvents(), [
                    'version' => 2, 'action' => 'accept',
                    'occurred_at_utc' => '2026-11-01T11:00:00Z',
                    'expires_at_utc' => null,
                ]],
                self::discountLines(), 1000, false,
            ],
        ];
        foreach ($bad as [$events, $items, $maxBp, $claimed]) {
            try {
                (new SyntheticProposalSendPreflight())->inspect($events, $items, $maxBp, $claimed);
                self::fail('Malformed preflight must not succeed.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Preflight sintetico de proposta invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_report_never_exposes_approval_identity_customer_or_proposal_events(): void
    {
        $report = (new SyntheticProposalSendPreflight())->inspect(
            self::draftEvents(), self::discountLines(), 0, true,
        );
        self::assertSame([
            'latest_version', 'latest_claimed_status', 'has_current_draft',
            'discount_line_count', 'gross_total_cents',
            'discount_total_cents', 'discounted_total_cents',
            'requires_manager_review', 'manager_approval_claimed',
            'blocker_codes', 'blocker_count',
            'policy_verified', 'identity_verified', 'approval_verified',
            'document_verified', 'recipient_verified',
            'informational_only', 'authorized', 'can_send',
            'can_issue_proposal', 'can_accept', 'can_charge', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('2026-11-01', $json);
        foreach ([
            'tenant', 'email', 'phone', 'customer', 'approver_id',
            'proposal_id', 'event_reference', 'contact', 'actor', 'payment',
        ] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
        self::assertFalse($report['can_persist']);
        self::assertFalse($report['can_send']);
    }
}
