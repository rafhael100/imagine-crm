<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxSubscriptionLookup;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

final class AsaasSandboxSubscriptionLookupTest extends TestCase
{
    private const API_KEY = '$aact_hmlg_ci-only-fake-sandbox-api-key-nonfunctional';
    private const REF = 'dddd1111-2222-4333-8444-555555555555';
    private const CUSTOMER = 'cus_SUBDRAFT11111';
    private const SUBSCRIPTION = 'sub_EXAMPLE111111';
    private string|false $oldFlag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oldFlag = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if ($this->oldFlag === false) {
            putenv('CRM_ASAAS_SANDBOX_ENABLED');
        } else {
            putenv('CRM_ASAAS_SANDBOX_ENABLED='.$this->oldFlag);
        }
        parent::tearDown();
    }

    private function lookup(string $apiKey = self::API_KEY): AsaasSandboxSubscriptionLookup
    {
        return new AsaasSandboxSubscriptionLookup(Http::getFacadeRoot(), $apiKey);
    }

    private static function subscription(
        string $reference = self::REF,
        string $customer = self::CUSTOMER,
        string $id = self::SUBSCRIPTION,
    ): array {
        return [
            'id' => $id,
            'externalReference' => $reference,
            'customer' => $customer,
            'status' => 'ACTIVE',
            'value' => 89.0,
            'cycle' => 'MONTHLY',
            'billingType' => 'PIX',
        ];
    }

    private function assertNoAuthorization(array $result): void
    {
        $this->assertTrue($result['informational_only']);
        $this->assertSame('sandbox', $result['environment']);
        foreach ([
            'ownership_of_company_verified', 'payment_verified', 'plan_approved',
            'terms_accepted', 'can_create_subscription', 'can_charge',
            'can_activate', 'can_invoice',
        ] as $field) {
            $this->assertFalse($result[$field], $field);
        }

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([self::API_KEY, self::CUSTOMER, '89.0', 'ACTIVE'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $json);
        }
    }

    public function test_exact_get_filter_and_matching_payer_find_only_one_subscription(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/subscriptions*' => function (Request $request) {
                $this->assertSame('GET', $request->method());
                $this->assertStringStartsWith(
                    'https://api-sandbox.asaas.com/v3/subscriptions?', $request->url(),
                );
                $this->assertStringContainsString('externalReference='.self::REF, $request->url());
                $this->assertStringContainsString('includeDeleted=true', $request->url());
                $this->assertStringContainsString('limit=2', $request->url());
                $this->assertTrue($request->hasHeader('access_token', self::API_KEY));
                $this->assertTrue($request->hasHeader('User-Agent', 'ImagineCRM/0.1 (sandbox)'));
                $this->assertFalse($request->hasHeader('asaas-access-token'));
                return Http::response([
                    'data' => [self::subscription()],
                    'hasMore' => false,
                ], 200);
            },
        ]);

        $result = $this->lookup()->byExternalReference(self::REF, self::CUSTOMER);
        $this->assertSame('sandbox_subscription_found', $result['status_code']);
        $this->assertSame(self::SUBSCRIPTION, $result['provider_subscription_id']);
        $this->assertNoAuthorization($result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->method() === 'GET');
    }

    public function test_not_found_never_authorizes_post_or_activation(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/subscriptions*' =>
                Http::response(['data' => [], 'hasMore' => false], 200),
        ]);

        $result = $this->lookup()->byExternalReference(self::REF, self::CUSTOMER);
        $this->assertSame('sandbox_subscription_absent', $result['status_code']);
        $this->assertNull($result['provider_subscription_id']);
        $this->assertNoAuthorization($result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->method() === 'GET');
    }

    public function test_ambiguous_mismatching_or_failed_results_are_rejected(): void
    {
        $cases = [
            [503, ['error' => 'unavailable']],
            [200, ['data' => [], 'hasMore' => true]],
            [200, ['data' => [self::subscription(), self::subscription()], 'hasMore' => false]],
            [200, ['data' => [self::subscription(
                'aaaaaaaa-bbbb-4444-8888-111111111111',
            )], 'hasMore' => false]],
            [200, ['data' => [self::subscription(
                self::REF, 'cus_DIFFERENT12345',
            )], 'hasMore' => false]],
            [200, ['data' => [self::subscription(
                self::REF, self::CUSTOMER, 'bad-subscription-id',
            )], 'hasMore' => false]],
            [200, ['data' => ['invalid'] , 'hasMore' => false]],
        ];

        foreach ($cases as [$httpCode, $body]) {
            Http::fake([
                'api-sandbox.asaas.com/v3/subscriptions*' =>
                    Http::response($body, $httpCode),
            ]);
            try {
                $this->lookup()->byExternalReference(self::REF, self::CUSTOMER);
                $this->fail('Ambiguous or unauthorized subscription mapping accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame(
                    'Consulta de assinatura Asaas sandbox inconclusiva.', $e->getMessage(),
                );
                $this->assertNull($e->getPrevious());
            }
        }
    }

    public function test_invalid_reference_or_customer_fails_before_any_network(): void
    {
        Http::fake();
        foreach ([
            ['injected', self::CUSTOMER],
            [self::REF, 'customer-without-provider-id'],
            [self::REF.'?limit=100', self::CUSTOMER],
            [self::REF, 'cus_x'],
        ] as [$ref, $customer]) {
            try {
                $this->lookup()->byExternalReference($ref, $customer);
                $this->fail('Invalid external reference or customer accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Identificadores de consulta sandbox invalidos.', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    public function test_sandbox_must_be_enabled_and_prod_key_never_accepted(): void
    {
        foreach (['', 'short', '$aact_prod_fictitious-secret'] as $secret) {
            try {
                $this->lookup($secret);
                $this->fail('Unsafe key accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame('Consulta de assinatura sandbox nao habilitada.', $e->getMessage());
            }
        }
        putenv('CRM_ASAAS_SANDBOX_ENABLED=false');
        try {
            $this->lookup();
            $this->fail('Feature flag disabled but lookup allowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Consulta de assinatura sandbox nao habilitada.', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_even_active_subscription_is_not_proof_of_paid_invoice(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/subscriptions*' =>
                Http::response(['data' => [self::subscription()], 'hasMore' => false], 200),
        ]);

        $found = $this->lookup()->byExternalReference(self::REF, self::CUSTOMER);
        $this->assertSame('sandbox_subscription_found', $found['status_code']);
        $this->assertNoAuthorization($found);
        $this->assertArrayNotHasKey('payment_status', $found);
        $this->assertArrayNotHasKey('billing_verified', $found);
        $this->assertArrayNotHasKey('value', $found);
        $this->assertArrayNotHasKey('plan_code', $found);
    }

    public function test_no_checkout_or_reconciliation_route_is_exposed(): void
    {
        $this->postJson('/api/asaas/subscriptions', [])->assertNotFound();
        $this->get('/api/asaas/subscriptions')->assertNotFound();
        $this->get('/api/asaas/lookup-subscription')->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('AsaasSandboxSubscriptionLookup', $source);
        }
        Http::assertNothingSent();
    }
}
