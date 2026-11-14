<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * F3-AIA: test ONLY a disposable PostgreSQL-16 UNIQUE nonce-claim candidate.
 *
 * This fixture is never a production service, API, IAM, ACL, migration,
 * credential store, or deployed schema. It changes only the ephemeral CI DB.
 */
final class AiaPostgresAtomicNonceCandidateTest extends TestCase
{
    private const TABLE = 'public.aia_lab_nonce_atomic_probe';
    private bool $created = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Only the disposable PostgreSQL integration job may run this test.');
        }
        // Prevent accidental invocation against an arbitrary or real DB.
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci') {
            throw new RuntimeException('NON_DISPOSABLE_DATABASE_DENIED');
        }
        $who = DB::selectOne('SELECT current_database() AS dbname, current_user AS username');
        if ($who === null || $who->dbname !== 'imagine_crm_ci'
            || $who->username !== 'postgres') {
            throw new RuntimeException('DATABASE_IDENTITY_DENIED');
        }

        DB::statement('CREATE TABLE '.self::TABLE.' (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            nonce varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            claimed_at bigint NOT NULL,
            PRIMARY KEY (issuer,audience,nonce)
        )');
        $this->created = true;

        $settings = config('database.connections.pgsql');
        $settings['url'] = null;
        config(['database.connections.aia_nonce_probe_second' => $settings]);
        DB::purge('aia_nonce_probe_second');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->created) {
                $one = DB::connection();
                if ($one->transactionLevel() > 0) {
                    $one->rollBack();
                }
                $one->statement('DROP TABLE IF EXISTS '.self::TABLE);
                DB::disconnect('aia_nonce_probe_second');
                DB::purge('aia_nonce_probe_second');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function claim(
        Connection $connection,
        string $nonce,
        string $tenant = 'SYNTHETIC_TENANT_A',
    ): bool {
        try {
            return $connection->affectingStatement(
                'INSERT INTO '.self::TABLE.' (
                    issuer,audience,nonce,tenant_ref,claimed_at
                 ) VALUES (?,?,?,?,?)
                 ON CONFLICT (issuer,audience,nonce) DO NOTHING',
                [
                    'SYNTHETIC_AGENT_SERVICE', 'SYNTHETIC_CRM_API',
                    $nonce, $tenant, 1794690010,
                ],
            ) === 1;
        } catch (\Throwable) {
            // A broken or unavailable shared backend cannot be treated as
            // evidence that the nonce is unused.
            return false;
        }
    }

    public function test_unique_claim_is_observed_by_two_independent_connections(): void
    {
        $one = DB::connection();
        $two = DB::connection('aia_nonce_probe_second');
        $firstPid = (int) $one->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $secondPid = (int) $two->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $this->assertNotSame($firstPid, $secondPid);

        $this->assertTrue($this->claim($one, 'SYNTHETIC_NONCE_A'));
        $this->assertFalse($this->claim($two, 'SYNTHETIC_NONCE_A'));
        // Changing the alleged tenant cannot reuse the issuer/audience nonce.
        $this->assertFalse($this->claim(
            $two, 'SYNTHETIC_NONCE_A', 'SYNTHETIC_TENANT_B'
        ));
        $this->assertTrue($this->claim($two, 'SYNTHETIC_NONCE_B'));
        $this->assertFalse($this->claim($one, 'SYNTHETIC_NONCE_B'));
        $this->assertSame(2, (int) $one->table('aia_lab_nonce_atomic_probe')->count());
    }

    public function test_rollback_is_not_falsely_registered_as_consumed(): void
    {
        $one = DB::connection();
        $two = DB::connection('aia_nonce_probe_second');
        $one->beginTransaction();
        try {
            $this->assertTrue($this->claim($one, 'SYNTHETIC_NONCE_ROLLBACK'));
        } finally {
            $one->rollBack();
        }
        $this->assertSame(0, $one->transactionLevel());
        $this->assertTrue($this->claim($two, 'SYNTHETIC_NONCE_ROLLBACK'));
        $this->assertFalse($this->claim($one, 'SYNTHETIC_NONCE_ROLLBACK'));
        $this->assertSame(1, (int) $one->table('aia_lab_nonce_atomic_probe')->count());
    }

    public function test_unique_key_is_database_enforced_and_not_deferrable(): void
    {
        $constraint = DB::selectOne(
            "SELECT contype,
                    CASE WHEN condeferrable THEN 1 ELSE 0 END AS is_deferred,
                    pg_get_constraintdef(oid) AS definition
             FROM pg_constraint
             WHERE conrelid = 'public.aia_lab_nonce_atomic_probe'::regclass
               AND contype = 'p'"
        );
        $this->assertNotNull($constraint);
        $this->assertSame('p', $constraint->contype);
        $this->assertSame(0, (int) $constraint->is_deferred);
        $this->assertStringContainsString(
            'PRIMARY KEY (issuer, audience, nonce)', $constraint->definition
        );
    }

    public function test_unavailable_backend_fails_closed_in_candidate_claim(): void
    {
        $two = DB::connection('aia_nonce_probe_second');
        DB::connection()->statement('DROP TABLE '.self::TABLE);
        $this->assertFalse($this->claim($two, 'SYNTHETIC_NONCE_OUTAGE'));
        $this->assertSame(0, DB::connection()->transactionLevel());
    }
}
