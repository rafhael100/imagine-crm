<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\AgentsPrivateRestrictedKeyReaderLab;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F246: disposable PG16 nonowner SELECT-only role, forced RLS, no business IO.
 * Verifies SOURCE of service key and tenant, NOT actual IAM/ACL or deployment.
 */
final class AgentsPrivateRestrictedKeyReaderTest extends TestCase
{
    private const TABLE='public.aia_lab_service_keys_f245';
    private const ROLE='crm_aia_f246_reader_ci';
    private const NOW=1794690010;
    private bool $hasTable=false;
    private bool $hasRole=false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F246_MINPRIV_LAB') !== 'true') {
            $this->markTestSkipped('F246 requires a dedicated disposable PG16 CI.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || DB::getDriverName() !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'imagine_crm_ci'
            || DB::connection()->getConfig('host') !== '127.0.0.1'
            || DB::connection()->getConfig('username') !== 'postgres') {
            throw new \RuntimeException('F246_NON_DISPOSABLE_DATABASE_DENIED');
        }
        $who=DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$who || $who->dbname !== 'imagine_crm_ci'
            || $who->username !== 'postgres') {
            throw new \RuntimeException('F246_DB_IDENTITY_DENIED');
        }

        $original=base_path('_aia_f246_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
        ] as $file) {
            self::assertFileExists($original.'/'.$file);
        }
        spl_autoload_register(static function (string $name) use ($original): void {
            $prefix='ImagineAI\\';
            if (str_starts_with($name,$prefix)) {
                $path=$original.'/src/'.str_replace(
                    '\\','/',substr($name,strlen($prefix))
                ).'.php';
                if (is_file($path)) require_once $path;
            }
        });

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
        $this->hasTable=true;
        DB::statement("CREATE ROLE ".self::ROLE."
            LOGIN PASSWORD 'f246-public-test-only'
            NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
        $this->hasRole=true;
        DB::statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO '.self::ROLE);
        DB::statement('GRANT USAGE ON SCHEMA public TO '.self::ROLE);
        DB::statement('GRANT SELECT ON TABLE '.self::TABLE.' TO '.self::ROLE);
        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE '.self::TABLE.' FORCE ROW LEVEL SECURITY');
        DB::statement('CREATE POLICY aia_f246_public_keys_read
            ON '.self::TABLE.' FOR SELECT TO '.self::ROLE.' USING (true)');

        $config=config('database.connections.pgsql');
        $config['url']=null;
        $config['username']=self::ROLE;
        $config['password']='f246-public-test-only';
        config(['database.connections.aia_f246_public_reader'=>$config]);
        DB::purge('aia_f246_public_reader');
        $original=DB::selectOne('SELECT pg_backend_pid() AS pid');
        $separate=DB::connection('aia_f246_public_reader')
            ->selectOne('SELECT pg_backend_pid() AS pid, current_user AS role');
        self::assertNotSame((int)$original->pid,(int)$separate->pid);
        self::assertSame(self::ROLE,$separate->role);
    }

    protected function tearDown(): void
    {
        try {
            DB::disconnect('aia_f246_public_reader');
            DB::purge('aia_f246_public_reader');
            if ($this->hasTable) {
                DB::statement('DROP TABLE IF EXISTS '.self::TABLE);
            }
            if ($this->hasRole) {
                DB::statement('REVOKE USAGE ON SCHEMA public FROM '.self::ROLE);
                DB::statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM '.self::ROLE);
                DB::statement('DROP ROLE '.self::ROLE);
            }
        } finally {
            parent::tearDown();
        }
    }

    private static function pub(string $seed): string
    {
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F246_'.$seed.'_TEST_ONLY',true)
        );
        return sodium_crypto_sign_publickey($pair);
    }

    private static function register(
        string $kid,string $tenant,string $seed,int $revision=1
    ): void {
        DB::table('aia_lab_service_keys_f245')->insert([
            'key_id'=>$kid,
            'tenant_ref'=>$tenant,
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'public_key_base64'=>base64_encode(self::pub($seed)),
            'enabled'=>true,
            'revoked_at'=>null,
            'not_before'=>self::NOW-10,
            'expires_at'=>self::NOW+40,
            'revision'=>$revision,
        ]);
    }

    private static function reader(): AgentsPrivateRestrictedKeyReaderLab
    {
        return new AgentsPrivateRestrictedKeyReaderLab(
            DB::connection('aia_f246_public_reader')
        );
    }

    public function test_select_only_nonowner_role_resolves_a_b_and_cannot_write(): void
    {
        self::register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        self::register('SYNTHETIC_F246_KEY_B','SYNTHETIC_TENANT_B','B');
        $a=self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW);
        $b=self::reader()->resolve('SYNTHETIC_F246_KEY_B',self::NOW);
        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame('SYNTHETIC_TENANT_A',$a['tenant_ref']);
        self::assertSame('SYNTHETIC_TENANT_B',$b['tenant_ref']);
        self::assertSame(self::pub('A'),$a['trusted_keys']['SYNTHETIC_F246_KEY_A']['public_key']);
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_UNKNOWN',self::NOW));

        // Cryptographic validation uses the ORIGINAL Agents Ed25519 verifier
        // with ONLY the trusted public key and tenant supplied by the PG role.
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F246_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>$a['tenant_ref'],
            'principal_ref'=>'SYNTHETIC_F246_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F246_RESOURCE',
            'purpose_ref'=>'SYNTHETIC_F246_PURPOSE',
            'fields'=>['ref','stage'],
        ]);
        $claims=json_encode([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>'SYNTHETIC_F246_KEY_A',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>$a['tenant_ref'],
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>'SYNTHETIC_F246_SIGNATURE_PROOF',
            'issued_at'=>self::NOW-5,
            'expires_at'=>self::NOW+10,
            'request_sha256'=>hash('sha256',$body),
        ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F246_A_TEST_ONLY',true)
        );
        $sig=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claims,
            sodium_crypto_sign_secretkey($pair)
        ));
        self::assertTrue((new \ImagineAI\MockBridge\MockServiceIdentityPreflight())
            ->verifyOnce($body,$claims,$sig,$a['trusted_keys'],$a['tenant_ref'],self::NOW));


        $rejected=false;
        try {
            DB::connection('aia_f246_public_reader')
                ->table('aia_lab_service_keys_f245')
                ->where('key_id','SYNTHETIC_F246_KEY_A')
                ->update(['enabled'=>false]);
        } catch (QueryException) {
            $rejected=true;
        }
        self::assertTrue($rejected);
        self::assertNotNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
    }

    public function test_invalid_rls_and_inherited_tenant_marker_fail_closed(): void
    {
        self::register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        self::assertNotNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));

        DB::statement('ALTER TABLE '.self::TABLE.' NO FORCE ROW LEVEL SECURITY');
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
        DB::statement('ALTER TABLE '.self::TABLE.' FORCE ROW LEVEL SECURITY');

        $reader=DB::connection('aia_f246_public_reader');
        $reader->selectOne("SELECT set_config('app.tenant_id', ?, false) AS scope",
            ['SYNTHETIC_TENANT_B']);
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
        $reader->selectOne("SELECT set_config('app.tenant_id', ?, false) AS scope",['']);
        self::assertNotNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
    }

    public function test_revocation_rotation_invalid_encoding_and_missing_table_deny(): void
    {
        self::register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        self::assertNotNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
        DB::table('aia_lab_service_keys_f245')
            ->where('key_id','SYNTHETIC_F246_KEY_A')
            ->update(['enabled'=>false,'revoked_at'=>self::NOW]);
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
        self::register('SYNTHETIC_F246_KEY_C','SYNTHETIC_TENANT_A','C');
        self::assertNotNull(self::reader()->resolve('SYNTHETIC_F246_KEY_C',self::NOW));
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_C',self::NOW+41));
        DB::table('aia_lab_service_keys_f245')
            ->where('key_id','SYNTHETIC_F246_KEY_C')
            ->update(['public_key_base64'=>str_repeat('?',44)]);
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_C',self::NOW));
        DB::statement('DROP TABLE '.self::TABLE);
        self::assertNull(self::reader()->resolve('SYNTHETIC_F246_KEY_C',self::NOW));
    }

    public function test_superuser_and_wrong_role_are_refused_even_with_valid_rows(): void
    {
        self::register('SYNTHETIC_F246_KEY_A','SYNTHETIC_TENANT_A','A');
        $privileged=new AgentsPrivateRestrictedKeyReaderLab(DB::connection());
        self::assertNull($privileged->resolve('SYNTHETIC_F246_KEY_A',self::NOW));
        self::assertNull(self::reader()->resolve('REAL_KEY_A',self::NOW));
        self::assertNull(self::reader()->resolve(str_repeat('X',120),self::NOW));
    }
}
