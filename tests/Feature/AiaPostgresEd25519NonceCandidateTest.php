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
