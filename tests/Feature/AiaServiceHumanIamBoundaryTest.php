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
        foreach ([
            'aia_f238_iam_read', 'aia_f238_iam_second',
            'aia_f238_admin_second', 'aia_f239_scoped_grants',
        ] as $name) {
            DB::disconnect($name);
            DB::purge($name);
        }
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
                lock_marker smallint NOT NULL DEFAULT 0,
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
                iam_session_id uuid NOT NULL,
                credential_version integer NOT NULL,
                request_sha256 char(64) NOT NULL,
                purpose_ref varchar(90) NOT NULL,
                resource_ref varchar(90) NOT NULL,
                issued_at bigint NOT NULL,
                expires_at bigint NOT NULL,
                revoked_at bigint NULL,
                version integer NOT NULL,
                lock_marker smallint NOT NULL DEFAULT 0,
                UNIQUE (tenant_ref,key_id,request_sha256)
            )');

            // F239: exact disposable privilege-minimum role; no business
            // or global IAM table ACL and no privileged issuer connection.
            $db->statement("CREATE ROLE crm_aia_grant_ci LOGIN
                PASSWORD 'ci-aia-grant-only'
                NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
            $db->statement('GRANT CONNECT ON DATABASE imagine_crm_ci
                TO crm_aia_grant_ci');
            $db->statement('GRANT USAGE ON SCHEMA public TO crm_aia_grant_ci');
            foreach ([
                'aia_lab_agent_human_bindings',
                'aia_lab_human_service_grants',
            ] as $table) {
                $db->statement('ALTER TABLE public.'.$table.
                    ' ENABLE ROW LEVEL SECURITY');
                $db->statement('ALTER TABLE public.'.$table.
                    ' FORCE ROW LEVEL SECURITY');
                $db->statement('CREATE POLICY aia_f239_read ON public.'.
                    $table.' FOR SELECT TO crm_aia_grant_ci
                    USING (tenant_ref = current_setting('.
                    "'app.tenant_id', true))");
                $db->statement('CREATE POLICY aia_f239_lock ON public.'.
                    $table.' FOR UPDATE TO crm_aia_grant_ci
                    USING (tenant_ref = current_setting('.
                    "'app.tenant_id', true)) WITH CHECK (false)");
                $db->statement('GRANT SELECT ON TABLE public.'.
                    $table.' TO crm_aia_grant_ci');
                // PG SELECT FOR UPDATE needs an UPDATE-eligible column;
                // this column has no business content and the RLS UPDATE
                // policy denies every attempted write.
                $db->statement('GRANT UPDATE (lock_marker) ON TABLE public.'.
                    $table.' TO crm_aia_grant_ci');
            }
            $db->statement('CREATE POLICY aia_f239_issue ON
                public.aia_lab_human_service_grants
                FOR INSERT TO crm_aia_grant_ci
                WITH CHECK (
                    tenant_ref = current_setting('. "'app.tenant_id',true)" .')
                    AND revoked_at IS NULL
                    AND version = 1 AND membership_version > 0
                    AND credential_version > 0
                    AND expires_at > issued_at
                    AND expires_at <= issued_at + 30
                )');
            $db->statement('GRANT INSERT ON TABLE
                public.aia_lab_human_service_grants TO crm_aia_grant_ci');
            $grantSettings = config('database.connections.pgsql');
            $grantSettings['url'] = null;
            $grantSettings['username'] = 'crm_aia_grant_ci';
            $grantSettings['password'] = 'ci-aia-grant-only';
            config(['database.connections.aia_f239_scoped_grants' => $grantSettings]);
            DB::purge('aia_f239_scoped_grants');
            $grantDb = DB::connection('aia_f239_scoped_grants');
            $this->assertSame('crm_aia_grant_ci',
                $grantDb->selectOne('SELECT current_user AS u')->u);
            $this->assertSame(0, (int) $grantDb->table(
                'public.aia_lab_agent_human_bindings'
            )->count());
            $this->assertSame(0, (int) $grantDb->table(
                'public.aia_lab_human_service_grants'
            )->count());
            $this->assertSame(0, $grantDb->transactionLevel());

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

            $witness = new \App\Support\Synthetic\AiaPgHumanDelegationWitnessLab($grantDb);
            $source = $this->app->make(GuardPrincipalSource::class);
            $sessionAuthority = $this->app->make(
                \App\Core\Identity\DatabaseSessionAuthority::class
            );
            $iamSettings = config('database.connections.pgsql');
            $iamSettings['url'] = null;
            $iamSettings['username'] = getenv('IAM_TEST_USERNAME');
            $iamSettings['password'] = getenv('IAM_TEST_PASSWORD');
            config(['database.connections.aia_f238_iam_read' => $iamSettings]);
            DB::purge('aia_f238_iam_read');
            $sessionContinuity = new \App\Support\Synthetic\AiaPgSessionContinuityLab(
                DB::connection('aia_f238_iam_read')
            );
            $keyring = new \App\Support\Synthetic\AiaPgServiceKeyringLab($db);
            $ledger = new \App\Support\Synthetic\AiaPgNonceCandidateLedger($db);

            // Signed Agent input does not create an IAM login or a human
            // grant. No authenticated session = zero inserted rows.
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $sessionAuthority, $store, self::C, $request, $kid, 1800000000,
            ));
            $this->assertSame(0, (int) $db
                ->table('aia_lab_human_service_grants')->count());

            // Runtime role has no permission on IAM, memberships, leads,
            // issuer keyring, or nonce; its locked row column cannot be
            // used to mutate an existing record.
            foreach ([
                'public.crm_auth_sessions',
                'public.crm_memberships',
                'public.crm_global_identities',
                'public.aia_lab_ed25519_service_keys',
                'public.aia_lab_nonce_atomic_probe',
            ] as $forbiddenTable) {
                $denied = false;
                try {
                    $grantDb->table($forbiddenTable)->count();
                } catch (\Throwable) {
                    $denied = true;
                }
                $this->assertTrue($denied, 'Unexpected runtime privilege: '.$forbiddenTable);
            }
            $this->assertSame(0, $grantDb->transactionLevel());

            $this->login(); // Existing CRM password + TOTP + verified session.
            $this->assertSame(self::A, $source->current()->principalId);
            // A's membership is in C only; a caller cannot issue under D.
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $sessionAuthority, $store, self::D, $request, $kid, 1800000000,
            ));
            $this->assertTrue($witness->issueFromCurrentHuman(
                $source, $sessionAuthority, $store, self::C, $request, $kid, 1800000000,
            ));
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $sessionAuthority, $store, self::C, $request, $kid, 1800000000,
            ), 'Duplicate issuance must not create extra delegated grants.');
            $this->assertSame(1, (int) $db
                ->table('aia_lab_human_service_grants')->count());
            // Even though it may lock a row, it cannot UPDATE/DELETE the
            // business/security content. RLS also rejects any cross-tenant
            // SELECT/INSERT without the server-set transaction-local marker.
            $this->assertSame(0, (int) $grantDb->table(
                'public.aia_lab_human_service_grants'
            )->count());
            $this->assertSame(0, $grantDb->transactionLevel());
            $db->table('aia_lab_agent_human_bindings')->insert([
                'tenant_ref' => 'SYNTHETIC_TENANT_B',
                'organization_id' => self::D,
                'principal_ref' => 'SYNTHETIC_F239_PRINCIPAL_B',
                'key_id' => $kid,
                'allowed_fields_json' => '["ref"]',
                'enabled' => true, 'revoked_at' => null, 'revision' => 1,
            ]);
            $grantDb->transaction(function () use ($grantDb): void {
                $grantDb->selectOne("SELECT set_config(
                    'app.tenant_id', ?, true)", ['SYNTHETIC_TENANT_A']);
                $this->assertSame(1, (int) $grantDb->table(
                    'public.aia_lab_human_service_grants'
                )->count());
                $this->assertSame(1, (int) $grantDb->table(
                    'public.aia_lab_agent_human_bindings'
                )->count());
                // A column-level lock grant cannot perform an UPDATE
                // because the RLS WITH CHECK is deliberately false.
                $badWrite = false;
                try {
                    $affected = $grantDb->table(
                        'public.aia_lab_human_service_grants'
                    )->update(['lock_marker' => 1]);
                    $badWrite = $affected !== 0;
                } catch (\Throwable) {
                    $badWrite = false;
                }
                $this->assertFalse($badWrite);
            }, 1);
            $this->assertSame(0, $grantDb->transactionLevel());
            $this->assertSame(0, (int) $grantDb->table(
                'public.aia_lab_human_service_grants'
            )->count());
            $this->assertSame(0, (int) $grantDb->table(
                'public.aia_lab_agent_human_bindings'
            )->count());
            $grant = $db->table('aia_lab_human_service_grants')->first();
            $this->assertSame(self::A, $grant->human_identity_id);
            $this->assertSame(self::C, $grant->organization_id);
            $this->assertSame(1, (int) $grant->membership_version);
            $this->assertTrue(Str::isUuid($grant->iam_session_id));
            $this->assertSame(1, (int) $grant->credential_version);
            $this->assertSame($bodyHash, $grant->request_sha256);
            $this->assertSame(1800000030, (int) $grant->expires_at);
            $this->assertFalse(property_exists($grant, 'session_token'));
            $this->assertFalse(property_exists($grant, 'cookie'));
            $this->assertFalse(property_exists($grant, 'token_hash'));
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
            ) use (
                $sign, $witness, $request, $tenant, $keyring, $ledger,
                $sessionContinuity, $store
            ): void {
                $body ??= $request;
                [$claims, $signature] = $sign($body, $nonce);
                $this->assertSame($expected, $witness->matchesSignedServiceWitness(
                    $body, $claims, $signature, $tenant, $clock,
                    $keyring, $ledger, $sessionContinuity, $store,
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
            // A real logout invalidates the EXACT originating session
            // and therefore this previously issued witness as well.
            $this->auth->logout();
            $this->assertSame('revoked', $this->fixtures
                ->table('crm_auth_sessions')
                ->where('id', $grant->iam_session_id)->value('state'));
            $probe('SYNTHETIC_F238_ISSUER_LOGOUT', false);
            $this->assertFalse($witness->issueFromCurrentHuman(
                $source, $sessionAuthority, $store, self::C, $narrowBody, $kid, 1800000000,
            ));
            $this->assertSame(1, (int) $db
                ->table('aia_lab_human_service_grants')->count());
            $this->assertSame(0, DB::connection('aia_f235_membership')->transactionLevel());
        } finally {
            DB::disconnect('aia_f239_scoped_grants');
            DB::purge('aia_f239_scoped_grants');
            foreach ([
                'aia_lab_human_service_grants',
                'aia_lab_agent_human_bindings',
                'aia_lab_nonce_atomic_probe',
                'aia_lab_ed25519_service_keys',
            ] as $table) {
                $db->statement('DROP TABLE IF EXISTS public.'.$table);
            }
            $db->statement('REVOKE USAGE ON SCHEMA public FROM crm_aia_grant_ci');
            $db->statement('REVOKE CONNECT ON DATABASE imagine_crm_ci
                FROM crm_aia_grant_ci');
            $db->statement('DROP ROLE IF EXISTS crm_aia_grant_ci');
        }
    }


    /**
     * F238: physical PostgreSQL lock test, NOT a thread simulation.
     * Two independent backend connections try to revoke the exact same
     * CRM IAM session or server-owned grant while a validation unit holds
     * its row locks. After commit, revocation is effective immediately.
     */
    public function test_f238_pg16_revocation_is_linearized_against_session_and_grant_reads(): void
    {
        if (getenv('AIA_F238_SESSION_LAB') !== 'true') {
            $this->markTestSkipped('F238 requires disposable PostgreSQL16 CI.');
        }
        $this->login();
        $source = $this->app->make(GuardPrincipalSource::class);
        $authority = $this->app->make(
            \App\Core\Identity\DatabaseSessionAuthority::class
        );
        $registered = $source->withinCurrent(
            fn (PrincipalReference $p) => $authority->sessionRow('crm.auth.token')
        );
        $this->assertNotNull($registered);
        $this->assertSame(self::A, $registered->identity_id);

        $iamSettings = config('database.connections.pgsql');
        $iamSettings['url'] = null;
        $iamSettings['username'] = getenv('IAM_TEST_USERNAME');
        $iamSettings['password'] = getenv('IAM_TEST_PASSWORD');
        config(['database.connections.aia_f238_iam_read' => $iamSettings]);
        config(['database.connections.aia_f238_iam_second' => $iamSettings]);
        DB::purge('aia_f238_iam_read');
        DB::purge('aia_f238_iam_second');
        $firstIam = DB::connection('aia_f238_iam_read');
        $secondIam = DB::connection('aia_f238_iam_second');
        $firstPid = (int) $firstIam
            ->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $secondPid = (int) $secondIam
            ->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $this->assertNotSame($firstPid, $secondPid);

        $continuity = new \App\Support\Synthetic\AiaPgSessionContinuityLab(
            $firstIam
        );
        $sessionId = $registered->id;
        $credentialVersion = (int) $registered->credential_version;
        $now = now()->getTimestamp();
        $called = false;
        $this->assertTrue($continuity->withinActiveSession(
            self::A, $sessionId, $credentialVersion, $now,
            function () use ($secondIam, $sessionId, &$called): bool {
                $called = true;
                $blocked = false;
                $secondIam->statement("SET lock_timeout = '180ms'");
                try {
                    // Competing connection really attempts UPDATE on the
                    // *same row*, while the first holds FOR SHARE.
                    $secondIam->table('public.crm_auth_sessions')
                        ->where('id', $sessionId)
                        ->update(['state' => 'revoked']);
                } catch (\Throwable) {
                    $blocked = true;
                } finally {
                    $secondIam->statement('RESET lock_timeout');
                }
                $this->assertTrue(
                    $blocked,
                    'Session revocation must wait for active validation'
                );
                return true;
            }
        ));
        $this->assertTrue($called);
        $this->assertSame(0, $firstIam->transactionLevel());
        $this->assertSame('active', $this->fixtures
            ->table('crm_auth_sessions')
            ->where('id', $sessionId)->value('state'));

        // The same connection succeeds AFTER the held row lock commits.
        $this->assertSame(1, $secondIam->table('public.crm_auth_sessions')
            ->where('id', $sessionId)
            ->update(['state' => 'revoked']));
        $calledAfter = false;
        $this->assertFalse($continuity->withinActiveSession(
            self::A, $sessionId, $credentialVersion, $now,
            static function () use (&$calledAfter): bool {
                $calledAfter = true;
                return true;
            }
        ));
        $this->assertFalse($calledAfter);

        // Repeat the physical race on the grant row. The F237 witness
        // uses the same lockForUpdate primitive during its full signed
        // verification. This table has only artificial CI data.
        $fixture = DB::connection();
        $fixture->statement('CREATE TABLE public.aia_lab_human_service_grants (
            grant_id uuid NOT NULL PRIMARY KEY, revoked_at bigint NULL
        )');
        try {
            $grantId = (string) Str::uuid();
            $fixture->table('aia_lab_human_service_grants')->insert([
                'grant_id' => $grantId, 'revoked_at' => null
            ]);
            $adminSettings = config('database.connections.pgsql');
            $adminSettings['url'] = null;
            config(['database.connections.aia_f238_admin_second' => $adminSettings]);
            DB::purge('aia_f238_admin_second');
            $secondAdmin = DB::connection('aia_f238_admin_second');
            $adminPid = (int) $secondAdmin->selectOne(
                'SELECT pg_backend_pid() AS pid'
            )->pid;
            $ownerPid = (int) $fixture->selectOne(
                'SELECT pg_backend_pid() AS pid'
            )->pid;
            $this->assertNotSame($adminPid, $ownerPid);

            $fixture->transaction(function () use (
                $fixture, $secondAdmin, $grantId
            ): void {
                $row = $fixture->table('public.aia_lab_human_service_grants')
                    ->where('grant_id', $grantId)->lockForUpdate()->first();
                $this->assertNull($row->revoked_at);
                $secondAdmin->statement("SET lock_timeout = '180ms'");
                $blocked = false;
                try {
                    $secondAdmin->table('public.aia_lab_human_service_grants')
                        ->where('grant_id', $grantId)
                        ->update(['revoked_at' => 1800000001]);
                } catch (\Throwable) {
                    $blocked = true;
                } finally {
                    $secondAdmin->statement('RESET lock_timeout');
                }
                $this->assertTrue(
                    $blocked, 'Grant revocation must serialize with read'
                );
            }, 1);
            $this->assertSame(1, $secondAdmin
                ->table('public.aia_lab_human_service_grants')
                ->where('grant_id', $grantId)
                ->update(['revoked_at' => 1800000001]));
            $this->assertSame(
                1800000001,
                (int) $fixture->table('public.aia_lab_human_service_grants')
                    ->where('grant_id', $grantId)->value('revoked_at')
            );
        } finally {
            DB::disconnect('aia_f238_admin_second');
            DB::purge('aia_f238_admin_second');
            $fixture->statement(
                'DROP TABLE IF EXISTS public.aia_lab_human_service_grants'
            );
        }
    }

}
