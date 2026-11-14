<?php
declare(strict_types=1);

namespace App\Core\Leads;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Deterministic, NON-OPERATIONAL consent & nurture lifecycle model.
 *
 * Only anonymous synthetic events; no transport, storage, jobs, PII or account
 * authorization. "Lab eligibility" is NOT permission to contact a person.
 * Both the report request and marketing subscription are independent.
 */
final class SyntheticCalculatorNurtureLifecycle
{
    private const TYPES = [
        'report_requested',
        'marketing_requested',
        'marketing_email_confirmed',
        'marketing_revoked',
        'report_delivery_claimed',
    ];
    private const MAX_EVENTS = 24;
    private const CONFIRM_SECONDS = 7 * 24 * 3600;

    /**
     * @param list<array{event_id:string,type:string,occurred_at:string}> $events
     * @return array<string,mixed>
     */
    public static function evaluate(array $events, string $nowUtc): array
    {
        if (!array_is_list($events) || count($events) > self::MAX_EVENTS) {
            throw new InvalidArgumentException('Invalid synthetic event sequence.');
        }
        $now = self::strictUtc($nowUtc);
        $seen = [];
        $lastTimestamp = null;
        $reportRequested = false;
        $reportClaimedDelivered = false;
        $marketingRequestedAt = null;
        $marketingConfirmed = false;
        $revoked = false;
        $marketingCycle = 0;

        foreach ($events as $event) {
            if (!is_array($event) || array_keys($event) !== ['event_id', 'type', 'occurred_at'] ||
                !is_string($event['event_id']) ||
                !preg_match('/\Aevt_[a-f0-9]{16}\z/D', $event['event_id']) ||
                isset($seen[$event['event_id']]) ||
                !is_string($event['type']) || !in_array($event['type'], self::TYPES, true) ||
                !is_string($event['occurred_at'])) {
                throw new InvalidArgumentException('Malformed, duplicate or unsupported synthetic event.');
            }
            $seen[$event['event_id']] = true;
            $ts = self::strictUtc($event['occurred_at']);
            if ($ts > $now || ($lastTimestamp !== null && $ts < $lastTimestamp)) {
                throw new InvalidArgumentException('Out-of-order or future synthetic consent event.');
            }
            $lastTimestamp = $ts;
            switch ($event['type']) {
                case 'report_requested':
                    if ($reportRequested) throw new InvalidArgumentException('Duplicate report request.');
                    $reportRequested = true;
                    break;
                case 'report_delivery_claimed':
                    if (!$reportRequested || $reportClaimedDelivered) {
                        throw new InvalidArgumentException('Invalid report delivery claim.');
                    }
                    $reportClaimedDelivered = true;
                    break;
                case 'marketing_requested':
                    // After revocation an entirely NEW request starts a new lab cycle.
                    if ($marketingRequestedAt !== null || $marketingConfirmed) {
                        throw new InvalidArgumentException('A request is already pending or confirmed.');
                    }
                    $marketingRequestedAt = $ts;
                    $marketingConfirmed = false;
                    $revoked = false;
                    ++$marketingCycle;
                    break;
                case 'marketing_email_confirmed':
                    if ($marketingRequestedAt === null || $marketingConfirmed ||
                        $ts - $marketingRequestedAt > self::CONFIRM_SECONDS ||
                        $revoked) {
                        throw new InvalidArgumentException('Unsolicited, reused or expired confirmation.');
                    }
                    $marketingConfirmed = true;
                    $marketingRequestedAt = null;
                    break;
                case 'marketing_revoked':
                    // Revocation may happen while pending or confirmed, but not
                    // when there never was a marketing intent.
                    if (($marketingRequestedAt === null && !$marketingConfirmed) || $revoked) {
                        throw new InvalidArgumentException('Unsolicited marketing revocation.');
                    }
                    $marketingRequestedAt = null;
                    $marketingConfirmed = false;
                    $revoked = true;
                    break;
            }
        }

        $pendingExpired = $marketingRequestedAt !== null &&
            $now - $marketingRequestedAt > self::CONFIRM_SECONDS;
        $state = $revoked ? 'revoked' :
            ($marketingConfirmed ? 'synthetic_confirmed_unverified' :
            ($marketingRequestedAt !== null ? ($pendingExpired ? 'expired_unconfirmed' : 'pending_unverified') :
            'not_requested'));
        $educationalPlan = $marketingConfirmed && !$revoked ? [
            ['day_offset' => 0, 'kind' => 'welcome_and_budget_guide'],
            ['day_offset' => 3, 'kind' => 'compare_channels'],
            ['day_offset' => 7, 'kind' => 'campaign_planning_checklist'],
            ['day_offset' => 14, 'kind' => 'optional_consultation_invitation'],
        ] : [];

        return [
            'schema' => 'synthetic.calculator.nurture.lifecycle.v1',
            'mode' => 'OFFLINE_NO_NETWORK_NO_PII',
            'report_requested_claimed' => $reportRequested,
            'report_delivery_claimed' => $reportClaimedDelivered,
            'report_delivery_proven' => false,
            'marketing_state' => $state,
            'synthetic_marketing_cycles' => $marketingCycle,
            'opt_in_proven' => false,
            'double_opt_in_proven' => false,
            'revoke_claimed' => $revoked,
            'candidate_educational_steps' => $educationalPlan,
            'can_send_report' => false,
            'can_send_marketing' => false,
            'can_persist_lead' => false,
            'can_queue_jobs' => false,
            'can_bill' => false,
        ];
    }

    private static function strictUtc(string $date): int
    {
        if (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D', $date)) {
            throw new InvalidArgumentException('Invalid UTC timestamp in consent lab.');
        }
        $utc = new DateTimeZone('UTC');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $date, $utc);
        if ($parsed === false || $parsed->format('Y-m-d\TH:i:s\Z') !== $date) {
            throw new InvalidArgumentException('Impossible UTC timestamp in consent lab.');
        }
        return $parsed->getTimestamp();
    }
}
