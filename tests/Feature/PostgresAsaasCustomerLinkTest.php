<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxCustomerGateway;
use App\Core\Billing\LaboratoryAsaasCustomerHandoff;
use App\Core\Billing\LaboratoryAsaasCustomerLink;
use Illuminate\Database\Connection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * BILL-ASAAS-05/06: real ephemeral PG16 uniqueness + mocked Asaas HTTP.
 *
 * There is no live provider, customer, tenant, consent, registration, route,
 * subscription, money, invoice or payment in these tests.
 */
final class PostgresAsaasCustomerLinkTest extends TestCase
{
    private const ORG_A = '11111111-aaaa-4000-8000-111111111111';
    private const ORG_B = '22222222-bbbb-4000-8000-222222222222';
    private const EXT_A = 'aaaa1111-2222-4333-8444-555555555555';
    private const EXT_B = 'bbbb1111-2222-4333-8444-666666666666';
    private const PROVIDER_A = 'cus_TESTLINK100001';
    private const PROVIDER_B = 'cus_TESTLINK200002';
    private const AUTH = '$aact_hmlg_ci-only-fake-sandbox-api-key-nonfunctional';
    private const NOW = 1700000010;

    private Connection $owner;
    private Connection $one;
    private Connection $two;
    private string|false $sandboxBefore;

    protected function setUp(): void
    {
        parent::setUp();

        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_LINK_LAB') !== 'synthetic-only'
            || getenv('BILLING_LINK_USERNAME') !== 'crm_billing_link_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('CI-only ephemeral Asaas organization link.');
        }

        $this->sandboxBefore = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests();
        $this->owner = DB::connection();
        $this->owner->table('crm_lab_asaas_customer_links')->delete();

        $cfg = $pg;
        $cfg['url'] = null;
        $cfg['username'] = getenv('BILLING_LINK_USERNAME');
        $cfg['password'] = getenv('BILLING_LINK_PASSWORD');
        config([
            'database.connections.asaas_link_one_ci' => $cfg,
            'database.connections.asaas_link_two_ci' => $cfg,
        ]);
        DB::purge('asaas_link_one_ci');
        DB::purge('asaas_link_two_ci');
        $this->one = DB::connection('asaas_link_one_ci');
        $this->two = DB::connection('asaas_link_two_ci');
    }

    protected function tearDown(): void
    {
        if (isset($this->one)) {
            foreach ([$this->one, $this->two] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->owner->table('crm_lab_asaas_customer_links')->delete();
            DB::purge('asaas_link_one_ci');
            DB::purge('asaas_link_two_ci');
        }
        if (isset($this->sandboxBefore)) {
            if ($this->sandboxBefore === false) {
                putenv('CRM_ASAAS_SANDBOX_ENABLED');
            } else {
                putenv('CRM_ASAAS_SANDBOX_ENABLED='.$this->sandboxBefore);
            }
        }
        parent::tearDown();
    }

    private function link(Connection $db): LaboratoryAsaasCustomerLink
    {
        return new LaboratoryAsaasCustomerLink($db);
    }

    private function flow(Connection $db): LaboratoryAsaasCustomerHandoff
    {
        return new LaboratoryAsaasCustomerHandoff(
            $this->link($db),
            new AsaasSandboxCustomerGateway(Http::getFacadeRoot(), self::AUTH),
        );
    }

    private function assertDenied(array $reply): void
    {
        foreach (['can_register', 'can_activate', 'can_charge'] as $flag) {
            $this->assertFalse($reply[$flag], $flag);
        }
        $content = json_encode($reply, JSON_THROW_ON_ERROR);
        foreach ([self::AUTH, self::ORG_A, self::EXT_A, self::PROVIDER_A] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $content);
        }
    }

    private function mockNotFoundThenCreate(string $customer = self::PROVIDER_A): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' => function (Request $request) use ($customer) {
                if ($request->method() === 'GET') {
                    $this->assertStringContainsString('externalReference='.self::EXT_A, $request->url());
                    return Http::response(['data' => [], 'hasMore' => false], 200);
                }
                $this->assertSame('POST', $request->method());
                $this->assertSame('https://api-sandbox.asaas.com/v3/customers', $request->url());
                $this->assertTrue($request->data()['notificationDisabled']);
                $this->assertSame(self::EXT_A, $request->data()['externalReference']);
                return Http::response([
                    'id' => $customer, 'externalReference' => self::EXT_A,
                ], 200);
            },
        ]);
    }

    public function test_two_connections_get_only_one_new_reservation_and_never_reuse_external_ref(): void
    {
        $first = $this->link($this->one)->reserve(self::ORG_A, self::EXT_A, self::NOW);
        $again = $this->link($this->two)->reserve(self::ORG_A, self::EXT_A, self::NOW + 1);

        $this->assertSame('new_reservation', $first['status_code']);
        $this->assertTrue($first['may_attempt_sandbox_once']);
        $this->assertSame('existing_pending', $again['status_code']);
        $this->assertFalse($again['may_attempt_sandbox_once']);
        $this->assertDenied($first);
        $this->assertDenied($again);
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_customer_links')->count());

        foreach ([
            [self::ORG_B, self::EXT_A],
            [self::ORG_A, self::EXT_B],
        ] as [$org, $external]) {
            try {
                $this->link($this->two)->reserve($org, $external, self::NOW + 3);
                $this->fail('Mismatched organization/external reference allowed.');
            } catch (RuntimeException $e) {
                $this->assertSame('Reserva de cliente Asaas indisponivel.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_customer_links')->count());
        Http::assertNothingSent();
    }

    public function test_bound_provider_id_is_immutable_idempotent_and_unique_globally(): void
    {
        $this->link($this->one)->reserve(self::ORG_A, self::EXT_A, self::NOW);
        $this->link($this->two)->reserve(self::ORG_B, self::EXT_B, self::NOW);

        $first = $this->link($this->one)->bind(self::ORG_A, self::EXT_A, self::PROVIDER_A);
        $same = $this->link($this->two)->bind(self::ORG_A, self::EXT_A, self::PROVIDER_A);
        $this->assertSame('new_link', $first['status_code']);
        $this->assertSame('same_link', $same['status_code']);
        $this->assertDenied($first);
        $this->assertDenied($same);

        foreach ([
            [self::ORG_A, self::EXT_A, self::PROVIDER_B],
            [self::ORG_A, self::EXT_B, self::PROVIDER_A],
            [self::ORG_B, self::EXT_B, self::PROVIDER_A],
        ] as [$org, $ext, $provider]) {
            try {
                $this->link($this->two)->bind($org, $ext, $provider);
                $this->fail('Provider id reassignment accepted.');
            } catch (RuntimeException $e) {
                $this->assertSame('Vinculo de cliente Asaas indisponivel.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }

        $this->assertSame(2, $this->owner->table('crm_lab_asaas_customer_links')->count());
        $this->assertSame(self::PROVIDER_A, $this->owner->table('crm_lab_asaas_customer_links')
            ->where('organization_ref', self::ORG_A)->value('provider_customer_id'));
        $this->assertNull($this->owner->table('crm_lab_asaas_customer_links')
            ->where('organization_ref', self::ORG_B)->value('provider_customer_id'));
        Http::assertNothingSent();
    }

    public function test_sandbox_http_create_is_never_repeated_by_a_second_process(): void
    {
        $this->mockNotFoundThenCreate();

        $new = $this->flow($this->one)->run(self::ORG_A, self::EXT_A, self::NOW);
        $replay = $this->flow($this->two)->run(self::ORG_A, self::EXT_A, self::NOW + 1);

        $this->assertSame('sandbox_linked', $new['status_code']);
        $this->assertSame('sandbox_already_linked', $replay['status_code']);
        $this->assertTrue($new['provider_request_may_have_occurred']);
        $this->assertFalse($replay['provider_request_may_have_occurred']);
        $this->assertFalse($new['can_activate']);
        $this->assertFalse($replay['can_charge']);
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_customer_links')->count());
        $this->assertSame('linked', $this->owner->table('crm_lab_asaas_customer_links')->value('state'));
        Http::assertSentCount(2); // exactly one GET + one POST
    }

    public function test_existing_sandbox_customer_is_reused_with_no_post(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' => Http::response([
                'data' => [[
                    'id' => self::PROVIDER_B,
                    'externalReference' => self::EXT_A,
                ]], 'hasMore' => false,
            ], 200),
        ]);

        $result = $this->flow($this->one)->run(self::ORG_A, self::EXT_A, self::NOW);
        $this->assertSame('sandbox_linked', $result['status_code']);
        $this->assertSame(self::PROVIDER_B, $this->owner->table('crm_lab_asaas_customer_links')
            ->value('provider_customer_id'));
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $req) => $req->method() === 'GET');
    }

    public function test_uncertain_sandbox_failure_is_review_only_without_auto_retry(): void
    {
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' => Http::response(
                ['errors' => [['code' => 'simulated_provider_unavailable']]], 503,
            ),
        ]);

        $out = $this->flow($this->one)->run(self::ORG_A, self::EXT_A, self::NOW);
        $again = $this->flow($this->two)->run(self::ORG_A, self::EXT_A, self::NOW + 1);

        $this->assertSame('sandbox_manual_review_required', $out['status_code']);
        $this->assertSame('sandbox_manual_review_required', $again['status_code']);
        $this->assertTrue($out['provider_request_may_have_occurred']);
        $this->assertFalse($again['provider_request_may_have_occurred']);
        $this->assertSame('review_required', $this->owner->table('crm_lab_asaas_customer_links')->value('state'));
        $this->assertNull($this->owner->table('crm_lab_asaas_customer_links')->value('provider_customer_id'));
        $this->assertFalse($again['can_register']);
        Http::assertSentCount(1); // no attempt from second connection
    }

    public function test_early_reservation_with_lost_response_is_not_retried(): void
    {
        $this->link($this->one)->reserve(self::ORG_A, self::EXT_A, self::NOW);
        $again = $this->flow($this->two)->run(self::ORG_A, self::EXT_A, self::NOW + 1);

        $this->assertSame('sandbox_manual_review_required', $again['status_code']);
        $this->assertFalse($again['provider_request_may_have_occurred']);
        $this->assertSame('pending_lookup', $this->owner->table('crm_lab_asaas_customer_links')->value('state'));
        Http::assertNothingSent();
    }

    public function test_foreign_provider_id_collision_goes_to_review_not_overwrite(): void
    {
        $this->link($this->one)->reserve(self::ORG_B, self::EXT_B, self::NOW);
        $this->link($this->one)->bind(self::ORG_B, self::EXT_B, self::PROVIDER_A);

        $this->mockNotFoundThenCreate(self::PROVIDER_A);
        $failed = $this->flow($this->two)->run(self::ORG_A, self::EXT_A, self::NOW);

        $this->assertSame('sandbox_manual_review_required', $failed['status_code']);
        $this->assertSame('linked', $this->owner->table('crm_lab_asaas_customer_links')
            ->where('organization_ref', self::ORG_B)->value('state'));
        $this->assertSame('review_required', $this->owner->table('crm_lab_asaas_customer_links')
            ->where('organization_ref', self::ORG_A)->value('state'));
        $this->assertSame(2, $this->owner->table('crm_lab_asaas_customer_links')->count());
        Http::assertSentCount(2);
    }

    public function test_transaction_rollback_releases_a_reservation_without_external_io(): void
    {
        $this->one->beginTransaction();
        $this->link($this->one)->reserve(self::ORG_A, self::EXT_A, self::NOW);
        $this->one->rollBack();

        $this->assertSame(0, $this->owner->table('crm_lab_asaas_customer_links')->count());
        $new = $this->link($this->two)->reserve(self::ORG_A, self::EXT_A, self::NOW + 1);
        $this->assertSame('new_reservation', $new['status_code']);
        Http::assertNothingSent();
    }

    public function test_never_send_sandbox_http_while_reservation_transaction_is_uncommitted(): void
    {
        $this->mockNotFoundThenCreate();
        $this->one->beginTransaction();
        try {
            $this->flow($this->one)->run(self::ORG_A, self::EXT_A, self::NOW);
            $this->fail('External HTTP must not run while reservation cannot be committed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Asaas sandbox nao permitido durante transacao aberta.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        } finally {
            $this->one->rollBack();
        }

        Http::assertNothingSent();
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_customer_links')->count());
    }

    public function test_role_has_only_synthetic_column_grants_and_owner_cannot_act_as_runtime(): void
    {
        foreach ([$this->one, $this->two] as $db) {
            $role = $db->selectOne(
                'SELECT rolsuper, rolbypassrls, rolcreaterole FROM pg_roles WHERE rolname = current_user',
            );
            $this->assertFalse((bool) $role->rolsuper);
            $this->assertFalse((bool) $role->rolbypassrls);
            $this->assertFalse((bool) $role->rolcreaterole);

            foreach (['DELETE', 'TRUNCATE', 'UPDATE', 'INSERT'] as $verb) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $verb],
                )->allowed, $verb);
            }
            foreach (['organization_ref', 'external_reference', 'reserved_at'] as $column) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'INSERT'],
                )->allowed, $column);
            }
            foreach (['provider_customer_id', 'state'] as $column) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'UPDATE'],
                )->allowed, $column);
            }
            foreach (['organization_ref', 'external_reference', 'reserved_at'] as $column) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'UPDATE'],
                )->allowed, $column);
            }
        }

        try {
            $this->link($this->owner)->reserve(self::ORG_A, self::EXT_A, self::NOW);
            $this->fail('Owner role must not perform synthetic runtime link.');
        } catch (RuntimeException $e) {
            $this->assertSame('Papel de vinculo Asaas nao autorizado.', $e->getMessage());
        }
    }

    public function test_no_asas_customer_link_service_is_published_in_any_route(): void
    {
        $this->get('/crm/login')->assertNotFound();
        $this->postJson('/api/asaas/customers', [])->assertNotFound();
        $this->postJson('/api/billing/link-customer', [])->assertNotFound();

        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratoryAsaasCustomerHandoff', $source);
            $this->assertStringNotContainsString('LaboratoryAsaasCustomerLink', $source);
        }
        Http::assertNothingSent();
    }
}
