<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncFailureKind as Kind;
use App\Core\Async\AsyncState;
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
use Tests\Fixtures\AsyncCases as Cases;
use Tests\Fixtures\PostgresSyntheticLedger as Ledger;
use Tests\TestCase;

final class PostgresAsyncLedgerTest extends TestCase
{
    use \Tests\Concerns\SyntheticIam { setUp as private bootIam; tearDown as private finishIam; }
    private Connection $ledgerDb;
    private Connection $businessDb;

    protected function setUp(): void
    {
        $this->bootIam();
        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_TEST_USERNAME')) {
            $this->markTestSkipped('Requires isolated PostgreSQL 16 async role and FORCE RLS.');
        }
        $this->clearLedger();
        $settings = config('database.connections.pgsql'); $settings['url'] = null;
        $settings['username'] = getenv('ASYNC_TEST_USERNAME'); $settings['password'] = getenv('ASYNC_TEST_PASSWORD');
        config(['database.connections.crm_async_lab' => $settings]); DB::purge('crm_async_lab');
        $this->ledgerDb = DB::connection('crm_async_lab');
        $this->ledgerDb->statement("SET lock_timeout = '150ms'");
        $settings['username'] = getenv('RLS_TEST_USERNAME'); $settings['password'] = getenv('RLS_TEST_PASSWORD');
        config(['database.connections.crm_async_membership' => $settings]); DB::purge('crm_async_membership');
        $this->businessDb = DB::connection('crm_async_membership');
        $this->fixtures->table('crm_organizations')->insert([['id' => self::C], ['id' => self::D]]);
        $this->fixtures->table('crm_memberships')->insert([
            'id' => (string) Str::uuid(), 'organization_id' => self::C, 'global_identity_id' => self::A,
            'status' => 'active', 'version' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->ledgerDb)) {
            while ($this->fixtures->transactionLevel()) { $this->fixtures->rollBack(); }
            $this->clearLedger(); DB::purge('crm_async_lab'); DB::purge('crm_async_membership');
        }
        $this->finishIam();
    }

    private function clearLedger(): void
    {
        foreach (array_reverse(Ledger::TABLES) as $table) {
            $this->fixtures->table($table)->whereIn('tenant_id', [self::C, self::D])->delete();
        }
    }

    private function ledger(string $tenant = self::C, ?Connection $connection = null): Ledger
    {
        $bridge = new AsyncAuthorityBridge(new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class),
            new PostgresActiveMembershipStore($this->businessDb), new DenyByDefaultAuthorizer()));
        return new Ledger($connection ?? $this->ledgerDb, $bridge, $tenant);
    }

    private function input(string $tenant = self::C, string $class = 'B'): array
    {
        $input = Cases::request($class); $input['tenant_id'] = $tenant;
        $input['executor']['tenant_id'] = $tenant; $input['intent']['scope']['id'] = $tenant;
        return $input;
    }

    public function test_atomic_inert_rows_deduplicate_after_new_connection_and_never_dispatch(): void
    {
        Bus::fake(); Queue::fake(); Event::fake(); Http::fake(); Mail::fake();
        $first = $this->ledger()->record($this->input());
        $this->assertSame('first_seen', $first->outcome);
        foreach (Ledger::TABLES as $table) { $this->assertSame(1, $this->fixtures->table($table)->count()); }
        DB::purge('crm_async_lab'); $this->ledgerDb = DB::connection('crm_async_lab');
        $duplicate = $this->ledger()->record($this->input());
        $this->assertSame('duplicate', $duplicate->outcome); $this->assertNotSame($first->attemptId, $duplicate->attemptId);
        $this->assertFalse($first->denial->decision->allowed); $this->assertFalse($duplicate->denial->decision->allowed);
        $this->assertSame(AsyncState::ReconciliationRequired, $duplicate->reconciliation->lifecycle->state);
        $this->assertSame(2, $this->fixtures->table(Ledger::TABLES[1])->count());
        foreach ([Ledger::TABLES[0], Ledger::TABLES[2], Ledger::TABLES[3]] as $table) {
            $this->assertSame(['registered_for_test'], $this->fixtures->table($table)->pluck('state')->all());
        }
        Bus::assertNothingDispatched(); Queue::assertNothingPushed(); Event::assertNothingDispatched();
        Mail::assertNothingSent(); Http::assertNothingSent(); $this->assertClean();
    }

    public function test_digest_conflicts_key_aliases_and_logical_operation_aliases_do_not_create_new_intents(): void
    {
        $ledger = $this->ledger(); $input = $this->input(); $ledger->record($input);
        $this->assertSame('conflict', $ledger->record($input, 1, 'lab.beta')->outcome);
        $input['operation_id'] = (string) Str::uuid();
        $this->assertSame('conflict', $ledger->record($input)->outcome);
        $input = $this->input(); $input['idempotency_key'] = (string) Str::uuid();
        $this->assertSame('conflict', $ledger->record($input)->outcome);
        $this->assertSame(1, $this->fixtures->table(Ledger::TABLES[0])->count());
        $this->assertSame(4, $this->fixtures->table(Ledger::TABLES[1])->count()); $this->assertClean();
    }

    public function test_tenant_revision_and_scope_are_independent_but_do_not_grant_authority(): void
    {
        $a = $this->ledger()->record($this->input());
        $b = $this->ledger(self::D)->record($this->input(self::D));
        $v2 = $this->ledger()->record($this->input(), 2);
        $input = $this->input(); $input['intent']['scope'] = ['kind' => 'record', 'id' => Cases::OPERATION];
        $scope = $this->ledger()->record($input);
        foreach ([$a, $b, $v2, $scope] as $result) {
            $this->assertSame('first_seen', $result->outcome); $this->assertFalse($result->denial->decision->allowed);
        }
        $this->assertSame(4, $this->fixtures->table(Ledger::TABLES[0])->count());
        $this->assertSame('inconclusive', $this->ledger()->record($this->input(self::D))->outcome);
        $this->assertClean();
    }

    public function test_two_connections_contend_on_unique_insert_then_replay_after_commit(): void
    {
        $this->ledger()->record($this->input()); $row = (array) $this->fixtures->table(Ledger::TABLES[0])->first();
        $this->clearLedger(); $this->fixtures->beginTransaction();
        $this->fixtures->table(Ledger::TABLES[0])->insert($row); // First connection's uncommitted intent.
        $blocked = $this->ledger()->record($this->input());
        $this->assertSame('inconclusive', $blocked->outcome);
        $this->assertSame(Kind::ConcurrencyBlocked, $blocked->diagnostic->kind);
        $this->assertFalse($blocked->denial->decision->allowed);
        $this->assertSame('first_seen', $this->ledger(self::D)->record($this->input(self::D))->outcome);
        $this->fixtures->commit();
        $this->assertSame('duplicate', $this->ledger()->record($this->input())->outcome);
        $this->assertSame(2, $this->fixtures->table(Ledger::TABLES[0])->count()); $this->assertClean();
    }

    public function test_rollback_of_competing_insert_allows_later_first_seen_without_automatic_retry(): void
    {
        $this->ledger()->record($this->input()); $row = (array) $this->fixtures->table(Ledger::TABLES[0])->first();
        $this->clearLedger(); $this->fixtures->beginTransaction(); $this->fixtures->table(Ledger::TABLES[0])->insert($row);
        $this->assertSame('inconclusive', $this->ledger()->record($this->input())->outcome);
        $this->fixtures->rollBack();
        $this->assertSame(0, $this->fixtures->table(Ledger::TABLES[0])->count());
        $this->assertSame('first_seen', $this->ledger()->record($this->input())->outcome); $this->assertClean();
    }

    public function test_exception_after_local_writes_rolls_back_all_four_tables(): void
    {
        $armed = true;
        DB::listen(function ($event) use (&$armed): void {
            if ($armed && $event->connectionName === 'crm_async_lab' && str_starts_with($event->sql, 'insert into "crm_lab_async_outbox"')) {
                $armed = false; throw new \RuntimeException('synthetic crash before commit');
            }
        });
        $result = $this->ledger()->record($this->input());
        $this->assertSame('inconclusive', $result->outcome); $this->assertFalse($result->denial->decision->allowed);
        foreach (Ledger::TABLES as $table) { $this->assertSame(0, $this->fixtures->table($table)->count()); }
        $this->assertSame('first_seen', $this->ledger()->record($this->input())->outcome); $this->assertClean();
    }

    public function test_lost_ack_after_commit_is_inconclusive_and_reconnection_observes_duplicate(): void
    {
        $armed = true;
        $this->app['events']->listen(TransactionCommitted::class, function ($event) use (&$armed): void {
            if ($armed && $event->connectionName === 'crm_async_lab') {
                $armed = false; throw new \RuntimeException('synthetic lost commit acknowledgement');
            }
        });
        $result = $this->ledger()->record($this->input());
        $this->assertSame('inconclusive', $result->outcome); $this->assertFalse($result->denial->decision->allowed);
        $this->assertSame(1, $this->fixtures->table(Ledger::TABLES[0])->count());
        DB::purge('crm_async_lab'); $this->ledgerDb = DB::connection('crm_async_lab');
        $this->assertSame('duplicate', $this->ledger()->record($this->input())->outcome); $this->assertClean();
    }

    public function test_expired_future_invalid_and_disabled_requests_do_not_write(): void
    {
        foreach (['expired', 'future', 'payload'] as $case) {
            $input = $this->input();
            if ($case === 'expired') { $input['expires_at'] = now()->getTimestamp(); }
            if ($case === 'future') { $input['issued_at'] += 10; }
            if ($case === 'payload') { $input['payload'] = 'synthetic-secret'; }
            $this->assertSame('inconclusive', $this->ledger()->record($input)->outcome);
        }
        foreach ([['testing', false], ['production', true], ['staging', true]] as [$env, $profile]) {
            $this->app->instance('env', $env); config(['identity.experimental' => $profile]);
            $this->assertSame(Kind::LaboratoryDisabled, $this->ledger()->record($this->input())->diagnostic->kind);
        }
        $this->app->instance('env', 'testing'); config(['identity.experimental' => true]);
        $this->assertSame(0, $this->fixtures->table(Ledger::TABLES[0])->count());
    }

    public function test_replay_revalidates_human_session_and_membership_even_when_record_already_exists(): void
    {
        $this->login(); $input = $this->input(self::C, 'A'); $ledger = $this->ledger();
        $this->assertSame(Kind::AuthorityUnproven, $ledger->record($input)->denial->diagnostic->kind);
        $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => 'suspended']);
        $replay = $ledger->record($input); $this->assertSame('duplicate', $replay->outcome);
        $this->assertSame(Kind::MembershipDenied, $replay->denial->diagnostic->kind);
        $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->update(['state' => 'revoked']);
        $revoked = $ledger->record($input); $this->assertSame('duplicate', $revoked->outcome);
        $this->assertSame(Kind::SessionRevoked, $revoked->denial->diagnostic->kind);
        $this->assertFalse($revoked->denial->decision->allowed); $this->assertClean();
    }

    public function test_dirty_context_privileged_role_and_actual_connection_failure_are_not_accepted(): void
    {
        $this->ledgerDb->selectOne("SELECT set_config('app.tenant_id', ?, false)", [self::D]);
        $this->assertSame(Kind::ContextContamination, $this->ledger()->record($this->input())->diagnostic->kind);
        $this->assertSame(self::D, $this->ledgerDb->selectOne("SELECT current_setting('app.tenant_id') AS tenant")->tenant);
        $this->ledgerDb->statement('RESET app.tenant_id'); // Fixture owns contamination; runtime never cleans silently.
        $this->assertSame('inconclusive', $this->ledger(connection: $this->fixtures)->record($this->input())->outcome);
        $settings = config('database.connections.crm_async_lab'); $settings['port'] = 1;
        config(['database.connections.crm_async_offline' => $settings]);
        try {
            $failure = $this->ledger(connection: DB::connection('crm_async_offline'))->record($this->input());
            $this->assertSame(Kind::InfrastructureUnavailable, $failure->diagnostic->kind);
            $this->assertSame('inconclusive', $failure->outcome);
        } finally { DB::purge('crm_async_offline'); }
        $this->assertSame(0, $this->fixtures->table(Ledger::TABLES[0])->count()); $this->assertClean();
    }

    public function test_rls_all_tables_cross_tenant_writes_and_role_grants_are_minimal(): void
    {
        $this->ledger()->record($this->input()); $this->ledger(self::D)->record($this->input(self::D));
        foreach (Ledger::TABLES as $table) {
            $this->assertSame(0, $this->ledgerDb->table($table)->count());
            $this->ledgerDb->transaction(function () use ($table): void {
                $this->ledgerDb->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::C]);
                $this->assertSame([self::C], $this->ledgerDb->table($table)->distinct()->pluck('tenant_id')->all());
                $row = (array) $this->fixtures->table($table)->where('tenant_id', self::D)->first(); $row['id'] = (string) Str::uuid();
                try { $this->ledgerDb->table($table)->insert($row); $this->fail('Cross tenant INSERT accepted.'); }
                catch (QueryException $e) { $this->assertSame('42501', $e->errorInfo[0]); }
                // PostgreSQL aborts the transaction; no further SQL until rollback.
            });
            foreach (['UPDATE', 'DELETE', 'TRUNCATE', 'TRIGGER'] as $privilege) {
                $this->assertFalse($this->ledgerDb->selectOne('SELECT has_table_privilege(current_user, ?, ?) AS allowed', [$table, $privilege])->allowed);
            }
        }
        foreach (['crm_memberships', 'crm_global_identities', 'crm_auth_credentials', 'crm_auth_sessions', 'crm_auth_mandates'] as $table) {
            $this->assertFalse($this->ledgerDb->selectOne("SELECT has_table_privilege(current_user, ?, 'SELECT') AS allowed", [$table])->allowed);
        }
        $this->assertClean();
    }

    public function test_database_rejects_business_states_and_composite_foreign_key_cross_tenant(): void
    {
        $this->ledger()->record($this->input());
        $row = (array) $this->fixtures->table(Ledger::TABLES[0])->first();
        foreach (['admitted', 'forwarded', 'officially_confirmed'] as $state) {
            $row['id'] = (string) Str::uuid(); $row['operation_id'] = $row['id']; $row['idempotency_key'] = $row['id']; $row['state'] = $state;
            try { $this->fixtures->table(Ledger::TABLES[0])->insert($row); $this->fail('Business state persisted.'); }
            catch (QueryException $e) { $this->assertSame('23514', $e->errorInfo[0]); }
        }
        $attempt = (array) $this->fixtures->table(Ledger::TABLES[1])->first();
        $attempt['id'] = (string) Str::uuid(); $attempt['tenant_id'] = self::D;
        try { $this->fixtures->table(Ledger::TABLES[1])->insert($attempt); $this->fail('Cross tenant FK accepted.'); }
        catch (QueryException $e) { $this->assertSame('23503', $e->errorInfo[0]); }
    }

    public function test_standard_migrate_excludes_async_and_explicit_migration_rolls_back_in_disposable_schema(): void
    {
        $schema = 'lab_async_probe_'.bin2hex(random_bytes(5)); $original = DB::getDefaultConnection();
        $this->fixtures->statement('CREATE SCHEMA '.$schema);
        $settings = config('database.connections.pgsql'); $settings['url'] = null; $settings['search_path'] = $schema;
        config(['database.connections.crm_async_migration_probe' => $settings]);
        try {
            DB::setDefaultConnection('crm_async_migration_probe');
            $this->artisan('migrate', ['--database' => 'crm_async_migration_probe', '--force' => true])->assertExitCode(0);
            foreach (Ledger::TABLES as $table) { $this->assertFalse(DB::connection()->getSchemaBuilder()->hasTable($table)); }
            $migration = require database_path('experimental-migrations/async/2026_11_05_000004_create_synthetic_async_ledger.php');
            $migration->up();
            foreach (Ledger::TABLES as $table) { $this->assertTrue(DB::connection()->getSchemaBuilder()->hasTable($table)); }
            $migration->down();
            foreach (Ledger::TABLES as $table) { $this->assertFalse(DB::connection()->getSchemaBuilder()->hasTable($table)); }
            $this->assertTrue(DB::connection()->getSchemaBuilder()->hasTable('crm_memberships'));
        } finally {
            DB::setDefaultConnection($original); DB::purge('crm_async_migration_probe');
            $this->fixtures->statement('DROP SCHEMA '.$schema.' CASCADE');
        }
    }

    private function assertClean(): void
    {
        foreach ([$this->ledgerDb, $this->businessDb] as $db) {
            $this->assertSame(0, $db->transactionLevel());
            $this->assertContains($db->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, '']);
        }
        $this->assertSame(0, $this->db->transactionLevel());
    }
}
