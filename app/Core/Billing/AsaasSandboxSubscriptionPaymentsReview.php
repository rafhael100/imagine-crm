<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-11: read-only view of already generated subscription payments.
 *
 * NEVER establishes billing/entitlement rights. Provider-reported payment
 * status is only an input for a separate future reconciliation process.
 * Sandbox-only, not registered on a route, worker or public endpoint.
 */
final readonly class AsaasSandboxSubscriptionPaymentsReview
{
    private const URL = 'https://api-sandbox.asaas.com/v3';
    private const MAX_RESULTS = 50;

    private const REVERSED = [
        'REFUNDED', 'PARTIALLY_REFUNDED', 'REFUND_IN_PROGRESS',
        'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE',
        'AWAITING_CHARGEBACK_REVERSAL', 'RECEIVED_IN_CASH_UNDONE',
    ];

    private const KNOWN = [
        'PENDING', 'OVERDUE', 'CONFIRMED', 'RECEIVED',
        'REFUNDED', 'PARTIALLY_REFUNDED', 'REFUND_IN_PROGRESS',
        'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE',
        'AWAITING_CHARGEBACK_REVERSAL', 'RECEIVED_IN_CASH_UNDONE',
        'DELETED', 'DUNNING_REQUESTED', 'DUNNING_RECEIVED',
        'AWAITING_RISK_ANALYSIS', 'AUTHORIZED',
    ];

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
            throw new RuntimeException('Consulta de cobrancas sandbox nao habilitada.');
        }
    }

    /**
     * @return array{
     *  signal_code:'synthetic_none_generated'|'synthetic_pending_review'|
     *              'synthetic_provider_receipt_review'|'synthetic_reversal_risk_review',
     *  payment_count:int,receipt_signals:int,reversal_signals:int,
     *  informational_only:true,complete_page:true,
     *  payment_verified:false,subscription_payment_verified:false,
     *  can_charge:false,can_activate:false,can_invoice:false,
     *  can_reenable:false
     * }
     */
    public function inspect(string $subscriptionId): array
    {
        if (preg_match('/\Asub_[A-Za-z0-9]{5,64}\z/D', $subscriptionId) !== 1) {
            throw new InvalidArgumentException('Assinatura sandbox de consulta invalida.');
        }

        try {
            $resp = $this->http->withHeaders([
                'access_token' => $this->sandboxKey,
                'User-Agent' => 'ImagineCRM/0.1 (sandbox)',
                'Accept' => 'application/json',
            ])->asJson()->connectTimeout(3)->timeout(10)
                ->get(self::URL.'/subscriptions/'.$subscriptionId.'/payments', [
                    'limit' => self::MAX_RESULTS,
                ]);

            if (!$resp->successful()) {
                throw new RuntimeException('Lista de cobrancas indisponivel.');
            }

            $json = $resp->json();
            if (!is_array($json) || !is_array($json['data'] ?? null)
                || !array_is_list($json['data'])
                || ($json['hasMore'] ?? null) !== false
                || count($json['data']) > self::MAX_RESULTS) {
                // More than 50 generated payments requires an audited
                // paginated reconciliation; no truncated financial decisions.
                throw new RuntimeException('Lista de cobrancas incompleta.');
            }

            $seen = [];
            $receipts = 0;
            $reversals = 0;
            foreach ($json['data'] as $payment) {
                if (!is_array($payment)
                    || !is_string($payment['id'] ?? null)
                    || preg_match('/\Apay_[A-Za-z0-9]{5,64}\z/D', $payment['id']) !== 1
                    || ($payment['subscription'] ?? null) !== $subscriptionId
                    || !is_string($payment['status'] ?? null)
                    || !in_array($payment['status'], self::KNOWN, true)
                    || isset($seen[$payment['id']])) {
                    throw new RuntimeException('Cobrança sem vinculo verificavel.');
                }
                $seen[$payment['id']] = true;

                if (in_array($payment['status'], self::REVERSED, true)) {
                    $reversals++;
                } elseif (in_array($payment['status'], ['CONFIRMED', 'RECEIVED'], true)) {
                    $receipts++;
                }
            }

            $signal = $reversals > 0 ? 'synthetic_reversal_risk_review'
                : ($receipts > 0 ? 'synthetic_provider_receipt_review'
                : ($json['data'] === [] ? 'synthetic_none_generated'
                : 'synthetic_pending_review'));

            return [
                'signal_code' => $signal,
                'payment_count' => count($json['data']),
                'receipt_signals' => $receipts,
                'reversal_signals' => $reversals,
                'informational_only' => true,
                'complete_page' => true,
                'payment_verified' => false,
                'subscription_payment_verified' => false,
                'can_charge' => false,
                'can_activate' => false,
                'can_invoice' => false,
                'can_reenable' => false,
            ];
        } catch (Throwable) {
            // Do not expose provider IDs, amounts, personal data, or API key
            // in exception chains, logs, or eventual HTTP responses.
            throw new RuntimeException('Analise de cobrancas Asaas sandbox inconclusiva.');
        }
    }
}
