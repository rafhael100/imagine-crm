<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\{
    MembershipDenied, MembershipSnapshot, PostgresActiveMembershipStore,
    TenantAccessBoundary
};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ImagineAI\MockBridge\{MockCrmReadRequest, MockServiceIdentityPreflight};
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * F235: genuine CRM Core IAM + physical membership contract paired with the
 * original pinned Agents Ed25519 proof in a disposable CI ONLY.
 *
 * A service signature NEVER authenticates a HUMAN or grants CRM access.
 * Valid human password+TOTP and active membership STILL do not bypass
 * DenyByDefaultAuthorizer. No production IAM, route, PII or client records.
 */
final class AiaServiceHumanIamBoundaryTest extends TestCase
{
    use SyntheticIam {
        setUp as protected bootstrapSyntheticIamLab;
        tearDown as protected cleanupSyntheticIamLab;
    }

    protected function setUp(): void
    {
        if (getenv('AIA_F235_IAM_LAB') !== 'true') {
            $this->markTestSkipped('F235 requires the expressly gated PG16 IAM laboratory.');
        }
        $this->bootstrapSyntheticIamLab();
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('IAM_TEST_USERNAME') !== 'crm_iam_ci'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci') {
            throw new \RuntimeException('F235_NON_DISPOSABLE_IAM_DATABASE_DENIED');
        }

        $agents = dirname(__DIR__, 2).'/_aia_f231_fixture';
        if (!is_file($agents.'/src/MockBridge/MockServiceIdentityPreflight.php')
            || !is_file($agents.'/src/MockBridge/MockCrmReadRequest.php')) {
            throw new \RuntimeException('F235_PINNED_AGENTS_SOURCE_MISSING');
        }
        spl_autoload_register(static function (string $name) use ($agents): void {
            $prefix = 'ImagineAI\\';
            if (str_starts_with($name, $prefix)) {
                require_once $agents.'/src/'.str_replace(
                    '\\', '/', substr($name, strlen($prefix))
                ).'.php';
            }
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('aia_f235_membership');
        DB::purge('aia_f235_membership');
        $this->cleanupSyntheticIamLab();
    }

    private function membershipStore(): PostgresActiveMembershipStore
    {
        $this->fixtures->table('crm_organizations')->insert([
            ['id'=>self::C], ['id'=>self::D],
        ]);
        $this->fixtures->table('crm_memberships')->insert([
            [
                'id'=>(string) Str::uuid(),
                'organization_id'=>self::C,
                'global_identity_id'=>self::A,
                'status'=>'active','version'=>1,
            ],
            [
                'id'=>(string) Str::uuid(),
                'organization_id'=>self::D,
                'global_identity_id'=>self::B,
                'status'=>'active','version'=>2,
            ],
        ]);

        $settings = config('database.connections.pgsql');
        $settings['url'] = null;
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        config(['database.connections.aia_f235_membership'=>$settings]);
        DB::purge('aia_f235_membership');

        $restricted = DB::connection('aia_f235_membership');
        $who = $restricted->selectOne(
            'SELECT current_user AS username, r.rolsuper, r.rolbypassrls
             FROM pg_roles r WHERE r.rolname=current_user'
        );
        $this->assertSame('crm_app_ci',$who->username);
        $this->assertFalse((bool) $who->rolsuper);
        $this->assertFalse((bool) $who->rolbypassrls);
        $this->assertSame(0, (int) $restricted
            ->table('public.crm_memberships')->count());

        return new PostgresActiveMembershipStore($restricted);
    }

    /** @return array{0:string,1:string,2:string,3:array<string,array{public_key:string,enabled:bool}>} */
    private static function signedServiceProof(): array
    {
        $request = MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F235_AIA_REQ',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_F235_UNVERIFIED_HUMAN',
            'resource_ref'=>'SYNTHETIC_F235_LEAD',
            'purpose_ref'=>'SYNTHETIC_F235_TEST',
            'fields'=>['ref','stage'],
        ]);
        $pair = sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F235_IAM_BOUNDARY_TEST_ONLY',true)
        );
        $keyId='SYNTHETIC_F235_KEY';
        $claims=[
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>$keyId,
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>'SYNTHETIC_F235_IAM_NONCE',
            'issued_at'=>1794690000,
            'expires_at'=>1794690020,
            'request_sha256'=>hash('sha256',$request),
        ];
        $bytes=json_encode(
            $claims,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR
        );
        $signature=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$bytes,
            sodium_crypto_sign_secretkey($pair)
        ));
        return [
            $request, $bytes, $signature,
            [$keyId=>[
                'public_key'=>sodium_crypto_sign_publickey($pair),
                'enabled'=>true,
            ]],
        ];
    }

    public function test_signed_agent_does_not_authenticate_a_human_or_bypass_core_authorization(): void
    {
        $store=$this->membershipStore();
        [$request,$claims,$signature,$keyring]=self::signedServiceProof();

        // Source is the original SHA-pinned Agents F231 verifier.
        $proof=new MockServiceIdentityPreflight();
        $this->assertTrue($proof->verifyOnce(
            $request,$claims,$signature,$keyring,'SYNTHETIC_TENANT_A',1794690010
        ));
        $this->assertFalse($proof->verifyOnce(
            $request,$claims,$signature,$keyring,'SYNTHETIC_TENANT_A',1794690010
        ));

        // The signed service principal is only a synthetic selector,
        // never a CRM global-identity UUID or an authenticated session.
        $signedPrincipal=json_decode($request,true,4,JSON_THROW_ON_ERROR)['principal_ref'];
        $this->assertFalse(Str::isUuid($signedPrincipal));
        $this->expectException(AuthenticationException::class);
        $this->app->make(GuardPrincipalSource::class)->current();
    }

    public function test_real_mfa_membership_is_separate_from_agent_service_and_core_still_denies(): void
    {
        $store=$this->membershipStore();
        [$request,$claims,$signature,$keyring]=self::signedServiceProof();
        $this->assertTrue((new MockServiceIdentityPreflight())->verifyOnce(
            $request,$claims,$signature,$keyring,'SYNTHETIC_TENANT_A',1794690010
        ));

        $this->login(); // Real CRM experimental password+TOTP, fixture identities.
        $source=$this->app->make(GuardPrincipalSource::class);
        $this->assertSame(self::A,$source->current()->principalId);
        $observed=false;
        $source->withinCurrent(function (PrincipalReference $verified)
            use ($store,&$observed): void {
            $this->assertSame(self::A,$verified->principalId);
            $store->within($verified,self::C,function (MembershipSnapshot $membership)
                use (&$observed): void {
                $this->assertSame(self::A,$membership->principalId);
                $this->assertSame(self::C,$membership->tenantId);
                $this->assertSame(1,$membership->version);
                $observed=true;
            });
        });
        $this->assertTrue($observed);
        $this->assertSame(0,DB::connection('aia_f235_membership')->transactionLevel());

        // The existing CRM Core protected entry point refuses service-like
        // lead reads even after valid human MFA AND membership.
        $boundary=new TenantAccessBoundary(
            $source,$store,new DenyByDefaultAuthorizer()
        );
        $executed=false;
        try {
            $boundary->execute(
                self::C,'crm.lead','read',function () use (&$executed): void {
                    $executed=true;
                }
            );
            $this->fail('F235_CORE_AUTHORIZER_WRONGFULLY_ALLOWED_LEAD');
        } catch (AuthorizationException) {
            $this->assertFalse($executed);
        }
        $this->assertSame(0,DB::connection('aia_f235_membership')->transactionLevel());

        // The verified identity A cannot impersonate identity B or inherit
        // B's tenant D from a signed principal_ref or request header.
        try {
            $source->withinCurrent(fn(PrincipalReference $p) =>
                $store->within($p,self::D,static fn() => null)
            );
            $this->fail('F235_CROSS_TENANT_MEMBERSHIP_GRANTED');
        } catch (MembershipDenied) {
            $this->assertSame(0,DB::connection('aia_f235_membership')->transactionLevel());
        }

        // Committed membership revocation denies the next evaluation;
        // an intact human MFA session does not rescue revoked membership.
        $this->fixtures->table('crm_memberships')
            ->where('organization_id',self::C)
            ->where('global_identity_id',self::A)
            ->update(['status'=>'revoked','version'=>2]);
        try {
            $source->withinCurrent(fn(PrincipalReference $p) =>
                $store->within($p,self::C,static fn() => null)
            );
            $this->fail('F235_REVOKED_MEMBERSHIP_GRANTED');
        } catch (MembershipDenied) {
            $this->assertSame(0,DB::connection('aia_f235_membership')->transactionLevel());
        }
        $this->assertSame(self::A,$source->current()->principalId);

        // An independently revoked IAM registry session invalidates human
        // evidence even while a synthetically signed service message exists.
        $this->fixtures->table('crm_auth_sessions')
            ->where('identity_id',self::A)->where('state','active')
            ->update(['state'=>'revoked']);
        try {
            $source->current();
            $this->fail('F235_REVOKED_IAM_SESSION_ACCEPTED');
        } catch (AuthenticationException) {
            $this->assertFalse($executed);
        }
    }
}
