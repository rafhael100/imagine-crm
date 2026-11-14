<?php

declare(strict_types=1);

namespace App\Core\Billing;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use DateTimeImmutable;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-05: sandbox-only PIX invoice for synthetic CRM trial + quote.
 *
 * This candidate is not wired to a route, controller, scheduler or webhook.
 * It never uses live credentials, real customer data or production endpoints.
 * A provider response never authorizes CRM access, settlement or a real charge.
 */
final readonly class AsaasSandboxPixPaymentGateway
{
    private const API = 'https://api-sandbox.asaas.com/v3';

    public function __construct(
        private Factory $http,
        #[\SensitiveParameter] private string $sandboxApiKey,
    ) {
        if (!app()->environment('local', 'testing')
            || getenv('CRM_ASAAS_SANDBOX_ENABLED') !== 'true'
            || getenv('CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED') !== 'true'
            || !str_starts_with($sandboxApiKey, '$aact_hmlg_')
            || strlen($sandboxApiKey) < 24 || strlen($sandboxApiKey) > 255
            || preg_match('/\s/', $sandboxApiKey) === 1) {
            throw new RuntimeException('Pagamento Asaas sandbox nao habilitado.');
        }
    }

    /**
     * All identifiers/dates are non-PII synthetic laboratory fixtures.
     *
     * The internal reference catalog is advisory; no production prices or
     * entitlements have been approved. The trial start date is unverified.
     *
     * @param array<string,mixed> $sandboxCustomer Result of the sandbox
     *                                              customer lookup candidate
     * @return array{
     *  status_code:'sandbox_pix_payment_found'|'sandbox_pix_payment_created',
     *  provider_payment_id:string,
     *  amount_cents:int,
     *  plan_code:string,
     *  billing_type:'PIX',
     *  environment:'sandbox',
     *  can_charge:false,can_activate:false,
     *  payment_confirmed:false,can_create_subscription:false,
     *  company_owner_verified:false,trial_start_verified:false,
     *  price_homologated:false,reconciliation_required:true
     * }
     */
    public function findOrCreateSyntheticPixCharge(
        array $sandboxCustomer,
        string $planCode,
        string $externalReference,
        string $trialStartsOn,
        string $dueDate,
    ): array {
        self::validateCustomer($sandboxCustomer);
        self::validateReferenceAndTrial($externalReference, $trialStartsOn, $dueDate);
        $quote = CommercialPlanReferenceCatalog::preview($planCode, 1);
        if (($quote['can_checkout'] ?? null) !== false
            || ($quote['can_charge'] ?? null) !== false
            || ($quote['price_homologated'] ?? null) !== false) {
            throw new InvalidArgumentException('Cotacao de laboratorio nao permitida.');
        }
        $customerId = $sandboxCustomer['provider_customer_id'];
        $cents = $quote['monthly_cents'];

        // This boundary is only for one synthetic fixed-price PIX invoice,
        // never a recurring subscription, actual Pix Automatic, card or split.
        try {
            $lookup = $this->request()->get(self::API.'/payments', [
                'externalReference' => $externalReference,
                'customer' => $customerId,
                'limit' => 2,
            ]);
            if (!$lookup->successful()) {
                throw new RuntimeException('Consulta sandbox de pagamento falhou.');
            }
            $list = $lookup->json();
            if (!is_array($list)
                || !is_array($list['data'] ?? null)
                || !array_is_list($list['data'])
                || ($list['hasMore'] ?? false) !== false
                || count($list['data']) > 1) {
                throw new RuntimeException('Pagamento sandbox ambiguo.');
            }
            if (count($list['data']) === 1) {
                $found = $list['data'][0];
                if (!self::matchesExpectedCharge(
                    $found, $customerId, $cents, $externalReference, $dueDate,
                )) {
                    throw new RuntimeException('Pagamento sandbox nao corresponde ao pedido.');
                }
                return self::answer($found['id'], $cents, $planCode, 'sandbox_pix_payment_found');
            }

            // No automatic retry of POST after timeout/uncertain response:
            // reconciliation by externalReference is required first.
            $created = $this->request()->post(self::API.'/payments', [
                'customer' => $customerId,
                'billingType' => 'PIX',
                'value' => $cents / 100,
                'dueDate' => $dueDate,
                'externalReference' => $externalReference,
                'description' => 'LAB-IMAGINE-CRM-'.$planCode.'-FICTICIO',
                'postalService' => false,
            ]);
            if (!$created->successful()
                || !self::matchesExpectedCharge(
                    $created->json(), $customerId, $cents, $externalReference, $dueDate,
                )) {
                throw new RuntimeException('Criacao sandbox de pagamento nao confirmada.');
            }
            return self::answer($created->json('id'), $cents, $planCode, 'sandbox_pix_payment_created');
        } catch (Throwable) {
            // Do not leak token, customer reference, provider response or PII.
            throw new RuntimeException('Operacao PIX sandbox inconclusiva.');
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

    private static function validateCustomer(array $customer): void
    {
        $requiredFalse = [
            'can_activate', 'can_charge', 'can_create_subscription',
            'company_owner_verified',
        ];
        if (($customer['environment'] ?? null) !== 'sandbox'
            || !in_array($customer['status_code'] ?? null, [
                'sandbox_customer_found', 'sandbox_customer_created',
            ], true)
            || !self::validCustomerId($customer['provider_customer_id'] ?? null)) {
            throw new InvalidArgumentException('Cliente Asaas de laboratorio invalido.');
        }
        foreach ($requiredFalse as $flag) {
            if (!array_key_exists($flag, $customer) || $customer[$flag] !== false) {
                throw new InvalidArgumentException('Cliente Asaas de laboratorio invalido.');
            }
        }
        if (array_diff(array_keys($customer), [
            'provider_customer_id', 'status_code', 'environment',
            'can_activate', 'can_charge', 'can_create_subscription',
            'company_owner_verified',
        ]) !== []) {
            throw new InvalidArgumentException('Cliente Asaas de laboratorio invalido.');
        }
    }

    private static function validateReferenceAndTrial(
        string $externalReference,
        string $trialStartsOn,
        string $dueDate,
    ): void {
        // Prefix and UUID prevent arbitrary order references, account fields
        // or customer-identifying content from entering provider payloads.
        if (preg_match(
            '/\ALAB-IMAGINE-ORDER-[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/D',
            $externalReference,
        ) !== 1) {
            throw new InvalidArgumentException('Pedido de laboratorio invalido.');
        }

        $trial = self::date($trialStartsOn);
        $due = self::date($dueDate);
        // 14-day trial is a planning rule, NOT entitlement validation.
        if ($trial === null || $due === null
            || $due < $trial->modify('+14 days')
            || $due > $trial->modify('+30 days')) {
            throw new InvalidArgumentException('Periodo de laboratorio invalido.');
        }
    }

    private static function date(string $date): ?DateTimeImmutable
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $date) !== 1) {
            return null;
        }
        $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $value instanceof DateTimeImmutable && $value->format('Y-m-d') === $date
            ? $value : null;
    }

    private static function validCustomerId(mixed $id): bool
    {
        return is_string($id)
            && preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $id) === 1;
    }

    private static function validPaymentId(mixed $id): bool
    {
        return is_string($id)
            && preg_match('/\Apay_[A-Za-z0-9]{5,64}\z/D', $id) === 1;
    }

    private static function matchesExpectedCharge(
        mixed $record,
        string $customerId,
        int $cents,
        string $externalReference,
        string $dueDate,
    ): bool {
        if (!is_array($record) || array_is_list($record)
            || !self::validPaymentId($record['id'] ?? null)
            || ($record['customer'] ?? null) !== $customerId
            || ($record['billingType'] ?? null) !== 'PIX'
            || ($record['externalReference'] ?? null) !== $externalReference
            || ($record['dueDate'] ?? null) !== $dueDate
            || ($record['status'] ?? null) !== 'PENDING') {
            return false;
        }
        $value = $record['value'] ?? null;
        return (is_int($value) || is_float($value))
            && is_finite((float) $value)
            && abs(((float) $value * 100) - $cents) < 0.001;
    }

    private static function answer(
        string $id, int $cents, string $planCode, string $status,
    ): array {
        return [
            'provider_payment_id' => $id,
            'status_code' => $status,
            'amount_cents' => $cents,
            'plan_code' => $planCode,
            'billing_type' => 'PIX',
            'environment' => 'sandbox',
            'can_charge' => false,
            'can_activate' => false,
            'payment_confirmed' => false,
            'can_create_subscription' => false,
            'company_owner_verified' => false,
            'trial_start_verified' => false,
            'price_homologated' => false,
            'reconciliation_required' => true,
        ];
    }
}
