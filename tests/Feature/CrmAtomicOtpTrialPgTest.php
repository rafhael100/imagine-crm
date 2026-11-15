<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Plans\LaboratoryAtomicOtpTrialEnrollment;
use App\Core\Plans\TrialWindowAdvisory;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CRM-TRIAL-OTP-PG01: real PostgreSQL16 disposable CI proof.
 * Test-only tables and grants are never Laravel production migrations.
 */
final class CrmAtomicOtpTrialPgTest extends TestCase
{
    private const START = 1800000000;
    private const CNPJ = '12.ABC.345/01DE-35';
    private const OTP_KEY = 'ci-only-otp-hmac-key-for-atomic-trial-2026';
    private const CONTACT_KEY = 'ci-only-contact-key-for-atomic-trial-2026';
    private const KEY_ONE = 'ci-only-trial-v1-historical-key-2026-very-long';
    private const KEY_TWO = 'ci-only-trial-v2-historical-key-2026-very-long';

    private Connection $owner;
    private Connection $runtime;
    private LaboratoryAtomicOtpTrialEnrollment $enrollment;
    private OfflineSignupOtpChallenge $otp;

    /** @return list<array{version:int,secret:string}> */
    private static function versions(): array
    {
        return [
            ['version' => 1, 'secret' => self::KEY_ONE],
            ['version' => 2, 'secret' => self::KEY_TWO],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');

        if (!app()->environment('testing')
            || DB::getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci'
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_TRIAL_OTP_PG_LAB') !== 'synthetic-only') {
            $this->markTestSkipped('Isolated, synthetic-only PostgreSQL16 GitHub Actions required.');
        }

        $this->owner = DB::connection();
        $this->assertSame(0, $this->owner->transactionLevel());
        $this->assertSame('postgres', $this->owner->selectOne('SELECT current_user AS role_name')->role_name);
        $this->assertMatchesRegularExpression('/\A16[0-9]{4}\z/',
            (string) $this->owner->selectOne('SHOW server_version_num')->server_version_num);

        // Throwaway schema, never a shipped production migration.
        $this->owner->statement(<<<'SQL'
CREATE TABLE public.crm_ci_trial_otp_challenges (
    nonce char(32) PRIMARY KEY,
    contact_binding char(64) NOT NULL,
    channel text NOT NULL CHECK (channel IN ('email', 'phone')),
    digest char(64) NOT NULL,
    issued_at bigint NOT NULL CHECK (issued_at > 0),
    expires_at bigint NOT NULL,
    attempts_used smallint NOT NULL DEFAULT 0 CHECK (attempts_used BETWEEN 0 AND 5),
    consumed_at bigint NULL,
    CHECK (expires_at = issued_at + 900)
)
SQL);
        $this->owner->statement(<<<'SQL'
CREATE TABLE public.crm_ci_trial_hmac_aliases (
    key_version smallint NOT NULL CHECK (key_version > 0),
    cnpj_hmac char(64) NOT NULL,
    started_at bigint NOT NULL,
    expires_at bigint NOT NULL,
    PRIMARY KEY (key_version, cnpj_hmac),
    CHECK (expires_at = started_at + 1209600)
)
SQL);
        $this->owner->statement(<<<'SQL'
CREATE TABLE public.crm_ci_trial_key_epochs (
    version smallint PRIMARY KEY,
    secret_tag char(64) NOT NULL
)
SQL);
        foreach (self::versions() as $version) {
            $this->owner->table('crm_ci_trial_key_epochs')->insert([
                'version' => $version['version'],
                'secret_tag' => hash_hmac(
                    'sha256', 'imagine.crm.trial.keyring.v1', $version['secret'],
                ),
            ]);
        }

        $this->owner->statement(<<<'SQL'
CREATE ROLE crm_trial_otp_ci LOGIN PASSWORD 'only-ephemeral-trial-otp-ci'
NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT
SQL);
        $this->owner->statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_trial_otp_ci');
        $this->owner->statement('GRANT USAGE ON SCHEMA public TO crm_trial_otp_ci');
        $this->owner->statement(
            'GRANT SELECT, INSERT, UPDATE (attempts_used, consumed_at)
             ON TABLE public.crm_ci_trial_otp_challenges TO crm_trial_otp_ci'
        );
        $this->owner->statement(
            'GRANT INSERT ON TABLE public.crm_ci_trial_hmac_aliases TO crm_trial_otp_ci'
        );
        $this->owner->statement(
            'GRANT SELECT ON TABLE public.crm_ci_trial_key_epochs TO crm_trial_otp_ci'
        );

        $pg['username'] = 'crm_trial_otp_ci';
        $pg['password'] = 'only-ephemeral-trial-otp-ci';
        $pg['url'] = null;
        config(['database.connections.crm_ci_atomic_trial' => $pg]);
        DB::purge('crm_ci_atomic_trial');
        $this->runtime = DB::connection('crm_ci_atomic_trial');
        $this->otp = new OfflineSignupOtpChallenge();
        $this->enrollment = new LaboratoryAtomicOtpTrialEnrollment(
            $this->runtime, self::OTP_KEY, self::versions(),
        );

        $this->assertFalse((bool) $this->runtime->selectOne(
            "SELECT has_table_privilege(current_user,
             'public.crm_ci_trial_hmac_aliases', 'UPDATE') AS can_write"
        )->can_write);
        $this->assertFalse((bool) $this->runtime->selectOne(
            "SELECT has_table_privilege(current_user,
             'public.crm_ci_trial_hmac_aliases', 'DELETE') AS can_delete"
        )->can_delete);
    }

    protected function tearDown(): void
    {
        if (isset($this->owner)) {
            DB::purge('crm_ci_atomic_trial');
            foreach ([
                'crm_ci_trial_otp_challenges',
                'crm_ci_trial_hmac_aliases',
                'crm_ci_trial_key_epochs',
            ] as $name) {
                $this->owner->statement('DROP TABLE IF EXISTS public.'.$name);
            }
            $this->owner->statement('DROP ROLE IF EXISTS crm_trial_otp_ci');
        }
        parent::tearDown();
    }

    /**
     * @return array{nonce:string,code:string,binding:string,channel:string}
     */
    private function issue(string $channel = 'email', string $contact = 'fixture-otp@example.test',
                           int $at = self::START): array
    {
        $binding = (new OfflineSignupContactHmacBinding())
            ->derive($contact, $channel, self::CONTACT_KEY);
        $issued = $this->otp->issue(self::OTP_KEY, $binding, $channel, $at);
        $this->enrollment->recordSyntheticChallenge($issued['challenge'], $binding, $channel);

        $this->assertSame(1, $this->owner->table('crm_ci_trial_otp_challenges')
            ->where('nonce', $issued['challenge']['nonce'])->count());
        $this->assertNull($this->owner->table('crm_ci_trial_otp_challenges')
            ->where('nonce', $issued['challenge']['nonce'])->value('consumed_at'));
        $this->assertSame(0, $this->runtime->transactionLevel());

        return [
            'nonce' => $issued['challenge']['nonce'],
            'code' => $issued['delivery_code'], // In PHPUnit memory only.
            'binding' => $binding,
            'channel' => $channel,
        ];
    }

    /** @param array{nonce:string,code:string,binding:string,channel:string} $issued */
    private function observe(array $issued, int $at, string $cnpj = self::CNPJ, ?string $code = null): array
    {
        $answer = $this->enrollment->observeSyntheticTrial(
            $issued['nonce'], $code ?? $issued['code'], $issued['binding'],
            $issued['channel'], $cnpj, $at,
        );
        foreach (['can_register', 'can_authenticate', 'can_create_tenant',
            'can_reserve_trial', 'can_allocate_quota', 'can_charge',
            'consent_verified', 'company_owner_verified',
            'contact_ownership_verified'] as $flag) {
            $this->assertFalse($answer[$flag] ?? $answer['contact_ownership_verified'] ?? false, $flag);
        }
        $this->assertSame(0, $this->runtime->transactionLevel());
        return $answer;
    }

    public function test_otp_is_durably_consumed_and_one_company_gets_only_one_two_key_marker(): void
    {
        $first = $this->issue();
        $out = $this->observe($first, self::START + 1);
        $this->assertSame('synthetic_first_marker', $out['status_code']);
        $this->assertSame(2, $this->owner->table('crm_ci_trial_hmac_aliases')->count());
        $this->assertSame(self::START + 1, (int) $this->owner
            ->table('crm_ci_trial_otp_challenges')->value('consumed_at'));

        $replayed = $this->observe($first, self::START + 2);
        $this->assertSame('synthetic_otp_denied', $replayed['status_code']);
        $this->assertSame(2, $this->owner->table('crm_ci_trial_hmac_aliases')->count());

        $phone = $this->issue('phone', '+5511999988888', self::START + 5);
        $duplicate = $this->observe($phone, self::START + 6);
        $this->assertSame('synthetic_duplicate_marker', $duplicate['status_code']);
        $this->assertSame(2, $this->owner->table('crm_ci_trial_hmac_aliases')->count());
        $this->assertNotNull($this->owner->table('crm_ci_trial_otp_challenges')
            ->where('nonce', $phone['nonce'])->value('consumed_at'));

        $start = (int) $this->owner->table('crm_ci_trial_hmac_aliases')->min('started_at');
        $this->assertSame('within_window',
            (new TrialWindowAdvisory($start, $start + 1209600 - 1))->phase()->value);
        $this->assertSame('after_window',
            (new TrialWindowAdvisory($start, $start + 1209600))->phase()->value);
        $this->assertSame(0, (new TrialWindowAdvisory($start, $start + 1209600))
            ->remainingSeconds());

        // A second code on day 15 cannot restart a used CNPJ trial.
        $afterExpiry = $this->issue('email', 'other-fixture@example.test',
            self::START + 14 * 86400 + 2);
        $this->assertSame('synthetic_duplicate_marker',
            $this->observe($afterExpiry, self::START + 14 * 86400 + 3)['status_code']);
        $this->assertSame(2, $this->owner->table('crm_ci_trial_hmac_aliases')->count());

        $dump = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([self::CNPJ, '12ABC34501DE35', $first['code'], $first['binding'],
            $first['nonce'], 'secret_tag', 'cnpj_hmac'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $dump);
        }
    }

    public function test_historical_v2_collision_rolls_back_new_v1_alias_but_consumes_otp(): void
    {
        // Force a collision on the *second* historical alias so the
        // transaction must roll back a prior successful v1 insert.
        $historical = (new OfflineCnpjTrialHmacBinding())->derive(self::CNPJ, self::KEY_TWO);
        $this->owner->table('crm_ci_trial_hmac_aliases')->insert([
            'key_version' => 2,
            'cnpj_hmac' => $historical,
            'started_at' => self::START - 60,
            'expires_at' => self::START - 60 + 1209600,
        ]);
        $issued = $this->issue();
        $answer = $this->observe($issued, self::START + 3);
        $this->assertSame('synthetic_duplicate_marker', $answer['status_code']);
        $this->assertSame(1, $this->owner->table('crm_ci_trial_hmac_aliases')->count());
        $this->assertSame(0, $this->owner->table('crm_ci_trial_hmac_aliases')
            ->where('key_version', 1)->count());
        $this->assertNotNull($this->owner->table('crm_ci_trial_otp_challenges')
            ->where('nonce', $issued['nonce'])->value('consumed_at'));
        $this->assertSame('synthetic_otp_denied',
            $this->observe($issued, self::START + 4)['status_code']);
    }

    public function test_wrong_codes_use_five_durable_attempts_and_expiry_is_fail_closed(): void
    {
        $issued = $this->issue();
        $wrong = substr($issued['code'], 0, 5)
            .(string) (((int) substr($issued['code'], -1) + 1) % 10);
        for ($i = 1; $i <= 5; ++$i) {
            $this->assertSame('synthetic_otp_denied',
                $this->observe($issued, self::START + $i, self::CNPJ, $wrong)['status_code']);
            $this->assertSame($i, (int) $this->owner
                ->table('crm_ci_trial_otp_challenges')->value('attempts_used'));
        }
        $this->assertSame('synthetic_otp_denied',
            $this->observe($issued, self::START + 6)['status_code']);
        $this->assertSame(0, $this->owner->table('crm_ci_trial_hmac_aliases')->count());

        $expired = $this->issue('phone', '+5511999977777', self::START + 10);
        $this->assertSame('synthetic_otp_denied',
            $this->observe($expired, self::START + 10 + 900)['status_code']);
        $this->assertSame(0, $this->owner->table('crm_ci_trial_hmac_aliases')->count());
    }

    public function test_incomplete_key_ring_denies_without_spending_otp_or_trial_aliases(): void
    {
        $issued = $this->issue();
        $this->owner->table('crm_ci_trial_key_epochs')->where('version', 2)->delete();
        try {
            $this->observe($issued, self::START + 1);
            $this->fail('An incomplete historical HMAC policy must not be accepted.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Observacao atomica de trial indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }

        $this->assertNull($this->owner->table('crm_ci_trial_otp_challenges')
            ->where('nonce', $issued['nonce'])->value('consumed_at'));
        $this->assertSame(0, $this->owner->table('crm_ci_trial_hmac_aliases')->count());

        $this->owner->table('crm_ci_trial_key_epochs')->insert([
            'version' => 2,
            'secret_tag' => hash_hmac('sha256', 'imagine.crm.trial.keyring.v1', self::KEY_TWO),
        ]);
        $this->assertSame('synthetic_first_marker',
            $this->observe($issued, self::START + 2)['status_code']);
    }
}
