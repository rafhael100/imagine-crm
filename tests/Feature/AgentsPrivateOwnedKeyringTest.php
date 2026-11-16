<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{
    AgentsPrivateOwnedKeyringLab, AgentsPrivatePgDiagnostic,
    AgentsSignedIngressCandidate, AiaPgNonceCandidateLedger
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F245: derive synthetic tenant AND Ed25519 public key from CRM-owned PG16.
 * Keys and tenants below are deliberately public test fixtures.
 * Never an actual service credential, access policy or business API.
 */
final class AgentsPrivateOwnedKeyringTest extends TestCase
{
    private const KEYS = 'public.aia_lab_service_keys_f245';
    private const NONCES = 'public.aia_lab_nonce_atomic_probe';
    private const NOW = 1794690010;
    private bool $createdKeys = false;
    private bool $createdNonces = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F245_KEYRING_LAB') !== 'true') {
            $this->markTestSkipped('F245 requires disposable, explicitly gated PG16 CI.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || getenv('CRM_AIA_F248_PARITY_LAB') !== 'true'
            || getenv('AIA_F243_PG_LAB') !== 'true'
            || DB::connection()->getDriverName() !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'imagine_crm_ci'
            || DB::connection()->getConfig('host') !== '127.0.0.1'
            || DB::connection()->getConfig('username') !== 'postgres'
            || DB::connection()->transactionLevel() !== 0) {
            throw new \RuntimeException('F245_NON_DISPOSABLE_ENVIRONMENT_DENIED');
        }
        $who = DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$who || $who->dbname !== 'imagine_crm_ci'
            || $who->username !== 'postgres') {
            throw new \RuntimeException('F245_DB_IDENTITY_DENIED');
        }

        $root=base_path('_aia_f245_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
            'src/MockBridge/MockSyntheticScopePreflight.php',
        ] as $file) {
            self::assertFileExists($root.'/'.$file);
        }
        spl_autoload_register(static function(string $class) use($root): void {
            $prefix='ImagineAI\\';
            if (str_starts_with($class,$prefix)) {
                $path=$root.'/src/'.str_replace(
                    '\\','/',substr($class,strlen($prefix))
                ).'.php';
                if (is_file($path)) require_once $path;
            }
        });

        DB::statement('CREATE TABLE '.self::KEYS.' (
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
        $this->createdKeys=true;
        DB::statement('CREATE TABLE '.self::NONCES.' (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            nonce varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            claimed_at bigint NOT NULL,
            PRIMARY KEY (issuer,audience,nonce)
        )');
        $this->createdNonces=true;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->createdNonces || $this->createdKeys) {
                $db=DB::connection();
                if ($db->transactionLevel()>0) $db->rollBack();
                if ($this->createdNonces) {
                    $db->statement('DROP TABLE IF EXISTS '.self::NONCES);
                }
                if ($this->createdKeys) {
                    $db->statement('DROP TABLE IF EXISTS '.self::KEYS);
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    private static function keyPair(string $seed): array
    {
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F245_'.$seed.'_TEST_ONLY',true)
        );
        return [sodium_crypto_sign_publickey($pair),
            sodium_crypto_sign_secretkey($pair)];
    }

    private function register(string $kid,string $tenant,string $seed): void
    {
        [$public]=self::keyPair($seed);
        DB::table('aia_lab_service_keys_f245')->insert([
            'key_id'=>$kid,
            'tenant_ref'=>$tenant,
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'public_key_base64'=>base64_encode($public),
            'enabled'=>true,
            'revoked_at'=>null,
            'not_before'=>self::NOW-10,
            'expires_at'=>self::NOW+50,
            'revision'=>1,
        ]);
    }

    /** @return array{0:string,1:string,2:string} */
    private static function signed(
        string $kid,string $tenant,string $nonce,string $seed
    ): array {
        [, $secret]=self::keyPair($seed);
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F245_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>$tenant,
            'principal_ref'=>'SYNTHETIC_F245_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F245_RESOURCE',
            'purpose_ref'=>'SYNTHETIC_F245_PURPOSE',
            'fields'=>['ref','stage'],
        ]);
        $claims=json_encode([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>$kid,
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>$tenant,
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>$nonce,
            'issued_at'=>self::NOW-5,
            'expires_at'=>self::NOW+20,
            'request_sha256'=>hash('sha256',$body),
        ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $sig=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claims,$secret
        ));
        return [$body,$claims,$sig];
    }

    private static function request(array $fixture,array $headers=[]): Request
    {
        return Request::create(
            '/__synthetic_f245_not_registered','POST',[],[],[],
            array_merge([
                'CONTENT_TYPE'=>'application/json',
                'HTTP_X_AIA_CLAIMS'=>$fixture[1],
                'HTTP_X_AIA_SIGNATURE'=>$fixture[2],
            ],$headers),
            $fixture[0]
        );
    }

    private static function scope(string $tenant): array
    {
        // Fixed test-only policy: NOT a real CRM membership/owner decision.
        return [
            'tenant_ref'=>$tenant,
            'principal_ref'=>'SYNTHETIC_F245_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F245_RESOURCE',
            'purpose_ref'=>'SYNTHETIC_F245_PURPOSE',
            'fields'=>['ref','stage'],
        ];
    }

    private function inspect(array $fixture,array &$observed,array $headers=[]): void
    {
        $db=DB::connection();
        $keyring=new AgentsPrivateOwnedKeyringLab($db);
        $callback=static function(string $body,string $claims,string $sig)
            use($keyring,$db,&$observed): bool {
            try {
                $decoded=json_decode($claims,true,4,JSON_THROW_ON_ERROR);
                $kid=$decoded['key_id'] ?? null;
                $resolved=is_string($kid)?$keyring->resolve($kid,self::NOW):null;
                if ($resolved===null) {
                    $observed[]=false;
                    return false;
                }
                $diagnostic=new AgentsPrivatePgDiagnostic(
                    new AiaPgNonceCandidateLedger($db),
                    $resolved['trusted_keys'],
                    self::scope($resolved['tenant_ref']),
                    $resolved['tenant_ref'],
                    self::NOW,
                );
                $ok=$diagnostic->inspect($body,$claims,$sig);
                $observed[]=$ok;
                return $ok;
            } catch (\Throwable) {
                $observed[]=false;
                return false;
            }
        };
        $before=Route::getRoutes()->count();
        $response=AgentsSignedIngressCandidate::inspect(
            self::request($fixture,$headers),$callback
        );
        self::assertSame(404,$response->getStatusCode());
        self::assertSame([
            'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state'=>'DENIED_OR_NOT_FOUND','projection'=>null,
            'real_authorization'=>false,'external_calls'=>0,
        ],$response->getData(true));
        self::assertNull($response->headers->get('set-cookie'));
        self::assertSame($before,Route::getRoutes()->count());
    }

    public function test_server_owned_a_b_bindings_and_replay(): void
    {
        $this->register('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A','A');
        $this->register('SYNTHETIC_F245_KEY_B','SYNTHETIC_TENANT_B','B');
        $witness=[];
        $a=self::signed('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_A','A');
        $b=self::signed('SYNTHETIC_F245_KEY_B','SYNTHETIC_TENANT_B',
            'SYNTHETIC_F245_NONCE_B','B');
        $this->inspect($a,$witness);
        $this->inspect($a,$witness);
        $this->inspect($b,$witness);
        self::assertSame([true,false,true],$witness);
        self::assertSame(2,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
    }

    public function test_signed_cross_tenant_and_unknown_key_do_not_consume(): void
    {
        $this->register('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A','A');
        $witness=[];
        $cross=self::signed('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_B',
            'SYNTHETIC_F245_NONCE_FORGED','A');
        $unknown=self::signed('SYNTHETIC_F245_KEY_Z','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_UNKNOWN','A');
        $this->inspect($cross,$witness);
        $this->inspect($unknown,$witness);
        self::assertSame([false,false],$witness);
        self::assertSame(0,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
        $valid=self::signed('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_FORGED','A');
        $this->inspect($valid,$witness);
        self::assertSame([false,false,true],$witness);
    }

    public function test_revocation_rotation_and_outage_are_deny_first(): void
    {
        $this->register('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A','A');
        $witness=[];
        $a=self::signed('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_FIRST','A');
        $this->inspect($a,$witness);
        DB::table('aia_lab_service_keys_f245')
            ->where('key_id','SYNTHETIC_F245_KEY_A')
            ->update(['enabled'=>false,'revoked_at'=>self::NOW]);
        $old=self::signed('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_NEW','A');
        $this->inspect($old,$witness);
        $this->register('SYNTHETIC_F245_KEY_C','SYNTHETIC_TENANT_A','C');
        $rotated=self::signed('SYNTHETIC_F245_KEY_C','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_ROTATED','C');
        $this->inspect($rotated,$witness);
        DB::statement('DROP TABLE '.self::KEYS);
        $missing=self::signed('SYNTHETIC_F245_KEY_C','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_DOWN','C');
        $this->inspect($missing,$witness);
        self::assertSame([true,false,true,false],$witness);
        self::assertSame(2,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
    }

    public function test_untrusted_external_tenant_header_never_reaches_keyring(): void
    {
        $this->register('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A','A');
        $witness=[];
        $fixture=self::signed('SYNTHETIC_F245_KEY_A','SYNTHETIC_TENANT_A',
            'SYNTHETIC_F245_NONCE_HEADER','A');
        $this->inspect($fixture,$witness,
            ['HTTP_X_TENANT_ID'=>'SYNTHETIC_TENANT_B']);
        self::assertSame([],$witness);
        self::assertSame(0,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
        $this->inspect($fixture,$witness);
        self::assertSame([true],$witness);
    }
}
