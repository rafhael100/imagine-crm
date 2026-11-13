<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-241: assess synthetic OTP budget snapshots without storing anything.
 *
 * No durable counters, atomic locks, network, endpoint, mailer, provider,
 * ownership proof, authorization or enrollment exist here. A favorable
 * observation can NEVER be used as a permit to issue or verify a real OTP.
 */
final class OfflineSignupOtpAbuseBudgetPreview
{
    public const WINDOW_SECONDS = OfflineSignupOtpChallenge::VALIDITY_SECONDS;

    /** @var array<string,array{contact:int,origin:int,cooldown:int}> */
    private const EXAMPLE_LIMITS = [
        'issue' => ['contact' => 3, 'origin' => 20, 'cooldown' => 60],
        'verify' => ['contact' => OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS,
            'origin' => 60, 'cooldown' => 10],
    ];

    /**
     * @param mixed $action Exactly issue or verify, not a transport action
     * @param mixed $opaqueContact HMAC digest of contact, not plain email/phone
     * @param mixed $opaqueOrigin Illustrative HMAC digest of request origin
     * @param mixed $now Integer Unix time of the simulated observation
     * @param mixed $contactSnapshot Fixed three-field untrusted counter claim
     * @param mixed $originSnapshot Fixed three-field untrusted counter claim
     * @return array<string,bool|int|string> Fixed non-authoritative reasons
     */
    public function inspect(
        mixed $action,
        #[\SensitiveParameter] mixed $opaqueContact,
        #[\SensitiveParameter] mixed $opaqueOrigin,
        mixed $now,
        mixed $contactSnapshot,
        mixed $originSnapshot,
    ): array {
        if (!is_string($action) || !array_key_exists($action, self::EXAMPLE_LIMITS)
            || !self::digest($opaqueContact) || !self::digest($opaqueOrigin)
            || hash_equals($opaqueContact, $opaqueOrigin)
            || !is_int($now) || $now < 0) {
            self::invalid();
        }

        $contact = self::counter($contactSnapshot, $now);
        $origin = self::counter($originSnapshot, $now);
        $limits = self::EXAMPLE_LIMITS[$action];

        $reason = match (true) {
            $contact['count'] >= $limits['contact'] => 'sample_contact_budget_exhausted',
            $origin['count'] >= $limits['origin'] => 'sample_origin_budget_exhausted',
            ($contact['count'] > 0
                && $now - $contact['latest'] < $limits['cooldown'])
                || ($origin['count'] > 0
                && $now - $origin['latest'] < $limits['cooldown'])
                => 'sample_cooldown_active',
            default => 'sample_within_illustrative_budget',
        };

        return [
            'reason_code' => $reason,
            'action' => $action,
            'example_window_seconds' => self::WINDOW_SECONDS,
            'example_contact_limit' => $limits['contact'],
            'example_origin_limit' => $limits['origin'],
            'example_cooldown_seconds' => $limits['cooldown'],
            'informational_only' => true,
            'durable_counter_verified' => false,
            'atomic_reservation_verified' => false,
            'origin_authenticity_verified' => false,
            'contact_ownership_verified' => false,
            'abuse_policy_approved' => false,
            'can_send' => false,
            'can_verify_real_code' => false,
            'can_queue' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_persist' => false,
        ];
    }

    private static function digest(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[a-f0-9]{64}\z/D', $value) === 1;
    }

    /**
     * Strict snapshot shape is a CLAIM, never proof of a durable rate-limit.
     *
     * @return array{count:int,latest:int}
     */
    private static function counter(mixed $claim, int $now): array
    {
        if (!is_array($claim) || count($claim) !== 3
            || !array_key_exists('window_start', $claim)
            || !array_key_exists('events', $claim)
            || !array_key_exists('latest_at', $claim)
            || !is_int($claim['window_start']) || $claim['window_start'] < 0
            || $claim['window_start'] > $now
            || !is_int($claim['events']) || $claim['events'] < 0
            || $claim['events'] > 100000
            || !is_int($claim['latest_at'])
            || $claim['latest_at'] < $claim['window_start']
            || $claim['latest_at'] > $now) {
            self::invalid();
        }

        // Exact 900s boundary resets the example window. Never add a TTL
        // to the provided timestamp: this avoids integer overflow.
        if ($now - $claim['window_start'] >= self::WINDOW_SECONDS) {
            return ['count' => 0, 'latest' => $now];
        }

        return ['count' => $claim['events'], 'latest' => $claim['latest_at']];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao local de limite OTP invalida.');
    }
}
