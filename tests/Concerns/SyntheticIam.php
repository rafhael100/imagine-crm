<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Core\Identity\ExperimentalAuthenticator;
use App\Core\Identity\Totp;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Connection;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Synthetic setup shared by F1-03 regression and F1-04 fault-injection tests. */
trait SyntheticIam
{
    protected const A = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    protected const B = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    protected const C = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
    protected const D = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';
    protected const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // RFC fixture only.
    protected const PASSWORD = 'synthetic-password-no-real-account';
    protected const RECOVERY = 'synthetic-single-use-recovery-code';
    protected Connection $db;
    protected Connection $fixtures;
    protected Store $browserSession;
    protected SessionGuard $guard;
    protected ExperimentalAuthenticator $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTimestamp(1800000000));
        // Isolate F1-03 core limits from the independently tested F1-04 budgets.
        config(['identity.experimental' => true, 'identity.abuse_store' => 'array',
            'identity.abuse_identity_limit' => 1000, 'identity.abuse_origin_limit' => 1000, 'identity.abuse_backoff' => 0]);
        $this->app['request']->server->set('REMOTE_ADDR', '127.0.0.1');
        (new AppServiceProvider($this->app))->register();
        $this->app['auth']->forgetGuards();
        $settings = config('database.connections.'.config('database.default'));
        if (DB::getDriverName() === 'pgsql') {
            if (!getenv('IAM_TEST_USERNAME')) {
                $this->markTestSkipped('Requires isolated IAM PostgreSQL role.');
            }
            $this->fixtures = DB::connection();
            $settings['username'] = getenv('IAM_TEST_USERNAME');
            $settings['password'] = getenv('IAM_TEST_PASSWORD');
            $settings['url'] = null;
        }
        config(['database.connections.crm_iam' => $settings]);
        DB::purge('crm_iam');
        $this->db = DB::connection('crm_iam');
        if ($this->db->getDriverName() === 'sqlite') {
            $old = DB::getDefaultConnection();
            DB::setDefaultConnection('crm_iam');
            Schema::create('crm_global_identities', function ($t): void { $t->uuid('id')->primary(); $t->timestamps(); });
            (require database_path('experimental-migrations/auth/2026_11_04_000002_create_candidate_crm_auth_registry.php'))->up();
            (require database_path('experimental-migrations/auth/2026_11_04_000003_create_candidate_iam_hardening.php'))->up();
            DB::setDefaultConnection($old);
            $this->fixtures = $this->db;
        }
        $this->cleanFixtures();
        foreach ([self::A => 'fixture-a', self::B => 'fixture-b'] as $id => $login) {
            $this->fixtures->table('crm_global_identities')->insert(['id' => $id]);
            $this->fixtures->table('crm_auth_credentials')->insert([
                'identity_id' => $id, 'login_key' => hash('sha256', $login),
                'password_hash' => $this->app['hash']->make(self::PASSWORD),
                'status' => 'active', 'verified_at' => now()->getTimestamp(), 'version' => 1,
                'factor_ciphertext' => $this->app['encrypter']->encryptString(self::SECRET),
                'factor_confirmed_at' => now()->getTimestamp(),
            ]);
            $this->fixtures->table('crm_auth_recovery_codes')->insert([
                'id' => (string) Str::uuid(), 'identity_id' => $id,
                'code_hash' => $this->app['hash']->make(self::RECOVERY), 'credential_version' => 1,
            ]);
        }
        $this->browserSession = $this->app['session.store'];
        $this->browserSession->start();
        $this->guard = $this->app['auth']->guard('crm_identity');
        $this->auth = $this->app->make(ExperimentalAuthenticator::class);
    }

    protected function tearDown(): void
    {
        if (isset($this->fixtures)) {
            $this->cleanFixtures();
        }
        $this->travelBack();
        parent::tearDown();
    }

    protected function cleanFixtures(): void
    {
        foreach (['crm_auth_operations', 'crm_auth_mandates'] as $table) {
            $this->fixtures->table($table)->whereIn('subject_id', [self::A, self::B])->orWhereIn('actor_id', [self::A, self::B])->delete();
        }
        $this->fixtures->table('crm_iam_audit_events')->whereIn('subject_id', [self::A, self::B])->orWhereIn('actor_id', [self::A, self::B])->delete();
        foreach (['crm_auth_recovery_codes', 'crm_auth_sessions', 'crm_auth_credentials'] as $table) {
            $this->fixtures->table($table)->whereIn('identity_id', [self::A, self::B])->delete();
        }
        if ($this->fixtures->getDriverName() === 'pgsql') {
            $this->fixtures->table('crm_memberships')->whereIn('global_identity_id', [self::A, self::B])->delete();
            $this->fixtures->table('crm_organizations')->whereIn('id', [self::C, self::D])->delete();
        }
        $this->fixtures->table('crm_global_identities')->whereIn('id', [self::A, self::B])->delete();
    }

    protected function login(string $login = 'fixture-a'): void
    {
        $this->auth->begin($login, self::PASSWORD);
        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
    }

}
