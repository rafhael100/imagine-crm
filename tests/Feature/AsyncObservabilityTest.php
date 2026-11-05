<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncFailureKind as Kind;
use App\Core\Async\AsyncState;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use Closure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\AsyncCases as Cases;
use Tests\Fixtures\SyntheticAsyncObserver;
use Tests\TestCase;

final class AsyncObservabilityTest extends TestCase
{
    use \Tests\Concerns\SyntheticIam;

    public static function classes(): array { return Cases::classes(); }

    #[DataProvider('classes')]
    public function test_all_classes_emit_only_sanitized_partial_observations_without_dispatch(string $class): void
    {
        if ($class === 'A') { $this->login(); }
        Bus::fake(); Queue::fake(); Event::fake(); Mail::fake(); Http::fake();
        $observer = new SyntheticAsyncObserver(); $bridge = $this->bridge($this->membership(), $observer);
        $input = Cases::request($class); $result = $bridge->inspect($input);
        $this->assertSame(Kind::AuthorityUnproven, $result->diagnostic->kind);
        $this->assertFalse($result->decision->allowed); $this->assertSame(AsyncState::Denied, $result->lifecycle->state);
        $this->assertCount(1, $observer->signals); $signal = $observer->signals[0];
        $this->assertSame($class, $signal['execution_class_claim']);
        $this->assertSame('unverified_claim', $signal['class_evidence']);
        $this->assertSame($result->decision->correlationId, $signal['correlation_id']);
        $this->assertSame($signal['correlation_id'], $signal['causation_id']);
        foreach ([self::A, self::C, self::D, Cases::SERVICE, Cases::OPERATION, $input['correlation_id'], $input['causation_id']] as $claim) {
            $this->assertStringNotContainsString($claim, json_encode($signal));
        }
        Bus::assertNothingDispatched(); Queue::assertNothingPushed(); Event::assertNothingDispatched();
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_payload_cannot_choose_a_diagnostic_or_provide_observation_data(): void
    {
        $observer = new SyntheticAsyncObserver(); $bridge = $this->bridge($this->membership(), $observer);
        $input = Cases::request(); $input['classification'] = 'session_revoked';
        $input['source'] = 'session_registry'; $input['token'] = 'synthetic-token';
        $input['payload'] = ['email' => 'synthetic@example.invalid'];
        $result = $bridge->inspect($input);
        $this->assertSame(Kind::ContractInvalid, $result->diagnostic->kind);
        $this->assertNull($result->observation->executionClass);
        foreach (['session_revoked', 'session_registry', 'synthetic-token', 'synthetic@example.invalid', self::A] as $value) {
            $this->assertStringNotContainsString($value, json_encode($observer->signals));
        }
        $this->assertFalse($result->decision->allowed);
    }

    public static function sessionFailures(): array { return [['revoked', Kind::SessionRevoked], ['mfa', Kind::SessionUnproven], ['expired', Kind::SessionUnproven]]; }

    #[DataProvider('sessionFailures')]
    public function test_registry_proven_revocation_is_distinct_from_other_unproven_session_conditions(string $change, Kind $expected): void
    {
        $this->login(); $observer = new SyntheticAsyncObserver();
        $bridge = $this->bridge($this->membership(), $observer);
        $this->assertSame(Kind::AuthorityUnproven, $bridge->inspect(Cases::request())->diagnostic->kind);
        $values = match ($change) { 'revoked' => ['state' => 'revoked'], 'mfa' => ['mfa_verified_at' => null], 'expired' => ['absolute_expires_at' => 1] };
        $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->where('state', 'active')->update($values);
        $result = $bridge->inspect(Cases::request());
        $this->assertSame($expected, $result->diagnostic->kind); $this->assertFalse($result->decision->allowed);
        $this->assertSame(0, $this->db->transactionLevel());
        // A mismatched session binding cannot be classified as a proven revocation.
        $this->browserSession->setId(str_repeat('x', 40));
        $this->assertSame(Kind::SessionUnproven, $bridge->inspect(Cases::request())->diagnostic->kind);
    }

    public function test_unknown_failure_is_sanitized_after_iam_rollback_and_observer_failure_never_retries(): void
    {
        $this->login(); $observer = new SyntheticAsyncObserver(); $observer->fail = true;
        $store = $this->membership(static fn () => throw new \RuntimeException('session_revoked synthetic-token synthetic@example.invalid'));
        $result = $this->bridge($store, $observer)->inspect(Cases::request());
        $this->assertSame(Kind::UnexpectedFailure, $result->diagnostic->kind);
        $this->assertSame(Kind::ObserverUnavailable, $result->observabilityFailure());
        $this->assertTrue($result->observationFailed); $this->assertSame(1, $observer->attempts);
        $this->assertFalse($result->decision->allowed); $this->assertSame(0, $this->db->transactionLevel());
        $this->assertSame(AsyncState::Denied, $result->lifecycle->state);
        $signal = json_encode($result->observation->signal());
        foreach (['synthetic-token', 'synthetic@example.invalid', 'session_revoked', 'RuntimeException'] as $secret) {
            $this->assertStringNotContainsString($secret, $signal);
        }
        $this->assertSame(Kind::AuthorityUnproven, $this->bridge($this->membership(), new SyntheticAsyncObserver())->inspect(Cases::request())->diagnostic->kind);
    }

    public function test_real_postgres_membership_contamination_lock_and_reuse_fail_closed(): void
    {
        $business = $this->business(); $this->login(); $observer = new SyntheticAsyncObserver();
        $bridge = $this->bridge(new PostgresActiveMembershipStore($business), $observer);
        $this->assertSame(Kind::AuthorityUnproven, $bridge->inspect(Cases::request())->diagnostic->kind);
        $input = Cases::request(); $input['tenant_id'] = $input['executor']['tenant_id'] = $input['intent']['scope']['id'] = self::D;
        $this->assertSame(Kind::MembershipDenied, $bridge->inspect($input)->diagnostic->kind);
        $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => 'disabled', 'version' => 2]);
        $this->assertSame(Kind::MembershipDenied, $bridge->inspect(Cases::request())->diagnostic->kind);
        $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => 'active', 'version' => 3]);
        $business->selectOne("SELECT set_config('app.tenant_id', ?, false)", [self::D]);
        try {
            $this->assertSame(Kind::ContextContamination, $bridge->inspect(Cases::request())->diagnostic->kind);
            $this->assertSame(0, $business->transactionLevel()); $this->assertSame(0, $this->db->transactionLevel());
            $this->assertSame(self::D, $business->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant);
        } finally { $business->selectOne("SELECT set_config('app.tenant_id', '', false)"); }
        $business->statement("SET lock_timeout = '100ms'");
        $this->fixtures->beginTransaction();
        try {
            $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->lockForUpdate()->first();
            $result = $bridge->inspect(Cases::request());
            $this->assertSame(Kind::ConcurrencyBlocked, $result->diagnostic->kind); $this->assertFalse($result->decision->allowed);
            $this->assertSame(0, $business->transactionLevel()); $this->assertSame(0, $this->db->transactionLevel());
            $this->assertTrue(in_array($business->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, ''], true));
        } finally { $this->fixtures->rollBack(); $business->statement("SET lock_timeout = '0'"); }
        foreach (['A', 'B', 'C', 'D', 'E', 'A'] as $class) {
            $this->assertSame(Kind::AuthorityUnproven, $bridge->inspect(Cases::request($class))->diagnostic->kind);
            $this->assertSame(0, $business->transactionLevel()); $this->assertSame(0, $this->db->transactionLevel());
            $this->assertTrue(in_array($business->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, ''], true));
        }
    }

    public function test_postgres_unavailable_connection_is_not_misreported_as_missing_authority(): void
    {
        $this->business(); $this->login();
        $settings = config('database.connections.crm_observation_business');
        $settings['port'] = 1; $settings['connect_timeout'] = 1;
        config(['database.connections.crm_observation_offline' => $settings]);
        $offline = DB::connection('crm_observation_offline');
        try {
            $result = $this->bridge(new PostgresActiveMembershipStore($offline), new SyntheticAsyncObserver())->inspect(Cases::request());
            $this->assertSame(Kind::InfrastructureUnavailable, $result->diagnostic->kind);
            $this->assertFalse($result->decision->allowed); $this->assertSame(0, $this->db->transactionLevel());
            $this->assertSame(0, $offline->transactionLevel());
        } finally { DB::purge('crm_observation_offline'); }
    }

    public function test_real_redis_failure_is_classified_without_sending_observations_or_losing_iam_rollback(): void
    {
        if (!getenv('IAM_ABUSE_REDIS_TEST')) { $this->markTestSkipped('Requires ephemeral Redis and phpredis in PostgreSQL CI.'); }
        $this->login();
        $this->assertNotEmpty(Redis::connection()->ping());
        $settings = config('database.redis.default');
        $settings['url'] = null; $settings['host'] = '127.0.0.1'; $settings['port'] = 1;
        $settings['timeout'] = 0.1; $settings['read_timeout'] = 0.1; $settings['max_retries'] = 0;
        config(['database.redis.crm_async_offline' => $settings]);
        $store = $this->membership(static fn () => Redis::connection('crm_async_offline')->get('synthetic-only'));
        try {
            $result = $this->bridge($store, new SyntheticAsyncObserver())->inspect(Cases::request());
            $this->assertSame(Kind::InfrastructureUnavailable, $result->diagnostic->kind);
            $this->assertSame('redis_driver', $result->diagnostic->source);
            $this->assertFalse($result->decision->allowed); $this->assertSame(0, $this->db->transactionLevel());
        } finally { Redis::purge('crm_async_offline'); }
    }

    public function test_disabled_profile_and_production_do_not_invoke_even_a_failing_observer(): void
    {
        $observer = new SyntheticAsyncObserver(); $observer->fail = true;
        $bridge = $this->bridge($this->membership(), $observer);
        config(['identity.experimental' => false]);
        $this->assertSame(Kind::LaboratoryDisabled, $bridge->inspect(Cases::request())->diagnostic->kind);
        config(['identity.experimental' => true]); $this->app->instance('env', 'production');
        $this->assertSame(Kind::LaboratoryDisabled, $bridge->inspect(Cases::request())->diagnostic->kind);
        $this->app->instance('env', 'testing');
        $this->assertSame(0, $observer->attempts);
        $this->assertInstanceOf(DenyByDefaultAuthorizer::class, $this->app->make(Authorizer::class));
        foreach (['/observations', '/reconciliation', '/async', '/jobs', '/webhooks'] as $path) { $this->get($path)->assertNotFound(); }
    }

    private function bridge(ActiveMembershipStore $store, SyntheticAsyncObserver $observer): AsyncAuthorityBridge
    {
        return new AsyncAuthorityBridge(new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class), $store, new DenyByDefaultAuthorizer()), $observer);
    }

    private function membership(?Closure $probe = null): ActiveMembershipStore
    {
        return new class($probe) implements ActiveMembershipStore {
            public function __construct(private ?Closure $probe) {}
            public function within(PrincipalReference $principal, string $tenantId, Closure $operation): mixed
            {
                if ($this->probe !== null) { ($this->probe)(); }
                return $operation(new MembershipSnapshot($tenantId, $principal->principalId, MembershipStatus::Active, 1));
            }
        };
    }

    private function business(): \Illuminate\Database\Connection
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('Requires actual PostgreSQL 16 role, RLS and row-lock behavior.'); }
        $this->fixtures->table('crm_organizations')->insert([['id' => self::C], ['id' => self::D]]);
        $this->fixtures->table('crm_memberships')->insert([
            ['id' => (string) Str::uuid(), 'organization_id' => self::C, 'global_identity_id' => self::A, 'status' => 'active', 'version' => 1],
            ['id' => (string) Str::uuid(), 'organization_id' => self::D, 'global_identity_id' => self::A, 'status' => 'suspended', 'version' => 1],
        ]);
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME'); $settings['password'] = getenv('RLS_TEST_PASSWORD'); $settings['url'] = null;
        config(['database.connections.crm_observation_business' => $settings]);
        return DB::connection('crm_observation_business');
    }
}
