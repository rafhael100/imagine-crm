<?php

declare(strict_types=1);

namespace App\Core\Billing;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-06: read-only, sandbox-only, server-to-server PIX status inspection.
 *
 * It is deliberately NOT an entitlement/settlement decision. A "RECEIVED"
 * provider snapshot is an observation, never an authorization to grant CRM
 * access. No routes, workers, real tenants, provider tokens or webhooks.
 */
final readonly class AsaasSandboxPixReconciliationInspector
{
    private const API = 'https://api-sandbox.asaas.com/v3';

    /** @var array<string,string> */
    private const CLASSIFICATION = [
        'PENDING' => 'awaiting_payment',
        'OVERDUE' => 'overdue_review',
        'CONFIRMED' => 'confirmed_not_received',
        'RECEIVED' => 'received_review_required',
        'REFUNDED' => 'reversal_or_risk_review',
        'PARTIALLY_REFUNDED' => 'reversal_or_risk_review',
        'DELETED' => 'reversal_or_risk_review',
        'CHARGEBACK_REQUESTED' => 'reversal_or_risk_review',
        'CHARGEBACK_DISPUTE' => 'reversal_or_risk_review',
        'AWAITING_CHARGEBACK_REVERSAL' => 'reversal_or_risk_review',
    ];

    public function __construct(
        private Factory $http,
        #[\SensitiveParameter] private string $sandboxApiKey,
    ) {
        if (!app()->environment('testing', 'local')
            || getenv('CRM_ASAAS_SANDBOX_ENABLED') !== 'true'
            || getenv('CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED') !== 'true'
            || !str_starts_with($sandboxApiKey, '$aact_hmlg_')
            || strlen($sandboxApiKey) < 24 || strlen($sandboxApiKey) > 255
            || preg_match('/\s/', $sandboxApiKey) === 1) {
            throw new RuntimeException('Consulta de pagamento Asaas sandbox nao habilitada.');
        }
    }

    /**
     * @param array<string,mixed> $sandboxCustomer The BILL-ASAAS-04 outcome
     * @param array<string,mixed> $sandboxCharge   The BILL-ASAAS-05 outcome
     *
     * @return array{
     *  provider_status:string,
     *  review_code:string,
     *  billing_type:'PIX',
     *  environment:'sandbox',
     *  amount_cents:int,
     *  can_activate:false,can_mark_paid:false,can_notify:false,
     *  can_create_subscription:false,settlement_confirmed:false,
     *  reconciliation_required:true,production_approved:false
     * }
     */
    public function inspectSyntheticPixPayment(
        array $sandboxCustomer,
        array $sandboxCharge,
        string $externalReference,
        string $dueDate,
    ): array {
        self::validateSyntheticInputs(
            $sandboxCustomer, $sandboxCharge, $externalReference, $dueDate,
        );
        $paymentId = $sandboxCharge['provider_payment_id'];
        try {
            // Only a direct GET on a fully validated fixed Asaas payment ID.
            // Never follow client-originated URLs or send a request body.
            $response = $this->request()->get(self::API.'/payments/'.$paymentId);
            if (!$response->successful()) {
                throw new RuntimeException('Consulta sandbox inconclusiva.');
            }
            $record = $response->json();
            if (!is_array($record) || array_is_list($record)
                || ($record['id'] ?? null) !== $paymentId
                || ($record['customer'] ?? null) !== $sandboxCustomer['provider_customer_id']
                || ($record['externalReference'] ?? null) !== $externalReference
                || ($record['dueDate'] ?? null) !== $dueDate
                || ($record['billingType'] ?? null) !== 'PIX'
                || !self::sameAmount($record['value'] ?? null, $sandboxCharge['amount_cents'])
                || !is_string($record['status'] ?? null)
                || !array_key_exists($record['status'], self::CLASSIFICATION)) {
                throw new RuntimeException('Divergencia de conciliação sandbox.');
            }

            // Intentionally DENY ALL business effects even for RECEIVED.
            return [
                'provider_status' => $record['status'],
                'review_code' => self::CLASSIFICATION[$record['status']],
                'billing_type' => 'PIX',
                'environment' => 'sandbox',
                'amount_cents' => $sandboxCharge['amount_cents'],
                'can_activate' => false,
                'can_mark_paid' => false,
                'can_notify' => false,
                'can_create_subscription' => false,
                'settlement_confirmed' => false,
                'reconciliation_required' => true,
                'production_approved' => false,
            ];
        } catch (Throwable) {
            // Do not leak payment IDs, provider details, references or tokens.
            throw new RuntimeException('Reconciliacao PIX sandbox inconclusiva.');
        }
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http->withHeaders([
            'access_token' => $this->sandboxApiKey,
            'User-Agent' => 'ImagineCRM/0.1 (sandbox)',
            'Accept' => 'application/json',
        ])->asJson()->timeout(10)->connectTimeout(3);
    }

    private static function validateSyntheticInputs(
        array $customer, array $charge, string $reference, string $dueDate,
    ): void {
        if (($customer['environment'] ?? null) !== 'sandbox'
            || !in_array($customer['status_code'] ?? null, [
                'sandbox_customer_found', 'sandbox_customer_created',
            ], true)
            || !self::validId($customer['provider_customer_id'] ?? null, 'cus_')
            || ($customer['can_activate'] ?? null) !== false
            || ($customer['can_charge'] ?? null) !== false
            || ($customer['can_create_subscription'] ?? null) !== false
            || ($customer['company_owner_verified'] ?? null) !== false
            || array_diff(array_keys($customer), [
                'provider_customer_id', 'status_code', 'environment',
                'can_activate', 'can_charge', 'can_create_subscription',
                'company_owner_verified',
            ]) !== []) {
            throw new InvalidArgumentException('Cliente de reconciliacao de laboratorio invalido.');
        }
        if (($charge['environment'] ?? null) !== 'sandbox'
            || !in_array($charge['status_code'] ?? null, [
                'sandbox_pix_payment_created', 'sandbox_pix_payment_found',
            ], true)
            || !self::validId($charge['provider_payment_id'] ?? null, 'pay_')
            || !in_array($charge['plan_code'] ?? null, [
                'basico', 'essencial', 'profissional', 'empresarial',
            ], true)
            || !is_int($charge['amount_cents'] ?? null)
            || ($charge['billing_type'] ?? null) !== 'PIX'
            || ($charge['can_charge'] ?? null) !== false
            || ($charge['can_activate'] ?? null) !== false
            || ($charge['payment_confirmed'] ?? null) !== false
            || ($charge['can_create_subscription'] ?? null) !== false
            || ($charge['company_owner_verified'] ?? null) !== false
            || ($charge['trial_start_verified'] ?? null) !== false
            || ($charge['price_homologated'] ?? null) !== false
            || ($charge['reconciliation_required'] ?? null) !== true
            || array_diff(array_keys($charge), [
                'provider_payment_id', 'status_code', 'amount_cents', 'plan_code',
                'billing_type', 'environment', 'can_charge', 'can_activate',
                'payment_confirmed', 'can_create_subscription',
                'company_owner_verified', 'trial_start_verified',
                'price_homologated', 'reconciliation_required',
            ]) !== []) {
            throw new InvalidArgumentException('Cobranca de reconciliacao de laboratorio invalida.');
        }
        $quote = CommercialPlanReferenceCatalog::preview($charge['plan_code'], 1);
        if ($charge['amount_cents'] !== $quote['monthly_cents']
            || ($quote['can_charge'] ?? null) !== false
            || ($quote['price_homologated'] ?? null) !== false
            || preg_match(
                '/\ALAB-IMAGINE-ORDER-[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/D',
                $reference,
            ) !== 1
            || !self::validDate($dueDate)) {
            throw new InvalidArgumentException('Pedido de reconciliacao de laboratorio invalido.');
        }
    }

    private static function validId(mixed $value, string $prefix): bool
    {
        return is_string($value)
            && preg_match('/\A'.preg_quote($prefix, '/').'[A-Za-z0-9]{5,64}\z/D', $value) === 1;
    }

    private static function validDate(string $date): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $date) !== 1) {
            return false;
        }
        $object = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $object instanceof \DateTimeImmutable && $object->format('Y-m-d') === $date;
    }

    private static function sameAmount(mixed $value, int $cents): bool
    {
        return (is_int($value) || is_float($value))
            && is_finite((float) $value)
            && abs(((float) $value * 100) - $cents) < 0.001;
    }
}
