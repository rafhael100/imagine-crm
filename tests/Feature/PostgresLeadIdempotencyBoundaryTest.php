<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadReceiptMacInspector;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CI-only PHYSICAL constraints probe using the EXISTING inert async ledger.
 *
 * No lead-specific storage, API, authority, migration, credential, grant,
 * real tenant or persistent deployment is introduced. In particular,
 * a role-set app.tenant_id GUC is not server-verified authentication.
 */
final class PostgresLeadIdempotencyBoundaryTest extends TestCase
{
    private const LEDGER = 'crm_lab_async_ledger';

    // Isolated from the existing C/D IAM and async ledger fixture tenants.
    private const A = '10101010-1010-4010-8010-101010101010';
    private const B = '20202020-2020-4020-8020-202020202020';
    private const KEY = '30303030-3030-4030-8030-303030303030';
    private const OTHER_KEY = '40404040-4040-4040-8040-404040404040';

    private Connection $primary;
    private Connection $contingency;
    private Connection $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('ASYNC_TEST_USERNAME') !== 'crm_async_ci'
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('Only disposable GitHub Actions PostgreSQL 16 async ledger laboratory.');
        }

        $this->fixture = DB::connection();
        $this->clearLab();

        // Two distinct PostgreSQL connections using the existing restricted,
        // NO BYPASSRLS/NO UPDATE/DELETE laboratory role.
        $settings = $pg;
        $settings['url'] = null;
        $settings['username'] = getenv('ASYNC_TEST_USERNAME');
        $settings['password'] = getenv('ASYNC_TEST_PASSWORD');

        config(['database.connections.lead_receiver_primary_lab' => $settings]);
        config(['database.connections.lead_receiver_contingency_lab' => $settings]);
        DB::purge('lead_receiver_primary_lab');
        DB::purge('lead_receiver_contingency_lab');
        $this->primary = DB::connection('lead_receiver_primary_lab');
        $this->contingency = DB::connection('lead_receiver_contingency_lab');
        $this->contingency->statement("SET lock_timeout = '250ms'");

        foreach ([$this->primary, $this->contingency] as $connection) {
            $role = $connection->selectOne("SELECT rolsuper, rolbypassrls
                FROM pg_roles WHERE rolname = current_user");
            $this->assertNotNull($role);
            $this->assertFalse((bool) $role->rolsuper);
            $this->assertFalse((bool) $role->rolbypassrls);
            $table = $connection->selectOne("SELECT relrowsecurity, relforcerowsecurity
                FROM pg_class WHERE oid = 'crm_lab_async_ledger'::regclass");
            $this->assertTrue((bool) $table->relrowsecurity);
            $this->assertTrue((bool) $table->relforcerowsecurity);
            foreach (['UPDATE', 'DELETE', 'TRUNCATE'] as $privilege) {
                $this->assertFalse((bool) $connection->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    [self::LEDGER, $privilege],
                )->allowed);
            }
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->primary)) {
            foreach ([$this->primary, $this->contingency] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->clearLab();
            DB::purge('lead_receiver_primary_lab');
            DB::purge('lead_receiver_contingency_lab');
        }
        parent::tearDown();
    }

    private function clearLab(): void
    {
        // Scoped cleanup only in the disposable CI service. We do not create
        // or drop schemas, change roles, execute production migrations or
        // touch data outside two fixed lab tenant IDs.
        $this->fixture->table(self::LEDGER)
            ->whereIn('tenant_id', [self::A, self::B])
            ->delete();
    }

    private function begin(Connection $db, string $tenant): void
    {
        $db->beginTransaction();
        $db->selectOne("SELECT set_config('app.tenant_id', ?, true)", [$tenant]);
    }

    private function row(string $tenant = self::A, string $key = self::KEY): array
    {
        $created = time();
        return [
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant,
            'operation_id' => (string) Str::uuid(),
            'idempotency_key' => $key,
            'revision' => 1,
            'scope_kind' => 'tenant',
            'scope_id' => $tenant,
            'policy_version' => 'lab-async-deny-v1',
            'digest' => hash('sha256', 'synthetic-lead-receiver-fixture-no-pii'),
            'state' => 'registered_for_test',
            'created_at' => $created,
            'expires_at' => $created + 86400,
        ];
    }

    public function test_two_real_connections_contend_on_uncommitted_unique_key_then_replay(): void
    {
        $this->begin($this->primary, self::A);
        $this->primary->table(self::LEDGER)->insert($this->row());
        $this->begin($this->contingency, self::A);

        try {
            $this->contingency->table(self::LEDGER)->insertOrIgnore($this->row());
            $this->fail('The second uncommitted physical insert must not proceed.');
        } catch (QueryException $e) {
            $this->assertSame('55P03', $e->errorInfo[0] ?? null);
        } finally {
            $this->contingency->rollBack();
        }

        // The winner is decided by the physical unique constraint, not PHP.
        $this->primary->commit();
        $this->begin($this->contingency, self::A);
        $this->assertSame(0, $this->contingency->table(self::LEDGER)->insertOrIgnore($this->row()));
        $this->assertSame(1, $this->contingency->table(self::LEDGER)->count());
        $this->contingency->commit();

        // No session-scoped tenant leaks out of the transaction.
        $this->assertSame(0, $this->contingency->table(self::LEDGER)->count());
        $this->assertSame(1, $this->fixture->table(self::LEDGER)->where('tenant_id', self::A)->count());
    }

    public function test_rollback_of_competing_receiver_allows_one_following_insert(): void
    {
        $this->begin($this->primary, self::A);
        $this->primary->table(self::LEDGER)->insert($this->row());

        $this->begin($this->contingency, self::A);
        try {
            $this->contingency->table(self::LEDGER)->insertOrIgnore($this->row());
            $this->fail('Must block behind an uncommitted competing row.');
        } catch (QueryException $e) {
            $this->assertSame('55P03', $e->errorInfo[0] ?? null);
        } finally {
            $this->contingency->rollBack();
        }

        $this->primary->rollBack();
        $this->begin($this->contingency, self::A);
        $this->assertSame(1, $this->contingency->table(self::LEDGER)->insertOrIgnore($this->row()));
        $this->contingency->commit();
        $this->assertSame(1, $this->fixture->table(self::LEDGER)->where('tenant_id', self::A)->count());
    }

    public function test_identical_submission_keys_are_independent_across_tenants_under_rls(): void
    {
        $this->begin($this->primary, self::A);
        $this->primary->table(self::LEDGER)->insert($this->row(self::A, self::KEY));

        $this->begin($this->contingency, self::B);
        $this->assertSame(1, $this->contingency->table(self::LEDGER)->insertOrIgnore(
            $this->row(self::B, self::KEY),
        ));
        $this->contingency->commit();
        $this->primary->commit();

        $this->begin($this->primary, self::A);
        $this->assertSame([self::A], $this->primary->table(self::LEDGER)->pluck('tenant_id')->all());
        $this->primary->commit();

        $this->begin($this->contingency, self::B);
        $this->assertSame([self::B], $this->contingency->table(self::LEDGER)->pluck('tenant_id')->all());
        $this->contingency->commit();

        $this->assertSame(2, $this->fixture->table(self::LEDGER)
            ->whereIn('tenant_id', [self::A, self::B])->count());
    }

    public function test_cross_tenant_insert_is_rejected_by_existing_rls_with_check(): void
    {
        $this->begin($this->primary, self::A);
        try {
            // Mismatched physical row tenant, not a matched trusted context.
            $this->primary->table(self::LEDGER)->insert($this->row(self::B, self::OTHER_KEY));
            $this->fail('Cross-tenant insert was unexpectedly permitted.');
        } catch (QueryException $e) {
            $this->assertSame('42501', $e->errorInfo[0] ?? null);
        } finally {
            $this->primary->rollBack();
        }
        $this->assertSame(0, $this->fixture->table(self::LEDGER)
            ->whereIn('tenant_id', [self::A, self::B])->count());
    }

    public function test_same_tenant_different_submission_is_not_person_deduplication(): void
    {
        $this->begin($this->primary, self::A);
        $this->assertSame(1, $this->primary->table(self::LEDGER)->insertOrIgnore($this->row()));
        $this->assertSame(0, $this->primary->table(self::LEDGER)->insertOrIgnore($this->row()));
        $this->assertSame(1, $this->primary->table(self::LEDGER)->insertOrIgnore(
            $this->row(self::A, self::OTHER_KEY),
        ));
        $this->assertSame(2, $this->primary->table(self::LEDGER)->count());
        $this->primary->commit();

        $this->assertSame(2, $this->fixture->table(self::LEDGER)->where('tenant_id', self::A)->count());
        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());
    }

    /**
     * F3-16: repeated authentic-looking fixture bytes remain valid HMACs.
     * Only the existing PostgreSQL unique constraint rejects a second
     * occurrence with the same tenant-scoped nonce-as-key marker.
     *
     * The marker is NOT a production anti-replay schema or a CRM receipt.
     */
    public function test_repeated_matching_hmac_still_requires_physical_nonce_claim(): void
    {
        $claim = new SyntheticIdempotencyClaim(
            new LeadSubmissionIdentity(self::A, self::KEY, self::OTHER_KEY),
            '50505050-5050-4050-8050-505050505050',
            '60606060-6060-4060-8060-606060606060',
            '70707070-7070-4070-8070-707070707070',
        );
        $nonce = self::KEY;
        $issuedAt = 1800000000;
        $expiresAt = 1800000300;
        $now = $issuedAt + 20;
        $secret = str_repeat('fixture-only-', 4);

        $mac = hash_hmac('sha256', implode("\n", [
            'imagine.lead-intake.synthetic-receipt.v1',
            $claim->identity->organizationReference,
            $claim->identity->bindingReference,
            $claim->identity->submissionReference,
            $claim->siteReference,
            $claim->formReference,
            $claim->destinationReference,
            (string) $issuedAt,
            (string) $expiresAt,
            $nonce,
        ]), $secret);

        $inspector = new SyntheticLeadReceiptMacInspector();
        foreach ([1, 2] as $_attempt) {
            $assessment = $inspector->inspect(
                $claim, $claim, $issuedAt, $expiresAt, $now, $nonce, $mac, $secret,
            );
            $this->assertSame('LAB_MAC_MATCH_UNTRUSTED', $assessment->reasonCode);
            $this->assertFalse($assessment->canAcceptLead());
            $this->assertFalse($assessment->canTreatAsReplaySafe());
        }

        $this->begin($this->primary, self::A);
        $this->assertSame(1, $this->primary->table(self::LEDGER)
            ->insertOrIgnore($this->row(self::A, $nonce)));
        $this->primary->commit();

        $this->begin($this->contingency, self::A);
        $this->assertSame(0, $this->contingency->table(self::LEDGER)
            ->insertOrIgnore($this->row(self::A, $nonce)));
        $this->assertSame(1, $this->contingency->table(self::LEDGER)->count());
        $this->contingency->commit();

        // Neither fixture secret nor nonce HMAC is written to a lead system.
        $this->assertFalse($this->primary->getSchemaBuilder()
            ->hasColumn(self::LEDGER, 'receipt_mac'));
    }

    public function test_nonce_marker_is_not_automatically_reusable_when_expiry_hint_passes(): void
    {
        $expired = $this->row(self::A, self::KEY);
        $expired['created_at'] = time() - 300;
        $expired['expires_at'] = time() - 1;

        $this->begin($this->primary, self::A);
        $this->assertSame(1, $this->primary->table(self::LEDGER)->insertOrIgnore($expired));
        $this->primary->commit();

        // A timestamp hint does not erase a committed unique-key marker.
        // Any retention/expiry procedure requires a separate approved design.
        $this->begin($this->contingency, self::A);
        $this->assertSame(0, $this->contingency->table(self::LEDGER)
            ->insertOrIgnore($this->row(self::A, self::KEY)));
        $this->assertSame(1, $this->contingency->table(self::LEDGER)->count());
        $this->contingency->commit();
    }

    public function test_two_different_nonces_with_same_digest_show_nonce_is_not_business_idempotency(): void
    {
        // Unique nonce markers alone cannot tell that the same business
        // submission was retried under another fresh nonce. A separate
        // trusted tenant+submission ledger is indispensable.
        $first = $this->row(self::A, self::KEY);
        $second = $this->row(self::A, self::OTHER_KEY);
        $this->assertSame($first['digest'], $second['digest']);
        $this->assertNotSame($first['operation_id'], $second['operation_id']);

        $this->begin($this->primary, self::A);
        $this->assertSame(1, $this->primary->table(self::LEDGER)->insertOrIgnore($first));
        $this->assertSame(1, $this->primary->table(self::LEDGER)->insertOrIgnore($second));
        $this->assertSame(2, $this->primary->table(self::LEDGER)->count());
        $this->primary->commit();
    }

    public function test_receiver_cannot_erase_nonce_marker_to_reenable_replay(): void
    {
        $this->begin($this->primary, self::A);
        $this->primary->table(self::LEDGER)->insert($this->row(self::A, self::KEY));
        $this->primary->commit();

        $this->begin($this->contingency, self::A);
        try {
            $this->contingency->table(self::LEDGER)
                ->where('idempotency_key', self::KEY)->delete();
            $this->fail('Restricted receiver unexpectedly deleted an anti-replay marker.');
        } catch (QueryException $e) {
            $this->assertSame('42501', $e->errorInfo[0] ?? null);
        } finally {
            $this->contingency->rollBack();
        }

        $this->begin($this->contingency, self::A);
        $this->assertSame(0, $this->contingency->table(self::LEDGER)
            ->insertOrIgnore($this->row(self::A, self::KEY)));
        $this->contingency->commit();
        $this->assertSame(1, $this->fixture->table(self::LEDGER)
            ->where('tenant_id', self::A)->count());
    }

    /**
     * A PostgreSQL policy with current_setting() only provides a row filter.
     * Without a server-authenticated context, the physical role cannot know
     * whether tenant A or B is legally accessible to the application caller.
     */
    public function test_untrusted_tenant_guc_can_switch_visible_tenant_rows(): void
    {
        // Fixtures inserted by ADMIN in disposable CI, never by an end user.
        $this->fixture->table(self::LEDGER)->insert([
            $this->row(self::A, self::KEY),
            $this->row(self::B, self::KEY),
        ]);

        $this->begin($this->primary, self::A);
        $this->assertSame([self::A],
            $this->primary->table(self::LEDGER)->pluck('tenant_id')->all());

        // Deliberately demonstrate that the restricted DB role CAN change
        // the GUC to B even though no identity/membership was authenticated.
        // This is an unmet security precondition, NOT a bypass to enable.
        $this->primary->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::B]);
        $this->assertSame([self::B],
            $this->primary->table(self::LEDGER)->pluck('tenant_id')->all());
        $this->primary->commit();

        // SET LOCAL disappears on commit; it does not solve provenance.
        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());
    }

    public function test_missing_and_rolled_back_tenant_context_cannot_read_fixture_rows(): void
    {
        $this->fixture->table(self::LEDGER)->insert($this->row());
        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());

        $this->begin($this->primary, self::A);
        $this->assertSame(1, $this->primary->table(self::LEDGER)->count());
        $this->primary->rollBack();
        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());

        $this->begin($this->primary, self::B);
        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());
        $this->primary->commit();
        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());
    }

    public function test_session_scoped_guc_would_leak_a_tenant_context_between_jobs(): void
    {
        $this->fixture->table(self::LEDGER)->insert($this->row());

        // Do NOT use false in production/pools. This deliberately probes
        // the hazard of session-scoped instead of transaction-scoped state.
        try {
            $this->primary->selectOne(
                "SELECT set_config('app.tenant_id', ?, false)", [self::A],
            );
            $this->assertSame(1, $this->primary->table(self::LEDGER)->count());
            $this->assertSame(1, $this->primary->table(self::LEDGER)->count());
        } finally {
            // Ensure the deliberately contaminated connection is reset even
            // if assertions throw; never poison other tests.
            $this->primary->statement('RESET app.tenant_id');
        }

        $this->assertSame(0, $this->primary->table(self::LEDGER)->count());
    }

    public function test_existing_async_ledger_is_not_the_business_lead_delivery_system(): void
    {
        // The PHP lab contract pins original destination, while the existing
        // async table intentionally has NO site/form/destination columns.
        // Do not treat uniqueness or RLS here as an implemented lead ledger.
        $context = new SyntheticIdempotencyClaim(
            new LeadSubmissionIdentity(self::A, self::KEY, self::OTHER_KEY),
            '50505050-5050-4050-8050-505050505050',
            '60606060-6060-4060-8060-606060606060',
            '70707070-7070-4070-8070-707070707070',
        );

        $this->assertSame(self::A, $context->identity->organizationReference);
        $this->assertFalse(method_exists($context, 'deliver'));
        $this->assertFalse($this->primary->getSchemaBuilder()->hasColumn(self::LEDGER, 'destination_reference'));
        $this->assertFalse($this->primary->getSchemaBuilder()->hasColumn(self::LEDGER, 'site_reference'));
    }
}
