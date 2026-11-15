<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * CRM-IAM-PG-HTTP-02: live Laravel HTTP + real verified IAM + PG16 membership.
 *
 * The only injected route returns the fake membership identity+version;
 * it never bypasses DenyByDefaultAuthorizer to return business records.
 * No production controller, authentication route or customer data.
 */
final class CrmIamPostgresHttpMembershipTest extends TestCase
{
    use SyntheticIam;

    private function prepareRealPgMembership(): PostgresActiveMembershipStore
    {
        if (DB::getDriverName() !== 'pgsql'
            || !getenv('IAM_TEST_USERNAME')
            || !getenv('RLS_TEST_USERNAME')) {
            $this->markTestSkipped('Requires isolated PostgreSQL16 IAM and tenant runtime roles.');
        }
        $this->fixtures->table('crm_organizations')->insert([
            ['id' => self::C], ['id' => self::D],
        ]);
        $this->fixtures->table('crm_memberships')->insert([
            [
                'id' => (string) Str::uuid(),
                'organization_id' => self::C,
                'global_identity_id' => self::A,
                'status' => 'active', 'version' => 2,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => self::D,
                'global_identity_id' => self::B,
                'status' => 'active', 'version' => 3,
            ],
        ]);

        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        $settings['url'] = null;
        config(['database.connections.crm_http_pg_probe' => $settings]);
        DB::purge('crm_http_pg_probe');

        $probe = DB::connection('crm_http_pg_probe');
        $this->assertSame('pgsql', $probe->getDriverName());
        $this->assertSame(0, $probe->transactionLevel());
        $this->assertSame(0, $probe->table('public.crm_memberships')->count());

        return new PostgresActiveMembershipStore($probe);
    }

    private function registerPrivateMembershipRoute(PostgresActiveMembershipStore $store): void
    {
        Route::middleware('web')->get(
            '/_ci/iam/pg-membership/{tenant}',
            function (string $tenant) use ($store) {
                $source = $this->app->make(GuardPrincipalSource::class);

                // Session + persisted MFA challenge are rechecked inside a
                // locked IAM transaction before the restricted RLS transaction.
                return $source->withinCurrent(
                    static fn (PrincipalReference $principal) => $store->within(
                        $principal, $tenant,
                        static fn (MembershipSnapshot $m) => response()->json([
                            'identity' => $m->principalId,
                            'tenant' => $m->tenantId,
                            'membership_version' => $m->version,
                            'is_business_authorized' => false,
                            'can_read_leads' => false,
                            'can_charge' => false,
                        ]),
                    ),
                );
            },
        );
    }

    private function attachExistingVerifiedSession(): void
    {
        $this->login(); // Existing real password+TOTP IAM service, fake values.
        // TestCase encrypts this raw server ID for the HTTP request. Never
        // disable cookie encryption or fake a principal in the request.
        $this->withCookie(
            (string) config('session.cookie'),
            $this->browserSession->getId(),
        )->withCredentials();
    }

    public function test_mfa_session_rechecks_real_pg16_active_membership_and_committed_revocation(): void
    {
        $store = $this->prepareRealPgMembership();
        $this->registerPrivateMembershipRoute($store);

        $this->getJson('/_ci/iam/pg-membership/'.self::C)->assertUnauthorized();
        $this->attachExistingVerifiedSession();
        $this->getJson('/_ci/iam/pg-membership/'.self::C)->assertOk()->assertJson([
            'identity' => self::A, 'tenant' => self::C,
            'membership_version' => 2,
            'is_business_authorized' => false,
            'can_read_leads' => false,
            'can_charge' => false,
        ]);
        $this->assertSame(0, DB::connection('crm_http_pg_probe')->transactionLevel());
        $this->assertSame(0, DB::connection('crm_http_pg_probe')
            ->table('public.crm_memberships')->count());

        // Identity A cannot borrow identity B's membership in tenant D.
        $this->getJson('/_ci/iam/pg-membership/'.self::D)->assertForbidden();

        // Source-owner revocation is committed by the PostgreSQL fixture
        // administrator. The existing IAM session stays authenticated, but
        // the next HTTP membership access must now fail immediately.
        $this->fixtures->table('crm_memberships')
            ->where('organization_id', self::C)
            ->where('global_identity_id', self::A)
            ->update(['status' => 'revoked', 'version' => 3]);
        $this->getJson('/_ci/iam/pg-membership/'.self::C)->assertForbidden();
        $this->assertSame(0, DB::connection('crm_http_pg_probe')->transactionLevel());
        $this->assertSame(0, DB::connection('crm_http_pg_probe')
            ->table('public.crm_memberships')->count());
        $this->assertSame(1, $this->db->table('crm_auth_sessions')
            ->where('state', 'active')->count());
        DB::purge('crm_http_pg_probe');
    }

    public function test_forged_http_tenant_and_principal_headers_cannot_change_verified_membership(): void
    {
        $store = $this->prepareRealPgMembership();
        $this->registerPrivateMembershipRoute($store);
        $this->attachExistingVerifiedSession();

        $this->withHeaders([
            'X-Tenant-Id' => self::D,
            'X-Identity-Id' => self::B,
            'X-Principal-Ref' => self::B,
            'X-Forwarded-User' => 'admin-synthetic',
            'Authorization' => 'Bearer synthetic-cannot-authenticate',
        ]);
        $this->getJson('/_ci/iam/pg-membership/'.self::C.'?tenant='.self::D)
            ->assertOk()->assertJson([
                'identity' => self::A,
                'tenant' => self::C,
                'can_read_leads' => false,
            ]);
        $this->getJson('/_ci/iam/pg-membership/'.self::D.'?tenant='.self::C)
            ->assertForbidden();

        $this->assertSame(0, DB::connection('crm_http_pg_probe')
            ->table('public.crm_memberships')->count());
        DB::purge('crm_http_pg_probe');
    }

    public function test_revoked_mfa_registry_session_denies_company_membership_before_pg_scope(): void
    {
        $store = $this->prepareRealPgMembership();
        $this->registerPrivateMembershipRoute($store);
        $this->attachExistingVerifiedSession();

        $this->getJson('/_ci/iam/pg-membership/'.self::C)
            ->assertOk()->assertJson(['identity' => self::A, 'tenant' => self::C]);

        $this->fixtures->table('crm_auth_sessions')
            ->where('identity_id', self::A)
            ->where('state', 'active')
            ->update(['state' => 'revoked']);

        $this->getJson('/_ci/iam/pg-membership/'.self::C)->assertUnauthorized();
        $this->assertSame(0, DB::connection('crm_http_pg_probe')->transactionLevel());
        $this->assertSame(0, DB::connection('crm_http_pg_probe')
            ->table('public.crm_memberships')->count());
        DB::purge('crm_http_pg_probe');
    }

    public function test_credential_version_rotation_revokes_existing_http_access_to_active_company(): void
    {
        $store = $this->prepareRealPgMembership();
        $this->registerPrivateMembershipRoute($store);
        $this->attachExistingVerifiedSession();

        $this->getJson('/_ci/iam/pg-membership/'.self::C)->assertOk();
        $this->fixtures->table('crm_auth_credentials')
            ->where('identity_id', self::A)
            ->update(['version' => 2]);

        // The membership itself remains active, but a stale identity
        // credential/session must never access it through the HTTP route.
        $this->assertSame('active', $this->fixtures->table('crm_memberships')
            ->where('global_identity_id', self::A)->value('status'));
        $this->getJson('/_ci/iam/pg-membership/'.self::C)->assertUnauthorized();
        $this->assertSame(0, DB::connection('crm_http_pg_probe')
            ->table('public.crm_memberships')->count());
        DB::purge('crm_http_pg_probe');
    }

    public function test_same_verified_identity_can_switch_two_active_companies_without_leaking_scope(): void
    {
        $store = $this->prepareRealPgMembership();
        $this->fixtures->table('crm_memberships')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => self::D,
            'global_identity_id' => self::A,
            'status' => 'active',
            'version' => 4,
        ]);
        $this->registerPrivateMembershipRoute($store);
        $this->attachExistingVerifiedSession();

        foreach ([
            [self::C, 2], [self::D, 4], [self::C, 2], [self::D, 4],
        ] as [$tenant, $version]) {
            $this->getJson('/_ci/iam/pg-membership/'.$tenant)
                ->assertOk()->assertJson([
                    'identity' => self::A,
                    'tenant' => $tenant,
                    'membership_version' => $version,
                    'can_read_leads' => false,
                    'can_charge' => false,
                ]);
            $this->assertSame(0, DB::connection('crm_http_pg_probe')
                ->table('public.crm_memberships')->count());
            $this->assertSame(0, DB::connection('crm_http_pg_probe')->transactionLevel());
        }
        DB::purge('crm_http_pg_probe');
    }

}
