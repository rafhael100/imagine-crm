<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-05: read-only drift sentinel for the exact PostgreSQL 16 CI RLS
 * policies. It detects new/permissive policies, loss of FORCE RLS and loss
 * of the tenant GUC predicate in the synthetic lab.
 *
 * GUC is mutable and never authenticates a principal or tenant. Passing this
 * test is NOT a security review or a production authorization to read data.
 */
final class PostgresLabRlsPolicyInventoryTest extends TestCase
{
    private const EXPECTED = [
        'crm_lab_async_attempts' => ['lab_tenant_isolation', 'tenant_id'],
        'crm_lab_async_inbox' => ['lab_tenant_isolation', 'tenant_id'],
        'crm_lab_async_ledger' => ['lab_tenant_isolation', 'tenant_id'],
        'crm_lab_async_outbox' => ['lab_tenant_isolation', 'tenant_id'],
        'crm_memberships' => ['crm_memberships_tenant_scope', 'organization_id'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_AUDIT_TEST_USERNAME')) {
            $this->markTestSkipped('Requires PostgreSQL 16 disposable laboratory schema.');
        }
    }

    public function test_only_expected_tenant_scoped_rls_policies_exist_on_crm_tables(): void
    {
        $policies = DB::select(
            "SELECT tablename, policyname, permissive, roles, cmd, qual, with_check
             FROM pg_policies
             WHERE schemaname = 'public' AND left(tablename, 4) = 'crm_'
             ORDER BY tablename, policyname"
        );

        self::assertCount(count(self::EXPECTED), $policies);
        self::assertSame(array_keys(self::EXPECTED),
            array_map(static fn (object $policy): string => $policy->tablename, $policies));

        foreach ($policies as $policy) {
            [$policyName, $scopeField] = self::EXPECTED[$policy->tablename];
            self::assertSame($policyName, $policy->policyname);
            self::assertSame('PERMISSIVE', $policy->permissive);
            self::assertSame('ALL', $policy->cmd);
            self::assertSame('{public}', $policy->roles);

            // The scope predicate must guard BOTH existing rows (USING) and
            // proposed writes (WITH CHECK), including lab async tables.
            foreach ([$policy->qual, $policy->with_check] as $predicate) {
                self::assertIsString($predicate);
                self::assertStringContainsString($scopeField, $predicate);
                self::assertStringContainsString('current_setting(', $predicate);
                self::assertStringContainsString('app.tenant_id', $predicate);
                self::assertStringContainsString('NULLIF(', strtoupper($predicate));
                self::assertStringNotContainsString(' OR ', strtoupper($predicate));
            }
            self::assertSame($policy->qual, $policy->with_check);
        }
    }

    public function test_each_tenant_policy_remains_enabled_and_forced_on_its_table(): void
    {
        foreach (array_keys(self::EXPECTED) as $table) {
            $status = DB::selectOne(
                "SELECT c.relrowsecurity, c.relforcerowsecurity,
                        owner.rolname AS owner_name
                 FROM pg_class c
                 JOIN pg_roles owner ON owner.oid = c.relowner
                 WHERE c.oid = ?::regclass",
                [$table],
            );
            self::assertNotNull($status, $table);
            self::assertTrue($status->relrowsecurity, $table);
            self::assertTrue($status->relforcerowsecurity, $table);
            self::assertSame('postgres', $status->owner_name, $table);
        }
    }
}
