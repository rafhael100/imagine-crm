<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{AiaPgNonceCandidateLedger, AiaReadOnlyMockBoundary};
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * F242: ORIGINAL Agents Ed25519 + EXISTING CRM private PostgreSQL16 nonce.
 * Only a throwaway GitHub CI DB; NEVER IAM, API, grants or a CRM lead read.
 */
final class AgentsPrivatePgNonceTest extends TestCase
{
    private const TABLE = 'public.aia_lab_nonce_atomic_probe';
    private const TENANT = 'SYNTHETIC_TENANT_A';
    private const NOW = 1794690010;
    private bool $created = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F242_PG_LAB') !== 'true') {
            $this->markTestSkipped('F242 requires an explicit disposable PG16 CI gate.');
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
            throw new RuntimeException('F242_NON_DISPOSABLE_ENVIRONMENT_DENIED');
        }
        $who=DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$who || $who->dbname !== 'imagine_crm_ci'
            || $who->username !== 'postgres') {
            throw new RuntimeException('F242_DB_IDENTITY_DENIED');
        }

        $fixture=base_path('_aia_f242_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
        ] as $relative) {
            self::assertFileExists($fixture.'/'.$relative);
        }
        spl_autoload_register(static function(string $class) use($fixture): void {
            $prefix='ImagineAI\\';
            if (str_starts_with($class,$prefix)) {
                $file=$fixture.'/src/'.str_replace(
                    '\\','/',substr($class,strlen($prefix))
                ).'.php';
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
        config(['database.connections.aia_f242_second'=>$settings]);
        DB::purge('aia_f242_second');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->created) {
                $conn=DB::connection();
                if ($conn->transactionLevel()>0) $conn->rollBack();
                $conn->statement('DROP TABLE IF EXISTS '.self::TABLE);
                DB::disconnect('aia_f242_second');
                DB::purge('aia_f242_second');
            }
        } finally {
            parent::tearDown();
        }
    }

    /** @return array{0:string,1:string,2:string,3:array<string,mixed>} */
    private static function signed(
        string $nonce,
        string $tenant=self::TENANT,
        array $changes=[]
    ): array {
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F242_TEST_ONLY',true)
        );
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F242_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>$tenant,
            'principal_ref'=>'SYNTHETIC_F242_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F242_LEAD',
            'purpose_ref'=>'SYNTHETIC_F242_PURPOSE',
            'fields'=>['ref','stage'],
        ]);
        $claims=array_replace([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>'SYNTHETIC_F242_KEY',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>$tenant,
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>$nonce,
            'issued_at'=>self::NOW-10,
            'expires_at'=>self::NOW+10,
            'request_sha256'=>hash('sha256',$body),
        ],$changes);
        $claimBytes=json_encode(
            $claims,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
        );
        $sig=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claimBytes,
            sodium_crypto_sign_secretkey($pair)
        ));
        return [
            $body,
            $claimBytes,
            $sig,
            ['SYNTHETIC_F242_KEY'=>[
                'public_key'=>sodium_crypto_sign_publickey($pair),
                'enabled'=>true,
            ]],
        ];
    }

    private static function verifies(array $fixture,string $tenant=self::TENANT): bool
    {
        [$body,$claims,$sig,$keys]=$fixture;
        return (new \ImagineAI\MockBridge\MockServiceIdentityPreflight())->verifyOnce(
            $body,$claims,$sig,$keys,$tenant,self::NOW
        );
    }

    public function test_f242_two_physical_pg_connections_reject_same_ed25519_nonce(): void
    {
        $one=DB::connection();
        $two=DB::connection('aia_f242_second');
        self::assertNotSame(
            (int)$one->selectOne('SELECT pg_backend_pid() AS pid')->pid,
            (int)$two->selectOne('SELECT pg_backend_pid() AS pid')->pid
        );
        $fixture=self::signed('SYNTHETIC_F242_NONCE_FIRST');
        self::assertTrue(self::verifies($fixture));
        self::assertTrue((new AiaPgNonceCandidateLedger($one))->claim(
            'SYNTHETIC_F242_NONCE_FIRST',self::TENANT,self::NOW
        ));
        // Fresh PHP verifier accepts fixture; persisted DB rejects replay.
        self::assertTrue(self::verifies($fixture));
        self::assertFalse((new AiaPgNonceCandidateLedger($two))->claim(
            'SYNTHETIC_F242_NONCE_FIRST',self::TENANT,self::NOW
        ));
        self::assertFalse((new AiaPgNonceCandidateLedger($two))->claim(
            'SYNTHETIC_F242_NONCE_FIRST','SYNTHETIC_TENANT_B',self::NOW
        ));
        self::assertSame(1,(int)$one->table('aia_lab_nonce_atomic_probe')->count());
        self::assertSame([
            'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state'=>'DENIED_OR_NOT_FOUND',
            'projection'=>null,'real_authorization'=>false,'external_calls'=>0,
        ],AiaReadOnlyMockBoundary::inspectCanonicalJson($fixture[0]));
    }

    public function test_f242_invalid_proof_does_not_consume_nonce(): void
    {
        $fixture=self::signed('SYNTHETIC_F242_NONCE_INVALID');
        $tampered=$fixture;
        $tampered[2]=base64_encode(str_repeat("\x00",64));
        self::assertFalse(self::verifies($tampered));
        $expired=self::signed(
            'SYNTHETIC_F242_NONCE_INVALID',self::TENANT,['expires_at'=>self::NOW]
        );
        self::assertFalse(self::verifies($expired));
        self::assertSame(0,(int)DB::table('aia_lab_nonce_atomic_probe')->count());

        self::assertTrue(self::verifies($fixture));
        $one=new AiaPgNonceCandidateLedger(DB::connection());
        self::assertTrue($one->claim('SYNTHETIC_F242_NONCE_INVALID',self::TENANT,self::NOW));
        self::assertFalse($one->claim('SYNTHETIC_F242_NONCE_INVALID',self::TENANT,self::NOW));
    }

    public function test_f242_tenant_mismatch_cannot_create_pg_claim(): void
    {
        $cross=self::signed('SYNTHETIC_F242_NONCE_TENANT','SYNTHETIC_TENANT_B');
        self::assertFalse(self::verifies($cross,self::TENANT));
        self::assertSame(0,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
        $valid=self::signed('SYNTHETIC_F242_NONCE_TENANT');
        self::assertTrue(self::verifies($valid));
        self::assertTrue((new AiaPgNonceCandidateLedger(DB::connection()))->claim(
            'SYNTHETIC_F242_NONCE_TENANT',self::TENANT,self::NOW
        ));
    }

    public function test_f242_unavailable_pg_ledger_denies_even_valid_signature(): void
    {
        $fixture=self::signed('SYNTHETIC_F242_NONCE_OUTAGE');
        self::assertTrue(self::verifies($fixture));
        DB::statement('DROP TABLE '.self::TABLE);
        $ledger=new AiaPgNonceCandidateLedger(DB::connection());
        self::assertFalse($ledger->claim(
            'SYNTHETIC_F242_NONCE_OUTAGE',self::TENANT,self::NOW
        ));
        self::assertSame(
            'DENIED_OR_NOT_FOUND',
            AiaReadOnlyMockBoundary::inspectCanonicalJson($fixture[0])['state']
        );
    }
}
