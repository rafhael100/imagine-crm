<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-03: behavioral negative proof that five low-privilege CI roles
 * cannot assume an elevated PostgreSQL identity using SQL session commands.
 *
 * Only the isolated GitHub PG16 lab is eligible. Commands are attempted
 * within transactions and rolled back, never touching objects, privileges,
 * migrations, data or any non-disposable database.
 */
final class PostgresLabRoleImpersonationDenyTest extends TestCase
{
    private const PREFIXES = [
        'RLS_TEST', 'IAM_TEST', 'PROVISION_TEST',
        'ASYNC_TEST', 'ASYNC_AUDIT_TEST',
    ];

    private const DENIED = [
        'SET ROLE postgres',
        'SET ROLE pg_read_all_data',
        'SET ROLE pg_write_all_data',
        'SET SESSION AUTHORIZATION postgres',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('ASYNC_AUDIT_TEST_USERNAME') !== 'crm_async_audit_ci') {
            $this->markTestSkipped('Only the ephemeral PG16 GitHub role matrix may execute session-denial probes.');
        }
    }

    public function test_five_runtime_roles_cannot_switch_to_superuser_or_global_reader_writer(): void
    {
        $attempts = 0;
        foreach (self::PREFIXES as $prefix) {
            $role = getenv($prefix.'_USERNAME');
            $password = getenv($prefix.'_PASSWORD');
            self::assertIsString($role);
            self::assertIsString($password);
            self::assertNotSame('', $role);
            self::assertNotSame('', $password);

            $name = 'crm_impersonation_probe_'.strtolower($prefix);
            $configuration = config('database.connections.pgsql');
            $configuration['url'] = null;
            $configuration['username'] = $role;
            $configuration['password'] = $password;
            config(['database.connections.'.$name => $configuration]);
            DB::purge($name);

            try {
                $connection = DB::connection($name);
                $identity = $connection->selectOne(
                    "SELECT current_user AS effective_user,
                            session_user AS login_user,
                            current_database() AS database_name"
                );
                self::assertNotNull($identity);
                self::assertSame($role, $identity->effective_user);
                self::assertSame($role, $identity->login_user);
                self::assertSame('imagine_crm_ci', $identity->database_name);

                foreach (self::DENIED as $command) {
                    $connection->beginTransaction();
                    try {
                        $connection->statement($command);
                        self::fail('An unprivileged CI runtime identity assumed an elevated role: '.$prefix);
                    } catch (QueryException $error) {
                        self::assertSame(
                            '42501',
                            $error->errorInfo[0] ?? null,
                            'Role escalation must fail with insufficient_privilege: '.$prefix,
                        );
                    } finally {
                        $connection->rollBack();
                    }

                    $after = $connection->selectOne(
                        "SELECT current_user AS effective_user,
                                session_user AS login_user,
                                current_setting('app.tenant_id', true) AS tenant_claim"
                    );
                    self::assertSame($role, $after->effective_user);
                    self::assertSame($role, $after->login_user);
                    self::assertContains($after->tenant_claim, [null, '']);
                    self::assertSame(0, $connection->transactionLevel());
                    ++$attempts;
                }
            } finally {
                DB::purge($name);
            }
        }

        self::assertSame(20, $attempts);
    }
}
