<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-08: PostgreSQL draft can only follow an already linked payer.
 *
 * CI-only. It cannot call Asaas, activate a tenant, accept terms or mark any
 * commercial price as approved. One unalterable synthetic draft per org.
 */
final readonly class LaboratoryAsaasSubscriptionDraft
{
    public function __construct(private Connection $database) {}

    /**
     * @param array<string,mixed> $spec Output of AsaasSubscriptionDraftSpec.
     * @return array{
     *  status_code:'new_blocked_draft'|'existing_identical_draft',
     *  informational_only:true,price_homologated:false,terms_accepted:false,
     *  can_create_subscription:false,can_charge:false,can_activate:false
     * }
     */
    public function persist(string $organizationRef, array $spec): array
    {
        $this->guard();

        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        $reference = $spec['external_reference'] ?? null;
        if (!is_string($reference)
            || preg_match($uuid, $reference) !== 1
            || preg_match($uuid, $organizationRef) !== 1
            || strcasecmp($reference, $organizationRef) === 0
            || ($spec['price_homologated'] ?? null) !== false
            || ($spec['terms_accepted'] ?? null) !== false
            || ($spec['can_create_subscription'] ?? null) !== false
            || ($spec['can_charge'] ?? null) !== false
            || ($spec['can_activate'] ?? null) !== false) {
            throw new InvalidArgumentException('Rascunho de assinatura nao autorizado.');
        }

        // Recompute against server-owned catalog/time rules. Never trust a
        // caller's amount, trial end, currency, cycle or other flags.
        $expected = AsaasSubscriptionDraftSpec::preview(
            (string) ($spec['plan_code'] ?? ''),
            (string) ($spec['trial_started_on'] ?? ''),
            (string) ($spec['first_due_on'] ?? ''),
            (string) ($spec['billing_type'] ?? ''),
            $reference,
        );
        if ($spec !== $expected) {
            throw new InvalidArgumentException('Rascunho de assinatura divergente do catalogo.');
        }

        try {
            // Insert only when a matching organization was already associated
            // with a provider customer. Provider ID itself is NEVER read.
            $count = $this->database->affectingStatement(
                "INSERT INTO crm_lab_asaas_subscription_drafts
                  (organization_ref, external_reference, plan_code, monthly_cents,
                   billing_type, trial_started_on, trial_ends_on, first_due_on)
                 SELECT ?::uuid, ?::uuid, ?, ?, ?, ?::date, ?::date, ?::date
                   FROM crm_lab_asaas_customer_links
                  WHERE organization_ref = ?::uuid AND state = 'linked'
                 ON CONFLICT DO NOTHING",
                [
                    $organizationRef,
                    $expected['external_reference'],
                    $expected['plan_code'],
                    $expected['monthly_cents'],
                    $expected['billing_type'],
                    $expected['trial_started_on'],
                    $expected['trial_ends_on'],
                    $expected['first_due_on'],
                    $organizationRef,
                ],
            );

            if ($count !== 0 && $count !== 1) {
                throw new RuntimeException('Resultado inesperado da reserva de assinatura.');
            }

            $row = $this->database->selectOne(
                'SELECT external_reference, plan_code, monthly_cents, billing_type,
                        trial_started_on, trial_ends_on, first_due_on, state
                   FROM crm_lab_asaas_subscription_drafts
                  WHERE organization_ref = ?::uuid',
                [$organizationRef],
            );
            if (!$row
                || (string) $row->state !== 'blocked_review'
                || strtolower((string) $row->external_reference) !== $expected['external_reference']
                || (string) $row->plan_code !== $expected['plan_code']
                || (int) $row->monthly_cents !== $expected['monthly_cents']
                || (string) $row->billing_type !== $expected['billing_type']
                || (string) $row->trial_started_on !== $expected['trial_started_on']
                || (string) $row->trial_ends_on !== $expected['trial_ends_on']
                || (string) $row->first_due_on !== $expected['first_due_on']) {
                throw new RuntimeException('Rascunho de assinatura inexistente ou divergente.');
            }

            return [
                'status_code' => $count === 1
                    ? 'new_blocked_draft' : 'existing_identical_draft',
                'informational_only' => true,
                'price_homologated' => false,
                'terms_accepted' => false,
                'can_create_subscription' => false,
                'can_charge' => false,
                'can_activate' => false,
            ];
        } catch (Throwable) {
            // Exception SQL text/params may include org refs; no propagation.
            throw new RuntimeException('Registro de assinatura de laboratorio indisponivel.');
        }
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_SUBSCRIPTION_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Assinatura em laboratorio restrita a CI PostgreSQL.');
        }

        try {
            $role = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Identidade de assinaturas indisponivel.');
        }

        if (($role->role_name ?? null) !== 'crm_billing_subscription_ci') {
            throw new RuntimeException('Papel de assinatura nao autorizado.');
        }
    }
}
