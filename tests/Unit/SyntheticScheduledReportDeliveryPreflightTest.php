<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticScheduledReportDeliveryPreflight;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticScheduledReportDeliveryPreflightTest extends TestCase
{
    /** @return array<string,mixed> */
    private static function schedule(): array
    {
        return [
            'at_utc' => '2026-11-01T10:00:00Z',
            'recipient_access' => true,
            'policy_revision' => 5,
            'requested_fields' => ['stage', 'score'],
        ];
    }

    /** @return array<string,mixed> */
    private static function delivery(): array
    {
        return [
            'at_utc' => '2026-11-02T10:00:00Z',
            'recipient_access' => true,
            'policy_revision' => 5,
            'report_state' => 'ready',
        ];
    }

    public function test_recipient_loses_permission_after_scheduling_so_delivery_stays_denied(): void
    {
        $schedule = self::schedule();
        $delivery = array_merge(self::delivery(), ['recipient_access' => false]);
        $originalSchedule = $schedule;
        $originalDelivery = $delivery;
        $report = (new SyntheticScheduledReportDeliveryPreflight())
            ->inspect($schedule, $delivery);

        self::assertSame(2, $report['requested_field_count']);
        self::assertSame('ready', $report['claimed_report_state']);
        self::assertTrue($report['access_revoked_since_schedule']);
        self::assertFalse($report['policy_revision_changed']);
        self::assertSame([
            'access_not_claimed_at_delivery',
            'recipient_identity_unverified',
            'tenant_scope_unverified',
            'recipient_permissions_unverified',
            'delivery_channel_unverified',
        ], $report['blocker_codes']);
        self::assertSame(5, $report['blocker_count']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'revalidation_verified', 'recipient_identity_verified',
            'tenant_scope_verified', 'recipient_permissions_verified',
            'transport_verified', 'authorized', 'can_read_report',
            'can_export', 'can_generate_link', 'can_deliver',
            'can_notify', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }
        self::assertSame($originalSchedule, $schedule);
        self::assertSame($originalDelivery, $delivery);
        self::assertSame(
            $report,
            (new SyntheticScheduledReportDeliveryPreflight())
                ->inspect($schedule, $delivery),
        );
    }

    public function test_policy_changes_and_expired_or_revoked_report_block_the_claim(): void
    {
        $preflight = new SyntheticScheduledReportDeliveryPreflight();
        $changed = $preflight->inspect(
            self::schedule(),
            array_merge(self::delivery(), ['policy_revision' => 6]),
        );
        self::assertTrue($changed['policy_revision_changed']);
        self::assertContains('policy_revision_changed', $changed['blocker_codes']);
        self::assertFalse($changed['can_deliver']);

        foreach (['revoked', 'expired', 'missing'] as $state) {
            $result = $preflight->inspect(
                self::schedule(),
                array_merge(self::delivery(), ['report_state' => $state]),
            );
            self::assertContains('report_not_ready_at_delivery', $result['blocker_codes']);
            self::assertSame($state, $result['claimed_report_state']);
            self::assertFalse($result['can_generate_link']);
            self::assertFalse($result['recipient_permissions_verified']);
        }
    }

    public function test_even_all_positive_claims_never_authorize_export_or_delivery(): void
    {
        $review = (new SyntheticScheduledReportDeliveryPreflight())
            ->inspect(self::schedule(), self::delivery());

        self::assertFalse($review['access_revoked_since_schedule']);
        self::assertFalse($review['policy_revision_changed']);
        self::assertSame([
            'recipient_identity_unverified',
            'tenant_scope_unverified',
            'recipient_permissions_unverified',
            'delivery_channel_unverified',
        ], $review['blocker_codes']);
        self::assertSame(4, $review['blocker_count']);
        self::assertFalse($review['can_read_report']);
        self::assertFalse($review['can_export']);
        self::assertFalse($review['can_deliver']);

        $sameInstant = (new SyntheticScheduledReportDeliveryPreflight())->inspect(
            self::schedule(),
            array_merge(self::delivery(), ['at_utc' => '2026-11-01T10:00:00Z']),
        );
        self::assertSame(4, $sameInstant['blocker_count']);
        self::assertFalse($sameInstant['authorized']);

        $neverHadAccess = (new SyntheticScheduledReportDeliveryPreflight())->inspect(
            array_merge(self::schedule(), ['recipient_access' => false]),
            array_merge(self::delivery(), ['recipient_access' => false]),
        );
        self::assertFalse($neverHadAccess['access_revoked_since_schedule']);
        self::assertContains('access_not_claimed_at_schedule', $neverHadAccess['blocker_codes']);
        self::assertContains('access_not_claimed_at_delivery', $neverHadAccess['blocker_codes']);
    }

    public function test_invalid_fields_time_and_fake_sensitive_access_fail_closed(): void
    {
        $a = self::schedule();
        $b = self::delivery();
        $bad = [
            [null, $b], [$a, null], ['schedule', $b], [$a, 'delivery'],
            [array_merge($a, ['email' => 'private@example.test']), $b],
            [$a, array_merge($b, ['tenant' => 'private@example.test'])],
            [array_merge($a, ['requested_fields' => ['email']]), $b],
            [array_merge($a, ['requested_fields' => ['phone']]), $b],
            [array_merge($a, ['requested_fields' => []]), $b],
            [array_merge($a, ['requested_fields' => ['stage', 'stage']]), $b],
            [array_merge($a, ['requested_fields' => [1 => 'stage']]), $b],
            [array_merge($a, ['requested_fields' => ['stage', 'score', 'claimed_value_cents', 'extra']]), $b],
            [array_merge($a, ['requested_fields' => 'stage']), $b],
            [array_merge($a, ['recipient_access' => 1]), $b],
            [$a, array_merge($b, ['recipient_access' => 'true'])],
            [array_merge($a, ['policy_revision' => 0]), $b],
            [array_merge($a, ['policy_revision' => 10001]), $b],
            [array_merge($a, ['policy_revision' => '5']), $b],
            [$a, array_merge($b, ['policy_revision' => 5.0])],
            [$a, array_merge($b, ['report_state' => 'paid'])],
            [$a, array_merge($b, ['report_state' => 1])],
            [array_merge($a, ['at_utc' => '2026-02-30T10:00:00Z']), $b],
            [$a, array_merge($b, ['at_utc' => '2026-02-30T10:00:00Z'])],
            [$a, array_merge($b, ['at_utc' => '2026-10-31T10:00:00Z'])],
            [$a, array_merge($b, ['at_utc' => '2026-11-02T10:00:00-03:00'])],
            [array_merge($a, ['at_utc' => '2026-11-01 10:00:00Z']), $b],
            [array_merge($a, ['at_utc' => '2100-01-01T10:00:00Z']), $b],
        ];
        foreach ($bad as [$schedule, $delivery]) {
            try {
                (new SyntheticScheduledReportDeliveryPreflight())
                    ->inspect($schedule, $delivery);
                self::fail('Unsafe report delivery preflight was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Entrega sintetica de relatorio invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_response_excludes_sensitive_fields_identities_and_timestamps(): void
    {
        $report = (new SyntheticScheduledReportDeliveryPreflight())
            ->inspect(self::schedule(), self::delivery());
        self::assertSame([
            'requested_field_count', 'claimed_report_state',
            'access_revoked_since_schedule', 'policy_revision_changed',
            'blocker_codes', 'blocker_count', 'revalidation_verified',
            'recipient_identity_verified', 'tenant_scope_verified',
            'recipient_permissions_verified', 'transport_verified',
            'informational_only', 'authorized', 'can_read_report',
            'can_export', 'can_generate_link', 'can_deliver',
            'can_notify', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('2026-11-01', $json);
        foreach ([
            'email', 'phone', 'name', 'tenant', 'customer', 'recipient_id',
            'report_id', 'token', 'link_url', 'actor', 'policy_revision_at',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        self::assertFalse($report['can_deliver']);
    }
}
