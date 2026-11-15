<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxPixPaymentGateway;
use App\Core\Billing\LaboratoryAsaasOrderPaymentHandoff;
use App\Core\Billing\LaboratoryAsaasOrderPaymentReviewer;
use App\Core\Billing\LaboratoryAsaasOrderReservation;
use Illuminate\Database\Connection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * BILL-ASAAS-INT-03: ephemeral PostgreSQL16 with two distinct creator/reviewer
 * sessions, fake HTTP only, no real payments, tenant access or activation.
 */
final class PostgresAsaasOrderReservationTest extends TestCase
{
    private const ORG_A = 'aaaaaaaa-2222-4333-8444-555555555551';
    private const ORG_B = 'bbbbbbbb-2222-4333-8444-555555555552';
    private const ORDER_A = '11111111-2222-4333-8444-555555555551';
    private const ORDER_B = '11111111-2222-4333-8444-555555555552';
    private const CUSTOMER_A = 'cus_SYNTHETIC1234';
    private const CUSTOMER_B = 'cus_SYNTHETIC5678';
    private const PAYMENT_A = 'pay_SYNTHETIC1234';
    private const PAYMENT_B = 'pay_SYNTHETIC5678';
    private const DUE_ON = '2026-12-15';
    private const TRIAL_START = '2026-12-01';
    private const KEY = '$aact_hmlg_ci-only-fake-sandbox-api-key-nonfunctional';

    private Connection $owner;
    private Connection $creatorA;
    private Connection $creatorB;
    private Connection $reviewerA;
    private Connection $reviewerB;
    /** @var array<string,string|false> */
    private array $prior = [];

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_ORDER_LAB') !== 'synthetic-only'
            || getenv('BILLING_ORDER_USERNAME') !== 'crm_billing_order_ci'
            || getenv('BILLING_ORDER_REVIEW_USERNAME') !== 'crm_billing_order_review_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('CI-only laboratory order reservation.');
        }

        foreach (['CRM_ASAAS_SANDBOX_ENABLED',
            'CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED'] as $key) {
            $this->prior[$key] = getenv($key);
            putenv($key.'=true');
        }
        Http::preventStrayRequests();
        $this->owner = DB::connection();
        $this->owner->table('crm_lab_asaas_order_reservations')->delete();
        $this->owner->table('crm_lab_asaas_customer_links')->delete();
        $this->owner->table('crm_lab_asaas_customer_links')->insert([
            [
                'organization_ref' => self::ORG_A,
                'external_reference' => 'cccccccc-2222-4333-8444-555555555551',
                'provider_customer_id' => self::CUSTOMER_A,
                'state' => 'linked', 'reserved_at' => 1700000000,
            ],
            [
                'organization_ref' => self::ORG_B,
                'external_reference' => 'cccccccc-2222-4333-8444-555555555552',
                'provider_customer_id' => self::CUSTOMER_B,
                'state' => 'linked', 'reserved_at' => 1700000000,
            ],
        ]);

        $pg['url'] = null;
        $roles = [
            'order_creator_a_ci' => ['BILLING_ORDER_USERNAME', 'BILLING_ORDER_PASSWORD'],
            'order_creator_b_ci' => ['BILLING_ORDER_USERNAME', 'BILLING_ORDER_PASSWORD'],
            'order_reviewer_a_ci' => ['BILLING_ORDER_REVIEW_USERNAME', 'BILLING_ORDER_REVIEW_PASSWORD'],
            'order_reviewer_b_ci' => ['BILLING_ORDER_REVIEW_USERNAME', 'BILLING_ORDER_REVIEW_PASSWORD'],
        ];
        foreach ($roles as $alias => [$userEnv, $passwordEnv]) {
            $cfg = $pg;
            $cfg['username'] = getenv($userEnv);
            $cfg['password'] = getenv($passwordEnv);
            config(['database.connections.'.$alias => $cfg]);
            DB::purge($alias);
        }
        $this->creatorA = DB::connection('order_creator_a_ci');
        $this->creatorB = DB::connection('order_creator_b_ci');
        $this->reviewerA = DB::connection('order_reviewer_a_ci');
        $this->reviewerB = DB::connection('order_reviewer_b_ci');
    }

    protected function tearDown(): void
    {
        if (isset($this->owner)) {
            foreach ([$this->creatorA, $this->creatorB, $this->reviewerA,
                $this->reviewerB] as $connection) {
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            }
            while ($this->owner->transactionLevel() > 0) {
                $this->owner->rollBack();
            }
            $this->owner->table('crm_lab_asaas_order_reservations')->delete();
            $this->owner->table('crm_lab_asaas_customer_links')->delete();
            foreach (['order_creator_a_ci', 'order_creator_b_ci',
                'order_reviewer_a_ci', 'order_reviewer_b_ci'] as $alias) {
                DB::purge($alias);
            }
        }
        foreach ($this->prior as $key => $before) {
            putenv($before === false ? $key : $key.'='.$before);
        }
        parent::tearDown();
    }

    private function reservation(?Connection $db = null): LaboratoryAsaasOrderReservation
    {
        return new LaboratoryAsaasOrderReservation($db ?? $this->creatorA);
    }

    private function reviewer(?Connection $db = null): LaboratoryAsaasOrderPaymentReviewer
    {
        return new LaboratoryAsaasOrderPaymentReviewer($db ?? $this->reviewerA);
    }

    private function handoff(?Connection $creator = null, ?Connection $reviewer = null): LaboratoryAsaasOrderPaymentHandoff
    {
        return new LaboratoryAsaasOrderPaymentHandoff(
            $this->reservation($creator),
            $this->reviewer($reviewer),
            new AsaasSandboxPixPaymentGateway(Http::getFacadeRoot(), self::KEY),
        );
    }

    /** @return array<string,mixed> */
    private static function customer(): array
    {
        return [
            'provider_customer_id' => self::CUSTOMER_A,
            'status_code' => 'sandbox_customer_found',
            'environment' => 'sandbox',
            'can_activate' => false,
            'can_charge' => false,
            'can_create_subscription' => false,
            'company_owner_verified' => false,
        ];
    }

    private function assertInert(array $reply): void
    {
        $this->assertTrue($reply['informational_only']);
        foreach (['can_checkout', 'can_charge', 'can_activate'] as $flag) {
            $this->assertFalse($reply[$flag], $flag);
        }
        $payload = json_encode($reply, JSON_THROW_ON_ERROR);
        foreach ([self::KEY, self::ORG_A, self::ORDER_A,
            self::CUSTOMER_A, self::PAYMENT_A] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $payload);
        }
    }

    public function test_global_order_key_prevents_replay_and_conflicting_tenant_without_http(): void
    {
        $first = $this->reservation()->reserve(
            self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON,
        );
        $replay = $this->reservation($this->creatorB)->reserve(
            self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON,
        );
        $conflict = $this->reservation($this->creatorB)->reserve(
            self::ORG_B, self::ORDER_A, self::CUSTOMER_B, 'essencial', self::DUE_ON,
        );
        $this->assertSame('new_reservation', $first['status_code']);
        $this->assertTrue($first['may_attempt_sandbox_once']);
        foreach ([$replay, $conflict] as $blocked) {
            $this->assertSame('existing_review_required', $blocked['status_code']);
            $this->assertFalse($blocked['may_attempt_sandbox_once']);
            $this->assertInert($blocked);
        }
        $this->assertInert($first);
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_order_reservations')->count());
        $this->assertSame(6900, $this->owner->table('crm_lab_asaas_order_reservations')->value('amount_cents'));
        Http::assertNothingSent();
    }

    public function test_customer_of_other_tenant_cannot_be_bound_to_order_even_on_new_uuid(): void
    {
        try {
            $this->reservation()->reserve(
                self::ORG_A, self::ORDER_A, self::CUSTOMER_B, 'essencial', self::DUE_ON,
            );
            $this->fail('Foreign customer reference must be rejected by PostgreSQL.');
        } catch (RuntimeException $e) {
            $this->assertSame('Reserva de pedido Asaas indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_order_reservations')->count());
        Http::assertNothingSent();
    }

    public function test_payment_id_is_immutable_idempotent_and_unique_across_orders(): void
    {
        $this->reservation()->reserve(
            self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON,
        );
        $this->reservation($this->creatorB)->reserve(
            self::ORG_B, self::ORDER_B, self::CUSTOMER_B, 'essencial', self::DUE_ON,
        );
        $first = $this->reviewer()->bind(
            self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON, self::PAYMENT_A,
        );
        $repeat = $this->reviewer($this->reviewerB)->bind(
            self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON, self::PAYMENT_A,
        );
        $this->assertSame('payment_linked', $first['status_code']);
        $this->assertSame('payment_already_linked', $repeat['status_code']);
        $this->assertTrue($first['informational_only']);
        $this->assertFalse($first['can_mark_paid']);
        foreach ([
            [self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::PAYMENT_B],
            [self::ORG_B, self::ORDER_B, self::CUSTOMER_B, 'essencial', self::PAYMENT_A],
            [self::ORG_B, self::ORDER_A, self::CUSTOMER_A, 'basico', self::PAYMENT_A],
        ] as [$org, $order, $customer, $plan, $id]) {
            try {
                $this->reviewer($this->reviewerB)->bind(
                    $org, $order, $customer, $plan, self::DUE_ON, $id,
                );
                $this->fail('Reassociation must be denied.');
            } catch (RuntimeException $e) {
                $this->assertSame('Vinculo de pagamento Asaas indisponivel.', $e->getMessage());
            }
        }
        $this->assertSame('linked', $this->owner->table('crm_lab_asaas_order_reservations')
            ->where('order_ref', self::ORDER_A)->value('state'));
        $this->assertNull($this->owner->table('crm_lab_asaas_order_reservations')
            ->where('order_ref', self::ORDER_B)->value('provider_payment_id'));
        Http::assertNothingSent();
    }

    public function test_handoff_calls_mock_provider_only_once_even_after_replay(): void
    {
        $created = false;
        Http::fake(function (Request $request) use (&$created) {
            $this->assertStringStartsWith('https://api-sandbox.asaas.com/v3/payments', $request->url());
            $this->assertTrue($request->hasHeader('access_token', self::KEY));
            $this->assertFalse($request->hasHeader('asaas-access-token'));
            if ($request->method() === 'GET') {
                return Http::response(['hasMore' => false,
                    'data' => $created ? [self::fakePayment()] : []], 200);
            }
            $this->assertSame('POST', $request->method());
            $this->assertFalse($created, 'A second payment POST was attempted.');
            $this->assertSame(self::CUSTOMER_A, $request->data()['customer']);
            $this->assertSame('LAB-IMAGINE-ORDER-'.self::ORDER_A, $request->data()['externalReference']);
            $this->assertSame('PIX', $request->data()['billingType']);
            $this->assertEqualsWithDelta(69, (float) $request->data()['value'], 0.00001);
            $created = true;
            return Http::response(self::fakePayment(), 200);
        });

        $first = $this->handoff()->run(
            self::ORG_A, self::ORDER_A, self::customer(), 'basico',
            self::TRIAL_START, self::DUE_ON,
        );
        $again = $this->handoff($this->creatorB, $this->reviewerB)->run(
            self::ORG_A, self::ORDER_A, self::customer(), 'basico',
            self::TRIAL_START, self::DUE_ON,
        );
        $this->assertSame('sandbox_payment_linked_review_only', $first['status_code']);
        $this->assertSame('sandbox_payment_manual_review_required', $again['status_code']);
        $this->assertTrue($first['provider_request_may_have_occurred']);
        $this->assertFalse($again['provider_request_may_have_occurred']);
        $this->assertInert($first);
        $this->assertInert($again);
        $this->assertSame(self::PAYMENT_A, $this->owner->table('crm_lab_asaas_order_reservations')
            ->where('order_ref', self::ORDER_A)->value('provider_payment_id'));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST');
    }

    /** @return array<string,mixed> */
    private static function fakePayment(): array
    {
        return [
            'id' => self::PAYMENT_A,
            'customer' => self::CUSTOMER_A,
            'billingType' => 'PIX',
            'value' => 69,
            'dueDate' => self::DUE_ON,
            'externalReference' => 'LAB-IMAGINE-ORDER-'.self::ORDER_A,
            'status' => 'PENDING',
        ];
    }

    public function test_provider_failure_leaves_review_marker_and_no_second_http(): void
    {
        Http::fake(['api-sandbox.asaas.com/v3/payments*' => Http::response(
            ['errors' => [['code' => 'simulated_unavailable']]], 503,
        )]);
        $first = $this->handoff()->run(
            self::ORG_A, self::ORDER_A, self::customer(), 'basico',
            self::TRIAL_START, self::DUE_ON,
        );
        $again = $this->handoff($this->creatorB, $this->reviewerB)->run(
            self::ORG_A, self::ORDER_A, self::customer(), 'basico',
            self::TRIAL_START, self::DUE_ON,
        );
        $this->assertSame('sandbox_payment_manual_review_required', $first['status_code']);
        $this->assertSame('sandbox_payment_manual_review_required', $again['status_code']);
        $this->assertTrue($first['provider_request_may_have_occurred']);
        $this->assertFalse($again['provider_request_may_have_occurred']);
        $this->assertSame('review_required', $this->owner->table('crm_lab_asaas_order_reservations')
            ->where('order_ref', self::ORDER_A)->value('state'));
        $this->assertNull($this->owner->table('crm_lab_asaas_order_reservations')
            ->where('order_ref', self::ORDER_A)->value('provider_payment_id'));
        Http::assertSentCount(1);
    }

    public function test_open_transaction_blocks_handoff_before_http_and_reviewer_denies_owner(): void
    {
        $this->creatorA->beginTransaction();
        try {
            $this->handoff()->run(
                self::ORG_A, self::ORDER_A, self::customer(), 'basico',
                self::TRIAL_START, self::DUE_ON,
            );
            $this->fail('Provider I/O permitted inside an uncommitted transaction.');
        } catch (RuntimeException $e) {
            $this->assertSame('Pagamento sandbox proibido durante transacao.', $e->getMessage());
        } finally {
            $this->creatorA->rollBack();
        }
        try {
            $this->reservation($this->owner)->reserve(
                self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON,
            );
            $this->fail('Owner must not reserve as runtime.');
        } catch (RuntimeException $e) {
            $this->assertSame('Papel de pedido nao autorizado.', $e->getMessage());
        }
        Http::assertNothingSent();
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_order_reservations')->count());
    }

    public function test_two_sessions_block_on_uncommitted_pk_until_rollback(): void
    {
        // A real PostgreSQL row/index conflict; never touch HTTP here.
        $this->owner->beginTransaction();
        try {
            $this->owner->table('crm_lab_asaas_order_reservations')->insert([
                'order_ref' => self::ORDER_A,
                'organization_ref' => self::ORG_A,
                'provider_customer_id' => self::CUSTOMER_A,
                'plan_code' => 'basico',
                'amount_cents' => 6900,
                'due_on' => self::DUE_ON,
            ]);
            $this->creatorB->statement("SET lock_timeout = '300ms'");
            try {
                $this->reservation($this->creatorB)->reserve(
                    self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON,
                );
                $this->fail('A conflicting reservation must wait for the first transaction.');
            } catch (RuntimeException $e) {
                $this->assertSame('Reserva de pedido Asaas indisponivel.', $e->getMessage());
            }
        } finally {
            $this->owner->rollBack();
            $this->creatorB->statement('RESET lock_timeout');
        }
        $fresh = $this->reservation($this->creatorB)->reserve(
            self::ORG_A, self::ORDER_A, self::CUSTOMER_A, 'basico', self::DUE_ON,
        );
        $this->assertSame('new_reservation', $fresh['status_code']);
        Http::assertNothingSent();
    }

    public function test_reviewer_grants_and_no_public_payment_routes(): void
    {
        $creator = $this->creatorA->selectOne(
            "SELECT has_table_privilege(current_user, 'crm_lab_asaas_order_reservations','SELECT') AS can_read,
                    has_table_privilege(current_user, 'crm_lab_asaas_order_reservations','DELETE') AS can_delete",
        );
        $this->assertFalse((bool) $creator->can_read);
        $this->assertFalse((bool) $creator->can_delete);
        $reviewer = $this->reviewerA->selectOne(
            "SELECT has_table_privilege(current_user, 'crm_lab_asaas_order_reservations','INSERT') AS can_insert,
                    has_column_privilege(current_user,'crm_lab_asaas_order_reservations','organization_ref','UPDATE') AS can_switch_tenant,
                    has_column_privilege(current_user,'crm_lab_asaas_order_reservations','provider_payment_id','UPDATE') AS can_link",
        );
        $this->assertFalse((bool) $reviewer->can_insert);
        $this->assertFalse((bool) $reviewer->can_switch_tenant);
        $this->assertTrue((bool) $reviewer->can_link);

        $this->postJson('/api/billing/asaas/orders', [])->assertNotFound();
        $this->postJson('/api/asaas/payments', [])->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $path) {
            $source = file_get_contents(base_path($path));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratoryAsaasOrderPaymentHandoff', $source);
            $this->assertStringNotContainsString('LaboratoryAsaasOrderReservation', $source);
        }
        Http::assertNothingSent();
    }
}
