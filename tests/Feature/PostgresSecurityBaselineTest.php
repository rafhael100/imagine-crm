<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Fixtures\PostgresSyntheticLedger;
use Tests\Support\SecurityBaselineChecks as Checks;
use Tests\TestCase;

/** Consolidated catalog/ACL proof. Existing suites retain behavioral tenant/revocation tests. */
final class PostgresSecurityBaselineTest extends TestCase
{
    private const TABLES = ['crm_global_identities', 'crm_organizations', 'crm_memberships', 'crm_auth_credentials',
        'crm_auth_sessions', 'crm_auth_recovery_codes', 'crm_auth_operations', 'crm_auth_mandates', 'crm_iam_audit_events',
        ...PostgresSyntheticLedger::TABLES];

    private function matrix(): array
    {
        $ledger = array_fill_keys(PostgresSyntheticLedger::TABLES, ['SELECT', 'INSERT']);
        return [
            'RLS_TEST' => ['crm_memberships' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE']],
            'IAM_TEST' => ['crm_global_identities' => ['SELECT'], 'crm_auth_credentials' => ['SELECT', 'UPDATE:last_totp_step'],
                'crm_auth_sessions' => ['SELECT', 'INSERT', 'UPDATE'], 'crm_auth_recovery_codes' => ['SELECT', 'UPDATE:consumed_at'],
                'crm_auth_operations' => ['SELECT', 'INSERT', 'UPDATE'], 'crm_iam_audit_events' => ['INSERT']],
            'PROVISION_TEST' => ['crm_global_identities' => ['SELECT', 'INSERT'], 'crm_auth_credentials' => ['SELECT', 'INSERT', 'UPDATE'],
                'crm_auth_recovery_codes' => ['SELECT', 'INSERT'], 'crm_auth_sessions' => ['SELECT', 'UPDATE'],
                'crm_auth_operations' => ['SELECT', 'INSERT', 'UPDATE'], 'crm_auth_mandates' => ['SELECT', 'UPDATE:revoked_at'],
                'crm_iam_audit_events' => ['INSERT']],
            'ASYNC_TEST' => $ledger,
            'ASYNC_AUDIT_TEST' => array_fill_keys(PostgresSyntheticLedger::TABLES, ['SELECT']),
        ];
    }

    public function test_effective_role_and_column_acl_matches_the_existing_closed_laboratory_matrix(): void
    {
        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_AUDIT_TEST_USERNAME')) { $this->markTestSkipped('Requires actual PostgreSQL 16 isolated roles and catalogs.'); }
        $setup = DB::connection();
        $tables = array_map(static fn ($row) => $row->tablename,
            $setup->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE 'crm_%' ORDER BY tablename"));
        $expectedTables = self::TABLES; sort($expectedTables); $this->assertSame($expectedTables, $tables);
        foreach ($this->matrix() as $prefix => $expected) {
            $settings = config('database.connections.pgsql'); $settings['url'] = null;
            $settings['username'] = getenv($prefix.'_USERNAME'); $settings['password'] = getenv($prefix.'_PASSWORD');
            $name = 'crm_baseline_'.strtolower($prefix); config(['database.connections.'.$name => $settings]); DB::purge($name);
            try {
                $role = DB::connection($name);
                $flags = $role->selectOne("SELECT rolsuper, rolbypassrls, rolcreaterole, rolcreatedb FROM pg_roles WHERE rolname = current_user");
                foreach (['rolsuper', 'rolbypassrls', 'rolcreaterole', 'rolcreatedb'] as $flag) { $this->assertFalse($flags->$flag); }
                $actual = [];
                foreach (self::TABLES as $table) {
                    $owner = $role->selectOne("SELECT pg_has_role(current_user, relowner, 'MEMBER') AS owns FROM pg_class WHERE oid = ?::regclass", [$table]);
                    $this->assertFalse($owner->owns);
                    $rights = [];
                    foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'TRUNCATE', 'REFERENCES', 'TRIGGER'] as $privilege) {
                        if ($role->selectOne('SELECT has_table_privilege(current_user, ?, ?) AS allowed', [$table, $privilege])->allowed) { $rights[] = $privilege; }
                    }
                    // Effective column grants matter even when table-wide privilege is absent.
                    foreach (['SELECT', 'INSERT', 'UPDATE', 'REFERENCES'] as $privilege) {
                        if (in_array($privilege, $rights, true)) { continue; }
                        foreach ($setup->getSchemaBuilder()->getColumnListing($table) as $column) {
                            if ($role->selectOne('SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed', [$table, $column, $privilege])->allowed) { $rights[] = $privilege.':'.$column; }
                        }
                    }
                    if ($rights !== []) { $actual[$table] = $rights; }
                }
                $this->assertTrue(Checks::aclMatches($actual, $expected), 'Effective laboratory ACL differs from approved test boundary.');
                $this->assertSame(0, $role->transactionLevel());
                $this->assertContains($role->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, '']);
            } finally { DB::purge($name); }
        }
        foreach (['crm_memberships', ...PostgresSyntheticLedger::TABLES] as $table) {
            $flags = $setup->selectOne('SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE oid = ?::regclass', [$table]);
            $this->assertTrue($flags->relrowsecurity); $this->assertTrue($flags->relforcerowsecurity);
        }
    }

    public function test_acl_verifier_rejects_added_business_and_column_grants_or_missing_restrictions(): void
    {
        $expected = ['crm_lab_async_ledger' => ['SELECT']];
        $this->assertTrue(Checks::aclMatches($expected, $expected));
        $this->assertFalse(Checks::aclMatches($expected + ['crm_memberships' => ['SELECT']], $expected));
        $this->assertFalse(Checks::aclMatches(['crm_lab_async_ledger' => ['SELECT', 'UPDATE:state']], $expected));
        $this->assertFalse(Checks::aclMatches(['crm_lab_async_ledger' => ['SELECT', 'TRIGGER']], $expected));
        $this->assertFalse(Checks::aclMatches([], $expected));
    }
}
