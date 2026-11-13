<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-226 — isolated signup verification primitive for a future CRM trial.
 *
 * Transport, binding-to-person, throttle, durable attempt counter, atomic
 * consumption, tenant/CNPJ eligibility, and actual signup remain UNBUILT.
 * In particular a matching candidate NEVER authorizes login or registration.
 */
final class OfflineSignupOtpChallenge
{
    public const CODE_DIGITS = 6;
    public const VALIDITY_SECONDS = 900;
    public const MAX_CANDIDATE_ATTEMPTS = 5;

    /**
     * Create a cryptographically random challenge for fictional offline tests.
     * The code is deliberately separated from the verifier's stored record.
     * Do NOT persist or log the transient delivery_code in a real system.
     *
     * @return array<string,mixed> Ephemeral code and non-authorizing record
     */
    public function issue(
        #[\SensitiveParameter] string $key,
        string $opaqueBinding,
        string $channel,
        int $issuedAt,
    ): array {
        self::validateContext($key, $opaqueBinding, $channel);
        if ($issuedAt < 0 || $issuedAt > PHP_INT_MAX - self::VALIDITY_SECONDS) {
            self::invalid();
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $nonce = bin2hex(random_bytes(16));
        $expiry = $issuedAt + self::VALIDITY_SECONDS;
        $digest = self::mac($key, $opaqueBinding, $channel, $nonce, $issuedAt, $expiry, $code);

        return [
            'challenge' => [
                'nonce' => $nonce,
                'digest' => $digest,
                'issued_at' => $issuedAt,
                'expires_at' => $expiry,
            ],
            'delivery_code' => $code,
            'informational_only' => true,
            'transport_configured' => false,
            'binding_verified' => false,
            'attempt_store_configured' => false,
            'atomic_replay_prevention_configured' => false,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_grant_access' => false,
        ];
    }

    /**
     * Evaluate a caller-supplied, untrusted candidate in memory only.
     * attemptsUsed and alreadyConsumed MUST be read and updated atomically
     * by a separately reviewed durable store before this could be a factor.
     *
     * @param mixed $challenge Four-field verifier record, no delivery_code
     * @param mixed $candidate Exactly six ASCII decimal digits
     * @param mixed $attemptsUsed Illustrative count 0..5
     * @param mixed $alreadyConsumed Illustrative replay state
     * @return array<string,mixed> Read-only matching observation, not authority
     */
    public function inspect(
        mixed $challenge,
        #[\SensitiveParameter] mixed $candidate,
        #[\SensitiveParameter] string $key,
        string $opaqueBinding,
        string $channel,
        int $observedAt,
        mixed $attemptsUsed,
        mixed $alreadyConsumed,
    ): array {
        self::validateContext($key, $opaqueBinding, $channel);
        if (!is_array($challenge) || count($challenge) !== 4
            || !array_key_exists('nonce', $challenge)
            || !array_key_exists('digest', $challenge)
            || !array_key_exists('issued_at', $challenge)
            || !array_key_exists('expires_at', $challenge)
            || !is_string($challenge['nonce'])
            || preg_match('/\A[a-f0-9]{32}\z/D', $challenge['nonce']) !== 1
            || !is_string($challenge['digest'])
            || preg_match('/\A[a-f0-9]{64}\z/D', $challenge['digest']) !== 1
            || !is_int($challenge['issued_at'])
            || !is_int($challenge['expires_at'])
            || $challenge['issued_at'] < 0
            || $challenge['issued_at'] > PHP_INT_MAX - self::VALIDITY_SECONDS
            || $challenge['expires_at'] !== $challenge['issued_at'] + self::VALIDITY_SECONDS
            || !is_string($candidate)
            || preg_match('/\A[0-9]{6}\z/D', $candidate) !== 1
            || $observedAt < 0
            || !is_int($attemptsUsed)
            || $attemptsUsed < 0 || $attemptsUsed > self::MAX_CANDIDATE_ATTEMPTS
            || !is_bool($alreadyConsumed)) {
            self::invalid();
        }

        $digest = self::mac(
            $key, $opaqueBinding, $channel, $challenge['nonce'],
            $challenge['issued_at'], $challenge['expires_at'], $candidate,
        );
        $matches = hash_equals($challenge['digest'], $digest);
        $status = match (true) {
            $alreadyConsumed => 'already_consumed_claim',
            $attemptsUsed >= self::MAX_CANDIDATE_ATTEMPTS => 'attempt_budget_exhausted_claim',
            $observedAt < $challenge['issued_at'] => 'before_issue',
            $observedAt >= $challenge['expires_at'] => 'expired',
            $matches => 'candidate_matches_only',
            default => 'candidate_mismatch',
        };

        return [
            'status_code' => $status,
            'candidate_matches' => $status === 'candidate_matches_only',
            'validity_seconds' => self::VALIDITY_SECONDS,
            'candidate_attempt_budget' => self::MAX_CANDIDATE_ATTEMPTS,
            'remaining_seconds' => $observedAt >= $challenge['expires_at']
                ? 0
                : max(0, $challenge['expires_at'] - max($observedAt, $challenge['issued_at'])),
            'informational_only' => true,
            'binding_verified' => false,
            'attempt_store_verified' => false,
            'atomic_replay_prevention_verified' => false,
            'transport_verified' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_grant_access' => false,
            'can_consume_challenge' => false,
            'can_send' => false,
        ];
    }

    private static function mac(
        #[\SensitiveParameter] string $key,
        string $binding,
        string $channel,
        string $nonce,
        int $issued,
        int $expiry,
        #[\SensitiveParameter] string $code,
    ): string {
        $message = implode('|', [
            'imagine.crm.signup_otp.v1', $binding, $channel,
            $nonce, (string) $issued, (string) $expiry, $code,
        ]);

        return hash_hmac('sha256', $message, $key);
    }

    private static function validateContext(
        #[\SensitiveParameter] string $key,
        string $opaqueBinding,
        string $channel,
    ): void {
        if (strlen($key) < 32 || strlen($key) > 128
            || preg_match('/\A[a-f0-9]{64}\z/D', $opaqueBinding) !== 1
            || !in_array($channel, ['email', 'phone'], true)) {
            self::invalid();
        }
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Desafio OTP local invalido.');
    }
}
