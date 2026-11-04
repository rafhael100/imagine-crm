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
use Illuminate\Contracts\Auth\Guard;
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
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->once()->andReturnNull();
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldNotReceive('within');

        $boundary = new TenantAccessBoundary(new GuardPrincipalSource($guard), $store, new DenyByDefaultAuthorizer());
        $this->expectException(AuthenticationException::class);
        $boundary->execute(self::A, 'lead', 'read', fn () => $this->fail('Must not run.'));
    }

    public function test_scaffold_user_id_is_not_a_global_identity_mapping(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->andReturn((new User())->forceFill(['id' => self::ID]));
        $this->expectException(AuthenticationException::class);
        (new GuardPrincipalSource($guard))->current();
    }

    public function test_guard_is_read_again_instead_of_caching_a_principal(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->twice()->andReturn($this->identity(), null);
        $source = new GuardPrincipalSource($guard);
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
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->andReturn($this->identity());
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldReceive('within')->once()->andReturnUsing(
            fn (PrincipalReference $principal, string $requested, Closure $operation) =>
                $operation(new MembershipSnapshot($tenant, $id, $status, 1))
        );
        $authorizer = Mockery::mock(Authorizer::class);
        $authorizer->shouldNotReceive('decide');
        $this->expectException(AuthorizationException::class);
        (new TenantAccessBoundary(new GuardPrincipalSource($guard), $store, $authorizer))
            ->execute(self::A, 'lead', 'read', fn () => $this->fail('Must not run.'));
    }

    public function test_active_membership_never_overrides_business_deny(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->andReturn($this->identity());
        $store = Mockery::mock(ActiveMembershipStore::class);
        $store->shouldReceive('within')->andReturnUsing(
            fn (PrincipalReference $principal, string $tenant, Closure $operation) =>
                $operation(new MembershipSnapshot($tenant, $principal->principalId, MembershipStatus::Active, 1))
        );
        $this->expectException(AuthorizationException::class);
        (new TenantAccessBoundary(new GuardPrincipalSource($guard), $store, $this->app->make(Authorizer::class)))
            ->execute(self::A, 'lead', 'export', fn () => $this->fail('Must not run.'));
    }

    public function test_container_resolves_boundary_without_enabling_public_routes(): void
    {
        $this->assertInstanceOf(TenantAccessBoundary::class, $this->app->make(TenantAccessBoundary::class));
        foreach (['/login', '/register', '/leads', '/export', '/files'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    private function identity(): GlobalIdentity
    {
        return (new GlobalIdentity())->forceFill(['id' => self::ID]);
    }
}
