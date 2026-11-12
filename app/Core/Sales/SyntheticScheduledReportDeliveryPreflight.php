<?php

declare(strict_types=1);

namespace App\Core\Sales;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R27/VEN-T18: fictional scheduled report delivery preflight.
 *
 * A granted permission at scheduling time does not guarantee access
 * at delivery time. This model does not authenticate users, check real
 * memberships, generate links, run jobs, read reports or send messages.
 */
final class SyntheticScheduledReportDeliveryPreflight
{
    private const REPORT_STATES = ['ready', 'revoked', 'expired', 'missing'];
    private const REPORT_FIELDS = ['stage', 'score', 'claimed_value_cents'];

    /**
     * @param mixed $schedule array{
     *   at_utc:string,recipient_access:bool,policy_revision:int,
     *   requested_fields:list<string>
     * }
     * @param mixed $delivery array{
     *   at_utc:string,recipient_access:bool,policy_revision:int,
     *   report_state:string
     * }
     * @return array<string,mixed> Deny-only snapshot without report content
     */
    public function inspect(mixed $schedule, mixed $delivery): array
    {
        if (!is_array($schedule) || !is_array($delivery)
            || count($schedule) !== 4 || count($delivery) !== 4) {
            self::invalid();
        }
        $scheduleKeys = array_keys($schedule);
        $deliveryKeys = array_keys($delivery);
        sort($scheduleKeys);
        sort($deliveryKeys);
        if ($scheduleKeys !== [
            'at_utc', 'policy_revision', 'recipient_access', 'requested_fields',
        ] || $deliveryKeys !== [
            'at_utc', 'policy_revision', 'recipient_access', 'report_state',
        ] || !is_string($schedule['at_utc'])
            || !self::canonicalUtc($schedule['at_utc'])
            || !is_string($delivery['at_utc'])
            || !self::canonicalUtc($delivery['at_utc'])
            || strcmp($delivery['at_utc'], $schedule['at_utc']) < 0
            || !is_bool($schedule['recipient_access'])
            || !is_bool($delivery['recipient_access'])
            || !is_int($schedule['policy_revision'])
            || !is_int($delivery['policy_revision'])
            || $schedule['policy_revision'] < 1
            || $schedule['policy_revision'] > 10000
            || $delivery['policy_revision'] < 1
            || $delivery['policy_revision'] > 10000
            || !is_string($delivery['report_state'])
            || !in_array($delivery['report_state'], self::REPORT_STATES, true)
            || !is_array($schedule['requested_fields'])
            || !array_is_list($schedule['requested_fields'])
            || count($schedule['requested_fields']) < 1
            || count($schedule['requested_fields']) > count(self::REPORT_FIELDS)) {
            self::invalid();
        }

        $fieldsSeen = [];
        foreach ($schedule['requested_fields'] as $field) {
            if (!is_string($field)
                || !in_array($field, self::REPORT_FIELDS, true)
                || isset($fieldsSeen[$field])) {
                // In particular: NEVER let customer names, phone, email,
                // notes or other sensitive field requests enter this preview.
                self::invalid();
            }
            $fieldsSeen[$field] = true;
        }

        $revoked = $schedule['recipient_access']
            && !$delivery['recipient_access'];
        $policyChanged = $schedule['policy_revision']
            !== $delivery['policy_revision'];

        $blockers = [];
        if (!$schedule['recipient_access']) {
            $blockers[] = 'access_not_claimed_at_schedule';
        }
        if (!$delivery['recipient_access']) {
            $blockers[] = 'access_not_claimed_at_delivery';
        }
        if ($policyChanged) {
            $blockers[] = 'policy_revision_changed';
        }
        if ($delivery['report_state'] !== 'ready') {
            $blockers[] = 'report_not_ready_at_delivery';
        }

        // Independent privileges and provenance must be checked by a real
        // backend at the moment of delivery. Even all positive caller claims
        // cannot remove these non-bypassable blockers.
        $blockers[] = 'recipient_identity_unverified';
        $blockers[] = 'tenant_scope_unverified';
        $blockers[] = 'recipient_permissions_unverified';
        $blockers[] = 'delivery_channel_unverified';

        return [
            'requested_field_count' => count($fieldsSeen),
            'claimed_report_state' => $delivery['report_state'],
            'access_revoked_since_schedule' => $revoked,
            'policy_revision_changed' => $policyChanged,
            'blocker_codes' => $blockers,
            'blocker_count' => count($blockers),
            'revalidation_verified' => false,
            'recipient_identity_verified' => false,
            'tenant_scope_verified' => false,
            'recipient_permissions_verified' => false,
            'transport_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_report' => false,
            'can_export' => false,
            'can_generate_link' => false,
            'can_deliver' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function canonicalUtc(string $value): bool
    {
        if (preg_match(
            '/\A20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D',
            $value,
        ) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'),
        );

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Entrega sintetica de relatorio invalida.');
    }
}
