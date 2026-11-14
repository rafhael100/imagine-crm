<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxSubscriptionPaymentsReview;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

final class AsaasSandboxSubscriptionPaymentsReviewTest extends TestCase
{
    private const KEY = '$aact_hmlg_fake-payments-sandbox-32-character-minimum';
    private const SUBSCRIPTION = 'sub_IMAGINE123456';
    private string|false $previous;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previous = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if ($this->previous === false) {
            putenv('CRM_ASAAS_SANDBOX_ENABLED');
        } else {
            putenv('CRM_ASAAS_SANDBOX_ENABLED='.$this->previous);
        }
        parent::tearDown();
    }

    private function service(string $key = self::KEY): AsaasSandboxSubscriptionPaymentsReview
    {
        return new AsaasSandboxSubscriptionPaymentsReview(Http::getFacadeRoot(), $key);
    }

    private static function payment(
        string $id = 'pay_IMAGINE123456',
        string $status = 'PENDING',
        string $subscription = self::SUBSCRIPTION,
    ): array {
        return [
            'id' => $id,
            'subscription' => $subscription,
            'status' => $status,
            'customer' => 'cus_SECRET_PRIVATE',
            'value' => 129.70,
            'description' => 'PII must not return in signal',
        ];
    }

    private function fake(array $payments, bool $hasMore = false, int $code = 200): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/subscriptions/*/payments*' =>
                function (Request $req) use ($payments, $hasMore, $code) {
                    $this->assertSame('GET', $req->method());
                    $this->assertStringStartsWith(
                        'https://api-sandbox.asaas.com/v3/subscriptions/'.self::SUBSCRIPTION.'/payments',
                        $req->url(),
                    );
                    $this->assertStringContainsString('limit=50', $req->url());
                    $this->assertTrue($req->hasHeader('access_token', self::KEY));
                    $this->assertTrue($req->hasHeader('User-Agent', 'ImagineCRM/0.1 (sandbox)'));
                    $this->assertFalse($req->hasHeader('asaas-access-token'));
                    return Http::response(['data' => $payments, 'hasMore' => $hasMore], $code);
                },
        ]);
    }

    private function assertNoRights(array $out): void
    {
        $this->assertTrue($out['informational_only']);
        $this->assertTrue($out['complete_page']);
        foreach ([
            'payment_verified', 'subscription_payment_verified',
            'can_charge', 'can_activate', 'can_invoice', 'can_reenable',
        ] as $field) {
            $this->assertFalse($out[$field], $field);
        }
        $text = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['cus_SECRET_PRIVATE', '129.70', 'PII', self::KEY, self::SUBSCRIPTION] as $secret) {
            $this->assertStringNotContainsString($secret, $text);
        }
    }

    public function test_empty_list_is_not_evidence_of_payment_or_permission(): void
    {
        $this->fake([]);
        $result = $this->service()->inspect(self::SUBSCRIPTION);
        $this->assertSame('synthetic_none_generated', $result['signal_code']);
        $this->assertSame(0, $result['payment_count']);
        $this->assertNoRights($result);
        Http::assertSentCount(1);
    }

    public function test_pending_and_overdue_payments_are_only_review_signals(): void
    {
        $this->fake([
            self::payment('pay_ONE11111', 'PENDING'),
            self::payment('pay_TWO22222', 'OVERDUE'),
        ]);
        $out = $this->service()->inspect(self::SUBSCRIPTION);
        $this->assertSame('synthetic_pending_review', $out['signal_code']);
        $this->assertSame(2, $out['payment_count']);
        $this->assertSame(0, $out['receipt_signals']);
        $this->assertNoRights($out);
    }

    public function test_confirmation_or_receipt_is_not_automated_activation(): void
    {
        $this->fake([
            self::payment('pay_ONE11111', 'CONFIRMED'),
            self::payment('pay_TWO22222', 'RECEIVED'),
        ]);
        $out = $this->service()->inspect(self::SUBSCRIPTION);
        $this->assertSame('synthetic_provider_receipt_review', $out['signal_code']);
        $this->assertSame(2, $out['receipt_signals']);
        $this->assertSame(0, $out['reversal_signals']);
        $this->assertNoRights($out);
    }

    public function test_refund_or_chargeback_takes_precedence_over_older_received_event(): void
    {
        $this->fake([
            self::payment('pay_ONE11111', 'RECEIVED'),
            self::payment('pay_TWO22222', 'REFUNDED'),
            self::payment('pay_THREE3333', 'CHARGEBACK_REQUESTED'),
        ]);
        $out = $this->service()->inspect(self::SUBSCRIPTION);
        $this->assertSame('synthetic_reversal_risk_review', $out['signal_code']);
        $this->assertSame(1, $out['receipt_signals']);
        $this->assertSame(2, $out['reversal_signals']);
        $this->assertNoRights($out);
    }

    public function test_partial_refund_risk_is_not_overridden_by_another_receipt(): void
    {
        $this->fake([
            self::payment('pay_ONE11111', 'RECEIVED'),
            self::payment('pay_TWO22222', 'PARTIALLY_REFUNDED'),
        ]);
        $out = $this->service()->inspect(self::SUBSCRIPTION);
        $this->assertSame('synthetic_reversal_risk_review', $out['signal_code']);
        $this->assertNoRights($out);
    }

    public function test_truncated_or_unrecognized_provider_evidence_is_denied(): void
    {
        foreach ([
            [self::payment(), true, 200],
            [self::payment('pay_111111111', 'UNKNOWN'), false, 200],
            [self::payment('pay_111111111', 'RECEIVED', 'sub_FOREIGN1234'), false, 200],
            [self::payment('pay_123456789'), false, 503],
        ] as [$payment, $hasMore, $statusCode]) {
            $this->fake([$payment], $hasMore, $statusCode);
            try {
                $this->service()->inspect(self::SUBSCRIPTION);
                $this->fail('Incomplete or conflicting provider statement must fail closed.');
            } catch (RuntimeException $e) {
                $this->assertSame('Analise de cobrancas Asaas sandbox inconclusiva.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
    }

    public function test_duplicate_payment_ids_cannot_inflate_receipt_counts(): void
    {
        $this->fake([
            self::payment('pay_REPEAT1111', 'RECEIVED'),
            self::payment('pay_REPEAT1111', 'RECEIVED'),
        ]);
        try {
            $this->service()->inspect(self::SUBSCRIPTION);
            $this->fail('Duplicate provider ID accepted as two payments.');
        } catch (RuntimeException $e) {
            $this->assertSame('Analise de cobrancas Asaas sandbox inconclusiva.', $e->getMessage());
        }
    }

    public function test_invalid_subscription_and_production_credentials_are_denied(): void
    {
        foreach (['untrusted/id', 'sub_a', 'sub_INJECT?limit=999'] as $id) {
            try {
                $this->service()->inspect($id);
                $this->fail('Unsafe subscription ID was accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Assinatura sandbox de consulta invalida.', $e->getMessage());
            }
        }
        foreach (['', 'short', '$aact_prod_unsafe_key_never_use'] as $key) {
            try {
                $this->service($key);
                $this->fail('Unsafe Asaas credential accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame('Consulta de cobrancas sandbox nao habilitada.', $e->getMessage());
            }
        }

        putenv('CRM_ASAAS_SANDBOX_ENABLED=false');
        try {
            $this->service();
            $this->fail('Disabled sandbox was allowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Consulta de cobrancas sandbox nao habilitada.', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_no_payment_checkout_or_reconciliation_api_is_exposed(): void
    {
        $this->get('/api/asaas/subscriptions/'.self::SUBSCRIPTION.'/payments')->assertNotFound();
        $this->postJson('/api/checkout', [])->assertNotFound();
        $this->postJson('/api/payments/confirm', [])->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('AsaasSandboxSubscriptionPaymentsReview', $source);
        }
        Http::assertNothingSent();
    }
}
