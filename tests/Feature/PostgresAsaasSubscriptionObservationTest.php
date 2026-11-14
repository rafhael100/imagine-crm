<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxSubscriptionLookup;
use App\Core\Billing\AsaasSubscriptionDraftSpec;
use App\Core\Billing\LaboratoryAsaasCustomerLink;
use App\Core\Billing\LaboratoryAsaasSubscriptionDraft;
use App\Core\Billing\LaboratoryAsaasSubscriptionObservation;
use Illuminate\Database\Connection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class PostgresAsaasSubscriptionObservationTest extends TestCase
{
    private const ORG_A = '11111111-aaaa-4000-8000-111111111111';
    private const ORG_B = '22222222-bbbb-4000-8000-222222222222';
    private const CUSTOMER_REF_A = 'aaaa1111-2222-4333-8444-555555555555';
    private const CUSTOMER_REF_B = 'bbbb1111-2222-4333-8444-666666666666';
    private const SUB_REF_A = 'dddd1111-2222-4333-8444-555555555555';
    private const SUB_REF_B = 'dddd2222-2222-4333-8444-666666666666';
    private const CUSTOMER_A = 'cus_OBSERVE111111';
    private const CUSTOMER_B = 'cus_OBSERVE222222';
    private const SUBSCRIPTION_ID = 'sub_OBSERVE111111';
    private const KEY = '$aact_hmlg_ci-only-fake-sandbox-api-key-nonfunctional';

    private Connection $admin;
    private Connection $payer;
    private Connection $draftWriter;
    private Connection $reviewOne;
    private Connection $reviewTwo;
    private string|false $oldFlag;

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_SUBSCRIPTION_OBSERVATION_LAB') !== 'synthetic-only'
            || getenv('BILLING_SUBSCRIPTION_REVIEW_USERNAME') !== 'crm_billing_subscription_review_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('CI-only subscription observation.');
        }

        $this->oldFlag = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests();

        $this->admin = DB::connection();
        $this->clean();

        $base = $pg;
        $base['url'] = null;
        foreach ([
            'subobs_payer_ci' => [getenv('BILLING_LINK_USERNAME'), getenv('BILLING_LINK_PASSWORD')],
            'subobs_draft_ci' => [getenv('BILLING_SUBSCRIPTION_USERNAME'), getenv('BILLING_SUBSCRIPTION_PASSWORD')],
            'subobs_reviewer_one_ci' => [getenv('BILLING_SUBSCRIPTION_REVIEW_USERNAME'), getenv('BILLING_SUBSCRIPTION_REVIEW_PASSWORD')],
            'subobs_reviewer_two_ci' => [getenv('BILLING_SUBSCRIPTION_REVIEW_USERNAME'), getenv('BILLING_SUBSCRIPTION_REVIEW_PASSWORD')],
        ] as $name => [$user, $pass]) {
            $cfg = $base;
            $cfg['username'] = $user;
            $cfg['password'] = $pass;
            config(['database.connections.'.$name => $cfg]);
            DB::purge($name);
        }
        $this->payer = DB::connection('subobs_payer_ci');
        $this->draftWriter = DB::connection('subobs_draft_ci');
        $this->reviewOne = DB::connection('subobs_reviewer_one_ci');
        $this->reviewTwo = DB::connection('subobs_reviewer_two_ci');
        $this->reviewTwo->statement("SET lock_timeout = '250ms'");
    }

    protected function tearDown(): void
    {
        if (isset($this->reviewOne)) {
            foreach ([$this->reviewOne, $this->reviewTwo, $this->payer, $this->draftWriter] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->clean();
            foreach ([
                'subobs_payer_ci', 'subobs_draft_ci',
                'subobs_reviewer_one_ci', 'subobs_reviewer_two_ci',
            ] as $name) {
                DB::purge($name);
            }
        }
        if (isset($this->oldFlag)) {
            if ($this->oldFlag === false) {
                putenv('CRM_ASAAS_SANDBOX_ENABLED');
            } else {
                putenv('CRM_ASAAS_SANDBOX_ENABLED='.$this->oldFlag);
            }
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $this->admin->table('crm_lab_asaas_subscription_observations')->delete();
        $this->admin->table('crm_lab_asaas_subscription_drafts')->delete();
        $this->admin->table('crm_lab_asaas_customer_links')->delete();
    }

    private function fixture(
        string $org = self::ORG_A,
        string $payerRef = self::CUSTOMER_REF_A,
        string $subRef = self::SUB_REF_A,
        string $payerId = self::CUSTOMER_A,
        bool $draft = true,
    ): void {
        $links = new LaboratoryAsaasCustomerLink($this->payer);
        $links->reserve($org, $payerRef, 1700000000);
        $links->bind($org, $payerRef, $payerId);
        if ($draft) {
            (new LaboratoryAsaasSubscriptionDraft($this->draftWriter))->persist(
                $org,
                AsaasSubscriptionDraftSpec::preview(
                    'essencial', '2026-11-14', '2026-11-28', 'PIX', $subRef,
                ),
            );
        }
    }

    private function mockRemote(
        string $customer = self::CUSTOMER_A,
        string $subscription = self::SUBSCRIPTION_ID,
        string $reference = self::SUB_REF_A,
        int $statusCode = 200,
        bool $hasMore = false,
    ): void {
        Http::fake([
            'api-sandbox.asaas.com/v3/subscriptions*' => function (Request $req)
                use ($customer, $subscription, $reference, $statusCode, $hasMore) {
                $this->assertSame('GET', $req->method());
                $this->assertStringContainsString(
                    'externalReference='.self::SUB_REF_A, $req->url(),
                );
                return Http::response([
                    'hasMore' => $hasMore,
                    'data' => [[
                        'id' => $subscription,
                        'customer' => $customer,
                        'externalReference' => $reference,
                        'status' => 'ACTIVE',
                        'value' => 89.00,
                    ]],
                ], $statusCode);
            },
        ]);
    }

    private function observe(?Connection $db = null, string $org = self::ORG_A, string $ref = self::SUB_REF_A): array
    {
        return (new LaboratoryAsaasSubscriptionObservation(
            $db ?? $this->reviewOne,
            new AsaasSandboxSubscriptionLookup(Http::getFacadeRoot(), self::KEY),
        ))->reconcile($org, $ref, 1700000020);
    }

    private function assertNeverGrants(array $out): void
    {
        $this->assertTrue($out['informational_only']);
        foreach ([
            'payment_verified', 'terms_accepted', 'can_create_subscription',
            'can_charge', 'can_activate', 'can_invoice',
        ] as $key) {
            $this->assertFalse($out[$key], $key);
        }
        $data = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([self::SUB_REF_A, self::ORG_A, self::CUSTOMER_A, self::SUBSCRIPTION_ID, self::KEY] as $secret) {
            $this->assertStringNotContainsString($secret, $data);
        }
    }

    public function test_first_lookup_persists_one_observation_and_second_worker_never_calls_asaas_again(): void
    {
        $this->fixture();
        $this->mockRemote();

        $first = $this->observe();
        $second = $this->observe($this->reviewTwo);

        $this->assertSame('synthetic_observed', $first['status_code']);
        $this->assertTrue($first['lookup_performed']);
        $this->assertSame('synthetic_already_observed', $second['status_code']);
        $this->assertFalse($second['lookup_performed']);
        $this->assertNeverGrants($first);
        $this->assertNeverGrants($second);
        Http::assertSentCount(1);

        $row = $this->admin->table('crm_lab_asaas_subscription_observations')->first();
        $this->assertSame(self::SUBSCRIPTION_ID, $row->provider_subscription_id);
        $this->assertSame('observed_no_rights', $row->state);
        foreach (['payment_verified','terms_accepted','can_charge','can_activate'] as $flag) {
            $this->assertFalse((bool) $row->{$flag}, $flag);
        }
    }

    public function test_missing_draft_or_wrong_reference_never_calls_provider(): void
    {
        $this->fixture(draft: false);

        foreach ([self::SUB_REF_A, self::SUB_REF_B] as $ref) {
            try {
                $this->observe(ref: $ref);
                $this->fail('Unreviewed subscription draft must never be queried.');
            } catch (RuntimeException $e) {
                $this->assertSame('Verificacao local de assinatura indisponivel.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
        Http::assertNothingSent();
        $this->assertSame(0, $this->admin->table('crm_lab_asaas_subscription_observations')->count());
    }

    public function test_absent_or_ambiguous_provider_result_never_persists_a_subscription(): void
    {
        $this->fixture();
        foreach (['absent', 'wrong_payer', 'server_error', 'has_more'] as $case) {
            if ($case === 'absent') {
                Http::fake(['api-sandbox.asaas.com/v3/subscriptions*' =>
                    Http::response(['data' => [], 'hasMore' => false], 200),
                ]);
            } else {
                $this->mockRemote(
                    customer: $case === 'wrong_payer' ? self::CUSTOMER_B : self::CUSTOMER_A,
                    statusCode: $case === 'server_error' ? 503 : 200,
                    hasMore: $case === 'has_more',
                );
            }
            $result = $this->observe();
            $this->assertSame('synthetic_review_required', $result['status_code']);
            $this->assertNeverGrants($result);
            $this->assertSame(0, $this->admin->table('crm_lab_asaas_subscription_observations')->count());
        }
    }

    public function test_provider_id_collision_between_two_organizations_fails_closed(): void
    {
        $this->fixture();
        $this->mockRemote();
        $this->observe();

        $this->fixture(self::ORG_B, self::CUSTOMER_REF_B, self::SUB_REF_B, self::CUSTOMER_B);
        Http::fake(['api-sandbox.asaas.com/v3/subscriptions*' =>
            Http::response([
                'hasMore' => false,
                'data' => [[
                    'id' => self::SUBSCRIPTION_ID,
                    'customer' => self::CUSTOMER_B,
                    'externalReference' => self::SUB_REF_B,
                ]],
            ], 200),
        ]);
        try {
            $this->observe($this->reviewTwo, self::ORG_B, self::SUB_REF_B);
            $this->fail('Same provider subscription cannot belong to two organizations.');
        } catch (RuntimeException $e) {
            $this->assertSame('Persistencia de assinatura em revisao indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
        $this->assertSame(1, $this->admin->table('crm_lab_asaas_subscription_observations')->count());
    }

    public function test_reviewer_transaction_open_and_wrong_role_are_denied_without_http(): void
    {
        $this->fixture();
        $this->reviewOne->beginTransaction();
        try {
            $this->observe();
            $this->fail('Provider GET during uncommitted SQL is forbidden.');
        } catch (RuntimeException $e) {
            $this->assertSame('Conciliacao de assinatura requer autocommit.', $e->getMessage());
        } finally {
            $this->reviewOne->rollBack();
        }

        foreach ([$this->admin, $this->draftWriter] as $wrongRole) {
            try {
                $this->observe($wrongRole);
                $this->fail('Unauthorized DB role was allowed.');
            } catch (RuntimeException $e) {
                $this->assertSame('Papel de revisao de assinatura nao autorizado.', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    public function test_reviewer_cannot_approve_terms_charge_or_update_observation(): void
    {
        foreach ([$this->reviewOne, $this->reviewTwo] as $db) {
            $role = $db->selectOne(
                'SELECT rolsuper, rolbypassrls, rolcreaterole FROM pg_roles WHERE rolname = current_user',
            );
            $this->assertFalse((bool) $role->rolsuper);
            $this->assertFalse((bool) $role->rolbypassrls);
            $this->assertFalse((bool) $role->rolcreaterole);

            foreach (['UPDATE', 'DELETE', 'TRUNCATE', 'INSERT'] as $operation) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_asaas_subscription_observations', $operation],
                )->allowed);
            }
            foreach (['can_activate','can_charge','payment_verified','terms_accepted','state'] as $column) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_subscription_observations', $column, 'INSERT'],
                )->allowed);
            }
            foreach (['provider_subscription_id','organization_ref','external_reference','observed_at'] as $column) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_subscription_observations', $column, 'INSERT'],
                )->allowed);
            }
            foreach (['provider_customer_id','state'] as $column) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'SELECT'],
                )->allowed);
            }
        }
    }

    public function test_application_has_no_live_subscription_or_finance_routes(): void
    {
        $this->postJson('/api/asaas/subscriptions', [])->assertNotFound();
        $this->postJson('/api/checkout', [])->assertNotFound();
        $this->get('/api/asaas/lookup-subscription')->assertNotFound();
        foreach (['routes/web.php','bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratoryAsaasSubscriptionObservation', $source);
        }
        Http::assertNothingSent();
    }
}
