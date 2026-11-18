<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-09: read-only lookup for Asaas Sandbox subscriptions.
 *
 * Does not create/modify any Asaas resource and cannot attest payment,
 * pricing approval, legal acceptance or application entitlements.
 * Not bound to a route or worker. Only local/testing with an explicit
 * sandbox flag and a sandbox-prefixed key.
 */
final readonly class AsaasSandboxSubscriptionLookup
{
    private const API = 'https://api-sandbox.asaas.com/v3';

    public function __construct(
        private Factory $http,
        #[\SensitiveParameter] private string $sandboxKey,
    ) {
        if (!app()->environment('testing', 'local')
            || getenv('CRM_ASAAS_SANDBOX_ENABLED') !== 'true'
            || !str_starts_with($sandboxKey, '$aact_hmlg_')
            || strlen($sandboxKey) < 24
            || strlen($sandboxKey) > 255
            || preg_match('/\s/', $sandboxKey) === 1) {
            throw new RuntimeException('Consulta de assinatura sandbox nao habilitada.');
        }
    }

    /**
     * @return array{
     *   status_code:'sandbox_subscription_found'|'sandbox_subscription_absent',
     *   provider_subscription_id:?string,
     *   informational_only:true,environment:'sandbox',
     *   ownership_of_company_verified:false,
     *   payment_verified:false,plan_approved:false,terms_accepted:false,
     *   can_create_subscription:false,can_charge:false,
     *   can_activate:false,can_invoice:false
     * }
     */
    public function byExternalReference(
        string $subscriptionReference,
        string $expectedCustomerId,
    ): array {
        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        if (preg_match($uuid, $subscriptionReference) !== 1
            || preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $expectedCustomerId) !== 1) {
            throw new InvalidArgumentException('Identificadores de consulta sandbox invalidos.');
        }

        try {
            $response = $this->http->withHeaders([
                'access_token' => $this->sandboxKey,
                'User-Agent' => 'ImagineCRM/0.1 (sandbox)',
                'Accept' => 'application/json',
            ])->asJson()->connectTimeout(3)->timeout(10)
                ->get(self::API.'/subscriptions', [
                    'externalReference' => $subscriptionReference,
                    'includeDeleted' => 'true',
                    'limit' => 2,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('Consulta sandbox nao respondeu.');
            }
            $body = $response->json();

            if (!is_array($body)
                || !is_array($body['data'] ?? null)
                || !array_is_list($body['data'])
                || ($body['hasMore'] ?? false) !== false
                || count($body['data']) > 1) {
                throw new RuntimeException('Resultado de assinatura sandbox ambiguo.');
            }

            if ($body['data'] === []) {
                return self::answer('sandbox_subscription_absent', null);
            }

            $subscription = $body['data'][0];
            if (!is_array($subscription)
                || ($subscription['externalReference'] ?? null) !== $subscriptionReference
                || ($subscription['customer'] ?? null) !== $expectedCustomerId
                || !is_string($subscription['id'] ?? null)
                || preg_match('/\Asub_[A-Za-z0-9]{5,64}\z/D', $subscription['id']) !== 1) {
                throw new RuntimeException('Assinatura sandbox nao corresponde ao pagador.');
            }

            // Even ACTIVE is a provider recurrence state, NOT proof that
            // the generated payment settled. Deliberately do not return
            // status/amount as evidence of an entitlement.
            return self::answer('sandbox_subscription_found', $subscription['id']);
        } catch (Throwable) {
            // Errors can contain personal data, provider IDs or credentials.
            // Do not chain them into logs or an eventual HTTP response.
            throw new RuntimeException('Consulta de assinatura Asaas sandbox inconclusiva.');
        }
    }

    private static function answer(string $status, ?string $id): array
    {
        return [
            'status_code' => $status,
            'provider_subscription_id' => $id,
            'informational_only' => true,
            'environment' => 'sandbox',
            'ownership_of_company_verified' => false,
            'payment_verified' => false,
            'plan_approved' => false,
            'terms_accepted' => false,
            'can_create_subscription' => false,
            'can_charge' => false,
            'can_activate' => false,
            'can_invoice' => false,
        ];
    }
}
