<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-10: no-effect reconciliation of a synthetic subscription.
 *
 * Only a pre-existing, blocked local draft and a linked synthetic customer
 * can yield an observed Asaas Sandbox subscription. Uses GET ONLY.
 * It never sends/creates payments, authorizes terms or activates access.
 *
 * A dedicated CI PostgreSQL role verifies provider customer ID; no
 * production route, scheduled worker or real Asaas key.
 */
final readonly class LaboratoryAsaasSubscriptionObservation
{
    public function __construct(
        private Connection $database,
        private AsaasSandboxSubscriptionLookup $lookup,
    ) {}

    /**
     * @return array{
     *   status_code:'synthetic_observed'|'synthetic_already_observed'|'synthetic_review_required',
     *   lookup_performed:bool,informational_only:true,
     *   payment_verified:false,terms_accepted:false,
     *   can_create_subscription:false,can_charge:false,
     *   can_activate:false,can_invoice:false
     * }
     */
    public function reconcile(string $organizationRef, string $externalReference, int $observedAt): array
    {
        $this->guard();
        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        if (preg_match($uuid, $organizationRef) !== 1
            || preg_match($uuid, $externalReference) !== 1
            || strcasecmp($organizationRef, $externalReference) === 0
            || $observedAt < 1) {
            throw new InvalidArgumentException('Referencia de assinatura invalida.');
        }

        // Never make an external request with a pending transaction:
        // reservations and provider references must already be committed.
        if ($this->database->transactionLevel() !== 0) {
            throw new RuntimeException('Conciliacao de assinatura requer autocommit.');
        }

        try {
            // If already observed, do not call the remote provider again.
            $prior = $this->database->selectOne(
                'SELECT external_reference, provider_subscription_id, state
                   FROM crm_lab_asaas_subscription_observations
                  WHERE organization_ref = ?::uuid',
                [$organizationRef],
            );
            if ($prior) {
                if (strtolower((string) $prior->external_reference) !== strtolower($externalReference)
                    || $prior->state !== 'observed_no_rights') {
                    throw new RuntimeException('Associacao de assinatura divergente.');
                }
                return self::answer('synthetic_already_observed', false);
            }

            // Local source-of-truth is the draft, not untrusted HTTP input.
            $draft = $this->database->selectOne(
                "SELECT d.state AS draft_state,
                        c.state AS customer_state, c.provider_customer_id
                   FROM crm_lab_asaas_subscription_drafts AS d
                   INNER JOIN crm_lab_asaas_customer_links AS c
                     ON c.organization_ref = d.organization_ref
                  WHERE d.organization_ref = ?::uuid
                    AND d.external_reference = ?::uuid",
                [$organizationRef, $externalReference],
            );
            if (!$draft
                || $draft->draft_state !== 'blocked_review'
                || $draft->customer_state !== 'linked'
                || !is_string($draft->provider_customer_id)
                || preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $draft->provider_customer_id) !== 1) {
                throw new RuntimeException('Rascunho e pagador nao conciliaveis.');
            }
        } catch (Throwable) {
            throw new RuntimeException('Verificacao local de assinatura indisponivel.');
        }

        try {
            $remote = $this->lookup->byExternalReference(
                strtolower($externalReference),
                $draft->provider_customer_id,
            );
        } catch (Throwable) {
            return self::answer('synthetic_review_required', true);
        }

        $subscriptionId = $remote['provider_subscription_id'] ?? null;
        if (($remote['status_code'] ?? null) !== 'sandbox_subscription_found'
            || ($remote['environment'] ?? null) !== 'sandbox'
            || ($remote['payment_verified'] ?? true) !== false
            || ($remote['terms_accepted'] ?? true) !== false
            || ($remote['can_charge'] ?? true) !== false
            || !is_string($subscriptionId)
            || preg_match('/\Asub_[A-Za-z0-9]{5,64}\z/D', $subscriptionId) !== 1) {
            return self::answer('synthetic_review_required', true);
        }

        try {
            $inserted = $this->database->affectingStatement(
                "INSERT INTO crm_lab_asaas_subscription_observations
                    (organization_ref, external_reference, provider_subscription_id, observed_at)
                 SELECT d.organization_ref, d.external_reference, ?, ?
                   FROM crm_lab_asaas_subscription_drafts AS d
                   INNER JOIN crm_lab_asaas_customer_links AS c
                     ON c.organization_ref = d.organization_ref
                  WHERE d.organization_ref = ?::uuid
                    AND d.external_reference = ?::uuid
                    AND d.state = 'blocked_review' AND c.state = 'linked'
                 ON CONFLICT DO NOTHING",
                [$subscriptionId, $observedAt, $organizationRef, $externalReference],
            );
            if ($inserted !== 0 && $inserted !== 1) {
                throw new RuntimeException('Resultado SQL de assinatura invalido.');
            }

            $actual = $this->database->selectOne(
                'SELECT external_reference, provider_subscription_id, state
                   FROM crm_lab_asaas_subscription_observations
                  WHERE organization_ref = ?::uuid',
                [$organizationRef],
            );
            if (!$actual
                || strtolower((string) $actual->external_reference) !== strtolower($externalReference)
                || !hash_equals($subscriptionId, (string) $actual->provider_subscription_id)
                || $actual->state !== 'observed_no_rights') {
                throw new RuntimeException('Associacao de assinatura nao confirmada.');
            }
        } catch (Throwable) {
            // SQL errors could contain pseudonymous identifiers. No chaining.
            throw new RuntimeException('Persistencia de assinatura em revisao indisponivel.');
        }

        return self::answer($inserted === 1 ? 'synthetic_observed' : 'synthetic_already_observed', true);
    }

    private static function answer(string $status, bool $lookedUp): array
    {
        return [
            'status_code' => $status,
            'lookup_performed' => $lookedUp,
            'informational_only' => true,
            'payment_verified' => false,
            'terms_accepted' => false,
            'can_create_subscription' => false,
            'can_charge' => false,
            'can_activate' => false,
            'can_invoice' => false,
        ];
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_SUBSCRIPTION_OBSERVATION_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Observacao de assinatura restrita a CI descartavel.');
        }
        try {
            $identity = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Identidade do revisor indisponivel.');
        }
        if (($identity->role_name ?? null) !== 'crm_billing_subscription_review_ci') {
            throw new RuntimeException('Papel de revisao de assinatura nao autorizado.');
        }
    }
}
