<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxCustomerGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

final class AsaasSandboxCustomerGatewayTest extends TestCase
{
    private const API_KEY = '$aact_hmlg_ci-only-fake-sandbox-api-key-nonfunctional';
    private const EXTERNAL_REFERENCE = '11111111-2222-4333-8444-555555555555';
    private const FIXTURE_NAME = 'LAB-IMAGINE-CLIENTE-FICTICIO';
    private const FIXTURE_CNPJ = '11222333000181';

    private string|false $priorFlag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priorFlag = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests(); // no external network, even if URL is wrong
    }

    protected function tearDown(): void
    {
        if ($this->priorFlag === false) {
            putenv('CRM_ASAAS_SANDBOX_ENABLED');
        } else {
            putenv('CRM_ASAAS_SANDBOX_ENABLED='.$this->priorFlag);
        }
        parent::tearDown();
    }

    private function gateway(string $key = self::API_KEY): AsaasSandboxCustomerGateway
    {
        return new AsaasSandboxCustomerGateway(Http::getFacadeRoot(), $key);
    }

    private function exerciseSyntheticCustomer(): array
    {
        return $this->gateway()->findOrCreateSyntheticCustomer(
            self::FIXTURE_NAME, self::FIXTURE_CNPJ, self::EXTERNAL_REFERENCE,
        );
    }

    private function assertNoFinancialGrants(array $result): void
    {
        $this->assertSame('sandbox', $result['environment']);
        foreach ([
            'can_activate', 'can_charge', 'can_create_subscription',
            'company_owner_verified',
        ] as $flag) {
            $this->assertFalse($result[$flag], $flag);
        }
        $body = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([self::API_KEY, self::FIXTURE_CNPJ, self::FIXTURE_NAME] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
    }

    public function test_lookup_reuses_existing_customer_without_any_post(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' => function (Request $request) {
                $this->assertSame('GET', $request->method());
                $this->assertStringContainsString(
                    'externalReference='.self::EXTERNAL_REFERENCE, $request->url(),
                );
                return Http::response([
                    'hasMore' => false,
                    'data' => [[
                        'id' => 'cus_AAAA00001111',
                        'externalReference' => self::EXTERNAL_REFERENCE,
                    ]],
                ], 200);
            },
        ]);

        $result = $this->exerciseSyntheticCustomer();
        $this->assertSame('sandbox_customer_found', $result['status_code']);
        $this->assertSame('cus_AAAA00001111', $result['provider_customer_id']);
        $this->assertNoFinancialGrants($result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) =>
            $r->hasHeader('access_token', self::API_KEY)
            && !$r->hasHeader('asaas-access-token')
            && $r->hasHeader('User-Agent', 'ImagineCRM/0.1 (sandbox)'));
    }

    public function test_only_absent_customer_is_created_in_sandbox_with_notifications_off(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' => function (Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response(['data' => [], 'hasMore' => false], 200);
                }
                $this->assertSame('POST', $request->method());
                $this->assertSame('https://api-sandbox.asaas.com/v3/customers', $request->url());
                $this->assertSame([
                    'name' => self::FIXTURE_NAME,
                    'cpfCnpj' => self::FIXTURE_CNPJ,
                    'externalReference' => self::EXTERNAL_REFERENCE,
                    'notificationDisabled' => true,
                ], $request->data());
                return Http::response([
                    'id' => 'cus_TEST12345678',
                    'externalReference' => self::EXTERNAL_REFERENCE,
                ], 200);
            },
        ]);

        $result = $this->exerciseSyntheticCustomer();
        $this->assertSame('sandbox_customer_created', $result['status_code']);
        $this->assertSame('cus_TEST12345678', $result['provider_customer_id']);
        $this->assertNoFinancialGrants($result);
        Http::assertSentCount(2);
    }

    public function test_invalid_or_wrong_sandbox_secret_and_disabled_flag_deny_before_network(): void
    {
        foreach (['$aact_prod_do_not_use', 'short', ''] as $key) {
            try {
                $this->gateway($key);
                $this->fail('Unsafe API key passed constructor.');
            } catch (RuntimeException $e) {
                $this->assertSame('Acesso Asaas sandbox nao habilitado.', $e->getMessage());
            }
        }
        putenv('CRM_ASAAS_SANDBOX_ENABLED=false');
        try {
            $this->gateway();
            $this->fail('Disabled sandbox flag did not block.');
        } catch (RuntimeException $e) {
            $this->assertSame('Acesso Asaas sandbox nao habilitado.', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_real_looking_input_or_unreviewed_reference_cannot_leave_the_process(): void
    {
        Http::fake();
        foreach ([
            ['Empresa Cliente Real', self::FIXTURE_CNPJ, self::EXTERNAL_REFERENCE],
            [self::FIXTURE_NAME, '12345678000199', self::EXTERNAL_REFERENCE],
            [self::FIXTURE_NAME, self::FIXTURE_CNPJ, 'injected-ref'],
        ] as [$name, $cnpj, $ref]) {
            try {
                $this->gateway()->findOrCreateSyntheticCustomer($name, $cnpj, $ref);
                $this->fail('Non-fixture business data was accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Cadastro Asaas sandbox ficticio invalido.', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    public function test_ambiguous_or_failed_lookup_never_creates_duplicate_customer(): void
    {
        foreach ([
            [500, ['error' => 'fake']],
            [200, ['hasMore' => true, 'data' => []]],
            [200, ['hasMore' => false, 'data' => [
                ['id' => 'cus_FIRST11111', 'externalReference' => self::EXTERNAL_REFERENCE],
                ['id' => 'cus_SECOND1111', 'externalReference' => self::EXTERNAL_REFERENCE],
            ]]],
            [200, ['hasMore' => false, 'data' => [
                ['id' => 'cus_OTHER11111', 'externalReference' => 'wrong-reference'],
            ]]],
        ] as [$status, $body]) {
            Http::fake([
                'api-sandbox.asaas.com/v3/customers*' => Http::response($body, $status),
            ]);
            try {
                $this->exerciseSyntheticCustomer();
                $this->fail('Ambiguous provider lookup was accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame('Operacao Asaas sandbox inconclusiva.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
    }

    public function test_failed_create_does_not_automatically_retry_or_claim_success(): void
    {
        $calls = 0;
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' => static function (Request $r) use (&$calls) {
                $calls++;
                return $r->method() === 'GET'
                    ? Http::response(['data' => [], 'hasMore' => false], 200)
                    : Http::response(['errors' => [['code' => 'uncertain']]], 503);
            },
        ]);

        try {
            $this->exerciseSyntheticCustomer();
            $this->fail('Provider failed, but customer was considered created.');
        } catch (RuntimeException $e) {
            $this->assertSame('Operacao Asaas sandbox inconclusiva.', $e->getMessage());
        }
        $this->assertSame(2, $calls, 'No silent POST retry after uncertain provider response.');
    }

    public function test_no_customer_creation_or_external_api_route_is_published(): void
    {
        $this->postJson('/api/asaas/customers', [])->assertNotFound();
        $this->postJson('/api/asaas/subscriptions', [])->assertNotFound();
        $this->postJson('/api/checkout', [])->assertNotFound();
        Http::assertNothingSent();
    }
}
