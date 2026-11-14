<?php

declare(strict_types=1);

namespace App\Core\Billing;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-INT-03: distinct, restricted reviewer for synthetic payment IDs.
 *
 * Reviewer may not INSERT, DELETE, switch tenant/order or initiate provider
 * HTTP. Provider existence must be verified independently by the caller.
 * This CI-only experiment is never evidence of payment settlement.
 */
final readonly class LaboratoryAsaasOrderPaymentReviewer
{
    public function __construct(private Connection $database) {}

    /**
     * @return array{status_code:'payment_linked'|'payment_already_linked',
     *     informational_only:true,can_charge:false,can_mark_paid:false,can_activate:false}
     */
    public function bind(
        string $organizationRef,
        string $orderRef,
        string $customerId,
        string $planCode,
        string $dueOn,
        string $paymentId,
    ): array {
        $this->assertAutocommitBoundary();
        LaboratoryAsaasOrderReservation::validateIdentity($organizationRef, $orderRef, $customerId);
        LaboratoryAsaasOrderReservation::validateDate($dueOn);
        if (preg_match('/\Apay_[A-Za-z0-9]{5,64}\z/D', $paymentId) !== 1) {
            throw new InvalidArgumentException('Identificador de pagamento de laboratorio invalido.');
        }
        $quote = CommercialPlanReferenceCatalog::preview($planCode, 1);

        try {
            $updated = $this->database->affectingStatement(
                "UPDATE crm_lab_asaas_order_reservations
                    SET provider_payment_id = ?, state = 'linked'
                  WHERE organization_ref = ?::uuid AND order_ref = ?::uuid
                    AND provider_customer_id = ? AND plan_code = ?
                    AND amount_cents = ? AND due_on = ?::date
                    AND state = 'reserved' AND provider_payment_id IS NULL",
                [$paymentId, $organizationRef, $orderRef, $customerId, $planCode,
                    $quote['monthly_cents'], $dueOn],
            );
            if ($updated === 1) {
                return self::answer('payment_linked');
            }
            if ($updated !== 0) {
                throw new RuntimeException('Transicao de pedido invalida.');
            }
            // A concurrent reviewer may have linked the same payment.
            // Only the IDENTICAL immutable association is idempotent.
            $row = $this->database->selectOne(
                'SELECT organization_ref, provider_customer_id, plan_code,
                        amount_cents, due_on, provider_payment_id, state
                   FROM crm_lab_asaas_order_reservations WHERE order_ref = ?::uuid',
                [$orderRef],
            );
            if ($row && (string) $row->organization_ref === strtolower($organizationRef)
                && $row->provider_customer_id === $customerId
                && $row->plan_code === $planCode
                && (int) $row->amount_cents === $quote['monthly_cents']
                && (string) $row->due_on === $dueOn
                && $row->state === 'linked'
                && $row->provider_payment_id === $paymentId) {
                return self::answer('payment_already_linked');
            }
        } catch (Throwable) {
            throw new RuntimeException('Vinculo de pagamento Asaas indisponivel.');
        }

        throw new RuntimeException('Vinculo de pagamento Asaas indisponivel.');
    }

    public function requireReview(string $organizationRef, string $orderRef): void
    {
        $this->assertAutocommitBoundary();
        try {
            $this->database->affectingStatement(
                "UPDATE crm_lab_asaas_order_reservations SET state = 'review_required'
                 WHERE organization_ref = ?::uuid AND order_ref = ?::uuid
                   AND state = 'reserved' AND provider_payment_id IS NULL",
                [$organizationRef, $orderRef],
            );
        } catch (Throwable) {
            throw new RuntimeException('Pedido exige conciliacao manual.');
        }
    }

    public function assertAutocommitBoundary(): void
    {
        $this->guard();
        if ($this->database->transactionLevel() !== 0) {
            throw new RuntimeException('Revisao de pagamento proibida durante transacao.');
        }
    }

    private static function answer(string $status): array
    {
        return [
            'status_code' => $status,
            'informational_only' => true,
            'can_charge' => false,
            'can_mark_paid' => false,
            'can_activate' => false,
        ];
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_ORDER_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Revisor de pagamento limitado a CI descartavel.');
        }
        try {
            $role = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Papel de revisao indisponivel.');
        }
        if (($role->role_name ?? null) !== 'crm_billing_order_review_ci') {
            throw new RuntimeException('Papel de revisao nao autorizado.');
        }
    }
}
