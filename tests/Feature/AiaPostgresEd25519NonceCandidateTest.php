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
}
