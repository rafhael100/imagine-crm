<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{AiaPgNonceCandidateLedger, AiaPgHmacLabBridge, AiaReadOnlyMockBoundary};
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
        return (new AiaPgNonceCandidateLedger($connection))->claim(
            $nonce, $tenant, 1794690010,
        );
    }

    public function test_lab_ledger_denies_untrusted_values_before_inserting(): void
    {
        $ledger = new AiaPgNonceCandidateLedger(DB::connection());
        foreach ([
            ['nonce' => 'NOT_SYNTHETIC'],
            ['nonce' => 'SYNTHETIC_A'],
            ['nonce' => str_repeat('SYNTHETIC_X', 100)],
            ['tenant' => 'CUSTOMER_REAL_TENANT'],
            ['tenant' => 'SYNTHETIC_LOWER_case'],
            ['tenant' => str_repeat('SYNTHETIC_X', 100)],
            ['time' => -1],
            ['time' => 4102444801],
        ] as $case) {
            $this->assertFalse($ledger->claim(
                $case['nonce'] ?? 'SYNTHETIC_NONCE_VALID',
                $case['tenant'] ?? 'SYNTHETIC_TENANT_A',
                $case['time'] ?? 1794690010,
            ));
        }
        $this->assertSame(
            0, (int) DB::table('aia_lab_nonce_atomic_probe')->count()
        );
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

    /**
     * The HMAC is intentionally forgeable: it proves LAB-ONLY integration
     * of signature preflight with a shared PostgreSQL nonce claim.
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function signedLabFixture(string $nonce): array
    {
        $body = json_encode([
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref' => 'SYNTHETIC_PG_SIGNED_REQUEST',
            'operation' => 'crm.lead.read_minimal',
            'tenant_ref' => 'SYNTHETIC_TENANT_A',
            'principal_ref' => 'SYNTHETIC_PRINCIPAL',
            'resource_ref' => 'SYNTHETIC_LEAD_A',
            'purpose_ref' => 'SYNTHETIC_TEST',
            'fields' => ['ref', 'stage'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $claims = [
            'issuer' => 'SYNTHETIC_AGENT_SERVICE',
            'audience' => 'SYNTHETIC_CRM_API',
            'tenant_ref' => 'SYNTHETIC_TENANT_A',
            'nonce' => $nonce,
            'issued_at' => 1794690000,
            'expires_at' => 1794690025,
            'body_sha256' => hash('sha256', $body),
        ];
        $claims['signature'] = hash_hmac('sha256', implode("\n", [
            'SYNTHETIC_AIA_HMAC_PROOF_V1', ...array_values($claims),
        ]), 'SYNTHETIC_PUBLIC_TEST_ONLY_PG_BRIDGE_KNOWN_NOT_SECRET_123456789');
        return [$body, $claims];
    }

    public function test_signed_mock_proof_claims_once_across_two_pg_connections(): void
    {
        $key = 'SYNTHETIC_PUBLIC_TEST_ONLY_PG_BRIDGE_KNOWN_NOT_SECRET_123456789';
        [$body, $claims] = self::signedLabFixture('SYNTHETIC_NONCE_SIGNED_A');
        $first = new AiaPgNonceCandidateLedger(DB::connection());
        $second = new AiaPgNonceCandidateLedger(
            DB::connection('aia_nonce_probe_second')
        );

        $this->assertTrue(AiaPgHmacLabBridge::matchesLabFixture(
            $body, $claims, 'SYNTHETIC_TENANT_A', 1794690010, $key, $first
        ));
        $this->assertFalse(AiaPgHmacLabBridge::matchesLabFixture(
            $body, $claims, 'SYNTHETIC_TENANT_A', 1794690010, $key, $second
        ));
        $this->assertSame(
            1, (int) DB::table('aia_lab_nonce_atomic_probe')->count()
        );

        [$bodyB, $proofB] = self::signedLabFixture('SYNTHETIC_NONCE_SIGNED_B');
        $deny = AiaPgHmacLabBridge::inspectLabFixture(
            $bodyB, $proofB, 'SYNTHETIC_TENANT_A', 1794690010, $key, $second
        );
        $this->assertSame(
            AiaReadOnlyMockBoundary::inspectCanonicalJson($bodyB), $deny
        );
        $this->assertSame('DENIED_OR_NOT_FOUND', $deny['state']);
        $this->assertFalse($deny['real_authorization']);
        $this->assertSame(
            2, (int) DB::table('aia_lab_nonce_atomic_probe')->count()
        );
    }

    public function test_invalid_mock_signature_never_claims_pg_nonce(): void
    {
        $key = 'SYNTHETIC_PUBLIC_TEST_ONLY_PG_BRIDGE_KNOWN_NOT_SECRET_123456789';
        [$body, $proof] = self::signedLabFixture('SYNTHETIC_NONCE_SIGNED_C');
        $ledger = new AiaPgNonceCandidateLedger(DB::connection());

        $invalids = [
            'wrong signing key' => [$body, $proof, 1794690010,
                'SYNTHETIC_PUBLIC_TEST_ONLY_DIFFERENT_KEY_KNOWN_123456789'],
            'wrong body' => [str_replace('SYNTHETIC_LEAD_A', 'SYNTHETIC_LEAD_B', $body),
                $proof, 1794690010, $key],
            'expired' => [$body, $proof, 1794690025, $key],
            'not yet issued' => [$body, $proof, 1794689999, $key],
        ];
        $otherTenant = $proof;
        $otherTenant['tenant_ref'] = 'SYNTHETIC_TENANT_B';
        $invalids['spoofed tenant'] = [$body, $otherTenant, 1794690010, $key];
        $spoofedAudience = $proof;
        $spoofedAudience['audience'] = 'SYNTHETIC_OTHER_SERVICE';
        $invalids['spoofed audience'] = [$body, $spoofedAudience, 1794690010, $key];
        $dup = substr($body, 0, -1)
            . ',"tenant_ref":"SYNTHETIC_TENANT_A"}';
        $invalids['duplicate key'] = [$dup, $proof, 1794690010, $key];

        foreach ($invalids as $label => [$candidateBody, $candidateProof, $time, $signedKey]) {
            $this->assertFalse(
                AiaPgHmacLabBridge::matchesLabFixture(
                    $candidateBody, $candidateProof, 'SYNTHETIC_TENANT_A',
                    $time, $signedKey, $ledger,
                ),
                $label,
            );
        }
        $this->assertCount(7, $invalids);
        $this->assertSame(
            0, (int) DB::table('aia_lab_nonce_atomic_probe')->count()
        );
    }

    public function test_signed_lab_proof_fails_closed_when_pg_backend_table_absent(): void
    {
        $key = 'SYNTHETIC_PUBLIC_TEST_ONLY_PG_BRIDGE_KNOWN_NOT_SECRET_123456789';
        [$body, $proof] = self::signedLabFixture('SYNTHETIC_NONCE_SIGNED_D');
        $ledger = new AiaPgNonceCandidateLedger(
            DB::connection('aia_nonce_probe_second')
        );
        DB::statement('DROP TABLE '.self::TABLE);
        $this->assertFalse(AiaPgHmacLabBridge::matchesLabFixture(
            $body, $proof, 'SYNTHETIC_TENANT_A', 1794690010, $key, $ledger
        ));
        $this->assertSame(
            AiaReadOnlyMockBoundary::inspectCanonicalJson($body),
            AiaPgHmacLabBridge::inspectLabFixture(
                $body, $proof, 'SYNTHETIC_TENANT_A',
                1794690010, $key, $ledger,
            ),
        );
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

    /**
     * F3-AIA parallel contention: two *simultaneously active* PostgreSQL
     * sessions attempt the same nonce. The first transaction remains open
     * until the second is confirmed waiting on a real PostgreSQL lock.
     *
     * This deliberately proves a database primitive, never operational IAM.
     */
    private function raceCandidate(bool $commitFirst): void
    {
        if (!function_exists('pg_connect') || !function_exists('pg_send_query_params')) {
            throw new RuntimeException('POSTGRES_ASYNC_EXTENSION_REQUIRED');
        }

        // Public CI-only credentials, reachable only on the ephemeral runner.
        // The setUp() DB, repository, runner and user guards run before this.
        $dsn = 'host=127.0.0.1 port=5432 dbname=imagine_crm_ci '
            . 'user=postgres password=ci-only-password connect_timeout=2';
        $first = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $second = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        if ($first === false || $second === false) {
            throw new RuntimeException('EPHEMERAL_CONNECTION_DENIED');
        }
        $firstOpen = false;
        try {
            $firstPidResult = pg_query($first, 'SELECT pg_backend_pid() AS pid');
            $secondPidResult = pg_query($second, 'SELECT pg_backend_pid() AS pid');
            if ($firstPidResult === false || $secondPidResult === false) {
                throw new RuntimeException('SESSION_PID_UNAVAILABLE');
            }
            $firstPid = (int) pg_fetch_result($firstPidResult, 0, 'pid');
            $secondPid = (int) pg_fetch_result($secondPidResult, 0, 'pid');
            $this->assertNotSame($firstPid, $secondPid);

            if (pg_query($first, 'BEGIN') === false) {
                throw new RuntimeException('FIRST_TRANSACTION_UNAVAILABLE');
            }
            $firstOpen = true;
            $query = 'INSERT INTO '.self::TABLE.' (
                issuer,audience,nonce,tenant_ref,claimed_at
                ) VALUES ($1,$2,$3,$4,$5)
                ON CONFLICT (issuer,audience,nonce) DO NOTHING
                RETURNING nonce';
            $nonce = $commitFirst
                ? 'SYNTHETIC_NONCE_CONCURRENT_COMMIT'
                : 'SYNTHETIC_NONCE_CONCURRENT_ROLLBACK';
            $args = [
                'SYNTHETIC_AGENT_SERVICE', 'SYNTHETIC_CRM_API',
                $nonce, 'SYNTHETIC_TENANT_A', '1794690010',
            ];
            $firstResult = pg_query_params($first, $query, $args);
            if ($firstResult === false) {
                throw new RuntimeException('FIRST_NONCE_INSERT_UNAVAILABLE');
            }
            $this->assertSame(1, pg_num_rows($firstResult));

            // Dispatch an actual overlapping query on the OTHER connection.
            // It must wait on the first transaction's uncommitted unique key.
            if (pg_query($second, "SET lock_timeout = '6000ms'") === false
                || pg_query($second, "SET statement_timeout = '7000ms'") === false
                || !pg_send_query_params($second, $query, [
                    'SYNTHETIC_AGENT_SERVICE', 'SYNTHETIC_CRM_API',
                    $nonce, 'SYNTHETIC_TENANT_B', '1794690011',
                ])) {
                throw new RuntimeException('PARALLEL_NONCE_DISPATCH_DENIED');
            }
            $waitObserved = false;
            $deadline = microtime(true) + 4.0;
            do {
                $state = DB::selectOne(
                    'SELECT wait_event_type, wait_event
                     FROM pg_stat_activity WHERE pid = ?',
                    [$secondPid],
                );
                if ($state !== null && $state->wait_event_type === 'Lock') {
                    $waitObserved = true;
                    break;
                }
                usleep(25_000);
            } while (microtime(true) < $deadline);
            $this->assertTrue(
                $waitObserved,
                'Second PostgreSQL session never reached lock contention.',
            );

            // Only NOW release the first transaction, and inspect the
            // concurrently issued second query's actual database result.
            if (pg_query($first, $commitFirst ? 'COMMIT' : 'ROLLBACK') === false) {
                throw new RuntimeException('FIRST_TRANSACTION_CLOSE_FAILED');
            }
            $firstOpen = false;
            $secondResult = pg_get_result($second);
            if ($secondResult === false) {
                throw new RuntimeException('SECOND_NONCE_RESULT_MISSING');
            }
            $this->assertSame(PGSQL_TUPLES_OK, pg_result_status($secondResult));
            $this->assertSame($commitFirst ? 0 : 1, pg_num_rows($secondResult));
            $this->assertFalse(pg_get_result($second));

            $saved = DB::table('aia_lab_nonce_atomic_probe')
                ->where('nonce', $nonce)->get(['tenant_ref']);
            $this->assertCount(1, $saved);
            $this->assertSame(
                $commitFirst ? 'SYNTHETIC_TENANT_A' : 'SYNTHETIC_TENANT_B',
                $saved[0]->tenant_ref,
            );
        } finally {
            // A failed assertion must not leave a hanging lock or pooled
            // connection. The ephemeral runner owns both connections.
            if ($firstOpen) {
                pg_query($first, 'ROLLBACK');
            }
            pg_close($second);
            pg_close($first);
        }
    }

    public function test_concurrent_insert_only_one_commits_across_two_live_sessions(): void
    {
        $this->raceCandidate(true);
    }

    public function test_concurrent_insert_can_recover_after_first_transaction_rolls_back(): void
    {
        $this->raceCandidate(false);
    }

    public function test_unavailable_backend_fails_closed_in_candidate_claim(): void
    {
        $two = DB::connection('aia_nonce_probe_second');
        DB::connection()->statement('DROP TABLE '.self::TABLE);
        $this->assertFalse($this->claim($two, 'SYNTHETIC_NONCE_OUTAGE'));
        $this->assertSame(0, DB::connection()->transactionLevel());
    }
}
