<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-03: detect indirect privilege inheritance / SET ROLE and
 * future-object PUBLIC grants in disposable PostgreSQL 16 CI.
 *
 * These are read-only catalog queries against fabricated CI roles.
 * This does NOT audit any permanent database or authorize a business read.
 */
final class PostgresLabIndirectPrivilegesTest extends TestCase
{
    private const PREFIXES = [
        'RLS_TEST', 'IAM_TEST', 'PROVISION_TEST',
        'ASYNC_TEST', 'ASYNC_AUDIT_TEST',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_AUDIT_TEST_USERNAME')) {
            $this->markTestSkipped('Requires the disposable PostgreSQL CI role matrix.');
        }
    }

    /** @return list<string> */
    private function runtimeRoles(): array
    {
        $names = [];
        foreach (self::PREFIXES as $prefix) {
            $name = getenv($prefix.'_USERNAME');
            self::assertIsString($name);
            self::assertNotSame('', $name);
            $names[] = $name;
        }
        self::assertCount(5, array_unique($names));

        return $names;
    }

    public function test_five_runtime_roles_are_not_members_of_any_other_role(): void
    {
        $roles = $this->runtimeRoles();
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $memberships = DB::select(
            "SELECT member_role.rolname AS member_name,
                    granted_role.rolname AS granted_role_name,
                    m.admin_option, m.inherit_option, m.set_option
             FROM pg_auth_members m
             JOIN pg_roles member_role ON member_role.oid = m.member
             JOIN pg_roles granted_role ON granted_role.oid = m.roleid
             WHERE member_role.rolname IN ({$placeholders})
             ORDER BY member_role.rolname, granted_role.rolname",
            $roles,
        );

        // NOINHERIT does not deny SET ROLE: an unexpected membership
        // may remain an escalation path. Reject every direct membership.
        self::assertSame([], $memberships);
    }

    public function test_default_acl_does_not_pregrant_future_crm_tables_or_sequences_to_public(): void
    {
        $grants = DB::select(
            "SELECT owner_role.rolname AS owner_name, d.defaclobjtype,
                    d.defaclnamespace, acl.privilege_type
             FROM pg_default_acl d
             JOIN pg_roles owner_role ON owner_role.oid = d.defaclrole
             CROSS JOIN LATERAL aclexplode(d.defaclacl) acl
             WHERE d.defaclobjtype IN ('r', 'S')
               AND acl.grantee = 0
             ORDER BY owner_role.rolname, d.defaclobjtype, d.defaclnamespace"
        );

        // Explicit ALTER DEFAULT PRIVILEGES could make a future table
        // publicly readable even if present-day table ACLs are clean.
        self::assertSame([], $grants);
    }

    public function test_runtime_roles_have_no_persistent_database_or_role_configuration_overrides(): void
    {
        $roles = $this->runtimeRoles();
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $overrides = DB::select(
            "SELECT role.rolname AS role_name, s.setdatabase, s.setconfig
             FROM pg_db_role_setting s
             JOIN pg_roles role ON role.oid = s.setrole
             WHERE role.rolname IN ({$placeholders})
             ORDER BY role.rolname",
            $roles,
        );

        // Disallow a silent ALTER ROLE ... SET search_path/row_security,
        // which could change how a pooled connection is interpreted.
        self::assertSame([], $overrides);
    }
}
