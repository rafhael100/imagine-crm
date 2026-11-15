<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * CRM-IAM-BUSINESS-HTTP-01: request -> MFA -> PostgreSQL membership -> ACL.
 *
 * This is a deny-only laboratory. The routes are injected by PHPUnit
 * and never exist in routes/web.php or any public Laravel environment.
 * All identities and organizations are synthetic.
 */
final class CrmIamBusinessHttpGateTest extends TestCase
{
    use SyntheticIam;

    private int $businessCallbacks = 0;

    private function installSyntheticMemberships(): TenantAccessBoundary
    {
        if (DB::getDriverName() !== 'pgsql'
            || getenv('IAM_TEST_USERNAME') !== 'crm_iam_ci'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm') {
            $this->markTestSkipped('Requires the isolated, restricted PostgreSQL16 GitHub CI profiles.');
        }

        $this->fixtures->table('crm_organizations')->insert([
            ['id' => self::C], ['id' => self::D],
        ]);
        $this->fixtures->table('crm_memberships')->insert([
            [
                'id' => (string) Str::uuid(),
                'organization_id' => self::C,
                'global_identity_id' => self::A,
                'status' => 'active',
                'version' => 2,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => self::D,
                'global_identity_id' => self::B,
                'status' => 'active',
                'version' => 3,
            ],
        ]);

        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        $settings['url'] = null;
        config(['database.connections.crm_business_gate_ci' => $settings]);
        DB::purge('crm_business_gate_ci');

        $db = DB::connection('crm_business_gate_ci');
        $this->assertSame('pgsql', $db->getDriverName());
        $this->assertSame(0, $db->transactionLevel());
        $this->assertSame(0, $db->table('public.crm_memberships')->count());

        return new TenantAccessBoundary(
            $this->app->make(GuardPrincipalSource::class),
            new PostgresActiveMembershipStore($db),
            new DenyByDefaultAuthorizer(),
        );
    }

    private function installOnlyPhpUnitRoutes(TenantAccessBoundary $boundary): void
    {
        Route::middleware('web')->match(
            ['GET', 'POST'],
            '/_ci/iam-business/{tenant}/{action}',
            function (Request $request, string $tenant, string $action) use ($boundary) {
                // This syntactically valid intent is STILL NOT an entitlement.
                // No client-supplied identity, role or approval is trusted.
                $intent = [
                    'schema_version' => 1,
                    'policy_version' => 'lab-deny-v1',
                    'resource' => 'lab.record',
                    'resource_id' => self::C,
                    'action' => $action,
                    'fields' => ['lab_id' => 'F-ID', 'lab_person' => 'F-PII'],
                    'scope' => ['kind' => 'tenant', 'id' => $tenant],
                    'channel' => 'interactive',
                ];
                $expectedPrincipal = $request->query('expected_principal');
                return $boundary->executeContract(
                    $tenant,
                    $intent,
                    function () {
                        ++$this->businessCallbacks;
                        // A successful business response would be a test failure.
                        return response()->json(['unexpected_business_access' => true]);
                    },
                    is_string($expectedPrincipal) ? $expectedPrincipal : null,
                );
            },
        );
    }

    private function attachVerifiedMfaSession(): void
    {
        $this->login();
        // WithCookie encrypts the raw server ID for the HTTP request.
        $this->withCookie(
            (string) config('session.cookie'),
            $this->browserSession->getId(),
        )->withCredentials();
    }

    private function assertNoLeakedScopeOrBusinessEffects(): void
    {
        $db = DB::connection('crm_business_gate_ci');
        $this->assertSame(0, $this->businessCallbacks);
        $this->assertSame(0, $db->transactionLevel());
        $this->assertSame(0, $db->table('public.crm_memberships')->count());
        $marker = $db->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant");
        $this->assertContains($marker->tenant ?? null, [null, '']);
    }

    public function test_verified_mfa_and_active_real_membership_still_deny_reads_updates_and_spoofed_roles(): void
    {
        $boundary = $this->installSyntheticMemberships();
        $this->installOnlyPhpUnitRoutes($boundary);

        $this->getJson('/_ci/iam-business/'.self::C.'/read')->assertUnauthorized();
        $this->assertNoLeakedScopeOrBusinessEffects();

        $this->attachVerifiedMfaSession();
        // A real verified identity and a real active PostgreSQL membership
        // do not grant lead/record read, modification or export rights.
        $this->getJson('/_ci/iam-business/'.self::C.'/read')->assertForbidden();
        $this->postJson('/_ci/iam-business/'.self::C.'/update')->assertForbidden();
        $this->getJson('/_ci/iam-business/'.self::C.'/export')->assertForbidden();
        $this->getJson('/_ci/iam-business/'.self::D.'/read')->assertForbidden();

        $this->withHeaders([
            'X-Tenant-Id' => self::C,
            'X-Identity-Id' => self::B,
            'X-Principal-Ref' => self::B,
            'X-Role' => 'admin',
            'Authorization' => 'Bearer synthetic-forged',
        ]);
        $this->getJson('/_ci/iam-business/'.self::C.'/read?role=owner&is_admin=1')
            ->assertForbidden();
        $this->getJson('/_ci/iam-business/'.self::C.'/read?expected_principal='.self::B)
            ->assertForbidden();

        $this->assertNoLeakedScopeOrBusinessEffects();
        DB::purge('crm_business_gate_ci');
    }

    public function test_membership_revocation_and_credentials_rotation_fail_closed_without_any_callback(): void
    {
        $boundary = $this->installSyntheticMemberships();
        $this->installOnlyPhpUnitRoutes($boundary);
        $this->attachVerifiedMfaSession();

        // Initially valid membership is still DENIED by business policy.
        $this->getJson('/_ci/iam-business/'.self::C.'/read')->assertForbidden();
        $this->fixtures->table('crm_memberships')
            ->where('organization_id', self::C)
            ->where('global_identity_id', self::A)
            ->update(['status' => 'revoked', 'version' => 3]);
        $this->getJson('/_ci/iam-business/'.self::C.'/read')->assertForbidden();

        // Even if the membership is reactivated, credential rotation
        // invalidates the existing MFA session before business access.
        $this->fixtures->table('crm_memberships')
            ->where('organization_id', self::C)
            ->where('global_identity_id', self::A)
            ->update(['status' => 'active', 'version' => 4]);
        $this->fixtures->table('crm_auth_credentials')
            ->where('identity_id', self::A)
            ->update(['version' => 2]);
        $this->getJson('/_ci/iam-business/'.self::C.'/read')->assertUnauthorized();

        $this->assertNoLeakedScopeOrBusinessEffects();
        DB::purge('crm_business_gate_ci');
    }
}
