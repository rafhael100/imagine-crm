<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GlobalIdentity;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantAccessBoundary;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard as Guard;
use Illuminate\Contracts\Auth\Factory;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class TenantAccessBoundaryTest extends TestCase
{
    private const ID = '33333333-3333-4333-8333-333333333333';
    private const A = '11111111-1111-4111-8111-111111111111';
    private const B = '22222222-2222-4222-8222-222222222222';

    public function test_anonymous_input_cannot_impersonate_a_principal(): void
    {
        $request = request();
        $request->headers->set('X-Tenant-Id', self::A);
        $request->merge(['user_id' => self::ID, 'tenant_id' => self::A]);
        $request->setUserResolver(fn () => $this->identity());
        $guard = $this->crmGuard();
        $guard->shouldReceive('user')->once()->andReturnNull();
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldNotReceive('within');

        $boundary = new TenantAccessBoundary(new GuardPrincipalSource($guard, $this->verifiedSession()), $store, new DenyByDefaultAuthorizer());
        $this->expectException(AuthenticationException::class);
        $boundary->execute(self::A, 'lead', 'read', fn () => $this->fail('Must not run.'));
    }

    public function test_scaffold_user_id_is_not_a_global_identity_mapping(): void
    {
        $guard = $this->crmGuard();
        $guard->shouldReceive('user')->andReturn((new User())->forceFill(['id' => self::ID]));
        $this->expectException(AuthenticationException::class);
        (new GuardPrincipalSource($guard, $this->verifiedSession()))->current();
    }

    public function test_guard_is_read_again_instead_of_caching_a_principal(): void
    {
        $guard = $this->crmGuard();
        $guard->shouldReceive('user')->twice()->andReturn($this->identity(), null);
        $source = new GuardPrincipalSource($guard, $this->verifiedSession());
        $this->assertSame(self::ID, $source->current()->principalId);
        $this->expectException(AuthenticationException::class);
        $source->current();
    }

    public static function untrustedFacts(): array
    {
        return [
            'different tenant' => [self::B, self::ID, MembershipStatus::Active],
            'different principal' => [self::A, self::B, MembershipStatus::Active],
            'suspended' => [self::A, self::ID, MembershipStatus::Suspended],
            'disabled' => [self::A, self::ID, MembershipStatus::Disabled],
        ];
    }

    #[DataProvider('untrustedFacts')]
    public function test_inconsistent_repository_facts_are_denied(string $tenant, string $id, MembershipStatus $status): void
    {
        $guard = $this->crmGuard();
        $guard->shouldReceive('user')->andReturn($this->identity());
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldReceive('within')->once()->andReturnUsing(
            fn (PrincipalReference $principal, string $requested, Closure $operation) =>
                $operation(new MembershipSnapshot($tenant, $id, $status, 1))
        );
        $authorizer = Mockery::mock(Authorizer::class);
        $authorizer->shouldNotReceive('decide');
        $this->expectException(AuthorizationException::class);
        (new TenantAccessBoundary(new GuardPrincipalSource($guard, $this->verifiedSession()), $store, $authorizer))
            ->execute(self::A, 'lead', 'read', fn () => $this->fail('Must not run.'));
    }

    public function test_active_membership_never_overrides_business_deny(): void
    {
        $guard = $this->crmGuard();
        $guard->shouldReceive('user')->andReturn($this->identity());
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldReceive('within')->andReturnUsing(
            fn (PrincipalReference $principal, string $tenant, Closure $operation) =>
                $operation(new MembershipSnapshot($tenant, $principal->principalId, MembershipStatus::Active, 1))
        );
        $this->expectException(AuthorizationException::class);
        (new TenantAccessBoundary(new GuardPrincipalSource($guard, $this->verifiedSession()), $store, $this->app->make(Authorizer::class)))
            ->execute(self::A, 'lead', 'export', fn () => $this->fail('Must not run.'));
    }

    public function test_container_resolves_boundary_without_enabling_public_routes(): void
    {
        $this->assertInstanceOf(TenantAccessBoundary::class, $this->app->make(TenantAccessBoundary::class));
        foreach (['/login', '/register', '/leads', '/export', '/files'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public static function invalidConfigurations(): array
    {
        return [
            'missing dedicated guard' => [null, null],
            'request driver' => [['driver' => 'token', 'provider' => 'crm'], ['driver' => 'eloquent', 'model' => GlobalIdentity::class]],
            'missing provider' => [['driver' => 'session'], null],
            'scaffold model' => [['driver' => 'session', 'provider' => 'crm'], ['driver' => 'eloquent', 'model' => User::class]],
            'unreviewed provider driver' => [['driver' => 'session', 'provider' => 'crm'], ['driver' => 'database', 'model' => GlobalIdentity::class]],
        ];
    }

    #[DataProvider('invalidConfigurations')]
    public function test_invalid_crm_guard_configuration_never_falls_back_to_web(?array $guardConfig, ?array $providerConfig): void
    {
        config(['auth.guards.crm_identity' => $guardConfig, 'auth.providers.crm' => $providerConfig]);
        $auth = Mockery::mock(Factory::class);
        $auth->shouldNotReceive('guard');
        $source = GuardPrincipalSource::fromConfiguration($auth, $this->app['config']);
        $this->expectException(AuthenticationException::class);
        $source->current();
    }

    public function test_default_container_does_not_consume_even_a_global_identity_set_on_web(): void
    {
        $this->app['auth']->guard('web')->setUser($this->identity());
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldNotReceive('within');
        $this->app->instance(ActiveMembershipStore::class, $store);
        $this->expectException(AuthenticationException::class);
        $this->app->make(TenantAccessBoundary::class)->execute(self::A, 'lead', 'read', fn () => $this->fail('Must not run.'));
    }

    public function test_explicit_crm_session_mapping_can_resolve_synthetic_identity_only(): void
    {
        $this->configureCrmGuard();
        $guard = $this->app['auth']->guard('crm_identity');
        $guard->setUser($this->identity()); // Trusted fixture, not a login endpoint.
        $source = GuardPrincipalSource::fromConfiguration($this->app['auth'], $this->app['config'], $this->verifiedSession());
        $this->assertSame(self::ID, $source->current()->principalId);
        $guard->forgetUser();
        $this->expectException(AuthenticationException::class);
        $source->current();
    }

    public function test_runtime_provider_mismatch_is_denied_despite_valid_configuration(): void
    {
        $this->configureCrmGuard();
        $guard = $this->app['auth']->guard('crm_identity');
        $guard->setUser($this->identity());
        $source = GuardPrincipalSource::fromConfiguration($this->app['auth'], $this->app['config'], $this->verifiedSession());
        $guard->setProvider(new EloquentUserProvider($this->app['hash'], User::class));
        $this->expectException(AuthenticationException::class);
        $source->current();
    }

    public function test_request_guard_cannot_replace_configured_session_driver(): void
    {
        $this->configureCrmGuard();
        $requestGuard = Mockery::mock(\Illuminate\Contracts\Auth\Guard::class);
        $requestGuard->shouldNotReceive('user');
        $auth = Mockery::mock(Factory::class);
        $auth->shouldReceive('guard')->once()->with('crm_identity')->andReturn($requestGuard);
        $source = GuardPrincipalSource::fromConfiguration($auth, $this->app['config']);
        $this->expectException(AuthenticationException::class);
        $source->current();
    }

    public static function invalidIdentifiers(): array
    {
        return [[''], ['not-a-uuid'], [null], [123]];
    }

    #[DataProvider('invalidIdentifiers')]
    public function test_invalid_identity_id_fails_with_authentication_error(mixed $id): void
    {
        $guard = $this->crmGuard();
        $guard->shouldReceive('user')->once()->andReturn((new GlobalIdentity())->forceFill(['id' => $id]));
        $this->expectException(AuthenticationException::class);
        (new GuardPrincipalSource($guard, $this->verifiedSession()))->current();
    }

    private function configureCrmGuard(): void
    {
        config([
            'auth.guards.crm_identity' => ['driver' => 'session', 'provider' => 'crm'],
            'auth.providers.crm' => ['driver' => 'eloquent', 'model' => GlobalIdentity::class],
        ]);
    }

    private function crmGuard(): Guard
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('getProvider')->andReturn(new EloquentUserProvider($this->app['hash'], GlobalIdentity::class));

        return $guard;
    }

    private function identity(): GlobalIdentity
    {
        return (new GlobalIdentity())->forceFill(['id' => self::ID]);
    }

    private function verifiedSession(): \App\Core\Identity\SessionAuthority
    {
        // Unit seam only; F1-03 integration tests exercise the real registry.
        $proof = Mockery::mock(\App\Core\Identity\SessionAuthority::class);
        $proof->shouldReceive('assertCurrent')->with(self::ID)->andReturnNull();
        return $proof;
    }
}
