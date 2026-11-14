<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-04: real Asaas v3 *Sandbox* HTTP contract for customer reuse.
 *
 * Disabled unless explicitly enabled in isolated local/testing environments.
 * Never chooses a plan, generates a payment, invoices, grants access, or
 * creates a subscription. Not wired to any route or service provider.
 *
 * A local idempotency reservation + operator/consent validation are required
 * BEFORE a production service may ever invoke a customer-creation operation.
 */
final readonly class AsaasSandboxCustomerGateway
{
    private const API = 'https://api-sandbox.asaas.com/v3';

    public function __construct(
        private Factory $http,
        #[\SensitiveParameter] private string $sandboxApiKey,
    ) {
        if (!app()->environment('testing', 'local')
            || getenv('CRM_ASAAS_SANDBOX_ENABLED') !== 'true'
            || !str_starts_with($sandboxApiKey, '$aact_hmlg_')
            || strlen($sandboxApiKey) < 24 || strlen($sandboxApiKey) > 255
            || preg_match('/\s/', $sandboxApiKey) === 1) {
            throw new RuntimeException('Acesso Asaas sandbox nao habilitado.');
        }
    }

    /**
     * @return array{
     *  provider_customer_id:string,
     *  status_code:'sandbox_customer_found'|'sandbox_customer_created',
     *  environment:'sandbox',can_activate:false,can_charge:false,
     *  can_create_subscription:false,company_owner_verified:false
     * }
     */
    public function findOrCreateSyntheticCustomer(
        #[\SensitiveParameter] string $name,
        #[\SensitiveParameter] string $cnpj,
        string $externalReference,
    ): array {
        self::validateInputs($name, $cnpj, $externalReference);

        // Asaas recommends reusing an existing customer identifier rather
        // than creating a duplicate after a retry. This lookup alone cannot
        // solve concurrent creates; DB-level idempotency remains a release gate.
        try {
            $result = $this->request()->get(self::API.'/customers', [
                'externalReference' => $externalReference,
                'limit' => 2,
            ]);
            if (!$result->successful()) {
                throw new RuntimeException('Falha de consulta sandbox.');
            }
            $list = $result->json();
            if (!is_array($list) || !is_array($list['data'] ?? null)
                || !array_is_list($list['data'])
                || ($list['hasMore'] ?? false) !== false
                || count($list['data']) > 1) {
                throw new RuntimeException('Consulta sandbox ambigua.');
            }
            if (count($list['data']) === 1) {
                $record = $list['data'][0];
                if (!is_array($record)
                    || ($record['externalReference'] ?? null) !== $externalReference
                    || !self::validProviderId($record['id'] ?? null)) {
                    throw new RuntimeException('Identificacao sandbox invalida.');
                }
                return self::answer($record['id'], 'sandbox_customer_found');
            }

            // No production customer, payment, subscription or billing
            // operation is permitted by this laboratory-only adapter.
            $created = $this->request()->post(self::API.'/customers', [
                'name' => trim($name),
                'cpfCnpj' => $cnpj,
                'externalReference' => $externalReference,
                'notificationDisabled' => true,
            ]);
            if (!$created->successful()) {
                throw new RuntimeException('Criacao sandbox nao confirmada.');
            }
            $response = $created->json();
            if (!is_array($response)
                || ($response['externalReference'] ?? null) !== $externalReference
                || !self::validProviderId($response['id'] ?? null)) {
                throw new RuntimeException('Resposta de criacao sandbox invalida.');
            }
            return self::answer($response['id'], 'sandbox_customer_created');
        } catch (Throwable) {
            // Never include HTTP responses, credentials, request body, CNPJ
            // or remote exception causes in application output/logs.
            throw new RuntimeException('Operacao Asaas sandbox inconclusiva.');
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

    private static function validateInputs(
        string $name,
        string $cnpj,
        string $externalReference,
    ): void {
        // Explicitly SYNTHETIC fixture only. Never use real company data or
        // an unapproved contact name through this method.
        if (!str_starts_with($name, 'LAB-IMAGINE-')
            || strlen($name) < 13 || strlen($name) > 100
            || preg_match('/\A[A-Z0-9 -]+\z/D', $name) !== 1
            || $cnpj !== '11222333000181'
            || preg_match('/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD',
                $externalReference) !== 1) {
            throw new InvalidArgumentException('Cadastro Asaas sandbox ficticio invalido.');
        }
    }

    private static function validProviderId(mixed $id): bool
    {
        return is_string($id)
            && preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $id) === 1;
    }

    private static function answer(string $id, string $status): array
    {
        return [
            'provider_customer_id' => $id,
            'status_code' => $status,
            'environment' => 'sandbox',
            'can_activate' => false,
            'can_charge' => false,
            'can_create_subscription' => false,
            'company_owner_verified' => false,
        ];
    }
}
