<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxPixReconciliationInspector;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/** Every provider response is fake. Reconciliation is review-only, no grants. */
final class AsaasSandboxPixReconciliationInspectorTest extends TestCase
{
    private const API_KEY = '$aact_hmlg_CI-only-not-a-working-key';
    private const REFERENCE = 'LAB-IMAGINE-ORDER-11111111-2222-4333-8444-555555555555';
    private const PAYMENT_ID = 'pay_CI12345678';
    private const CUSTOMER_ID = 'cus_CI12345678';
    private const DUE = '2026-12-15';

    private array $prior;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prior = [
            getenv('CRM_ASAAS_SANDBOX_ENABLED'),
            getenv('CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED'),
        ];
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        putenv('CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED=true');
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        foreach (['CRM_ASAAS_SANDBOX_ENABLED', 'CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED'] as $i => $var) {
            $previous = $this->prior[$i];
            putenv($previous === false ? $var : $var.'='.$previous);
        }
        parent::tearDown();
    }

    private function inspector(string $key = self::API_KEY): AsaasSandboxPixReconciliationInspector
    {
        return new AsaasSandboxPixReconciliationInspector(Http::getFacadeRoot(), $key);
    }

    private function customer(): array
    {
        return [
            'provider_customer_id' => self::CUSTOMER_ID,
            'status_code' => 'sandbox_customer_found',
            'environment' => 'sandbox',
            'can_activate' => false,
            'can_charge' => false,
            'can_create_subscription' => false,
            'company_owner_verified' => false,
        ];
    }

    private function charge(): array
    {
        return [
            'provider_payment_id' => self::PAYMENT_ID,
            'status_code' => 'sandbox_pix_payment_created',
            'amount_cents' => 6900,
            'plan_code' => 'basico',
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

    private function providerRecord(string $status = 'PENDING'): array
    {
        return [
            'id' => self::PAYMENT_ID,
            'customer' => self::CUSTOMER_ID,
            'externalReference' => self::REFERENCE,
            'billingType' => 'PIX',
            'value' => 69.0,
            'dueDate' => self::DUE,
            'status' => $status,
            'invoiceUrl' => 'https://not-a-real-payment.example/ignore', // never returned
            'customerEmail' => 'fake@example.test', // never returned
        ];
    }

    public static function providerStatuses(): array
    {
        return [
            'pending' => ['PENDING', 'awaiting_payment'],
            'late' => ['OVERDUE', 'overdue_review'],
            'confirmed_not_received' => ['CONFIRMED', 'confirmed_not_received'],
            'received_must_not_grant' => ['RECEIVED', 'received_review_required'],
            'refunded' => ['REFUNDED', 'reversal_or_risk_review'],
            'partially_refunded' => ['PARTIALLY_REFUNDED', 'reversal_or_risk_review'],
            'deleted' => ['DELETED', 'reversal_or_risk_review'],
            'chargeback_requested' => ['CHARGEBACK_REQUESTED', 'reversal_or_risk_review'],
            'chargeback_dispute' => ['CHARGEBACK_DISPUTE', 'reversal_or_risk_review'],
            'reversal_pending' => ['AWAITING_CHARGEBACK_REVERSAL', 'reversal_or_risk_review'],
        ];
    }

    #[DataProvider('providerStatuses')]
    public function test_all_provider_statuses_remain_review_only(
        string $providerStatus, string $expectedClassification,
    ): void {
        Http::fake([
            'api-sandbox.asaas.com/v3/payments/*' =>
                Http::response($this->providerRecord($providerStatus), 200),
        ]);

        $result = $this->inspector()->inspectSyntheticPixPayment(
            $this->customer(), $this->charge(), self::REFERENCE, self::DUE,
        );

        $this->assertSame('sandbox', $result['environment']);
        $this->assertSame('PIX', $result['billing_type']);
        $this->assertSame(6900, $result['amount_cents']);
        $this->assertSame($providerStatus, $result['provider_status']);
        $this->assertSame($expectedClassification, $result['review_code']);
        foreach ([
            'can_activate', 'can_mark_paid', 'can_notify',
            'can_create_subscription', 'settlement_confirmed', 'production_approved',
        ] as $flag) {
            $this->assertFalse($result[$flag]);
        }
        $this->assertTrue($result['reconciliation_required']);

        $data = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([self::API_KEY, self::REFERENCE, self::PAYMENT_ID,
            'not-a-real-payment.example', 'fake@example.test'] as $secret) {
            $this->assertStringNotContainsString($secret, $data);
        }
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) =>
            $r->method() === 'GET'
            && $r->url() === 'https://api-sandbox.asaas.com/v3/payments/'.self::PAYMENT_ID
            && $r->hasHeader('access_token', self::API_KEY)
            && $r->hasHeader('User-Agent', 'ImagineCRM/0.1 (sandbox)')
            && !$r->hasHeader('asaas-access-token'));
        Http::assertNotSent(fn (Request $r) => $r->method() !== 'GET');
    }

    public static function mismatchedFields(): array
    {
        return [
            'wrong_customer' => ['customer', 'cus_UNEXPECTED999'],
            'wrong_ref' => ['externalReference', 'real-order-001'],
            'wrong_due' => ['dueDate', '2027-01-01'],
            'wrong_amount' => ['value', 999.0],
            'wrong_method' => ['billingType', 'CREDIT_CARD'],
            'different_payment' => ['id', 'pay_WRONG12345'],
            'unknown_state' => ['status', 'CAPTURED_UNREVIEWED'],
            'null_state' => ['status', null],
            'string_amount' => ['value', '69'],
        ];
    }

    #[DataProvider('mismatchedFields')]
    public function test_provider_mismatch_never_unlocks_anything(
        string $field, mixed $value,
    ): void {
        $record = $this->providerRecord('RECEIVED');
        $record[$field] = $value;
        Http::fake([
            'api-sandbox.asaas.com/v3/payments/*' => Http::response($record, 200),
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reconciliacao PIX sandbox inconclusiva.');
        try {
            $this->inspector()->inspectSyntheticPixPayment(
                $this->customer(), $this->charge(), self::REFERENCE, self::DUE,
            );
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_invalid_local_customer_and_payment_claims_deny_before_http(): void
    {
        Http::fake();
        foreach ([
            [array_merge($this->customer(), ['environment' => 'production']), $this->charge()],
            [array_merge($this->customer(), ['can_activate' => true]), $this->charge()],
            [array_merge($this->customer(), ['provider_customer_id' => 'real-customer']), $this->charge()],
            [$this->customer(), array_merge($this->charge(), ['plan_code' => 'unknown'])],
            [$this->customer(), array_merge($this->charge(), ['amount_cents' => 1])],
            [$this->customer(), array_merge($this->charge(), ['can_mark_paid' => true])],
            [$this->customer(), array_merge($this->charge(), ['can_activate' => true])],
            [$this->customer(), array_merge($this->charge(), ['payment_confirmed' => true])],
        ] as [$customer, $charge]) {
            try {
                $this->inspector()->inspectSyntheticPixPayment(
                    $customer, $charge, self::REFERENCE, self::DUE,
                );
                $this->fail('Unsafe claim was permitted.');
            } catch (InvalidArgumentException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        foreach ([
            ['real-order', self::DUE],
            [self::REFERENCE, 'not-a-date'],
            [self::REFERENCE, '2026-02-30'],
        ] as [$reference, $due]) {
            try {
                $this->inspector()->inspectSyntheticPixPayment(
                    $this->customer(), $this->charge(), $reference, $due,
                );
                $this->fail('Invalid reference/date was accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    public static function providerErrors(): array
    {
        return [
            'server_failure' => [500, []],
            'not_found' => [404, []],
            'empty_json' => [200, []],
            'list_instead_of_detail' => [200, [['id' => 'pay_CI12345678']]],
        ];
    }

    #[DataProvider('providerErrors')]
    public function test_invalid_or_failed_provider_response_is_generic(
        int $httpCode, array $body,
    ): void {
        Http::fake([
            'api-sandbox.asaas.com/v3/payments/*' => Http::response($body, $httpCode),
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reconciliacao PIX sandbox inconclusiva.');
        try {
            $this->inspector()->inspectSyntheticPixPayment(
                $this->customer(), $this->charge(), self::REFERENCE, self::DUE,
            );
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_disabled_flags_and_live_keys_block_even_read_only_queries(): void
    {
        Http::fake();
        foreach (['$aact_prod_live_fake', 'short', ''] as $key) {
            try {
                $this->inspector($key);
                $this->fail('Key was accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame(
                    'Consulta de pagamento Asaas sandbox nao habilitada.', $e->getMessage(),
                );
            }
        }
        foreach (['CRM_ASAAS_SANDBOX_ENABLED', 'CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED'] as $flag) {
            putenv($flag.'=false');
            try {
                $this->inspector();
                $this->fail('Disabled inspector accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame(
                    'Consulta de pagamento Asaas sandbox nao habilitada.', $e->getMessage(),
                );
            }
            putenv($flag.'=true');
        }
        Http::assertNothingSent();
    }
}
