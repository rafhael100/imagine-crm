<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticProposalValidityPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticProposalValidityPreviewTest extends TestCase
{
    private const ISSUE = '2026-11-01T12:00:00Z';
    private const EXPIRY = '2026-11-08T12:00:00Z';
    private const ISSUE_EPOCH = 1793534400;
    private const EXPIRY_EPOCH = self::ISSUE_EPOCH + 7 * 86400;

    public function test_before_during_last_second_and_exact_expiry_boundaries(): void
    {
        $cases = [
            ['2026-11-01T11:59:59Z', 'before_issue', 1, 604801],
            [self::ISSUE, 'within_validity', 0, 604800],
            ['2026-11-04T12:00:00Z', 'within_validity', 0, 345600],
            ['2026-11-08T11:59:59Z', 'within_validity', 0, 1],
            [self::EXPIRY, 'expired', 0, 0],
            ['2026-11-09T12:00:00Z', 'expired', 0, 0],
        ];

        foreach ($cases as [$now, $phase, $toIssue, $toExpiry]) {
            $review = new SyntheticProposalValidityPreview(self::ISSUE, self::EXPIRY, $now);
            $result = $review->readout();
            self::assertSame($phase, $review->phase());
            self::assertSame($phase, $result['phase']);
            self::assertSame(self::ISSUE_EPOCH, $result['issued_at_epoch']);
            self::assertSame(self::EXPIRY_EPOCH, $result['expires_at_epoch']);
            self::assertSame(604800, $result['validity_seconds']);
            self::assertSame($toIssue, $review->secondsUntilIssue());
            self::assertSame($toExpiry, $review->secondsUntilExpiry());
            self::assertSame($toIssue, $result['seconds_until_issue']);
            self::assertSame($toExpiry, $result['seconds_until_expiry']);
            self::assertSame($result, $review->readout());
        }
    }

    public function test_even_validity_window_does_not_grant_any_operational_authority(): void
    {
        $result = (new SyntheticProposalValidityPreview(
            self::ISSUE, self::EXPIRY, self::ISSUE,
        ))->readout();
        self::assertSame([
            'phase', 'issued_at_epoch', 'expires_at_epoch', 'validity_seconds',
            'seconds_until_issue', 'seconds_until_expiry',
            'validity_policy_verified', 'link_authorized',
            'acceptance_verified', 'informational_only',
            'authorized', 'can_send', 'can_accept', 'can_renew',
            'can_sign', 'can_charge', 'can_persist',
        ], array_keys($result));

        self::assertTrue($result['informational_only']);
        foreach ([
            'validity_policy_verified', 'link_authorized',
            'acceptance_verified', 'authorized', 'can_send',
            'can_accept', 'can_renew', 'can_sign', 'can_charge',
            'can_persist',
        ] as $flag) {
            self::assertFalse($result[$flag]);
        }
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['"tenant"', '"email"', '"phone"', '"customer"', '"link_token"', '"proposal_id"'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
    }

    public function test_maximum_366_days_is_a_lab_cap_not_customer_policy(): void
    {
        $good = (new SyntheticProposalValidityPreview(
            '2026-01-01T00:00:00Z', '2027-01-02T00:00:00Z',
            '2026-12-31T23:59:59Z',
        ))->readout();
        self::assertSame(366 * 86400, $good['validity_seconds']);
        self::assertSame('within_validity', $good['phase']);
        self::assertFalse($good['validity_policy_verified']);
    }

    public function test_invalid_windows_dates_timezones_and_oversized_duration_are_denied(): void
    {
        $bad = [
            [self::ISSUE, self::ISSUE, self::ISSUE],
            [self::EXPIRY, self::ISSUE, self::ISSUE],
            ['2026-01-01T00:00:00Z', '2027-01-03T00:00:00Z', self::ISSUE],
            ['2026-02-30T00:00:00Z', self::EXPIRY, self::ISSUE],
            ['2026-11-01T12:00:60Z', self::EXPIRY, self::ISSUE],
            ['2026-11-01T12:00:00+00:00', self::EXPIRY, self::ISSUE],
            ['2026-11-01 12:00:00Z', self::EXPIRY, self::ISSUE],
            ['0000-01-01T00:00:00Z', self::EXPIRY, self::ISSUE],
            ['2100-01-01T00:00:00Z', self::EXPIRY, self::ISSUE],
            [self::ISSUE, '2026-11-08T12:00:00-03:00', self::ISSUE],
            [self::ISSUE, self::EXPIRY, 'private@example.test'],
            [self::ISSUE, self::EXPIRY, '2026-11-01T12:00:00.000Z'],
        ];
        foreach ($bad as [$issue, $expiry, $observed]) {
            try {
                new SyntheticProposalValidityPreview($issue, $expiry, $observed);
                self::fail('Invalid or noncanonical fictional proposal validity accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Validade sintetica de proposta invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }
}
