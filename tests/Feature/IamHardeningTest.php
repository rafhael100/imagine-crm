<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\DatabaseSessionAuthority;
use App\Core\Identity\ExperimentalAuthenticator;
use App\Core\Identity\GlobalIdentity;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\IamAbuseGuard;
use App\Core\Identity\IamAudit;
use App\Core\Identity\IamOperationContext;
use App\Core\Identity\LaboratoryProvisioner;
use App\Core\Identity\Totp;
use App\Core\Identity\TrustedPeerOrigin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class IamHardeningTest extends TestCase
{
    // Avoid Laravel's automatic tearDown{TraitName} hook naming convention.
    use \Tests\Concerns\SyntheticIam { tearDown as protected finishSyntheticFixture; }

    private const E = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
    private array $failedReference = [];

    protected function tearDown(): void
    {
        if (isset($this->fixtures) && $this->fixtures->getDriverName() === 'pgsql') {
            foreach (['crm_auth_recovery_codes', 'crm_auth_sessions', 'crm_auth_credentials'] as $table) {
                $this->fixtures->table($table)->where('identity_id', self::E)->delete();
            }
            $this->fixtures->table('crm_global_identities')->where('id', self::E)->delete();
        }
        $this->finishSyntheticFixture();
    }

    private function denied(\Closure $operation): void
    {
        try { $operation(); $this->fail('Security check must deny.'); }
        catch (AuthenticationException $e) { $this->assertSame('CRM authentication required.', $e->getMessage()); }
    }

    private function provisioner(): LaboratoryProvisioner
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('Administrative role/dual connection proof requires PostgreSQL.'); }
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('PROVISION_TEST_USERNAME');
        $settings['password'] = getenv('PROVISION_TEST_PASSWORD');
        $settings['url'] = null;
        config(['database.connections.crm_provision' => $settings]);
        DB::purge('crm_provision');
        return $this->app->make(LaboratoryProvisioner::class);
    }

    private function mandate(string $actor, string $target, string $action, array $changes = []): string
    {
        $id = (string) Str::uuid();
        $this->fixtures->table('crm_auth_mandates')->insert(array_merge([
            'id' => $id, 'actor_id' => $actor, 'subject_id' => $target, 'action' => $action,
            'actor_version' => 1, 'expires_at' => now()->getTimestamp() + 600,
        ], $changes));
        return $id;
    }

    public function test_audit_correlates_sensitive_actions_without_secret_payloads(): void
    {
        $this->login();
        $token = $this->browserSession->get('crm.auth.token');
        $this->auth->refresh();
        $this->auth->revoke((string) Str::uuid());
        $this->travel(30)->seconds();
        $this->auth->stepUp((new Totp())->code(self::SECRET, now()->getTimestamp()));
        $this->auth->revokeAll();
        $this->auth->begin('fixture-b', self::PASSWORD);
        $this->auth->completeMfa(self::RECOVERY, true);
        $this->auth->logout();
        $events = $this->fixtures->table('crm_iam_audit_events')->whereIn('subject_id', [self::A, self::B])->get();
        foreach (['password', 'mfa', 'recovery', 'step_up', 'refresh', 'revoke', 'revoke_all', 'logout'] as $action) {
            $this->assertTrue($events->contains(fn ($e) => $e->action === $action && $e->outcome === 'succeeded'));
        }
        $json = json_encode($events);
        foreach ([self::PASSWORD, self::SECRET, self::RECOVERY, $token, 'fixture-a', '127.0.0.1', 'password_hash', 'factor_ciphertext'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
        foreach ($events as $event) { $this->assertTrue(Str::isUuid($event->correlation_id)); }
    }

    public function test_unknown_identity_and_wrong_password_stay_neutral_and_audited(): void
    {
        foreach (['fixture-a', 'unknown-synthetic'] as $login) {
            $this->denied(fn () => $this->auth->begin($login, 'wrong-secret-never-log'));
        }
        $this->assertNull($this->guard->user());
        $this->assertGreaterThanOrEqual(2, $this->fixtures->table('crm_iam_audit_events')->where('action', 'password')->where('outcome', 'denied')->count());
        $this->assertStringNotContainsString('wrong-secret-never-log', json_encode($this->fixtures->table('crm_iam_audit_events')->get()));
    }

    public function test_origin_ignores_forwarded_headers_and_refuses_missing_transport_peer(): void
    {
        $source = $this->app->make(TrustedPeerOrigin::class);
        $before = $source->key();
        request()->headers->set('X-Forwarded-For', '192.0.2.45');
        request()->headers->set('Forwarded', 'for=192.0.2.46');
        $this->assertSame($before, $source->key());
        request()->server->set('REMOTE_ADDR', '192.0.2.1');
        $this->assertNotSame($before, $source->key());
        request()->server->remove('REMOTE_ADDR');
        $this->denied(fn () => $source->key());
    }

    public function test_spray_recovery_budget_backoff_and_expiry_are_shared(): void
    {
        config(['identity.abuse_origin_limit' => 3, 'identity.abuse_identity_limit' => 2, 'identity.abuse_backoff' => 2]);
        $guard = $this->app->make(IamAbuseGuard::class);
        $origin = str_repeat('a', 64);
        $guard->admit('password', 'one', $origin);
        $guard->admit('password', 'two', $origin);
        $guard->admit('password', 'three', $origin);
        $this->denied(fn () => $guard->admit('password', 'four', $origin));
        $guard->admit('recovery', 'same-identity', str_repeat('b', 64));
        $guard->failed('recovery', 'same-identity', str_repeat('b', 64));
        $this->denied(fn () => $guard->admit('recovery', 'same-identity', str_repeat('c', 64)));
        $this->travel(3)->seconds();
        $guard->admit('recovery', 'same-identity', str_repeat('c', 64));
        $this->denied(fn () => $guard->admit('recovery', 'same-identity', str_repeat('d', 64)));
        $this->travel(61)->seconds();
        $guard->admit('recovery', 'same-identity', str_repeat('d', 64));
        $this->assertTrue(true); // Successful admission after the explicit window boundary.
    }

    public function test_cache_outage_and_lock_contention_never_admit_authentication(): void
    {
        $cache = $this->app['cache']->store('array');
        $lock = $cache->getStore()->lock('crm.iam.abuse.budgets', 5);
        $this->assertTrue($lock->get());
        $this->denied(fn () => $this->auth->begin('fixture-a', self::PASSWORD));
        $lock->release();
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->where('state', 'pending')->count());
        $broken = \Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
        $broken->shouldReceive('getStore')->andThrow(new \RuntimeException('synthetic-cache-outage-no-sensitive-payload'));
        $this->app->instance(IamAbuseGuard::class, new IamAbuseGuard($broken));
        $this->auth = $this->app->make(ExperimentalAuthenticator::class);
        $this->denied(fn () => $this->auth->begin('fixture-a', self::PASSWORD));
        $this->assertNull($this->guard->user());
        $this->assertNull($this->app->make(IamOperationContext::class)->id);
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->where('state', 'active')->count());
        config(['cache.stores.synthetic_null' => ['driver' => 'null']]);
        $this->denied(fn () => (new IamAbuseGuard($this->app['cache']->store('synthetic_null')))->admit('password', 'x', str_repeat('a', 64)));
    }

    private function faultableSession(): FaultableIamSession
    {
        $store = new FaultableIamSession('synthetic-iam', new ArraySessionHandler(120));
        $store->start();
        $this->app->instance('session.store', $store);
        $this->browserSession = $store;
        $this->app['auth']->forgetGuards();
        $this->guard = $this->app['auth']->guard('crm_identity');
        $this->auth = $this->app->make(ExperimentalAuthenticator::class);
        return $store;
    }

    public function test_sql_rollback_and_compensation_when_token_storage_fails_before_commit(): void
    {
        $session = $this->faultableSession();
        $this->auth->begin('fixture-a', self::PASSWORD);
        $session->failToken = true;
        $this->denied(fn () => $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp())));
        $this->assertNull($this->guard->user());
        $this->assertNull($this->db->table('crm_auth_credentials')->where('identity_id', self::A)->value('last_totp_step'));
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->whereIn('state', ['active', 'pending'])->count());
        $this->assertSame(0, $this->db->transactionLevel());
    }

    public function test_failed_session_save_after_sql_commit_revokes_old_and_new_references(): void
    {
        $session = $this->faultableSession();
        $this->login();
        $oldId = $session->getId(); $oldToken = $session->get('crm.auth.token');
        $session->failSave = true;
        $this->denied(fn () => $this->auth->refresh());
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->where('state', 'active')->count());
        $this->assertNull($this->guard->user());
        $session->failSave = false;
        $session->setId($oldId); $session->put('crm.auth.token', $oldToken);
        $this->guard->setUser((new GlobalIdentity())->forceFill(['id' => self::A]));
        $this->denied(fn () => $this->app->make(GuardPrincipalSource::class)->current());
    }

    public function test_silent_false_session_write_cannot_finalize_a_new_reference(): void
    {
        $store = new Store('synthetic-false-write', new FalseWritingIamHandler(120));
        $store->start();
        $this->app->instance('session.store', $store);
        $this->app['auth']->forgetGuards();
        $this->guard = $this->app['auth']->guard('crm_identity');
        $this->auth = $this->app->make(ExperimentalAuthenticator::class);
        $this->denied(fn () => $this->auth->begin('fixture-a', self::PASSWORD));
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->whereIn('state', ['active', 'pending'])->count());
        $this->assertNull($this->guard->user());
    }

    private function failCompletionAudit(): FaultableIamAudit
    {
        $audit = new FaultableIamAudit();
        $this->app->instance(IamAudit::class, $audit);
        $this->auth = $this->app->make(ExperimentalAuthenticator::class);
        $this->login();
        $this->failedReference = ['id' => $this->browserSession->getId(), 'token' => $this->browserSession->get('crm.auth.token')];
        $audit->failCompletion = true;
        $this->denied(fn () => $this->auth->refresh());
        return $audit;
    }

    public function test_audit_outage_leaves_inconclusive_sessions_unusable_even_with_cached_guard(): void
    {
        $this->failCompletionAudit();
        $op = $this->fixtures->table('crm_auth_operations')->where('action', 'refresh')->where('state', 'prepared')->orderByDesc('created_at')->first();
        $this->assertNotNull($op);
        $this->assertTrue($this->fixtures->table('crm_auth_sessions')->where('security_hold', $op->id)->exists());
        $new = $this->fixtures->table('crm_auth_sessions')->where('operation_id', $op->id)->first();
        $this->assertFalse($this->app->make(DatabaseSessionAuthority::class)->usable($new));
        $this->assertNull($this->app->make(IamOperationContext::class)->id);
        $this->browserSession->setId($this->failedReference['id']);
        $this->browserSession->put('crm.auth.token', $this->failedReference['token']);
        $this->guard->setUser((new GlobalIdentity())->forceFill(['id' => self::A]));
        $this->denied(fn () => $this->app->make(GuardPrincipalSource::class)->current());
    }

    public function test_postgres_internal_provisioning_is_scoped_confirmed_and_idempotent(): void
    {
        $provisioner = $this->provisioner();
        $this->login();
        $mandate = $this->mandate(self::A, self::E, 'provision');
        $id = (string) Str::uuid();
        $invoke = fn () => $provisioner->credential($mandate, $id, self::E, 'lab-new', self::PASSWORD, self::SECRET, (new Totp())->code(self::SECRET, now()->getTimestamp()));
        $result = $invoke();
        $this->assertSame('completed', $result['status']);
        $this->assertCount(6, $result['recovery_codes']);
        $this->assertSame(['status' => 'already_completed'], $invoke());
        $c = $this->fixtures->table('crm_auth_credentials')->where('identity_id', self::E)->first();
        $this->assertSame(1, (int) $c->version);
        $this->assertNotSame(self::PASSWORD, $c->password_hash);
        $this->assertNotSame(self::SECRET, $c->factor_ciphertext);
        $this->assertSame(0, $this->fixtures->table('crm_memberships')->where('global_identity_id', self::E)->count());
        $audit = json_encode($this->fixtures->table('crm_iam_audit_events')->where('correlation_id', $id)->get());
        foreach (array_merge([self::PASSWORD, self::SECRET], $result['recovery_codes']) as $secret) { $this->assertStringNotContainsString($secret, $audit); }
        $this->fixtures->table('crm_auth_mandates')->where('id', $mandate)->update(['revoked_at' => now()->getTimestamp()]);
        $this->denied($invoke); // Idempotency never bypasses current authority.
    }

    public function test_postgres_spoofed_expired_wrong_scope_self_grants_and_stale_mfa_deny(): void
    {
        $provisioner = $this->provisioner();
        $this->login();
        request()->merge(['actor_id' => self::B, 'role' => 'superadmin', 'verified' => true]);
        foreach ([['actor_id' => self::B], ['expires_at' => now()->getTimestamp()], ['revoked_at' => 1], ['action' => 'rotate'], ['subject_id' => self::B], ['actor_version' => 2]] as $change) {
            $mandate = $this->mandate(self::A, self::E, 'provision', $change);
            $this->denied(fn () => $provisioner->credential($mandate, (string) Str::uuid(), self::E, 'lab-new', self::PASSWORD, self::SECRET, (new Totp())->code(self::SECRET, now()->getTimestamp())));
        }
        $mandate = $this->mandate(self::A, self::A, 'issue_recovery');
        $this->denied(fn () => $provisioner->recovery($mandate, (string) Str::uuid(), self::A, 1));
        $this->travel(301)->seconds();
        $mandate = $this->mandate(self::A, self::B, 'issue_recovery');
        $this->denied(fn () => $provisioner->recovery($mandate, (string) Str::uuid(), self::B, 1));
        $this->assertFalse($this->fixtures->table('crm_global_identities')->where('id', self::E)->exists());
    }

    public function test_postgres_rotation_recovery_and_audit_failure_do_not_bypass_epoch(): void
    {
        $this->provisioner();
        $audit = new FaultableIamAudit(); $this->app->instance(IamAudit::class, $audit);
        $this->login();
        $provisioner = $this->app->make(LaboratoryProvisioner::class);
        $mandate = $this->mandate(self::A, self::B, 'rotate');
        $id = (string) Str::uuid();
        $invoke = fn () => $provisioner->credential($mandate, $id, self::B, 'lab-b', self::PASSWORD, self::SECRET, (new Totp())->code(self::SECRET, now()->getTimestamp()), 1);
        $audit->failCompletion = true; $this->denied($invoke);
        $this->assertSame(1, (int) $this->fixtures->table('crm_auth_credentials')->where('identity_id', self::B)->value('version'));
        $this->assertFalse($this->fixtures->table('crm_auth_operations')->where('id', $id)->exists());
        $audit->failCompletion = false; $this->assertSame(2, $invoke()['version']);
        $mandate = $this->mandate(self::A, self::B, 'issue_recovery');
        $result = $provisioner->recovery($mandate, (string) Str::uuid(), self::B, 2);
        $this->assertSame(3, $result['version']);
        $this->assertCount(6, $result['recovery_codes']);
        $this->denied(fn () => $provisioner->recovery($mandate, (string) Str::uuid(), self::B, 2));
    }

    public function test_postgres_reconciliation_only_revokes_and_is_idempotent_under_mandate(): void
    {
        $this->provisioner();
        $audit = $this->failCompletionAudit();
        $op = $this->fixtures->table('crm_auth_operations')->where('action', 'refresh')->where('state', 'prepared')->orderByDesc('created_at')->first();
        $audit->failCompletion = false;
        $this->browserSession->invalidate(); $this->guard->forgetUser();
        $this->login('fixture-b');
        $mandate = $this->mandate(self::B, self::A, 'reconcile');
        $provisioner = $this->app->make(LaboratoryProvisioner::class);
        $request = (string) Str::uuid();
        $this->assertSame('completed', $provisioner->reconcile($mandate, $request, self::A, $op->id)['status']);
        $this->assertSame('already_completed', $provisioner->reconcile($mandate, $request, self::A, $op->id)['status']);
        $this->assertSame('compensated', $this->fixtures->table('crm_auth_operations')->where('id', $op->id)->value('state'));
        $this->assertSame(0, $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->where('state', 'active')->count());
        $this->assertSame(self::B, $this->app->make(GuardPrincipalSource::class)->current()->principalId);
    }

    public function test_postgres_roles_cannot_edit_audit_create_mandates_or_cross_business_domain(): void
    {
        $this->provisioner(); $this->login();
        foreach ([$this->db, DB::connection('crm_provision')] as $runtime) {
            foreach (['crm_iam_audit_events', 'crm_auth_mandates'] as $table) {
                try { $runtime->table($table)->insert(['id' => (string) Str::uuid()]); if ($table === 'crm_iam_audit_events') { continue; } $this->fail('No runtime mandate grant.'); }
                catch (QueryException $e) { $this->assertContains($e->errorInfo[0] ?? null, $table === 'crm_auth_mandates' ? ['42501'] : ['23502']); }
            }
            try { $runtime->table('crm_iam_audit_events')->where('subject_id', self::A)->update(['outcome' => 'succeeded']); $this->fail('Append-only audit required.'); }
            catch (QueryException $e) { $this->assertSame('42501', $e->errorInfo[0] ?? null); }
        }
        try { DB::connection('crm_provision')->table('crm_memberships')->count(); $this->fail('No business grants.'); }
        catch (QueryException $e) { $this->assertSame('42501', $e->errorInfo[0] ?? null); }
    }

    public function test_postgres_shared_redis_budgets_and_contended_lock_fail_closed(): void
    {
        if (!getenv('IAM_ABUSE_REDIS_TEST')) { $this->markTestSkipped('Real Redis integration runs in isolated PostgreSQL CI.'); }
        config(['identity.abuse_origin_limit' => 2, 'identity.abuse_identity_limit' => 2]);
        $cache = $this->app['cache']->store('redis');
        $key = bin2hex(random_bytes(32));
        $one = new IamAbuseGuard($cache); $two = new IamAbuseGuard($this->app['cache']->store('redis'));
        $one->admit('password', $key, $key); $two->admit('password', $key, $key);
        $this->denied(fn () => $one->admit('password', $key, $key));
        $lock = $cache->getStore()->lock('crm.iam.abuse.budgets', 5); $this->assertTrue($lock->get());
        try { $this->denied(fn () => $two->admit('password', 'new', str_repeat('e', 64))); }
        finally { $lock->release(); }
    }
}

final class FaultableIamSession extends Store
{
    public bool $failSave = false;
    public bool $failToken = false;
    public function save(): void { if ($this->failSave) { throw new \RuntimeException('Synthetic session save failure.'); } parent::save(); }
    public function put($key, $value = null): void { if ($this->failToken && $key === 'crm.auth.token') { throw new \RuntimeException('Synthetic token write failure.'); } parent::put($key, $value); }
}

final class FalseWritingIamHandler extends ArraySessionHandler
{
    public function write($sessionId, $data): bool { return false; }
}

final class FaultableIamAudit extends IamAudit
{
    public bool $failCompletion = false;
    public function append(Connection $db, string $correlation, string $action, string $outcome, ?string $actor = null, ?string $subject = null, ?string $origin = null): void
    {
        if ($this->failCompletion && $outcome !== 'attempted') { throw new \RuntimeException('Synthetic audit outage.'); }
        parent::append($db, $correlation, $action, $outcome, $actor, $subject, $origin);
    }
}
