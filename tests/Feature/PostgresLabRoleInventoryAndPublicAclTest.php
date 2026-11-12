<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-03/05: the GitHub PostgreSQL 16 laboratory must not silently gain a
 * sixth runtime identity or a PUBLIC ACL for CRM objects.
 *
 * Catalog queries are read-only. This does not inspect an actual VPS, confer
 * authorization or replace the separate independent SEG/DBA review.
 */
final class PostgresLabRoleInventoryAndPublicAclTest extends TestCase
{
    private const PREFIXES = [
        'RLS_TEST', 'IAM_TEST', 'PROVISION_TEST',
        'ASYNC_TEST', 'ASYNC_AUDIT_TEST',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_AUDIT_TEST_USERNAME')) {
            $this->markTestSkipped('Requires the disposable GitHub PostgreSQL role matrix.');
        }
    }

    public function test_only_declared_nonprivileged_runtime_logins_exist_in_disposable_ci(): void
    {
        $expected = ['postgres'];
        foreach (self::PREFIXES as $prefix) {
            $username = getenv($prefix.'_USERNAME');
            self::assertIsString($username);
            self::assertNotSame('', $username);
            $expected[] = $username;
        }
        sort($expected);

        $rows = DB::select(
            'SELECT rolname, rolreplication, rolinherit, rolsuper,
                    rolbypassrls, rolcreatedb, rolcreaterole
             FROM pg_roles WHERE rolcanlogin ORDER BY rolname'
        );
        $actual = array_map(static fn (object $row): string => $row->rolname, $rows);

        // Exact enumeration catches new login roles omitted from the existing
        // five-role privilege matrix; "postgres" only migrates disposable CI.
        self::assertSame($expected, $actual);
        foreach ($rows as $row) {
            if ($row->rolname === 'postgres') {
                continue;
            }
            foreach (['rolreplication', 'rolinherit', 'rolsuper',
                'rolbypassrls', 'rolcreatedb', 'rolcreaterole'] as $privilege) {
                self::assertFalse(
                    $row->$privilege,
                    'Unexpected PostgreSQL runtime role capability: '.$row->rolname.'/'.$privilege,
                );
            }
        }
    }

    public function test_public_has_no_create_authority_on_database_or_public_schema(): void
    {
        $databaseAcl = DB::select(
            "SELECT privilege_type
             FROM pg_database d
             CROSS JOIN LATERAL aclexplode(
                 COALESCE(d.datacl, acldefault('d', d.datdba))
             ) acl
             WHERE d.datname = current_database()
               AND acl.grantee = 0
               AND acl.privilege_type = 'CREATE'"
        );
        self::assertSame([], $databaseAcl, 'PUBLIC cannot create schemas in the CI database.');

        $schemaAcl = DB::select(
            "SELECT privilege_type
             FROM pg_namespace n
             CROSS JOIN LATERAL aclexplode(
                 COALESCE(n.nspacl, acldefault('n', n.nspowner))
             ) acl
             WHERE n.nspname = 'public'
               AND acl.grantee = 0
               AND acl.privilege_type = 'CREATE'"
        );
        self::assertSame([], $schemaAcl, 'PUBLIC cannot create CRM tables in the public schema.');
    }

    public function test_public_has_no_table_or_sequence_privileges_on_crm_objects(): void
    {
        // PostgreSQL encodes the PUBLIC grantee as zero in its ACL arrays.
        // acldefault() resolves implicit grants as well as explicit relacl.
        $publicGrants = DB::select(
            "SELECT c.relname, c.relkind, acl.privilege_type
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             CROSS JOIN LATERAL aclexplode(
                 COALESCE(c.relacl,
                     acldefault(
                         (CASE WHEN c.relkind = 'S' THEN 'S' ELSE 'r' END)::\"char\",
                         c.relowner
                     )
                 )
             ) acl
             WHERE n.nspname = 'public'
               AND c.relname LIKE 'crm\\_%' ESCAPE '\\'
               AND c.relkind IN ('r', 'p', 'S')
               AND acl.grantee = 0
             ORDER BY c.relname, acl.privilege_type"
        );
        self::assertSame([], $publicGrants, 'No CRM table or sequence may grant access to PUBLIC.');

        $inventory = DB::selectOne(
            "SELECT COUNT(*) AS count_objects
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = 'public'
               AND c.relname LIKE 'crm\\_%' ESCAPE '\\'
               AND c.relkind IN ('r', 'p', 'S')"
        );
        self::assertNotNull($inventory);
        self::assertGreaterThan(0, (int) $inventory->count_objects);
    }
}
