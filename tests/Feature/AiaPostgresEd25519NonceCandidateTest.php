<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{
    AiaPgEd25519LabBridge, AiaPgNonceCandidateLedger, AiaReadOnlyMockBoundary
};
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * F232: real throwaway PG16 atomic nonce storage + original pinned Agents
 * Ed25519 verifier, while CRM business access always remains DENIED.
 *
 * The test never runs against a non-disposable tenant or a production DB.
 */
final class AiaPostgresEd25519NonceCandidateTest extends TestCase
{
    private const TABLE = 'public.aia_lab_nonce_atomic_probe';
    private const CLAIMS_DOMAIN = "SYNTHETIC_AIA_SERVICE_ED25519_V1\n";
    private bool $tableCreated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_ED25519_PG_LAB') !== 'true') {
            $this->markTestSkipped('F232 has a dedicated CI test job and pinned Agents source.');
        }
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci') {
            throw new RuntimeException('F232_NON_DISPOSABLE_DATABASE_DENIED');
        }
        $identity = DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$identity || $identity->dbname !== 'imagine_crm_ci'
            || $identity->username !== 'postgres') {
            throw new RuntimeException('F232_DATABASE_IDENTITY_DENIED');
        }
        $agents = dirname(__DIR__, 2).'/_aia_f231_fixture';
        if (!is_file($agents.'/src/MockBridge/MockServiceIdentityPreflight.php')
            || !is_file($agents.'/src/MockBridge/MockCrmReadRequest.php')) {
            throw new RuntimeException('F232_PINNED_AGENTS_SOURCE_MISSING');
        }
        spl_autoload_register(static function (string $name) use ($agents): void {
            $prefix = 'ImagineAI\\';
            if (str_starts_with($name, $prefix)) {
                require_once $agents.'/src/'.str_replace(
                    '\\', '/', substr($name, strlen($prefix))
                ).'.php';
            }
        });

        DB::statement('CREATE TABLE '.self::TABLE.' (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            nonce varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            claimed_at bigint NOT NULL,
            PRIMARY KEY (issuer,audience,nonce)
        )');
        $this->tableCreated = true;

        $settings = config('database.connections.pgsql');
        $settings['url'] = null;
        config(['database.connections.aia_f232_second' => $settings]);
        DB::purge('aia_f232_second');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->tableCreated) {
                $one = DB::connection();
                if ($one->transactionLevel() > 0) {
                    $one->rollBack();
                }
                $one->statement('DROP TABLE IF EXISTS '.self::TABLE);
                DB::disconnect('aia_f232_second');
                DB::purge('aia_f232_second');
            }
        } finally {
            parent::tearDown();
        }
    }

    private static function request(string $tenant): string
    {
        return \ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref' => 'SYNTHETIC_F232_READ_REQUEST',
            'operation' => 'crm.lead.read_minimal',
            'tenant_ref' => $tenant,
            'principal_ref' => 'SYNTHETIC_F232_USER',
            'resource_ref' => 'SYNTHETIC_F232_LEAD',
            'purpose_ref' => 'SYNTHETIC_F232_LAB',
            'fields' => ['ref', 'stage'],
        ]);
    }

    /** @return array{0:string,1:string} */
    private static function signed(
        string $request,
        string $tenant,
        string $nonce,
        string $secret,
        array $override = [],
    ): array {
        $claims = array_replace([
            'contract' => 'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer' => 'SYNTHETIC_AGENT_SERVICE',
            'audience' => 'SYNTHETIC_CRM_API',
            'key_id' => 'SYNTHETIC_F232_KEY',
            'subject' => 'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref' => $tenant,
            'operation' => 'crm.lead.read_minimal',
            'nonce' => $nonce,
            'issued_at' => 1794690000,
            'expires_at' => 1794690020,
            'request_sha256' => hash('sha256', $request),
        ], $override);
        $bytes = json_encode(
            $claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        return [
            $bytes,
            base64_encode(sodium_crypto_sign_detached(
                self::CLAIMS_DOMAIN.$bytes, $secret
            )),
        ];
    }

    /** @return array{0:string,1:string,2:array<string,array{public_key:string,enabled:bool}>} */
    private static function publicTestKeys(): array
    {
        // Public deterministic test seed, unsuitable for any real identity.
        $pair = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'PUBLIC_F232_CI_ED25519_ONLY', true)
        );
        return [
            sodium_crypto_sign_secretkey($pair),
            sodium_crypto_sign_publickey($pair),
            [
                'SYNTHETIC_F232_KEY' => [
                    'public_key' => sodium_crypto_sign_publickey($pair),
                    'enabled' => true,
                ],
            ],
        ];
    }

    private static function assertDenial(array $result): void
    {
        self::assertSame([
            'contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state' => 'DENIED_OR_NOT_FOUND',
            'projection' => null,
            'real_authorization' => false,
            'external_calls' => 0,
        ], $result);
    }

    public function test_real_pg16_rejects_replay_across_two_connections_and_tenants(): void
    {
        [$secret, $public, $trusted] = self::publicTestKeys();
        $this->assertSame(SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, strlen($public));
        $one = new AiaPgNonceCandidateLedger(DB::connection());
        $two = new AiaPgNonceCandidateLedger(DB::connection('aia_f232_second'));
        $pidA = (int) DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $pidB = (int) DB::connection('aia_f232_second')
            ->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $this->assertNotSame($pidA, $pidB);

        foreach (['SYNTHETIC_TENANT_A','SYNTHETIC_TENANT_B','SYNTHETIC_TENANT_C'] as $i => $tenant) {
            $request = self::request($tenant);
            $nonce = 'SYNTHETIC_F232_PG_NONCE_'.$i;
            [$claims,$sig] = self::signed($request, $tenant, $nonce, $secret);
            $this->assertTrue(AiaPgEd25519LabBridge::matchesLabFixture(
                $request,$claims,$sig,$trusted,$tenant,1794690010,$one
            ));
            self::assertDenial(AiaPgEd25519LabBridge::inspectLabFixture(
                $request,$claims,$sig,$trusted,$tenant,1794690010,$two
            ));
            $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
                $request,$claims,$sig,$trusted,$tenant,1794690010,$two
            ));
            $other = $tenant === 'SYNTHETIC_TENANT_A'
                ? 'SYNTHETIC_TENANT_B' : 'SYNTHETIC_TENANT_A';
            $otherReq = self::request($other);
            [$otherClaims,$otherSig] = self::signed(
                $otherReq,$other,$nonce,$secret
            );
            $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
                $otherReq,$otherClaims,$otherSig,$trusted,$other,1794690010,$two
            ));
        }
        $this->assertSame(3, (int) DB::table('aia_lab_nonce_atomic_probe')->count());
        $this->assertSame(0, DB::connection()->transactionLevel());
    }

    public function test_invalid_proofs_fail_before_any_postgres_nonce_insert(): void
    {
        [$secret, $public, $trusted] = self::publicTestKeys();
        $tenant = 'SYNTHETIC_TENANT_A';
        $request = self::request($tenant);
        $nonce = 'SYNTHETIC_F232_NEGATIVE_NONCE';
        [$body,$signature] = self::signed($request,$tenant,$nonce,$secret);
        $wrongTenant = self::signed($request,$tenant,$nonce,$secret,[
            'tenant_ref' => 'SYNTHETIC_TENANT_B',
        ]);
        $wrongScope = self::signed($request,$tenant,$nonce,$secret,[
            'operation' => 'crm.lead.write',
        ]);
        $wrongAudience = self::signed($request,$tenant,$nonce,$secret,[
            'audience' => 'SYNTHETIC_OTHER',
        ]);
        $wrongBodyHash = self::signed($request,$tenant,$nonce,$secret,[
            'request_sha256' => str_repeat('0',64),
        ]);
        $wrongKey = self::signed($request,$tenant,$nonce,$secret,[
            'key_id' => 'SYNTHETIC_UNTRUSTED',
        ]);
        $expired = self::signed($request,$tenant,$nonce,$secret,[
            'expires_at' => 1794690005,
        ]);
        $invalids = [
            [$request,$wrongTenant[0],$wrongTenant[1],$trusted,1794690010],
            [$request,$wrongScope[0],$wrongScope[1],$trusted,1794690010],
            [$request,$wrongAudience[0],$wrongAudience[1],$trusted,1794690010],
            [$request,$wrongBodyHash[0],$wrongBodyHash[1],$trusted,1794690010],
            [$request,$wrongKey[0],$wrongKey[1],$trusted,1794690010],
            [$request,$expired[0],$expired[1],$trusted,1794690010],
            [$request,$body,$signature,[],1794690010],
            [$request,$body,$signature,['SYNTHETIC_F232_KEY'=>[
                'public_key'=>$public,'enabled'=>false,
            ]],1794690010],
            [$request,$body,$signature,$trusted,1794690020],
            [self::request('SYNTHETIC_TENANT_B'),$body,$signature,$trusted,1794690010],
            [$request,$body,'INVALID_SIGNATURE',$trusted,1794690010],
        ];
        $this->assertCount(11,$invalids);
        $ledger = new AiaPgNonceCandidateLedger(DB::connection());
        foreach ($invalids as [$req,$claims,$sig,$keys,$clock]) {
            $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
                $req,$claims,$sig,$keys,$tenant,$clock,$ledger
            ));
        }
        $this->assertSame(0, (int) DB::table('aia_lab_nonce_atomic_probe')->count());
        // Invalid signatures never poison the valid nonce: claim exactly once.
        $this->assertTrue(AiaPgEd25519LabBridge::matchesLabFixture(
            $request,$body,$signature,$trusted,$tenant,1794690010,$ledger
        ));
        $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
            $request,$body,$signature,$trusted,$tenant,1794690010,$ledger
        ));
        $this->assertSame(1, (int) DB::table('aia_lab_nonce_atomic_probe')->count());
    }

    public function test_server_owned_pg_keyring_revocation_rotation_and_tenant_scope(): void
    {
        [$privateA, $publicA] = self::publicTestKeys();
        $pairB = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'PUBLIC_F232_ROTATION_TEST_ONLY', true)
        );
        $privateB = sodium_crypto_sign_secretkey($pairB);
        $publicB = sodium_crypto_sign_publickey($pairB);
        $tenant = 'SYNTHETIC_TENANT_A';
        $other = 'SYNTHETIC_TENANT_B';
        $keyId = 'SYNTHETIC_F232_KEY';
        $request = self::request($tenant);
        $ledger = new AiaPgNonceCandidateLedger(DB::connection('aia_f232_second'));

        DB::statement('CREATE TABLE public.aia_lab_ed25519_service_keys (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            key_id varchar(90) NOT NULL,
            public_key_base64 varchar(44) NOT NULL,
            enabled boolean NOT NULL,
            revoked_at bigint NULL,
            revision integer NOT NULL,
            PRIMARY KEY (tenant_ref,key_id)
        )');
        try {
            $scope = [
                'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                'audience' => 'SYNTHETIC_CRM_API',
                'tenant_ref' => $tenant,
                'key_id' => $keyId,
            ];
            DB::table('aia_lab_ed25519_service_keys')->insert(
                $scope + [
                    'public_key_base64' => base64_encode($publicA),
                    'enabled' => true, 'revoked_at' => null, 'revision' => 1,
                ]
            );
            $keyring = new \App\Support\Synthetic\AiaPgServiceKeyringLab(
                DB::connection()
            );

            $this->assertSame([], $keyring->trustedPublicKey($keyId, $other));
            $this->assertSame([], $keyring->trustedPublicKey(
                'SYNTHETIC_F232_UNKNOWN', $tenant
            ));
            $this->assertSame(
                $publicA,
                $keyring->trustedPublicKey($keyId, $tenant)[$keyId]['public_key']
            );
            [$claimsA,$sigA] = self::signed(
                $request,$tenant,'SYNTHETIC_F232_KEYRING_NONCE_A',$privateA
            );
            $this->assertTrue(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $request,$claimsA,$sigA,$tenant,1794690010,$keyring,$ledger
            ));
            $this->assertSame(1, (int) DB::table('aia_lab_nonce_atomic_probe')->count());
            // A client-supplied test key never overrides the server row:
            // only a database-trusted key can be selected.
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $request,$claimsA,$sigA,$tenant,1794690010,$keyring,$ledger
            ));
            self::assertDenial(AiaPgEd25519LabBridge::inspectWithServerKeyring(
                $request,$claimsA,$sigA,$tenant,1794690010,$keyring,$ledger
            ));

            DB::table('aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenant)
                ->where('key_id',$keyId)
                ->update(['revoked_at'=>1794690005,'revision'=>2]);
            $this->assertSame([], $keyring->trustedPublicKey($keyId,$tenant));
            [$claimsB,$sigB] = self::signed(
                $request,$tenant,'SYNTHETIC_F232_KEYRING_NONCE_B',$privateA
            );
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $request,$claimsB,$sigB,$tenant,1794690010,$keyring,$ledger
            ));
            $this->assertSame(1, (int) DB::table('aia_lab_nonce_atomic_probe')->count());

            // Rotate the server-owned public key and reject the old signer
            // before it can consume the next nonce.
            DB::table('aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenant)
                ->where('key_id',$keyId)
                ->update([
                    'revoked_at'=>null,
                    'public_key_base64'=>base64_encode($publicB),
                    'revision'=>3,
                ]);
            $this->assertSame(
                $publicB,
                $keyring->trustedPublicKey($keyId,$tenant)[$keyId]['public_key']
            );
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $request,$claimsB,$sigB,$tenant,1794690010,$keyring,$ledger
            ));
            $this->assertSame(1, (int) DB::table('aia_lab_nonce_atomic_probe')->count());

            [$rotatedClaims,$rotatedSig] = self::signed(
                $request,$tenant,'SYNTHETIC_F232_KEYRING_NONCE_B',$privateB
            );
            $this->assertTrue(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $request,$rotatedClaims,$rotatedSig,$tenant,1794690010,$keyring,$ledger
            ));
            $this->assertSame(2, (int) DB::table('aia_lab_nonce_atomic_probe')->count());

            // Removing the key registry must fail closed without damaging the
            // already written replay evidence or exposing a positive read.
            DB::statement('DROP TABLE public.aia_lab_ed25519_service_keys');
            $this->assertSame([], $keyring->trustedPublicKey($keyId,$tenant));
            self::assertDenial(AiaPgEd25519LabBridge::inspectWithServerKeyring(
                $request,$rotatedClaims,$rotatedSig,$tenant,1794690010,
                $keyring,$ledger
            ));
            $this->assertSame(2, (int) DB::table('aia_lab_nonce_atomic_probe')->count());
        } finally {
            DB::statement('DROP TABLE IF EXISTS public.aia_lab_ed25519_service_keys');
        }
    }

    /**
     * F233: an Ed25519 signature and PG nonce are not sufficient to read.
     * Service delegation, resource owner and each permitted field are
     * independent synthetic facts controlled by the CRM-side test database.
     */
    public function test_pg16_service_delegation_owner_and_fields_are_not_real_authorization(): void
    {
        [$secret, $public] = self::publicTestKeys();
        $tenant = 'SYNTHETIC_TENANT_A';
        $other = 'SYNTHETIC_TENANT_B';
        $kid = 'SYNTHETIC_F232_KEY';
        $principal = 'SYNTHETIC_F232_USER';
        $resource = 'SYNTHETIC_F232_LEAD';
        $purpose = 'SYNTHETIC_F232_LAB';
        $now = 1794690010;

        $db = DB::connection();
        // These three policy tables and the public-key table exist ONLY in
        // this disposable CI test, never in a real migration.
        $db->statement('CREATE TABLE public.aia_lab_ed25519_service_keys (
            issuer varchar(90) NOT NULL, audience varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL, key_id varchar(90) NOT NULL,
            public_key_base64 varchar(44) NOT NULL, enabled boolean NOT NULL,
            revoked_at bigint NULL, revision integer NOT NULL,
            PRIMARY KEY (tenant_ref,key_id)
        )');
        try {
            $db->statement('CREATE TABLE public.aia_lab_service_delegations (
                tenant_ref varchar(90) NOT NULL, key_id varchar(90) NOT NULL,
                principal_ref varchar(90) NOT NULL, purpose_ref varchar(90) NOT NULL,
                operation varchar(40) NOT NULL, enabled boolean NOT NULL,
                revoked_at bigint NULL, not_before bigint NOT NULL,
                expires_at bigint NOT NULL, revision integer NOT NULL,
                PRIMARY KEY (tenant_ref,key_id,principal_ref,purpose_ref,operation)
            )');
            $db->statement('CREATE TABLE public.aia_lab_resource_owners (
                tenant_ref varchar(90) NOT NULL, resource_ref varchar(90) NOT NULL,
                principal_ref varchar(90) NOT NULL, status varchar(20) NOT NULL,
                PRIMARY KEY (tenant_ref,resource_ref)
            )');
            $db->statement('CREATE TABLE public.aia_lab_service_field_grants (
                tenant_ref varchar(90) NOT NULL, key_id varchar(90) NOT NULL,
                principal_ref varchar(90) NOT NULL, resource_ref varchar(90) NOT NULL,
                field_code varchar(30) NOT NULL, enabled boolean NOT NULL,
                PRIMARY KEY (tenant_ref,key_id,principal_ref,resource_ref,field_code)
            )');

            $db->table('aia_lab_ed25519_service_keys')->insert([
                [
                    'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                    'audience' => 'SYNTHETIC_CRM_API',
                    'tenant_ref' => $tenant, 'key_id' => $kid,
                    'public_key_base64' => base64_encode($public),
                    'enabled' => true, 'revoked_at' => null, 'revision' => 1,
                ],
                [
                    // Even a correctly signed tenant B request has no
                    // delegation or records. No cross-company inheritance.
                    'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                    'audience' => 'SYNTHETIC_CRM_API',
                    'tenant_ref' => $other, 'key_id' => $kid,
                    'public_key_base64' => base64_encode($public),
                    'enabled' => true, 'revoked_at' => null, 'revision' => 1,
                ],
            ]);
            $delegation = [
                'tenant_ref' => $tenant, 'key_id' => $kid,
                'principal_ref' => $principal, 'purpose_ref' => $purpose,
                'operation' => 'crm.lead.read_minimal', 'enabled' => true,
                'revoked_at' => null, 'not_before' => 1794690000,
                'expires_at' => 1794690020, 'revision' => 1,
            ];
            $db->table('aia_lab_service_delegations')->insert($delegation);
            $db->table('aia_lab_resource_owners')->insert([
                'tenant_ref' => $tenant, 'resource_ref' => $resource,
                'principal_ref' => $principal, 'status' => 'active',
            ]);
            foreach (['ref','stage'] as $field) {
                $db->table('aia_lab_service_field_grants')->insert([
                    'tenant_ref' => $tenant, 'key_id' => $kid,
                    'principal_ref' => $principal, 'resource_ref' => $resource,
                    'field_code' => $field, 'enabled' => true,
                ]);
            }

            // F234: the reviewer reads under a non-owner SELECT-only role.
            // A disabled tenant B fixture exists to expose real RLS filtering.
            $db->table('aia_lab_service_delegations')->insert(
                array_replace($delegation, [
                    'tenant_ref' => $other, 'enabled' => false,
                ])
            );
            $db->table('aia_lab_resource_owners')->insert([
                'tenant_ref' => $other, 'resource_ref' => $resource,
                'principal_ref' => $principal, 'status' => 'active',
            ]);
            $db->table('aia_lab_service_field_grants')->insert([
                'tenant_ref' => $other, 'key_id' => $kid,
                'principal_ref' => $principal, 'resource_ref' => $resource,
                'field_code' => 'ref', 'enabled' => true,
            ]);

            $db->statement(
                "CREATE ROLE crm_aia_scope_ci LOGIN PASSWORD 'ci-aia-scope-only'
                NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS"
            );
            $db->statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_aia_scope_ci');
            $db->statement('GRANT USAGE ON SCHEMA public TO crm_aia_scope_ci');
            foreach ([
                'aia_lab_service_delegations',
                'aia_lab_resource_owners',
                'aia_lab_service_field_grants',
            ] as $table) {
                $db->statement("ALTER TABLE public.$table ENABLE ROW LEVEL SECURITY");
                $db->statement("ALTER TABLE public.$table FORCE ROW LEVEL SECURITY");
                $db->statement("CREATE POLICY aia_f234_tenant_select ON public.$table
                    FOR SELECT TO crm_aia_scope_ci
                    USING (tenant_ref = current_setting('app.tenant_id', true))");
                $db->statement("GRANT SELECT ON public.$table TO crm_aia_scope_ci");
            }
            $settings = config('database.connections.pgsql');
            $settings['url'] = null;
            $settings['username'] = 'crm_aia_scope_ci';
            $settings['password'] = 'ci-aia-scope-only';
            config(['database.connections.aia_f234_scope_read' => $settings]);
            DB::purge('aia_f234_scope_read');
            $restricted = DB::connection('aia_f234_scope_read');

            $who = $restricted->selectOne(
                'SELECT current_user AS username,
                    r.rolsuper, r.rolbypassrls FROM pg_roles r
                 WHERE r.rolname = current_user'
            );
            $this->assertSame('crm_aia_scope_ci', $who->username);
            $this->assertFalse((bool) $who->rolsuper);
            $this->assertFalse((bool) $who->rolbypassrls);
            $this->assertSame(0,(int) $restricted
                ->table('public.aia_lab_service_delegations')->count());

            // Even with same table schema, RLS shows only the selected company.
            foreach ([[$tenant,1],[$other,1]] as [$company,$visibleCount]) {
                $restricted->transaction(function () use (
                    $restricted,$company,$visibleCount
                ): void {
                    $restricted->selectOne(
                        "SELECT set_config('app.tenant_id', ?, true)",
                        [$company]
                    );
                    $this->assertSame($visibleCount,(int) $restricted
                        ->table('public.aia_lab_service_delegations')->count());
                    $this->assertSame($visibleCount,(int) $restricted
                        ->table('public.aia_lab_resource_owners')->count());
                });
                $this->assertSame(0,(int) $restricted
                    ->table('public.aia_lab_service_delegations')->count());
            }
            // A SELECT-only policy role cannot mutate scope facts or read IAM.
            $writeBlocked = false;
            try {
                $restricted->table('public.aia_lab_service_delegations')
                    ->where('tenant_ref',$tenant)->update(['enabled'=>true]);
            } catch (\Throwable) {
                $writeBlocked = true;
            }
            $this->assertTrue($writeBlocked);

            $keyring = new \App\Support\Synthetic\AiaPgServiceKeyringLab($db);
            $scope = new \App\Support\Synthetic\AiaPgServiceScopeLab($restricted);
            $ledger = new AiaPgNonceCandidateLedger(
                DB::connection('aia_f232_second')
            );
            $body = self::request($tenant);
            $counter = 0;
            $probe = function (
                string $request,
                string $expectedCompany,
                bool $expect,
            ) use ($secret,$keyring,$scope,$ledger,$restricted,$now,&$counter): void {
                ++$counter;
                $nonce = 'SYNTHETIC_F233_SCOPED_NONCE_'.$counter;
                [$claims,$signature] = self::signed(
                    $request,$expectedCompany,$nonce,$secret
                );
                $before = (int) DB::table('aia_lab_nonce_atomic_probe')->count();
                $match = AiaPgEd25519LabBridge::matchesWithServerScope(
                    $request,$claims,$signature,$expectedCompany,$now,
                    $keyring,$ledger,$scope
                );
                $this->assertSame($expect,$match);
                // Even a matching synthetic policy is never released to
                // callers; it only proves a code path in this lab.
                self::assertDenial(AiaReadOnlyMockBoundary::inspectCanonicalJson($request));
                $after = (int) DB::table('aia_lab_nonce_atomic_probe')->count();
                // Valid signatures consume nonce even when policy denies.
                $this->assertGreaterThanOrEqual($before,$after);
                $this->assertLessThanOrEqual($before+1,$after);
                $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerScope(
                    $request,$claims,$signature,$expectedCompany,$now,
                    $keyring,$ledger,$scope
                ));
                $this->assertSame(0,$restricted->transactionLevel());
                $marker = $restricted->selectOne(
                    "SELECT current_setting('app.tenant_id',true) AS tenant"
                );
                $this->assertContains($marker->tenant,[null,'']);
            };
            $alterBody = static function (array $change) use ($body): string {
                $original = json_decode($body,true,4,JSON_THROW_ON_ERROR);
                return \ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson(
                    array_replace($original,$change)
                );
            };

            $probe($body,$tenant,true);
            $probe(self::request($other),$other,false); // tenant B has key only

            // No independent delegation or owner record, no candidate.
            $db->table('aia_lab_service_delegations')
                ->where('tenant_ref',$tenant)->update(['enabled'=>false]);
            $probe($body,$tenant,false);
            $db->table('aia_lab_service_delegations')
                ->where('tenant_ref',$tenant)->update(['enabled'=>true]);

            $db->table('aia_lab_service_delegations')
                ->where('tenant_ref',$tenant)->update(['revoked_at'=>1794690005]);
            $probe($body,$tenant,false);
            $db->table('aia_lab_service_delegations')
                ->where('tenant_ref',$tenant)->update(['revoked_at'=>null]);

            $db->table('aia_lab_service_delegations')
                ->where('tenant_ref',$tenant)->update(['expires_at'=>$now]);
            $probe($body,$tenant,false);
            $db->table('aia_lab_service_delegations')
                ->where('tenant_ref',$tenant)->update(['expires_at'=>1794690020]);

            $db->table('aia_lab_resource_owners')
                ->where('tenant_ref',$tenant)->update(['status'=>'revoked']);
            $probe($body,$tenant,false);
            $db->table('aia_lab_resource_owners')
                ->where('tenant_ref',$tenant)->update(['status'=>'active']);

            $db->table('aia_lab_service_field_grants')
                ->where('tenant_ref',$tenant)
                ->where('field_code','stage')->update(['enabled'=>false]);
            $probe($body,$tenant,false);
            // A narrower projection can match when only ref is active.
            $probe($alterBody(['fields'=>['ref']]),$tenant,true);
            $db->table('aia_lab_service_field_grants')
                ->where('tenant_ref',$tenant)
                ->where('field_code','stage')->update(['enabled'=>true]);
            $probe($body,$tenant,true);

            $probe($alterBody(['principal_ref'=>'SYNTHETIC_OTHER_PRINCIPAL']),$tenant,false);
            $probe($alterBody(['resource_ref'=>'SYNTHETIC_OTHER_LEAD']),$tenant,false);
            $probe($alterBody(['purpose_ref'=>'SYNTHETIC_OTHER_PURPOSE']),$tenant,false);

            // Signature proof still cannot inherit another tenant's owner
            // or field facts even when the service key happens to be the same.
            $this->assertSame(2,(int) $db->table('aia_lab_ed25519_service_keys')->count());
            $this->assertSame(2,(int) $db->table('aia_lab_resource_owners')->count());
            $this->assertSame(3,(int) $db->table('aia_lab_service_field_grants')->count());

            // A revoked service key prevents PG nonce claim and any policy
            // lookup; a public result never reveals which fact failed.
            $db->table('aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenant)->update(['revoked_at'=>$now]);
            $before = (int) DB::table('aia_lab_nonce_atomic_probe')->count();
            $probe($body,$tenant,false);
            $this->assertSame(
                $before,(int) DB::table('aia_lab_nonce_atomic_probe')->count()
            );
            $db->table('aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenant)->update(['revoked_at'=>null]);

            // Failure of the server-owned delegation table must deny
            // even with a valid signer and previously valid scope.
            $db->statement('DROP TABLE public.aia_lab_service_delegations');
            $probe($body,$tenant,false);

            $this->assertSame(14,$counter);
        } finally {
            DB::disconnect('aia_f234_scope_read');
            DB::purge('aia_f234_scope_read');
            $db->statement('DROP TABLE IF EXISTS public.aia_lab_service_field_grants');
            $db->statement('DROP TABLE IF EXISTS public.aia_lab_resource_owners');
            $db->statement('DROP TABLE IF EXISTS public.aia_lab_service_delegations');
            $db->statement('DROP TABLE IF EXISTS public.aia_lab_ed25519_service_keys');
            $db->statement('REVOKE USAGE ON SCHEMA public FROM crm_aia_scope_ci');
            $db->statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM crm_aia_scope_ci');
            $db->statement('DROP ROLE IF EXISTS crm_aia_scope_ci');
        }
    }

    public function test_pg_backend_outage_is_uniformly_denied_even_if_ed25519_passes(): void
    {
        [$secret, ,$trusted] = self::publicTestKeys();
        $tenant='SYNTHETIC_TENANT_A';
        $request=self::request($tenant);
        [$body,$sig]=self::signed($request,$tenant,'SYNTHETIC_F232_OUTAGE_NONCE',$secret);
        $ledger=new AiaPgNonceCandidateLedger(DB::connection('aia_f232_second'));

        DB::statement('DROP TABLE '.self::TABLE);
        $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
            $request,$body,$sig,$trusted,$tenant,1794690010,$ledger
        ));
        self::assertDenial(AiaPgEd25519LabBridge::inspectLabFixture(
            $request,$body,$sig,$trusted,$tenant,1794690010,$ledger
        ));
        self::assertDenial(AiaReadOnlyMockBoundary::inspectCanonicalJson($request));
    }
    /**
     * F240: the real original Agents Ed25519 verifier reads a CRM-owned
     * public key through a nonowner SELECT-only PG16 connection under RLS.
     * All signer keys and companies are artificial; no service endpoint.
     */
    public function test_f240_restricted_service_public_keys_rotate_revoke_and_fail_closed(): void
    {
        if (getenv('AIA_F240_KEYRING_LAB') !== 'true') {
            $this->markTestSkipped('F240 requires isolated PG16 CI verifier role.');
        }

        $admin = DB::connection();
        $tenantA = 'SYNTHETIC_TENANT_A';
        $tenantB = 'SYNTHETIC_TENANT_B';
        $keyId = 'SYNTHETIC_F232_KEY';
        $keyA = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'PUBLIC_F240_KEY_A_ONLY', true)
        );
        $keyB = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'PUBLIC_F240_KEY_B_ONLY', true)
        );
        $rotatedA = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'PUBLIC_F240_KEY_A_ROTATED_ONLY', true)
        );
        $pubA = sodium_crypto_sign_publickey($keyA);
        $pubB = sodium_crypto_sign_publickey($keyB);
        $newPubA = sodium_crypto_sign_publickey($rotatedA);
        $privateA = sodium_crypto_sign_secretkey($keyA);
        $privateB = sodium_crypto_sign_secretkey($keyB);
        $newPrivateA = sodium_crypto_sign_secretkey($rotatedA);

        $admin->statement('CREATE TABLE public.aia_lab_ed25519_service_keys (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            key_id varchar(90) NOT NULL,
            public_key_base64 varchar(44) NOT NULL,
            enabled boolean NOT NULL,
            revoked_at bigint NULL,
            revision integer NOT NULL,
            PRIMARY KEY (tenant_ref,key_id)
        )');

        $roleCreated = false;
        try {
            $admin->statement('CREATE ROLE crm_aia_key_verify_ci LOGIN
                PASSWORD '."'f240-public-test-only'".'
                NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS');
            $roleCreated = true;
            $admin->statement('GRANT CONNECT ON DATABASE imagine_crm_ci
                TO crm_aia_key_verify_ci');
            $admin->statement('GRANT USAGE ON SCHEMA public
                TO crm_aia_key_verify_ci');
            $admin->statement('ALTER TABLE public.aia_lab_ed25519_service_keys
                ENABLE ROW LEVEL SECURITY');
            $admin->statement('ALTER TABLE public.aia_lab_ed25519_service_keys
                FORCE ROW LEVEL SECURITY');
            $admin->statement("CREATE POLICY aia_f240_verify_public_key
                ON public.aia_lab_ed25519_service_keys
                FOR SELECT TO crm_aia_key_verify_ci
                USING (tenant_ref = current_setting('app.tenant_id',true))");
            $admin->statement('GRANT SELECT ON TABLE
                public.aia_lab_ed25519_service_keys TO crm_aia_key_verify_ci');

            $scope = [
                'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                'audience' => 'SYNTHETIC_CRM_API',
                'key_id' => $keyId,
            ];
            foreach ([
                [$tenantA, $pubA],
                [$tenantB, $pubB],
            ] as [$tenant, $public]) {
                $admin->table('public.aia_lab_ed25519_service_keys')
                    ->insert($scope + [
                        'tenant_ref' => $tenant,
                        'public_key_base64' => base64_encode($public),
                        'enabled' => true,
                        'revoked_at' => null,
                        'revision' => 1,
                    ]);
            }

            $settings = config('database.connections.pgsql');
            $settings['url'] = null;
            $settings['username'] = 'crm_aia_key_verify_ci';
            $settings['password'] = 'f240-public-test-only';
            config(['database.connections.aia_f240_public_keys' => $settings]);
            DB::purge('aia_f240_public_keys');
            $restricted = DB::connection('aia_f240_public_keys');
            $restrictedKeyring = new \App\Support\Synthetic\AiaPgRestrictedKeyringLab(
                $restricted
            );

            $this->assertSame('crm_aia_key_verify_ci',
                $restricted->selectOne('SELECT current_user AS username')->username);
            $this->assertSame(0, $restricted->transactionLevel());
            $this->assertSame(0, (int) $restricted
                ->table('public.aia_lab_ed25519_service_keys')->count());
            $this->assertSame($pubA,
                $restrictedKeyring->trustedPublicKey($keyId,$tenantA)[$keyId]['public_key']);
            $this->assertSame($pubB,
                $restrictedKeyring->trustedPublicKey($keyId,$tenantB)[$keyId]['public_key']);
            $this->assertSame(0, $restricted->transactionLevel());
            $this->assertSame(0, (int) $restricted
                ->table('public.aia_lab_ed25519_service_keys')->count());
            $this->assertSame([],
                $restrictedKeyring->trustedPublicKey('SYNTHETIC_UNKNOWN',$tenantA));
            $this->assertSame([],
                $restrictedKeyring->trustedPublicKey($keyId,'INVALID_TENANT'));

            // Even a compromised verifier connection does not have SQL ACL
            // to create, rotate, revoke, delete, or inspect CRM identities.
            foreach ([
                static fn ($db) => $db->table(
                    'public.aia_lab_ed25519_service_keys'
                )->where('tenant_ref','SYNTHETIC_TENANT_A')
                  ->update(['revoked_at' => 1794690001]),
                static fn ($db) => $db->table(
                    'public.aia_lab_ed25519_service_keys'
                )->where('tenant_ref','SYNTHETIC_TENANT_A')->delete(),
                static fn ($db) => $db->table(
                    'public.aia_lab_ed25519_service_keys'
                )->insert([
                    'issuer'=>'SYNTHETIC_AGENT_SERVICE',
                    'audience'=>'SYNTHETIC_CRM_API',
                    'tenant_ref'=>'SYNTHETIC_TENANT_C',
                    'key_id'=>'SYNTHETIC_F240_FORGED',
                    'public_key_base64'=>base64_encode($pubA),
                    'enabled'=>true,'revoked_at'=>null,'revision'=>1,
                ]),
                static fn ($db) => $db->table('public.crm_auth_sessions')->count(),
                static fn ($db) => $db->table('public.crm_memberships')->count(),
            ] as $operation) {
                $denied = false;
                try {
                    $operation($restricted);
                } catch (\Throwable) {
                    $denied = true;
                }
                $this->assertTrue($denied, 'Public verifier role gained extra SQL privileges');
            }
            $this->assertSame(0, $restricted->transactionLevel());

            $ledger = new AiaPgNonceCandidateLedger(
                DB::connection('aia_f232_second')
            );
            $requestA = self::request($tenantA);
            $requestB = self::request($tenantB);
            [$claimsA,$sigA] = self::signed(
                $requestA,$tenantA,'SYNTHETIC_F240_FIRST_A',$privateA
            );
            [$claimsB,$sigB] = self::signed(
                $requestB,$tenantB,'SYNTHETIC_F240_FIRST_B',$privateB
            );
            $this->assertTrue(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestA,$claimsA,$sigA,$tenantA,1794690010,$restrictedKeyring,$ledger
            ));
            $this->assertTrue(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestB,$claimsB,$sigB,$tenantB,1794690010,$restrictedKeyring,$ledger
            ));
            $this->assertSame(2, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestB,$claimsB,$sigB,$tenantA,1794690010,$restrictedKeyring,$ledger
            ));

            // Revoked key: do not consume nonce despite a correct OLD
            // signature, even if the customer-provided key selector matches.
            $admin->table('public.aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenantA)->update([
                    'revoked_at'=>1794690005,'revision'=>2,
                ]);
            $this->assertSame([],
                $restrictedKeyring->trustedPublicKey($keyId,$tenantA));
            [$revokedClaims,$revokedSig] = self::signed(
                $requestA,$tenantA,'SYNTHETIC_F240_NEXT_A',$privateA
            );
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestA,$revokedClaims,$revokedSig,$tenantA,1794690010,
                $restrictedKeyring,$ledger
            ));
            $this->assertSame(2, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            // Simulated CRM-controlled rotation to new PUBLIC key bytes.
            // The old signer cannot spend a fresh nonce. New signer can.
            $admin->table('public.aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenantA)->update([
                    'public_key_base64'=>base64_encode($newPubA),
                    'revoked_at'=>null,'revision'=>3,'enabled'=>true,
                ]);
            $this->assertSame($newPubA,
                $restrictedKeyring->trustedPublicKey($keyId,$tenantA)[$keyId]['public_key']);
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestA,$revokedClaims,$revokedSig,$tenantA,1794690010,
                $restrictedKeyring,$ledger
            ));
            [$rotatedClaims,$rotatedSig] = self::signed(
                $requestA,$tenantA,'SYNTHETIC_F240_NEXT_A',$newPrivateA
            );
            $this->assertTrue(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestA,$rotatedClaims,$rotatedSig,$tenantA,1794690010,
                $restrictedKeyring,$ledger
            ));
            $this->assertSame(3, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            // Disabling B cannot affect A's public key. Invalid key rows
            // and unavailable key storage fail closed, without fallback.
            $admin->table('public.aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenantB)->update([
                    'enabled'=>false,'revision'=>2,
                ]);
            $this->assertSame([],
                $restrictedKeyring->trustedPublicKey($keyId,$tenantB));
            $this->assertSame($newPubA,
                $restrictedKeyring->trustedPublicKey($keyId,$tenantA)[$keyId]['public_key']);
            $admin->table('public.aia_lab_ed25519_service_keys')
                ->where('tenant_ref',$tenantB)->update([
                    'enabled'=>true,'public_key_base64'=>'not-a-valid-base64',
                    'revision'=>3,
                ]);
            $this->assertSame([],
                $restrictedKeyring->trustedPublicKey($keyId,$tenantB));
            $admin->statement('DROP TABLE public.aia_lab_ed25519_service_keys');
            $this->assertSame([],
                $restrictedKeyring->trustedPublicKey($keyId,$tenantA));
            [$missingClaims,$missingSig] = self::signed(
                $requestA,$tenantA,'SYNTHETIC_F240_MISSING_TABLE',$newPrivateA
            );
            $this->assertFalse(AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $requestA,$missingClaims,$missingSig,$tenantA,1794690010,
                $restrictedKeyring,$ledger
            ));
            $this->assertSame(3, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            self::assertDenial(AiaPgEd25519LabBridge::inspectWithServerKeyring(
                $requestA,$missingClaims,$missingSig,$tenantA,1794690010,
                $restrictedKeyring,$ledger
            ));
        } finally {
            DB::disconnect('aia_f240_public_keys');
            DB::purge('aia_f240_public_keys');
            $admin->statement('DROP TABLE IF EXISTS
                public.aia_lab_ed25519_service_keys');
            if ($roleCreated) {
                $admin->statement('REVOKE USAGE ON SCHEMA public
                    FROM crm_aia_key_verify_ci');
                $admin->statement('REVOKE CONNECT ON DATABASE imagine_crm_ci
                    FROM crm_aia_key_verify_ci');
                $admin->statement('DROP ROLE crm_aia_key_verify_ci');
            }
        }
    }

    /**
     * F241: the nonce ledger is physically INSERT-only (not privileged PG),
     * with two independent backend connections and real PG16 UNIQUE+RLS.
     */
    public function test_f241_insert_only_nonce_role_rejects_replays_and_cannot_read_or_mutate(): void
    {
        if (getenv('AIA_F241_NONCE_ROLE_LAB') !== 'true') {
            $this->markTestSkipped('F241 requires disposable PG16 CI.');
        }
        $admin = DB::connection();
        $roleCreated = false;
        try {
            $admin->statement("CREATE ROLE crm_aia_nonce_ci LOGIN
                PASSWORD 'f241-insert-only-test'
                NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
            $roleCreated = true;
            $admin->statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_aia_nonce_ci');
            $admin->statement('GRANT USAGE ON SCHEMA public TO crm_aia_nonce_ci');
            $admin->statement('ALTER TABLE public.aia_lab_nonce_atomic_probe
                ENABLE ROW LEVEL SECURITY');
            $admin->statement('ALTER TABLE public.aia_lab_nonce_atomic_probe
                FORCE ROW LEVEL SECURITY');
            $admin->statement("CREATE POLICY aia_f241_nonce_only_insert
                ON public.aia_lab_nonce_atomic_probe FOR INSERT
                TO crm_aia_nonce_ci
                WITH CHECK (
                    tenant_ref = current_setting('app.tenant_id',true)
                    AND issuer = 'SYNTHETIC_AGENT_SERVICE'
                    AND audience = 'SYNTHETIC_CRM_API'
                    AND claimed_at > 0
                )");
            $admin->statement('GRANT INSERT ON TABLE
                public.aia_lab_nonce_atomic_probe TO crm_aia_nonce_ci');

            $settings = config('database.connections.pgsql');
            $settings['url'] = null;
            $settings['username'] = 'crm_aia_nonce_ci';
            $settings['password'] = 'f241-insert-only-test';
            config([
                'database.connections.aia_f241_nonce_one' => $settings,
                'database.connections.aia_f241_nonce_two' => $settings,
            ]);
            DB::purge('aia_f241_nonce_one');
            DB::purge('aia_f241_nonce_two');
            $one = DB::connection('aia_f241_nonce_one');
            $two = DB::connection('aia_f241_nonce_two');
            $this->assertNotSame(
                (int) $one->selectOne('SELECT pg_backend_pid() AS pid')->pid,
                (int) $two->selectOne('SELECT pg_backend_pid() AS pid')->pid
            );
            $ledgerOne = new \App\Support\Synthetic\AiaPgRestrictedNonceLedgerLab($one);
            $ledgerTwo = new \App\Support\Synthetic\AiaPgRestrictedNonceLedgerLab($two);
            $tenantA = 'SYNTHETIC_TENANT_A';
            $tenantB = 'SYNTHETIC_TENANT_B';
            $now = 1794690010;

            $this->assertTrue($ledgerOne->claim(
                'SYNTHETIC_F241_FIRST_NONCE', $tenantA, $now
            ));
            // Unique constraint is on issuer + audience + nonce, so a
            // replay under another tenant cannot consume a second claim.
            $this->assertFalse($ledgerTwo->claim(
                'SYNTHETIC_F241_FIRST_NONCE', $tenantA, $now
            ));
            $this->assertFalse($ledgerTwo->claim(
                'SYNTHETIC_F241_FIRST_NONCE', $tenantB, $now
            ));
            $this->assertSame(1, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertSame(0,$one->transactionLevel());
            $this->assertSame(0,$two->transactionLevel());

            foreach ([
                static fn ($db) => $db->table('public.aia_lab_nonce_atomic_probe')->count(),
                static fn ($db) => $db->table('public.aia_lab_nonce_atomic_probe')
                    ->update(['claimed_at' => 1794690011]),
                static fn ($db) => $db->table('public.aia_lab_nonce_atomic_probe')->delete(),
                static fn ($db) => $db->table('public.crm_memberships')->count(),
                static fn ($db) => $db->table('public.crm_auth_sessions')->count(),
            ] as $attempt) {
                $blocked = false;
                try {
                    $attempt($one);
                } catch (\Throwable) {
                    $blocked = true;
                }
                $this->assertTrue($blocked,
                    'Nonce role unexpectedly has read/update/delete/business grants');
            }

            // Tenant marker must come from the trusted bridge. A role
            // trying to insert B while its transaction is scoped to A
            // is physically rejected by PostgreSQL RLS.
            $crossTenantDenied = false;
            try {
                $one->transaction(function () use ($one,$tenantA,$tenantB,$now): void {
                    $one->selectOne("SELECT set_config('app.tenant_id', ?, true)",
                        [$tenantA]);
                    $one->table('public.aia_lab_nonce_atomic_probe')->insert([
                        'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                        'audience' => 'SYNTHETIC_CRM_API',
                        'nonce' => 'SYNTHETIC_F241_FORGED_CROSS',
                        'tenant_ref' => $tenantB,
                        'claimed_at' => $now,
                    ]);
                }, 1);
            } catch (\Throwable) {
                $crossTenantDenied = true;
            }
            $this->assertTrue($crossTenantDenied);
            $this->assertSame(1, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            // Original SHA-pinned Agents Ed25519 is the only signer
            // verification before a nonce becomes persisted.
            [$private, $public] = self::publicTestKeys();
            $req = self::request($tenantA);
            $trusted = ['SYNTHETIC_F232_KEY' => [
                'public_key' => $public, 'enabled' => true,
            ]];
            [$claimBytes,$sig] = self::signed(
                $req,$tenantA,'SYNTHETIC_F241_SIGNED_NONCE',$private
            );
            $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
                $req,$claimBytes,'INVALID_SIGNATURE',$trusted,
                $tenantA,$now,$ledgerOne
            ));
            $this->assertSame(1, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertTrue(AiaPgEd25519LabBridge::matchesLabFixture(
                $req,$claimBytes,$sig,$trusted,$tenantA,$now,$ledgerOne
            ));
            $this->assertFalse(AiaPgEd25519LabBridge::matchesLabFixture(
                $req,$claimBytes,$sig,$trusted,$tenantA,$now,$ledgerTwo
            ));
            self::assertDenial(AiaPgEd25519LabBridge::inspectLabFixture(
                $req,$claimBytes,$sig,$trusted,$tenantA,$now,$ledgerOne
            ));
            $this->assertSame(2, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertSame(0,$one->transactionLevel());
            $this->assertSame(0,$two->transactionLevel());

            // Disabling FORCE RLS is an explicit failure of the guard,
            // not a reason to fall back to a privileged connection.
            $admin->statement('ALTER TABLE public.aia_lab_nonce_atomic_probe
                NO FORCE ROW LEVEL SECURITY');
            $this->assertFalse($ledgerOne->claim(
                'SYNTHETIC_F241_RLS_REMOVED', $tenantA, $now
            ));
            $this->assertSame(2, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $admin->statement('ALTER TABLE public.aia_lab_nonce_atomic_probe
                FORCE ROW LEVEL SECURITY');
        } finally {
            foreach (['aia_f241_nonce_one','aia_f241_nonce_two'] as $connection) {
                DB::disconnect($connection);
                DB::purge($connection);
            }
            $admin->statement('DROP POLICY IF EXISTS aia_f241_nonce_only_insert
                ON public.aia_lab_nonce_atomic_probe');
            if ($roleCreated) {
                $admin->statement('REVOKE INSERT ON TABLE public.aia_lab_nonce_atomic_probe
                    FROM crm_aia_nonce_ci');
                $admin->statement('REVOKE USAGE ON SCHEMA public FROM crm_aia_nonce_ci');
                $admin->statement('REVOKE CONNECT ON DATABASE imagine_crm_ci
                    FROM crm_aia_nonce_ci');
                $admin->statement('DROP ROLE crm_aia_nonce_ci');
            }
        }
    }

    /**
     * F242: service TENANT from a CRM-owned globally-unique key binding,
     * never from an unverified client claim, route or SQL tenant marker.
     * Uses original SHA-pinned Agents Ed25519 + F240 read-only keyring
     * + F241 insert-only nonce ledger, with three separate PG roles.
     */
    public function test_f242_trusted_key_binding_derives_tenant_before_atomic_proof(): void
    {
        if (getenv('AIA_F242_PEER_ROUTING_LAB') !== 'true') {
            $this->markTestSkipped('F242 needs the pinned Agents + PG16 CI gate.');
        }

        $admin = DB::connection();
        $createdRoles = [];
        $admin->statement('CREATE TABLE public.aia_lab_service_tenant_bindings (
            key_id varchar(90) NOT NULL PRIMARY KEY,
            tenant_ref varchar(90) NOT NULL,
            organization_id uuid NOT NULL,
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            subject varchar(90) NOT NULL,
            enabled boolean NOT NULL,
            revoked_at bigint NULL,
            revision integer NOT NULL,
            lock_marker smallint NOT NULL DEFAULT 0
        )');
        $admin->statement('CREATE TABLE public.aia_lab_ed25519_service_keys (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            key_id varchar(90) NOT NULL,
            public_key_base64 varchar(44) NOT NULL,
            enabled boolean NOT NULL,
            revoked_at bigint NULL,
            revision integer NOT NULL,
            lock_marker smallint NOT NULL DEFAULT 0,
            PRIMARY KEY (tenant_ref,key_id)
        )');
        try {
            $roles = [
                'crm_aia_peer_router_ci' => [
                    'aia_lab_service_tenant_bindings', 'SELECT'
                ],
                'crm_aia_key_verify_ci' => [
                    'aia_lab_ed25519_service_keys', 'SELECT'
                ],
                'crm_aia_nonce_ci' => [
                    'aia_lab_nonce_atomic_probe', 'INSERT'
                ],
            ];
            foreach ($roles as $role => [$table,$privilege]) {
                $admin->statement("CREATE ROLE ".$role." LOGIN
                    PASSWORD 'f242-disposable-role'
                    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
                $createdRoles[] = $role;
                $admin->statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO '.$role);
                $admin->statement('GRANT USAGE ON SCHEMA public TO '.$role);
                $admin->statement('ALTER TABLE public.'.$table.
                    ' ENABLE ROW LEVEL SECURITY');
                $admin->statement('ALTER TABLE public.'.$table.
                    ' FORCE ROW LEVEL SECURITY');
                $admin->statement('GRANT '.$privilege.' ON TABLE public.'.$table.
                    ' TO '.$role);
            }
            // The trusted binding is independent of tenant claims and the
            // GUC: a globally unique key_id selects exactly ONE record.
            $admin->statement('CREATE POLICY aia_f242_peer_select ON
                public.aia_lab_service_tenant_bindings FOR SELECT
                TO crm_aia_peer_router_ci USING (true)');
            $admin->statement("CREATE POLICY aia_f242_public_key_select ON
                public.aia_lab_ed25519_service_keys FOR SELECT
                TO crm_aia_key_verify_ci
                USING (tenant_ref = current_setting('app.tenant_id',true))");
            $admin->statement("CREATE POLICY aia_f242_nonce_insert ON
                public.aia_lab_nonce_atomic_probe FOR INSERT
                TO crm_aia_nonce_ci
                WITH CHECK (
                    tenant_ref = current_setting('app.tenant_id',true)
                    AND issuer='SYNTHETIC_AGENT_SERVICE'
                    AND audience='SYNTHETIC_CRM_API'
                    AND claimed_at > 0
                )");

            $tenantA = 'SYNTHETIC_TENANT_A';
            $tenantB = 'SYNTHETIC_TENANT_B';
            $keyA = 'SYNTHETIC_F242_KEY_A';
            $keyB = 'SYNTHETIC_F242_KEY_B';
            $orgA = '11111111-1111-4111-8111-111111111111';
            $orgB = '22222222-2222-4222-8222-222222222222';
            $pairA = sodium_crypto_sign_seed_keypair(
                hash('sha256','F242_ONLY_PEER_A_PUBLIC_CI',true)
            );
            $pairB = sodium_crypto_sign_seed_keypair(
                hash('sha256','F242_ONLY_PEER_B_PUBLIC_CI',true)
            );
            $secretA = sodium_crypto_sign_secretkey($pairA);
            $secretB = sodium_crypto_sign_secretkey($pairB);
            $pubA = sodium_crypto_sign_publickey($pairA);
            $pubB = sodium_crypto_sign_publickey($pairB);
            foreach ([
                [$keyA,$tenantA,$orgA,$pubA],
                [$keyB,$tenantB,$orgB,$pubB],
            ] as [$key,$tenant,$organization,$public]) {
                $common = [
                    'key_id'=>$key,
                    'tenant_ref'=>$tenant,
                    'issuer'=>'SYNTHETIC_AGENT_SERVICE',
                    'audience'=>'SYNTHETIC_CRM_API',
                    'enabled'=>true,'revoked_at'=>null,'revision'=>1,
                ];
                $admin->table('public.aia_lab_service_tenant_bindings')
                    ->insert($common + [
                        'organization_id'=>$organization,
                        'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
                    ]);
                $admin->table('public.aia_lab_ed25519_service_keys')
                    ->insert($common + [
                        'public_key_base64'=>base64_encode($public),
                    ]);
            }
            $settings = config('database.connections.pgsql');
            $settings['url'] = null;
            foreach ([
                'aia_f242_binding' => 'crm_aia_peer_router_ci',
                'aia_f242_keyring' => 'crm_aia_key_verify_ci',
                'aia_f242_nonce' => 'crm_aia_nonce_ci',
            ] as $connectionName => $username) {
                $scoped = $settings;
                $scoped['username'] = $username;
                $scoped['password'] = 'f242-disposable-role';
                config(['database.connections.'.$connectionName => $scoped]);
                DB::purge($connectionName);
            }
            $boundDb = DB::connection('aia_f242_binding');
            $keyDb = DB::connection('aia_f242_keyring');
            $nonceDb = DB::connection('aia_f242_nonce');
            $this->assertCount(3, array_unique([
                $boundDb->selectOne('SELECT pg_backend_pid() AS pid')->pid,
                $keyDb->selectOne('SELECT pg_backend_pid() AS pid')->pid,
                $nonceDb->selectOne('SELECT pg_backend_pid() AS pid')->pid,
            ]));
            $binding = new \App\Support\Synthetic\AiaPgServiceTenantBindingLab($boundDb);
            $keyring = new \App\Support\Synthetic\AiaPgRestrictedKeyringLab($keyDb);
            $nonce = new \App\Support\Synthetic\AiaPgRestrictedNonceLedgerLab($nonceDb);

            // Key ID is a selector; the only independently bound tenant
            // and organization arrive from server-side globally unique row.
            $this->assertSame(
                ['tenant_ref'=>$tenantA,'organization_id'=>$orgA],
                $binding->resolveByKeyId($keyA)
            );
            $this->assertSame(
                ['tenant_ref'=>$tenantB,'organization_id'=>$orgB],
                $binding->resolveByKeyId($keyB)
            );
            $this->assertNull($binding->resolveByKeyId('SYNTHETIC_F242_ABSENT'));
            $this->assertNull($binding->resolveByKeyId('attacker-controlled'));
            $this->assertSame(0,$boundDb->transactionLevel());
            $this->assertSame(0,$keyDb->transactionLevel());
            $this->assertSame(0,$nonceDb->transactionLevel());
            $reqA = self::request($tenantA);
            $reqB = self::request($tenantB);
            $now = 1794690010;
            $sign = static fn (string $body,string $tenant,string $nonceValue,
                string $private,string $kid) => self::signed(
                    $body,$tenant,$nonceValue,$private,['key_id'=>$kid]
                );
            [$aClaims,$aSignature] = $sign(
                $reqA,$tenantA,'SYNTHETIC_F242_FIRST_A',$secretA,$keyA
            );
            [$bClaims,$bSignature] = $sign(
                $reqB,$tenantB,'SYNTHETIC_F242_FIRST_B',$secretB,$keyB
            );
            $verify = static fn (string $req,string $claims,string $signature) =>
                \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::verifyAndConsume(
                    $req,$claims,$signature,$now,$binding,$keyring,$nonce
                );
            // An A claim cannot forge B simply by setting tenant_ref,
            // even if SIGNED by A's own valid secret key.
            [$forgedClaims,$forgedSignature] = $sign(
                $reqB,$tenantB,'SYNTHETIC_F242_FORGED_B',$secretA,$keyA
            );
            $this->assertNull($verify($reqB,$forgedClaims,$forgedSignature));
            $this->assertNull($verify($reqA,$aClaims,$bSignature));
            $this->assertNull($verify($reqA,$aClaims,'INVALID_SIGNATURE'));
            $this->assertSame(0, (int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            $this->assertSame(
                ['tenant_ref'=>$tenantA,'organization_id'=>$orgA],
                $verify($reqA,$aClaims,$aSignature)
            );
            $this->assertSame(
                ['tenant_ref'=>$tenantB,'organization_id'=>$orgB],
                $verify($reqB,$bClaims,$bSignature)
            );
            $this->assertNull($verify($reqA,$aClaims,$aSignature));
            $this->assertSame(2,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            // Physical permission boundary: the binding-only role cannot
            // change tenant ownership or inspect IAM, while the key role
            // and nonce role cannot query the binding table at all.
            foreach ([
                static fn ($db) => $db->table('public.aia_lab_service_tenant_bindings')
                    ->update(['tenant_ref'=>'SYNTHETIC_TENANT_B']),
                static fn ($db) => $db->table('public.aia_lab_service_tenant_bindings')
                    ->delete(),
                static fn ($db) => $db->table('public.crm_auth_sessions')->count(),
            ] as $bad) {
                $denied = false;
                try { $bad($boundDb); } catch (\Throwable) { $denied=true; }
                $this->assertTrue($denied);
            }
            foreach ([$keyDb,$nonceDb] as $wrongRole) {
                $denied=false;
                try {
                    $wrongRole->table('public.aia_lab_service_tenant_bindings')->count();
                } catch (\Throwable) { $denied=true; }
                $this->assertTrue($denied);
            }

            // Revoking the server-owned mapping denies an otherwise-valid
            // fresh signature, without consuming a PostgreSQL nonce.
            $admin->table('aia_lab_service_tenant_bindings')
                ->where('key_id',$keyA)
                ->update(['revoked_at'=>1794690009,'revision'=>2]);
            [$revokedClaims,$revokedSig] = $sign(
                $reqA,$tenantA,'SYNTHETIC_F242_REVOKED',$secretA,$keyA
            );
            $this->assertNull($verify($reqA,$revokedClaims,$revokedSig));
            $this->assertSame(2,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            // Changing the mapping to a different tenant invalidates A
            // regardless of A's public key still existing. No fallback
            // to signed tenant_ref nor SQL marker.
            $admin->table('aia_lab_service_tenant_bindings')
                ->where('key_id',$keyA)->update([
                    'tenant_ref'=>$tenantB,
                    'organization_id'=>$orgB,
                    'revoked_at'=>null,'revision'=>3,
                ]);
            $this->assertNull($verify($reqA,$revokedClaims,$revokedSig));
            $this->assertSame(2,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $admin->table('aia_lab_service_tenant_bindings')
                ->where('key_id',$keyA)->update([
                    'tenant_ref'=>$tenantA,'organization_id'=>$orgA,
                    'revision'=>4,
                ]);

            // Loss of FORCE RLS denies mapping; no shadow keyring or
            // nonce path can be reached when privilege controls change.
            $admin->statement('ALTER TABLE public.aia_lab_service_tenant_bindings
                NO FORCE ROW LEVEL SECURITY');
            $this->assertNull($binding->resolveByKeyId($keyA));
            $this->assertNull($verify($reqA,$revokedClaims,$revokedSig));
            $admin->statement('ALTER TABLE public.aia_lab_service_tenant_bindings
                FORCE ROW LEVEL SECURITY');
            $this->assertSame(2,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertSame(0,$boundDb->transactionLevel());
            $this->assertSame(0,$keyDb->transactionLevel());
            $this->assertSame(0,$nonceDb->transactionLevel());

            // F243: separate scoped role can lock a trusted binding only.
            // Its fake UPDATE(lock_marker) right allows SELECT FOR UPDATE;
            // RLS WITH CHECK(false) prohibits any effective UPDATE.
            $admin->statement("CREATE ROLE crm_aia_peer_lock_ci LOGIN
                PASSWORD 'f243-disposable-role'
                NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
            $createdRoles[] = 'crm_aia_peer_lock_ci';
            $admin->statement('GRANT CONNECT ON DATABASE imagine_crm_ci
                TO crm_aia_peer_lock_ci');
            $admin->statement('GRANT USAGE ON SCHEMA public
                TO crm_aia_peer_lock_ci');
            $admin->statement('GRANT SELECT ON TABLE
                public.aia_lab_service_tenant_bindings
                TO crm_aia_peer_lock_ci');
            $admin->statement('GRANT UPDATE (lock_marker) ON TABLE
                public.aia_lab_service_tenant_bindings
                TO crm_aia_peer_lock_ci');
            $admin->statement('CREATE POLICY aia_f243_lock ON
                public.aia_lab_service_tenant_bindings FOR UPDATE
                TO crm_aia_peer_lock_ci
                USING (true) WITH CHECK (false)');
            $admin->statement('CREATE POLICY aia_f243_read ON
                public.aia_lab_service_tenant_bindings FOR SELECT
                TO crm_aia_peer_lock_ci USING (true)');
            $scoped = $settings;
            $scoped['username'] = 'crm_aia_peer_lock_ci';
            $scoped['password'] = 'f243-disposable-role';
            config(['database.connections.aia_f243_peer_lock'=>$scoped]);
            config(['database.connections.aia_f243_revoker'=>$settings]);
            DB::purge('aia_f243_peer_lock');
            DB::purge('aia_f243_revoker');
            $lockDb = DB::connection('aia_f243_peer_lock');
            $revoker = DB::connection('aia_f243_revoker');
            $this->assertNotSame(
                $lockDb->selectOne('SELECT pg_backend_pid() AS pid')->pid,
                $revoker->selectOne('SELECT pg_backend_pid() AS pid')->pid
            );
            $locked = new \App\Support\Synthetic\AiaPgLockedServiceTenantBindingLab(
                $lockDb
            );
            $raceObserved = false;
            $this->assertSame(
                ['tenant_ref'=>$tenantA,'organization_id'=>$orgA],
                $locked->withinLockedBinding(
                    $keyA,
                    function (array $trusted) use (
                        $revoker,$keyA,&$raceObserved
                    ): bool {
                        $this->assertSame('SYNTHETIC_TENANT_A',$trusted['tenant_ref']);
                        $revoker->statement("SET lock_timeout = '180ms'");
                        try {
                            // Real competing transaction must block on the
                            // exact row currently used for verification.
                            $revoker->table('public.aia_lab_service_tenant_bindings')
                                ->where('key_id',$keyA)
                                ->update(['enabled'=>false]);
                        } catch (\Throwable) {
                            $raceObserved = true;
                        } finally {
                            $revoker->statement('RESET lock_timeout');
                        }
                        return $raceObserved;
                    }
                )
            );
            $this->assertTrue($raceObserved);
            $this->assertSame(0,$lockDb->transactionLevel());
            $this->assertSame(0,$revoker->transactionLevel());

            [$stableClaims,$stableSig] = $sign(
                $reqA,$tenantA,'SYNTHETIC_F243_STABLE_A',$secretA,$keyA
            );
            $this->assertSame(
                ['tenant_ref'=>$tenantA,'organization_id'=>$orgA],
                \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::
                    verifyWithStableBinding(
                        $reqA,$stableClaims,$stableSig,$now,
                        $locked,$keyring,$nonce
                    )
            );
            $this->assertSame(3,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertSame(1,$revoker->table(
                'public.aia_lab_service_tenant_bindings'
            )->where('key_id',$keyA)->update(['enabled'=>false]));
            [$afterRevocation,$afterSig] = $sign(
                $reqA,$tenantA,'SYNTHETIC_F243_DENY_AFTER_REVOKE',
                $secretA,$keyA
            );
            $this->assertNull(
                \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::
                    verifyWithStableBinding(
                        $reqA,$afterRevocation,$afterSig,$now,
                        $locked,$keyring,$nonce
                    )
            );
            $this->assertSame(3,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());
            $this->assertSame(0,$lockDb->transactionLevel());
            $this->assertSame(0,$revoker->transactionLevel());

            // The lock role cannot change the trust row's real fields.
            $badMutation = false;
            try {
                $lockDb->table('public.aia_lab_service_tenant_bindings')
                    ->update(['tenant_ref'=>$tenantB]);
            } catch (\Throwable) {
                $badMutation = true;
            }
            $this->assertTrue($badMutation);

            // F244: the key verifier also holds a physical row lock on
            // the exact CRM-owned public key during the signed nonce proof.
            $admin->table('aia_lab_service_tenant_bindings')
                ->where('key_id',$keyA)->update([
                    'enabled'=>true,'revision'=>5,
                ]);
            $admin->statement("CREATE ROLE crm_aia_key_lock_ci LOGIN
                PASSWORD 'f244-disposable-role'
                NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
            $createdRoles[]='crm_aia_key_lock_ci';
            $admin->statement('GRANT CONNECT ON DATABASE imagine_crm_ci
                TO crm_aia_key_lock_ci');
            $admin->statement('GRANT USAGE ON SCHEMA public
                TO crm_aia_key_lock_ci');
            $admin->statement('GRANT SELECT ON TABLE
                public.aia_lab_ed25519_service_keys TO crm_aia_key_lock_ci');
            $admin->statement('GRANT UPDATE (lock_marker) ON TABLE
                public.aia_lab_ed25519_service_keys TO crm_aia_key_lock_ci');
            $admin->statement("CREATE POLICY aia_f244_lock_public_key ON
                public.aia_lab_ed25519_service_keys FOR UPDATE
                TO crm_aia_key_lock_ci
                USING (tenant_ref = current_setting('app.tenant_id',true))
                WITH CHECK (false)");
            $admin->statement("CREATE POLICY aia_f244_read_public_key ON
                public.aia_lab_ed25519_service_keys FOR SELECT
                TO crm_aia_key_lock_ci
                USING (tenant_ref = current_setting('app.tenant_id',true))");
            $keySettings=$settings;
            $keySettings['username']='crm_aia_key_lock_ci';
            $keySettings['password']='f244-disposable-role';
            config(['database.connections.aia_f244_key_lock'=>$keySettings]);
            config(['database.connections.aia_f244_key_revoker'=>$settings]);
            DB::purge('aia_f244_key_lock');
            DB::purge('aia_f244_key_revoker');
            $keyLockDb=DB::connection('aia_f244_key_lock');
            $keyRevoker=DB::connection('aia_f244_key_revoker');
            $this->assertNotSame(
                $keyLockDb->selectOne('SELECT pg_backend_pid() AS pid')->pid,
                $keyRevoker->selectOne('SELECT pg_backend_pid() AS pid')->pid
            );
            $lockedKey=new \App\Support\Synthetic\AiaPgLockedServiceKeyringLab(
                $keyLockDb
            );
            $blockedKeyRevocation=false;
            $this->assertTrue($lockedKey->withinLockedPublicKey(
                $keyA,$tenantA,
                function (array $trusted) use (
                    $keyA,$pubA,$tenantA,$keyRevoker,&$blockedKeyRevocation
                ): bool {
                    $this->assertSame($pubA,$trusted[$keyA]['public_key']);
                    $keyRevoker->statement("SET lock_timeout = '180ms'");
                    try {
                        $keyRevoker->table('public.aia_lab_ed25519_service_keys')
                            ->where('key_id',$keyA)
                            ->where('tenant_ref',$tenantA)
                            ->update(['revoked_at'=>1794690009]);
                    } catch (\Throwable) {
                        $blockedKeyRevocation=true;
                    } finally {
                        $keyRevoker->statement('RESET lock_timeout');
                    }
                    return $blockedKeyRevocation;
                }
            ));
            $this->assertTrue($blockedKeyRevocation);
            $this->assertSame(0,$keyLockDb->transactionLevel());
            $this->assertSame(0,$keyRevoker->transactionLevel());

            [$lockedClaims,$lockedSig]=$sign(
                $reqA,$tenantA,'SYNTHETIC_F244_VERIFIED_A',$secretA,$keyA
            );
            $this->assertSame(
                ['tenant_ref'=>$tenantA,'organization_id'=>$orgA],
                \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::
                    verifyWithStableIdentity(
                        $reqA,$lockedClaims,$lockedSig,$now,
                        $locked,$lockedKey,$nonce
                    )
            );
            $this->assertSame(4,(int) $admin
                ->table('aia_lab_nonce_atomic_probe')->count());

            // F247: an ephemeral HTTP route, mounted ONLY in this exact
            // PG16 CI test, forwards the canonical request into the
            // production-equivalent F244 diagnostic chain:
            // route -> envelope -> binding lock -> key lock ->
            // ORIGINAL SHA-pinned Agents verifier -> INSERT-only nonce.
            // A valid proof, replay, or revocation all return identical
            // HTTP 404 with zero data and zero external calls.
            if (getenv('AIA_F247_HTTP_E2E_LAB') === 'true') {
                $httpPath = '/__synthetic_f247_http_pg16';
                $httpVerifications = 0;
                \Illuminate\Support\Facades\Route::post(
                    $httpPath,
                    function (\Illuminate\Http\Request $incoming) use (
                        $now,$locked,$lockedKey,$nonce,&$httpVerifications
                    ) {
                        return \App\Support\Synthetic\AiaStagingDeniedIngressLab::
                            respond(
                                $incoming,
                                static function (
                                    string $receivedBody,
                                    string $receivedClaims,
                                    string $receivedSignature
                                ) use (
                                    $now,$locked,$lockedKey,$nonce,
                                    &$httpVerifications
                                ): bool {
                                    $httpVerifications++;
                                    return \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::
                                        verifyWithStableIdentity(
                                                $receivedBody,$receivedClaims,
                                                $receivedSignature,$now,
                                                $locked,$lockedKey,$nonce
                                            ) !== null;
                                }
                            );
                    }
                );

                [$wireClaims,$wireSignature] = $sign(
                    $reqA,$tenantA,'SYNTHETIC_F247_HTTP_VALID_A',
                    $secretA,$keyA
                );
                $sendHttp = function (string $claims, string $sig) use (
                    $httpPath,$reqA
                ): void {
                    $response = $this->call(
                        'POST',$httpPath,[],[],[],
                        [
                            'CONTENT_TYPE'=>'application/json',
                            'HTTP_X_AIA_CLAIMS'=>$claims,
                            'HTTP_X_AIA_SIGNATURE'=>$sig,
                        ],$reqA
                    );
                    $response->assertStatus(404)->assertExactJson([
                        'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
                        'state'=>'DENIED_OR_NOT_FOUND',
                        'projection'=>null,
                        'real_authorization'=>false,
                        'external_calls'=>0,
                    ]);
                    $this->assertStringContainsString(
                        'no-store',
                        (string) $response->headers->get('cache-control')
                    );
                    $this->assertSame(
                        '',(string) $response->headers->get('set-cookie')
                    );
                };
                $sendHttp($wireClaims,$wireSignature);
                $this->assertSame(1,$httpVerifications);
                $this->assertSame(5,(int) $admin
                    ->table('aia_lab_nonce_atomic_probe')->count());
                $sendHttp($wireClaims,$wireSignature);
                $this->assertSame(2,$httpVerifications);
                $this->assertSame(5,(int) $admin
                    ->table('aia_lab_nonce_atomic_probe')->count());
                $this->assertSame(0,$lockDb->transactionLevel());
                $this->assertSame(0,$keyLockDb->transactionLevel());
                $this->assertSame(0,$nonceDb->transactionLevel());
            }

            // Once verification releases both ordered locks, revocation
            // commits and a correctly signed FRESH nonce fails closed.
            $this->assertSame(1,$keyRevoker
                ->table('public.aia_lab_ed25519_service_keys')
                ->where('key_id',$keyA)
                ->where('tenant_ref',$tenantA)
                ->update(['revoked_at'=>1794690009,'revision'=>2]));
            [$rotatedClaims,$rotatedSig]=$sign(
                $reqA,$tenantA,'SYNTHETIC_F244_DENY_REVOKED',$secretA,$keyA
            );
            $this->assertNull(
                \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::
                    verifyWithStableIdentity(
                        $reqA,$rotatedClaims,$rotatedSig,$now,
                        $locked,$lockedKey,$nonce
                    )
            );
            $this->assertSame(
                getenv('AIA_F247_HTTP_E2E_LAB') === 'true' ? 5 : 4,
                (int) $admin->table('aia_lab_nonce_atomic_probe')->count()
            );
            $this->assertSame(0,$lockDb->transactionLevel());
            $this->assertSame(0,$keyLockDb->transactionLevel());
            $this->assertSame(0,$nonceDb->transactionLevel());
            $this->assertSame(0,$keyRevoker->transactionLevel());

            // The key locking role is physically unable to rotate or
            // revoke key bytes, even though it can acquire a row lock.
            foreach ([
                static fn ($db) => $db
                    ->table('public.aia_lab_ed25519_service_keys')
                    ->update(['revoked_at'=>null]),
                static fn ($db) => $db
                    ->table('public.aia_lab_ed25519_service_keys')
                    ->update(['public_key_base64'=>'FORGED']),
            ] as $invalidKeyChange) {
                $blocked=false;
                try { $invalidKeyChange($keyLockDb); }
                catch (\Throwable) { $blocked=true; }
                $this->assertTrue($blocked);
            }

            self::assertDenial(
                \App\Support\Synthetic\AiaPgTenantBoundServiceProofLab::inspectDenied(
                    $reqA,$revokedClaims,$revokedSig,$now,$binding,$keyring,$nonce
                )
            );
        } finally {
            foreach (['aia_f242_binding','aia_f242_keyring','aia_f242_nonce',
                'aia_f243_peer_lock','aia_f243_revoker',
                'aia_f244_key_lock','aia_f244_key_revoker']
                as $name) {
                DB::disconnect($name);
                DB::purge($name);
            }
            foreach ([
                'aia_lab_service_tenant_bindings',
                'aia_lab_ed25519_service_keys',
            ] as $table) {
                $admin->statement('DROP TABLE IF EXISTS public.'.$table);
            }
            $admin->statement('DROP POLICY IF EXISTS aia_f242_nonce_insert
                ON public.aia_lab_nonce_atomic_probe');
            foreach ($createdRoles as $role) {
                if ($role==='crm_aia_nonce_ci') {
                    $admin->statement('REVOKE INSERT ON TABLE
                        public.aia_lab_nonce_atomic_probe FROM '.$role);
                }
                $admin->statement('REVOKE USAGE ON SCHEMA public FROM '.$role);
                $admin->statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM '.$role);
                $admin->statement('DROP ROLE '.$role);
            }
        }
    }

}
