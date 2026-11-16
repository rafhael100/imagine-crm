<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{
    AgentsPrivateRestrictedKeyringLab, AgentsPrivatePgDiagnostic,
    AgentsSignedIngressCandidate, AiaPgNonceCandidateLedger, AiaReadOnlyMockBoundary
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F246: real PostgreSQL 16 FORCE RLS, SELECT-only peer, and original
 * Agents Ed25519 verification, in a disposable CI database ONLY.
 */
final class AgentsPrivateRestrictedKeyringTest extends TestCase
{
    private const TABLE = 'public.aia_lab_service_keys_f246';
    private const ROLE = 'crm_aia_keyreader_f246_ci';
    private const NOW = 1794690010;
    private bool $tableCreated = false;
    private bool $roleCreated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F246_RLS_LAB') !== 'true') {
            $this->markTestSkipped('F246 runs only in disposable PG16 CI.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_USERNAME') !== 'postgres'
            || DB::connection()->getDriverName() !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'imagine_crm_ci'
            || DB::connection()->getConfig('host') !== '127.0.0.1'
            || DB::connection()->getConfig('username') !== 'postgres'
            || DB::connection()->transactionLevel() !== 0) {
            throw new \RuntimeException('F246_NON_DISPOSABLE_ENVIRONMENT_DENIED');
        }

        $identity=DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$identity || $identity->dbname !== 'imagine_crm_ci'
            || $identity->username !== 'postgres') {
            throw new \RuntimeException('F246_DB_IDENTITY_DENIED');
        }

        $fixture=base_path('_aia_f246_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
        ] as $path) {
            self::assertFileExists($fixture.'/'.$path);
        }
        spl_autoload_register(static function (string $class) use ($fixture): void {
            $prefix='ImagineAI\\';
            if (str_starts_with($class,$prefix)) {
                $path=$fixture.'/src/'.str_replace(
                    '\\','/',substr($class,strlen($prefix))
                ).'.php';
                if (is_file($path)) require_once $path;
            }
        });

        DB::statement("CREATE ROLE crm_aia_keyreader_f246_ci
            LOGIN PASSWORD 'f246-disposable-ci-only'
            NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
        $this->roleCreated=true;
        DB::statement('CREATE TABLE '.self::TABLE.' (
            key_id varchar(90) PRIMARY KEY,
            tenant_ref varchar(90) NOT NULL,
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            subject varchar(90) NOT NULL,
            public_key_base64 varchar(44) NOT NULL,
            enabled boolean NOT NULL,
            revoked_at bigint NULL,
            not_before bigint NOT NULL,
            expires_at bigint NOT NULL,
            revision integer NOT NULL
        )');
        $this->tableCreated=true;

        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE '.self::TABLE.' FORCE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY f246_public_key_active_only
            ON ".self::TABLE."
            FOR SELECT TO crm_aia_keyreader_f246_ci
            USING (
                issuer='SYNTHETIC_AGENT_SERVICE'
                AND audience='SYNTHETIC_CRM_API'
                AND subject='SYNTHETIC_AGENTS_READ_ONLY'
                AND enabled=true AND revoked_at IS NULL
            )");
        DB::statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO '.self::ROLE);
        DB::statement('GRANT USAGE ON SCHEMA public TO '.self::ROLE);
        DB::statement('GRANT SELECT ON TABLE '.self::TABLE.' TO '.self::ROLE);

        $settings=config('database.connections.pgsql');
        $settings['url']=null;
        $settings['username']=self::ROLE;
        $settings['password']='f246-disposable-ci-only';
        config(['database.connections.aia_f246_reader'=>$settings]);
        DB::purge('aia_f246_reader');
    }

    protected function tearDown(): void
    {
        try {
            DB::disconnect('aia_f246_reader');
            DB::purge('aia_f246_reader');
            if ($this->tableCreated) {
                DB::statement('DROP TABLE IF EXISTS '.self::TABLE);
            }
            if ($this->roleCreated) {
                // Grants on schema/database survive DROP TABLE; release all
                // disposable CI privileges before dropping the test role.
                DB::statement('REVOKE USAGE ON SCHEMA public FROM '.self::ROLE);
                DB::statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM '.self::ROLE);
                DB::statement('DROP ROLE IF EXISTS '.self::ROLE);
            }
        } finally {
            parent::tearDown();
        }
    }

    private static function pair(string $seed): array
    {
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F246_'.$seed.'_TEST_ONLY',true)
        );
        return [
            sodium_crypto_sign_publickey($pair),
            sodium_crypto_sign_secretkey($pair),
        ];
    }

    private function register(string $id,string $tenant,string $seed): void
    {
        [$pub]=self::pair($seed);
        DB::table('aia_lab_service_keys_f246')->insert([
            'key_id'=>$id,
            'tenant_ref'=>$tenant,
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'public_key_base64'=>base64_encode($pub),
            'enabled'=>true,
            'revoked_at'=>null,
            'not_before'=>self::NOW-10,
            'expires_at'=>self::NOW+30,
            'revision'=>1,
        ]);
    }

    private function lookup(string $id, int $now=self::NOW): ?array
    {
        return (new AgentsPrivateRestrictedKeyringLab(
            DB::connection('aia_f246_reader')
        ))->resolve($id,$now);
    }

    private static function signed(
        string $keyId, string $tenant, string $seed,
        string $nonce='SYNTHETIC_F246_NONCE_A'
    ): array
    {
        [, $secret]=self::pair($seed);
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F246_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>$tenant,
            'principal_ref'=>'SYNTHETIC_F246_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F246_LEAD',
            'purpose_ref'=>'SYNTHETIC_F246_PURPOSE',
            'fields'=>['ref','stage'],
        ]);
        $claims=json_encode([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>$keyId,
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>$tenant,
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>$nonce,
            'issued_at'=>self::NOW-5,
            'expires_at'=>self::NOW+10,
            'request_sha256'=>hash('sha256',$body),
        ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $sig=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claims,$secret
        ));
        return [$body,$claims,$sig];
    }

    private static function verify(array $fixture,array $resolved): bool
    {
        [$body,$claims,$signature]=$fixture;
        return (new \ImagineAI\MockBridge\MockServiceIdentityPreflight())->verifyOnce(
            $body,$claims,$signature,$resolved['trusted_keys'],
            $resolved['tenant_ref'],self::NOW
        );
    }

    public function test_force_rls_and_select_only_role_are_effectively_enabled(): void
    {
        $role=DB::selectOne(
            'SELECT rolsuper, rolbypassrls, rolcreaterole FROM pg_roles WHERE rolname=?',
            [self::ROLE]
        );
        self::assertNotNull($role);
        self::assertFalse((bool)$role->rolsuper);
        self::assertFalse((bool)$role->rolbypassrls);
        self::assertFalse((bool)$role->rolcreaterole);

        $rls=DB::selectOne(
            'SELECT relrowsecurity, relforcerowsecurity
             FROM pg_class WHERE oid=?::regclass',
            [self::TABLE]
        );
        self::assertNotNull($rls);
        self::assertTrue((bool)$rls->relrowsecurity);
        self::assertTrue((bool)$rls->relforcerowsecurity);

        $priv=DB::selectOne(
            'SELECT has_table_privilege(?, ?, ? ) AS can_select,
                has_table_privilege(?, ?, ? ) AS can_insert,
                has_table_privilege(?, ?, ? ) AS can_update,
                has_table_privilege(?, ?, ? ) AS can_delete',
            [self::ROLE,self::TABLE,'SELECT',self::ROLE,self::TABLE,'INSERT',
             self::ROLE,self::TABLE,'UPDATE',self::ROLE,self::TABLE,'DELETE']
        );
        self::assertTrue((bool)$priv->can_select);
        self::assertFalse((bool)$priv->can_insert);
        self::assertFalse((bool)$priv->can_update);
        self::assertFalse((bool)$priv->can_delete);
    }

    public function test_keyring_can_verify_original_agents_signature_but_not_cross_tenant(): void
    {
        $this->register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        $this->register('SYNTHETIC_F246_KEY_B','SYNTHETIC_TENANT_B','B');

        $a=$this->lookup('SYNTHETIC_F246_KEY_A');
        $b=$this->lookup('SYNTHETIC_F246_KEY_B');
        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame('SYNTHETIC_TENANT_A',$a['tenant_ref']);
        self::assertSame('SYNTHETIC_TENANT_B',$b['tenant_ref']);
        self::assertTrue(self::verify(
            self::signed('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A'),$a
        ));
        self::assertFalse(self::verify(
            self::signed('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_B','A'),$a
        ));
        self::assertFalse(self::verify(
            self::signed('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','B'),$a
        ));
        self::assertNull($this->lookup('SYNTHETIC_F246_UNKNOWN_KEY'));
        self::assertSame(
            'DENIED_OR_NOT_FOUND',
            AiaReadOnlyMockBoundary::inspectCanonicalJson('')['state']
        );
    }

    public function test_database_role_cannot_modify_or_read_revoked_keys(): void
    {
        $this->register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        $reader=DB::connection('aia_f246_reader');
        self::assertNotNull($this->lookup('SYNTHETIC_F246_KEY_A'));

        $writeDenied=false;
        try {
            $reader->statement(
                "UPDATE public.aia_lab_service_keys_f246 SET enabled=false
                 WHERE key_id='SYNTHETIC_F246_KEY_A'"
            );
        } catch (\Throwable) {
            $writeDenied=true;
        }
        self::assertTrue($writeDenied);
        DB::table('aia_lab_service_keys_f246')
            ->where('key_id','SYNTHETIC_F246_KEY_A')
            ->update(['enabled'=>false,'revoked_at'=>self::NOW]);
        self::assertNull($this->lookup('SYNTHETIC_F246_KEY_A'));
    }

    public function test_fail_closed_when_rls_or_table_is_unavailable(): void
    {
        $this->register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        self::assertNotNull($this->lookup('SYNTHETIC_F246_KEY_A'));

        DB::statement('ALTER TABLE '.self::TABLE.' DISABLE ROW LEVEL SECURITY');
        self::assertNull($this->lookup('SYNTHETIC_F246_KEY_A'));
        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');
        self::assertNotNull($this->lookup('SYNTHETIC_F246_KEY_A'));

        DB::statement('DROP TABLE '.self::TABLE);
        $this->tableCreated=false;
        self::assertNull($this->lookup('SYNTHETIC_F246_KEY_A'));
    }
    public function test_f247_private_http_uses_restricted_keyring_then_pg_nonce_with_uniform_denial(): void
    {
        if (getenv('AIA_F247_COMPOSED_LAB') !== 'true'
            || getenv('AIA_F243_PG_LAB') !== 'true'
            || getenv('CRM_AIA_F248_PARITY_LAB') !== 'true') {
            $this->markTestSkipped('F247 requires exact disposable composed CI gate.');
        }

        $nonceTable='public.aia_lab_nonce_atomic_probe';
        DB::statement('CREATE TABLE '.$nonceTable.' (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            nonce varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            claimed_at bigint NOT NULL,
            PRIMARY KEY(issuer,audience,nonce)
        )');
        try {
            $this->register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
            $owner=DB::connection();
            $observed=[];
            // Claims may select a synthetic key_id, NEVER tenant or public key.
            // Tenant and Ed25519 verification key MUST come from the restricted
            // RLS-filtered CRM public-key table on every attempt.
            $callback=function(string $body,string $claims,string $signature)
                use(&$observed,$owner): bool {
                try {
                    $decoded=json_decode($claims,true,4,JSON_THROW_ON_ERROR);
                    $keyId=$decoded['key_id'] ?? null;
                    $owned=is_string($keyId) ? $this->lookup($keyId) : null;
                    if ($owned === null) {
                        $observed[]=false;
                        return false;
                    }
                    $scope=[
                        'tenant_ref'=>$owned['tenant_ref'],
                        'principal_ref'=>'SYNTHETIC_F246_PRINCIPAL',
                        'resource_ref'=>'SYNTHETIC_F246_LEAD',
                        'purpose_ref'=>'SYNTHETIC_F246_PURPOSE',
                        'fields'=>['ref','stage'],
                    ];
                    $diagnostic=new AgentsPrivatePgDiagnostic(
                        new AiaPgNonceCandidateLedger($owner),
                        $owned['trusted_keys'],$scope,$owned['tenant_ref'],self::NOW
                    );
                    $value=$diagnostic->inspect($body,$claims,$signature);
                    $observed[]=$value;
                    return $value;
                } catch (\Throwable) {
                    $observed[]=false;
                    return false;
                }
            };
            $send=function(array $signed,array $extra=[]) use($callback): void {
                [$body,$claims,$sig]=$signed;
                $request=Request::create(
                    '/__synthetic_f247_not_registered',
                    'POST',[],[],[],
                    array_merge([
                        'CONTENT_TYPE'=>'application/json',
                        'HTTP_X_AIA_CLAIMS'=>$claims,
                        'HTTP_X_AIA_SIGNATURE'=>$sig,
                    ],$extra),
                    $body,
                );
                $routeCount=Route::getRoutes()->count();
                $response=AgentsSignedIngressCandidate::inspect($request,$callback);
                self::assertSame(404,$response->getStatusCode());
                self::assertSame([
                    'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
                    'state'=>'DENIED_OR_NOT_FOUND',
                    'projection'=>null,
                    'real_authorization'=>false,
                    'external_calls'=>0,
                ],$response->getData(true));
                self::assertNull($response->headers->get('set-cookie'));
                self::assertSame($routeCount,Route::getRoutes()->count());
            };
            $valid=self::signed('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A',
                'SYNTHETIC_F247_NONCE_A');
            $cross=self::signed('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_B','A',
                'SYNTHETIC_F247_NONCE_B');
            $send($cross);
            self::assertSame([false],$observed);
            self::assertSame(0,(int)$owner->table('aia_lab_nonce_atomic_probe')->count());
            $send($valid);
            $send($valid); // Different verifier instance, same PG nonce.
            self::assertSame([false,true,false],$observed);
            self::assertSame(1,(int)$owner->table('aia_lab_nonce_atomic_probe')->count());

            // Malicious tenant header is rejected before any callback.
            $send($valid,['HTTP_X_TENANT_ID'=>'SYNTHETIC_TENANT_B']);
            self::assertSame([false,true,false],$observed);

            $owner->table('aia_lab_service_keys_f246')
                ->where('key_id','SYNTHETIC_F246_KEY_A')
                ->update(['enabled'=>false,'revoked_at'=>self::NOW]);
            $later=self::signed('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A',
                'SYNTHETIC_F247_NONCE_AFTER_REVOKE');
            $send($later);
            self::assertSame([false,true,false,false],$observed);
            self::assertSame(1,(int)$owner->table('aia_lab_nonce_atomic_probe')->count());

            // Simulate RLS drift: even previously valid key cannot be used.
            $owner->statement('ALTER TABLE '.self::TABLE.' DISABLE ROW LEVEL SECURITY');
            $send($later);
            self::assertSame([false,true,false,false,false],$observed);
            self::assertSame(1,(int)$owner->table('aia_lab_nonce_atomic_probe')->count());
        } finally {
            DB::statement('DROP TABLE IF EXISTS '.$nonceTable);
        }
    }

}
