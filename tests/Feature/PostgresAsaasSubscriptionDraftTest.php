<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasSubscriptionDraftSpec;
use App\Core\Billing\LaboratoryAsaasCustomerLink;
use App\Core\Billing\LaboratoryAsaasSubscriptionDraft;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

final class PostgresAsaasSubscriptionDraftTest extends TestCase
{
    private const ORG_A = '11111111-aaaa-4000-8000-111111111111';
    private const ORG_B = '22222222-bbbb-4000-8000-222222222222';
    private const PAYER_REF_A = 'aaaa1111-2222-4333-8444-555555555555';
    private const PAYER_REF_B = 'bbbb1111-2222-4333-8444-666666666666';
    private const DRAFT_REF_A = 'dddd1111-2222-4333-8444-555555555555';
    private const DRAFT_REF_B = 'dddd2222-2222-4333-8444-666666666666';
    private const PAYER_A = 'cus_SUBDRAFT11111';
    private const PAYER_B = 'cus_SUBDRAFT22222';

    private Connection $owner;
    private Connection $link;
    private Connection $one;
    private Connection $two;

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_SUBSCRIPTION_LAB') !== 'synthetic-only'
            || getenv('BILLING_SUBSCRIPTION_USERNAME') !== 'crm_billing_subscription_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('Disposable PostgreSQL subscription draft only.');
        }

        $this->owner = DB::connection();
        $this->clean();

        $config = $pg;
        $config['url'] = null;
        $roles = [
            'subscription_payer_link_ci' => [getenv('BILLING_LINK_USERNAME'), getenv('BILLING_LINK_PASSWORD')],
            'subscription_draft_one_ci' => [
                getenv('BILLING_SUBSCRIPTION_USERNAME'), getenv('BILLING_SUBSCRIPTION_PASSWORD'),
            ],
            'subscription_draft_two_ci' => [
                getenv('BILLING_SUBSCRIPTION_USERNAME'), getenv('BILLING_SUBSCRIPTION_PASSWORD'),
            ],
        ];
        foreach ($roles as $name => [$username, $password]) {
            $cfg = $config;
            $cfg['username'] = $username;
            $cfg['password'] = $password;
            config(['database.connections.'.$name => $cfg]);
            DB::purge($name);
        }

        $this->link = DB::connection('subscription_payer_link_ci');
        $this->one = DB::connection('subscription_draft_one_ci');
        $this->two = DB::connection('subscription_draft_two_ci');
        $this->two->statement("SET lock_timeout = '200ms'");
    }

    protected function tearDown(): void
    {
        if (isset($this->one)) {
            foreach ([$this->link, $this->one, $this->two] as $connection) {
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            }
            $this->clean();
            foreach ([
                'subscription_payer_link_ci', 'subscription_draft_one_ci', 'subscription_draft_two_ci',
            ] as $name) {
                DB::purge($name);
            }
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $this->owner->table('crm_lab_asaas_subscription_drafts')->delete();
        $this->owner->table('crm_lab_asaas_customer_links')->delete();
    }

    private function createPayerLink(
        string $org = self::ORG_A,
        string $externalRef = self::PAYER_REF_A,
        string $payer = self::PAYER_A,
        bool $linked = true,
    ): void {
        $svc = new LaboratoryAsaasCustomerLink($this->link);
        $svc->reserve($org, $externalRef, 1700000000);
        if ($linked) {
            $svc->bind($org, $externalRef, $payer);
        }
    }

    private static function spec(
        string $plan = 'essencial',
        string $ref = self::DRAFT_REF_A,
        string $firstDue = '2026-11-28',
    ): array {
        return AsaasSubscriptionDraftSpec::preview(
            $plan, '2026-11-14', $firstDue, 'PIX', $ref,
        );
    }

    private function drafts(Connection $db): LaboratoryAsaasSubscriptionDraft
    {
        return new LaboratoryAsaasSubscriptionDraft($db);
    }

    private function assertDenied(array $out): void
    {
        $this->assertTrue($out['informational_only']);
        foreach (['price_homologated', 'terms_accepted',
            'can_create_subscription', 'can_charge', 'can_activate'] as $key) {
            $this->assertFalse($out[$key], $key);
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([self::ORG_A, self::PAYER_A, self::PAYER_REF_A, self::DRAFT_REF_A] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function test_draft_requires_linked_payer_before_recording(): void
    {
        $spec = self::spec();
        try {
            $this->drafts($this->one)->persist(self::ORG_A, $spec);
            $this->fail('Unregistered organization accepted.');
        } catch (RuntimeException $e) {
            $this->assertSame('Registro de assinatura de laboratorio indisponivel.', $e->getMessage());
        }

        $this->createPayerLink(linked: false);
        try {
            $this->drafts($this->one)->persist(self::ORG_A, $spec);
            $this->fail('Pending payer association accepted.');
        } catch (RuntimeException $e) {
            $this->assertSame('Registro de assinatura de laboratorio indisponivel.', $e->getMessage());
        }

        $this->assertSame(0, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());

        $this->linkService()->bind(self::ORG_A, self::PAYER_REF_A, self::PAYER_A);
        $first = $this->drafts($this->one)->persist(self::ORG_A, $spec);
        $this->assertSame('new_blocked_draft', $first['status_code']);
        $this->assertDenied($first);
    }

    private function linkService(): LaboratoryAsaasCustomerLink
    {
        return new LaboratoryAsaasCustomerLink($this->link);
    }

    public function test_idempotency_preserves_first_price_plan_method_and_due_date(): void
    {
        $this->createPayerLink();
        $spec = self::spec();
        $first = $this->drafts($this->one)->persist(self::ORG_A, $spec);
        $duplicate = $this->drafts($this->two)->persist(self::ORG_A, $spec);
        $this->assertSame('new_blocked_draft', $first['status_code']);
        $this->assertSame('existing_identical_draft', $duplicate['status_code']);
        $this->assertDenied($first);
        $this->assertDenied($duplicate);

        $rows = $this->owner->table('crm_lab_asaas_subscription_drafts')->get();
        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame('essencial', $row->plan_code);
        $this->assertSame(8900, $row->monthly_cents);
        $this->assertSame('PIX', $row->billing_type);
        $this->assertSame('MONTHLY', $row->cycle);
        $this->assertSame('2026-11-28', $row->first_due_on);
        $this->assertSame('blocked_review', $row->state);
        foreach ([
            'price_homologated', 'terms_accepted', 'company_owner_verified',
            'can_create_subscription', 'can_charge', 'can_activate', 'can_invoice',
        ] as $flag) {
            $this->assertFalse((bool) $row->{$flag}, $flag);
        }
        $this->assertNull($this->owner->table('crm_lab_asaas_subscription_drafts')
            ->where('organization_ref', self::ORG_B)->first());
    }

    public function test_same_org_cannot_change_plan_due_date_or_reference_by_replay(): void
    {
        $this->createPayerLink();
        $this->drafts($this->one)->persist(self::ORG_A, self::spec());

        foreach ([
            self::spec('profissional'),
            self::spec('essencial', self::DRAFT_REF_A, '2026-12-28'),
            self::spec('essencial', self::DRAFT_REF_B),
        ] as $changed) {
            try {
                $this->drafts($this->two)->persist(self::ORG_A, $changed);
                $this->fail('Silent change of the accepted draft snapshot was allowed.');
            } catch (RuntimeException $e) {
                $this->assertSame('Registro de assinatura de laboratorio indisponivel.', $e->getMessage());
            }
        }
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());
        $this->assertSame(8900, $this->owner->table('crm_lab_asaas_subscription_drafts')
            ->first()->monthly_cents);
    }

    public function test_global_draft_reference_cannot_be_reassigned_to_other_org(): void
    {
        $this->createPayerLink();
        $this->createPayerLink(self::ORG_B, self::PAYER_REF_B, self::PAYER_B);
        $this->drafts($this->one)->persist(self::ORG_A, self::spec());

        try {
            $this->drafts($this->two)->persist(self::ORG_B, self::spec());
            $this->fail('Global reference collision not rejected.');
        } catch (RuntimeException $e) {
            $this->assertSame('Registro de assinatura de laboratorio indisponivel.', $e->getMessage());
        }

        $this->assertSame('new_blocked_draft', $this->drafts($this->two)
            ->persist(self::ORG_B, self::spec('profissional', self::DRAFT_REF_B))['status_code']);
        $this->assertSame(2, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());
    }

    public function test_tampered_plan_price_and_any_true_entitlement_are_rejected(): void
    {
        $this->createPayerLink();
        foreach ([
            ['monthly_cents', 1],
            ['can_charge', true],
            ['price_homologated', true],
            ['terms_accepted', true],
            ['trial_ends_on', '2026-11-15'],
            ['cycle', 'WEEKLY'],
            ['currency', 'USD'],
        ] as [$key, $value]) {
            $fake = self::spec();
            $fake[$key] = $value;
            try {
                $this->drafts($this->one)->persist(self::ORG_A, $fake);
                $this->fail('Tampered draft accepted: '.$key);
            } catch (InvalidArgumentException $e) {
                $this->assertStringNotContainsString(self::ORG_A, $e->getMessage());
            }
        }
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());
    }

    public function test_two_transactions_compete_on_organization_draft_primary_key(): void
    {
        $this->createPayerLink();
        $spec = self::spec();
        $this->one->beginTransaction();
        $this->assertSame('new_blocked_draft',
            $this->drafts($this->one)->persist(self::ORG_A, $spec)['status_code']);

        $this->two->beginTransaction();
        try {
            $this->drafts($this->two)->persist(self::ORG_A, $spec);
            $this->fail('Second process must wait for the uncommitted unique key.');
        } catch (RuntimeException $e) {
            $this->assertSame('Registro de assinatura de laboratorio indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        } finally {
            $this->two->rollBack();
        }
        $this->one->commit();

        $this->assertSame('existing_identical_draft',
            $this->drafts($this->two)->persist(self::ORG_A, $spec)['status_code']);
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());
    }

    public function test_rollback_releases_draft_without_sending_payment(): void
    {
        $this->createPayerLink();
        $this->one->beginTransaction();
        $this->drafts($this->one)->persist(self::ORG_A, self::spec());
        $this->one->rollBack();

        $this->assertSame(0, $this->owner->table('crm_lab_asaas_subscription_drafts')->count());
        $this->assertSame('new_blocked_draft',
            $this->drafts($this->two)->persist(self::ORG_A, self::spec())['status_code']);
    }

    public function test_role_cannot_modify_subscription_draft_or_read_payer_id(): void
    {
        foreach ([$this->one, $this->two] as $db) {
            $role = $db->selectOne(
                'SELECT rolsuper, rolbypassrls, rolcreaterole FROM pg_roles WHERE rolname = current_user',
            );
            foreach (['rolsuper', 'rolbypassrls', 'rolcreaterole'] as $prop) {
                $this->assertFalse((bool) $role->{$prop}, $prop);
            }

            foreach (['UPDATE', 'DELETE', 'TRUNCATE'] as $op) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_asaas_subscription_drafts', $op],
                )->allowed, $op);
            }

            foreach (['provider_customer_id', 'external_reference'] as $column) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_customer_links', $column, 'SELECT'],
                )->allowed, $column);
            }

            foreach (['can_activate', 'can_charge', 'can_create_subscription',
                'terms_accepted', 'price_homologated', 'state'] as $column) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_subscription_drafts', $column, 'INSERT'],
                )->allowed, $column);
            }
        }

        try {
            $this->drafts($this->owner)->persist(self::ORG_A, self::spec());
            $this->fail('Owner role must not be a synthetic subscription actor.');
        } catch (RuntimeException $e) {
            $this->assertSame('Papel de assinatura nao autorizado.', $e->getMessage());
        }
    }

    public function test_application_exposes_no_subscription_checkout_endpoint(): void
    {
        $this->postJson('/api/asaas/subscriptions', [])->assertNotFound();
        $this->postJson('/api/checkout', [])->assertNotFound();
        $this->get('/api/asaas/subscriptions')->assertNotFound();

        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratoryAsaasSubscriptionDraft', $source);
        }
    }
}
