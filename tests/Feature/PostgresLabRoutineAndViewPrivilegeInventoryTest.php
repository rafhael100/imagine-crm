<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-03/05 — catalog-only detection of additional PostgreSQL privilege
 * surfaces outside ordinary tables: SECURITY DEFINER routines, CRM-named
 * routines, PUBLIC default EXECUTE grants, views and foreign relations.
 *
 * No function/DDL is executed. Applies ONLY to the disposable PG16 CI
 * installation; separate migration/review authorization remains required.
 */
final class PostgresLabRoutineAndViewPrivilegeInventoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('ASYNC_AUDIT_TEST_USERNAME') !== 'crm_async_audit_ci') {
            $this->markTestSkipped('Requires the isolated GitHub PG16 CI role matrix.');
        }
    }

    public function test_no_public_security_definer_routines_exist_in_disposable_ci(): void
    {
        $routines = DB::select(
            "SELECT n.nspname, p.proname, p.prokind, owner.rolname AS owner_name
             FROM pg_proc p
             JOIN pg_namespace n ON n.oid = p.pronamespace
             JOIN pg_roles owner ON owner.oid = p.proowner
             WHERE n.nspname = 'public'
               AND p.prosecdef = true
               AND p.prokind IN ('f', 'p')
             ORDER BY p.proname"
        );

        // Even a benign-looking definer may be executable by PUBLIC by
        // default; any addition demands explicit, separate review.
        self::assertSame([], $routines, 'Unexpected SECURITY DEFINER code in the public schema.');
    }

    public function test_no_unreviewed_crm_routines_exist_in_any_non_system_schema(): void
    {
        $routines = DB::select(
            "SELECT n.nspname, p.proname, p.prosecdef, p.prokind
             FROM pg_proc p
             JOIN pg_namespace n ON n.oid = p.pronamespace
             WHERE n.nspname NOT IN ('pg_catalog', 'information_schema')
               AND left(n.nspname, 3) <> 'pg_'
               AND left(p.proname, 4) = 'crm_'
             ORDER BY n.nspname, p.proname"
        );
        self::assertSame([], $routines, 'The CI baseline must not gain unreviewed CRM stored routines.');

        $defaultPublicExec = DB::select(
            "SELECT owner.rolname AS owner_name, d.defaclnamespace, a.privilege_type
             FROM pg_default_acl d
             JOIN pg_roles owner ON owner.oid = d.defaclrole
             CROSS JOIN LATERAL aclexplode(d.defaclacl) a
             WHERE d.defaclobjtype = 'f'
               AND a.grantee = 0
             ORDER BY owner.rolname, d.defaclnamespace"
        );

        // This checks EXPLICIT default ACL rows. In PostgreSQL, an implicit
        // PUBLIC EXECUTE may exist for future functions even when none are
        // listed here, hence the strict no-unreviewed-CRM-routines assertion.
        self::assertSame([], $defaultPublicExec, 'Explicit PUBLIC function defaults are not permitted.');
    }

    public function test_no_public_grant_exists_on_crm_named_views_or_foreign_tables(): void
    {
        $grants = DB::select(
            "SELECT n.nspname, c.relname, c.relkind, a.privilege_type
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             CROSS JOIN LATERAL aclexplode(
                 COALESCE(c.relacl, acldefault('r'::\"char\", c.relowner))
             ) a
             WHERE n.nspname NOT IN ('pg_catalog', 'information_schema')
               AND left(n.nspname, 3) <> 'pg_'
               AND left(c.relname, 4) = 'crm_'
               AND c.relkind IN ('v', 'm', 'f')
               AND a.grantee = 0
             ORDER BY n.nspname, c.relname, a.privilege_type"
        );
        self::assertSame([], $grants, 'Future CRM views or foreign tables must not be exposed to PUBLIC.');
    }
}
