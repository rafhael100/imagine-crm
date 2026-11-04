<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
}
