<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxPixPaymentGateway;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * BILL-ASAAS-05: every HTTP call is intercepted and verified with Http::fake.
 * No real Asaas Sandbox or production API receives these tests.
 */
final class AsaasSandboxPixPaymentGatewayTest extends TestCase
{
    private const API_KEY = '$aact_hmlg_fake-token-for-tests-nonfunctional';
    private const EXTERNAL_REFERENCE = 'LAB-IMAGINE-ORDER-11111111-2222-4333-8444-555555555555';
    private const CUSTOMER_ID = 'cus_FAKE12345678';
    private const PAYMENT_ID = 'pay_FAKE12345678';
    private const TRIAL_START = '2026-12-01';
    private const DUE_DATE = '2026-12-15';

    private array $previousEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousEnvironment = [
            getenv('CRM_ASAAS_SANDBOX_ENABLED'),
            getenv('CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED'),
        ];
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        putenv('CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED=true');
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        foreach (['CRM_ASAAS_SANDBOX_ENABLED', 'CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED'] as $i => $name) {
            $previous = $this->previousEnvironment[$i];
            putenv($previous === false ? $name : $name.'='.$previous);
        }
        parent::tearDown();
    }

    private function gateway(string $key = self::API_KEY): AsaasSandboxPixPaymentGateway
    {
        return new AsaasSandboxPixPaymentGateway(Http::getFacadeRoot(), $key);
    }

    /**
     * Laravel accumulates fake callbacks when fake() is called repeatedly.
     * Each data-case must have an independent HTTP factory; otherwise stale
     * responses can hide POST requests or cause false-positive retries.
     */
    private function resetHttpFakes(): void
    {
        Http::swap(new Factory());
        Http::preventStrayRequests();
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

    private function runCharge(
        string $plan = 'basico', ?array $customer = null,
        string $reference = self::EXTERNAL_REFERENCE,
        string $trial = self::TRIAL_START,
        string $due = self::DUE_DATE,
    ): array {
        return $this->gateway()->findOrCreateSyntheticPixCharge(
            $customer ?? $this->customer(), $plan, $reference, $trial, $due,
        );
    }

    private function paymentRecord(int $cents = 6900): array
    {
        return [
            'id' => self::PAYMENT_ID,
            'customer' => self::CUSTOMER_ID,
            'billingType' => 'PIX',
            'value' => $cents / 100,
            'dueDate' => self::DUE_DATE,
            'externalReference' => self::EXTERNAL_REFERENCE,
            'status' => 'PENDING',
        ];
    }

    private function assertDenyOnlyResult(array $result, int $amount): void
    {
        $this->assertSame('sandbox', $result['environment']);
        $this->assertSame('PIX', $result['billing_type']);
        $this->assertSame($amount, $result['amount_cents']);
        $this->assertSame(self::PAYMENT_ID, $result['provider_payment_id']);
        foreach ([
            'can_charge', 'can_activate', 'payment_confirmed',
            'can_create_subscription', 'company_owner_verified',
            'trial_start_verified', 'price_homologated',
        ] as $flag) {
            $this->assertFalse($result[$flag]);
        }
        $this->assertTrue($result['reconciliation_required']);
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString(self::API_KEY, $json);
        $this->assertStringNotContainsString(self::EXTERNAL_REFERENCE, $json);
        $this->assertArrayNotHasKey('payment_url', $result);
    }

    public function test_existing_pending_sandbox_pix_is_reused_without_post(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/payments*' => fn (Request $request) =>
                Http::response(['hasMore' => false, 'data' => [$this->paymentRecord()]], 200),
        ]);
        $result = $this->runCharge();
        $this->assertSame('sandbox_pix_payment_found', $result['status_code']);
        $this->assertDenyOnlyResult($result, 6900);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) =>
            $r->method() === 'GET'
            && str_contains($r->url(), 'externalReference='.self::EXTERNAL_REFERENCE)
            && str_contains($r->url(), 'customer='.self::CUSTOMER_ID)
            && $r->hasHeader('access_token', self::API_KEY)
            && !$r->hasHeader('asaas-access-token')
            && $r->hasHeader('User-Agent', 'ImagineCRM/0.1 (sandbox)'));
    }

    public function test_synthetic_pix_creation_uses_one_plan_price_and_no_subscription(): void
    {
        $calls = 0;
        Http::fake([
            'api-sandbox.asaas.com/v3/payments*' =>
                function (Request $request) use (&$calls) {
                    $calls++;
                    if ($request->method() === 'GET') {
                        $this->assertStringContainsString(
                            'externalReference='.self::EXTERNAL_REFERENCE,
                            $request->url(),
                        );
                        return Http::response(['hasMore' => false, 'data' => []], 200);
                    }
                    $this->assertSame('POST', $request->method());
                    $this->assertSame(
                        'https://api-sandbox.asaas.com/v3/payments',
                        $request->url(),
                    );
                    $this->assertSame([
                        'customer' => self::CUSTOMER_ID,
                        'billingType' => 'PIX',
                        'value' => 89,
                        'dueDate' => self::DUE_DATE,
                        'externalReference' => self::EXTERNAL_REFERENCE,
                        'description' => 'LAB-IMAGINE-CRM-essencial-FICTICIO',
                        'postalService' => false,
                    ], $request->data());
                    return Http::response($this->paymentRecord(8900), 200);
                },
        ]);

        $result = $this->runCharge('essencial');
        $this->assertSame('sandbox_pix_payment_created', $result['status_code']);
        $this->assertSame('essencial', $result['plan_code']);
        $this->assertDenyOnlyResult($result, 8900);
        $this->assertSame(2, $calls);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $r) =>
            str_contains($r->url(), '/subscriptions')
            || str_contains($r->url(), '/paymentLinks')
            || str_contains($r->url(), '/checkout')
            || str_contains($r->url(), '/invoices'));
    }

    public function test_all_four_plan_amounts_use_only_internal_cents_without_discounts(): void
    {
        foreach (['basico' => 6900, 'essencial' => 8900, 'profissional' => 14900,
            'empresarial' => 29900] as $code => $cents) {
            $this->resetHttpFakes();
            Http::fake([
                'api-sandbox.asaas.com/v3/payments*' =>
                    fn (Request $r) => $r->method() === 'GET'
                        ? Http::response(['hasMore' => false, 'data' => []], 200)
                        : Http::response($this->paymentRecord($cents), 200),
            ]);
            $result = $this->runCharge($code);
            $this->assertDenyOnlyResult($result, $cents);
            Http::assertSent(fn (Request $r) =>
                $r->method() === 'POST'
                && is_numeric($r->data()['value'] ?? null)
                && abs((float) $r->data()['value'] * 100 - $cents) < 0.001
                && !array_key_exists('discount', $r->data())
                && !array_key_exists('interest', $r->data())
                && !array_key_exists('fine', $r->data())
                && !array_key_exists('split', $r->data())
                && !array_key_exists('installmentCount', $r->data()));
        }
    }

    public function test_production_flags_and_live_keys_fail_before_any_request(): void
    {
        Http::fake();
        foreach (['$aact_prod_live-like-token', 'invalid', ''] as $key) {
            try {
                $this->gateway($key);
                $this->fail('Unsafe Asaas secret accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame('Pagamento Asaas sandbox nao habilitado.', $e->getMessage());
            }
        }
        foreach (['CRM_ASAAS_SANDBOX_ENABLED', 'CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED'] as $name) {
            putenv($name.'=false');
            try {
                $this->gateway();
                $this->fail('Disabled sandbox payment flag accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame('Pagamento Asaas sandbox nao habilitado.', $e->getMessage());
            }
            putenv($name.'=true');
        }
        Http::assertNothingSent();
    }

    public function test_unapproved_customer_trial_reference_or_plan_does_not_send_http(): void
    {
        Http::fake();
        $invalidCustomers = [
            array_merge($this->customer(), ['environment' => 'production']),
            array_merge($this->customer(), ['can_charge' => true]),
            array_merge($this->customer(), ['can_activate' => true]),
            array_merge($this->customer(), ['provider_customer_id' => 'cus_real_customer']),
            array_merge($this->customer(), ['unexpected' => 'sensitive']),
        ];
        foreach ($invalidCustomers as $customer) {
            try {
                $this->runCharge(customer: $customer);
                $this->fail('Unreviewed customer was accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Cliente Asaas de laboratorio invalido.', $e->getMessage());
            }
        }
        foreach ([
            ['invalid-plan', self::EXTERNAL_REFERENCE, self::TRIAL_START, self::DUE_DATE],
            ['basico', 'real-order-007', self::TRIAL_START, self::DUE_DATE],
            ['basico', self::EXTERNAL_REFERENCE, '2026-12-01', '2026-12-10'],
            ['basico', self::EXTERNAL_REFERENCE, '2026-12-01', '2027-02-01'],
            ['basico', self::EXTERNAL_REFERENCE, '2026-02-30', self::DUE_DATE],
        ] as [$code, $ref, $trial, $due]) {
            $this->expectNoNetworkFor($code, $ref, $trial, $due);
        }
        Http::assertNothingSent();
    }

    private function expectNoNetworkFor(
        string $code, string $ref, string $trial, string $due,
    ): void {
        try {
            $this->runCharge($code, reference: $ref, trial: $trial, due: $due);
            $this->fail('Invalid sandbox trial/price was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotSame('', $e->getMessage());
        }
    }

    public function test_ambiguous_lookup_or_wrong_provider_values_never_create_charge(): void
    {
        foreach ([
            [503, ['error' => 'temporary']],
            [200, ['hasMore' => true, 'data' => []]],
            [200, ['hasMore' => false, 'data' => [
                $this->paymentRecord(), $this->paymentRecord(),
            ]]],
            [200, ['hasMore' => false, 'data' => [
                array_merge($this->paymentRecord(), ['customer' => 'cus_WRONG123']),
            ]]],
            [200, ['hasMore' => false, 'data' => [
                array_merge($this->paymentRecord(), ['value' => 99.0]),
            ]]],
            [200, ['hasMore' => false, 'data' => [
                array_merge($this->paymentRecord(), ['status' => 'RECEIVED']),
            ]]],
            [200, ['hasMore' => false, 'data' => [
                array_merge($this->paymentRecord(), ['billingType' => 'CREDIT_CARD']),
            ]]],
        ] as [$status, $payload]) {
            $calls = 0;
            $this->resetHttpFakes();
            Http::fake([
                'api-sandbox.asaas.com/v3/payments*' =>
                    static function (Request $request) use ($payload, $status, &$calls) {
                        $calls++;
                        if ($request->method() !== 'GET') {
                            throw new RuntimeException('POST não autorizado por consulta ambígua');
                        }
                        return Http::response($payload, $status);
                    },
            ]);
            try {
                $this->runCharge();
                $this->fail('Ambiguous provider list was treated as successful.');
            } catch (RuntimeException $e) {
                $this->assertSame('Operacao PIX sandbox inconclusiva.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
            $this->assertSame(1, $calls, 'Only the lookup GET is allowed on ambiguity.');
        }
    }

    public function test_create_error_or_mismatched_post_does_not_retry_or_activate(): void
    {
        foreach ([
            [503, ['error' => 'uncertain']],
            [200, array_merge($this->paymentRecord(), ['dueDate' => '2026-12-16'])],
            [200, array_merge($this->paymentRecord(), ['status' => 'CONFIRMED'])],
        ] as [$code, $body]) {
            $this->resetHttpFakes();
            $calls = 0;
            Http::fake([
                'api-sandbox.asaas.com/v3/payments*' =>
                    function (Request $r) use (&$calls, $code, $body) {
                        $calls++;
                        return $r->method() === 'GET'
                            ? Http::response(['hasMore' => false, 'data' => []], 200)
                            : Http::response($body, $code);
                    },
            ]);
            try {
                $this->runCharge();
                $this->fail('Create error should not grant success.');
            } catch (RuntimeException $e) {
                $this->assertSame('Operacao PIX sandbox inconclusiva.', $e->getMessage());
            }
            $this->assertSame(2, $calls, 'No POST retry on uncertain response.');
            Http::assertSentCount(2);
        }
    }
}
