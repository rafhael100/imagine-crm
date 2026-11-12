<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GlobalIdentity;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard as Guard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class PostgresMembershipRlsTest extends TestCase
{
    private const ORG_A = '11111111-1111-4111-8111-111111111111';
    private const ORG_B = '22222222-2222-4222-8222-222222222222';
    private const PRINCIPAL = '33333333-3333-4333-8333-333333333333';
    private const OTHER_PRINCIPAL = '77777777-7777-4777-8777-777777777777';

    protected function setUp(): void
    {
        parent::setUp();

        // The SQLite scaffold job intentionally skips PostgreSQL-only checks.
        if (DB::getDriverName() !== 'pgsql' || !getenv('RLS_TEST_USERNAME')) {
            $this->markTestSkipped('Requires isolated PostgreSQL integration job.');
        }

        $now = now();
        DB::table('crm_global_identities')->insertOrIgnore([
            ['id' => self::PRINCIPAL, 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::OTHER_PRINCIPAL, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('crm_organizations')->insertOrIgnore([
            ['id' => self::ORG_A, 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::ORG_B, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('crm_memberships')->insertOrIgnore([
            [
                'id' => '44444444-4444-4444-8444-444444444444',
                'organization_id' => self::ORG_A,
                'global_identity_id' => self::PRINCIPAL,
                'status' => 'active', 'version' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => '55555555-5555-4555-8555-555555555555',
                'organization_id' => self::ORG_B,
                'global_identity_id' => self::PRINCIPAL,
                'status' => 'suspended', 'version' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        config(['database.connections.pgsql_rls_probe' => $settings]);
        DB::purge('pgsql_rls_probe');
        DB::table('crm_memberships')->where('organization_id', self::ORG_A)
            ->update(['status' => 'active', 'version' => 1]);
        DB::table('crm_memberships')->where('organization_id', self::ORG_B)
            ->update(['status' => 'suspended', 'version' => 1]);
    }

    public function test_missing_tenant_context_denies_rows(): void
    {
        $this->assertSame(0, DB::connection('pgsql_rls_probe')->table('crm_memberships')->count());
    }

    public function test_switching_tenants_does_not_reuse_old_context(): void
    {
        $probe = DB::connection('pgsql_rls_probe');

        $seenA = $probe->transaction(function ($conn): array {
            $conn->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_A]);
            return $conn->table('crm_memberships')->pluck('organization_id')->all();
        });
        $this->assertSame([self::ORG_A], $seenA);
        $this->assertSame(0, $probe->table('crm_memberships')->count());

        $seenB = $probe->transaction(function ($conn): array {
            $conn->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_B]);
            return $conn->table('crm_memberships')->pluck('organization_id')->all();
        });
        $this->assertSame([self::ORG_B], $seenB);
        $this->assertSame(0, $probe->table('crm_memberships')->count());
    }

    public function test_cross_tenant_insert_is_rejected_by_with_check(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $probe->beginTransaction();

        try {
            $probe->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_A]);

            try {
                $probe->table('crm_memberships')->insert([
                    'id' => '88888888-8888-4888-8888-888888888888',
                    'organization_id' => self::ORG_B,
                    'global_identity_id' => self::OTHER_PRINCIPAL,
                    'status' => 'active', 'version' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->fail('A cross-tenant INSERT must not succeed.');
            } catch (QueryException $exception) {
                $this->assertSame('42501', $exception->errorInfo[0] ?? null);
            }
        } finally {
            $probe->rollBack();
        }
    }

    public function test_cross_tenant_update_is_rejected_by_with_check(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $probe->beginTransaction();

        try {
            $probe->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_A]);

            try {
                $probe->table('crm_memberships')
                    ->where('organization_id', self::ORG_A)
                    ->update(['organization_id' => self::ORG_B]);
                $this->fail('A cross-tenant UPDATE must not succeed.');
            } catch (QueryException $exception) {
                $this->assertSame('42501', $exception->errorInfo[0] ?? null);
            }
        } finally {
            $probe->rollBack();
        }
    }

    public function test_app_role_cannot_read_global_identity_catalog(): void
    {
        $probe = DB::connection('pgsql_rls_probe');

        try {
            $probe->table('crm_global_identities')->count();
            $this->fail('Application role should have no access to global identities.');
        } catch (QueryException $exception) {
            $this->assertSame('42501', $exception->errorInfo[0] ?? null);
        }
    }

    public function test_app_role_does_not_bypass_rls_or_own_membership_table(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $role = $probe->selectOne(
            'SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user'
        );
        $this->assertFalse($role->rolsuper);
        $this->assertFalse($role->rolbypassrls);

        $ownership = $probe->selectOne(
            "SELECT pg_get_userbyid(relowner) AS owner FROM pg_class WHERE relname = 'crm_memberships'"
        );
        $this->assertNotSame(getenv('RLS_TEST_USERNAME'), $ownership->owner);
    }

    public function test_verified_membership_lookup_is_scoped_and_cleared_after_commit(): void
    {
        $seen = $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_A, function ($membership): array {
            $this->assertSame(self::PRINCIPAL, $membership->principalId);
            $this->assertSame(1, $membership->version);
            return DB::connection('pgsql_rls_probe')->table('crm_memberships')->pluck('organization_id')->all();
        });
        $this->assertSame([self::ORG_A], $seen);
        $this->assertSame(0, DB::connection('pgsql_rls_probe')->table('crm_memberships')->count());
    }

    public function test_membership_is_rechecked_after_revocation_without_affecting_other_tenant(): void
    {
        $principal = new PrincipalReference(self::PRINCIPAL);
        $store = $this->store();
        $this->assertSame(1, $store->within($principal, self::ORG_A, fn ($m) => $m->version));
        DB::table('crm_memberships')->where('organization_id', self::ORG_B)->update(['status' => 'active']);
        foreach (['suspended', 'disabled', 'revoked', 'unknown'] as $status) {
            DB::table('crm_memberships')->where('organization_id', self::ORG_A)
                ->update(['status' => $status, 'version' => 2]);
            try {
                $store->within($principal, self::ORG_A, fn () => $this->fail('Revoked membership must not run.'));
                $this->fail('Expected denial.');
            } catch (AuthorizationException) {
                $this->assertSame(0, DB::connection('pgsql_rls_probe')->table('crm_memberships')->count());
            }
            $this->assertSame(self::ORG_B, $store->within($principal, self::ORG_B, fn ($m) => $m->tenantId));
        }
    }

    public function test_another_identity_cannot_use_existing_membership(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->store()->within(new PrincipalReference(self::OTHER_PRINCIPAL), self::ORG_A,
            fn () => $this->fail('Impersonation must not run.'));
    }

    public function test_suspended_tenant_is_not_unlocked_by_active_membership_elsewhere(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_B,
            fn () => $this->fail('Tenant spoofing must not run.'));
    }

    public function test_rollback_clears_transaction_context_and_next_tenant_is_revalidated(): void
    {
        try {
            $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_A,
                function (): void { throw new RuntimeException('Synthetic failure'); });
            $this->fail('Expected synthetic failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic failure', $e->getMessage());
        }
        $this->assertSame(0, DB::connection('pgsql_rls_probe')->transactionLevel());
        $this->assertSame(0, DB::connection('pgsql_rls_probe')->table('crm_memberships')->count());
        $this->expectException(AuthorizationException::class);
        $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_B,
            fn () => $this->fail('Old A context must not authorize B.'));
    }

    public function test_nested_tenant_switch_is_rejected(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        try {
            $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_A, function () use ($probe): void {
                try {
                    $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_B, fn () => null);
                    $this->fail('Nested context switch must fail.');
                } catch (LogicException) {
                    $this->assertSame([self::ORG_A], $probe->table('crm_memberships')->pluck('organization_id')->all());
                }
            });
        } finally {
            $this->assertSame(0, $probe->transactionLevel());
        }
        $this->assertSame(0, $probe->table('crm_memberships')->count());
    }

    public function test_dirty_pooled_connection_is_rejected_instead_of_trusted(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $probe->selectOne("SELECT set_config('app.tenant_id', ?, false)", [self::ORG_B]);
        try {
            $this->expectException(LogicException::class);
            $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_A,
                fn () => $this->fail('Dirty connection must not run.'));
        } finally {
            // Test fixture cleanup; production must discard/quarantine dirty pools.
            $probe->selectOne("SELECT set_config('app.tenant_id', '', false)");
        }
    }

    public function test_privileged_connection_cannot_be_used_as_runtime_adapter(): void
    {
        $this->expectException(LogicException::class);
        (new PostgresActiveMembershipStore(DB::connection()))->within(
            new PrincipalReference(self::PRINCIPAL), self::ORG_A,
            fn () => $this->fail('Superuser must not execute runtime operations.'),
        );
    }

    public function test_real_membership_does_not_grant_a_business_effect(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('getProvider')->andReturn(new EloquentUserProvider($this->app['hash'], GlobalIdentity::class));
        $guard->shouldReceive('user')->once()->andReturn(
            (new GlobalIdentity())->forceFill(['id' => self::PRINCIPAL])
        );
        $boundary = new TenantAccessBoundary(new GuardPrincipalSource($guard, $this->verifiedSession()), $this->store(), new DenyByDefaultAuthorizer());
        try {
            $boundary->execute(self::ORG_A, 'lead', 'read', fn () => $this->fail('Business callback must never run.'));
            $this->fail('Expected deny-by-default.');
        } catch (AuthorizationException) {
            $this->assertSame(0, DB::connection('pgsql_rls_probe')->table('crm_memberships')->count());
        }
    }

    public function test_cross_tenant_update_and_delete_cannot_target_invisible_rows(): void
    {
        $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_A, function (): void {
            $table = DB::connection('pgsql_rls_probe')->table('crm_memberships')->where('organization_id', self::ORG_B);
            $this->assertSame(0, (clone $table)->update(['version' => 99]));
            $this->assertSame(0, (clone $table)->delete());
        });
        $this->assertSame(1, (int) DB::table('crm_memberships')->where('organization_id', self::ORG_B)->value('version'));
    }

    /**
     * SEGDBA-05 regression: one verified human may belong to two tenants,
     * but each unit of work must independently check that exact membership.
     * The read-only laboratory callback is NOT an approved business read.
     */
    public function test_same_principal_with_two_active_memberships_never_merges_tenant_contexts(): void
    {
        DB::table('crm_memberships')->where('organization_id', self::ORG_B)
            ->update(['status' => 'active', 'version' => 3]);

        $principal = new PrincipalReference(self::PRINCIPAL);
        $probe = DB::connection('pgsql_rls_probe');
        $store = $this->store();

        foreach ([[self::ORG_A, 1], [self::ORG_B, 3], [self::ORG_A, 1]] as [$tenantId, $expectedVersion]) {
            $visible = $store->within($principal, $tenantId, function ($membership) use ($tenantId, $probe, $expectedVersion): array {
                $this->assertSame($tenantId, $membership->tenantId);
                $this->assertSame($expectedVersion, $membership->version);
                $this->assertSame(1, $probe->transactionLevel());
                return $probe->table('crm_memberships')->pluck('organization_id')->all();
            });
            $this->assertSame([$tenantId], $visible);
            $this->assertSame(0, $probe->transactionLevel());
            $this->assertSame(0, $probe->table('crm_memberships')->count());
        }

        // Revoking only B must not revoke A or leave an inherited B context.
        DB::table('crm_memberships')->where('organization_id', self::ORG_B)
            ->update(['status' => 'revoked', 'version' => 4]);
        try {
            $store->within($principal, self::ORG_B, fn () => $this->fail('Revoked B callback must not run.'));
            $this->fail('Expected revocation denial for B.');
        } catch (AuthorizationException) {
            $this->assertSame(0, $probe->transactionLevel());
            $this->assertSame(0, $probe->table('crm_memberships')->count());
        }
        $this->assertSame(
            [self::ORG_A],
            $store->within($principal, self::ORG_A, fn () => $probe->table('crm_memberships')->pluck('organization_id')->all()),
        );
        $this->assertSame(0, $probe->table('crm_memberships')->count());
    }

    public function test_two_active_memberships_remain_separate_across_parallel_database_sessions(): void
    {
        DB::table('crm_memberships')->where('organization_id', self::ORG_B)
            ->update(['status' => 'active', 'version' => 2]);

        $first = DB::connection('pgsql_rls_probe');
        $settings = config('database.connections.pgsql_rls_probe');
        config(['database.connections.pgsql_rls_parallel' => $settings]);
        DB::purge('pgsql_rls_parallel');
        $second = DB::connection('pgsql_rls_parallel');
        $principal = new PrincipalReference(self::PRINCIPAL);

        try {
            $outer = new PostgresActiveMembershipStore($first);
            $inner = new PostgresActiveMembershipStore($second);

            $outer->within($principal, self::ORG_A, function () use ($first, $inner, $second, $principal): void {
                $this->assertSame([self::ORG_A], $first->table('crm_memberships')->pluck('organization_id')->all());

                $seenB = $inner->within($principal, self::ORG_B, function ($membership) use ($second): array {
                    $this->assertSame(self::ORG_B, $membership->tenantId);
                    $this->assertSame(1, $second->transactionLevel());
                    return $second->table('crm_memberships')->pluck('organization_id')->all();
                });
                $this->assertSame([self::ORG_B], $seenB);

                $this->assertSame(0, $second->transactionLevel());
                $this->assertSame(0, $second->table('crm_memberships')->count());
                $this->assertSame([self::ORG_A], $first->table('crm_memberships')->pluck('organization_id')->all());

                // Different connections can carry separate local contexts; this
                // does NOT authorize nested switching on the same connection.
                try {
                    (new PostgresActiveMembershipStore($first))->within(
                        $principal, self::ORG_B,
                        fn () => $this->fail('Nested tenant switch on one DB connection must not run.'),
                    );
                    $this->fail('Expected a nested-transaction guard.');
                } catch (LogicException) {
                    $this->assertSame([self::ORG_A], $first->table('crm_memberships')->pluck('organization_id')->all());
                }
            });

            $this->assertSame(0, $first->transactionLevel());
            $this->assertSame(0, $second->transactionLevel());
            $this->assertSame(0, $first->table('crm_memberships')->count());
            $this->assertSame(0, $second->table('crm_memberships')->count());
        } finally {
            DB::disconnect('pgsql_rls_parallel');
            DB::purge('pgsql_rls_parallel');
        }
    }

    public function test_two_active_memberships_never_activate_a_business_allow_policy(): void
    {
        DB::table('crm_memberships')->where('organization_id', self::ORG_B)
            ->update(['status' => 'active', 'version' => 2]);
        $probe = DB::connection('pgsql_rls_probe');

        foreach ([self::ORG_A, self::ORG_B] as $tenantId) {
            $guard = Mockery::mock(Guard::class);
            $guard->shouldReceive('getProvider')->andReturn(
                new EloquentUserProvider($this->app['hash'], GlobalIdentity::class),
            );
            $guard->shouldReceive('user')->once()->andReturn(
                (new GlobalIdentity())->forceFill(['id' => self::PRINCIPAL]),
            );
            $boundary = new TenantAccessBoundary(
                new GuardPrincipalSource($guard, $this->verifiedSession()),
                $this->store(),
                new DenyByDefaultAuthorizer(),
            );

            try {
                $boundary->execute(
                    $tenantId,
                    'lead',
                    'read',
                    fn () => $this->fail('An active membership does not authorize a business callback.'),
                );
                $this->fail('Unapproved business access unexpectedly succeeded.');
            } catch (AuthorizationException) {
                $this->assertSame(0, $probe->transactionLevel());
                $this->assertSame(0, $probe->table('crm_memberships')->count());
            }
        }
    }

    public function test_custom_guc_is_mutable_and_does_not_prove_active_membership(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $status = $probe->transaction(function () use ($probe): string {
            // Deliberate counterexample: the runtime role can choose this marker.
            $probe->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::ORG_B]);
            return $probe->table('crm_memberships')->where('global_identity_id', self::PRINCIPAL)->value('status');
        });
        $this->assertSame('suspended', $status);
        $this->assertSame(0, $probe->table('crm_memberships')->count());
        $this->expectException(AuthorizationException::class);
        $this->store()->within(new PrincipalReference(self::PRINCIPAL), self::ORG_B,
            fn () => $this->fail('A mutable GUC does not establish membership authority.'));
    }

    public function test_missing_or_malformed_tenant_is_denied_without_residual_context(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        foreach (['', 'not-a-uuid', "'; SELECT 1; --"] as $tenant) {
            try {
                $this->store()->within(new PrincipalReference(self::PRINCIPAL), $tenant,
                    fn () => $this->fail('Unvalidated tenant must not run.'));
                $this->fail('Expected denial.');
            } catch (AuthorizationException) {
                $this->assertSame(0, $probe->transactionLevel());
                $this->assertSame(0, $probe->table('crm_memberships')->count());
            }
        }
    }

    /**
     * SEGDBA F3-180: a reusable connection's mutable search_path cannot
     * decide which table supplies a membership snapshot or its metadata.
     * The context setter does not authenticate; authorization stays denied.
     */
    public function test_membership_lookup_is_schema_qualified_under_hostile_search_path(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $before = $probe->selectOne("SELECT current_setting('search_path') AS value");
        $probe->statement('SET search_path TO pg_catalog');

        try {
            $current = $probe->selectOne("SELECT current_setting('search_path') AS value");
            $this->assertSame('pg_catalog', $current->value);
            $snapshot = $this->store()->within(
                new PrincipalReference(self::PRINCIPAL),
                self::ORG_A,
                fn ($membership) => [$membership->tenantId, $membership->principalId, $membership->version],
            );
            $this->assertSame([self::ORG_A, self::PRINCIPAL, 1], $snapshot);

            try {
                $this->store()->within(
                    new PrincipalReference(self::OTHER_PRINCIPAL),
                    self::ORG_A,
                    fn () => $this->fail('Unlinked principal must not execute.'),
                );
                $this->fail('A valid-looking tenant and search_path cannot grant membership.');
            } catch (AuthorizationException) {
                $this->assertSame(0, $probe->transactionLevel());
            }
        } finally {
            $probe->selectOne("SELECT set_config('search_path', ?, false)", [$before->value]);
        }

        $restored = $probe->selectOne("SELECT current_setting('search_path') AS value");
        $this->assertSame($before->value, $restored->value);
        $this->assertSame(0, $probe->transactionLevel());
        $this->assertSame(0, $probe->table('public.crm_memberships')->count());
    }

    /**
     * Reproduces the database name-resolution ambiguity within a disposable
     * session. An attacker-controlled TEMP table must never shadow the real
     * membership registry or trick either metadata or the row lookup.
     *
     * The temporary table is created and dropped ONLY in PostgreSQL CI.
     */
    public function test_temporary_table_shadow_cannot_supply_membership_or_approval(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $tempAllowed = $probe->selectOne(
            "SELECT has_database_privilege(current_user, current_database(), 'TEMPORARY') AS allowed"
        );
        if (!$tempAllowed->allowed) {
            $this->markTestSkipped('Database role has no TEMP privilege; shadow attack cannot be staged.');
        }

        $probe->statement(
            'CREATE TEMP TABLE crm_memberships (
                organization_id uuid, global_identity_id uuid,
                status varchar(16), version integer
            ) ON COMMIT PRESERVE ROWS'
        );

        try {
            $probe->insert(
                "INSERT INTO pg_temp.crm_memberships
                 (organization_id, global_identity_id, status, version)
                 VALUES (?::uuid, ?::uuid, 'active', 999)",
                [self::ORG_A, self::OTHER_PRINCIPAL],
            );
            $resolution = $probe->selectOne(
                "SELECT to_regclass('crm_memberships') <>
                        to_regclass('public.crm_memberships') AS is_shadowed"
            );
            $this->assertTrue($resolution->is_shadowed);

            // The real registry contains the legitimate principal, not the
            // forged active row for the unrelated principal.
            $snapshot = $this->store()->within(
                new PrincipalReference(self::PRINCIPAL), self::ORG_A,
                fn ($membership) => [$membership->principalId, $membership->version],
            );
            $this->assertSame([self::PRINCIPAL, 1], $snapshot);
            try {
                $this->store()->within(
                    new PrincipalReference(self::OTHER_PRINCIPAL),
                    self::ORG_A,
                    fn () => $this->fail('Forged TEMP membership must never execute.'),
                );
                $this->fail('A temporary shadow table cannot grant membership.');
            } catch (AuthorizationException) {
                $this->assertSame(0, $probe->transactionLevel());
            }
        } finally {
            $probe->statement('DROP TABLE IF EXISTS pg_temp.crm_memberships');
        }

        $this->assertSame(0, $probe->transactionLevel());
        $this->assertSame(0, $probe->table('public.crm_memberships')->count());
    }

    public function test_row_security_off_cannot_start_tenant_membership_callback(): void
    {
        $probe = DB::connection('pgsql_rls_probe');
        $original = $probe->selectOne(
            "SELECT current_setting('row_security') AS setting"
        );
        $calls = 0;
        $probe->statement('SET row_security = off');

        try {
            $this->assertSame(
                'off',
                $probe->selectOne(
                    "SELECT current_setting('row_security') AS setting"
                )->setting,
            );
            try {
                $this->store()->within(
                    new PrincipalReference(self::PRINCIPAL),
                    self::ORG_A,
                    function () use (&$calls): void { ++$calls; },
                );
                $this->fail('Disabled RLS must fail before reading membership.');
            } catch (LogicException $e) {
                $this->assertSame('Unsafe tenant connection.', $e->getMessage());
            }

            $this->assertSame(0, $calls);
            $this->assertSame(0, $probe->transactionLevel());
            $this->assertSame(
                'off',
                $probe->selectOne("SELECT current_setting('row_security') AS setting")->setting,
                'A dirty connection must be reset by its owner rather than repaired secretly.',
            );
        } finally {
            $probe->selectOne(
                "SELECT set_config('row_security', ?, false)",
                [$original->setting],
            );
        }

        $this->assertSame(
            1,
            $this->store()->within(
                new PrincipalReference(self::PRINCIPAL),
                self::ORG_A,
                fn ($membership): int => $membership->version,
            ),
        );
        $this->assertSame(0, $probe->transactionLevel());
        $this->assertSame(0, $probe->table('public.crm_memberships')->count());
    }

    private function store(): PostgresActiveMembershipStore
    {
        return new PostgresActiveMembershipStore(DB::connection('pgsql_rls_probe'));
    }

    private function verifiedSession(): \App\Core\Identity\SessionAuthority
    {
        $proof = Mockery::mock(\App\Core\Identity\SessionAuthority::class);
        $proof->shouldReceive('assertCurrent')->with(self::PRINCIPAL)->andReturnNull();
        $proof->shouldReceive('withinCurrent')->with(self::PRINCIPAL, Mockery::type(\Closure::class))
            ->andReturnUsing(fn (string $id, \Closure $operation) => $operation());
        return $proof;
    }
}
