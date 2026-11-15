<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxSubscriptionLookup;
use App\Core\Billing\AsaasSubscriptionDraftSpec;
use App\Core\Billing\LaboratoryAsaasOrderPaymentReviewer;
use App\Core\Billing\LaboratoryAsaasOrderReservation;
use App\Core\Billing\LaboratoryAsaasSubscriptionDraft;
use App\Core\Billing\LaboratoryAsaasSubscriptionObservation;
use Illuminate\Database\Connection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * INT-05: one physical CI database for customer, one-shot order, blocked
 * subscription and read-only observation. No real Asaas, rights or routes.
 */
final class PostgresAsaasBillingIntegratedLedgerTest extends TestCase
{
    private const ORG = 'aaaabbbb-2222-4333-8444-555555555551';
    private const PAYER_REF = 'bbbbcccc-2222-4333-8444-555555555551';
    private const ORDER_REF = 'ccccdddd-2222-4333-8444-555555555551';
    private const SUB_REF = 'ddddeeee-2222-4333-8444-555555555551';
    private const CUSTOMER = 'cus_INTEGRATED55555';
    private const PAYMENT = 'pay_INTEGRATED55555';
    private const SUBSCRIPTION = 'sub_INTEGRATED55555';
    private const KEY = '$aact_hmlg_ci-only-fake-integration-key-nonfunctional';

    private Connection $owner;
    private Connection $orderCreator;
    private Connection $orderReviewer;
    private Connection $subscriptionWriter;
    private Connection $subscriptionReviewer;
    private string|false $priorFlag;

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_ORDER_LAB') !== 'synthetic-only'
            || getenv('CRM_ASAAS_SUBSCRIPTION_LAB') !== 'synthetic-only'
            || getenv('CRM_ASAAS_SUBSCRIPTION_OBSERVATION_LAB') !== 'synthetic-only'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('Only disposable PG16 billing integration in CI.');
        }

        $this->priorFlag = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests();
        $this->owner = DB::connection();
        $this->clean();

        $pg['url'] = null;
        $roles = [
            'integrated_order_create_ci' => ['BILLING_ORDER_USERNAME', 'BILLING_ORDER_PASSWORD'],
            'integrated_order_review_ci' => ['BILLING_ORDER_REVIEW_USERNAME', 'BILLING_ORDER_REVIEW_PASSWORD'],
            'integrated_subscription_write_ci' => ['BILLING_SUBSCRIPTION_USERNAME', 'BILLING_SUBSCRIPTION_PASSWORD'],
            'integrated_subscription_review_ci' => ['BILLING_SUBSCRIPTION_REVIEW_USERNAME', 'BILLING_SUBSCRIPTION_REVIEW_PASSWORD'],
        ];
        foreach ($roles as $alias => [$username, $password]) {
            $cfg = $pg;
            $cfg['username'] = getenv($username);
            $cfg['password'] = getenv($password);
            config(['database.connections.'.$alias => $cfg]);
            DB::purge($alias);
        }
        $this->orderCreator = DB::connection('integrated_order_create_ci');
        $this->orderReviewer = DB::connection('integrated_order_review_ci');
        $this->subscriptionWriter = DB::connection('integrated_subscription_write_ci');
        $this->subscriptionReviewer = DB::connection('integrated_subscription_review_ci');
    }

    protected function tearDown(): void
    {
        if (isset($this->owner)) {
            foreach ([$this->orderCreator, $this->orderReviewer,
                $this->subscriptionWriter, $this->subscriptionReviewer] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->clean();
            foreach ([
                'integrated_order_create_ci', 'integrated_order_review_ci',
                'integrated_subscription_write_ci', 'integrated_subscription_review_ci',
            ] as $alias) {
                DB::purge($alias);
            }
        }
        if (isset($this->priorFlag)) {
            putenv($this->priorFlag === false
                ? 'CRM_ASAAS_SANDBOX_ENABLED'
                : 'CRM_ASAAS_SANDBOX_ENABLED='.$this->priorFlag);
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $this->owner->table('crm_lab_asaas_subscription_observations')->delete();
        $this->owner->table('crm_lab_asaas_subscription_drafts')->delete();
        $this->owner->table('crm_lab_asaas_order_reservations')->delete();
        $this->owner->table('crm_lab_asaas_customer_links')->delete();
    }

    public function test_order_and_subscription_share_one_linked_payer_without_unlocking_rights(): void
    {
        $this->owner->table('crm_lab_asaas_customer_links')->insert([
            'organization_ref' => self::ORG,
            'external_reference' => self::PAYER_REF,
            'provider_customer_id' => self::CUSTOMER,
            'state' => 'linked',
            'reserved_at' => 1700000000,
        ]);

        $order = (new LaboratoryAsaasOrderReservation($this->orderCreator))->reserve(
            self::ORG, self::ORDER_REF, self::CUSTOMER, 'basico', '2026-12-15',
        );
        $this->assertSame('new_reservation', $order['status_code']);
        $this->assertTrue($order['may_attempt_sandbox_once']);
        $this->assertFalse($order['can_activate']);

        $draft = (new LaboratoryAsaasSubscriptionDraft($this->subscriptionWriter))->persist(
            self::ORG, AsaasSubscriptionDraftSpec::preview(
                'basico', '2026-12-01', '2026-12-15', 'PIX', self::SUB_REF,
            ),
        );
        $this->assertSame('new_blocked_draft', $draft['status_code']);
        $this->assertFalse($draft['can_charge']);

        // A synthetic payment ID is intentionally linked by a distinct SQL
        // reviewer for testing relational integrity. NOT provider settlement.
        $linked = (new LaboratoryAsaasOrderPaymentReviewer($this->orderReviewer))->bind(
            self::ORG, self::ORDER_REF, self::CUSTOMER,
            'basico', '2026-12-15', self::PAYMENT,
        );
        $this->assertSame('payment_linked', $linked['status_code']);
        $this->assertFalse($linked['can_mark_paid']);

        Http::fake(['api-sandbox.asaas.com/v3/subscriptions*' =>
            function (Request $request) {
                $this->assertSame('GET', $request->method());
                $this->assertStringContainsString('externalReference='.self::SUB_REF, $request->url());
                $this->assertTrue($request->hasHeader('access_token', self::KEY));
                return Http::response([
                    'data' => [[
                        'id' => self::SUBSCRIPTION,
                        'externalReference' => self::SUB_REF,
                        'customer' => self::CUSTOMER,
                        'status' => 'ACTIVE',
                    ]],
                    'hasMore' => false,
                ], 200);
            },
        ]);

        $observation = (new LaboratoryAsaasSubscriptionObservation(
            $this->subscriptionReviewer,
            new AsaasSandboxSubscriptionLookup(Http::getFacadeRoot(), self::KEY),
        ))->reconcile(self::ORG, self::SUB_REF, 1700000060);
        $this->assertSame('synthetic_observed', $observation['status_code']);
        $this->assertFalse($observation['payment_verified']);
        $this->assertFalse($observation['can_activate']);
        $this->assertFalse($observation['can_invoice']);
        Http::assertSentCount(1);

        $row = $this->owner->selectOne(
            'SELECT o.organization_ref, o.provider_customer_id, o.provider_payment_id,
                    o.state AS order_state, d.state AS subscription_state,
                    d.monthly_cents, v.provider_subscription_id,
                    v.payment_verified, v.can_activate
               FROM crm_lab_asaas_order_reservations AS o
               JOIN crm_lab_asaas_customer_links AS c
                 ON c.organization_ref = o.organization_ref
                AND c.provider_customer_id = o.provider_customer_id
               JOIN crm_lab_asaas_subscription_drafts AS d
                 ON d.organization_ref = o.organization_ref
               JOIN crm_lab_asaas_subscription_observations AS v
                 ON v.organization_ref = d.organization_ref
              WHERE o.order_ref = ?::uuid',
            [self::ORDER_REF],
        );
        $this->assertNotNull($row);
        $this->assertSame(self::ORG, $row->organization_ref);
        $this->assertSame(self::CUSTOMER, $row->provider_customer_id);
        $this->assertSame(self::PAYMENT, $row->provider_payment_id);
        $this->assertSame('linked', $row->order_state);
        $this->assertSame('blocked_review', $row->subscription_state);
        $this->assertSame(6900, (int) $row->monthly_cents);
        $this->assertSame(self::SUBSCRIPTION, $row->provider_subscription_id);
        $this->assertFalse((bool) $row->payment_verified);
        $this->assertFalse((bool) $row->can_activate);

        // Role isolation: observation reviewer cannot read order ledger.
        $mayReadOrders = $this->subscriptionReviewer->selectOne(
            "SELECT has_table_privilege(current_user, 'crm_lab_asaas_order_reservations','SELECT') AS allowed",
        );
        $this->assertFalse((bool) $mayReadOrders->allowed);
    }

    public function test_unlinked_customer_cannot_create_order_or_draft(): void
    {
        $this->owner->table('crm_lab_asaas_customer_links')->insert([
            'organization_ref' => self::ORG,
            'external_reference' => self::PAYER_REF,
            'provider_customer_id' => null,
            'state' => 'pending_lookup',
            'reserved_at' => 1700000000,
        ]);
        try {
            (new LaboratoryAsaasOrderReservation($this->orderCreator))->reserve(
                self::ORG, self::ORDER_REF, self::CUSTOMER, 'basico', '2026-12-15',
            );
            $this->fail('Pending payer must not be usable by orders.');
        } catch (RuntimeException $error) {
            $this->assertSame('Reserva de pedido Asaas indisponivel.', $error->getMessage());
        }
        try {
            (new LaboratoryAsaasSubscriptionDraft($this->subscriptionWriter))->persist(
                self::ORG,
                AsaasSubscriptionDraftSpec::preview(
                    'basico', '2026-12-01', '2026-12-15', 'PIX', self::SUB_REF,
                ),
            );
            $this->fail('Pending payer must not create blocked subscription draft.');
        } catch (RuntimeException $error) {
            $this->assertSame('Registro de assinatura de laboratorio indisponivel.', $error->getMessage());
        }
        Http::assertNothingSent();
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_order_reservations')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());
    }
}
