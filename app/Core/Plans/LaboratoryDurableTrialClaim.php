<?php

declare(strict_types=1);

namespace App\Core\Plans;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * F3-TRIAL-PG01: physical first-use uniqueness lab (NO customer entitlement).
 *
 * This class is intentionally in application Core so its SQL/constraint
 * behavior is exercised with the Laravel connection stack. It refuses to
 * run outside disposable GitHub CI PostgreSQL or with a privileged role.
 *
 * There is no published route, auth, payment, tenant, CNPJ lookup, verified
 * company ownership, consent or transport. The database row is an immutable
 * synthetic observation, NEVER a trial activation.
 *
 * Rotation between different HMAC keys is NOT implemented. This only
 * enforces uniqueness for one fixed key version in the CI experiment.
 */
final readonly class LaboratoryDurableTrialClaim
{
    private const TRIAL_SECONDS = 14 * 86400;

    public function __construct(
        private Connection $database,
        #[\SensitiveParameter] private string $dedicatedHmacKey,
    ) {
        if (strlen($dedicatedHmacKey) < 32 || strlen($dedicatedHmacKey) > 128) {
            throw new InvalidArgumentException('Contexto de observacao de trial invalido.');
        }
    }

    /**
     * @return array{
     *   status_code:'synthetic_first_marker'|'synthetic_duplicate_marker',
     *   physical_uniqueness_observed:true,
     *   key_version:1,informational_only:true,company_owner_verified:false,
     *   contact_verified:false,consent_verified:false,
     *   can_reserve_trial:false,can_register:false,can_create_tenant:false,
     *   can_authenticate:false,can_charge:false,can_allocate_quota:false
     * }
     */
    public function observe(
        #[\SensitiveParameter] string $cnpj,
        int $startedAt,
    ): array {
        $this->guard();

        if ($startedAt < 1 || $startedAt > PHP_INT_MAX - self::TRIAL_SECONDS) {
            throw new InvalidArgumentException('Horario de observacao de trial invalido.');
        }

        // CNPJ never reaches SQL or outputs. The purpose-separated HMAC,
        // derived with a test-only secret, is the sole inserted identifier.
        $fingerprint = (new OfflineCnpjTrialHmacBinding())->derive($cnpj, $this->dedicatedHmacKey);

        try {
            // The restricted role has only INSERT; no SELECT, UPDATE or
            // DELETE. affected rows = 1 first time, 0 on unique conflict.
            // No read-before-write race, no expiration-based reset.
            $count = $this->database->affectingStatement(
                "INSERT INTO crm_lab_trial_claims
                    (cnpj_hmac, key_version, started_at, expires_at, state)
                 VALUES (?, 1, ?, ?, 'synthetic_observation_only')
                 ON CONFLICT DO NOTHING",
                [$fingerprint, $startedAt, $startedAt + self::TRIAL_SECONDS],
            );
        } catch (Throwable) {
            // QueryException may include parameter bindings (sensitive HMAC);
            // never chain it or leak it through controller/log/HTTP output.
            throw new RuntimeException('Observacao persistente de laboratorio indisponivel.');
        }

        if ($count !== 0 && $count !== 1) {
            throw new RuntimeException('Resultado inesperado de observacao de laboratorio.');
        }

        return [
            'status_code' => $count === 1 ? 'synthetic_first_marker' : 'synthetic_duplicate_marker',
            'physical_uniqueness_observed' => true,
            'key_version' => 1,
            'informational_only' => true,
            'company_owner_verified' => false,
            'contact_verified' => false,
            'consent_verified' => false,
            'can_reserve_trial' => false,
            'can_register' => false,
            'can_create_tenant' => false,
            'can_authenticate' => false,
            'can_charge' => false,
            'can_allocate_quota' => false,
        ];
    }

    private function guard(): void
    {
        $pgsql = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_TRIAL_LEDGER_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pgsql)
            || ($pgsql['host'] ?? null) !== '127.0.0.1'
            || ($pgsql['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Acesso a observacao de trial limitado a laboratorio CI.');
        }

        // Neither DB owner nor the CRM app/tenant roles may use this
        // experiment. Requires the test-only INSERT-only database identity.
        $actual = $this->database->selectOne('SELECT current_user AS role_name');
        if (($actual->role_name ?? null) !== 'crm_trial_claim_ci') {
            throw new RuntimeException('Papel de trial do laboratorio nao autorizado.');
        }
    }
}
