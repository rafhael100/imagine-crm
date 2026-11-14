<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-07: GET-only recovery of a synthetic customer binding.
 *
 * Distinct DB principal from the initial reservation/POST actor. Even an
 * absent provider result never authorizes another POST or new reservation.
 *
 * This class is not registered with Laravel routes or workers. CI ONLY.
 */
final readonly class LaboratoryAsaasCustomerReconciler
{
    public function __construct(
        private Connection $database,
        private AsaasSandboxCustomerGateway $sandbox,
    ) {}

    /**
     * @return array{
     *  status_code:'sandbox_reconciled'|'sandbox_already_linked'|'sandbox_review_pending',
     *  provider_lookup_performed:bool,
     *  informational_only:true,can_create_customer:false,
     *  can_register:false,can_activate:false,can_charge:false
     * }
     */
    public function lookupAndReconcile(string $organizationRef, string $externalReference): array
    {
        $this->guard();

        if ($this->database->transactionLevel() !== 0) {
            throw new RuntimeException('Reconciliacao Asaas nao permitida durante transacao.');
        }

        self::validate($organizationRef, $externalReference);

        $row = $this->row($organizationRef, $externalReference);
        if ($row->state === 'linked') {
            return self::answer('sandbox_already_linked', false);
        }
        if (!in_array($row->state, ['pending_lookup', 'review_required'], true)
            || $row->provider_customer_id !== null) {
            throw new RuntimeException('Estado de conciliacao nao autorizado.');
        }

        try {
            // GET ONLY. Provider might have committed an earlier POST for
            // which our prior worker never received a response. Empty or
            // ambiguous lookup is NOT evidence that POST can be replayed.
            $result = $this->sandbox->lookupOnlySyntheticCustomer($externalReference);
        } catch (Throwable) {
            $this->markReview($organizationRef, $externalReference);
            return self::answer('sandbox_review_pending', true);
        }

        if (($result['environment'] ?? null) !== 'sandbox'
            || ($result['can_create_customer'] ?? true) !== false
            || ($result['can_charge'] ?? true) !== false
            || ($result['can_activate'] ?? true) !== false) {
            $this->markReview($organizationRef, $externalReference);
            return self::answer('sandbox_review_pending', true);
        }

        if (($result['status_code'] ?? null) !== 'sandbox_customer_found') {
            $this->markReview($organizationRef, $externalReference);
            return self::answer('sandbox_review_pending', true);
        }

        $id = $result['provider_customer_id'] ?? null;
        if (!is_string($id)
            || preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $id) !== 1) {
            $this->markReview($organizationRef, $externalReference);
            return self::answer('sandbox_review_pending', true);
        }

        try {
            $updated = $this->database->affectingStatement(
                "UPDATE crm_lab_asaas_customer_links
                    SET state = 'linked', provider_customer_id = ?
                  WHERE organization_ref = ?::uuid
                    AND external_reference = ?::uuid
                    AND state IN ('pending_lookup', 'review_required')
                    AND provider_customer_id IS NULL",
                [$id, $organizationRef, $externalReference],
            );
            if ($updated === 1) {
                return self::answer('sandbox_reconciled', true);
            }
            if ($updated !== 0) {
                throw new RuntimeException('Conciliacao inconsistente.');
            }

            // Two reviewers raced. Only an IDENTICAL committed association
            // is idempotent; a different ID or ref must never overwrite.
            $current = $this->row($organizationRef, $externalReference);
            if ($current->state === 'linked'
                && is_string($current->provider_customer_id)
                && hash_equals($id, $current->provider_customer_id)) {
                return self::answer('sandbox_already_linked', true);
            }
        } catch (Throwable) {
            // Do not leak SQL errors with synthetic provider-ID bindings.
            throw new RuntimeException('Conciliacao Asaas indisponivel.');
        }

        throw new RuntimeException('Conciliacao Asaas indisponivel.');
    }

    private function markReview(string $organizationRef, string $externalReference): void
    {
        try {
            $this->database->affectingStatement(
                "UPDATE crm_lab_asaas_customer_links
                    SET state = 'review_required'
                  WHERE organization_ref = ?::uuid
                    AND external_reference = ?::uuid
                    AND state = 'pending_lookup' AND provider_customer_id IS NULL",
                [$organizationRef, $externalReference],
            );
        } catch (Throwable) {
            // A pending reservation itself blocks any second provider POST.
            throw new RuntimeException('Revisao manual Asaas indisponivel.');
        }
    }

    private function row(string $organizationRef, string $externalReference): object
    {
        try {
            $row = $this->database->selectOne(
                'SELECT external_reference, provider_customer_id, state
                   FROM crm_lab_asaas_customer_links
                  WHERE organization_ref = ?::uuid',
                [$organizationRef],
            );
        } catch (Throwable) {
            throw new RuntimeException('Consulta local de conciliacao indisponivel.');
        }
        if (!$row || !hash_equals(
            strtolower($externalReference),
            strtolower((string) $row->external_reference),
        )) {
            throw new RuntimeException('Referencia local de conciliacao nao autorizada.');
        }
        return $row;
    }

    private static function validate(string $org, string $external): void
    {
        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        if (preg_match($uuid, $org) !== 1 || preg_match($uuid, $external) !== 1
            || strcasecmp($org, $external) === 0) {
            throw new InvalidArgumentException('Identificadores sintéticos de conciliacao invalidos.');
        }
    }

    private static function answer(string $status, bool $lookedUp): array
    {
        return [
            'status_code' => $status,
            'provider_lookup_performed' => $lookedUp,
            'informational_only' => true,
            'can_create_customer' => false,
            'can_register' => false,
            'can_activate' => false,
            'can_charge' => false,
        ];
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_LINK_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Conciliacao Asaas limitada a CI descartavel.');
        }

        try {
            $who = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Identidade de conciliacao indisponivel.');
        }
        if (($who->role_name ?? null) !== 'crm_billing_link_review_ci') {
            throw new RuntimeException('Papel de conciliacao Asaas nao autorizado.');
        }
    }
}
