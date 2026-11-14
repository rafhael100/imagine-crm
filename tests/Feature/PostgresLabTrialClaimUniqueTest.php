<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Plans\LaboratoryDurableTrialClaim;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * F3-TRIAL-PG01: PostgreSQL 16 physical uniqueness on two independent
 * restricted-role connections. No public routes, no live signup/trial.
 */
final class PostgresLabTrialClaimUniqueTest extends TestCase
{
    private const CNPJ_A = '12.ABC.345/01DE-35';
    private const CNPJ_A_ALT = '12abc34501de35';
    private const CNPJ_B = '11.222.333/0001-81';
    private const HMAC_KEY = 'crm-f3-trial-lab-synthetic-dedicated-hmac-key-32-bytes';
    private const OTHER_HMAC_KEY = 'crm-f3-trial-lab-other-synthetic-dedicated-hmac-key-32-bytes';
    private const START = 1700000000;
    private const TABLE = 'crm_lab_trial_claims';

    private Connection $admin;
    private Connection $primary;
    private Connection $contingency;

    protected function setUp(): void
    {
        parent::setUp();

        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_TRIAL_LEDGER_LAB') !== 'synthetic-only'
            || getenv('TRIAL_TEST_USERNAME') !== 'crm_trial_claim_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('Only disposable GitHub-hosted PostgreSQL trial laboratory.');
        }

        $this->admin = DB::connection();
        $this->clean();
        $settings = $pg;
        $settings['url'] = null;
        $settings['username'] = getenv('TRIAL_TEST_USERNAME');
        $settings['password'] = getenv('TRIAL_TEST_PASSWORD');

        config(['database.connections.crm_trial_primary_ci' => $settings]);
        config(['database.connections.crm_trial_other_ci' => $settings]);

        DB::purge('crm_trial_primary_ci');
        DB::purge('crm_trial_other_ci');
        $this->primary = DB::connection('crm_trial_primary_ci');
        $this->contingency = DB::connection('crm_trial_other_ci');
        $this->contingency->statement("SET lock_timeout = '200ms'");
    }

    protected function tearDown(): void
    {
        if (isset($this->primary)) {
            foreach ([$this->primary, $this->contingency] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->clean();
            DB::purge('crm_trial_primary_ci');
            DB::purge('crm_trial_other_ci');
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $binding = new OfflineCnpjTrialHmacBinding();
        // Scoped cleanup of only documented fictitious identifiers.
        $this->admin->table(self::TABLE)->whereIn('cnpj_hmac', [
            $binding->derive(self::CNPJ_A, self::HMAC_KEY),
            $binding->derive(self::CNPJ_B, self::HMAC_KEY),
            $binding->derive(self::CNPJ_A, self::OTHER_HMAC_KEY),
        ])->delete();
    }

    private function service(Connection $db, string $key = self::HMAC_KEY): LaboratoryDurableTrialClaim
    {
        return new LaboratoryDurableTrialClaim($db, $key);
    }

    public function test_insert_only_role_cannot_read_change_or_enumerate_global_trial_claims(): void
    {
        foreach ([$this->primary, $this->contingency] as $db) {
            $role = $db->selectOne(
                "SELECT rolsuper, rolbypassrls, rolcreaterole, rolinherit
                 FROM pg_roles WHERE rolname = current_user",
            );
            self::assertNotNull($role);
            self::assertFalse((bool) $role->rolsuper);
            self::assertFalse((bool) $role->rolbypassrls);
            self::assertFalse((bool) $role->rolcreaterole);
            self::assertFalse((bool) $role->rolinherit);
            foreach (['SELECT', 'UPDATE', 'DELETE', 'TRUNCATE', 'REFERENCES', 'TRIGGER'] as $operation) {
                $allowed = $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    [self::TABLE, $operation],
                );
                self::assertFalse((bool) $allowed->allowed, $operation);
            }
            self::assertTrue((bool) $db->selectOne(
                'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                [self::TABLE, 'INSERT'],
            )->allowed);
            $this->assertSame(0, $db->transactionLevel());
        }
    }

    public function test_same_company_only_one_marker_across_two_connections_and_after_14_days(): void
    {
        $first = $this->service($this->primary)->observe(self::CNPJ_A, self::START);
        $this->assertSame('synthetic_first_marker', $first['status_code']);
        $replay = $this->service($this->contingency)->observe(self::CNPJ_A_ALT, self::START + 1);
        $this->assertSame('synthetic_duplicate_marker', $replay['status_code']);
        $expired = $this->service($this->contingency)->observe(self::CNPJ_A, self::START + 15 * 86400);
        $this->assertSame('synthetic_duplicate_marker', $expired['status_code']);

        foreach ([$first, $replay, $expired] as $result) {
            $this->assertTrue($result['physical_uniqueness_observed']);
            $this->assertTrue($result['informational_only']);
            $this->assertSame(1, $result['key_version']);
            foreach ([
                'company_owner_verified', 'contact_verified', 'consent_verified',
                'can_reserve_trial', 'can_register', 'can_create_tenant',
                'can_authenticate', 'can_charge', 'can_allocate_quota',
            ] as $flag) {
                $this->assertFalse($result[$flag], $flag);
            }
            $wire = json_encode($result, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString(self::CNPJ_A, $wire);
            $this->assertStringNotContainsString('cnpj_hmac', $wire);
        }

        $rows = $this->admin->table(self::TABLE)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]->key_version);
        $this->assertSame(self::START, (int) $rows[0]->started_at);
        $this->assertSame(self::START + 1209600, (int) $rows[0]->expires_at);
        $this->assertSame('synthetic_observation_only', $rows[0]->state);
        $this->assertFalse((bool) $rows[0]->can_activate);
        $this->assertFalse((bool) $rows[0]->can_register);
        $this->assertFalse((bool) $rows[0]->can_charge);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $rows[0]->cnpj_hmac);
    }

    public function test_parallel_uncommitted_same_company_blocks_then_replays_safely(): void
    {
        $this->primary->beginTransaction();
        $this->assertSame('synthetic_first_marker',
            $this->service($this->primary)->observe(self::CNPJ_A, self::START)['status_code']);
        $this->contingency->beginTransaction();

        try {
            $this->service($this->contingency)->observe(self::CNPJ_A_ALT, self::START + 1);
            $this->fail('Concurrent insert cannot bypass the committed global key.');
        } catch (RuntimeException $e) {
            $this->assertSame('Observacao persistente de laboratorio indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious(), 'Never propagate query with HMAC binding in exception');
        } finally {
            $this->contingency->rollBack();
        }

        $this->primary->commit();
        $this->assertSame('synthetic_duplicate_marker',
            $this->service($this->contingency)->observe(self::CNPJ_A_ALT, self::START + 2)['status_code']);
        $this->assertSame(1, $this->admin->table(self::TABLE)->count());
    }

    public function test_rolled_back_first_claim_does_not_reserve_the_synthetic_company(): void
    {
        $this->primary->beginTransaction();
        $this->assertSame('synthetic_first_marker',
            $this->service($this->primary)->observe(self::CNPJ_B, self::START)['status_code']);
        $this->primary->rollBack();

        $this->assertSame(0, $this->admin->table(self::TABLE)->count());
        $this->assertSame('synthetic_first_marker',
            $this->service($this->contingency)->observe(self::CNPJ_B, self::START + 5)['status_code']);
        $this->assertSame(1, $this->admin->table(self::TABLE)->count());
    }

    public function test_another_synthetic_company_may_have_one_independent_marker(): void
    {
        $a = $this->service($this->primary)->observe(self::CNPJ_A, self::START);
        $b = $this->service($this->contingency)->observe(self::CNPJ_B, self::START);
        $this->assertSame('synthetic_first_marker', $a['status_code']);
        $this->assertSame('synthetic_first_marker', $b['status_code']);
        $this->assertSame(2, $this->admin->table(self::TABLE)->count());
    }

    public function test_invalid_documents_and_time_are_rejected_without_insert(): void
    {
        foreach ([
            ['12.ABC.345/01DE-36', self::START],
            [self::CNPJ_A, 0],
            [self::CNPJ_A, -1],
            [self::CNPJ_A, PHP_INT_MAX],
        ] as [$cnpj, $at]) {
            try {
                $this->service($this->primary)->observe($cnpj, $at);
                $this->fail('Invalid trial input was accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringNotContainsString($cnpj, $e->getMessage());
            }
        }
        $this->assertSame(0, $this->admin->table(self::TABLE)->count());
    }

    public function test_admin_database_identity_is_rejected_by_the_lab_adapter(): void
    {
        try {
            $this->service($this->admin)->observe(self::CNPJ_A, self::START);
            $this->fail('Owner must not be accepted as the runtime actor.');
        } catch (RuntimeException $e) {
            $this->assertSame('Papel de trial do laboratorio nao autorizado.', $e->getMessage());
        }
        $this->assertSame(0, $this->admin->table(self::TABLE)->count());
    }

    public function test_non_current_hmac_key_is_a_known_release_gate_not_false_guarantee(): void
    {
        $first = $this->service($this->primary)->observe(self::CNPJ_A, self::START);
        $other = $this->service($this->contingency, self::OTHER_HMAC_KEY)
            ->observe(self::CNPJ_A, self::START + 1);

        // Demonstrates what must be closed with historical-key candidates
        // before accepting real registration: key rotation changes the HMAC.
        $this->assertSame('synthetic_first_marker', $first['status_code']);
        $this->assertSame('synthetic_first_marker', $other['status_code']);
        $this->assertSame(2, $this->admin->table(self::TABLE)->count());
    }
}
