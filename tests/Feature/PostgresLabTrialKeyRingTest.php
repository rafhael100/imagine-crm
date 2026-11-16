<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Plans\LaboratoryDurableTrialClaim;
use App\Core\Plans\LaboratoryHistoricalTrialAliases;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * F3-TRIAL-PG02 — verifies rotation-safe historical aliases without the
 * ability to SELECT any trial identifiers as the restricted runtime role.
 */
final class PostgresLabTrialKeyRingTest extends TestCase
{
    private const A = '12.ABC.345/01DE-35';
    private const B = '11.222.333/0001-81';
    private const V1 = 'crm-f3-trial-lab-synthetic-dedicated-hmac-key-32-bytes';
    private const V2 = 'crm-f3-trial-lab-other-synthetic-dedicated-hmac-key-32-bytes';
    private const START = 1700000000;

    private Connection $admin;
    private Connection $primary;
    private Connection $secondary;

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
            $this->markTestSkipped('CI-only historical trial aliases.');
        }
        $this->admin = DB::connection();
        $this->clean();
        $cfg = $pg;
        $cfg['url'] = null;
        $cfg['username'] = getenv('TRIAL_TEST_USERNAME');
        $cfg['password'] = getenv('TRIAL_TEST_PASSWORD');
        config([
            'database.connections.trial_keyring_primary_ci' => $cfg,
            'database.connections.trial_keyring_secondary_ci' => $cfg,
        ]);
        DB::purge('trial_keyring_primary_ci');
        DB::purge('trial_keyring_secondary_ci');
        $this->primary = DB::connection('trial_keyring_primary_ci');
        $this->secondary = DB::connection('trial_keyring_secondary_ci');
        $this->secondary->statement("SET lock_timeout = '200ms'");
    }

    protected function tearDown(): void
    {
        if (isset($this->primary)) {
            foreach ([$this->primary, $this->secondary] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->clean();
            DB::purge('trial_keyring_primary_ci');
            DB::purge('trial_keyring_secondary_ci');
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $builder = new OfflineCnpjTrialHmacBinding();
        $this->admin->table('crm_lab_trial_claims')->whereIn('cnpj_hmac', [
            $builder->derive(self::A, self::V1),
            $builder->derive(self::A, self::V2),
            $builder->derive(self::B, self::V1),
            $builder->derive(self::B, self::V2),
        ])->delete();
        $this->admin->table('crm_lab_trial_key_epochs')->where('version', 2)->delete();
    }

    private static function ring(): array
    {
        return [
            ['version' => 1, 'secret' => self::V1],
            ['version' => 2, 'secret' => self::V2],
        ];
    }

    private function rotate(): void
    {
        $this->admin->table('crm_lab_trial_key_epochs')->insert([
            'version' => 2,
            'secret_tag' => hash_hmac('sha256', 'imagine.crm.trial.keyring.v1', self::V2),
        ]);
    }

    private function alias(Connection $db, ?array $ring = null): LaboratoryHistoricalTrialAliases
    {
        return new LaboratoryHistoricalTrialAliases($db, $ring ?? self::ring());
    }

    public function test_read_only_registry_is_visible_but_claims_remain_unreadable(): void
    {
        foreach ([$this->primary, $this->secondary] as $db) {
            $policyRead = $db->selectOne(
                "SELECT has_table_privilege(current_user, 'crm_lab_trial_key_epochs', 'SELECT') AS allowed",
            );
            $this->assertTrue((bool) $policyRead->allowed);
            $this->assertFalse((bool) $db->selectOne(
                "SELECT has_table_privilege(current_user, 'crm_lab_trial_claims', 'SELECT') AS allowed",
            )->allowed);
            foreach (['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $operation) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_trial_key_epochs', $operation],
                )->allowed);
            }
        }
        $policy = $this->primary->select(
            'SELECT version, secret_tag FROM crm_lab_trial_key_epochs ORDER BY version',
        );
        $this->assertCount(1, $policy);
        $this->assertSame(1, (int) $policy[0]->version);
    }

    public function test_old_company_remains_duplicate_after_v2_policy_rollout(): void
    {
        $first = (new LaboratoryDurableTrialClaim($this->primary, self::V1))
            ->observe(self::A, self::START);
        $this->assertSame('synthetic_first_marker', $first['status_code']);
        $this->rotate();

        $duplicate = $this->alias($this->secondary)->observe(self::A, self::START + 500);
        $this->assertSame('synthetic_duplicate_marker', $duplicate['status_code']);
        $this->assertSame(2, $duplicate['alias_versions_checked']);
        $this->assertTrue($duplicate['complete_history_policy_checked']);
        $this->assertFalse($duplicate['can_register']);
        $this->assertFalse($duplicate['can_reserve_trial']);

        // A transaction that detects old v1 must not leave a v2 alias behind.
        $this->assertSame(1, $this->admin->table('crm_lab_trial_claims')->count());
        try {
            (new LaboratoryDurableTrialClaim($this->primary, self::V1))
                ->observe(self::B, self::START + 1000);
            $this->fail('A client using only v1 after policy expansion must be denied.');
        } catch (RuntimeException $e) {
            $this->assertSame('Politica historica de trial nao corresponde a chave.', $e->getMessage());
        }
        $this->assertSame(1, $this->admin->table('crm_lab_trial_claims')->count());
    }

    public function test_new_company_claim_adds_both_aliases_atomically_and_replay_denied(): void
    {
        $this->rotate();
        $first = $this->alias($this->primary)->observe(self::A, self::START);
        $duplicate = $this->alias($this->secondary)->observe(self::A, self::START + 15 * 86400);
        $this->assertSame('synthetic_first_marker', $first['status_code']);
        $this->assertSame('synthetic_duplicate_marker', $duplicate['status_code']);
        $this->assertSame(2, $this->admin->table('crm_lab_trial_claims')->count());
        $this->assertSame([1, 2], $this->admin->table('crm_lab_trial_claims')
            ->orderBy('key_version')->pluck('key_version')->all());

        foreach ([$first, $duplicate] as $result) {
            $this->assertTrue($result['informational_only']);
            foreach (['can_register', 'can_reserve_trial', 'can_authenticate',
                'can_create_tenant', 'can_charge', 'can_allocate_quota',
                'company_owner_verified', 'contact_verified', 'consent_verified'] as $flag) {
                $this->assertFalse($result[$flag], $flag);
            }
            $wire = json_encode($result, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('cnpj', strtolower($wire));
            $this->assertStringNotContainsString('secret', strtolower($wire));
        }
    }

    public function test_missing_or_wrong_historical_key_is_refused_without_sql_claim(): void
    {
        $this->rotate();
        foreach ([
            [['version' => 2, 'secret' => self::V2]],
            [['version' => 1, 'secret' => self::V1]],
            [['version' => 1, 'secret' => self::V1], ['version' => 2, 'secret' => self::V1.'x']],
        ] as $ring) {
            try {
                $this->alias($this->primary, $ring)->observe(self::A, self::START);
                $this->fail('Incomplete or tampered key ring must fail closed.');
            } catch (RuntimeException|\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, $this->admin->table('crm_lab_trial_claims')->count());
    }

    public function test_concurrent_historical_claim_uses_uniqueness_without_select(): void
    {
        $this->rotate();
        $this->primary->beginTransaction();
        $first = $this->alias($this->primary)->observe(self::A, self::START);
        $this->assertSame('synthetic_first_marker', $first['status_code']);

        try {
            $this->alias($this->secondary)->observe(self::A, self::START + 1);
            $this->fail('Uncommitted alias must block a concurrent request.');
        } catch (RuntimeException $e) {
            $this->assertSame('Observacao historica de laboratorio indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }

        $this->primary->commit();
        $this->assertSame('synthetic_duplicate_marker',
            $this->alias($this->secondary)->observe(self::A, self::START + 2)['status_code']);
        $this->assertSame(2, $this->admin->table('crm_lab_trial_claims')->count());
    }

    public function test_rollback_removes_all_version_aliases(): void
    {
        $this->rotate();
        $this->primary->beginTransaction();
        $this->assertSame('synthetic_first_marker',
            $this->alias($this->primary)->observe(self::B, self::START)['status_code']);
        $this->primary->rollBack();
        $this->assertSame(0, $this->admin->table('crm_lab_trial_claims')->count());
        $this->assertSame('synthetic_first_marker',
            $this->alias($this->secondary)->observe(self::B, self::START + 1)['status_code']);
        $this->assertSame(2, $this->admin->table('crm_lab_trial_claims')->count());
    }
}
