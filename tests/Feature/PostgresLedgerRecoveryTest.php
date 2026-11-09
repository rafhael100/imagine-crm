<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncDenial;
use App\Core\Async\AsyncDiagnostic;
use App\Core\Async\AsyncFailureKind as Kind;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Fixtures\AsyncCases;
use Tests\Fixtures\ConsultativeLedgerInspector;
use Tests\Fixtures\LedgerDescription;
use Tests\Fixtures\LedgerFixtureManifest;
use Tests\Fixtures\LedgerGap as Gap;
use Tests\Fixtures\LedgerIntegrityProbe;
use Tests\Fixtures\LedgerReceipt;
use Tests\Fixtures\PostgresLedgerReader;
use Tests\Fixtures\PostgresSyntheticLedger as Ledger;
use Tests\TestCase;

final class PostgresLedgerRecoveryTest extends TestCase
{
    use \Tests\Concerns\SyntheticIam { setUp as private bootIam; tearDown as private finishIam; }
    private Connection $writer;
    private Connection $peer;
    private Connection $audit;
    private Connection $membership;
    private array $replicas = [];

    protected function setUp(): void
    {
        $this->bootIam();
        if (DB::getDriverName() !== 'pgsql' || getenv('ASYNC_AUDIT_TEST_USERNAME') !== 'crm_async_audit_ci') {
            $this->markTestSkipped('Requires disposable PostgreSQL 16 and SELECT-only audit role.');
        }
        $this->clearLedger();
        $settings = config('database.connections.pgsql'); $settings['url'] = null;
        $settings['username'] = getenv('ASYNC_TEST_USERNAME'); $settings['password'] = getenv('ASYNC_TEST_PASSWORD');
        $this->writer = $this->connection('crm_recovery_writer', $settings); $this->peer = $this->connection('crm_recovery_peer', $settings);
        $settings['username'] = getenv('ASYNC_AUDIT_TEST_USERNAME'); $settings['password'] = getenv('ASYNC_AUDIT_TEST_PASSWORD');
        $this->audit = $this->connection('crm_recovery_audit', $settings);
        $settings['username'] = getenv('RLS_TEST_USERNAME'); $settings['password'] = getenv('RLS_TEST_PASSWORD');
        $this->membership = $this->connection('crm_recovery_membership', $settings);
        $this->fixtures->table('crm_organizations')->insert([['id' => self::C], ['id' => self::D]]);
        $this->fixtures->table('crm_memberships')->insert(['id' => (string) Str::uuid(), 'organization_id' => self::C,
            'global_identity_id' => self::A, 'status' => 'active', 'version' => 1]);
    }

    protected function tearDown(): void
    {
        if (isset($this->writer)) {
            foreach ([$this->writer, $this->peer, $this->audit] as $db) { while ($db->transactionLevel()) { $db->rollBack(); } }
            foreach ($this->replicas as [$schema, $setupName, $auditName]) {
                DB::purge($auditName); DB::purge($setupName);
                // Fixed prefix + random hex, created in this disposable CI DB by this test only.
                $this->fixtures->statement('DROP SCHEMA '.$schema.' CASCADE');
            }
            $this->clearLedger();
            foreach (['crm_recovery_writer', 'crm_recovery_peer', 'crm_recovery_audit', 'crm_recovery_membership', 'crm_recovery_offline'] as $name) { DB::purge($name); }
        }
        $this->finishIam();
    }

    private function connection(string $name, array $settings): Connection
    {
        config(['database.connections.'.$name => $settings]); DB::purge($name); return DB::connection($name);
    }

    private function clearLedger(): void
    {
        foreach (array_reverse(Ledger::TABLES) as $table) { $this->fixtures->table($table)->whereIn('tenant_id', [self::C, self::D])->delete(); }
    }

    private function bridge(): AsyncAuthorityBridge
    {
        return new AsyncAuthorityBridge(new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class),
            new PostgresActiveMembershipStore($this->membership), new DenyByDefaultAuthorizer()));
    }

    private function input(string $tenant = self::C, string $class = 'B'): array
    {
        $input = AsyncCases::request($class); $input['tenant_id'] = $tenant;
        $input['executor']['tenant_id'] = $tenant; $input['intent']['scope']['id'] = $tenant; return $input;
    }

    private function seedLedger(string $tenant = self::C, string $class = 'B'): array
    {
        $input = $this->input($tenant, $class); $receipt = (new Ledger($this->writer, $this->bridge(), $tenant))->record($input);
        $this->assertSame('first_seen', $receipt->outcome);
        return [$input, new LedgerFixtureManifest([[LedgerDescription::parse($input, 1, 'lab.alpha'), [$receipt]]]), $receipt];
    }

    private function inspector(?Connection $audit = null, string $tenant = self::C): ConsultativeLedgerInspector
    {
        return new ConsultativeLedgerInspector(new PostgresLedgerReader($audit ?? $this->audit, $tenant), $this->bridge(), $tenant);
    }

    private function sample(?Connection $audit = null, string $tenant = self::C): array
    {
        return (new PostgresLedgerReader($audit ?? $this->audit, $tenant))->sample();
    }

    /** Schema setup/restore belongs only to the TEST, never to the inspector/reader. */
    private function replica(bool $install = true): array
    {
        $suffix = bin2hex(random_bytes(5)); $schema = 'lab_recovery_'.$suffix;
        $setupName = 'crm_recovery_setup_'.$suffix; $auditName = 'crm_recovery_read_'.$suffix;
        $this->fixtures->statement('CREATE SCHEMA '.$schema);
        $this->replicas[] = [$schema, $setupName, $auditName];
        $settings = config('database.connections.pgsql'); $settings['url'] = null; $settings['search_path'] = $schema;
        $setup = $this->connection($setupName, $settings);
        $original = DB::getDefaultConnection();
        try {
            DB::setDefaultConnection($setupName);
            if ($install) { (require database_path('experimental-migrations/async/2026_11_05_000004_create_synthetic_async_ledger.php'))->up(); }
        } finally { DB::setDefaultConnection($original); }
        $setup->statement('GRANT USAGE ON SCHEMA '.$schema.' TO crm_async_audit_ci');
        if ($install) { $setup->statement('GRANT SELECT ON ALL TABLES IN SCHEMA '.$schema.' TO crm_async_audit_ci'); }
        $settings['username'] = getenv('ASYNC_AUDIT_TEST_USERNAME'); $settings['password'] = getenv('ASYNC_AUDIT_TEST_PASSWORD');
        return [$setup, $this->connection($auditName, $settings)];
    }

    private function restoreFixture(Connection $setup, array $sample): void
    {
        $setup->transaction(function () use ($setup, $sample): void {
            foreach (array_keys(LedgerIntegrityProbe::COLUMNS) as $index => $name) {
                if ($sample[$name] !== []) { $setup->table(Ledger::TABLES[$index])->insert($sample[$name]); }
            }
        }, 1);
    }

    public function test_select_only_consistent_read_never_mutates_ledger_or_dispatches_domain_effects(): void
    {
        [$input, $manifest] = $this->seedLedger(); $before = $this->sample();
        Bus::fake(); Queue::fake(); Http::fake(); Mail::fake();
        Event::fakeExcept([\Illuminate\Database\Events\ConnectionEstablished::class,
            \Illuminate\Database\Events\TransactionBeginning::class, \Illuminate\Database\Events\StatementPrepared::class,
            \Illuminate\Database\Events\QueryExecuted::class, \Illuminate\Database\Events\TransactionCommitting::class,
            TransactionCommitted::class, \Illuminate\Database\Events\TransactionRolledBack::class]);
        $mode = null;
        DB::listen(function ($event) use (&$mode): void {
            if ($event->connectionName === 'crm_recovery_audit' && str_contains($event->sql, 'from "crm_lab_async_ledger"')) {
                $mode = $this->audit->selectOne("SELECT current_setting('transaction_read_only') AS readonly, current_setting('transaction_isolation') AS isolation");
            }
        });
        $report = $this->inspector()->inspect($input, $manifest);
        $this->assertSame([], $report->findings); $this->assertFalse($report->denial->decision->allowed);
        $this->assertSame('on', $mode->readonly); $this->assertSame('repeatable read', $mode->isolation);
        $this->assertSame('needs_owner', $report->signal()['owner_review']); $this->assertSame($before, $this->sample());
        Bus::assertNothingDispatched(); Queue::assertNothingPushed(); Event::assertNothingDispatched();
        Http::assertNothingSent(); Mail::assertNothingSent(); $this->assertClean();
    }

    public function test_full_snapshot_restore_matches_invariants_and_digests_without_authorization(): void
    {
        [$input, $manifest] = $this->seedLedger(); $this->seedLedger(self::D); $source = $this->sample();
        [$setup, $audit] = $this->replica(); $this->restoreFixture($setup, $source);
        $restored = $this->sample($audit); $this->assertSame($source, $restored);
        $this->assertSame(hash('sha256', json_encode($source)), hash('sha256', json_encode($restored)));
        $report = $this->inspector($audit)->inspect($input, $manifest);
        $this->assertSame([], $report->findings); $this->assertFalse($report->denial->decision->allowed);
        $this->assertSame('inconclusive', $report->signal()['assessment']);
        $this->assertSame([], $this->sample($audit, self::D)['ledger']);
        $this->assertSame(1, count($this->sample(tenant: self::D)['ledger']));
        $this->assertSame($source, $this->sample()); $this->assertClean();
    }

    public function test_partial_restore_and_changed_digest_are_detected_without_automatic_repair(): void
    {
        [$input, $manifest] = $this->seedLedger(); $source = $this->sample();
        [$setup, $audit] = $this->replica(); $partial = $source; $partial['outbox'] = [];
        $this->restoreFixture($setup, $partial);
        $setup->table(Ledger::TABLES[0])->update(['digest' => str_repeat('0', 64)]);
        $before = $this->sample($audit); $report = $this->inspector($audit)->inspect($input, $manifest);
        foreach ([Gap::MissingOutbox, Gap::DigestMismatch] as $gap) { $this->assertContains($gap, $report->findings); }
        $this->assertFalse($report->denial->decision->allowed); $this->assertSame('reconciliation_required', $report->signal()['state']);
        $this->assertSame($before, $this->sample($audit)); $this->assertSame($source, $this->sample());
    }

    public function test_conflicting_restore_is_rejected_atomically_and_remains_missing_not_completed(): void
    {
        [$input, $manifest] = $this->seedLedger(); $source = $this->sample(); [$setup, $audit] = $this->replica();
        $conflicting = $source; $copy = $source['ledger'][0]; $copy['id'] = (string) Str::uuid(); $conflicting['ledger'][] = $copy;
        try { $this->restoreFixture($setup, $conflicting); $this->fail('Duplicate restore must not be accepted.'); }
        catch (QueryException $e) { $this->assertSame('23505', $e->errorInfo[0]); }
        foreach ($this->sample($audit) as $rows) { $this->assertSame([], $rows); }
        $report = $this->inspector($audit)->inspect($input, $manifest);
        $this->assertContains(Gap::MissingLedger, $report->findings); $this->assertFalse($report->denial->decision->allowed);
        $this->assertSame($source, $this->sample());
    }

    public function test_select_role_has_no_writes_iam_or_business_access_and_rls_is_tenant_bound(): void
    {
        [, $manifest] = $this->seedLedger(); $this->seedLedger(self::D);
        $this->assertSame([Gap::CrossTenant], $this->inspector()->inspect($this->input(self::D), $manifest)->findings);
        $this->assertSame([self::C], array_unique(array_column($this->sample()['ledger'], 'tenant_id')));
        $this->assertSame([self::D], array_unique(array_column($this->sample(tenant: self::D)['ledger'], 'tenant_id')));
        foreach (Ledger::TABLES as $table) {
            $this->assertSame(0, $this->audit->table($table)->count());
            foreach (['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE', 'REFERENCES', 'TRIGGER'] as $permission) {
                $this->assertFalse($this->audit->selectOne('SELECT has_table_privilege(current_user, ?, ?) AS allowed', [$table, $permission])->allowed);
            }
            $this->audit->beginTransaction();
            try {
                $this->audit->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::C]);
                $this->assertSame(0, $this->audit->table($table)->where('tenant_id', self::D)->count());
            } finally { $this->audit->rollBack(); }
        }
        foreach (['crm_memberships', 'crm_global_identities', 'crm_auth_credentials', 'crm_auth_sessions', 'crm_auth_operations', 'crm_iam_audit_events'] as $table) {
            $this->assertFalse($this->audit->selectOne("SELECT has_table_privilege(current_user, ?, 'SELECT') AS allowed", [$table])->allowed);
        }
        $row = $this->sample()['ledger'][0]; $row['id'] = (string) Str::uuid();
        try { $this->audit->table(Ledger::TABLES[0])->insert($row); $this->fail('Reader must have no INSERT.'); }
        catch (QueryException $e) { $this->assertSame('42501', $e->errorInfo[0]); }
        $this->assertClean();
    }

    public function test_dirty_nested_privileged_and_insert_capable_readers_fail_closed(): void
    {
        [$input, $manifest] = $this->seedLedger();
        $this->audit->selectOne("SELECT set_config('app.tenant_id', ?, false)", [self::D]);
        $report = $this->inspector()->inspect($input, $manifest);
        $this->assertSame(Kind::ContextContamination, $report->diagnostic->kind);
        $this->assertSame(self::D, $this->audit->selectOne("SELECT current_setting('app.tenant_id') AS tenant")->tenant);
        $this->audit->statement('RESET app.tenant_id'); // Only fixture resets its own deliberate contamination.
        foreach ([$this->fixtures, $this->writer] as $unsafe) {
            $this->assertSame([Gap::UnsafeReader], $this->inspector($unsafe)->inspect($input, $manifest)->findings);
        }
        $this->audit->beginTransaction();
        $this->assertSame([Gap::UnsafeReader], $this->inspector()->inspect($input, $manifest)->findings);
        $this->assertSame(1, $this->audit->transactionLevel()); $this->audit->rollBack();
        $this->audit->statement('SET row_security = off');
        try { $this->assertSame([Gap::UnsafeReader], $this->inspector()->inspect($input, $manifest)->findings); }
        finally { $this->audit->statement('SET row_security = on'); }
        $this->assertClean();
    }

    public function test_absent_schema_unavailable_db_and_disabled_profile_never_reuse_old_success(): void
    {
        [$input, $manifest] = $this->seedLedger(); $this->assertSame([], $this->inspector()->inspect($input, $manifest)->findings);
        [, $audit] = $this->replica(false);
        $this->assertSame([Gap::SchemaUnavailable], $this->inspector($audit)->inspect($input, $manifest)->findings);
        $settings = config('database.connections.crm_recovery_audit'); $settings['port'] = 1;
        $offline = $this->connection('crm_recovery_offline', $settings);
        $failure = $this->inspector($offline)->inspect($input, $manifest);
        $this->assertSame([Gap::ReadUnavailable], $failure->findings);
        $this->assertSame(Kind::InfrastructureUnavailable, $failure->diagnostic->kind);
        $this->assertFalse($failure->denial->decision->allowed);
        foreach ([['testing', false], ['production', true], ['staging', true]] as [$env, $profile]) {
            $this->app->instance('env', $env); config(['identity.experimental' => $profile]);
            $this->assertSame([Gap::LaboratoryDisabled], $this->inspector($offline)->inspect($input, $manifest)->findings);
        }
        $this->app->instance('env', 'testing'); config(['identity.experimental' => true]); $this->assertClean();
    }

    public function test_force_rls_cannot_be_disabled_even_in_disposable_restore(): void
    {
        [$input, $manifest] = $this->seedLedger(); [$setup, $audit] = $this->replica();
        $this->restoreFixture($setup, $this->sample());
        $setup->statement('ALTER TABLE crm_lab_async_outbox NO FORCE ROW LEVEL SECURITY');
        $report = $this->inspector($audit)->inspect($input, $manifest);
        $this->assertSame([Gap::UnsafeReader], $report->findings); $this->assertFalse($report->denial->decision->allowed);
    }

    public function test_read_only_transaction_itself_blocks_writes_independent_of_role_acl(): void
    {
        [$setup] = $this->replica();
        $setup->beginTransaction();
        try {
            $setup->statement('SET TRANSACTION READ ONLY');
            try { $setup->table(Ledger::TABLES[0])->delete(); $this->fail('Read-only transaction accepted mutation.'); }
            catch (QueryException $e) { $this->assertSame('25006', $e->errorInfo[0]); }
        } finally { $setup->rollBack(); }
    }

    public function test_column_only_update_grant_is_also_refused_in_disposable_replica(): void
    {
        [$input, $manifest] = $this->seedLedger(); [$setup, $audit] = $this->replica();
        $this->restoreFixture($setup, $this->sample());
        // No source grants change. The schema and this grant disappear together at teardown.
        $setup->statement('GRANT UPDATE (state) ON crm_lab_async_outbox TO crm_async_audit_ci');
        $this->assertFalse($audit->selectOne("SELECT has_table_privilege(current_user, 'crm_lab_async_outbox', 'UPDATE') AS allowed")->allowed);
        $this->assertTrue($audit->selectOne("SELECT has_any_column_privilege(current_user, 'crm_lab_async_outbox', 'UPDATE') AS allowed")->allowed);
        $report = $this->inspector($audit)->inspect($input, $manifest);
        $this->assertSame([Gap::UnsafeReader], $report->findings); $this->assertFalse($report->denial->decision->allowed);
    }

    private function pendingPeerRows(array $source): array
    {
        $input = $this->input(); $input['operation_id'] = (string) Str::uuid(); $input['idempotency_key'] = (string) Str::uuid();
        $description = LedgerDescription::parse($input, 1, 'lab.alpha');
        $receipt = new LedgerReceipt('first_seen', (string) Str::uuid(), new AsyncDenial((string) Str::uuid(), []), AsyncDiagnostic::reconciliation());
        $row = $source['ledger'][0]; $row['id'] = (string) Str::uuid(); $row['operation_id'] = $input['operation_id'];
        $row['idempotency_key'] = $input['idempotency_key']; $row['digest'] = $description->digest;
        $rows = ['ledger' => [$row], 'attempts' => [array_replace($source['attempts'][0], ['id' => $receipt->attemptId,
            'ledger_id' => $row['id'], 'correlation_id' => $receipt->denial->observation->correlationId, 'causation_id' => $receipt->denial->observation->id])],
            'inbox' => [array_replace($source['inbox'][0], ['id' => (string) Str::uuid(), 'ledger_id' => $row['id']])],
            'outbox' => [array_replace($source['outbox'][0], ['id' => (string) Str::uuid(), 'ledger_id' => $row['id']])]];
        $this->peer->beginTransaction(); $this->peer->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::C]);
        foreach (array_keys($rows) as $index => $name) { $this->peer->table(Ledger::TABLES[$index])->insert($rows[$name]); }
        return [$description, [$receipt]];
    }

    public function test_two_connections_see_only_committed_consistent_snapshot_not_mixed_table_generations(): void
    {
        [$input, $manifest] = $this->seedLedger(); $source = $this->sample(); $next = $this->pendingPeerRows($source);
        $full = new LedgerFixtureManifest([...$manifest->cases, $next]); $armed = true;
        DB::listen(function ($event) use (&$armed): void {
            if ($armed && $event->connectionName === 'crm_recovery_audit' && str_contains($event->sql, 'from "crm_lab_async_ledger"')) {
                $armed = false; $this->peer->commit(); // Commit after reader's snapshot was established.
            }
        });
        $snapshot = $this->sample(); $this->assertSame($source, $snapshot);
        $this->assertContains(Gap::MissingLedger, (new LedgerIntegrityProbe())->inspect($snapshot, $full, self::C)->findings);
        $this->assertSame([], $this->inspector()->inspect($input, $full)->findings);
        foreach ($this->sample() as $rows) { $this->assertCount(2, $rows); }
        $this->assertClean();
    }

    public function test_uncommitted_and_rolled_back_attempts_do_not_become_recovered_facts(): void
    {
        [$input, $manifest] = $this->seedLedger(); $source = $this->sample(); $next = $this->pendingPeerRows($source);
        $expectedButUncommitted = new LedgerFixtureManifest([...$manifest->cases, $next]);
        $this->assertSame($source, $this->sample()); $this->peer->rollBack();
        $report = $this->inspector()->inspect($input, $expectedButUncommitted);
        $this->assertContains(Gap::MissingLedger, $report->findings); $this->assertContains(Gap::MissingAttempt, $report->findings);
        $this->assertSame($source, $this->sample()); $this->assertFalse($report->denial->decision->allowed); $this->assertClean();
    }

    public function test_lost_commit_ack_is_read_consultatively_without_retry_or_new_attempt(): void
    {
        $armed = true;
        $this->app['events']->listen(TransactionCommitted::class, function ($event) use (&$armed): void {
            if ($armed && $event->connectionName === 'crm_recovery_writer') { $armed = false; throw new \RuntimeException('synthetic lost ACK'); }
        });
        $input = $this->input(); $receipt = (new Ledger($this->writer, $this->bridge(), self::C))->record($input);
        $this->assertSame('inconclusive', $receipt->outcome);
        // No manufactured first_seen receipt: the fixture has no verified attempt acknowledgement.
        $manifest = new LedgerFixtureManifest([[LedgerDescription::parse($input, 1, 'lab.alpha'), []]]);
        DB::purge('crm_recovery_audit'); $this->audit = DB::connection('crm_recovery_audit');
        $before = $this->sample(); $report = $this->inspector()->inspect($input, $manifest);
        $this->assertContains(Gap::UnexpectedRecord, $report->findings); $this->assertFalse($report->denial->decision->allowed);
        $this->assertSame($before, $this->sample()); $this->assertCount(1, $before['attempts']); $this->assertClean();
    }

    public function test_consistent_snapshot_does_not_override_revoked_membership_or_session(): void
    {
        $this->login(); [$input, $manifest] = $this->seedLedger(class: 'A'); $before = $this->sample(); $inspector = $this->inspector();
        $this->assertSame([], $inspector->inspect($input, $manifest)->findings);
        $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => 'suspended']);
        $suspended = $inspector->inspect($input, $manifest);
        $this->assertSame(Kind::MembershipDenied, $suspended->denial->diagnostic->kind); $this->assertFalse($suspended->denial->decision->allowed);
        $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->update(['state' => 'revoked']);
        $revoked = $inspector->inspect($input, $manifest);
        $this->assertSame(Kind::SessionRevoked, $revoked->denial->diagnostic->kind); $this->assertFalse($revoked->denial->decision->allowed);
        $this->assertSame($before, $this->sample()); $this->assertClean();
    }

    private function assertClean(): void
    {
        foreach ([$this->audit, $this->peer, $this->writer, $this->membership] as $db) {
            $this->assertSame(0, $db->transactionLevel());
            $this->assertContains($db->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, '']);
        }
        $this->assertSame(0, $this->db->transactionLevel());
    }
}
