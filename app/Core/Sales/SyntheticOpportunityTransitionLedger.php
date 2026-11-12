<?php

declare(strict_types=1);

namespace App\Core\Sales;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R02/R03/R04/R05/R06: pure review of invented opportunity transitions.
 *
 * The revision is only an in-memory monotonic check, NOT a database CAS,
 * lock, proof of a confirmed sale or a real authorization decision.
 */
final class SyntheticOpportunityTransitionLedger
{
    private const MAX_EVENTS = 24;
    private const ACTIONS = ['create', 'move', 'win', 'lose', 'reopen'];
    private const LOSS_REASONS = ['price', 'timing', 'competition', 'no_response', 'other'];
    private const REOPEN_REASONS = ['correction', 'customer_returned', 'new_context'];

    /**
     * @param mixed $events Strict list<array{
     *   revision:int,action:string,stage:int,confirmed:bool,
     *   loss_reason_code:?string,reopen_reason_code:?string,
     *   occurred_at_utc:string
     * }>
     * @return array<string,mixed> Deny-only projection, no event IDs or PII
     */
    public function inspect(mixed $events): array
    {
        if (!is_array($events) || !array_is_list($events)
            || count($events) > self::MAX_EVENTS) {
            self::invalid();
        }

        $status = null;
        $stage = null;
        $previousUtc = null;
        $moves = 0;
        $wins = 0;
        $losses = 0;
        $reopenings = 0;
        $reasons = array_fill_keys(self::LOSS_REASONS, 0);

        foreach ($events as $index => $event) {
            if (!is_array($event) || count($event) !== 7) {
                self::invalid();
            }
            $keys = array_keys($event);
            sort($keys);
            if ($keys !== [
                'action', 'confirmed', 'loss_reason_code',
                'occurred_at_utc', 'reopen_reason_code', 'revision', 'stage',
            ]
                || !is_int($event['revision'])
                || $event['revision'] !== $index + 1
                || !is_string($event['action'])
                || !in_array($event['action'], self::ACTIONS, true)
                || !is_int($event['stage'])
                || $event['stage'] < 0
                || $event['stage'] > 3
                || !is_bool($event['confirmed'])
                || !is_string($event['occurred_at_utc'])
                || !self::canonicalUtc($event['occurred_at_utc'])
                || ($previousUtc !== null
                    && strcmp($event['occurred_at_utc'], $previousUtc) <= 0)) {
                self::invalid();
            }

            $action = $event['action'];
            $lossReason = $event['loss_reason_code'];
            $reopenReason = $event['reopen_reason_code'];
            $confirmed = $event['confirmed'];
            $requestedStage = $event['stage'];

            if ($action === 'lose') {
                if (!is_string($lossReason)
                    || !in_array($lossReason, self::LOSS_REASONS, true)) {
                    self::invalid();
                }
            } elseif ($lossReason !== null) {
                self::invalid();
            }

            if ($action === 'reopen') {
                if (!is_string($reopenReason)
                    || !in_array($reopenReason, self::REOPEN_REASONS, true)) {
                    self::invalid();
                }
            } elseif ($reopenReason !== null) {
                self::invalid();
            }

            // A closed outcome requires a distinct invented confirmation bit.
            // It is not proof of a human or an authorized external command.
            if ($confirmed !== ($action === 'win' || $action === 'lose')) {
                self::invalid();
            }

            if ($action === 'create') {
                if ($index !== 0 || $status !== null) {
                    self::invalid();
                }
                $status = 'open';
                $stage = $requestedStage;
            } elseif ($action === 'move') {
                if ($status !== 'open' || $requestedStage === $stage) {
                    self::invalid();
                }
                $stage = $requestedStage;
                ++$moves;
            } elseif ($action === 'win' || $action === 'lose') {
                if ($status !== 'open' || $requestedStage !== $stage) {
                    self::invalid();
                }
                $status = $action === 'win' ? 'won' : 'lost';
                if ($action === 'win') {
                    ++$wins;
                } else {
                    ++$losses;
                    ++$reasons[$lossReason];
                }
            } elseif ($action === 'reopen') {
                if (($status !== 'won' && $status !== 'lost')
                    || $requestedStage !== $stage) {
                    self::invalid();
                }
                $status = 'open';
                ++$reopenings;
            } else {
                self::invalid();
            }

            $previousUtc = $event['occurred_at_utc'];
        }

        // An invented "won" event, including a repeated win after reopening,
        // is never counted as verified sale, target attainment or revenue.
        return [
            'event_count' => count($events),
            'latest_revision' => count($events),
            'latest_stage' => $stage,
            'latest_claimed_status' => $status,
            'claimed_moves' => $moves,
            'claimed_wins' => $wins,
            'claimed_losses' => $losses,
            'claimed_reopenings' => $reopenings,
            'claimed_loss_reasons' => $reasons,
            'recognized_wins' => 0,
            'recognized_revenue_cents' => 0,
            'history_verified' => false,
            'tenant_policy_verified' => false,
            'concurrency_verified' => false,
            'confirmation_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_move' => false,
            'can_mark_won' => false,
            'can_mark_lost' => false,
            'can_reopen' => false,
            'can_update_targets' => false,
            'can_invoice' => false,
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

        $utc = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            $value,
            new DateTimeZone('UTC'),
        );

        return $utc !== false && $utc->format('Y-m-d\TH:i:s\Z') === $value;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Historico sintetico de oportunidade invalido.');
    }
}
