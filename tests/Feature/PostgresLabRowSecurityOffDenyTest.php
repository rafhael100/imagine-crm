<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-05: behavioral test for the PostgreSQL row_security=off safeguard.
 *
 * Unprivileged CI accounts may change the row_security session parameter,
 * but PostgreSQL must ERROR rather than silently expose tenant rows.
 * Disposable PG16 only; no real customer, schema, migration or write.
 */
final class PostgresLabRowSecurityOffDenyTest extends TestCase
{
    /** @var array<string,string> */
    private const SUBJECTS = [
        'RLS_TEST' => 'crm_memberships',
        'ASYNC_TEST' => 'crm_lab_async_ledger',
        'ASYNC_AUDIT_TEST' => 'crm_lab_async_ledger',
    ];

    private const TENANT = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('ASYNC_AUDIT_TEST_USERNAME') !== 'crm_async_audit_ci') {
            $this->markTestSkipped('Only isolated PostgreSQL 16 CI may run row_security probes.');
        }
    }

    public function test_row_security_off_errors_instead_of_bypassing_for_three_nonprivileged_roles(): void
    {
        $denials = 0;
        foreach (self::SUBJECTS as $prefix => $table) {
            $username = getenv($prefix.'_USERNAME');
            $password = getenv($prefix.'_PASSWORD');
            self::assertIsString($username);
            self::assertNotSame('', $username);
            self::assertIsString($password);
            self::assertNotSame('', $password);

            $connectionName = 'crm_rls_off_'.strtolower($prefix);
            $config = config('database.connections.pgsql');
            $config['url'] = null;
            $config['username'] = $username;
            $config['password'] = $password;
            config(['database.connections.'.$connectionName => $config]);
            DB::purge($connectionName);

            try {
                $db = DB::connection($connectionName);
                $role = $db->selectOne(
                    "SELECT current_user AS role_name, current_database() AS db_name,
                            current_setting('row_security') AS rls_setting,
                            r.rolsuper, r.rolbypassrls
                     FROM pg_roles r WHERE r.rolname = current_user"
                );
                self::assertSame($username, $role->role_name);
                self::assertSame('imagine_crm_ci', $role->db_name);
                self::assertSame('on', $role->rls_setting);
                self::assertFalse($role->rolsuper);
                self::assertFalse($role->rolbypassrls);

                $policy = $db->selectOne(
                    "SELECT relrowsecurity, relforcerowsecurity
                     FROM pg_class WHERE oid = ?::regclass",
                    [$table],
                );
                self::assertNotNull($policy);
                self::assertTrue($policy->relrowsecurity);
                self::assertTrue($policy->relforcerowsecurity);

                // The role can submit a syntactically valid session GUC
                // and tenant claim. Neither claim is an authorization.
                $db->beginTransaction();
                try {
                    $db->selectOne(
                        "SELECT set_config('app.tenant_id', ?, true)",
                        [self::TENANT],
                    );
                    $db->statement('SET LOCAL row_security = off');
                    $setting = $db->selectOne(
                        "SELECT current_setting('row_security') AS value"
                    );
                    self::assertSame('off', $setting->value);

                    try {
                        // Table comes exclusively from the hard-coded map,
                        // not user input. All checks are SELECT only.
                        $db->selectOne('SELECT COUNT(*) AS count_rows FROM public.'.$table);
                        self::fail('Unprivileged role silently bypassed RLS: '.$prefix);
                    } catch (QueryException $error) {
                        self::assertSame(
                            '42501',
                            $error->errorInfo[0] ?? null,
                            'row_security=off must fail with insufficient_privilege: '.$prefix,
                        );
                        ++$denials;
                    }
                } finally {
                    $db->rollBack();
                }

                $clean = $db->selectOne(
                    "SELECT current_setting('row_security') AS value,
                            current_setting('app.tenant_id', true) AS tenant,
                            current_user AS role_name"
                );
                self::assertSame('on', $clean->value);
                self::assertContains($clean->tenant, [null, '']);
                self::assertSame($username, $clean->role_name);
                self::assertSame(0, $db->transactionLevel());
            } finally {
                DB::purge($connectionName);
            }
        }

        self::assertSame(3, $denials);
    }
}
