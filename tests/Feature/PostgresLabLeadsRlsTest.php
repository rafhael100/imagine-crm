<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\PrincipalReference;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\LaboratorySignedSiteCapture;
use App\Core\Leads\LaboratoryTenantLeadLedger;
use App\Core\Tasks\LaboratoryTenantTaskLedger;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * CI PG16 only. Tests real membership locking + FORCE RLS + separate
 * sessions, while business policies remain universally deny-by-default.
 */
final class PostgresLabLeadsRlsTest extends TestCase
{
    private const ORG_A = 'a1111111-1111-4111-8111-111111111111';
    private const ORG_B = 'b2222222-2222-4222-8222-222222222222';
    private const PRINCIPAL = 'c3333333-3333-4333-8333-333333333333';
    private const INTRUDER = 'd4444444-4444-4444-8444-444444444444';
    private const LEAD_A = 'a5555555-5555-4555-8555-555555555555';
    private const LEAD_B = 'b6666666-6666-4666-8666-666666666666';

    private const TASK_A = 'a9999999-9999-4999-8999-999999999999';
    private const TASK_B = 'b9999999-9999-4999-8999-999999999999';

    private Connection $owner;
    private Connection $one;
    private Connection $two;

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_LEADS_LAB') !== 'synthetic-only'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('PostgreSQL16 leads in GitHub CI only.');
        }

        $this->owner = DB::connection();
        $this->clean();
        $now = now();
        $this->owner->table('crm_global_identities')->insert([
            ['id' => self::PRINCIPAL, 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::INTRUDER, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->owner->table('crm_organizations')->insert([
            ['id' => self::ORG_A, 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::ORG_B, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->owner->table('crm_memberships')->insert([
            [
                'id' => 'e7777777-7777-4777-8777-777777777777',
                'organization_id' => self::ORG_A,
                'global_identity_id' => self::PRINCIPAL,
                'status' => 'active', 'version' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'f8888888-8888-4888-8888-888888888888',
                'organization_id' => self::ORG_B,
                'global_identity_id' => self::PRINCIPAL,
                'status' => 'active', 'version' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $pg['url'] = null;
        $pg['username'] = getenv('RLS_TEST_USERNAME');
        $pg['password'] = getenv('RLS_TEST_PASSWORD');
        foreach (['leads_one_ci', 'leads_two_ci'] as $alias) {
            config(['database.connections.'.$alias => $pg]);
            DB::purge($alias);
        }
        $this->one = DB::connection('leads_one_ci');
        $this->two = DB::connection('leads_two_ci');
    }

    protected function tearDown(): void
    {
        if (isset($this->owner)) {
            if (isset($this->one)) {
                foreach ([$this->one, $this->two] as $db) {
                    while ($db->transactionLevel() > 0) {
                        $db->rollBack();
                    }
                }
            }
            $this->clean();
            foreach (['leads_one_ci', 'leads_two_ci'] as $alias) {
                DB::purge($alias);
            }
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $this->owner->table('crm_lab_lead_capture_keys')->delete();
        $this->owner->table('crm_lab_site_delivery_bindings')->delete();
        $this->owner->table('crm_lab_task_events')->delete();
        $this->owner->table('crm_lab_followup_tasks')->delete();
        $this->owner->table('crm_lab_lead_events')->delete();
        $this->owner->table('crm_lab_leads')->delete();
        $this->owner->table('crm_memberships')
            ->whereIn('organization_id', [self::ORG_A, self::ORG_B])->delete();
        $this->owner->table('crm_organizations')
            ->whereIn('id', [self::ORG_A, self::ORG_B])->delete();
        $this->owner->table('crm_global_identities')
            ->whereIn('id', [self::PRINCIPAL, self::INTRUDER])->delete();
    }

    private function ledger(?Connection $connection = null): LaboratoryTenantLeadLedger
    {
        return new LaboratoryTenantLeadLedger($connection ?? $this->one);
    }

    private function tasks(?Connection $connection = null): LaboratoryTenantTaskLedger
    {
        return new LaboratoryTenantTaskLedger($connection ?? $this->one);
    }

    private static function principal(): PrincipalReference
    {
        return new PrincipalReference(self::PRINCIPAL);
    }

    private function assertNoEffects(array $out): void
    {
        foreach (['can_publish', 'can_contact', 'can_activate'] as $key) {
            $this->assertFalse($out[$key], $key);
        }
    }

    public function test_two_active_memberships_keep_lead_list_and_kanban_isolated(): void
    {
        $a = $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A001');
        $b = $this->ledger($this->two)->createSynthetic(self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_B001');
        $this->assertSame('synthetic_created', $a['status_code']);
        $this->assertSame('synthetic_created', $b['status_code']);
        $this->assertNoEffects($a);
        $this->assertNoEffects($b);

        $fromA = $this->ledger()->listSynthetic(self::principal(), self::ORG_A);
        $fromB = $this->ledger($this->two)->listSynthetic(self::principal(), self::ORG_B);
        $this->assertCount(1, $fromA);
        $this->assertCount(1, $fromB);
        $this->assertSame(self::LEAD_A, $fromA[0]['id']);
        $this->assertSame(self::LEAD_B, $fromB[0]['id']);
        $this->assertSame('new', $fromA[0]['stage']);
        $this->assertSame('DEMO_A001', $fromA[0]['synthetic_title']);
        $this->assertSame([], $this->one->table('public.crm_lab_leads')->get()->all());
        $this->assertSame([], $this->two->table('public.crm_lab_leads')->get()->all());
        $this->assertSame(2, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(0, $this->one->transactionLevel());
    }

    public function test_stale_version_and_invisible_cross_tenant_move_have_same_denial(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A002');
        $this->ledger($this->two)->createSynthetic(self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_B002');

        $first = $this->ledger()->moveSyntheticStage(
            self::principal(), self::ORG_A, self::LEAD_A, 'qualified', 1,
        );
        $stale = $this->ledger($this->two)->moveSyntheticStage(
            self::principal(), self::ORG_A, self::LEAD_A, 'proposal', 1,
        );
        $cross = $this->ledger()->moveSyntheticStage(
            self::principal(), self::ORG_A, self::LEAD_B, 'qualified', 1,
        );
        $this->assertSame('synthetic_stage_changed', $first['status_code']);
        $this->assertSame('synthetic_not_changed', $stale['status_code']);
        $this->assertSame($stale['status_code'], $cross['status_code']);
        $this->assertNoEffects($first);
        $this->assertNoEffects($cross);
        $this->assertSame('qualified', $this->owner->table('crm_lab_leads')
            ->where('id', self::LEAD_A)->value('stage'));
        $this->assertSame(2, $this->owner->table('crm_lab_leads')
            ->where('id', self::LEAD_A)->value('version'));
        $this->assertSame('new', $this->owner->table('crm_lab_leads')
            ->where('id', self::LEAD_B)->value('stage'));
    }

    public function test_kanban_follows_approved_stages_and_terminal_state_is_immutable(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A003');
        $this->assertSame('synthetic_not_changed', $this->ledger()->moveSyntheticStage(
            self::principal(), self::ORG_A, self::LEAD_A, 'won', 1,
        )['status_code']);
        foreach ([['qualified', 1], ['proposal', 2], ['won', 3]] as [$stage, $version]) {
            $this->assertSame('synthetic_stage_changed', $this->ledger()->moveSyntheticStage(
                self::principal(), self::ORG_A, self::LEAD_A, $stage, $version,
            )['status_code']);
        }
        $this->assertSame('synthetic_not_changed', $this->ledger()->moveSyntheticStage(
            self::principal(), self::ORG_A, self::LEAD_A, 'lost', 4,
        )['status_code']);
        $this->assertSame('won', $this->owner->table('crm_lab_leads')->value('stage'));
        $this->assertSame(4, $this->owner->table('crm_lab_leads')->value('version'));
    }

    public function test_archived_lead_is_not_reopened_or_deleted_by_runtime_role(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A004');
        $result = $this->ledger()->archiveSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 1);
        $this->assertSame('synthetic_archived', $result['status_code']);
        $this->assertSame('synthetic_not_changed', $this->ledger($this->two)
            ->archiveSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 1)['status_code']);
        $this->assertSame([], $this->ledger()->listSynthetic(self::principal(), self::ORG_A));
        $this->assertTrue((bool) $this->owner->table('crm_lab_leads')->value('archived'));

        try {
            (new PostgresActiveMembershipStore($this->one))->within(
                self::principal(), self::ORG_A, function (): void {
                    $this->one->table('public.crm_lab_leads')
                        ->where('id', self::LEAD_A)
                        ->update(['archived' => false, 'version' => 3]);
                },
            );
            $this->fail('Direct SQL may not unarchive a record.');
        } catch (QueryException $e) {
            $this->assertSame('23514', $e->errorInfo[0] ?? null);
        }
        $this->assertSame(0, $this->one->transactionLevel());
        $this->assertTrue((bool) $this->owner->table('crm_lab_leads')->value('archived'));
    }

    public function test_cross_tenant_insert_is_denied_by_real_rls_with_check(): void
    {
        $this->one->beginTransaction();
        try {
            $this->one->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_A]);
            try {
                $this->one->table('public.crm_lab_leads')->insert([
                    'id' => self::LEAD_A,
                    'organization_id' => self::ORG_B,
                    'synthetic_title' => 'DEMO_OTHER',
                ]);
                $this->fail('Cross-organization INSERT must be rejected.');
            } catch (QueryException $e) {
                $this->assertSame('42501', $e->errorInfo[0] ?? null);
            }
        } finally {
            $this->one->rollBack();
        }
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
    }

    public function test_membership_revocation_and_unknown_principal_never_execute(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A005');
        $this->owner->table('crm_memberships')
            ->where('organization_id', self::ORG_A)
            ->update(['status' => 'revoked', 'version' => 2]);
        try {
            $this->ledger()->listSynthetic(self::principal(), self::ORG_A);
            $this->fail('Revoked membership must stop reading leads.');
        } catch (AuthorizationException) {
            $this->assertSame(0, $this->one->transactionLevel());
        }
        try {
            $this->ledger()->listSynthetic(new PrincipalReference(self::INTRUDER), self::ORG_B);
            $this->fail('Another principal must not reuse a tenant membership.');
        } catch (AuthorizationException) {
            $this->assertSame(0, $this->one->transactionLevel());
        }
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
    }

    public function test_two_connections_never_mix_tenants_even_while_transactions_overlap(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A006');
        $this->ledger()->createSynthetic(self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_B006');

        $outer = new PostgresActiveMembershipStore($this->one);
        $inner = new PostgresActiveMembershipStore($this->two);
        $outer->within(self::principal(), self::ORG_A, function () use ($inner): void {
            $this->assertSame([self::LEAD_A],
                $this->one->table('public.crm_lab_leads')->pluck('id')->all());
            $inner->within(self::principal(), self::ORG_B, function (): void {
                $this->assertSame([self::LEAD_B],
                    $this->two->table('public.crm_lab_leads')->pluck('id')->all());
            });
            $this->assertSame([self::LEAD_A],
                $this->one->table('public.crm_lab_leads')->pluck('id')->all());
        });
        $this->assertSame(0, $this->one->transactionLevel());
        $this->assertSame(0, $this->two->transactionLevel());
        $this->assertSame(0, $this->two->table('public.crm_lab_leads')->count());
    }

    public function test_role_cannot_delete_edit_identity_or_own_leads_table(): void
    {
        $this->assertFalse((bool) $this->one->selectOne(
            "SELECT has_table_privilege(current_user, 'public.crm_lab_leads','DELETE') AS allowed",
        )->allowed);
        foreach (['organization_id', 'id', 'synthetic_title'] as $field) {
            $this->assertFalse((bool) $this->one->selectOne(
                "SELECT has_column_privilege(current_user, 'public.crm_lab_leads', ?, 'UPDATE') AS allowed",
                [$field],
            )->allowed);
        }
        $role = $this->one->selectOne(
            "SELECT r.rolsuper, r.rolbypassrls, c.relrowsecurity, c.relforcerowsecurity,
                    pg_has_role(current_user,c.relowner,'MEMBER') AS owns_table
               FROM pg_roles r, pg_class c
              WHERE r.rolname=current_user AND c.oid='public.crm_lab_leads'::regclass",
        );
        $this->assertFalse((bool) $role->rolsuper);
        $this->assertFalse((bool) $role->rolbypassrls);
        $this->assertFalse((bool) $role->owns_table);
        $this->assertTrue((bool) $role->relrowsecurity);
        $this->assertTrue((bool) $role->relforcerowsecurity);
    }

    public function test_append_only_audit_records_creation_stage_move_archive_in_one_tenant(): void
    {
        $this->ledger()->createSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A007',
        );
        $this->ledger($this->two)->createSynthetic(
            self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_B007',
        );
        $this->ledger()->moveSyntheticStage(
            self::principal(), self::ORG_A, self::LEAD_A, 'qualified', 1,
        );
        $this->ledger()->archiveSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A, 2,
        );

        $events = $this->owner->table('crm_lab_lead_events')
            ->where('lead_id', self::LEAD_A)->orderBy('version')->get()->all();
        $this->assertCount(3, $events);
        $this->assertSame(['created', 'stage_moved', 'archived'],
            array_map(static fn ($event) => $event->event_type, $events));
        $this->assertSame([1, 2, 3],
            array_map(static fn ($event) => (int) $event->version, $events));
        $this->assertSame(['new', 'qualified', 'qualified'],
            array_map(static fn ($event) => $event->to_stage, $events));
        foreach ($events as $event) {
            $this->assertSame(self::ORG_A, $event->organization_id);
        }
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')
            ->where('organization_id', self::ORG_B)->count());
        $this->assertFalse((bool) $this->one->selectOne(
            "SELECT has_table_privilege(current_user, 'public.crm_lab_lead_events','SELECT') AS allowed",
        )->allowed);
        foreach (['UPDATE', 'DELETE', 'TRUNCATE'] as $permission) {
            $this->assertFalse((bool) $this->one->selectOne(
                'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                ['public.crm_lab_lead_events', $permission],
            )->allowed, $permission);
        }
    }

    public function test_duplicate_audit_event_rolls_back_the_kanban_change_atomically(): void
    {
        $this->ledger()->createSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A008',
        );
        // A deliberate synthetic fixture collision; a production user
        // cannot INSERT this event without a permitted membership context.
        $this->owner->table('crm_lab_lead_events')->insert([
            'event_id' => 'e9999999-9999-4999-8999-999999999999',
            'lead_id' => self::LEAD_A,
            'organization_id' => self::ORG_A,
            'version' => 2,
            'event_type' => 'stage_moved',
            'from_stage' => 'new',
            'to_stage' => 'qualified',
        ]);
        try {
            $this->ledger()->moveSyntheticStage(
                self::principal(), self::ORG_A, self::LEAD_A, 'qualified', 1,
            );
            $this->fail('Changing stage without committing audit must be impossible.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Transicao sintetica de lead indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
        $row = $this->owner->table('crm_lab_leads')->where('id', self::LEAD_A)->first();
        $this->assertSame('new', $row->stage);
        $this->assertSame(1, $row->version);
        $this->assertSame(2, $this->owner->table('crm_lab_lead_events')
            ->where('lead_id', self::LEAD_A)->count());
        $this->assertSame(0, $this->one->transactionLevel());
    }

    private static function siteClaim(
        string $organization,
        string $submission = '12121212-2222-4222-8222-555555555551',
        string $binding = '23232323-2222-4222-8222-555555555551',
        string $site = '34343434-2222-4222-8222-555555555551',
        string $form = '45454545-2222-4222-8222-555555555551',
        string $destination = '56565656-2222-4222-8222-555555555551',
    ): SyntheticIdempotencyClaim {
        return new SyntheticIdempotencyClaim(
            new LeadSubmissionIdentity($organization, $binding, $submission),
            $site, $form, $destination,
        );
    }

    public function test_synthetic_sites_event_replay_is_idempotent_and_mismatches_are_denied(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $first = $this->ledger()->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_A009', 'sites', $claim,
        );
        $again = $this->ledger($this->two)->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_A009', 'sites', $claim,
        );
        $changedTitle = $this->ledger()->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_CHANGED', 'sites', $claim,
        );
        $changedId = $this->ledger($this->two)->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_B,
            'DEMO_A009', 'sites', $claim,
        );
        $newDigestSameLead = $this->ledger()->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_A009', 'sites', self::siteClaim(self::ORG_A,
                submission: '12121212-2222-4222-8222-555555555552'),
        );

        $this->assertSame('synthetic_captured', $first['status_code']);
        $this->assertSame('synthetic_replayed', $again['status_code']);
        foreach ([$changedTitle, $changedId, $newDigestSameLead] as $blocked) {
            $this->assertSame('synthetic_conflict_denied', $blocked['status_code']);
            $this->assertNoEffects($blocked);
            $this->assertCount(4, $blocked);
            $this->assertArrayNotHasKey('lead_id', $blocked);
            $this->assertArrayNotHasKey('event_sha256', $blocked);
        }
        $this->assertNoEffects($first);
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame('DEMO_A009',
            $this->owner->table('crm_lab_leads')->value('synthetic_title'));
        $this->assertSame(0, $this->one->transactionLevel());
        $this->assertSame(0, $this->two->transactionLevel());
    }

    public function test_site_binding_form_and_destination_cannot_change_during_replay(): void
    {
        $original = self::siteClaim(self::ORG_A);
        $this->assertSame('synthetic_captured', $this->ledger()->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A014', 'sites', $original,
        )['status_code']);

        $mutations = [
            self::siteClaim(self::ORG_A,
                binding: '23232323-2222-4222-8222-555555555552'),
            self::siteClaim(self::ORG_A,
                site: '34343434-2222-4222-8222-555555555552'),
            self::siteClaim(self::ORG_A,
                form: '45454545-2222-4222-8222-555555555552'),
            self::siteClaim(self::ORG_A,
                destination: '56565656-2222-4222-8222-555555555552'),
        ];
        foreach ($mutations as $claim) {
            $denied = $this->ledger($this->two)->captureSynthetic(
                self::principal(), self::ORG_A, self::LEAD_A,
                'DEMO_A014', 'sites', $claim,
            );
            $this->assertSame('synthetic_conflict_denied', $denied['status_code']);
            $this->assertNoEffects($denied);
        }
        $changedSource = $this->ledger($this->two)->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_B,
            'DEMO_A014', 'landing', $original,
        );
        $this->assertSame('synthetic_conflict_denied', $changedSource['status_code']);
        $this->assertSame('synthetic_replayed', $this->ledger($this->two)->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_A014', 'sites', $original,
        )['status_code']);

        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $row = $this->owner->table('crm_lab_lead_capture_keys')->first();
        $this->assertSame($original->identity->bindingReference, $row->binding_reference);
        $this->assertSame($original->siteReference, $row->site_reference);
        $this->assertSame($original->formReference, $row->form_reference);
        $this->assertSame($original->destinationReference, $row->destination_reference);
    }

    public function test_identical_submission_reference_is_scoped_to_each_tenant(): void
    {
        $claimA = self::siteClaim(self::ORG_A);
        $claimB = self::siteClaim(self::ORG_B);
        $a = $this->ledger()->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_A010', 'landing', $claimA,
        );
        $b = $this->ledger($this->two)->captureSynthetic(
            self::principal(), self::ORG_B, self::LEAD_B,
            'DEMO_B010', 'landing', $claimB,
        );
        $this->assertSame('synthetic_captured', $a['status_code']);
        $this->assertSame('synthetic_captured', $b['status_code']);
        $this->assertSame(2, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(2, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame(2, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame([self::LEAD_A], array_column(
            $this->ledger()->listSynthetic(self::principal(), self::ORG_A), 'id',
        ));
        $this->assertSame([self::LEAD_B], array_column(
            $this->ledger()->listSynthetic(self::principal(), self::ORG_B), 'id',
        ));
        $this->assertSame(0,
            $this->one->table('public.crm_lab_lead_capture_keys')->count());
    }

    public function test_invalid_source_tenant_and_title_never_reach_capture_sql(): void
    {
        foreach ([
            ['unknown', self::siteClaim(self::ORG_A), 'DEMO_A011'],
            ['sites', self::siteClaim(self::ORG_B), 'DEMO_A011'],
            ['sites', self::siteClaim(self::ORG_A), 'invalid title!'],
        ] as [$source, $claim, $title]) {
            try {
                $this->ledger()->captureSynthetic(
                    self::principal(), self::ORG_A, self::LEAD_A,
                    $title, $source, $claim,
                );
                $this->fail('Invalid origin claim must be rejected before SQL.');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('Captura de lead sintetico invalida.', $e->getMessage());
            }
        }
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_capture_keys')->count());
    }

    public function test_failed_lead_insert_rolls_back_capture_key_without_orphan(): void
    {
        $this->ledger()->createSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_A013',
        );
        try {
            $this->ledger($this->two)->captureSynthetic(
                self::principal(), self::ORG_A, self::LEAD_B,
                'DEMO_A013', 'sites', self::siteClaim(self::ORG_A),
            );
            $this->fail('A captured event cannot commit when its lead fails.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Captura sintetica de lead indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame(0, $this->two->transactionLevel());
    }

    public function test_capture_is_revocation_aware_and_capture_claims_are_append_only(): void
    {
        $this->owner->table('crm_memberships')
            ->where('organization_id', self::ORG_A)
            ->update(['status' => 'revoked', 'version' => 2]);
        try {
            $this->ledger()->captureSynthetic(
                self::principal(), self::ORG_A, self::LEAD_A,
                'DEMO_A012', 'onepage', self::siteClaim(self::ORG_A),
            );
            $this->fail('Revoked principal cannot capture.');
        } catch (AuthorizationException) {
            $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        }
        foreach (['UPDATE', 'DELETE', 'TRUNCATE'] as $permission) {
            $this->assertFalse((bool) $this->one->selectOne(
                'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                ['public.crm_lab_lead_capture_keys', $permission],
            )->allowed);
        }
        $this->assertFalse((bool) $this->one->selectOne(
            "SELECT has_column_privilege(current_user, 'public.crm_lab_lead_capture_keys','recorded_at','INSERT') AS allowed",
        )->allowed);
        $this->assertSame(0, $this->one->transactionLevel());
    }

    private function seedActiveSiteBinding(
        string $organization = self::ORG_A,
        ?SyntheticIdempotencyClaim $claim = null,
        string $source = 'sites',
    ): void {
        $claim ??= self::siteClaim($organization);
        $this->owner->table('crm_lab_site_delivery_bindings')->insert([
            'organization_id' => $organization,
            'binding_reference' => $claim->identity->bindingReference,
            'site_reference' => $claim->siteReference,
            'form_reference' => $claim->formReference,
            'destination_reference' => $claim->destinationReference,
            'source_code' => $source,
            'state' => 'active',
            'version' => 1,
        ]);
    }

    private function signedCapture(?Connection $connection = null, ?string $key = null): LaboratorySignedSiteCapture
    {
        return new LaboratorySignedSiteCapture(
            $this->ledger($connection),
            $key ?? (string) getenv('CRM_LEADS_SIGNING_KEY_CI'),
        );
    }

    private static function syntheticSiteSignature(
        SyntheticIdempotencyClaim $claim,
        string $leadId,
        string $title,
        string $source,
        string $nonce,
        int $issued,
        int $expires,
    ): string {
        return hash_hmac('sha256', LaboratorySignedSiteCapture::canonicalForCi(
            $claim, $leadId, $title, $source, $nonce, $issued, $expires,
        ), (string) getenv('CRM_LEADS_SIGNING_KEY_CI'));
    }

    public function test_ci_signed_site_capture_accepts_one_valid_message_then_deduplicates_replay(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $this->seedActiveSiteBinding(self::ORG_A, $claim);
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $expires = $issued + 120;
        $signature = self::syntheticSiteSignature(
            $claim, self::LEAD_A, 'DEMO_SIGN01', 'sites', $nonce, $issued, $expires,
        );

        $first = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_SIGN01', 'sites', $claim,
            $nonce, $issued, $expires, $issued + 10, $signature,
        );
        $replay = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_SIGN01', 'sites', $claim,
            $nonce, $issued, $expires, $issued + 20, $signature,
        );

        $this->assertSame('synthetic_captured', $first['status_code']);
        $this->assertSame('synthetic_replayed', $replay['status_code']);
        $unsignedBypass = $this->ledger()->captureSynthetic(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_SIGN01', 'sites', $claim,
        );
        $this->assertSame('synthetic_conflict_denied', $unsignedBypass['status_code']);
        $this->assertNoEffects($unsignedBypass);
        $this->assertNoEffects($first);
        $this->assertNoEffects($replay);
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame($nonce,
            $this->owner->table('crm_lab_lead_capture_keys')->value('signed_nonce'));

        // A message expires even when its submission was already persisted.
        $expired = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_SIGN01', 'sites', $claim,
            $nonce, $issued, $expires, $expires, $signature,
        );
        $this->assertSame('synthetic_integrity_denied', $expired['status_code']);
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
    }

    public function test_signed_capture_rejects_tampering_and_invalid_time_before_any_sql(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $expires = $issued + 120;
        $signature = self::syntheticSiteSignature(
            $claim, self::LEAD_A, 'DEMO_SIGN02', 'sites', $nonce, $issued, $expires,
        );
        $siteChanged = self::siteClaim(
            self::ORG_A, site: '34343434-2222-4222-8222-555555555552',
        );

        foreach ([
            ['DEMO_SIGN03', 'sites', $claim, $nonce, $issued, $expires, $issued + 1, $signature],
            ['DEMO_SIGN02', 'landing', $claim, $nonce, $issued, $expires, $issued + 1, $signature],
            ['DEMO_SIGN02', 'sites', $siteChanged, $nonce, $issued, $expires, $issued + 1, $signature],
            ['DEMO_SIGN02', 'sites', $claim, 'bbbbbbbb-cccc-4ddd-8eee-aaaaaaaaaaaa', $issued, $expires, $issued + 1, $signature],
            ['DEMO_SIGN02', 'sites', $claim, $nonce, $issued, $expires, $issued + 1, str_repeat('a', 64)],
            ['DEMO_SIGN02', 'sites', $claim, $nonce, $issued, $expires, $expires, $signature],
            ['DEMO_SIGN02', 'sites', $claim, $nonce, $issued + 31, $expires, $issued, $signature],
            ['DEMO_SIGN02', 'sites', $claim, $nonce, $issued, $issued + 301, $issued + 1, $signature],
            ['DEMO_SIGN02', 'sites', $claim, $nonce, $issued, $expires, $issued + 1, 'bad-signature'],
        ] as [$title, $source, $differentClaim, $givenNonce, $at, $until, $now, $mac]) {
            $result = $this->signedCapture()->captureSigned(
                self::principal(), self::ORG_A, self::LEAD_A,
                $title, $source, $differentClaim,
                $givenNonce, $at, $until, $now, $mac,
            );
            $this->assertSame('synthetic_integrity_denied', $result['status_code']);
            $this->assertNoEffects($result);
        }

        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_capture_keys')->count());
    }

    public function test_signed_nonce_cannot_be_reused_for_another_submission_same_tenant(): void
    {
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $this->seedActiveSiteBinding();
        $issued = 1700000000;
        $expires = $issued + 120;
        $firstClaim = self::siteClaim(self::ORG_A);
        $secondClaim = self::siteClaim(
            self::ORG_A, submission: '12121212-2222-4222-8222-555555555552',
        );
        $one = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_SIGN04', 'sites', $firstClaim, $nonce, $issued, $expires, $issued + 5,
            self::syntheticSiteSignature(
                $firstClaim, self::LEAD_A, 'DEMO_SIGN04', 'sites', $nonce, $issued, $expires,
            ),
        );
        $duplicateNonce = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_B,
            'DEMO_SIGN05', 'sites', $secondClaim, $nonce, $issued, $expires, $issued + 5,
            self::syntheticSiteSignature(
                $secondClaim, self::LEAD_B, 'DEMO_SIGN05', 'sites', $nonce, $issued, $expires,
            ),
        );
        $this->assertSame('synthetic_captured', $one['status_code']);
        $this->assertSame('synthetic_conflict_denied', $duplicateNonce['status_code']);
        $this->assertNoEffects($duplicateNonce);
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertFalse((bool) $this->one->selectOne(
            "SELECT has_column_privilege(current_user, 'public.crm_lab_lead_capture_keys','signed_nonce','UPDATE') AS allowed",
        )->allowed);
    }

    public function test_signed_capture_requires_registered_active_source_binding_and_revokes_retries(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $expires = $issued + 120;
        $signed = self::syntheticSiteSignature(
            $claim, self::LEAD_A, 'DEMO_REG01', 'sites', $nonce, $issued, $expires,
        );

        // An authentic fixture MAC alone cannot admit a non-existent binding.
        $missing = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_REG01', 'sites', $claim, $nonce, $issued, $expires,
            $issued + 1, $signed,
        );
        $this->assertSame('synthetic_conflict_denied', $missing['status_code']);
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());

        $this->seedActiveSiteBinding();
        $valid = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_REG01', 'sites', $claim, $nonce, $issued, $expires,
            $issued + 2, $signed,
        );
        $this->assertSame('synthetic_captured', $valid['status_code']);

        $this->owner->table('crm_lab_site_delivery_bindings')
            ->where('organization_id', self::ORG_A)
            ->update(['state' => 'revoked', 'version' => 2]);
        $afterRevocation = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_REG01', 'sites', $claim, $nonce, $issued, $expires,
            $issued + 3, $signed,
        );
        $this->assertSame('synthetic_conflict_denied', $afterRevocation['status_code']);

        // Revocation is terminal: an owner cannot silently reactivate a
        // retired binding or reroute its source to another channel.
        try {
            $this->owner->table('crm_lab_site_delivery_bindings')
                ->where('organization_id', self::ORG_A)
                ->update(['state' => 'active', 'source_code' => 'landing', 'version' => 3]);
            $this->fail('A revoked binding must remain revoked.');
        } catch (QueryException $e) {
            $this->assertSame('23514', $e->errorInfo[0] ?? null);
        }

        $this->assertNoEffects($missing);
        $this->assertNoEffects($afterRevocation);
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
    }

    public function test_active_registry_mismatched_source_and_identity_mutations_are_denied(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $this->seedActiveSiteBinding(self::ORG_A, $claim, 'landing');
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $expires = $issued + 120;
        $reply = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_REG03', 'sites', $claim, $nonce, $issued, $expires, $issued + 5,
            self::syntheticSiteSignature(
                $claim, self::LEAD_A, 'DEMO_REG03', 'sites', $nonce, $issued, $expires,
            ),
        );
        $this->assertSame('synthetic_conflict_denied', $reply['status_code']);
        foreach ([
            ['site_reference' => '34343434-2222-4222-8222-555555555552', 'version' => 2],
            ['source_code' => 'sites', 'version' => 2],
        ] as $mutation) {
            try {
                $this->owner->table('crm_lab_site_delivery_bindings')
                    ->where('organization_id', self::ORG_A)->update($mutation);
                $this->fail('The binding identity must be immutable.');
            } catch (QueryException $e) {
                $this->assertSame('23514', $e->errorInfo[0] ?? null);
            }
        }
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_capture_keys')->count());
    }

    public function test_concurrent_revocation_waits_for_inflight_capture_advisory_transaction(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $this->seedActiveSiteBinding(self::ORG_A, $claim);
        $this->assertTrue(extension_loaded('pgsql'), 'PG16 concurrency test needs pgsql extension');

        // Simulate the exact critical section used by signed capture on the
        // non-bypass, SELECT-only connection. Hold it across the owner UPDATE.
        $key = 'crm_lab_binding|'.self::ORG_A.'|'.$claim->identity->bindingReference;
        $this->one->beginTransaction();
        $revoker = null;
        try {
            $this->one->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_A]);
            $this->one->selectOne(
                'SELECT pg_advisory_xact_lock(hashtextextended(?::text, 0))',
                [$key],
            );
            $this->assertSame('active', $this->one
                ->table('public.crm_lab_site_delivery_bindings')->value('state'));

            $revoker = pg_connect(
                'host=127.0.0.1 port=5432 dbname=imagine_crm_ci user=postgres'
                .' password=ci-only-password connect_timeout=5',
                PGSQL_CONNECT_FORCE_NEW,
            );
            $this->assertNotFalse($revoker);
            $this->assertNotFalse(pg_query($revoker, "SET lock_timeout = '5000ms'"));
            $this->assertTrue(pg_send_query_params(
                $revoker,
                'UPDATE public.crm_lab_site_delivery_bindings
                    SET state=$1, version=$2
                  WHERE organization_id=$3::uuid AND binding_reference=$4::uuid',
                ['revoked', 2, self::ORG_A, $claim->identity->bindingReference],
            ));

            // The revoker must not commit a state change while the prior
            // signed capture owns the advisory xact lock.
            usleep(150000);
            $this->assertTrue(pg_consume_input($revoker));
            $this->assertTrue(pg_connection_busy($revoker));
            $this->assertSame('active',
                $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));
            $this->one->commit();
            $outcome = pg_get_result($revoker);
            $this->assertNotFalse($outcome);
            $this->assertSame(PGSQL_COMMAND_OK, pg_result_status($outcome));
            $this->assertSame(1, pg_affected_rows($outcome));
            pg_free_result($outcome);
        } finally {
            while ($this->one->transactionLevel() > 0) {
                $this->one->rollBack();
            }
            if ($revoker !== null && $revoker !== false) {
                pg_close($revoker);
            }
        }
        $this->assertSame('revoked',
            $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));

        // The same synthetically signed message is now denied, even when
        // nonce/clock/MAC are otherwise valid. No lead or audit is created.
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $expires = $issued + 120;
        $answer = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_REG04', 'sites', $claim,
            $nonce, $issued, $expires, $issued + 5,
            self::syntheticSiteSignature(
                $claim, self::LEAD_A, 'DEMO_REG04', 'sites', $nonce, $issued, $expires,
            ),
        );
        $this->assertSame('synthetic_conflict_denied', $answer['status_code']);
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_capture_keys')->count());
    }

    public function test_pg07_revocation_commits_before_real_capture_and_denies_without_effects(): void
    {
        $claim = $this->pg07Fixture();
        $before = $this->pg07CaptureRows();
        $worker = $revoker = null;
        try {
            $worker = $this->pg07StartWorker($claim);
            $revoker = $this->pg07OwnerSession();
            $this->pg07Revoke($revoker, $claim);
            $this->pg07Command($worker, 'capture');
            $this->pg07WaitForLock($worker['identity'], $revoker, 'advisory');
            $this->assertSame($before, $this->pg07CaptureRows());

            $this->assertNotFalse(pg_query($revoker['connection'], 'COMMIT'));
            $this->pg07AssertAnswer($this->pg07ReadFrame($worker), $worker, 'synthetic_conflict_denied');
            $this->assertSame('revoked', $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));
            $this->assertSame(2, $this->owner->table('crm_lab_site_delivery_bindings')->value('version'));
            $this->assertSame($before, $this->pg07CaptureRows(), 'A refused capture must not reserve its nonce.');
            $this->pg07AssertOtherTenant($worker);
            $this->pg07FinishWorker($worker);
            fwrite(STDOUT, "PG07_REVOKE_FIRST_PASS real_capture=1 advisory_wait_observed=1 effects_delta=0 tenant_b_unchanged=1\n");
        } finally {
            $this->pg07CleanupWorker($worker);
            if ($revoker !== null) {
                pg_close($revoker['connection']);
            }
        }
    }

    public function test_pg07_real_capture_timeout_rolls_back_then_same_connection_recovers(): void
    {
        $claim = $this->pg07Fixture();
        $before = $this->pg07CaptureRows();
        $worker = $revoker = null;
        try {
            $worker = $this->pg07StartWorker($claim, 3000);
            $revoker = $this->pg07OwnerSession();
            $this->pg07Revoke($revoker, $claim);
            $this->pg07Command($worker, 'capture');
            $this->pg07WaitForLock($worker['identity'], $revoker, 'advisory');
            $failed = $this->pg07ReadFrame($worker);
            $this->assertSame('attempt', $failed['phase']);
            $this->assertNull($failed['answer']);
            $this->assertSame(\RuntimeException::class, $failed['exception']);
            $this->assertSame(['sqlstate' => '55P03', 'statement' => 'advisory'], $failed['failure']);
            $this->assertSame([], $failed['completed']);
            $this->pg07AssertClean($failed['state'], $worker);
            $this->assertSame($before, $this->pg07CaptureRows());

            // The owner abandons its uncommitted revocation. No reconnect or
            // RESET repairs the child; it reuses the same message and nonce.
            $this->assertNotFalse(pg_query($revoker['connection'], 'ROLLBACK'));
            $this->assertSame('active', $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));
            $this->assertSame(1, $this->owner->table('crm_lab_site_delivery_bindings')->value('version'));
            $this->pg07Command($worker, 'capture');
            $this->pg07AssertAnswer($this->pg07ReadFrame($worker), $worker, 'synthetic_captured');
            $captured = $this->pg07CaptureRows();
            foreach (['crm_lab_leads', 'crm_lab_lead_capture_keys', 'crm_lab_lead_events'] as $table) {
                $this->assertSame(1, $this->owner->table($table)->where('organization_id', self::ORG_A)->count());
                $this->assertSame(
                    array_values(array_filter($before[$table], fn ($row) => $row['organization_id'] === self::ORG_B)),
                    array_values(array_filter($captured[$table], fn ($row) => $row['organization_id'] === self::ORG_B)),
                );
            }
            $this->assertSame('abababab-cccc-4ddd-8eee-aaaaaaaaaaaa',
                $this->owner->table('crm_lab_lead_capture_keys')->where('organization_id', self::ORG_A)->value('signed_nonce'));
            $this->pg07Command($worker, 'capture');
            $this->pg07AssertAnswer($this->pg07ReadFrame($worker), $worker, 'synthetic_replayed');
            $this->assertSame($captured, $this->pg07CaptureRows());
            $this->pg07AssertOtherTenant($worker);
            $this->pg07FinishWorker($worker);
            fwrite(STDOUT, "PG07_LOCK_TIMEOUT_PASS sqlstate=55P03 real_wait=1 effects_delta=0 same_pid_retry=1 nonce_reused_once=1 clean_scope=1\n");
        } finally {
            $this->pg07CleanupWorker($worker);
            if ($revoker !== null) {
                pg_close($revoker['connection']);
            }
        }
    }

    public function test_pg07_late_audit_failure_rolls_back_real_capture_and_releases_waiting_revocation(): void
    {
        $claim = $this->pg07Fixture();
        $before = $this->pg07CaptureRows();
        $worker = $revoker = $auditLocker = null;
        $constraint = false;
        try {
            // This fault belongs only to this synthetic lead. It is removed
            // in finally; no fixture grant, trigger or runtime code changes.
            $this->owner->statement("ALTER TABLE public.crm_lab_lead_events
                ADD CONSTRAINT crm_pg07_audit_failure_ci CHECK (NOT (
                    organization_id = '".self::ORG_A."'::uuid
                    AND lead_id = '".self::LEAD_A."'::uuid AND event_type = 'created'))");
            $constraint = true;
            $worker = $this->pg07StartWorker($claim);
            $auditLocker = $this->pg07OwnerSession();
            $this->assertNotFalse(pg_query($auditLocker['connection'], 'BEGIN'));
            $this->assertNotFalse(pg_query($auditLocker['connection'],
                'LOCK TABLE public.crm_lab_lead_events IN ACCESS EXCLUSIVE MODE'));
            $this->pg07Command($worker, 'capture');
            $this->pg07WaitForLock($worker['identity'], $auditLocker, 'relation');

            // A third connection revokes while the REAL capture holds the
            // binding lock. Reusing auditLocker here would invent a deadlock.
            $revoker = $this->pg07OwnerSession();
            $this->pg07Revoke($revoker, $claim, true);
            $this->pg07WaitForLock($revoker, $worker['identity'], 'advisory');
            $this->assertNotFalse(pg_query($auditLocker['connection'], 'ROLLBACK'));

            $failed = $this->pg07ReadFrame($worker);
            $this->assertSame('attempt', $failed['phase']);
            $this->assertNull($failed['answer']);
            $this->assertSame(\RuntimeException::class, $failed['exception']);
            $this->assertSame(['sqlstate' => '23514', 'statement' => 'audit_insert'], $failed['failure']);
            $this->assertSame(['advisory', 'claim_insert', 'lead_insert'], $failed['completed']);
            $this->pg07AssertClean($failed['state'], $worker);
            $this->pg07WaitForRevocation($revoker);
            $this->assertNotFalse(pg_query($revoker['connection'], 'COMMIT'));
            $this->assertSame('revoked', $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));
            $this->assertSame($before, $this->pg07CaptureRows(), 'Failed audit must roll back lead, claim and nonce.');
            $this->pg07Command($worker, 'capture');
            $this->pg07AssertAnswer($this->pg07ReadFrame($worker), $worker, 'synthetic_conflict_denied');
            $this->assertSame($before, $this->pg07CaptureRows());
            $this->pg07AssertOtherTenant($worker);
            $this->pg07FinishWorker($worker);
        } finally {
            $this->pg07CleanupWorker($worker);
            foreach ([$revoker, $auditLocker] as $session) {
                if ($session !== null) {
                    pg_close($session['connection']);
                }
            }
            if ($constraint) {
                $this->owner->transaction(function (): void {
                    $this->owner->statement("SET LOCAL lock_timeout = '5s'");
                    $this->owner->statement('ALTER TABLE public.crm_lab_lead_events DROP CONSTRAINT crm_pg07_audit_failure_ci');
                });
            }
        }
        $this->assertSame(0, $this->owner->table('pg_constraint')
            ->where('conname', 'crm_pg07_audit_failure_ci')->count());
        fwrite(STDOUT, "PG07_LATE_AUDIT_ROLLBACK_PASS sqlstate=23514 real_capture=1 relation_wait=1 revoker_advisory_wait=1 partial_writes_rolled_back=1 fault_removed=1\n");
    }

    private function pg07Fixture(): SyntheticIdempotencyClaim
    {
        if (getenv('CRM_LEADS_PG07_CONCURRENCY_CI') !== '1') {
            $this->markTestSkipped('PG07 real-process interleavings require the dedicated CI opt-in.');
        }
        $this->assertTrue(extension_loaded('pgsql') && function_exists('proc_open'));
        $claim = self::siteClaim(self::ORG_A);
        $this->seedActiveSiteBinding(self::ORG_A, $claim);
        $this->assertSame('synthetic_created', $this->ledger($this->two)
            ->createSynthetic(self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_PG07_B')['status_code']);
        return $claim;
    }

    private function pg07CaptureRows(): array
    {
        $rows = [];
        foreach ([
            'crm_lab_leads' => 'id',
            'crm_lab_lead_capture_keys' => 'lead_id',
            'crm_lab_lead_events' => 'event_id',
        ] as $table => $order) {
            $rows[$table] = $this->owner->table($table)->orderBy($order)->get()
                ->map(static fn ($row) => (array) $row)->all();
        }
        return $rows;
    }

    private function pg07StartWorker(SyntheticIdempotencyClaim $claim, int $timeout = 10000): array
    {
        $marker = 'crm_pg07_'.bin2hex(random_bytes(12));
        $worker = ['marker' => $marker, 'identity' => null, 'buffer' => '', 'exit_code' => null, 'pipes' => []];
        $worker['process'] = proc_open(
            [PHP_BINARY, base_path('tests/Labs/leads/signed-capture-race-worker.php'), $marker],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $worker['pipes'], base_path(), null, ['bypass_shell' => true],
        );
        $this->assertIsResource($worker['process']);
        stream_set_blocking($worker['pipes'][1], false);
        stream_set_blocking($worker['pipes'][2], false);
        try {
            $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
            $issued = 1700000000;
            $this->pg07WriteFrame($worker, [
                'principal' => self::PRINCIPAL, 'organization' => self::ORG_A,
                'other_organization' => self::ORG_B, 'lead' => self::LEAD_A,
                'binding' => $claim->identity->bindingReference,
                'submission' => $claim->identity->submissionReference,
                'site' => $claim->siteReference, 'form' => $claim->formReference,
                'destination' => $claim->destinationReference, 'title' => 'DEMO_PG07_RACE',
                'nonce' => $nonce, 'issued' => $issued, 'expires' => $issued + 120,
                'now' => $issued + 5, 'lock_timeout_ms' => $timeout,
                'signature' => self::syntheticSiteSignature(
                    $claim, self::LEAD_A, 'DEMO_PG07_RACE', 'sites', $nonce, $issued, $issued + 120,
                ),
            ]);
            $ready = $this->pg07ReadFrame($worker);
            $this->assertSame('ready', $ready['phase']);
            $worker['identity'] = $ready['identity'];
            $this->assertSame($marker, $worker['identity']['application_name']);
            $this->assertSame('crm_app_ci', $worker['identity']['usename']);
            $this->assertSame('imagine_crm_ci', $worker['identity']['datname']);
            $this->assertGreaterThan(0, $worker['identity']['pid']);
            return $worker;
        } catch (\Throwable $error) {
            $this->pg07CleanupWorker($worker);
            throw $error;
        }
    }

    private function pg07WriteFrame(array $worker, array $frame): void
    {
        $line = json_encode($frame, JSON_THROW_ON_ERROR)."\n";
        $this->assertSame(strlen($line), fwrite($worker['pipes'][0], $line));
        fflush($worker['pipes'][0]);
    }

    private function pg07Command(array $worker, string $command): void
    {
        $this->pg07WriteFrame($worker, ['command' => $command]);
    }

    private function pg07ReadFrame(array &$worker): array
    {
        $deadline = microtime(true) + 10;
        do {
            $worker['buffer'] .= stream_get_contents($worker['pipes'][1]);
            // Drain bounded diagnostic output, but never print SQL or params.
            stream_get_contents($worker['pipes'][2]);
            $this->assertLessThan(65536, strlen($worker['buffer']));
            $newline = strpos($worker['buffer'], "\n");
            if ($newline !== false) {
                $line = substr($worker['buffer'], 0, $newline);
                $worker['buffer'] = substr($worker['buffer'], $newline + 1);
                $frame = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
                $this->assertIsArray($frame);
                return $frame;
            }
            $state = proc_get_status($worker['process']);
            if (!$state['running']) {
                $worker['exit_code'] = $state['exitcode'];
                $worker['buffer'] .= stream_get_contents($worker['pipes'][1]);
                if (!str_contains($worker['buffer'], "\n")) {
                    $this->fail('PG07 child exited without its expected JSON frame.');
                }
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        $this->fail('PG07 child response deadline exceeded.');
    }

    private function pg07OwnerSession(): array
    {
        $marker = 'crm_pg07_owner_'.bin2hex(random_bytes(12));
        $connection = pg_connect(
            'host=127.0.0.1 port=5432 dbname=imagine_crm_ci user=postgres'
            .' password=ci-only-password connect_timeout=5 application_name='.$marker,
            PGSQL_CONNECT_FORCE_NEW,
        );
        $this->assertNotFalse($connection);
        try {
            $this->assertNotFalse(pg_query($connection, "SET statement_timeout = '15s'"));
            $this->assertNotFalse(pg_query($connection, "SET lock_timeout = '10s'"));
            $this->assertNotFalse(pg_query($connection, "SET idle_in_transaction_session_timeout = '15s'"));
            $result = pg_query($connection,
                'SELECT pid, backend_start::text, application_name, usename, datname FROM pg_stat_activity WHERE pid = pg_backend_pid()');
            $this->assertNotFalse($result);
            $identity = pg_fetch_assoc($result);
            $this->assertIsArray($identity);
            pg_free_result($result);
            $identity['pid'] = (int) $identity['pid'];
            return ['connection' => $connection] + $identity;
        } catch (\Throwable $error) {
            pg_close($connection);
            throw $error;
        }
    }

    private function pg07Revoke(array $session, SyntheticIdempotencyClaim $claim, bool $async = false): void
    {
        $this->assertNotFalse(pg_query($session['connection'], 'BEGIN'));
        $sql = 'UPDATE public.crm_lab_site_delivery_bindings SET state=$1, version=$2
                 WHERE organization_id=$3::uuid AND binding_reference=$4::uuid';
        $parameters = ['revoked', 2, self::ORG_A, $claim->identity->bindingReference];
        if ($async) {
            $this->assertTrue(pg_send_query_params($session['connection'], $sql, $parameters));
        } else {
            $result = pg_query_params($session['connection'], $sql, $parameters);
            $this->assertNotFalse($result);
            $this->assertSame(PGSQL_COMMAND_OK, pg_result_status($result));
            $this->assertSame(1, pg_affected_rows($result));
            pg_free_result($result);
        }
    }

    private function pg07WaitForRevocation(array $session): void
    {
        $deadline = microtime(true) + 8;
        do {
            $this->assertTrue(pg_consume_input($session['connection']));
            if (!pg_connection_busy($session['connection'])) {
                $result = pg_get_result($session['connection']);
                $this->assertNotFalse($result);
                $this->assertSame(PGSQL_COMMAND_OK, pg_result_status($result));
                $this->assertSame(1, pg_affected_rows($result));
                pg_free_result($result);
                $this->assertFalse(pg_get_result($session['connection']));
                return;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        $this->fail('PG07 revoker did not resume after capture rollback.');
    }

    private function pg07WaitForLock(array $waiter, array $holder, string $kind): void
    {
        $match = $kind === 'advisory'
            ? "w.locktype = 'advisory' AND h.classid = w.classid AND h.objid = w.objid AND h.objsubid = w.objsubid"
            : "w.locktype = 'relation' AND w.relation = 'public.crm_lab_lead_events'::regclass
               AND h.relation = w.relation AND h.mode = 'AccessExclusiveLock'
               AND a.query ILIKE 'insert into %crm_lab_lead_events%'";
        $deadline = microtime(true) + 5;
        do {
            $row = $this->owner->selectOne("SELECT EXISTS (
                SELECT 1 FROM pg_stat_activity a CROSS JOIN pg_stat_activity b
                 WHERE a.pid = ? AND a.backend_start = ?::timestamptz AND a.application_name = ? AND a.usename = ?
                   AND b.pid = ? AND b.backend_start = ?::timestamptz AND b.application_name = ? AND b.usename = ?
                   AND a.datname = 'imagine_crm_ci' AND b.datname = a.datname
                   AND a.wait_event_type = 'Lock' AND b.pid = ANY(pg_blocking_pids(a.pid))
                   AND EXISTS (SELECT 1 FROM pg_locks w JOIN pg_locks h
                     ON h.locktype = w.locktype AND h.database = w.database
                    WHERE w.pid = a.pid AND h.pid = b.pid AND NOT w.granted AND h.granted AND $match)
                ) AS waiting", [
                $waiter['pid'], $waiter['backend_start'], $waiter['application_name'], $waiter['usename'],
                $holder['pid'], $holder['backend_start'], $holder['application_name'], $holder['usename'],
            ]);
            if ((bool) $row->waiting) {
                return;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        $this->fail('PG07 expected real '.$kind.' lock wait was not observed.');
    }

    private function pg07AssertClean(array $state, array $worker): void
    {
        $this->assertSame($worker['identity']['pid'], $state['pid']);
        $this->assertSame(0, $state['transaction_level']);
        $this->assertFalse($state['pdo_in_transaction']);
        $this->assertSame('', $state['tenant']);
        $this->assertSame(0, $state['advisory_locks']);
        $this->assertSame(0, $state['unscoped_leads']);
        $this->assertSame(0, $state['unscoped_claims']);
    }

    private function pg07AssertAnswer(array $frame, array $worker, string $status): void
    {
        $this->assertSame('attempt', $frame['phase']);
        $this->assertNull($frame['exception']);
        $this->assertNull($frame['failure']);
        $this->assertSame($status, $frame['answer']['status_code']);
        $this->assertNoEffects($frame['answer']);
        $this->pg07AssertClean($frame['state'], $worker);
    }

    private function pg07AssertOtherTenant(array &$worker): void
    {
        $this->pg07Command($worker, 'other_tenant');
        $frame = $this->pg07ReadFrame($worker);
        $this->assertSame('other_tenant', $frame['phase']);
        $this->assertSame([self::LEAD_B], $frame['ids']);
        $this->pg07AssertClean($frame['state'], $worker);
    }

    private function pg07FinishWorker(array &$worker): void
    {
        $this->pg07Command($worker, 'stop');
        $this->assertSame('stopped', $this->pg07ReadFrame($worker)['phase']);
        $deadline = microtime(true) + 3;
        do {
            $state = proc_get_status($worker['process']);
            if (!$state['running']) {
                $this->assertSame(0, $state['exitcode'] >= 0 ? $state['exitcode'] : $worker['exit_code']);
                foreach ($worker['pipes'] as $pipe) {
                    fclose($pipe);
                }
                proc_close($worker['process']);
                $worker['process'] = null;
                $worker['pipes'] = [];
                $backendDeadline = microtime(true) + 3;
                do {
                    $remaining = $this->owner->table('pg_stat_activity')
                        ->where('application_name', $worker['marker'])->count();
                    if ($remaining === 0) {
                        return;
                    }
                    usleep(10000);
                } while (microtime(true) < $backendDeadline);
                $this->fail('PG07 stopped child retained its PostgreSQL backend.');
                return;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        $this->fail('PG07 worker did not stop within its deadline.');
    }

    private function pg07CleanupWorker(?array &$worker): void
    {
        if ($worker === null) {
            return;
        }
        if (is_resource($worker['process'])) {
            foreach ($worker['pipes'] as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            if (proc_get_status($worker['process'])['running']) {
                proc_terminate($worker['process']);
                $deadline = microtime(true) + 1;
                while (proc_get_status($worker['process'])['running'] && microtime(true) < $deadline) {
                    usleep(10000);
                }
                if (proc_get_status($worker['process'])['running']) {
                    proc_terminate($worker['process'], 9);
                }
            }
            proc_close($worker['process']);
        }
        // Exceptional cleanup only, restricted to this test's pre-connection
        // random marker, DB and role. Capture PID/start again, then match both.
        $remaining = $this->owner->select(
            "SELECT pid, backend_start::text FROM pg_stat_activity
              WHERE application_name = ? AND datname = 'imagine_crm_ci' AND usename = 'crm_app_ci'",
            [$worker['marker']],
        );
        foreach ($remaining as $backend) {
            $this->owner->selectOne("SELECT pg_terminate_backend(pid)
                FROM pg_stat_activity WHERE pid = ? AND backend_start = ?::timestamptz
                  AND application_name = ? AND datname = 'imagine_crm_ci' AND usename = 'crm_app_ci'",
                [$backend->pid, $backend->backend_start, $worker['marker']]);
        }
        $worker = null;
    }

    public function test_registered_site_binding_is_tenant_private_and_nonwritable_by_runtime_role(): void
    {
        $this->seedActiveSiteBinding(self::ORG_B);
        $claim = self::siteClaim(self::ORG_A);
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $until = $issued + 120;

        $result = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_REG02', 'sites', $claim,
            $nonce, $issued, $until, $issued + 2,
            self::syntheticSiteSignature(
                $claim, self::LEAD_A, 'DEMO_REG02', 'sites', $nonce, $issued, $until,
            ),
        );
        $this->assertSame('synthetic_conflict_denied', $result['status_code']);
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_lead_capture_keys')->count());

        $visible = (new PostgresActiveMembershipStore($this->one))->within(
            self::principal(), self::ORG_A, fn () =>
                $this->one->table('public.crm_lab_site_delivery_bindings')->count(),
        );
        $this->assertSame(0, $visible);
        $this->assertSame(0, $this->one->transactionLevel());
        foreach (['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $permission) {
            $this->assertFalse((bool) $this->one->selectOne(
                'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                ['public.crm_lab_site_delivery_bindings', $permission],
            )->allowed, $permission);
        }
        $this->assertSame(1, $this->owner->table('crm_lab_site_delivery_bindings')->count());
    }

    public function test_signed_site_capture_fails_closed_for_wrong_test_key_and_never_adds_routes(): void
    {
        try {
            $this->signedCapture(key: 'not-the-CI-fixture-key')->captureSigned(
                self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_SIGN06',
                'sites', self::siteClaim(self::ORG_A),
                'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa',
                1700000000, 1700000120, 1700000005, str_repeat('a', 64),
            );
            $this->fail('A non-CI key must not be trusted.');
        } catch (LogicException $e) {
            $this->assertSame(
                'Verificacao sintética de origem disponivel somente no CI descartavel.',
                $e->getMessage(),
            );
        }
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());
        $this->postJson('/api/sites/intake', [])->assertNotFound();
        $this->postJson('/api/leads/signed', [])->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratorySignedSiteCapture', $source);
        }
    }

    public function test_synthetic_followup_tasks_are_isolated_by_tenant_and_bound_to_lead(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_T001');
        $this->ledger($this->two)->createSynthetic(self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_T002');
        $a = $this->tasks()->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_A, 'TASK_CALL_001', '2026-12-01',
        );
        $b = $this->tasks($this->two)->createSynthetic(
            self::principal(), self::ORG_B, self::TASK_B,
            self::LEAD_B, 'TASK_MAIL_002', '2026-12-02',
        );
        $this->assertSame('synthetic_task_created', $a['status_code']);
        $this->assertSame('synthetic_task_created', $b['status_code']);
        $this->assertFalse($a['can_contact']);
        $this->assertFalse($a['can_notify']);
        $this->assertFalse($a['can_activate']);
        $visibleA = $this->tasks()->pendingSynthetic(self::principal(), self::ORG_A);
        $visibleB = $this->tasks($this->two)->pendingSynthetic(self::principal(), self::ORG_B);
        $this->assertSame([self::TASK_A], array_column($visibleA, 'task_id'));
        $this->assertSame([self::TASK_B], array_column($visibleB, 'task_id'));
        $this->assertSame('2026-12-01', $visibleA[0]['due_on']);
        $this->assertSame(2, $this->owner->table('crm_lab_task_events')->count());
        $this->assertSame(2, $this->owner->table('crm_lab_followup_tasks')->count());
        $this->assertSame(0, $this->one->table('public.crm_lab_followup_tasks')->count());
        $this->assertSame(0, $this->two->table('public.crm_lab_followup_tasks')->count());
        $this->assertSame(0, $this->one->transactionLevel());
    }

    public function test_synthetic_task_completion_is_optimistic_terminal_and_audited(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_T003');
        $this->tasks()->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_A, 'TASK_REVIEW_003', '2026-12-03',
        );
        $first = $this->tasks()->resolveSynthetic(
            self::principal(), self::ORG_A, self::TASK_A, 'done', 1,
        );
        $stale = $this->tasks($this->two)->resolveSynthetic(
            self::principal(), self::ORG_A, self::TASK_A, 'cancelled', 1,
        );
        $terminal = $this->tasks()->resolveSynthetic(
            self::principal(), self::ORG_A, self::TASK_A, 'cancelled', 2,
        );
        $foreign = $this->tasks($this->two)->resolveSynthetic(
            self::principal(), self::ORG_B, self::TASK_A, 'done', 1,
        );
        $this->assertSame('synthetic_task_resolved', $first['status_code']);
        $this->assertSame('synthetic_task_not_changed', $stale['status_code']);
        $this->assertSame('synthetic_task_not_changed', $terminal['status_code']);
        $this->assertSame('synthetic_task_not_changed', $foreign['status_code']);
        $this->assertSame([], $this->tasks()->pendingSynthetic(self::principal(), self::ORG_A));
        $row = $this->owner->table('crm_lab_followup_tasks')->first();
        $this->assertSame('done', $row->status);
        $this->assertSame(2, $row->version);
        $this->assertSame(['created','completed'], $this->owner->table('crm_lab_task_events')
            ->orderBy('version')->pluck('event_type')->all());
        $this->assertSame(0, $this->one->transactionLevel());
        $this->assertSame(0, $this->two->transactionLevel());
    }

    public function test_task_cannot_attach_to_lead_of_other_tenant_or_archived_lead(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_B, self::LEAD_B, 'DEMO_T004');
        $foreign = $this->tasks()->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_B, 'TASK_FOREIGN', '2026-12-04',
        );
        $this->assertSame('synthetic_task_denied', $foreign['status_code']);
        $this->assertSame(0, $this->owner->table('crm_lab_followup_tasks')->count());

        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_T005');
        $this->ledger()->archiveSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 1);
        $archived = $this->tasks()->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_A, 'TASK_ARCHIVED', '2026-12-04',
        );
        $this->assertSame('synthetic_task_denied', $archived['status_code']);
        $this->assertSame(0, $this->owner->table('crm_lab_task_events')->count());
        $this->assertSame(0, $this->owner->table('crm_lab_followup_tasks')->count());
    }

    public function test_task_update_rolls_back_if_append_only_audit_insert_fails(): void
    {
        $this->ledger()->createSynthetic(self::principal(), self::ORG_A, self::LEAD_A, 'DEMO_T006');
        $this->tasks()->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_A, 'TASK_AUDIT_006', '2026-12-06',
        );
        $this->owner->table('crm_lab_task_events')->insert([
            'event_id' => 'f7777777-7777-4777-8777-777777777777',
            'task_id' => self::TASK_A,
            'organization_id' => self::ORG_A,
            'version' => 2,
            'event_type' => 'completed',
        ]);
        try {
            $this->tasks($this->two)->resolveSynthetic(
                self::principal(), self::ORG_A, self::TASK_A, 'done', 1,
            );
            $this->fail('A task transition without its audit is prohibited.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Transicao de tarefa sintetica indisponivel.', $e->getMessage());
        }
        $row = $this->owner->table('crm_lab_followup_tasks')->first();
        $this->assertSame('todo', $row->status);
        $this->assertSame(1, $row->version);
        $this->assertSame(2, $this->owner->table('crm_lab_task_events')->count());
        $this->assertSame(0, $this->two->transactionLevel());
    }

    public function test_revoked_membership_and_restricted_task_role_are_denied(): void
    {
        foreach (['DELETE', 'TRUNCATE'] as $permission) {
            $this->assertFalse((bool) $this->one->selectOne(
                'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                ['public.crm_lab_followup_tasks', $permission],
            )->allowed);
        }
        foreach (['organization_id','lead_id','synthetic_subject','due_on'] as $column) {
            $this->assertFalse((bool) $this->one->selectOne(
                "SELECT has_column_privilege(current_user, 'public.crm_lab_followup_tasks', ?, 'UPDATE') AS allowed",
                [$column],
            )->allowed);
        }
        foreach (['SELECT', 'UPDATE', 'DELETE'] as $permission) {
            $this->assertFalse((bool) $this->one->selectOne(
                'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                ['public.crm_lab_task_events', $permission],
            )->allowed);
        }
        $this->owner->table('crm_memberships')
            ->where('organization_id', self::ORG_A)->update(['status' => 'revoked','version' => 2]);
        try {
            $this->tasks()->pendingSynthetic(self::principal(), self::ORG_A);
            $this->fail('Revoked member must not read tasks.');
        } catch (AuthorizationException) {
            $this->assertSame(0, $this->one->transactionLevel());
        }
        $this->postJson('/api/tasks', [])->assertNotFound();
        $this->get('/api/tasks')->assertNotFound();
    }

    public function test_integrated_signed_capture_creates_tenant_lead_then_audited_followup_task(): void
    {
        $claim = self::siteClaim(self::ORG_A);
        $this->seedActiveSiteBinding(self::ORG_A, $claim);
        $nonce = 'abababab-cccc-4ddd-8eee-aaaaaaaaaaaa';
        $issued = 1700000000;
        $expires = $issued + 120;
        $mac = self::syntheticSiteSignature(
            $claim, self::LEAD_A, 'DEMO_INTEGRATED01', 'sites',
            $nonce, $issued, $expires,
        );

        $earlyTask = $this->tasks()->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_A, 'TASK_INTEGRATED_01', '2026-12-10',
        );
        $this->assertSame('synthetic_task_denied', $earlyTask['status_code']);

        $captured = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_INTEGRATED01', 'sites', $claim,
            $nonce, $issued, $expires, $issued + 3, $mac,
        );
        $this->assertSame('synthetic_captured', $captured['status_code']);
        $this->assertNoEffects($captured);

        $task = $this->tasks($this->two)->createSynthetic(
            self::principal(), self::ORG_A, self::TASK_A,
            self::LEAD_A, 'TASK_INTEGRATED_01', '2026-12-10',
        );
        $this->assertSame('synthetic_task_created', $task['status_code']);
        $this->assertFalse($task['can_contact']);
        $this->assertFalse($task['can_notify']);

        $replay = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            'DEMO_INTEGRATED01', 'sites', $claim,
            $nonce, $issued, $expires, $issued + 5, $mac,
        );
        $this->assertSame('synthetic_replayed', $replay['status_code']);
        $this->assertCount(1, $this->tasks()->pendingSynthetic(
            self::principal(), self::ORG_A,
        ));
        $this->assertSame([], $this->tasks()->pendingSynthetic(
            self::principal(), self::ORG_B,
        ));

        $resolved = $this->tasks()->resolveSynthetic(
            self::principal(), self::ORG_A, self::TASK_A, 'done', 1,
        );
        $this->assertSame('synthetic_task_resolved', $resolved['status_code']);
        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_followup_tasks')->count());
        $this->assertSame(2, $this->owner->table('crm_lab_task_events')->count());

        foreach (['crm_lab_leads','crm_lab_lead_capture_keys',
            'crm_lab_lead_events','crm_lab_followup_tasks','crm_lab_task_events'] as $table) {
            $this->assertSame(self::ORG_A,
                $this->owner->table($table)->value('organization_id'), $table);
        }
        $this->assertSame(0, $this->one->transactionLevel());
        $this->assertSame(0, $this->two->transactionLevel());
    }

    public function test_wrong_database_role_and_public_endpoints_stay_denied(): void
    {
        try {
            (new LaboratoryTenantLeadLedger($this->owner))
                ->listSynthetic(self::principal(), self::ORG_A);
            $this->fail('Database superuser may not be used by service.');
        } catch (LogicException $e) {
            $this->assertSame('Papel de leads nao autorizado.', $e->getMessage());
        }
        $this->postJson('/api/leads', [])->assertNotFound();
        $this->get('/api/leads')->assertNotFound();
        $this->postJson('/api/kanban/move', [])->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('LaboratoryTenantLeadLedger', $source);
        }
    }

    /**
     * End-to-end laboratory seam using the ORIGINAL Sites F398 emitter with
     * the ORIGINAL CRM PG16 binding + FORCE RLS + signed ledger.
     * Never an operational sender, provisioning API, IAM grant or customer.
     */
    public function test_original_sites_f398_signed_capture_persists_only_with_current_binding(): void
    {
        if (getenv('CRM_SITES_F398_PG_CI') !== '1'
            || getenv('SITES_F398_CRM_CAPTURE_CI') !== '1') {
            $this->markTestSkipped('Pinned Sites original available only in dedicated synthetic PG16 CI.');
        }
        $source = getenv('F398_SITES_SOURCE');
        if (!is_string($source) || !is_file($source.'/src/Integration/SyntheticCrmCaptureEnvelope.php')) {
            throw new LogicException('Pinned original Sites emitter missing.');
        }
        require_once $source.'/src/Integration/SyntheticCrmCaptureEnvelope.php';

        $now = 1790000000;
        $claim = self::siteClaim(self::ORG_A);
        $wireInput = [
            'organizationReference' => self::ORG_A,
            'bindingReference' => $claim->identity->bindingReference,
            'submissionReference' => $claim->identity->submissionReference,
            'siteReference' => $claim->siteReference,
            'formReference' => $claim->formReference,
            'destinationReference' => $claim->destinationReference,
            'leadId' => self::LEAD_A,
            'syntheticTitle' => 'DEMO_F398_PG',
            'sourceCode' => 'sites',
            'nonce' => 'a8888888-8888-4888-8888-888888888888',
            'issuedAt' => $now - 10,
            'expiresAt' => $now + 40,
        ];
        $wire = \Imagine\Sites\Integration\SyntheticCrmCaptureEnvelope::sign($wireInput, $now);
        $this->assertSame(
            LaboratorySignedSiteCapture::canonicalForCi(
                $claim, $wire['leadId'], $wire['syntheticTitle'],
                $wire['sourceCode'], $wire['nonce'],
                $wire['issuedAt'], $wire['expiresAt'],
            ),
            \Imagine\Sites\Integration\SyntheticCrmCaptureEnvelope::canonical($wire),
        );

        // Even a correctly signed Sites message cannot provision its own binding.
        $missing = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            $wire['syntheticTitle'], $wire['sourceCode'], $claim,
            $wire['nonce'], $wire['issuedAt'], $wire['expiresAt'], $now, $wire['signature'],
        );
        $this->assertSame('synthetic_conflict_denied', $missing['status_code']);
        $this->assertSame(0, $this->owner->table('crm_lab_leads')->count());

        // Only the independent fixture owner grants this temporary lab binding.
        $this->seedActiveSiteBinding(self::ORG_A, $claim, 'sites');
        $accepted = $this->signedCapture()->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            $wire['syntheticTitle'], $wire['sourceCode'], $claim,
            $wire['nonce'], $wire['issuedAt'], $wire['expiresAt'], $now, $wire['signature'],
        );
        $replayed = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            $wire['syntheticTitle'], $wire['sourceCode'], $claim,
            $wire['nonce'], $wire['issuedAt'], $wire['expiresAt'], $now + 1, $wire['signature'],
        );
        $this->assertSame('synthetic_captured', $accepted['status_code']);
        $this->assertSame('synthetic_replayed', $replayed['status_code']);
        $this->assertNoEffects($accepted);
        $this->assertNoEffects($replayed);

        // Valid identity and binding do not permit forged form fields.
        $wrongForm = self::siteClaim(
            self::ORG_A, form: '45454545-2222-4222-8222-555555555552'
        );
        $forged = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            $wire['syntheticTitle'], $wire['sourceCode'], $wrongForm,
            $wire['nonce'], $wire['issuedAt'], $wire['expiresAt'], $now, $wire['signature'],
        );
        $this->assertSame('synthetic_integrity_denied', $forged['status_code']);

        // A second company cannot borrow the first company's registered form.
        $tenantBInput = $wireInput;
        $tenantBInput['organizationReference'] = self::ORG_B;
        $tenantBInput['leadId'] = self::LEAD_B;
        $tenantBInput['nonce'] = 'b8888888-8888-4888-8888-888888888888';
        $tenantB = \Imagine\Sites\Integration\SyntheticCrmCaptureEnvelope::sign($tenantBInput, $now);
        $otherTenant = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_B, self::LEAD_B,
            $tenantB['syntheticTitle'], $tenantB['sourceCode'],
            self::siteClaim(self::ORG_B),
            $tenantB['nonce'], $tenantB['issuedAt'], $tenantB['expiresAt'],
            $now, $tenantB['signature'],
        );
        $this->assertSame('synthetic_conflict_denied', $otherTenant['status_code']);

        $this->owner->table('crm_lab_site_delivery_bindings')
            ->where('organization_id', self::ORG_A)
            ->update(['state' => 'revoked', 'version' => 2]);
        $revoked = $this->signedCapture($this->two)->captureSigned(
            self::principal(), self::ORG_A, self::LEAD_A,
            $wire['syntheticTitle'], $wire['sourceCode'], $claim,
            $wire['nonce'], $wire['issuedAt'], $wire['expiresAt'], $now + 2, $wire['signature'],
        );
        $this->assertSame('synthetic_conflict_denied', $revoked['status_code']);
        foreach ([$missing, $forged, $otherTenant, $revoked] as $denied) {
            $this->assertNoEffects($denied);
        }

        $this->assertSame(1, $this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame(1, $this->owner->table('crm_lab_site_delivery_bindings')->count());
        $this->assertSame([], $this->one->table('public.crm_lab_leads')->get()->all());
        $this->assertSame([], $this->two->table('public.crm_lab_leads')->get()->all());
    }

}

