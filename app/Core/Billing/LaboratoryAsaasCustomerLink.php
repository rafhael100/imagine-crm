<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-05: durable synthetic organization/customer binding in PG CI.
 *
 * The reservation is committed BEFORE any optional provider HTTP. A crash
 * after reservation cannot automatically trigger a second create. The only
 * way to release an unresolved reservation requires a separate review flow
 * (NOT implemented). No live organization, entitlement, payment, API route.
 */
final readonly class LaboratoryAsaasCustomerLink
{
    public function __construct(private Connection $database) {}

    /**
     * @return array{
     *  status_code:'new_reservation'|'existing_pending'|'needs_review'|'already_linked',
     *  may_attempt_sandbox_once:bool,
     *  can_register:false,can_activate:false,can_charge:false
     * }
     */
    public function reserve(string $organizationRef, string $externalReference, int $at): array
    {
        $this->guard();
        self::validate($organizationRef, $externalReference);

        if ($at <= 0) {
            throw new InvalidArgumentException('Referencia de cliente de laboratorio invalida.');
        }

        try {
            $count = $this->database->affectingStatement(
                "INSERT INTO crm_lab_asaas_customer_links
                    (organization_ref, external_reference, reserved_at)
                 VALUES (?::uuid, ?::uuid, ?)
                 ON CONFLICT DO NOTHING",
                [$organizationRef, $externalReference, $at],
            );
            if ($count !== 0 && $count !== 1) {
                throw new RuntimeException('Reserva com resultado inesperado.');
            }

            // The restricted CI role can SELECT synthetic link fields only.
            // In production use scoped RLS/security-definer authority instead.
            $record = $this->database->selectOne(
                'SELECT external_reference, provider_customer_id, state
                   FROM crm_lab_asaas_customer_links
                  WHERE organization_ref = ?::uuid',
                [$organizationRef],
            );
            if (!$record
                || !hash_equals(strtolower($externalReference), strtolower((string) $record->external_reference))) {
                throw new RuntimeException('Referencia de cliente já pertence a outra organizacao.');
            }

            $status = match ($record->state) {
                'pending_lookup' => $count === 1 ? 'new_reservation' : 'existing_pending',
                'review_required' => 'needs_review',
                'linked' => 'already_linked',
                default => throw new RuntimeException('Estado inesperado de reserva.'),
            };

            return [
                'status_code' => $status,
                'may_attempt_sandbox_once' => $status === 'new_reservation',
                'can_register' => false,
                'can_activate' => false,
                'can_charge' => false,
            ];
        } catch (Throwable) {
            // Never disclose bindings, provider IDs or organization records.
            throw new RuntimeException('Reserva de cliente Asaas indisponivel.');
        }
    }

    /**
     * Bind a provider ID only after a trusted sandbox response. A conflicting
     * provider ID or a changed external reference must never overwrite.
     *
     * @return array{status_code:'new_link'|'same_link',can_activate:false,can_charge:false}
     */
    public function bind(
        string $organizationRef,
        string $externalReference,
        string $providerCustomerId,
    ): array {
        $this->guard();
        self::validate($organizationRef, $externalReference);
        if (preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $providerCustomerId) !== 1) {
            throw new InvalidArgumentException('Identificador sandbox invalido.');
        }

        try {
            $count = $this->database->affectingStatement(
                "UPDATE crm_lab_asaas_customer_links
                    SET provider_customer_id = ?, state = 'linked'
                  WHERE organization_ref = ?::uuid
                    AND external_reference = ?::uuid
                    AND state = 'pending_lookup'
                    AND provider_customer_id IS NULL",
                [$providerCustomerId, $organizationRef, $externalReference],
            );

            if ($count === 1) {
                return ['status_code' => 'new_link', 'can_activate' => false, 'can_charge' => false];
            }
            if ($count !== 0) {
                throw new RuntimeException('Resultado inesperado de vinculo.');
            }

            $row = $this->database->selectOne(
                'SELECT external_reference, provider_customer_id, state
                   FROM crm_lab_asaas_customer_links
                  WHERE organization_ref = ?::uuid',
                [$organizationRef],
            );
            if (!$row || $row->state !== 'linked'
                || !hash_equals(strtolower($externalReference), strtolower((string) $row->external_reference))
                || !hash_equals($providerCustomerId, (string) $row->provider_customer_id)) {
                throw new RuntimeException('Vinculo de cliente divergente.');
            }
            return ['status_code' => 'same_link', 'can_activate' => false, 'can_charge' => false];
        } catch (Throwable) {
            throw new RuntimeException('Vinculo de cliente Asaas indisponivel.');
        }
    }

    /**
     * Conservative failure marker. Once marked, no automatic provider POST
     * can be attempted; an independently approved reconciliation is required.
     */
    public function requireReview(string $organizationRef, string $externalReference): void
    {
        $this->guard();
        self::validate($organizationRef, $externalReference);

        try {
            $updated = $this->database->affectingStatement(
                "UPDATE crm_lab_asaas_customer_links
                    SET state = 'review_required'
                  WHERE organization_ref = ?::uuid
                    AND external_reference = ?::uuid
                    AND state = 'pending_lookup'
                    AND provider_customer_id IS NULL",
                [$organizationRef, $externalReference],
            );
            if ($updated !== 1) {
                throw new RuntimeException('Nao foi possivel reservar para revisao.');
            }
        } catch (Throwable) {
            throw new RuntimeException('Revisao de vinculo Asaas indisponivel.');
        }
    }

    private static function validate(string $organizationRef, string $externalReference): void
    {
        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        if (preg_match($uuid, $organizationRef) !== 1
            || preg_match($uuid, $externalReference) !== 1
            || strcasecmp($organizationRef, $externalReference) === 0) {
            throw new InvalidArgumentException('Referencia de cliente de laboratorio invalida.');
        }
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
            throw new RuntimeException('Vinculo Asaas permitido somente na CI descartavel.');
        }

        try {
            $who = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Papel de vinculo Asaas indisponivel.');
        }
        if (($who->role_name ?? null) !== 'crm_billing_link_ci') {
            throw new RuntimeException('Papel de vinculo Asaas nao autorizado.');
        }
    }
}
