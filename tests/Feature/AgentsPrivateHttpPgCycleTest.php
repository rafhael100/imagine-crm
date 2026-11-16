<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{
    AgentsSignedIngressCandidate, AgentsPrivatePgDiagnostic, AiaPgNonceCandidateLedger
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** F243: in-memory HTTP test-only F248 ingress -> original Agents -> PG16. */
final class AgentsPrivateHttpPgCycleTest extends TestCase
{
    private const TABLE = 'public.aia_lab_nonce_atomic_probe';
    private const TENANT = 'SYNTHETIC_TENANT_A';
    private const NOW = 1794690010;
    private bool $created = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F243_PG_LAB') !== 'true') {
            $this->markTestSkipped('F243 requires isolated CI service and flag.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || getenv('CRM_AIA_F248_PARITY_LAB') !== 'true'
            || DB::getDriverName() !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'imagine_crm_ci'
            || !app()->environment('testing')) {
            throw new \RuntimeException('F243_NON_DISPOSABLE_CI_DENIED');
        }
        $who = DB::selectOne('SELECT current_database() AS dbname, current_user AS username');
        if (!$who || $who->dbname !== 'imagine_crm_ci'
            || $who->username !== 'postgres') {
            throw new \RuntimeException('F243_DATABASE_IDENTITY_DENIED');
        }
        $root=base_path('_aia_f243_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
            'src/MockBridge/MockSyntheticScopePreflight.php',
            'src/MockBridge/MockCrmPublicResponseContract.php',
            'src/MockBridge/MockSyntheticHttpDenialReceipt.php',
        ] as $relative) {
            self::assertFileExists($root.'/'.$relative);
        }
        spl_autoload_register(static function(string $class) use ($root): void {
            $prefix='ImagineAI\\';
            if (str_starts_with($class,$prefix)) {
                $file=$root.'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
                if (is_file($file)) require_once $file;
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
        $this->created=true;
        $settings=config('database.connections.pgsql');
        $settings['url']=null;
        config(['database.connections.aia_f243_second'=>$settings]);
        DB::purge('aia_f243_second');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->created) {
                $db=DB::connection();
                if ($db->transactionLevel()>0) $db->rollBack();
                $db->statement('DROP TABLE IF EXISTS '.self::TABLE);
                DB::disconnect('aia_f243_second');
                DB::purge('aia_f243_second');
            }
        } finally {
            parent::tearDown();
        }
    }

    /** @return array{0:string,1:string,2:string,3:array,4:array} */
    private static function fixture(): array
    {
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F243_TEST_ONLY',true)
        );
        $keyId='SYNTHETIC_F243_KEY_A';
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F243_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>self::TENANT,
            'principal_ref'=>'SYNTHETIC_F243_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F243_RESOURCE',
            'purpose_ref'=>'SYNTHETIC_F243_PURPOSE',
            'fields'=>['ref','stage'],
        ]);
        $claims=json_encode([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>$keyId,
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>self::TENANT,
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>'SYNTHETIC_F243_NONCE_A',
            'issued_at'=>self::NOW-10,
            'expires_at'=>self::NOW+10,
            'request_sha256'=>hash('sha256',$body),
        ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $sig=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claims,
            sodium_crypto_sign_secretkey($pair)
        ));
        $keys=[$keyId=>[
            'public_key'=>sodium_crypto_sign_publickey($pair),'enabled'=>true,
        ]];
        $scope=[
            'tenant_ref'=>self::TENANT,
            'principal_ref'=>'SYNTHETIC_F243_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F243_RESOURCE',
            'purpose_ref'=>'SYNTHETIC_F243_PURPOSE',
            'fields'=>['ref','stage'],
        ];
        return [$body,$claims,$sig,$keys,$scope];
    }

    private static function request(array $fixture,array $extra=[]): Request
    {
        [$body,$claims,$signature]=$fixture;
        return Request::create('/__synthetic_f243_never_registered','POST',
            [],[],[],array_merge([
                'CONTENT_TYPE'=>'application/json',
                'HTTP_X_AIA_CLAIMS'=>$claims,
                'HTTP_X_AIA_SIGNATURE'=>$signature,
            ],$extra),$body);
    }

    private static function assertDenied(\Illuminate\Http\JsonResponse $response): void
    {
        self::assertSame(404,$response->getStatusCode());
        self::assertSame([
            'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state'=>'DENIED_OR_NOT_FOUND',
            'projection'=>null,
            'real_authorization'=>false,
            'external_calls'=>0,
        ],$response->getData(true));
        self::assertNull($response->headers->get('set-cookie'));
        self::assertTrue(
            \ImagineAI\MockBridge\MockSyntheticHttpDenialReceipt::matches(
                404,[
                    'content-type'=>(string)$response->headers->get('content-type'),
                    'cache-control'=>(string)$response->headers->get('cache-control'),
                    'x-robots-tag'=>(string)$response->headers->get('x-robots-tag'),
                    'x-content-type-options'=>(string)$response->headers->get('x-content-type-options'),
                ],$response->getContent()
            )
        );
    }

    public function test_f243_two_postgres_connections_consume_one_signed_http_nonce(): void
    {
        $fixture=self::fixture();
        [,,$sig,$keys,$scope]=$fixture;
        $one=DB::connection();
        $two=DB::connection('aia_f243_second');
        self::assertNotSame(
            (int)$one->selectOne('SELECT pg_backend_pid() AS pid')->pid,
            (int)$two->selectOne('SELECT pg_backend_pid() AS pid')->pid
        );
        $first=new AgentsPrivatePgDiagnostic(
            new AiaPgNonceCandidateLedger($one),$keys,$scope,self::TENANT,self::NOW
        );
        $second=new AgentsPrivatePgDiagnostic(
            new AiaPgNonceCandidateLedger($two),$keys,$scope,self::TENANT,self::NOW
        );
        $observed=[];
        $callback=static function(string $body,string $claims,string $signature)
            use ($first,$second,&$observed): bool {
            $result=count($observed)===0
                ? $first->inspect($body,$claims,$signature)
                : $second->inspect($body,$claims,$signature);
            $observed[]=$result;
            return $result;
        };
        $before=Route::getRoutes()->count();
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($fixture),$callback
        ));
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($fixture),$callback
        ));
        self::assertSame([true,false],$observed);
        self::assertSame(1,(int)$one->table('aia_lab_nonce_atomic_probe')->count());
        self::assertSame($before,Route::getRoutes()->count());
    }

    public function test_f243_invalid_signature_scope_and_tenant_cannot_claim(): void
    {
        $fixture=self::fixture();
        [,,,$keys,$scope]=$fixture;
        $first=new AgentsPrivatePgDiagnostic(
            new AiaPgNonceCandidateLedger(DB::connection()),
            $keys,$scope,self::TENANT,self::NOW
        );
        $seen=[];
        $callback=static function(string $body,string $claims,string $signature)
            use ($first,&$seen): bool {
            $ok=$first->inspect($body,$claims,$signature);
            $seen[]=$ok;
            return $ok;
        };
        $badSig=$fixture;
        $badSig[2]=base64_encode(str_repeat("\x00",64));
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($badSig),$callback
        ));
        $badScope=new AgentsPrivatePgDiagnostic(
            new AiaPgNonceCandidateLedger(DB::connection()),
            $keys,array_replace($scope,['resource_ref'=>'SYNTHETIC_OTHER_LEAD']),
            self::TENANT,self::NOW
        );
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($fixture),
            static fn(string $b,string $c,string $s): bool => $badScope->inspect($b,$c,$s)
        ));
        self::assertSame([false],$seen);
        self::assertSame(0,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($fixture),$callback
        ));
        self::assertSame([false,true],$seen);
        self::assertSame(1,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
    }

    public function test_f243_invalid_external_tenant_header_or_pg_outage_denies(): void
    {
        $fixture=self::fixture();
        [,,,$keys,$scope]=$fixture;
        $first=new AgentsPrivatePgDiagnostic(
            new AiaPgNonceCandidateLedger(DB::connection()),
            $keys,$scope,self::TENANT,self::NOW
        );
        $called=0;
        $callback=static function(string $body,string $claims,string $signature)
            use ($first,&$called): bool {
            ++$called;
            return $first->inspect($body,$claims,$signature);
        };
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($fixture,['HTTP_X_TENANT_ID'=>'SYNTHETIC_TENANT_B']),$callback
        ));
        self::assertSame(0,$called);
        self::assertSame(0,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
        DB::statement('DROP TABLE '.self::TABLE);
        self::assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($fixture),$callback
        ));
        self::assertSame(1,$called);
    }
}
