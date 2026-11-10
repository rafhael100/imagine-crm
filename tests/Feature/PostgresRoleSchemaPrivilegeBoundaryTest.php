<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-03/P1: read-only privilege proof using ONLY the isolated PostgreSQL
 * 16 GitHub CI service and its five fabricated non-bypass roles.
 *
 * These assertions do not replace independent review of a real environment
 * and never issue DDL, GRANT, SET ROLE or business operations.
 */
final class PostgresRoleSchemaPrivilegeBoundaryTest extends TestCase
{
    private const ROLE_PREFIXES = [
        'RLS_TEST', 'IAM_TEST', 'PROVISION_TEST',
        'ASYNC_TEST', 'ASYNC_AUDIT_TEST',
    ];

    private const SENSITIVE_ROLES = [
        'postgres',
        'pg_database_owner',
        'pg_read_all_data',
        'pg_write_all_data',
        'pg_read_server_files',
        'pg_write_server_files',
        'pg_execute_server_program',
        'pg_signal_backend',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_AUDIT_TEST_USERNAME')) {
            $this->markTestSkipped('Requires the disposable GitHub PostgreSQL role matrix.');
        }
    }

    public function test_every_lab_role_lacks_schema_and_database_creation_authority(): void
    {
        foreach (self::ROLE_PREFIXES as $prefix) {
            $name = 'crm_schema_privilege_'.strtolower($prefix);
            $config = config('database.connections.pgsql');
            $config['url'] = null;
            $config['username'] = getenv($prefix.'_USERNAME');
            $config['password'] = getenv($prefix.'_PASSWORD');
            config(['database.connections.'.$name => $config]);
            DB::purge($name);

            try {
                $connection = DB::connection($name);
                $actual = $connection->selectOne(
                    "SELECT current_database() AS db_name, current_user AS role_name,
                        has_database_privilege(current_user, current_database(), 'CREATE') AS can_create_schema,
                        has_schema_privilege(current_user, 'public', 'CREATE') AS can_create_table,
                        has_schema_privilege(current_user, 'public', 'USAGE') AS can_use_schema,
                        rolsuper, rolcreatedb, rolcreaterole, rolbypassrls
                     FROM pg_roles WHERE rolname = current_user"
                );

                $this->assertNotNull($actual);
                $this->assertSame('imagine_crm_ci', $actual->db_name);
                $this->assertSame(getenv($prefix.'_USERNAME'), $actual->role_name);
                $this->assertTrue($actual->can_use_schema);
                foreach ([
                    'can_create_schema', 'can_create_table', 'rolsuper',
                    'rolcreatedb', 'rolcreaterole', 'rolbypassrls',
                ] as $capability) {
                    $this->assertFalse(
                        $actual->$capability,
                        'A synthetic runtime role has an unsafe database privilege: '.$prefix.'/'.$capability,
                    );
                }
                $this->assertSame(0, $connection->transactionLevel());
            } finally {
                DB::purge($name);
            }
        }
    }

    public function test_no_lab_role_can_inherit_or_set_an_elevated_builtin_role(): void
    {
        foreach (self::ROLE_PREFIXES as $prefix) {
            $name = 'crm_role_inheritance_'.strtolower($prefix);
            $config = config('database.connections.pgsql');
            $config['url'] = null;
            $config['username'] = getenv($prefix.'_USERNAME');
            $config['password'] = getenv($prefix.'_PASSWORD');
            config(['database.connections.'.$name => $config]);
            DB::purge($name);

            try {
                $db = DB::connection($name);
                foreach (self::SENSITIVE_ROLES as $privilegedRole) {
                    $result = $db->selectOne(
                        "SELECT pg_has_role(current_user, ?::name, 'MEMBER') AS is_member,
                                pg_has_role(current_user, ?::name, 'USAGE') AS inherits_role",
                        [$privilegedRole, $privilegedRole],
                    );
                    $this->assertFalse($result->is_member, 'Synthetic role is a member of '.$privilegedRole);
                    $this->assertFalse($result->inherits_role, 'Synthetic role inherits '.$privilegedRole);
                }
                $this->assertSame(0, $db->transactionLevel());
            } finally {
                DB::purge($name);
            }
        }
    }
}
