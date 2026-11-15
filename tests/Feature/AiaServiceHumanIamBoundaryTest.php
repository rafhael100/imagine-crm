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
        // This test is explicitly SKIPPED in the general suite. In that
        // case Laravel was never booted and DB facade is not registered.
        if (!isset($this->fixtures)) {
            parent::tearDown();
            return;
        }
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
    /**
     * F237: a genuine MFA human initiates an inert, server-owned delegation.
     * A signed service can only match the witness while its exact request,
     * CRM-owned binding and *current* PG membership version remain valid.
     *
     * No customer records, no service permission, no human session token
     * crosses into the service proof; no business effect is reachable.
     */
    public function test_f237_recent_mfa_issues_bound_witness_and_pg_revocation_blocks_service(): void
    {
        if (getenv('AIA_F237_DELEGATION_LAB') !== 'true') {
            $this->markTestSkipped('F237 requires the exact disposable CI gate.');
        }
        $store = $this->membershipStore();
        $db = DB::connection();
        $request = MockCrmReadRequest::encodeCanonicalJson([
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref' => 'SYNTHETIC_F237_READ',
            'operation' => 'crm.lead.read_minimal',
            'tenant_ref' => 'SYNTHETIC_TENANT_A',
            'principal_ref' => 'SYNTHETIC_F237_PRINCIPAL',
            'resource_ref' => 'SYNTHETIC_F237_LEAD',
            'purpose_ref' => 'SYNTHETIC_F237_PURPOSE',
            'fields' => ['ref', 'stage'],
        ]);
        $kid = 'SYNTHETIC_F237_KEY';
        $tenant = 'SYNTHETIC_TENANT_A';
        $pair = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'PUBLIC_F237_HUMAN_DELEGATION_ONLY', true)
        );
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $bodyHash = hash('sha256', $request);

        $db->statement('CREATE TABLE public.aia_lab_ed25519_service_keys (
            issuer varchar(90) NOT NULL, audience varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL, key_id varchar(90) NOT NULL,
            public_key_base64 varchar(44) NOT NULL, enabled boolean NOT NULL,
            revoked_at bigint NULL, revision integer NOT NULL,
            PRIMARY KEY (tenant_ref,key_id)
        )');
        try {
            $db->statement('CREATE TABLE public.aia_lab_nonce_atomic_probe (
                issuer varchar(90) NOT NULL, audience varchar(90) NOT NULL,
                nonce varchar(90) NOT NULL, tenant_ref varchar(90) NOT NULL,
                claimed_at bigint NOT NULL, PRIMARY KEY (issuer,audience,nonce)
            )');
            $db->statement('CREATE TABLE public.aia_lab_agent_human_bindings (
                tenant_ref varchar(90) NOT NULL,
                organization_id uuid NOT NULL,
                principal_ref varchar(90) NOT NULL,
                key_id varchar(90) NOT NULL,
                allowed_fields_json text NOT NULL,
                enabled boolean NOT NULL,
                revoked_at bigint NULL,
                revision integer NOT NULL,
                PRIMARY KEY (tenant_ref,key_id,principal_ref)
            )');
            $db->statement('CREATE TABLE public.aia_lab_human_service_grants (
                grant_id uuid NOT NULL PRIMARY KEY,
                tenant_ref varchar(90) NOT NULL,
                organization_id uuid NOT NULL,
                key_id varchar(90) NOT NULL,
                principal_ref varchar(90) NOT NULL,
                human_identity_id uuid NOT NULL,
                membership_version integer NOT NULL,
                request_sha256 char(64) NOT NULL,
                purpose_ref varchar(90) NOT NULL,
                resource_ref varchar(90) NOT NULL,
                issued_at bigint NOT NULL,
                expires_at bigint NOT NULL,
                revoked_at bigint NULL,
                version integer NOT NULL,
                UNIQUE (tenant_ref,key_id,request_sha256)
            )');

            $db->table('aia_lab_agent_human_bindings')->insert([
                'tenant_ref' => $tenant,
                'organization_id' => self::C,
                'principal_ref' => 'SYNTHETIC_F237_PRINCIPAL',
                'key_id' => $kid,
                'allowed_fields_json' => '["ref","stage"]',
                'enabled' => true, 'revoked_at' => null, 'revision' => 1,
            ]);
            $db->table('aia_lab_ed25519_service_keys')->insert([
                'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                'audience' => 'SYNTHETIC_CRM_API',
                'tenant_ref' => $tenant,
                'key_id' => $kid,
                'public_key_base64' => base64_encode($public),
                'enabled' => true, 'revoked_at' => null, 'revision' => 1,
            ]);

            $witness = new \App\Support\Synthetic\AiaPgHumanDelegationWitnessLab($db);
            $source = $this->app->make(GuardPrincipalSource::class);
            $keyring = new \App\Support\Synthetic\AiaPgServiceKeyringLab($db);
            $ledger = new \App\Support\Synthetic\AiaPgNonceCandidateLedger($db);

            // Signed Agent input does not create an IAM login or a human
            // grant. No authenticated session = zero inserted rows.
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $store, self::C, $request, $kid, 1800000000,
            ));
            $this->assertSame(0, (int) $db
                ->table('aia_lab_human_service_grants')->count());

            $this->login(); // Existing CRM password + TOTP + verified session.
            $this->assertSame(self::A, $source->current()->principalId);
            // A's membership is in C only; a caller cannot issue under D.
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $store, self::D, $request, $kid, 1800000000,
            ));
            $this->assertTrue($witness->issueFromCurrentHuman(
                $source, $store, self::C, $request, $kid, 1800000000,
            ));
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $store, self::C, $request, $kid, 1800000000,
            ), 'Duplicate issuance must not create extra delegated grants.');
            $this->assertSame(1, (int) $db
                ->table('aia_lab_human_service_grants')->count());
            $grant = $db->table('aia_lab_human_service_grants')->first();
            $this->assertSame(self::A, $grant->human_identity_id);
            $this->assertSame(self::C, $grant->organization_id);
            $this->assertSame(1, (int) $grant->membership_version);
            $this->assertSame($bodyHash, $grant->request_sha256);
            $this->assertSame(1800000030, (int) $grant->expires_at);
            $this->assertFalse(property_exists($grant, 'session_token'));
            $this->assertFalse(property_exists($grant, 'cookie'));
            $this->assertFalse(property_exists($grant, 'email'));

            $sign = static function (string $body, string $nonce) use (
                $kid, $tenant, $secret
            ): array {
                $claims = [
                    'contract' => 'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
                    'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                    'audience' => 'SYNTHETIC_CRM_API',
                    'key_id' => $kid,
                    'subject' => 'SYNTHETIC_AGENTS_READ_ONLY',
                    'tenant_ref' => $tenant,
                    'operation' => 'crm.lead.read_minimal',
                    'nonce' => $nonce,
                    'issued_at' => 1800000000,
                    'expires_at' => 1800000020,
                    'request_sha256' => hash('sha256', $body),
                ];
                $canonical = json_encode(
                    $claims,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                );
                return [
                    $canonical,
                    base64_encode(sodium_crypto_sign_detached(
                        "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$canonical,
                        $secret,
                    )),
                ];
            };
            $probe = function (
                string $nonce, bool $expected, ?string $body = null,
                int $clock = 1800000010,
            ) use ($sign, $witness, $request, $tenant, $keyring, $ledger, $store): void {
                $body ??= $request;
                [$claims, $signature] = $sign($body, $nonce);
                $this->assertSame($expected, $witness->matchesSignedServiceWitness(
                    $body, $claims, $signature, $tenant, $clock,
                    $keyring, $ledger, $store,
                ));
                // This is a CRM-side experiment only: the public response
                // must never reflect the internal positive witness result.
                $this->assertSame(
                    'DENIED_OR_NOT_FOUND',
                    \App\Support\Synthetic\AiaReadOnlyMockBoundary
                        ::inspectCanonicalJson($body)['state']
                );
            };

            $probe('SYNTHETIC_F237_FIRST_NONCE', true);
            $probe('SYNTHETIC_F237_FIRST_NONCE', false); // replay

            // Fresh and correctly signed requests still cannot change any
            // field, resource or purpose of the human's exact witness.
            $narrow = json_decode($request, true, 4, JSON_THROW_ON_ERROR);
            $narrow['fields'] = ['ref'];
            $narrowBody = MockCrmReadRequest::encodeCanonicalJson($narrow);
            $probe('SYNTHETIC_F237_NARROW_NONCE', false, $narrowBody);

            $db->table('aia_lab_agent_human_bindings')
                ->where('tenant_ref', $tenant)->update(['enabled' => false]);
            $probe('SYNTHETIC_F237_BINDING_DISABLED', false);
            $db->table('aia_lab_agent_human_bindings')
                ->where('tenant_ref', $tenant)->update(['enabled' => true]);

            $db->table('aia_lab_human_service_grants')->update([
                'revoked_at' => 1800000005, 'version' => 2,
            ]);
            $probe('SYNTHETIC_F237_GRANT_REVOKED', false);
            $db->table('aia_lab_human_service_grants')->update([
                'revoked_at' => null, 'version' => 3,
            ]);

            // Even with all service facts valid, live CRM membership
            // revocation and membership-version rotation are rechecked.
            $this->fixtures->table('crm_memberships')
                ->where('global_identity_id', self::A)
                ->where('organization_id', self::C)
                ->update(['version' => 2]);
            $probe('SYNTHETIC_F237_MEMBERSHIP_STALE', false);
            $this->fixtures->table('crm_memberships')
                ->where('global_identity_id', self::A)
                ->where('organization_id', self::C)
                ->update(['version' => 1, 'status' => 'revoked']);
            $probe('SYNTHETIC_F237_MEMBER_REVOKED', false);
            $this->fixtures->table('crm_memberships')
                ->where('global_identity_id', self::A)
                ->where('organization_id', self::C)
                ->update(['status' => 'active']);

            $db->table('aia_lab_human_service_grants')
                ->update(['expires_at' => 1800000010]);
            $probe('SYNTHETIC_F237_GRANT_EXPIRED', false);
            $db->table('aia_lab_human_service_grants')
                ->update(['expires_at' => 1800000030]);

            $db->table('aia_lab_ed25519_service_keys')
                ->where('tenant_ref', $tenant)->update(['revoked_at' => 1800000005]);
            $before = (int) $db->table('aia_lab_nonce_atomic_probe')->count();
            $probe('SYNTHETIC_F237_SERVICE_KEY_REVOKED', false);
            $this->assertSame(
                $before, (int) $db->table('aia_lab_nonce_atomic_probe')->count()
            );
            $db->table('aia_lab_ed25519_service_keys')
                ->where('tenant_ref', $tenant)->update(['revoked_at' => null]);

            // Revoked browser IAM cannot create any NEW grant, even when
            // existing membership is active. Service proof never forwards
            // cookies or inherits CRM interactive permissions.
            $this->fixtures->table('crm_auth_sessions')
                ->where('identity_id', self::A)->where('state', 'active')
                ->update(['state' => 'revoked']);
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $store, self::C, $narrowBody, $kid, 1800000000,
            ));
            $this->assertSame(1, (int) $db
                ->table('aia_lab_human_service_grants')->count());
            $this->assertSame(0, DB::connection('aia_f235_membership')->transactionLevel());
        } finally {
            foreach ([
                'aia_lab_human_service_grants',
                'aia_lab_agent_human_bindings',
                'aia_lab_nonce_atomic_probe',
                'aia_lab_ed25519_service_keys',
            ] as $table) {
                $db->statement('DROP TABLE IF EXISTS public.'.$table);
            }
        }
    }


}
