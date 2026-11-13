<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Identity\OfflineSignupOtpChallenge;
use InvalidArgumentException;

/**
 * F3-246: functional OTP lifecycle sandbox, loaded only by Composer autoload-dev.
 *
 * State exists ONLY in this PHP object. It uses the real cryptographic Core
 * challenge but is never a session store, authorization factor, mailer,
 * database, API controller, queue, tenant authority or production adapter.
 *
 * Return values including ephemeral delivery_code belong only inside tests.
 * Do not log, persist, forward or expose these test-only values.
 */
final class SignupOtpLifecycleSandbox
{
    private const ISSUE_CONTACT_LIMIT = 3;
    private const ISSUE_ORIGIN_LIMIT = 20;
    private const VERIFY_ORIGIN_LIMIT = 60;
    private const RESEND_COOLDOWN_SECONDS = 60;

    /** @var array<string,array{challenge:array<string,mixed>,attempts:int,consumed:bool}> */
    private array $latest = [];

    /** @var array<string,array{start:int,count:int,last:int|null}> */
    private array $issuedByContact = [];

    /** @var array<string,array{start:int,count:int,last:int|null}> */
    private array $issuedByOrigin = [];

    /** @var array<string,array{start:int,count:int,last:int|null}> */
    private array $verifiedByOrigin = [];

    private ?int $lastTime = null;
    private OfflineSignupOtpChallenge $core;

    public function __construct(
        #[\SensitiveParameter] private readonly string $key,
    ) {
        if (strlen($key) < 32 || strlen($key) > 128) {
            self::invalid();
        }
        $this->core = new OfflineSignupOtpChallenge();
    }

    /**
     * @return array<string,mixed> Transient test-only code, never a send permit
     */
    public function issue(
        string $contactBinding,
        string $originBinding,
        string $channel,
        int $at,
    ): array {
        $this->checkContext($contactBinding, $originBinding, $channel, $at);
        $scope = self::scope($contactBinding, $channel);

        $this->refresh($this->issuedByContact, $scope, $at);
        $this->refresh($this->issuedByOrigin, $originBinding, $at);
        $contact = $this->issuedByContact[$scope];
        $origin = $this->issuedByOrigin[$originBinding];

        if ($contact['count'] >= self::ISSUE_CONTACT_LIMIT) {
            return self::issueResult('sandbox_contact_budget_exhausted');
        }
        if ($origin['count'] >= self::ISSUE_ORIGIN_LIMIT) {
            return self::issueResult('sandbox_origin_budget_exhausted');
        }
        if ($contact['last'] !== null
            && $at - $contact['last'] < self::RESEND_COOLDOWN_SECONDS) {
            return self::issueResult('sandbox_resend_cooldown');
        }

        // This is real cryptographic Core issuance, but no code leaves memory.
        $challenge = $this->core->issue($this->key, $contactBinding, $channel, $at);
        $this->latest[$scope] = [
            'challenge' => $challenge['challenge'],
            'attempts' => 0,
            'consumed' => false,
        ];
        $this->issuedByContact[$scope]['count']++;
        $this->issuedByContact[$scope]['last'] = $at;
        $this->issuedByOrigin[$originBinding]['count']++;
        $this->issuedByOrigin[$originBinding]['last'] = $at;

        return self::issueResult('sandbox_code_created', $challenge['delivery_code']);
    }

    /**
     * Caller must use the test-only ephemeral code returned by issue().
     * A simulated match only changes this object's memory, NEVER authorizes
     * account creation, access, real verification, delivery or persistence.
     *
     * @return array<string,mixed>
     */
    public function verify(
        string $contactBinding,
        string $originBinding,
        string $channel,
        #[\SensitiveParameter] mixed $candidate,
        int $at,
    ): array {
        $this->checkContext($contactBinding, $originBinding, $channel, $at);
        $scope = self::scope($contactBinding, $channel);

        $this->refresh($this->verifiedByOrigin, $originBinding, $at);
        if ($this->verifiedByOrigin[$originBinding]['count'] >= self::VERIFY_ORIGIN_LIMIT) {
            return self::verificationResult('sandbox_origin_verification_budget_exhausted');
        }
        // Invalid/missing/superseded candidates ALSO consume the origin budget.
        $this->verifiedByOrigin[$originBinding]['count']++;

        if (!isset($this->latest[$scope])) {
            return self::verificationResult('sandbox_no_challenge');
        }
        $state = $this->latest[$scope];
        if ($state['consumed']) {
            return self::verificationResult('sandbox_replay_denied');
        }
        if ($at >= $state['challenge']['expires_at']) {
            return self::verificationResult('sandbox_expired');
        }
        if ($state['attempts'] >= OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS) {
            return self::verificationResult('sandbox_attempt_limit');
        }

        // Every submitted candidate uses one local attempt, even if malformed.
        $attemptsUsed = $state['attempts'];
        $this->latest[$scope]['attempts']++;
        if (!is_string($candidate) || preg_match('/\A[0-9]{6}\z/D', $candidate) !== 1) {
            return self::verificationResult('sandbox_wrong_code');
        }

        $observation = $this->core->inspect(
            $state['challenge'],
            $candidate,
            $this->key,
            $contactBinding,
            $channel,
            $at,
            $attemptsUsed,
            false,
        );
        if ($observation['candidate_matches'] !== true) {
            return self::verificationResult('sandbox_wrong_code');
        }

        // A single-process flag proves only local replay prevention. There
        // is NO durable CAS, lock, atomic OTP consumption or tenant authority.
        $this->latest[$scope]['consumed'] = true;
        return self::verificationResult('sandbox_match_consumed_in_memory');
    }

    /**
     * Exposes only safe counters, never challenge digests or delivery codes.
     *
     * @return array<string,int|bool>
     */
    public function diagnostics(): array
    {
        return [
            'active_scopes_in_memory' => count($this->latest),
            'issue_contact_windows' => count($this->issuedByContact),
            'issue_origin_windows' => count($this->issuedByOrigin),
            'verify_origin_windows' => count($this->verifiedByOrigin),
            'only_one_process' => true,
            'durable_state_verified' => false,
            'network_delivery_verified' => false,
            'can_register' => false,
            'can_authenticate' => false,
        ];
    }

    private function checkContext(
        string $contactBinding,
        string $originBinding,
        string $channel,
        int $at,
    ): void {
        if (preg_match('/\A[a-f0-9]{64}\z/D', $contactBinding) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/D', $originBinding) !== 1
            || hash_equals($contactBinding, $originBinding)
            || !in_array($channel, ['email', 'phone'], true)
            || $at < 0 || $at > PHP_INT_MAX - OfflineSignupOtpChallenge::VALIDITY_SECONDS
            || ($this->lastTime !== null && $at < $this->lastTime)) {
            self::invalid();
        }
        $this->lastTime = $at;
    }

    private static function scope(string $binding, string $channel): string
    {
        return hash('sha256', "imagine.crm.sandbox.scope.v1\x00".$channel."\x00".$binding);
    }

    /**
     * @param array<string,array{start:int,count:int,last:int|null}> $map
     */
    private function refresh(array &$map, string $scope, int $now): void
    {
        if (!isset($map[$scope])
            || $now - $map[$scope]['start'] >= OfflineSignupOtpChallenge::VALIDITY_SECONDS) {
            $map[$scope] = ['start' => $now, 'count' => 0, 'last' => null];
        }
    }

    /** @return array<string,mixed> */
    private static function issueResult(string $status, ?string $code = null): array
    {
        return [
            'status' => $status,
            'delivery_code' => $code, // test-only transient value, NEVER public
            'informational_only' => true,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_persist' => false,
        ];
    }

    /** @return array<string,bool|string> */
    private static function verificationResult(string $status): array
    {
        return [
            'status' => $status,
            'informational_only' => true,
            'durable_consume_verified' => false,
            'can_grant_access' => false,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Jornada OTP de laboratório invalida.');
    }
}
