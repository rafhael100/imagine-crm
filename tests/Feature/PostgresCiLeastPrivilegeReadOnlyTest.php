<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA F3-206 — database catalog-only checks against the actual five
 * isolated PostgreSQL 16 CI roles, not a mocked AuthorizationDecision.
 *
 * No CREATE/DROP/ALTER/GRANT/REVOKE/SET ROLE, no data reads, no IAM ALLOW.
 * These assertions are not a security certification for a persistent DB.
 */
final class PostgresCiLeastPrivilegeReadOnlyTest extends TestCase
{
    /** @var array<string,string> */
    private const CI_ROLES = [
        'RLS_TEST_USERNAME' => 'crm_app_ci',
        'IAM_TEST_USERNAME' => 'crm_iam_ci',
        'PROVISION_TEST_USERNAME' => 'crm_provision_ci',
        'ASYNC_TEST_USERNAME' => 'crm_async_ci',
        'ASYNC_AUDIT_TEST_USERNAME' => 'crm_async_audit_ci',
    ];

    /** @var list<string> */
    private const SENSITIVE_SYSTEM_ROLES = [
        'postgres',
        'pg_read_all_data',
        'pg_write_all_data',
        'pg_read_server_files',
        'pg_write_server_files',
        'pg_execute_server_program',
        'pg_signal_backend',
        'pg_monitor',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Only the disposable PostgreSQL CI job can supply catalog evidence.');
        }

        self::assertSame('true', getenv('CI'));
        self::assertSame('true', getenv('GITHUB_ACTIONS'));
        self::assertSame('imagine_crm_ci', DB::connection()->getDatabaseName());

        foreach (self::CI_ROLES as $envName => $expectedRole) {
            self::assertSame($expectedRole, getenv($envName),
                'PostgreSQL CI must use the five explicitly isolated role names.');
        }
    }

    public function test_all_five_runtime_roles_are_not_privileged_and_cannot_create_persistent_objects(): void
    {
        foreach (self::CI_ROLES as $role) {
            // Catalog-only; the administration connection does not perform
            // any business read or become a runtime application connection.
            $facts = DB::selectOne(
                "SELECT rolsuper, rolbypassrls, rolcreatedb, rolcreaterole,
                        rolreplication, rolinherit, rolcanlogin
                 FROM pg_catalog.pg_roles WHERE rolname = ?",
                [$role],
            );
            self::assertNotNull($facts, 'Expected isolated CI role not found: '.$role);
            self::assertFalse($facts->rolsuper, $role.' may not be superuser');
            self::assertFalse($facts->rolbypassrls, $role.' may not bypass RLS');
            self::assertFalse($facts->rolcreatedb, $role.' may not create databases');
            self::assertFalse($facts->rolcreaterole, $role.' may not administer roles');
            self::assertFalse($facts->rolreplication, $role.' may not replicate');
            self::assertFalse($facts->rolinherit, $role.' must have NOINHERIT in this CI');
            self::assertTrue($facts->rolcanlogin, $role.' is an explicit login fixture');

            $scope = DB::selectOne(
                "SELECT has_database_privilege(?::name, current_database(), 'CREATE') AS db_create,
                        has_schema_privilege(?::name, 'public', 'CREATE') AS schema_create",
                [$role, $role],
            );
            self::assertFalse($scope->db_create, $role.' cannot create schemas');
            self::assertFalse($scope->schema_create, $role.' cannot create tables in public');
        }
    }

    public function test_no_runtime_role_inherits_a_superuser_or_sensitive_predefined_postgres_role(): void
    {
        foreach (self::CI_ROLES as $role) {
            foreach (self::SENSITIVE_SYSTEM_ROLES as $sensitive) {
                $result = DB::selectOne(
                    "SELECT pg_has_role(?::name, ?::name, 'MEMBER') AS is_member,
                            pg_has_role(?::name, ?::name, 'SET') AS can_set",
                    [$role, $sensitive, $role, $sensitive],
                );
                self::assertFalse($result->is_member, $role.' must not inherit '.$sensitive);
                self::assertFalse($result->can_set, $role.' must not SET ROLE '.$sensitive);
            }
        }
    }

    public function test_runtime_role_table_grants_remain_separated_between_modules(): void
    {
        $restricted = [
            'crm_app_ci' => [
                'public.crm_global_identities',
                'public.crm_auth_credentials',
                'public.crm_auth_sessions',
                'public.crm_lab_async_ledger',
            ],
            'crm_iam_ci' => [
                'public.crm_memberships',
                'public.crm_lab_async_ledger',
                'public.crm_lab_async_outbox',
            ],
            'crm_provision_ci' => [
                'public.crm_memberships',
                'public.crm_lab_async_ledger',
                'public.crm_lab_async_outbox',
            ],
            'crm_async_ci' => [
                'public.crm_memberships',
                'public.crm_global_identities',
                'public.crm_auth_credentials',
            ],
            'crm_async_audit_ci' => [
                'public.crm_memberships',
                'public.crm_global_identities',
                'public.crm_auth_credentials',
            ],
        ];

        foreach ($restricted as $role => $tables) {
            foreach ($tables as $table) {
                $grants = DB::selectOne(
                    "SELECT has_table_privilege(?::name, ?::text, 'SELECT') AS can_select,
                            has_table_privilege(?::name, ?::text, 'INSERT') AS can_insert,
                            has_table_privilege(?::name, ?::text, 'UPDATE') AS can_update,
                            has_table_privilege(?::name, ?::text, 'DELETE') AS can_delete",
                    [$role, $table, $role, $table, $role, $table, $role, $table],
                );
                self::assertFalse($grants->can_select, $role.' SELECT forbidden: '.$table);
                self::assertFalse($grants->can_insert, $role.' INSERT forbidden: '.$table);
                self::assertFalse($grants->can_update, $role.' UPDATE forbidden: '.$table);
                self::assertFalse($grants->can_delete, $role.' DELETE forbidden: '.$table);
            }
        }

        $expected = DB::selectOne(
            "SELECT has_table_privilege('crm_app_ci', 'public.crm_memberships', 'SELECT') AS membership_read,
                    has_table_privilege('crm_async_ci', 'public.crm_lab_async_ledger', 'INSERT') AS async_insert,
                    has_table_privilege('crm_async_audit_ci', 'public.crm_lab_async_ledger', 'SELECT') AS audit_read",
        );
        self::assertTrue($expected->membership_read);
        self::assertTrue($expected->async_insert);
        self::assertTrue($expected->audit_read);
    }
}
