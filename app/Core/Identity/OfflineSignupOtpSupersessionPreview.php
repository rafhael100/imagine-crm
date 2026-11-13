<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-245: only a read-only claim about OTP resend supersession.
 *
 * Does NOT prove either record belongs to the same contact or channel.
 * Trusting caller-provided "latest" is unsafe without an atomic durable
 * latest-challenge pointer and independent identity/tenant controls.
 */
final class OfflineSignupOtpSupersessionPreview
{
    /**
     * @param mixed $presented Four-field original challenge record
     * @param mixed $latest Four-field caller-claimed latest record
     * @param mixed $now Synthetic time
     * @return array<string,mixed> Fixed diagnostic codes, zero authority
     */
    public function inspect(mixed $presented, mixed $latest, mixed $now): array
    {
        self::validate($presented);
        self::validate($latest);

        if (!is_int($now) || $now < $latest['issued_at']
            || $now < $presented['issued_at']
            || $latest['issued_at'] < $presented['issued_at']
            || ($presented['nonce'] === $latest['nonce']
                && ($presented['issued_at'] !== $latest['issued_at']
                    || $presented['digest'] !== $latest['digest']))) {
            throw new InvalidArgumentException('Revisao de reenvio OTP invalida.');
        }

        $sameRecord = hash_equals($presented['nonce'], $latest['nonce'])
            && hash_equals($presented['digest'], $latest['digest'])
            && $presented['issued_at'] === $latest['issued_at'];

        $code = match (true) {
            $now >= $latest['expires_at'] => 'latest_expired_claim',
            !$sameRecord => 'earlier_challenge_superseded_claim',
            default => 'latest_challenge_claim_only',
        };

        return [
            'reason_code' => $code,
            'illustrative_validity_seconds' => OfflineSignupOtpChallenge::VALIDITY_SECONDS,
            'latest_pointer_durable_verified' => false,
            'challenge_mac_verified' => false,
            'same_contact_verified' => false,
            'same_channel_verified' => false,
            'atomic_invalidation_verified' => false,
            'informational_only' => true,
            'can_consume' => false,
            'can_verify_real_code' => false,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_persist' => false,
        ];
    }

    private static function validate(mixed $record): void
    {
        if (!is_array($record) || count($record) !== 4
            || !array_key_exists('nonce', $record)
            || !array_key_exists('digest', $record)
            || !array_key_exists('issued_at', $record)
            || !array_key_exists('expires_at', $record)
            || !is_string($record['nonce'])
            || preg_match('/\A[a-f0-9]{32}\z/D', $record['nonce']) !== 1
            || !is_string($record['digest'])
            || preg_match('/\A[a-f0-9]{64}\z/D', $record['digest']) !== 1
            || !is_int($record['issued_at']) || $record['issued_at'] < 0
            || $record['issued_at'] > PHP_INT_MAX - OfflineSignupOtpChallenge::VALIDITY_SECONDS
            || !is_int($record['expires_at'])
            || $record['expires_at'] !== $record['issued_at'] + OfflineSignupOtpChallenge::VALIDITY_SECONDS) {
            throw new InvalidArgumentException('Revisao de reenvio OTP invalida.');
        }
    }
}
