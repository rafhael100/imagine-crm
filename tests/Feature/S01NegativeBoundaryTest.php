<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationEnvelope;
use App\Core\Authorization\AuthorizationIntent;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use App\Core\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * S01-NEG-01: inert, synthetic denial proofs only. No real read_self policy,
 * no public authentication route, no authorization to reveal organization data.
 */
final class S01NegativeBoundaryTest extends TestCase
{
    use SyntheticIam;

    public function test_spoofed_request_identity_and_tenant_never_reach_membership(): void
    {
        request()->headers->set('X-Tenant-Id', self::C);
        request()->headers->set('X-Identity-Id', self::A);
        request()->merge(['tenant_id' => self::C, 'identity_id' => self::A]);

        $memberships = Mockery::mock(ActiveMembershipStore::class);
        $memberships->shouldNotReceive('within');
        $this->app->instance(ActiveMembershipStore::class, $memberships);

        $this->expectException(AuthenticationException::class);
        $this->app->make(TenantAccessBoundary::class)->executeContract(
            self::C, self::s01Intent(), fn () => $this->fail('No S01 read or callback is permitted.')
        );
    }

    public function test_password_only_pending_mfa_never_reaches_membership(): void
    {
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->assertNull($this->guard->user());

        $memberships = Mockery::mock(ActiveMembershipStore::class);
        $memberships->shouldNotReceive('within');
        $this->app->instance(ActiveMembershipStore::class, $memberships);

        $this->expectException(AuthenticationException::class);
        $this->app->make(TenantAccessBoundary::class)->executeContract(
            self::C, self::s01Intent(), fn () => $this->fail('Pending MFA must not authorize S01.')
        );
    }

    public function test_revoked_registry_session_never_reaches_membership(): void
    {
        $this->login();
        $this->fixtures->table('crm_auth_sessions')
            ->where('identity_id', self::A)->update(['state' => 'revoked']);

        $memberships = Mockery::mock(ActiveMembershipStore::class);
        $memberships->shouldNotReceive('within');
        $this->app->instance(ActiveMembershipStore::class, $memberships);

        $this->expectException(AuthenticationException::class);
        $this->app->make(TenantAccessBoundary::class)->executeContract(
            self::C, self::s01Intent(), fn () => $this->fail('Revoked session must not authorize S01.')
        );
    }

    public function test_read_self_remains_denied_with_real_synthetic_mfa_and_active_membership(): void
    {
        $this->requirePostgres();
        $this->insertOrganizationsAndMemberships();
        $business = $this->businessConnection();
        $this->login();

        $effectRan = false;
        try {
            $this->app->make(TenantAccessBoundary::class)->executeContract(
                self::C,
                self::s01Intent(),
                function () use (&$effectRan): void { $effectRan = true; }
            );
            $this->fail('Unapproved read_self must never run even with active membership.');
        } catch (AuthorizationException $e) {
            $this->assertSame('Protected operation denied.', $e->getMessage());
        }
        $this->assertFalse($effectRan);
        $this->assertSame(0, $business->transactionLevel());
        $this->assertContains($business->selectOne(
            "SELECT current_setting('app.tenant_id', true) AS tenant"
        )->tenant, [null, '']);
    }

    public function test_another_identity_membership_cannot_be_used_for_read_self(): void
    {
        $this->requirePostgres();
        // A belongs to C. D belongs to B. Request-controlled D cannot select B's membership.
        $this->insertOrganizationsAndMemberships();
        $business = $this->businessConnection();
        $this->login();
        request()->headers->set('X-Tenant-Id', self::D);
        request()->merge(['tenant_id' => self::D]);

        $effectRan = false;
        try {
            $this->app->make(TenantAccessBoundary::class)->executeContract(
                self::D, self::s01Intent(self::D),
                function () use (&$effectRan): void { $effectRan = true; }
            );
            $this->fail('Cross-tenant membership must never satisfy S01.');
        } catch (AuthorizationException $e) {
            $this->assertSame('Tenant access denied.', $e->getMessage());
        }
        $this->assertFalse($effectRan);
        $this->assertSame(0, $business->transactionLevel());
        $this->assertContains($business->selectOne(
            "SELECT current_setting('app.tenant_id', true) AS tenant"
        )->tenant, [null, '']);
    }

    public static function unapprovedS01Fields(): array
    {
        return [
            'organization display name not in laboratory vocabulary' => [['organization_display_name' => 'F-BIZ']],
            'other membership identity not in laboratory vocabulary' => [['membership_identity' => 'F-ID']],
            'invalid classification never reaches the membership check' => [['lab_secret' => 'F-UNREVIEWED']],
        ];
    }

    #[DataProvider('unapprovedS01Fields')]
    public function test_unapproved_field_claims_fail_before_identity_and_membership(array $fields): void
    {
        $memberships = Mockery::mock(ActiveMembershipStore::class);
        $memberships->shouldNotReceive('within');
        $this->app->instance(ActiveMembershipStore::class, $memberships);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Protected operation denied.');
        $this->app->make(TenantAccessBoundary::class)->executeContract(
            self::C, array_replace(self::s01Intent(), ['fields' => $fields]),
            fn () => $this->fail('Unreviewed field claims cannot expose any data.')
        );
    }

    public function test_even_a_consistent_read_self_envelope_is_not_an_allow(): void
    {
        $intent = AuthorizationIntent::fromArray(self::s01Intent());
        $context = new TenantContext(self::C, self::A, (string) Str::uuid());
        $envelope = new AuthorizationEnvelope(
            $context, new PrincipalReference(self::A),
            new MembershipSnapshot(self::C, self::A, MembershipStatus::Active, 1), $intent
        );
        foreach ([null, 'cam_03_other_members', 'cam_09_billing'] as $field) {
            $decision = (new DenyByDefaultAuthorizer())->decide(new AccessRequest(
                $context, 'organization.self', 'read_self', $field, $envelope
            ));
            $this->assertFalse($decision->allowed);
            $this->assertSame('CONTRACT_INVALID', $decision->reasonCode);
            $this->assertSame(['code', 'correlation_id'], array_keys($decision->signal()));
        }
        $this->assertFalse(method_exists(\App\Core\Authorization\AuthorizationDecision::class, 'allow'));
    }

    public function test_no_s01_public_read_or_login_route_exists(): void
    {
        foreach (['/crm/self', '/crm/organization/self', '/api/crm/self',
            '/api/crm/organization/self', '/crm/login'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    private static function s01Intent(string $tenant = self::C): array
    {
        return [
            'schema_version' => 1,
            'policy_version' => 'lab-deny-v1',
            'resource' => 'organization.self',
            'resource_id' => $tenant,
            'action' => 'read_self',
            // Laboratory fields are merely untrusted descriptive input.
            'fields' => ['lab_id' => 'F-ID'],
            'scope' => ['kind' => 'tenant', 'id' => $tenant],
            'channel' => 'interactive',
        ];
    }

    private function requirePostgres(): void
    {
        if ($this->db->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires disposable PostgreSQL CI and non-bypass IAM/RLS roles.');
        }
    }

    private function insertOrganizationsAndMemberships(): void
    {
        $this->fixtures->table('crm_organizations')->insert([
            ['id' => self::C], ['id' => self::D],
        ]);
        $this->fixtures->table('crm_memberships')->insert([
            [
                'id' => (string) Str::uuid(), 'organization_id' => self::C,
                'global_identity_id' => self::A, 'status' => 'active', 'version' => 1,
            ],
            [
                'id' => (string) Str::uuid(), 'organization_id' => self::D,
                'global_identity_id' => self::B, 'status' => 'active', 'version' => 1,
            ],
        ]);
    }

    private function businessConnection(): \Illuminate\Database\Connection
    {
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        $settings['url'] = null;
        config(['database.connections.crm_s01_negative_probe' => $settings]);
        $db = DB::connection('crm_s01_negative_probe');
        $this->app->instance(
            ActiveMembershipStore::class, new PostgresActiveMembershipStore($db)
        );

        return $db;
    }
}
