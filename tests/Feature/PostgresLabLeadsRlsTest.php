<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\PrincipalReference;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\LaboratorySignedSiteCapture;
use App\Core\Leads\LaboratoryTenantLeadLedger;
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
}
