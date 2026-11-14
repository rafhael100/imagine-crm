<?php

declare(strict_types=1);

namespace App\Core\Billing;

use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-06: isolated sandbox customer lifecycle coordinator.
 *
 * Physical reservation is durable *before* the first provider request.
 * A second process sees an existing reservation and may NOT try POST again.
 * An uncertain HTTP/DB result requires manual reconciliation. The shared
 * sandbox gateway remains restricted to synthetic data and never charges.
 *
 * Intentionally NOT published in routes, container bindings or a worker.
 */
final readonly class LaboratoryAsaasCustomerHandoff
{
    public function __construct(
        private LaboratoryAsaasCustomerLink $links,
        private AsaasSandboxCustomerGateway $sandbox,
    ) {}

    /**
     * @return array{
     *   status_code:'sandbox_linked'|'sandbox_already_linked'|'sandbox_manual_review_required',
     *   provider_request_may_have_occurred:bool,
     *   can_register:false,can_activate:false,can_charge:false,
     *   company_owner_verified:false,informational_only:true
     * }
     */
    public function run(string $organizationRef, string $externalReference, int $reservedAt): array
    {
        // This call verifies CI-only role/environment and reserves a physical
        // organization/reference pair using the global PostgreSQL PK/index.
        $this->links->assertAutocommitBoundary();
        $claim = $this->links->reserve($organizationRef, $externalReference, $reservedAt);
        if ($claim['status_code'] === 'already_linked') {
            return self::answer('sandbox_already_linked', false);
        }
        if (!$claim['may_attempt_sandbox_once']) {
            // A previous process may already have issued POST, even when its
            // response was lost. Never automatically retry/recreate.
            return self::answer('sandbox_manual_review_required', false);
        }

        try {
            $provider = $this->sandbox->findOrCreateSyntheticCustomer(
                'LAB-IMAGINE-CLIENTE-FICTICIO', '11222333000181', $externalReference,
            );
            if (($provider['environment'] ?? null) !== 'sandbox'
                || !in_array($provider['status_code'] ?? '', [
                    'sandbox_customer_found', 'sandbox_customer_created',
                ], true)
                || ($provider['can_charge'] ?? true) !== false) {
                throw new RuntimeException('Resposta externa ainda nao confiavel.');
            }
            $this->links->bind($organizationRef, $externalReference, $provider['provider_customer_id']);
            return self::answer('sandbox_linked', true);
        } catch (Throwable) {
            // Regardless of network/binding error, lock the reservation into
            // manual review; retry cannot initiate a new POST in any case.
            try {
                $this->links->requireReview($organizationRef, $externalReference);
            } catch (Throwable) {
                // Preserve deny; the unresolved reservation also denies POST.
            }
            return self::answer('sandbox_manual_review_required', true);
        }
    }

    private static function answer(string $status, bool $attempted): array
    {
        return [
            'status_code' => $status,
            'provider_request_may_have_occurred' => $attempted,
            'can_register' => false,
            'can_activate' => false,
            'can_charge' => false,
            'company_owner_verified' => false,
            'informational_only' => true,
        ];
    }
}
