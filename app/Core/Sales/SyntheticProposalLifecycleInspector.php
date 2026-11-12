<?php

declare(strict_types=1);

namespace App\Core\Sales;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R11/R14/R15/R16/R17: deterministic review of hypothetical proposal
 * events. No command execution, authorization, verified acceptance or storage.
 *
 * The word "accepted" remains an untrusted claim. Sending, signatures,
 * billing and winning opportunities are never enabled by this reducer.
 */
final class SyntheticProposalLifecycleInspector
{
    private const MAX_EVENTS = 24;
    private const MAX_VERSIONS = 8;

    private const ACTIONS = [
        'create', 'send', 'view', 'accept', 'decline', 'expire', 'revise',
    ];

    /**
     * @param mixed $events Strict list<array{
     *   version:int,action:string,occurred_at_utc:string,expires_at_utc:?string
     * }>
     * @return array<string, mixed> A fixed informational report without PII
     */
    public function inspect(mixed $events): array
    {
        if (!is_array($events) || !array_is_list($events)
            || count($events) > self::MAX_EVENTS) {
            self::invalid();
        }

        $versions = [];
        $deadlines = [];
        $lastInstant = null;
        $current = 0;

        foreach ($events as $index => $event) {
            if (!is_array($event) || count($event) !== 4) {
                self::invalid();
            }

            $keys = array_keys($event);
            sort($keys);
            if ($keys !== ['action', 'expires_at_utc', 'occurred_at_utc', 'version']
                || !is_int($event['version'])
                || $event['version'] < 1
                || $event['version'] > self::MAX_VERSIONS
                || !is_string($event['action'])
                || !in_array($event['action'], self::ACTIONS, true)
                || !is_string($event['occurred_at_utc'])
                || !self::canonicalUtc($event['occurred_at_utc'])
                || ($lastInstant !== null
                    && strcmp($event['occurred_at_utc'], $lastInstant) <= 0)) {
                self::invalid();
            }

            $action = $event['action'];
            $version = $event['version'];
            $at = $event['occurred_at_utc'];
            $expiry = $event['expires_at_utc'];

            if ($action === 'send') {
                if (!is_string($expiry)) {
                    self::invalid();
                }
                try {
                    // Reuse the same date-window guard as the proposal view.
                    new SyntheticProposalValidityPreview($at, $expiry, $at);
                } catch (InvalidArgumentException) {
                    self::invalid();
                }
            } elseif ($expiry !== null) {
                self::invalid();
            }

            if ($action === 'create') {
                if ($index !== 0 || $version !== 1 || $current !== 0) {
                    self::invalid();
                }
                $current = 1;
                $versions[1] = ['version' => 1, 'status' => 'draft', 'viewed' => false];
            } elseif ($action === 'revise') {
                if ($current === 0
                    || $version !== $current + 1
                    || $current >= self::MAX_VERSIONS
                    || $versions[$current]['status'] === 'draft') {
                    self::invalid();
                }
                $current = $version;
                $versions[$current] = [
                    'version' => $version,
                    'status' => 'draft',
                    'viewed' => false,
                ];
            } else {
                if ($current === 0 || $version !== $current) {
                    self::invalid();
                }

                $status = $versions[$current]['status'];
                if ($action === 'send') {
                    if ($status !== 'draft') {
                        self::invalid();
                    }
                    $versions[$current]['status'] = 'sent';
                    $deadlines[$current] = $expiry;
                } elseif ($action === 'view') {
                    if ($status !== 'sent' || strcmp($at, $deadlines[$current]) >= 0) {
                        self::invalid();
                    }
                    $versions[$current]['viewed'] = true;
                } elseif ($action === 'accept' || $action === 'decline') {
                    if ($status !== 'sent' || strcmp($at, $deadlines[$current]) >= 0) {
                        self::invalid();
                    }
                    $versions[$current]['status'] = $action === 'accept'
                        ? 'accepted' : 'declined';
                } elseif ($action === 'expire') {
                    if ($status !== 'sent' || strcmp($at, $deadlines[$current]) < 0) {
                        self::invalid();
                    }
                    $versions[$current]['status'] = 'expired';
                } else {
                    self::invalid();
                }
            }

            $lastInstant = $at;
        }

        // Use the existing version reducer, so status semantics and the
        // no-acceptance-transfer rule cannot drift across two features.
        $view = (new SyntheticProposalVersionReview())->inspect(array_values($versions));

        return [
            'event_count' => count($events),
            'versions_seen' => $view['versions_seen'],
            'versions' => array_values($versions),
            'latest_version' => $view['latest_version'],
            'latest_claimed_status' => $view['latest_claimed_status'],
            'latest_viewed' => $view['latest_viewed'],
            'claimed_status_counts' => $view['claimed_status_counts'],
            'prior_claimed_accepted_versions' => $view['prior_claimed_accepted_versions'],
            'viewed_without_claimed_acceptance' => $view['viewed_without_claimed_acceptance'],
            'prior_acceptance_applies_to_latest' => false,
            'viewed_equals_acceptance' => false,
            'event_source_verified' => false,
            'approval_policy_verified' => false,
            'acceptance_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_send' => false,
            'can_accept' => false,
            'can_sign' => false,
            'can_charge' => false,
            'can_mark_won' => false,
            'can_persist' => false,
        ];
    }

    private static function canonicalUtc(string $utc): bool
    {
        if (preg_match(
            '/\A20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D',
            $utc,
        ) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            $utc,
            new DateTimeZone('UTC'),
        );

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $utc;
    }

    private static function invalid(): never
    {
        // No user-controlled event, timestamp, version or tenant is reflected.
        throw new InvalidArgumentException('Ciclo sintetico de proposta invalido.');
    }
}
