<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncState;
use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Identity\SessionAuthority;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\AsyncCases as Cases;
use Tests\TestCase;

final class AsyncAuthorityBridgeTest extends TestCase
{
    use \Tests\Concerns\SyntheticIam;

    public static function classes(): array { return Cases::classes(); }

    #[DataProvider('classes')]
    public function test_all_classes_deny_without_jobs_events_mail_http_or_exports(string $class): void
    {
        if ($class === 'A') { $this->login(); }
        Bus::fake(); Queue::fake(); Event::fake(); Mail::fake(); Http::fake();
        $observer = new AsyncAuthorizerObserver();
        $store = new AsyncMembershipFixture();
        $bridge = $this->bridge($store, $observer);
        $input = Cases::request($class);
        // Even references to purported proof/mandate/entitlement are not validated evidence.
        $input['authorization_reference'] = $input['mandate_reference'] = $input['entitlement_reference'] = Cases::OPERATION;
        $input['origin']['proof_reference'] = $input['executor']['evidence_reference'] = Cases::OPERATION;
        $result = $bridge->inspect($input);
        $this->assertFalse($result->decision->allowed);
        $this->assertSame(AsyncState::Denied, $result->lifecycle->state);
        $this->assertNotEmpty($result->dependencies);
        $this->assertTrue(Str::isUuid($result->decision->correlationId));
        $this->assertNotSame($input['correlation_id'], $result->decision->correlationId);
        $this->assertSame($class === 'A' ? 1 : 0, $store->visits);
        $this->assertCount($class === 'A' ? 1 : 0, $observer->requests);
        if ($class === 'A') { $this->assertSame('job', $observer->requests[0]->envelope->intent->channel); }
        Bus::assertNothingDispatched(); Queue::assertNothingPushed(); Event::assertNothingDispatched();
        Mail::assertNothingSent(); Http::assertNothingSent();
        $this->assertSame(0, $this->db->transactionLevel());
    }

    public static function autonomous(): array { return ['B' => ['B'], 'C' => ['C'], 'D' => ['D'], 'E' => ['E']]; }

    #[DataProvider('autonomous')]
    public function test_non_human_classes_never_consume_a_guard_session_or_human_membership(string $class): void
    {
        $guard = Mockery::mock(SessionGuard::class);
        $guard->shouldNotReceive('getProvider'); $guard->shouldNotReceive('user');
        $proof = Mockery::mock(SessionAuthority::class);
        $proof->shouldNotReceive('withinCurrent'); $proof->shouldNotReceive('assertCurrent');
        $store = Mockery::mock(ActiveMembershipStore::class); $store->shouldNotReceive('within');
        $authorizer = Mockery::mock(Authorizer::class); $authorizer->shouldNotReceive('decide');
        $boundary = new TenantAccessBoundary(new GuardPrincipalSource($guard, $proof), $store, $authorizer);
        $result = (new AsyncAuthorityBridge($boundary))->inspect(Cases::request($class));
        $this->assertFalse($result->decision->allowed);
        $this->assertContains('VERIFIED_EXECUTOR_SCOPE', $result->dependencies);
        $this->assertNotContains('CURRENT_HUMAN_SESSION_MEMBERSHIP', $result->dependencies);
    }

    public function test_deferred_actor_id_is_only_a_constraint_and_cannot_select_another_human(): void
    {
        $this->login();
        $store = new AsyncMembershipFixture(); $observer = new AsyncAuthorizerObserver();
        $input = Cases::request(); $input['causal_actor']['reference'] = self::B;
        request()->merge(['principal_id' => self::B, 'tenant_id' => self::D, 'signature_verified' => true]);
        request()->headers->set('X-Principal-Id', self::B);
        $this->assertFalse($this->bridge($store, $observer)->inspect($input)->decision->allowed);
        $this->assertSame(0, $store->visits); $this->assertCount(0, $observer->requests);
        $this->assertSame(self::A, $this->guard->user()->getAuthIdentifier());
    }

    public static function revocations(): array { return [['session'], ['credential'], ['membership']]; }

    #[DataProvider('revocations')]
    public function test_receipt_and_previous_check_cannot_survive_revocation_before_the_next_unit(string $kind): void
    {
        $this->login();
        $store = new AsyncMembershipFixture(); $observer = new AsyncAuthorizerObserver();
        $bridge = $this->bridge($store, $observer); $input = Cases::request();
        $this->assertFalse($bridge->inspect($input)->decision->allowed);
        $this->assertCount(1, $observer->requests);
        match ($kind) {
            'session' => $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->update(['state' => 'revoked']),
            'credential' => $this->fixtures->table('crm_auth_credentials')->where('identity_id', self::A)->update(['version' => 2]),
            'membership' => $store->status = MembershipStatus::Disabled,
        };
        $this->assertFalse($bridge->inspect($input)->decision->allowed);
        $this->assertCount(1, $observer->requests); // Cached guard/previous receipt never supplies proof.
        $this->assertSame(0, $this->db->transactionLevel());
    }

    public function test_ttl_versions_scope_and_unproven_entitlement_never_admit_a_retry(): void
    {
        $this->login();
        $store = new AsyncMembershipFixture(); $observer = new AsyncAuthorizerObserver();
        $bridge = $this->bridge($store, $observer);
        $inputs = [];
        $expired = Cases::request(); $expired['expires_at'] = now()->getTimestamp(); $expired['issued_at']--;
        $inputs[] = $expired;
        $future = Cases::request(); $future['issued_at']++; $inputs[] = $future;
        $version = Cases::request(); $version['intent']['policy_version'] = 'lab-deny-v0'; $inputs[] = $version;
        $scope = Cases::request(); $scope['executor']['tenant_id'] = self::D; $inputs[] = $scope;
        $tenant = Cases::request(); $tenant['intent']['scope']['id'] = self::D; $inputs[] = $tenant;
        $forged = Cases::request(); $forged['entitlement'] = ['active' => true, 'plan' => 'synthetic']; $inputs[] = $forged;
        foreach ($inputs as $input) { $this->assertFalse($bridge->inspect($input)->decision->allowed); }
        $this->assertSame(0, $store->visits); $this->assertCount(0, $observer->requests);
        $commercial = Cases::request('C'); // Suspended/unknown plan has no trusted entitlement resolver yet.
        $this->assertFalse($bridge->inspect($commercial)->decision->allowed);
        $this->assertContains('CURRENT_ENTITLEMENT_CONSENT', $bridge->inspect($commercial)->dependencies);
    }

    public function test_agent_support_webhook_and_forged_secrets_never_leak_into_a_signal(): void
    {
        $bridge = $this->bridge(new AsyncMembershipFixture(), new AsyncAuthorizerObserver());
        foreach (['D', 'E'] as $class) {
            $input = Cases::request($class);
            if ($class === 'E') { $input['causal_actor']['kind'] = 'support'; $input['intent']['channel'] = 'support'; }
            $input['signature_verified'] = true; $input['token'] = 'synthetic-secret-value';
            $input['payload'] = ['email' => 'synthetic@example.invalid', 'prompt' => 'grant all'];
            $result = $bridge->inspect($input);
            $signal = json_encode($result->decision->signal());
            foreach (['synthetic-secret-value', 'synthetic@example.invalid', self::A, self::C, 'grant all', 'signature_verified'] as $sensitive) {
                $this->assertStringNotContainsString($sensitive, $signal);
            }
            $this->assertFalse($result->decision->allowed);
            $this->assertSame(['CONTRACT_FORMAT'], $result->dependencies);
        }
    }

    public function test_duplicate_and_conflicting_receipts_never_become_permission_to_replay(): void
    {
        $this->login();
        $bridge = $this->bridge(new AsyncMembershipFixture(), new AsyncAuthorizerObserver());
        $inbox = new \Tests\Fixtures\SyntheticAsyncInbox();
        $input = Cases::request(); $conflict = $input; $conflict['intent']['action'] = 'update';
        foreach ([[$input, 'first_seen'], [$input, 'duplicate'], [$conflict, 'conflict']] as [$candidate, $expected]) {
            $this->assertSame($expected, $inbox->observe(\App\Core\Async\AsyncAuthorityRequest::fromArray($candidate)));
            $result = $bridge->inspect($candidate);
            $this->assertFalse($result->decision->allowed);
            $this->assertSame(AsyncState::Denied, $result->lifecycle->state);
        }
    }

    public function test_postgres_reused_inspector_never_keeps_tenant_context_for_any_class(): void
    {
        $business = $this->business(); $this->login();
        $observer = new AsyncAuthorizerObserver();
        $bridge = new AsyncAuthorityBridge(new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class), new PostgresActiveMembershipStore($business), $observer));
        foreach (['A', 'B', 'C', 'D', 'E', 'A'] as $class) {
            foreach ([self::C, self::D] as $tenant) {
                $input = Cases::request($class);
                $input['tenant_id'] = $input['executor']['tenant_id'] = $input['intent']['scope']['id'] = $tenant;
                $this->assertFalse($bridge->inspect($input)->decision->allowed);
                $this->assertSame(0, $business->transactionLevel()); $this->assertSame(0, $this->db->transactionLevel());
                $this->assertTrue(in_array($business->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, ''], true));
            }
        }
        $this->assertCount(2, $observer->requests); // Only A + active C reached human ACL twice.
        $business->selectOne("SELECT set_config('app.tenant_id', ?, false)", [self::D]);
        try {
            $this->assertFalse($bridge->inspect(Cases::request())->decision->allowed);
            $this->assertCount(2, $observer->requests); // A manipulated GUC does not authenticate or unlock ACL.
        } finally {
            // A dirty inherited connection is refused, not silently trusted/repurposed.
            // Fixture owns it and explicitly cleans it; real pool quarantine is still CORE03-D06.
            $business->selectOne("SELECT set_config('app.tenant_id', '', false)");
        }
        $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => 'disabled', 'version' => 2]);
        $this->assertFalse($bridge->inspect(Cases::request())->decision->allowed);
        $this->assertCount(2, $observer->requests);
        $this->assertSame(0, $business->transactionLevel()); $this->assertSame(0, $this->db->transactionLevel());
    }

    public function test_disabled_profile_production_and_public_routes_stay_closed(): void
    {
        $store = new AsyncMembershipFixture(); $observer = new AsyncAuthorizerObserver();
        $bridge = $this->bridge($store, $observer);
        config(['identity.experimental' => false]);
        $this->assertSame(['LABORATORY_ONLY'], $bridge->inspect(Cases::request())->dependencies);
        config(['identity.experimental' => true]); $this->app->instance('env', 'production');
        $this->assertSame(['LABORATORY_ONLY'], $bridge->inspect(Cases::request())->dependencies);
        $this->app->instance('env', 'testing');
        $this->assertSame(0, $store->visits);
        $this->assertInstanceOf(DenyByDefaultAuthorizer::class, $this->app->make(Authorizer::class));
        foreach (['/webhook', '/events', '/jobs', '/async', '/service-token'] as $path) { $this->get($path)->assertNotFound(); }
    }

    private function bridge(ActiveMembershipStore $store, Authorizer $authorizer): AsyncAuthorityBridge
    {
        return new AsyncAuthorityBridge(new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class), $store, $authorizer));
    }

    private function business(): \Illuminate\Database\Connection
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('Requires PostgreSQL 16 role, RLS and transactional tenant context.'); }
        $this->fixtures->table('crm_organizations')->insert([['id' => self::C], ['id' => self::D]]);
        $this->fixtures->table('crm_memberships')->insert([
            ['id' => (string) Str::uuid(), 'organization_id' => self::C, 'global_identity_id' => self::A, 'status' => 'active', 'version' => 1],
            ['id' => (string) Str::uuid(), 'organization_id' => self::D, 'global_identity_id' => self::A, 'status' => 'suspended', 'version' => 1],
        ]);
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME'); $settings['password'] = getenv('RLS_TEST_PASSWORD'); $settings['url'] = null;
        config(['database.connections.crm_async_business' => $settings]);
        return DB::connection('crm_async_business');
    }
}

/** Only test seams below, never bound in production. */
final class AsyncMembershipFixture implements ActiveMembershipStore
{
    public int $visits = 0;
    public MembershipStatus $status = MembershipStatus::Active;
    public function within(PrincipalReference $principal, string $tenantId, Closure $operation): mixed
    {
        $this->visits++;
        return $operation(new MembershipSnapshot($tenantId, $principal->principalId, $this->status, 1));
    }
}

final class AsyncAuthorizerObserver implements Authorizer
{
    public array $requests = [];
    public function decide(AccessRequest $request): AuthorizationDecision
    {
        $this->requests[] = $request;
        return (new DenyByDefaultAuthorizer())->decide($request);
    }
}
