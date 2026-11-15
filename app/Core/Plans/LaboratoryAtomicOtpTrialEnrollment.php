<?php

declare(strict_types=1);

namespace App\Core\Plans;

use App\Core\Identity\OfflineCnpjTrialKeyRotationCandidates;
use App\Core\Identity\OfflineSignupOtpChallenge;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * CRM-TRIAL-OTP-PG01: synthetic, durable PostgreSQL OTP consumption + CNPJ
 * alias uniqueness in a single *outer* transaction on a disposable runner.
 *
 * No HTTP route, user registration, legal owner verification, entitlement,
 * consent, provider message, subscription, or right to use the SaaS.
 * Refuses all runtime environments other than the explicitly gated CI lab.
 */
final readonly class LaboratoryAtomicOtpTrialEnrollment
{
    private OfflineSignupOtpChallenge $otp;

    /**
     * @param list<array{version:int,secret:string}> $keyVersions
     *   Complete, ordered historical key ring for the disposable CI ledger.
     */
    public function __construct(
        private Connection $db,
        #[\SensitiveParameter] private string $otpKey,
        #[\SensitiveParameter] private array $keyVersions,
    ) {
        if (strlen($otpKey) < 32 || strlen($otpKey) > 128) {
            throw new InvalidArgumentException('Contexto OTP de laboratorio invalido.');
        }
        $this->otp = new OfflineSignupOtpChallenge();
    }

    /**
     * Persist only the challenge digest: NEVER the transient delivery code.
     * @param array{nonce:string,digest:string,issued_at:int,expires_at:int} $challenge
     */
    public function recordSyntheticChallenge(
        array $challenge,
        string $opaqueContactBinding,
        string $channel,
    ): void {
        $this->guard();
        if (!self::hex($opaqueContactBinding, 64)
            || !in_array($channel, ['email', 'phone'], true)
            || array_keys($challenge) !== ['nonce', 'digest', 'issued_at', 'expires_at']
            || !self::hex($challenge['nonce'] ?? null, 32)
            || !self::hex($challenge['digest'] ?? null, 64)
            || !is_int($challenge['issued_at'] ?? null)
            || !is_int($challenge['expires_at'] ?? null)
            || $challenge['issued_at'] < 1
            || $challenge['issued_at'] > PHP_INT_MAX - OfflineSignupOtpChallenge::VALIDITY_SECONDS
            || $challenge['expires_at'] !== $challenge['issued_at'] + OfflineSignupOtpChallenge::VALIDITY_SECONDS) {
            throw new InvalidArgumentException('Desafio OTP sintetico invalido.');
        }
        try {
            $this->db->table('public.crm_ci_trial_otp_challenges')->insert([
                'nonce' => $challenge['nonce'],
                'contact_binding' => $opaqueContactBinding,
                'channel' => $channel,
                'digest' => $challenge['digest'],
                'issued_at' => $challenge['issued_at'],
                'expires_at' => $challenge['expires_at'],
                'attempts_used' => 0,
            ]);
        } catch (Throwable) {
            throw new RuntimeException('Persistencia de OTP sintetico indisponivel.');
        }
    }

    /**
     * OTP and ALL historical CNPJ aliases are serialized by one PostgreSQL
     * transaction. A partial alias insert is rolled back to a savepoint while
     * keeping the OTP consumed on duplicate, preventing replay.
     *
     * The output is INTERNAL CI evidence only, never an HTTP response.
     * @return array<string, bool|string|int>
     */
    public function observeSyntheticTrial(
        string $challengeNonce,
        #[\SensitiveParameter] string $candidate,
        string $opaqueContactBinding,
        string $channel,
        #[\SensitiveParameter] string $cnpj,
        int $observedAt,
    ): array {
        $this->guard();
        if (!self::hex($challengeNonce, 32)
            || !self::hex($opaqueContactBinding, 64)
            || !in_array($channel, ['email', 'phone'], true)
            || preg_match('/\A[0-9]{6}\z/D', $candidate) !== 1
            || $observedAt < 1
            || $this->db->transactionLevel() !== 0) {
            throw new InvalidArgumentException('Observacao de trial sintetico invalida.');
        }

        try {
            return $this->db->transaction(function () use (
                $challengeNonce, $candidate, $opaqueContactBinding,
                $channel, $cnpj, $observedAt,
            ): array {
                $row = $this->db->table('public.crm_ci_trial_otp_challenges')
                    ->where('nonce', $challengeNonce)
                    ->where('contact_binding', $opaqueContactBinding)
                    ->where('channel', $channel)
                    ->lockForUpdate()
                    ->first();

                if (!$row || $row->consumed_at !== null
                    || (int) $row->attempts_used >= OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS) {
                    return self::result('synthetic_otp_denied');
                }

                $challenge = [
                    'nonce' => (string) $row->nonce,
                    'digest' => (string) $row->digest,
                    'issued_at' => (int) $row->issued_at,
                    'expires_at' => (int) $row->expires_at,
                ];

                $observation = $this->otp->inspect(
                    $challenge, $candidate, $this->otpKey, $opaqueContactBinding,
                    $channel, $observedAt, (int) $row->attempts_used, false,
                );

                if ($observation['status_code'] !== 'candidate_matches_only') {
                    // A wrong guess uses one of the five durable attempts.
                    // An expired/future request never extends the deadline.
                    if ($observation['status_code'] === 'candidate_mismatch') {
                        $this->db->table('public.crm_ci_trial_otp_challenges')
                            ->where('nonce', $challengeNonce)
                            ->update(['attempts_used' => (int) $row->attempts_used + 1]);
                    }
                    return self::result('synthetic_otp_denied');
                }

                // Validate complete historical aliases + key policy BEFORE
                // consuming the challenge; mismatch aborts the transaction.
                $derived = (new OfflineCnpjTrialKeyRotationCandidates())
                    ->derive($cnpj, $this->keyVersions);
                $aliases = $derived['candidate_versions_and_bindings'];
                $this->assertCompleteKeyHistory($aliases);

                $this->db->table('public.crm_ci_trial_otp_challenges')
                    ->where('nonce', $challengeNonce)
                    ->update(['consumed_at' => $observedAt]);

                // Laravel's nested transaction uses a PG savepoint. On an
                // historical duplicate, undo any new aliases but preserve
                // the OTP consumed in the outer transaction. A race can
                // never produce partial new-key enrollment.
                try {
                    $this->db->transaction(function () use ($aliases, $observedAt): void {
                        foreach ($aliases as $alias) {
                            $count = $this->db->affectingStatement(
                                "INSERT INTO public.crm_ci_trial_hmac_aliases
                                  (key_version, cnpj_hmac, started_at, expires_at)
                                 VALUES (?, ?, ?, ?)
                                 ON CONFLICT DO NOTHING",
                                [
                                    $alias['version'],
                                    $alias['internal_binding'],
                                    $observedAt,
                                    $observedAt + 14 * 86400,
                                ],
                            );
                            if ($count === 0) {
                                throw new LaboratoryTrialAliasCollision();
                            }
                            if ($count !== 1) {
                                throw new RuntimeException('Contagem de aliases inesperada.');
                            }
                        }
                    }, 1);
                    return self::result('synthetic_first_marker');
                } catch (LaboratoryTrialAliasCollision) {
                    return self::result('synthetic_duplicate_marker');
                }
            }, 1);
        } catch (Throwable) {
            // Never expose SQL bindings, OTP digest, CNPJ or keys in errors.
            throw new RuntimeException('Observacao atomica de trial indisponivel.');
        }
    }

    /** @param list<array{version:int,internal_binding:string}> $aliases */
    private function assertCompleteKeyHistory(array $aliases): void
    {
        $policy = $this->db->table('public.crm_ci_trial_key_epochs')
            ->orderBy('version')->get(['version', 'secret_tag']);
        if ($policy->count() !== count($aliases)) {
            throw new RuntimeException('Historico de chaves incompleto.');
        }

        foreach ($aliases as $index => $alias) {
            $secret = $this->keyVersions[$index]['secret'];
            $tag = hash_hmac('sha256', 'imagine.crm.trial.keyring.v1', $secret);
            $row = $policy[$index];
            if ((int) $row->version !== $alias['version']
                || !is_string($row->secret_tag)
                || !hash_equals($row->secret_tag, $tag)) {
                throw new RuntimeException('Historico de chaves invalido.');
            }
        }
    }

    private function guard(): void
    {
        $pgsql = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_TRIAL_OTP_PG_LAB') !== 'synthetic-only'
            || $this->db->getDriverName() !== 'pgsql'
            || !is_array($pgsql)
            || ($pgsql['host'] ?? null) !== '127.0.0.1'
            || ($pgsql['database'] ?? null) !== 'imagine_crm_ci'
            || $this->db->transactionLevel() !== 0) {
            throw new RuntimeException('Trial OTP restrito a PostgreSQL efemero do CI.');
        }
        $role = $this->db->selectOne(
            'SELECT current_user AS role_name, rolsuper, rolbypassrls FROM pg_roles WHERE rolname=current_user'
        );
        if (!$role || $role->role_name !== 'crm_trial_otp_ci'
            || $role->rolsuper || $role->rolbypassrls) {
            throw new RuntimeException('Papel OTP do CI nao autorizado.');
        }
    }

    private static function hex(mixed $value, int $length): bool
    {
        return is_string($value)
            && preg_match('/\A[a-f0-9]{'.$length.'}\z/D', $value) === 1;
    }

    /** @return array<string,bool|string|int> */
    private static function result(string $status): array
    {
        return [
            'status_code' => $status,
            'illustrative_trial_days' => 14,
            'physical_otp_consumption_observed' => true,
            'historical_aliases_checked' => true,
            'informational_only' => true,
            'contact_ownership_verified' => false,
            'company_owner_verified' => false,
            'consent_verified' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_create_tenant' => false,
            'can_reserve_trial' => false,
            'can_allocate_quota' => false,
            'can_charge' => false,
        ];
    }
}

/** Savepoint rollback signal used only inside the synthetic outer transaction. */
final class LaboratoryTrialAliasCollision extends RuntimeException {}
