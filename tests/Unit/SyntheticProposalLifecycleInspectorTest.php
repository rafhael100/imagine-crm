<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticProposalLifecycleInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticProposalLifecycleInspectorTest extends TestCase
{
    /** @return array{version:int,action:string,occurred_at_utc:string,expires_at_utc:?string} */
    private static function event(
        int $version,
        string $action,
        string $when,
        ?string $expiry = null,
    ): array {
        return [
            'version' => $version,
            'action' => $action,
            'occurred_at_utc' => $when,
            'expires_at_utc' => $expiry,
        ];
    }

    public function test_revised_proposal_never_inherits_earlier_claimed_acceptance(): void
    {
        $events = [
            self::event(1, 'create', '2026-11-01T10:00:00Z'),
            self::event(1, 'send', '2026-11-01T11:00:00Z', '2026-11-08T11:00:00Z'),
            self::event(1, 'view', '2026-11-01T12:00:00Z'),
            self::event(1, 'accept', '2026-11-02T12:00:00Z'),
            self::event(2, 'revise', '2026-11-03T12:00:00Z'),
            self::event(2, 'send', '2026-11-03T13:00:00Z', '2026-11-08T13:00:00Z'),
            self::event(2, 'view', '2026-11-03T14:00:00Z'),
            self::event(2, 'decline', '2026-11-04T12:00:00Z'),
        ];
        $initial = $events;
        $result = (new SyntheticProposalLifecycleInspector())->inspect($events);

        self::assertSame(8, $result['event_count']);
        self::assertSame(2, $result['versions_seen']);
        self::assertSame(2, $result['latest_version']);
        self::assertSame('declined', $result['latest_claimed_status']);
        self::assertSame([
            ['version' => 1, 'status' => 'accepted', 'viewed' => true],
            ['version' => 2, 'status' => 'declined', 'viewed' => true],
        ], $result['versions']);
        self::assertSame([
            'draft' => 0, 'sent' => 0, 'accepted' => 1,
            'declined' => 1, 'expired' => 0,
        ], $result['claimed_status_counts']);
        self::assertSame(1, $result['prior_claimed_accepted_versions']);
        self::assertSame(1, $result['viewed_without_claimed_acceptance']);
        self::assertFalse($result['prior_acceptance_applies_to_latest']);
        self::assertFalse($result['viewed_equals_acceptance']);
        self::assertFalse($result['acceptance_verified']);
        self::assertTrue($result['informational_only']);
        self::assertSame($initial, $events);
        self::assertSame($result, (new SyntheticProposalLifecycleInspector())->inspect($events));
        foreach (['authorized', 'can_send', 'can_accept', 'can_sign', 'can_charge', 'can_mark_won', 'can_persist'] as $flag) {
            self::assertFalse($result[$flag]);
        }
    }

    public function test_expiration_is_effective_at_exact_second_and_revision_starts_draft(): void
    {
        $events = [
            self::event(1, 'create', '2026-11-01T10:00:00Z'),
            self::event(1, 'send', '2026-11-01T11:00:00Z', '2026-11-02T11:00:00Z'),
            self::event(1, 'view', '2026-11-02T10:59:59Z'),
            self::event(1, 'expire', '2026-11-02T11:00:00Z'),
            self::event(2, 'revise', '2026-11-02T11:00:01Z'),
        ];
        $out = (new SyntheticProposalLifecycleInspector())->inspect($events);
        self::assertSame('draft', $out['latest_claimed_status']);
        self::assertSame([
            ['version' => 1, 'status' => 'expired', 'viewed' => true],
            ['version' => 2, 'status' => 'draft', 'viewed' => false],
        ], $out['versions']);
        self::assertFalse($out['can_accept']);
        self::assertFalse($out['can_send']);

        $empty = (new SyntheticProposalLifecycleInspector())->inspect([]);
        self::assertSame(0, $empty['event_count']);
        self::assertSame(0, $empty['versions_seen']);
        self::assertNull($empty['latest_version']);
        self::assertNull($empty['latest_claimed_status']);
        self::assertSame([], $empty['versions']);
        self::assertFalse($empty['authorized']);
    }

    public function test_maximum_eight_versions_remain_bounded_and_unverified(): void
    {
        $events = [self::event(1, 'create', '2026-11-01T10:00:00Z')];
        for ($v = 1; $v <= 8; ++$v) {
            if ($v > 1) {
                $events[] = self::event($v, 'revise', sprintf('2026-11-%02dT10:00:00Z', $v));
            }
            $events[] = self::event(
                $v,
                'send',
                sprintf('2026-11-%02dT11:00:00Z', $v),
                sprintf('2026-11-%02dT12:00:00Z', $v),
            );
        }
        $result = (new SyntheticProposalLifecycleInspector())->inspect($events);
        self::assertSame(16, $result['event_count']);
        self::assertSame(8, $result['versions_seen']);
        self::assertSame('sent', $result['latest_claimed_status']);
        self::assertSame(8, $result['claimed_status_counts']['sent']);
        self::assertFalse($result['can_send']);

        $events[] = self::event(9, 'revise', '2026-11-09T10:00:00Z');
        $this->expectException(InvalidArgumentException::class);
        (new SyntheticProposalLifecycleInspector())->inspect($events);
    }

    public function test_up_to_24_chronological_views_are_accepted_as_untrusted_claims(): void
    {
        $events = [
            self::event(1, 'create', '2026-11-01T11:59:58Z'),
            self::event(1, 'send', '2026-11-01T11:59:59Z', '2026-11-02T12:00:00Z'),
        ];
        for ($second = 0; $second <= 21; ++$second) {
            $events[] = self::event(1, 'view', sprintf('2026-11-01T12:00:%02dZ', $second));
        }

        $result = (new SyntheticProposalLifecycleInspector())->inspect($events);
        self::assertSame(24, $result['event_count']);
        self::assertTrue($result['latest_viewed']);
        self::assertSame('sent', $result['latest_claimed_status']);
        self::assertSame(1, $result['viewed_without_claimed_acceptance']);

        $events[] = self::event(1, 'view', '2026-11-01T12:00:22Z');
        $this->expectException(InvalidArgumentException::class);
        (new SyntheticProposalLifecycleInspector())->inspect($events);
    }

    public function test_invalid_transitions_dates_and_private_fields_fail_closed(): void
    {
        $create = self::event(1, 'create', '2026-11-01T10:00:00Z');
        $send = self::event(1, 'send', '2026-11-01T11:00:00Z', '2026-11-02T11:00:00Z');
        $atIssue = '2026-11-01T11:00:00Z';
        $atExpiry = '2026-11-02T11:00:00Z';
        $invalid = [
            null, 'event', true, 5,
            [1 => $create],
            [false],
            [self::event(2, 'create', '2026-11-01T10:00:00Z')],
            [self::event(1, 'send', $atIssue, $atExpiry)],
            [$create, $create],
            [$create, self::event(1, 'create', '2026-11-01T10:00:01Z')],
            [$create, self::event(1, 'view', $atIssue)],
            [$create, self::event(1, 'accept', $atIssue)],
            [$create, self::event(2, 'revise', $atIssue)],
            [$create, self::event(3, 'send', $atIssue, $atExpiry)],
            [$create, self::event(1, 'send', $atIssue, null)],
            [$create, self::event(1, 'send', $atIssue, $atIssue)],
            [$create, self::event(1, 'send', $atIssue, '2028-11-02T11:00:00Z')],
            [$create, self::event(1, 'send', $atIssue, '2026-02-30T11:00:00Z')],
            [$create, self::event(1, 'send', $atIssue, '2026-11-02T11:00:00+00:00')],
            [$create, $send, self::event(1, 'send', '2026-11-01T11:00:01Z', '2026-11-03T00:00:00Z')],
            [$create, $send, self::event(1, 'view', $atExpiry)],
            [$create, $send, self::event(1, 'accept', $atExpiry)],
            [$create, $send, self::event(1, 'decline', '2026-11-03T00:00:00Z')],
            [$create, $send, self::event(1, 'expire', '2026-11-02T10:59:59Z')],
            [$create, $send, self::event(1, 'accept', '2026-11-01T11:00:00Z')],
            [$create, $send, self::event(1, 'view', '2026-11-01T10:59:59Z')],
            [$create, $send, self::event(2, 'view', '2026-11-01T12:00:00Z')],
            [$create, $send, self::event(1, 'accept', '2026-11-01T12:00:00Z'),
                self::event(1, 'decline', '2026-11-01T12:00:01Z')],
            [$create, $send, self::event(1, 'expire', $atExpiry),
                self::event(1, 'accept', '2026-11-02T11:00:01Z')],
            [self::event(1, 'create', '2026-02-30T10:00:00Z')],
            [self::event(1, 'create', '2026-11-01 10:00:00Z')],
            [self::event(1, 'create', '2026-11-01T10:00:00+00:00')],
            [self::event(1, 'create', '2026-11-01T10:00:00.000Z')],
            [array_merge($create, ['tenant' => 'private@example.test'])],
            [['version' => 1, 'action' => 'create', 'occurred_at_utc' => '2026-11-01T10:00:00Z']],
            [['version' => '1', 'action' => 'create', 'occurred_at_utc' => '2026-11-01T10:00:00Z', 'expires_at_utc' => null]],
            [self::event(1, 'approve', '2026-11-01T10:00:00Z')],
            [self::event(1, 'create', '2026-11-01T10:00:00Z', '2026-11-02T10:00:00Z')],
        ];

        foreach ($invalid as $events) {
            try {
                (new SyntheticProposalLifecycleInspector())->inspect($events);
                self::fail('Invalid or unauthorized fictitious proposal transition was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Ciclo sintetico de proposta invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_result_has_no_dates_ids_money_or_business_authority(): void
    {
        $result = (new SyntheticProposalLifecycleInspector())->inspect([
            self::event(1, 'create', '2026-11-01T10:00:00Z'),
            self::event(1, 'send', '2026-11-01T11:00:00Z', '2026-11-02T11:00:00Z'),
            self::event(1, 'accept', '2026-11-01T12:00:00Z'),
        ]);
        self::assertSame([
            'event_count', 'versions_seen', 'versions',
            'latest_version', 'latest_claimed_status', 'latest_viewed',
            'claimed_status_counts', 'prior_claimed_accepted_versions',
            'viewed_without_claimed_acceptance',
            'prior_acceptance_applies_to_latest', 'viewed_equals_acceptance',
            'event_source_verified', 'approval_policy_verified',
            'acceptance_verified', 'informational_only', 'authorized',
            'can_send', 'can_accept', 'can_sign', 'can_charge',
            'can_mark_won', 'can_persist',
        ], array_keys($result));
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('2026-11-01', $json);
        foreach ([
            'tenant', 'email', 'phone', 'actor_id', 'customer', 'event_id',
            'proposal_id', 'link_token', 'signature', 'money', 'amount_cents',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        foreach ($result as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
    }
}
