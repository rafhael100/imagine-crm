<?php

declare(strict_types=1);

namespace App\Core\Plans;

use App\Core\Identity\OfflineCnpjTrialKeyRotationCandidates;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * F3-TRIAL-PG02: atomic historical-alias proof for SYNTHETIC trial markers.
 *
 * The restricted DB identity has INSERT on trial HMAC rows and SELECT ONLY
 * on non-PII key-version policy; no direct claim lookup or mutation.
 *
 * Every historical HMAC alias is inserted in ONE SQL transaction. If any
 * alias already exists, roll back the whole attempt (even aliases that
 * were new). This prevents a partial new-key marker after an older claim.
 *
 * This is NOT customer registration, eligibility, identity/ownership proof,
 * trial activation, invitation or billing. CI lab environment only.
 */
final readonly class LaboratoryHistoricalTrialAliases
{
    private const TRIAL_SECONDS = 14 * 86400;

    /**
     * @param list<array{version:int,secret:string}> $keyVersions
     *   Must contain ALL configured historical versions, sorted ascending.
     */
    public function __construct(
        private Connection $database,
        #[\SensitiveParameter] private array $keyVersions,
    ) {}

    /**
     * @return array{
     *  status_code:'synthetic_first_marker'|'synthetic_duplicate_marker',
     *  alias_versions_checked:int,physical_uniqueness_observed:true,
     *  complete_history_policy_checked:true,informational_only:true,
     *  can_register:false,can_reserve_trial:false,can_authenticate:false,
     *  can_create_tenant:false,can_charge:false,can_allocate_quota:false,
     *  company_owner_verified:false,contact_verified:false,consent_verified:false
     * }
     */
    public function observe(#[\SensitiveParameter] string $cnpj, int $startedAt): array
    {
        $this->guard();

        if ($startedAt < 1 || $startedAt > PHP_INT_MAX - self::TRIAL_SECONDS) {
            throw new InvalidArgumentException('Horario de observacao de trial invalido.');
        }

        // Strict validation and purpose-separated HMAC calculation occurs
        // offline; no raw CNPJ is sent to the database or returned.
        $derived = (new OfflineCnpjTrialKeyRotationCandidates())->derive($cnpj, $this->keyVersions);
        $aliases = $derived['candidate_versions_and_bindings'];

        try {
            $policy = $this->database->select(
                'SELECT version, secret_tag FROM crm_lab_trial_key_epochs ORDER BY version',
            );
        } catch (Throwable) {
            throw new RuntimeException('Politica historica de trial indisponivel.');
        }

        if (count($policy) !== count($aliases)) {
            throw new RuntimeException('Historico incompleto de chaves de trial.');
        }

        foreach ($aliases as $i => $alias) {
            $version = $alias['version'];
            $key = $this->keyVersions[$i]['secret'];
            $tag = hash_hmac('sha256', 'imagine.crm.trial.keyring.v1', $key);

            if ((int) ($policy[$i]->version ?? 0) !== $version
                || !is_string($policy[$i]->secret_tag ?? null)
                || !hash_equals($policy[$i]->secret_tag, $tag)) {
                throw new RuntimeException('Historico incompleto de chaves de trial.');
            }
        }

        try {
            $this->database->transaction(function () use ($aliases, $startedAt): void {
                foreach ($aliases as $alias) {
                    $count = $this->database->affectingStatement(
                        "INSERT INTO crm_lab_trial_claims
                             (cnpj_hmac, key_version, started_at, expires_at, state)
                         VALUES (?, ?, ?, ?, 'synthetic_observation_only')
                         ON CONFLICT DO NOTHING",
                        [
                            $alias['internal_binding'],
                            $alias['version'],
                            $startedAt,
                            $startedAt + self::TRIAL_SECONDS,
                        ],
                    );

                    if ($count === 0) {
                        // Deliberately throw inside transaction: any earlier
                        // aliases inserted by THIS request must be rolled back.
                        throw new LaboratoryHistoricalAliasCollision();
                    }
                    if ($count !== 1) {
                        throw new RuntimeException('Resultado inesperado de alias.');
                    }
                }
            }, 1);
            $first = true;
        } catch (LaboratoryHistoricalAliasCollision) {
            $first = false;
        } catch (Throwable) {
            // No database exception chaining: bindings include private HMACs.
            throw new RuntimeException('Observacao historica de laboratorio indisponivel.');
        }

        return [
            'status_code' => $first ? 'synthetic_first_marker' : 'synthetic_duplicate_marker',
            'alias_versions_checked' => count($aliases),
            'physical_uniqueness_observed' => true,
            'complete_history_policy_checked' => true,
            'informational_only' => true,
            'can_register' => false,
            'can_reserve_trial' => false,
            'can_authenticate' => false,
            'can_create_tenant' => false,
            'can_charge' => false,
            'can_allocate_quota' => false,
            'company_owner_verified' => false,
            'contact_verified' => false,
            'consent_verified' => false,
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
            throw new RuntimeException('Acesso a observacao historica limitado a laboratorio CI.');
        }

        $role = $this->database->selectOne('SELECT current_user AS role_name');
        if (($role->role_name ?? null) !== 'crm_trial_claim_ci') {
            throw new RuntimeException('Papel de trial do laboratorio nao autorizado.');
        }
    }
}

/** Internal-only transactional signal; never returned to HTTP or logs. */
final class LaboratoryHistoricalAliasCollision extends RuntimeException {}
