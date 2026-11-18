<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxCustomerGateway;
use App\Core\Billing\AsaasSandboxPixPaymentGateway;
use App\Core\Billing\AsaasSandboxPixReconciliationInspector;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * BILL-ASAAS-07: one full end-to-end laboratory chain under a single
 * Laravel HTTP fake. It never contacts a real payment provider.
 *
 * No application route, token, tenant, subscription or invoice is activated.
 */
final class AsaasSandboxBillingJourneyTest extends TestCase
{
    private const FAKE_KEY = '$aact_hmlg_CI-only-nonfunctional-integration-secret';
    private const CUSTOMER_REF = '11111111-2222-4333-8444-555555555555';
    private const ORDER_REF = 'LAB-IMAGINE-ORDER-11111111-2222-4333-8444-555555555555';
    private const CUSTOMER_ID = 'cus_SYNTHETIC1234';
    private const PAYMENT_ID = 'pay_SYNTHETIC1234';
    private const FAKE_NAME = 'LAB-IMAGINE-CLIENTE-FICTICIO';
    private const FAKE_CNPJ = '11222333000181';

    private array $prior;

    protected function setUp(): void
    {
        parent::setUp();
        $flags = [
            'CRM_ASAAS_SANDBOX_ENABLED',
            'CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED',
            'CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED',
        ];
        $this->prior = [];
        foreach ($flags as $name) {
            $this->prior[$name] = getenv($name);
            putenv($name.'=true');
        }
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        foreach ($this->prior as $name => $before) {
            putenv($before === false ? $name : $name.'='.$before);
        }
        parent::tearDown();
    }

    private static function paymentFixture(string $status): array
    {
        return [
            'id' => self::PAYMENT_ID,
            'customer' => self::CUSTOMER_ID,
            'externalReference' => self::ORDER_REF,
            'billingType' => 'PIX',
            'dueDate' => '2026-12-15',
            'value' => 69,
            'status' => $status,
        ];
    }

    private static function assertNotAuthorized(array $result): void
    {
        foreach ([
            'can_activate', 'can_charge', 'can_create_subscription',
        ] as $flag) {
            self::assertFalse($result[$flag]);
        }
    }

    public function test_full_trial_customer_pix_replay_and_reconciliation_remain_inert(): void
    {
        $customerCreated = false;
        $paymentCreated = false;
        $calls = [];

        Http::fake(function (Request $request) use (
            &$customerCreated, &$paymentCreated, &$calls,
        ) {
            $method = $request->method();
            $url = $request->url();
            $parts = parse_url($url);
            if (!is_array($parts)
                || ($parts['scheme'] ?? null) !== 'https'
                || ($parts['host'] ?? null) !== 'api-sandbox.asaas.com'
                || !str_starts_with($parts['path'] ?? '', '/v3/')) {
                throw new RuntimeException('A request escaped the fake Sandbox host.');
            }
            $path = $parts['path'];
            $calls[] = $method.' '.$path;

            $this->assertTrue($request->hasHeader('access_token', self::FAKE_KEY));
            $this->assertFalse($request->hasHeader('asaas-access-token'));
            $this->assertTrue($request->hasHeader('User-Agent', 'ImagineCRM/0.1 (sandbox)'));

            if ($method === 'GET' && $path === '/v3/customers') {
                $this->assertStringContainsString(
                    'externalReference='.self::CUSTOMER_REF, $url,
                );
                return Http::response([
                    'hasMore' => false,
                    'data' => $customerCreated
                        ? [['id' => self::CUSTOMER_ID,
                            'externalReference' => self::CUSTOMER_REF]]
                        : [],
                ], 200);
            }
            if ($method === 'POST' && $path === '/v3/customers') {
                $this->assertFalse($customerCreated, 'Customer must be created only once.');
                $this->assertSame([
                    'name' => self::FAKE_NAME,
                    'cpfCnpj' => self::FAKE_CNPJ,
                    'externalReference' => self::CUSTOMER_REF,
                    'notificationDisabled' => true,
                ], $request->data());
                $customerCreated = true;
                return Http::response([
                    'id' => self::CUSTOMER_ID,
                    'externalReference' => self::CUSTOMER_REF,
                ], 200);
            }
            if ($method === 'GET' && $path === '/v3/payments') {
                $this->assertStringContainsString(
                    'externalReference='.self::ORDER_REF, $url,
                );
                $this->assertStringContainsString(
                    'customer='.self::CUSTOMER_ID, $url,
                );
                return Http::response([
                    'hasMore' => false,
                    'data' => $paymentCreated
                        ? [self::paymentFixture('PENDING')] : [],
                ], 200);
            }
            if ($method === 'POST' && $path === '/v3/payments') {
                $this->assertTrue($customerCreated);
                $this->assertFalse($paymentCreated, 'A second payment was created.');
                $payload = $request->data();
                $this->assertSame(self::CUSTOMER_ID, $payload['customer']);
                $this->assertSame('PIX', $payload['billingType']);
                $this->assertSame('2026-12-15', $payload['dueDate']);
                $this->assertSame(self::ORDER_REF, $payload['externalReference']);
                $this->assertEqualsWithDelta(69, (float) $payload['value'], 0.00001);
                foreach (['subscription', 'installmentCount', 'creditCard',
                    'discount', 'interest', 'split', 'fine'] as $forbidden) {
                    $this->assertArrayNotHasKey($forbidden, $payload);
                }
                $paymentCreated = true;
                return Http::response(self::paymentFixture('PENDING'), 200);
            }
            if ($method === 'GET' && $path === '/v3/payments/'.self::PAYMENT_ID) {
                $this->assertTrue($paymentCreated);
                return Http::response(self::paymentFixture('RECEIVED'), 200);
            }
            throw new RuntimeException('Unexpected payment endpoint or method.');
        });

        $http = Http::getFacadeRoot();
        $customerGateway = new AsaasSandboxCustomerGateway($http, self::FAKE_KEY);
        $pixGateway = new AsaasSandboxPixPaymentGateway($http, self::FAKE_KEY);
        $reconciler = new AsaasSandboxPixReconciliationInspector($http, self::FAKE_KEY);

        $customer = $customerGateway->findOrCreateSyntheticCustomer(
            self::FAKE_NAME, self::FAKE_CNPJ, self::CUSTOMER_REF,
        );
        $this->assertSame('sandbox_customer_created', $customer['status_code']);
        self::assertNotAuthorized($customer);

        $payment = $pixGateway->findOrCreateSyntheticPixCharge(
            $customer, 'basico', self::ORDER_REF, '2026-12-01', '2026-12-15',
        );
        $this->assertSame('sandbox_pix_payment_created', $payment['status_code']);
        $this->assertSame(6900, $payment['amount_cents']);
        self::assertNotAuthorized($payment);

        // Replay: same local reference and customer MUST reuse both provider
        // records; no customer POST and no extra charge POST.
        $sameCustomer = $customerGateway->findOrCreateSyntheticCustomer(
            self::FAKE_NAME, self::FAKE_CNPJ, self::CUSTOMER_REF,
        );
        $this->assertSame('sandbox_customer_found', $sameCustomer['status_code']);
        $samePayment = $pixGateway->findOrCreateSyntheticPixCharge(
            $sameCustomer, 'basico', self::ORDER_REF, '2026-12-01', '2026-12-15',
        );
        $this->assertSame('sandbox_pix_payment_found', $samePayment['status_code']);
        $this->assertSame($payment['provider_payment_id'], $samePayment['provider_payment_id']);

        $reconciled = $reconciler->inspectSyntheticPixPayment(
            $sameCustomer, $samePayment, self::ORDER_REF, '2026-12-15',
        );
        $this->assertSame('RECEIVED', $reconciled['provider_status']);
        $this->assertSame('received_review_required', $reconciled['review_code']);
        foreach ([
            'can_activate', 'can_mark_paid', 'can_notify',
            'can_create_subscription', 'settlement_confirmed', 'production_approved',
        ] as $flag) {
            $this->assertFalse($reconciled[$flag], $flag);
        }
        $this->assertTrue($reconciled['reconciliation_required']);

        $this->assertSame([
            'GET /v3/customers',
            'POST /v3/customers',
            'GET /v3/payments',
            'POST /v3/payments',
            'GET /v3/customers',
            'GET /v3/payments',
            'GET /v3/payments/'.self::PAYMENT_ID,
        ], $calls);
        Http::assertSentCount(7);
        $this->assertSame(2, count(array_filter($calls, static fn ($c) => str_starts_with($c, 'POST '))));
        $serialized = json_encode([$customer, $payment, $sameCustomer, $samePayment, $reconciled], JSON_THROW_ON_ERROR);
        foreach ([self::FAKE_KEY, self::FAKE_CNPJ, self::FAKE_NAME, self::ORDER_REF] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $serialized);
        }
    }
}
