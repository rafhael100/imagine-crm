<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\DatabaseSessionAuthority;
use App\Core\Identity\ExperimentalAuthenticator;
use App\Core\Identity\GlobalIdentity;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\Totp;
use App\Core\Tenancy\TenantAccessBoundary;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AuthKernelTest extends TestCase
{
    use \Tests\Concerns\SyntheticIam;

    public function test_password_does_not_authenticate_before_mfa(): void
    {
        $old = $this->browserSession->getId();
        $this->browserSession->put('crm.tenant', self::C);
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->assertNotSame($old, $this->browserSession->getId());
        $this->assertNull($this->browserSession->get('crm.tenant'));
        $this->assertNull($this->guard->user());
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_confirmed_mfa_rotates_session_and_produces_registry_proof(): void
    {
        $this->auth->begin('fixture-a', self::PASSWORD);
        $pendingId = $this->browserSession->getId();
        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
        $this->assertNotSame($pendingId, $this->browserSession->getId());
        $this->assertNull($this->browserSession->get('crm.auth.pending'));
        $this->assertSame(self::A, $this->app->make(GuardPrincipalSource::class)->current()->principalId);
        $row = $this->db->table('crm_auth_sessions')->where('state', 'active')->first();
        $this->assertNotSame($this->browserSession->get('crm.auth.token'), $row->token_hash);
        $this->assertNotNull($row->mfa_verified_at);
    }

    public function test_wrong_password_and_unknown_identity_have_same_error(): void
    {
        $messages = [];
        foreach (['fixture-a', 'unknown-user'] as $login) {
            try { $this->auth->begin($login, 'wrong'); $this->fail('Must deny.'); }
            catch (AuthenticationException $e) { $messages[] = $e->getMessage(); }
        }
        $this->assertSame($messages[0], $messages[1]);
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->count());
    }

    public function test_missing_or_incorrect_mfa_revokes_the_challenge(): void
    {
        foreach (['', 'invalid', '1e1234'] as $code) {
            $this->auth->begin('fixture-a', self::PASSWORD);
            try { $this->auth->completeMfa($code); $this->fail('Must deny.'); }
            catch (AuthenticationException) {
                $this->assertNull($this->guard->user());
                $this->assertSame(0, $this->db->table('crm_auth_sessions')->where('state', 'pending')->count());
            }
        }
    }

    public function test_missing_factor_or_unverified_or_disabled_identity_cannot_begin(): void
    {
        foreach ([['factor_confirmed_at' => null], ['verified_at' => null], ['status' => 'disabled']] as $change) {
            $this->fixtures->table('crm_auth_credentials')->where('identity_id', self::A)->update(array_merge(
                ['factor_confirmed_at' => now()->getTimestamp(), 'verified_at' => now()->getTimestamp(), 'status' => 'active'], $change));
            try { $this->auth->begin('fixture-a', self::PASSWORD); $this->fail('Must deny.'); }
            catch (AuthenticationException) { $this->assertNull($this->guard->user()); }
        }
    }

    public function test_totp_cannot_be_replayed_in_another_challenge(): void
    {
        $this->login();
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->expectException(AuthenticationException::class);
        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
    }

    public function test_recovery_code_is_hashed_and_consumed_once(): void
    {
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->auth->completeMfa(self::RECOVERY, true);
        $this->assertSame(self::A, $this->app->make(GuardPrincipalSource::class)->current()->principalId);
        $code = $this->db->table('crm_auth_recovery_codes')->where('identity_id', self::A)->first();
        $this->assertNotSame(self::RECOVERY, $code->code_hash);
        $this->assertNotNull($code->consumed_at);
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->expectException(AuthenticationException::class);
        $this->auth->completeMfa(self::RECOVERY, true);
    }

    public function test_factor_is_encrypted_and_no_secret_is_in_session_listing(): void
    {
        $this->login();
        $c = $this->db->table('crm_auth_credentials')->where('identity_id', self::A)->first();
        $this->assertNotSame(self::SECRET, $c->factor_ciphertext);
        $this->assertNotSame(self::PASSWORD, $c->password_hash);
        $json = json_encode($this->auth->sessions());
        foreach ([self::SECRET, self::PASSWORD, self::RECOVERY, 'token_hash', 'binding'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function test_pending_and_idle_sessions_expire(): void
    {
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->travel(config('identity.pending_seconds') + 1)->seconds();
        try { $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp())); $this->fail('Must expire.'); }
        catch (AuthenticationException) { $this->assertNull($this->guard->user()); }
        $this->login();
        $this->travel(config('identity.idle_seconds') + 1)->seconds();
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_refresh_rotates_and_cannot_extend_absolute_expiry(): void
    {
        $this->login();
        $oldToken = $this->browserSession->get('crm.auth.token');
        $oldId = $this->browserSession->getId();
        $absolute = $this->db->table('crm_auth_sessions')->where('state', 'active')->value('absolute_expires_at');
        $this->auth->refresh();
        $this->assertNotSame($oldToken, $this->browserSession->get('crm.auth.token'));
        $this->assertNotSame($oldId, $this->browserSession->getId());
        $this->assertSame('revoked', $this->db->table('crm_auth_sessions')->where('token_hash', hash('sha256', $oldToken))->value('state'));
        $this->assertSame((int) $absolute, (int) $this->db->table('crm_auth_sessions')->where('state', 'active')->value('absolute_expires_at'));
        $this->travelTo(now()->setTimestamp((int) $absolute));
        $this->expectException(AuthenticationException::class);
        $this->auth->refresh();
    }

    public function test_old_registry_token_is_rejected_after_rotation(): void
    {
        $this->login();
        $old = $this->browserSession->get('crm.auth.token');
        $oldId = $this->browserSession->getId();
        $this->auth->refresh();
        $this->browserSession->put('crm.auth.token', $old);
        $this->browserSession->setId($oldId); // Deliberate synthetic replay.
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_revocation_is_seen_even_when_guard_has_cached_the_user(): void
    {
        $this->login();
        $source = $this->app->make(GuardPrincipalSource::class);
        $this->assertSame(self::A, $source->current()->principalId);
        $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->update(['state' => 'revoked']);
        $this->expectException(AuthenticationException::class);
        $source->current();
    }

    public function test_credential_version_change_invalidates_existing_sessions(): void
    {
        $this->login();
        $this->fixtures->table('crm_auth_credentials')->where('identity_id', self::A)->update(['version' => 2]);
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_pending_challenge_cannot_survive_credential_version_change(): void
    {
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->fixtures->table('crm_auth_credentials')->where('identity_id', self::A)->update(['version' => 2]);
        $this->expectException(AuthenticationException::class);
        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
    }

    public function test_request_input_and_an_injected_model_do_not_supply_registry_proof(): void
    {
        request()->merge(['identity_id' => self::A, 'tenant_id' => self::C]);
        request()->headers->set('X-Identity-Id', self::A);
        request()->setUserResolver(fn () => (new GlobalIdentity())->forceFill(['id' => self::A]));
        try { $this->app->make(GuardPrincipalSource::class)->current(); $this->fail('Request must not authenticate.'); }
        catch (AuthenticationException) { $this->assertSame(0, $this->db->table('crm_auth_sessions')->count()); }
        $this->guard->setUser((new GlobalIdentity())->forceFill(['id' => self::A]));
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_logout_and_account_switch_clear_identity_tenant_and_csrf_token(): void
    {
        $this->login();
        $this->browserSession->put('crm.tenant', self::C);
        $oldCsrf = $this->browserSession->token();
        $this->auth->begin('fixture-b', self::PASSWORD);
        $this->assertNull($this->guard->user());
        $this->assertNull($this->browserSession->get('crm.tenant'));
        $this->assertNotSame($oldCsrf, $this->browserSession->token());
        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
        $this->assertSame(self::B, $this->app->make(GuardPrincipalSource::class)->current()->principalId);
        $this->auth->logout();
        $this->assertNull($this->guard->user());
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->where('state', 'active')->count());
    }

    public function test_another_identity_cannot_use_the_current_session_reference(): void
    {
        $this->login();
        $this->guard->setUser((new GlobalIdentity())->forceFill(['id' => self::B]));
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_copied_reference_is_bound_to_the_server_session_id(): void
    {
        $this->login();
        $this->browserSession->setId(Str::random(40));
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_self_service_revocation_cannot_revoke_another_identity(): void
    {
        $this->login();
        $foreignId = (string) Str::uuid();
        $this->fixtures->table('crm_auth_sessions')->insert([
            'id' => $foreignId, 'identity_id' => self::B, 'token_hash' => hash('sha256', 'foreign-fixture'),
            'state' => 'active', 'credential_version' => 1, 'binding' => hash('sha256', 'foreign-fixture'),
            'created_at' => now()->getTimestamp(), 'last_seen_at' => now()->getTimestamp(),
            'expires_at' => now()->getTimestamp() + 100, 'absolute_expires_at' => now()->getTimestamp() + 100,
            'mfa_verified_at' => now()->getTimestamp(),
        ]);
        $this->auth->revoke($foreignId);
        $this->assertSame('active', $this->db->table('crm_auth_sessions')->where('id', $foreignId)->value('state'));
        $this->assertCount(1, $this->auth->sessions());
        $this->auth->revokeAll();
        $this->assertNull($this->guard->user());
        $this->assertSame('active', $this->db->table('crm_auth_sessions')->where('id', $foreignId)->value('state'));
    }

    public function test_step_up_is_required_and_mfa_replay_does_not_satisfy_it(): void
    {
        $this->login();
        $this->travel(config('identity.step_up_seconds') + 1)->seconds();
        try { $this->auth->revokeAll(); $this->fail('Recent MFA required.'); }
        catch (AuthenticationException) { $this->assertNotNull($this->guard->user()); }
        $this->auth->stepUp((new Totp())->code(self::SECRET, now()->getTimestamp()));
        $this->auth->revokeAll();
        $this->assertNull($this->guard->user());
    }

    public function test_password_attempts_are_limited_across_session_rotation(): void
    {
        for ($i = 0; $i < config('identity.attempts'); $i++) {
            try { $this->auth->begin('fixture-a', 'wrong'); } catch (AuthenticationException) {}
        }
        $this->expectException(AuthenticationException::class);
        $this->auth->begin('fixture-a', self::PASSWORD);
    }

    public function test_mfa_limit_is_shared_across_new_challenges(): void
    {
        for ($i = 0; $i < config('identity.attempts'); $i++) {
            $this->auth->begin('fixture-a', self::PASSWORD);
            try { $this->auth->completeMfa('invalid'); } catch (AuthenticationException) {}
        }
        $limiter = $this->app->make(RateLimiter::class);
        $this->assertSame(config('identity.attempts'), $limiter->attempts('crm.auth.mfa.'.self::A));
        // Isolate the MFA limiter from the independently tested password limiter.
        $limiter->clear('crm.auth.password.'.hash('sha256', 'fixture-a'));
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->expectException(AuthenticationException::class);
        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
    }

    public function test_public_authentication_routes_remain_absent(): void
    {
        foreach (['/login', '/register', '/mfa', '/recovery', '/sessions', '/leads'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_test_only_http_adapter_enforces_csrf_and_secure_cookie_flags(): void
    {
        Route::middleware(['web', EnforcedTestCsrf::class])->post('/_synthetic/auth', function () {
            $this->auth->begin('fixture-a', self::PASSWORD);
            return response()->noContent();
        });
        $this->post('/_synthetic/auth')->assertStatus(419);
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->count());
        $response = $this->withSession(['_token' => 'synthetic-csrf'])->post('/_synthetic/auth', ['_token' => 'synthetic-csrf']);
        $response->assertNoContent();
        $cookies = array_filter($response->headers->getCookies(), fn ($c) => $c->getName() === config('session.cookie'));
        $this->assertNotEmpty($cookies);
        $cookie = array_values($cookies)[0];
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());
    }

    public function test_disabled_profile_cannot_resolve_the_internal_authenticator(): void
    {
        config(['identity.experimental' => false]);
        $this->expectException(\LogicException::class);
        $this->app->make(ExperimentalAuthenticator::class);
    }

    public function test_client_side_session_storage_is_rejected(): void
    {
        config(['session.driver' => 'cookie']);
        $this->expectException(\LogicException::class);
        $this->app->make(ExperimentalAuthenticator::class);
    }

    public function test_production_environment_cannot_use_the_candidate_registry(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\LogicException::class);
        $this->app->make(ExperimentalAuthenticator::class);
    }

    public function test_registry_table_resolver_allowlists_exact_known_identifiers(): void
    {
        $expectedPrefix = $this->db->getDriverName() === 'pgsql' ? 'public.' : '';
        foreach ([
            'crm_global_identities','crm_auth_credentials','crm_auth_sessions',
            'crm_auth_recovery_codes','crm_auth_operations','crm_auth_mandates',
            'crm_iam_audit_events',
        ] as $table) {
            $this->assertSame(
                $expectedPrefix.$table,
                \App\Core\Identity\IamRegistryTable::name($this->db, $table),
            );
        }
        foreach ([
            '', 'crm_memberships', 'crm_auth_credentials; DROP TABLE x',
            'public.crm_auth_credentials', 'crm_auth_credentials" OR 1=1',
            'crm_auth_credentials ', 'CRM_AUTH_CREDENTIALS',
        ] as $invalid) {
            try {
                \App\Core\Identity\IamRegistryTable::name($this->db, $invalid);
                $this->fail('Unlisted IAM table identifier was accepted.');
            } catch (\LogicException $e) {
                $this->assertSame('Unknown IAM registry table.', $e->getMessage());
                $this->assertStringNotContainsString($invalid, $e->getMessage());
            }
        }
    }

    public function test_postgres_iam_login_ignores_hostile_connection_search_path(): void
    {
        if ($this->db->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-only session search_path probe.');
        }

        $prior = $this->db->selectOne("SELECT current_setting('search_path') AS value");
        $this->db->statement('SET search_path TO pg_catalog');
        try {
            $this->assertSame(
                'pg_catalog',
                $this->db->selectOne("SELECT current_setting('search_path') AS value")->value,
            );
            $this->login();
            $this->assertSame(self::A, $this->guard->user()?->getAuthIdentifier());
            $this->assertSame(0, $this->db->transactionLevel());
        } finally {
            $this->db->selectOne(
                "SELECT set_config('search_path', ?, false)",
                [$prior->value],
            );
        }
        $this->assertSame(
            $prior->value,
            $this->db->selectOne("SELECT current_setting('search_path') AS value")->value,
        );
    }

    public function test_postgres_iam_registry_cannot_be_replaced_by_temporary_shadow_tables(): void
    {
        if ($this->db->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-only temporary shadow probe.');
        }
        $permission = $this->db->selectOne(
            "SELECT has_database_privilege(current_user, current_database(), 'TEMPORARY') AS allowed"
        );
        if (!$permission->allowed) {
            $this->markTestSkipped('Runtime IAM role has no TEMP privilege.');
        }

        // Name-only decoys, intentionally missing actual credential/session
        // columns. Any accidental unqualified IAM lookup or audit write fails.
        $this->db->statement('CREATE TEMP TABLE crm_auth_credentials (id uuid) ON COMMIT PRESERVE ROWS');
        $this->db->statement('CREATE TEMP TABLE crm_global_identities (id uuid) ON COMMIT PRESERVE ROWS');
        $this->db->statement('CREATE TEMP TABLE crm_iam_audit_events (id uuid) ON COMMIT PRESERVE ROWS');

        try {
            foreach ([
                'crm_auth_credentials','crm_global_identities','crm_iam_audit_events',
            ] as $table) {
                $resolution = $this->db->selectOne(
                    "SELECT to_regclass(?) <> to_regclass(?) AS shadowed",
                    [$table, 'public.'.$table],
                );
                $this->assertTrue($resolution->shadowed, $table);
            }

            $this->login();
            $this->assertSame(self::A, $this->guard->user()?->getAuthIdentifier());
            $this->assertSame(0, $this->db->transactionLevel());
            $this->assertSame(0, $this->db->table('public.crm_auth_credentials')
                ->where('identity_id', self::B)
                ->where('version', 999)
                ->count());
        } finally {
            foreach ([
                'crm_iam_audit_events','crm_global_identities','crm_auth_credentials',
            ] as $table) {
                $this->db->statement('DROP TABLE IF EXISTS pg_temp.'.$table);
            }
        }

        $this->assertSame(0, $this->db->transactionLevel());
    }

    public function test_postgres_iam_and_business_roles_are_segregated(): void
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('PostgreSQL ACL proof only.'); }
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        config(['database.connections.crm_business_auth_probe' => $settings]);
        $business = DB::connection('crm_business_auth_probe');
        foreach ([[$this->db, 'crm_memberships'], [$business, 'crm_auth_credentials'], [$business, 'crm_auth_sessions'], [$business, 'crm_auth_recovery_codes']] as [$connection, $table]) {
            try { $connection->table($table)->count(); $this->fail('Role must not access the other security domain.'); }
            catch (QueryException $e) { $this->assertSame('42501', $e->errorInfo[0] ?? null); }
        }
        $role = $this->db->selectOne('SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user');
        $this->assertFalse($role->rolsuper);
        $this->assertFalse($role->rolbypassrls);
        $this->expectException(\LogicException::class);
        (new DatabaseSessionAuthority($this->fixtures, $this->browserSession))->transaction(fn () => $this->fail('No privileged runtime.'));
    }

    public function test_postgres_authentication_never_bypasses_membership_or_business_deny(): void
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('PostgreSQL tenant integration only.'); }
        $this->fixtures->table('crm_organizations')->insert([['id' => self::C], ['id' => self::D]]);
        $this->fixtures->table('crm_memberships')->insert([
            ['id' => (string) Str::uuid(), 'organization_id' => self::C, 'global_identity_id' => self::A, 'status' => 'active', 'version' => 1],
            ['id' => (string) Str::uuid(), 'organization_id' => self::D, 'global_identity_id' => self::A, 'status' => 'suspended', 'version' => 1],
        ]);
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME'); $settings['password'] = getenv('RLS_TEST_PASSWORD');
        config(['database.connections.crm_business_auth_probe' => $settings]);
        $store = new \App\Core\Tenancy\PostgresActiveMembershipStore(DB::connection('crm_business_auth_probe'));
        $this->app->instance(\App\Core\Tenancy\ActiveMembershipStore::class, $store);
        $this->login();
        $boundary = $this->app->make(TenantAccessBoundary::class);
        foreach ([self::C, self::D] as $tenant) {
            try { $boundary->execute($tenant, 'lead', 'read', fn () => $this->fail('No business effect.')); $this->fail('Must deny.'); }
            catch (AuthorizationException) { $this->assertSame(0, DB::connection('crm_business_auth_probe')->transactionLevel()); }
        }
        $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => 'disabled']);
        $this->expectException(AuthorizationException::class);
        $boundary->execute(self::C, 'lead', 'read', fn () => $this->fail('Revocation must deny.'));
    }

    public function test_postgres_concurrent_factor_use_waits_for_the_credential_lock(): void
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('PostgreSQL concurrency proof only.'); }
        config(['database.connections.crm_iam_race' => config('database.connections.crm_iam')]);
        $other = DB::connection('crm_iam_race');
        $this->db->beginTransaction();
        try {
            $this->db->table('crm_auth_credentials')->where('identity_id', self::A)->lockForUpdate()->first();
            try {
                $other->transaction(function () use ($other): void {
                    $other->statement("SET LOCAL lock_timeout = '100ms'");
                    $other->table('crm_auth_credentials')->where('identity_id', self::A)->lockForUpdate()->first();
                });
                $this->fail('Concurrent factor consumption must not skip the lock.');
            } catch (QueryException $e) {
                $this->assertSame('55P03', $e->errorInfo[0] ?? null);
            }
        } finally {
            $this->db->rollBack();
            DB::purge('crm_iam_race');
        }
        $this->assertNull($this->db->table('crm_auth_credentials')->where('identity_id', self::A)->value('last_totp_step'));
    }

    public static function revocationKinds(): array
    {
        return ['session' => [false], 'credential epoch' => [true]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('revocationKinds')]
    public function test_postgres_revocation_serializes_with_the_authorization_boundary(bool $credential): void
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('PostgreSQL revocation race proof only.'); }
        $this->fixtures->table('crm_organizations')->insert(['id' => self::C]);
        $this->fixtures->table('crm_memberships')->insert([
            'id' => (string) Str::uuid(), 'organization_id' => self::C,
            'global_identity_id' => self::A, 'status' => 'active', 'version' => 1,
        ]);
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        config(['database.connections.crm_business_race_probe' => $settings]);
        $business = DB::connection('crm_business_race_probe');
        $realStore = new \App\Core\Tenancy\PostgresActiveMembershipStore($business);
        config(['database.connections.crm_iam_revoke_probe' => config('database.connections.crm_iam')]);
        $revoker = $credential ? $this->fixtures : DB::connection('crm_iam_revoke_probe');
        $this->login();
        $source = $this->app->make(GuardPrincipalSource::class);
        $source->current(); // An earlier proof must never substitute for the boundary lock.
        $visits = 0;
        $revoke = function () use ($revoker, $credential): void {
            $revoker->table($credential ? 'crm_auth_credentials' : 'crm_auth_sessions')
                ->where('identity_id', self::A)->update($credential ? ['version' => 2] : ['state' => 'revoked']);
        };
        $probe = function () use ($business, $revoker, $revoke, &$visits): void {
            $visits++;
            $this->assertSame(1, $this->db->transactionLevel());
            $this->assertSame(1, $business->transactionLevel());
            try {
                $revoker->transaction(function () use ($revoker, $revoke): void {
                    $revoker->statement("SET LOCAL lock_timeout = '100ms'");
                    $revoke();
                });
                $this->fail('Concurrent revocation must wait at the authorization boundary.');
            } catch (QueryException $e) {
                $this->assertSame('55P03', $e->errorInfo[0] ?? null);
            }
        };
        // Test-only observer inside the REAL membership transaction. No ALLOW policy.
        $observedStore = new class($realStore, $probe) implements \App\Core\Tenancy\ActiveMembershipStore {
            public function __construct(private $store, private \Closure $probe) {}
            public function within(\App\Core\Identity\PrincipalReference $principal, string $tenantId, \Closure $operation): mixed
            {
                return $this->store->within($principal, $tenantId, function ($membership) use ($operation) {
                    ($this->probe)();
                    return $operation($membership);
                });
            }
        };
        $boundary = new TenantAccessBoundary($source, $observedStore, new \App\Core\Authorization\DenyByDefaultAuthorizer());
        try {
            $boundary->execute(self::C, 'lead', 'read', fn () => $this->fail('Business effects remain denied.'));
            $this->fail('Deny by default required.');
        } catch (AuthorizationException) {
            $this->assertSame(1, $visits);
        }
        $this->assertSame(0, $this->db->transactionLevel());
        $this->assertSame(0, $business->transactionLevel());
        $this->assertSame(0, $revoker->transactionLevel());
        $this->assertTrue(in_array($business->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, ''], true));
        // Membership lookup only, no business effect or ALLOW. Prove the IAM lock
        // also survives a successful inner commit, not only the deny rollback.
        $this->app->make(DatabaseSessionAuthority::class)->withinCurrent(self::A, function () use ($realStore, $business, $revoker, $revoke): void {
            $version = $realStore->within(new \App\Core\Identity\PrincipalReference(self::A), self::C, fn ($membership) => $membership->version);
            $this->assertSame(1, $version);
            $this->assertSame(0, $business->transactionLevel());
            $this->assertSame(1, $this->db->transactionLevel());
            try {
                $revoker->transaction(function () use ($revoker, $revoke): void {
                    $revoker->statement("SET LOCAL lock_timeout = '100ms'");
                    $revoke();
                });
                $this->fail('IAM locks must remain held after the membership commit.');
            } catch (QueryException $e) {
                $this->assertSame('55P03', $e->errorInfo[0] ?? null);
            }
        });
        $revoke(); // After the in-flight unit ends, revocation can commit.
        try {
            $boundary->execute(self::C, 'lead', 'read', fn () => $this->fail('Committed revocation must deny.'));
            $this->fail('Revocation must be re-read despite the cached guard.');
        } catch (AuthenticationException) {
            $this->assertSame(1, $visits); // Not even membership/authorizer reached again.
        }
        $this->assertSame(0, $this->db->transactionLevel());
        DB::purge('crm_iam_revoke_probe');
        DB::purge('crm_business_race_probe');
    }
}

// Laravel skips CSRF during PHPUnit by default. This test-only adapter forces it.
final class EnforcedTestCsrf extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool { return false; }
}
