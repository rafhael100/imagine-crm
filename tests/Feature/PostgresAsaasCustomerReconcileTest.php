<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSandboxCustomerGateway;
use App\Core\Billing\LaboratoryAsaasCustomerLink;
use App\Core\Billing\LaboratoryAsaasCustomerReconciler;
use Illuminate\Database\Connection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class PostgresAsaasCustomerReconcileTest extends TestCase
{
    private const ORG_A = '11111111-aaaa-4000-8000-111111111111';
    private const ORG_B = '22222222-bbbb-4000-8000-222222222222';
    private const EXT_A = 'aaaa1111-2222-4333-8444-555555555555';
    private const EXT_B = 'bbbb1111-2222-4333-8444-666666666666';
    private const CUSTOMER = 'cus_RECONCILE11111';
    private const API_KEY = '$aact_hmlg_ci-only-fake-sandbox-api-key-nonfunctional';

    private Connection $owner;
    private Connection $creator;
    private Connection $reviewer;
    private Connection $otherReviewer;
    private string|false $priorFlag;

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_LINK_LAB') !== 'synthetic-only'
            || getenv('BILLING_LINK_REVIEW_USERNAME') !== 'crm_billing_link_review_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('CI-only Asaas GET-only reconciliation.');
        }
        $this->priorFlag = getenv('CRM_ASAAS_SANDBOX_ENABLED');
        putenv('CRM_ASAAS_SANDBOX_ENABLED=true');
        Http::preventStrayRequests();
        $this->owner = DB::connection();
        $this->owner->table('crm_lab_asaas_customer_links')->delete();

        $cfg = $pg;
        $cfg['url'] = null;
        $roles = [
            'reconcile_creator_ci' => [
                getenv('BILLING_LINK_USERNAME'), getenv('BILLING_LINK_PASSWORD')],
            'reconcile_reviewer_ci' => [
                getenv('BILLING_LINK_REVIEW_USERNAME'), getenv('BILLING_LINK_REVIEW_PASSWORD')],
            'reconcile_other_reviewer_ci' => [
                getenv('BILLING_LINK_REVIEW_USERNAME'), getenv('BILLING_LINK_REVIEW_PASSWORD')],
        ];
        foreach ($roles as $name => [$user, $password]) {
            $roleCfg = $cfg;
            $roleCfg['username'] = $user;
            $roleCfg['password'] = $password;
            config(['database.connections.'.$name => $roleCfg]);
            DB::purge($name);
        }
        $this->creator = DB::connection('reconcile_creator_ci');
        $this->reviewer = DB::connection('reconcile_reviewer_ci');
        $this->otherReviewer = DB::connection('reconcile_other_reviewer_ci');
    }

    protected function tearDown(): void
    {
        if (isset($this->creator)) {
            foreach ([$this->creator, $this->reviewer, $this->otherReviewer] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->owner->table('crm_lab_asaas_customer_links')->delete();
            foreach (['reconcile_creator_ci', 'reconcile_reviewer_ci',
                'reconcile_other_reviewer_ci'] as $name) {
                DB::purge($name);
            }
        }
        if (isset($this->priorFlag)) {
            if ($this->priorFlag === false) {
                putenv('CRM_ASAAS_SANDBOX_ENABLED');
            } else {
                putenv('CRM_ASAAS_SANDBOX_ENABLED='.$this->priorFlag);
            }
        }
        parent::tearDown();
    }

    private function initialReservation(bool $requireReview = true): void
    {
        $link = new LaboratoryAsaasCustomerLink($this->creator);
        $link->reserve(self::ORG_A, self::EXT_A, 1700000000);
        if ($requireReview) {
            $link->requireReview(self::ORG_A, self::EXT_A);
        }
    }

    private function reconciler(?Connection $db = null): LaboratoryAsaasCustomerReconciler
    {
        return new LaboratoryAsaasCustomerReconciler(
            $db ?? $this->reviewer,
            new AsaasSandboxCustomerGateway(Http::getFacadeRoot(), self::API_KEY),
        );
    }

    private function mockFound(
        string $external = self::EXT_A,
        string $customer = self::CUSTOMER,
    ): void {
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' =>
                function (Request $r) use ($external, $customer) {
                    $this->assertSame('GET', $r->method(), 'Reviewer must never POST');
                    $this->assertStringContainsString('externalReference='.self::EXT_A, $r->url());
                    return Http::response([
                        'data' => [[
                            'id' => $customer,
                            'externalReference' => $external,
                        ]],
                        'hasMore' => false,
                    ], 200);
                },
        ]);
    }

    private function assertNeutral(array $out): void
    {
        $this->assertTrue($out['informational_only']);
        foreach (['can_create_customer', 'can_register', 'can_activate', 'can_charge'] as $flag) {
            $this->assertFalse($out[$flag], $flag);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([self::API_KEY, self::EXT_A, self::ORG_A, self::CUSTOMER] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function test_found_customer_reconciles_once_without_any_new_post(): void
    {
        $this->initialReservation();
        $this->mockFound();

        $first = $this->reconciler()->lookupAndReconcile(self::ORG_A, self::EXT_A);
        $again = $this->reconciler($this->otherReviewer)
            ->lookupAndReconcile(self::ORG_A, self::EXT_A);

        $this->assertSame('sandbox_reconciled', $first['status_code']);
        $this->assertSame('sandbox_already_linked', $again['status_code']);
        $this->assertTrue($first['provider_lookup_performed']);
        $this->assertFalse($again['provider_lookup_performed']);
        $this->assertNeutral($first);
        $this->assertNeutral($again);
        $this->assertSame('linked', $this->owner->table('crm_lab_asaas_customer_links')->value('state'));
        $this->assertSame(self::CUSTOMER, $this->owner->table('crm_lab_asaas_customer_links')
            ->value('provider_customer_id'));
        Http::assertSentCount(1);
    }

    public function test_absent_customer_does_not_create_a_new_one_or_change_review_status(): void
    {
        $this->initialReservation(false);
        Http::fake([
            'api-sandbox.asaas.com/v3/customers*' =>
                Http::response(['data' => [], 'hasMore' => false], 200),
        ]);
        $out = $this->reconciler()->lookupAndReconcile(self::ORG_A, self::EXT_A);

        $this->assertSame('sandbox_review_pending', $out['status_code']);
        $this->assertNeutral($out);
        $this->assertTrue($out['provider_lookup_performed']);
        $this->assertSame('review_required', $this->owner->table('crm_lab_asaas_customer_links')->value('state'));
        $this->assertNull($this->owner->table('crm_lab_asaas_customer_links')->value('provider_customer_id'));
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->method() === 'GET');
    }

    public function test_ambiguous_wrong_reference_and_provider_error_fail_closed(): void
    {
        foreach (['ambiguous', 'wrong', 'unavailable'] as $mode) {
            $this->owner->table('crm_lab_asaas_customer_links')->delete();
            $this->initialReservation();
            if ($mode === 'ambiguous') {
                Http::fake(['api-sandbox.asaas.com/v3/customers*' =>
                    Http::response(['data' => [
                        ['id' => self::CUSTOMER, 'externalReference' => self::EXT_A],
                        ['id' => 'cus_RECONCILE2222', 'externalReference' => self::EXT_A],
                    ], 'hasMore' => false], 200),
                ]);
            } elseif ($mode === 'wrong') {
                $this->mockFound(self::EXT_B);
            } else {
                Http::fake(['api-sandbox.asaas.com/v3/customers*' =>
                    Http::response(['error' => 'simulation'], 503),
                ]);
            }
            $out = $this->reconciler()->lookupAndReconcile(self::ORG_A, self::EXT_A);
            $this->assertSame('sandbox_review_pending', $out['status_code']);
            $this->assertNeutral($out);
            $this->assertSame('review_required', $this->owner->table('crm_lab_asaas_customer_links')->value('state'));
            $this->assertNull($this->owner->table('crm_lab_asaas_customer_links')->value('provider_customer_id'));
        }
    }

    public function test_collision_cannot_move_existing_provider_id_between_organizations(): void
    {
        $link = new LaboratoryAsaasCustomerLink($this->creator);
        $link->reserve(self::ORG_B, self::EXT_B, 1700000000);
        $link->bind(self::ORG_B, self::EXT_B, self::CUSTOMER);
        $this->initialReservation();
        $this->mockFound();

        try {
            $this->reconciler()->lookupAndReconcile(self::ORG_A, self::EXT_A);
            $this->fail('Cannot bind provider ID belonging to another organization.');
        } catch (RuntimeException $e) {
            $this->assertSame('Conciliacao Asaas indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }

        $this->assertSame('review_required', $this->owner->table('crm_lab_asaas_customer_links')
            ->where('organization_ref', self::ORG_A)->value('state'));
        $this->assertSame('linked', $this->owner->table('crm_lab_asaas_customer_links')
            ->where('organization_ref', self::ORG_B)->value('state'));
        Http::assertSentCount(1);
    }

    public function test_external_reference_mismatch_or_no_reservation_cannot_query_provider(): void
    {
        foreach ([[self::ORG_A, self::EXT_A]] as [$org, $ref]) {
            try {
                $this->reconciler()->lookupAndReconcile($org, $ref);
                $this->fail('Unreserved organization may not look up Asaas.');
            } catch (RuntimeException $e) {
                $this->assertSame('Referencia local de conciliacao nao autorizada.', $e->getMessage());
            }
        }
        $this->initialReservation();
        try {
            $this->reconciler()->lookupAndReconcile(self::ORG_A, self::EXT_B);
            $this->fail('Wrong external reference may not perform HTTP.');
        } catch (RuntimeException $e) {
            $this->assertSame('Referencia local de conciliacao nao autorizada.', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_open_transaction_and_wrong_db_actor_are_denied_before_http(): void
    {
        $this->initialReservation();
        $this->reviewer->beginTransaction();
        try {
            $this->reconciler()->lookupAndReconcile(self::ORG_A, self::EXT_A);
            $this->fail('Never GET during uncommitted reviewer transaction.');
        } catch (RuntimeException $e) {
            $this->assertSame('Reconciliacao Asaas nao permitida durante transacao.', $e->getMessage());
        } finally {
            $this->reviewer->rollBack();
        }

        foreach ([$this->creator, $this->owner] as $db) {
            try {
                $this->reconciler($db)->lookupAndReconcile(self::ORG_A, self::EXT_A);
                $this->fail('Creation/owner actor cannot reconcile.');
            } catch (RuntimeException $e) {
                $this->assertSame('Papel de conciliacao Asaas nao autorizado.', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    public function test_reviewer_role_cannot_insert_delete_or_change_references(): void
    {
        foreach ([$this->reviewer, $this->otherReviewer] as $db) {
            $role = $db->selectOne(
                'SELECT rolsuper, rolbypassrls, rolcreaterole FROM pg_roles WHERE rolname = current_user',
            );
            $this->assertFalse((bool) $role->rolsuper);
            $this->assertFalse((bool) $role->rolbypassrls);
            $this->assertFalse((bool) $role->rolcreaterole);
            foreach (['INSERT', 'DELETE', 'TRUNCATE', 'UPDATE'] as $op) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $op],
                )->allowed, $op);
            }
            foreach (['organization_ref', 'external_reference', 'reserved_at'] as $column) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'UPDATE'],
                )->allowed, $column);
            }
            foreach (['state', 'provider_customer_id'] as $column) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'UPDATE'],
                )->allowed, $column);
            }
        }
        Http::assertNothingSent();
    }

    public function test_reconciliation_has_no_public_route_or_worker(): void
    {
        $this->postJson('/api/asaas/reconcile', [])->assertNotFound();
        $this->get('/api/asaas/customers')->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratoryAsaasCustomerReconciler', $source);
        }
        Http::assertNothingSent();
    }
}
