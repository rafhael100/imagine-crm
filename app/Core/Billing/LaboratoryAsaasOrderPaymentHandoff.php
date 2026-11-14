<?php

declare(strict_types=1);

namespace App\Core\Billing;

use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-INT-03: CI-only handoff of an already synthetic customer -> order.
 *
 * A committed order reservation precedes any mocked provider GET/POST.
 * Existing/conflicting reservations never retry HTTP. Linkage uses a separate
 * SQL reviewer role. Neither a provider response nor a link grants CRM access.
 * This class is NOT registered as a route, listener, worker or scheduler.
 */
final readonly class LaboratoryAsaasOrderPaymentHandoff
{
    public function __construct(
        private LaboratoryAsaasOrderReservation $orders,
        private LaboratoryAsaasOrderPaymentReviewer $reviewer,
        private AsaasSandboxPixPaymentGateway $sandbox,
    ) {}

    /**
     * @param array<string,mixed> $syntheticCustomer
     * @return array{status_code:'sandbox_payment_linked_review_only'|'sandbox_payment_manual_review_required',
     *     provider_request_may_have_occurred:bool,informational_only:true,
     *     can_checkout:false,can_charge:false,can_activate:false,can_mark_paid:false}
     */
    public function run(
        string $organizationRef,
        string $orderRef,
        array $syntheticCustomer,
        string $planCode,
        string $trialStartsOn,
        string $dueOn,
    ): array {
        $this->orders->assertAutocommitBoundary();
        $this->reviewer->assertAutocommitBoundary();
        $customerId = $syntheticCustomer['provider_customer_id'] ?? null;
        if (!is_string($customerId)
            || ($syntheticCustomer['environment'] ?? null) !== 'sandbox'
            || !in_array($syntheticCustomer['status_code'] ?? null,
                ['sandbox_customer_found', 'sandbox_customer_created'], true)
            || ($syntheticCustomer['can_charge'] ?? true) !== false
            || ($syntheticCustomer['can_activate'] ?? true) !== false
            || ($syntheticCustomer['can_create_subscription'] ?? true) !== false
            || ($syntheticCustomer['company_owner_verified'] ?? true) !== false) {
            throw new RuntimeException('Cliente ficticio invalido para pedido.');
        }

        // Physical INSERT commits before provider I/O and guarantees one winner.
        $claim = $this->orders->reserve(
            $organizationRef, $orderRef, $customerId, $planCode, $dueOn,
        );
        if ($claim['may_attempt_sandbox_once'] !== true) {
            return self::answer('sandbox_payment_manual_review_required', false);
        }

        try {
            $result = $this->sandbox->findOrCreateSyntheticPixCharge(
                $syntheticCustomer, $planCode, 'LAB-IMAGINE-ORDER-'.$orderRef,
                $trialStartsOn, $dueOn,
            );
            if (($result['environment'] ?? null) !== 'sandbox'
                || !in_array($result['status_code'] ?? null, [
                    'sandbox_pix_payment_found', 'sandbox_pix_payment_created',
                ], true)
                || ($result['plan_code'] ?? null) !== $planCode
                || ($result['can_charge'] ?? true) !== false
                || ($result['can_activate'] ?? true) !== false
                || ($result['payment_confirmed'] ?? true) !== false
                || ($result['reconciliation_required'] ?? null) !== true
                || !is_string($result['provider_payment_id'] ?? null)) {
                throw new RuntimeException('Pagamento ficticio inconclusivo.');
            }

            // The separate reviewer checks all immutable stored dimensions.
            $this->reviewer->bind(
                $organizationRef, $orderRef, $customerId, $planCode, $dueOn,
                $result['provider_payment_id'],
            );
            return self::answer('sandbox_payment_linked_review_only', true);
        } catch (Throwable) {
            try {
                $this->reviewer->requireReview($organizationRef, $orderRef);
            } catch (Throwable) {
                // An unresolved reservation still blocks another provider POST.
            }
            return self::answer('sandbox_payment_manual_review_required', true);
        }
    }

    private static function answer(string $status, bool $request): array
    {
        return [
            'status_code' => $status,
            'provider_request_may_have_occurred' => $request,
            'informational_only' => true,
            'can_checkout' => false,
            'can_charge' => false,
            'can_activate' => false,
            'can_mark_paid' => false,
        ];
    }
}
