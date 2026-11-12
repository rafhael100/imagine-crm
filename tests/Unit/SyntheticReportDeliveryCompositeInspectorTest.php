<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticReportDeliveryCompositeInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticReportDeliveryCompositeInspectorTest extends TestCase
{
    /** @return list<array{stage:string,claimed_value_cents:int}> */
    private static function rows(): array
    {
        return [
            ['stage' => 'new', 'claimed_value_cents' => 100],
            ['stage' => 'new', 'claimed_value_cents' => 200],
            ['stage' => 'new', 'claimed_value_cents' => 300],
            ['stage' => 'won', 'claimed_value_cents' => 987654],
        ];
    }

    /** @return array<string,mixed> */
    private static function schedule(): array
    {
        return [
            'at_utc' => '2026-11-01T10:00:00Z',
            'recipient_access' => true,
            'policy_revision' => 1,
            'requested_fields' => ['stage', 'claimed_value_cents'],
        ];
    }

    /** @return array<string,mixed> */
    private static function delivery(): array
    {
        return [
            'at_utc' => '2026-11-02T10:00:00Z',
            'recipient_access' => true,
            'policy_revision' => 1,
            'report_state' => 'ready',
        ];
    }

    public function test_all_positive_access_claims_still_return_no_csv_bytes_or_delivery_rights(): void
    {
        $rows = self::rows();
        $schedule = self::schedule();
        $delivery = self::delivery();
        $inspector = new SyntheticReportDeliveryCompositeInspector();
        $report = $inspector->inspect($rows, 3, $schedule, $delivery);

        self::assertSame('stage,count,claimed_sum_cents', $report['csv_format']);
        self::assertFalse($report['csv_text_returned']);
        self::assertSame(1, $report['visible_aggregate_lines']);
        self::assertSame(1, $report['suppressed_bucket_count']);
        self::assertSame(2, $report['requested_field_count']);
        self::assertSame('ready', $report['claimed_report_state']);
        self::assertFalse($report['recipient_access_revoked_claim']);
        self::assertFalse($report['policy_revision_changed_claim']);
        self::assertSame(['small_groups_suppressed'], $report['review_issue_codes']);
        self::assertSame(1, $report['review_issue_count']);
        self::assertSame([
            'recipient_identity_unverified', 'tenant_scope_unverified',
            'recipient_permissions_unverified', 'delivery_channel_unverified',
        ], $report['recipient_blocker_codes']);
        self::assertSame(0, $report['recognized_revenue_cents']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'report_data_verified', 'tenant_scope_verified',
            'privacy_policy_verified', 'recipient_permissions_verified',
            'transport_verified', 'authorized', 'can_read_report',
            'can_export', 'can_generate_link', 'can_download',
            'can_deliver', 'can_notify', 'can_persist',
        ] as $key) {
            self::assertFalse($report[$key], $key);
        }
        self::assertSame(self::rows(), $rows);
        self::assertSame(self::schedule(), $schedule);
        self::assertSame(self::delivery(), $delivery);
        self::assertSame($report, $inspector->inspect($rows, 3, $schedule, $delivery));

        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('987654', $json);
        self::assertStringNotContainsString('new,3,600', $json);
        self::assertStringNotContainsString('2026-11-', $json);
        self::assertStringNotContainsString('"csv_preview_utf8"', $json);
    }

    public function test_loss_of_access_policy_change_and_expiration_produce_distinct_codes(): void
    {
        $out = (new SyntheticReportDeliveryCompositeInspector())->inspect(
            self::rows(), 3, self::schedule(),
            array_merge(self::delivery(), [
                'recipient_access' => false,
                'policy_revision' => 2,
                'report_state' => 'expired',
            ]),
        );
        self::assertTrue($out['recipient_access_revoked_claim']);
        self::assertTrue($out['policy_revision_changed_claim']);
        self::assertSame('expired', $out['claimed_report_state']);
        self::assertSame([
            'small_groups_suppressed',
            'recipient_access_revoked_claim',
            'recipient_policy_changed_claim',
            'report_not_ready_claim',
        ], $out['review_issue_codes']);
        self::assertSame(4, $out['review_issue_count']);
        self::assertContains('access_not_claimed_at_delivery', $out['recipient_blocker_codes']);
        self::assertContains('policy_revision_changed', $out['recipient_blocker_codes']);
        self::assertContains('report_not_ready_at_delivery', $out['recipient_blocker_codes']);
        self::assertFalse($out['can_download']);
        self::assertFalse($out['can_deliver']);
        self::assertSame(0, $out['recognized_revenue_cents']);
    }

    public function test_no_group_suppression_still_does_not_return_csv_or_authorize_sender(): void
    {
        $rows = [
            ['stage' => 'won', 'claimed_value_cents' => 100],
            ['stage' => 'won', 'claimed_value_cents' => 200],
            ['stage' => 'won', 'claimed_value_cents' => 300],
        ];
        $out = (new SyntheticReportDeliveryCompositeInspector())->inspect(
            $rows, 3, self::schedule(), self::delivery(),
        );
        self::assertSame(1, $out['visible_aggregate_lines']);
        self::assertSame(0, $out['suppressed_bucket_count']);
        self::assertSame([], $out['review_issue_codes']);
        self::assertSame(0, $out['review_issue_count']);
        self::assertFalse($out['csv_text_returned']);
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_read_report']);
        self::assertFalse($out['can_deliver']);

        $empty = (new SyntheticReportDeliveryCompositeInspector())->inspect(
            [], 3, self::schedule(), self::delivery(),
        );
        self::assertSame(0, $empty['visible_aggregate_lines']);
        self::assertSame(0, $empty['suppressed_bucket_count']);
        self::assertFalse($empty['can_export']);
    }

    public function test_invalid_hidden_row_and_recipient_payloads_are_both_denied(): void
    {
        $goodRows = self::rows();
        $schedule = self::schedule();
        $delivery = self::delivery();
        $bad = [
            [null, 3, $schedule, $delivery],
            ['rows', 3, $schedule, $delivery],
            [$goodRows, '3', $schedule, $delivery],
            [$goodRows, 1, $schedule, $delivery],
            [$goodRows, 6, $schedule, $delivery],
            [[['stage' => 'won', 'claimed_value_cents' => 5, 'email' => 'private@example.test']], 3, $schedule, $delivery],
            [[['stage' => 'won', 'claimed_value_cents' => '=SUM(A1)']], 3, $schedule, $delivery],
            [$goodRows, 3, null, $delivery],
            [$goodRows, 3, $schedule, null],
            [$goodRows, 3, array_merge($schedule, ['tenant' => 'private@example.test']), $delivery],
            [$goodRows, 3, $schedule, array_merge($delivery, ['email' => 'private@example.test'])],
            [$goodRows, 3, array_merge($schedule, ['requested_fields' => ['email']]), $delivery],
            [$goodRows, 3, $schedule, array_merge($delivery, ['at_utc' => '2026-10-01T10:00:00Z'])],
            [$goodRows, 3, $schedule, array_merge($delivery, ['report_state' => 'paid'])],
        ];
        foreach ($bad as [$rows, $k, $scheduled, $delivered]) {
            try {
                (new SyntheticReportDeliveryCompositeInspector())
                    ->inspect($rows, $k, $scheduled, $delivered);
                self::fail('Untrusted report package or recipient should fail.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Pacote sintetico de relatorio invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_closed_metadata_schema_carries_no_data_payload_or_export_link(): void
    {
        $out = (new SyntheticReportDeliveryCompositeInspector())->inspect(
            self::rows(), 3, self::schedule(), self::delivery(),
        );
        self::assertSame([
            'csv_format', 'csv_text_returned', 'visible_aggregate_lines',
            'suppressed_bucket_count', 'requested_field_count',
            'claimed_report_state', 'recipient_access_revoked_claim',
            'policy_revision_changed_claim', 'review_issue_codes',
            'review_issue_count', 'recipient_blocker_codes',
            'recognized_revenue_cents', 'report_data_verified',
            'tenant_scope_verified', 'privacy_policy_verified',
            'recipient_permissions_verified', 'transport_verified',
            'informational_only', 'authorized', 'can_read_report',
            'can_export', 'can_generate_link', 'can_download',
            'can_deliver', 'can_notify', 'can_persist',
        ], array_keys($out));
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'customer', 'email', 'phone', 'lead_id',
            'opportunity_id', 'task_id', 'event_reference', 'actor_id',
            'recipient_id', 'csv_preview_utf8', 'download_link',
            'file_path', 'report_data', 'attachment', 'token',
        ] as $key) {
            self::assertStringNotContainsString('"'.$key.'"', $json);
        }
    }
}
