<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{
    AgentsPrivateKeyRevocationLockLab, AiaPgNonceCandidateLedger,
    AiaReadOnlyMockBoundary
};
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F248 — PostgreSQL row-lock serialization with a real independent PG
 * revoker connection. Fully synthetic; NO route, IAM grant or actual lead.
 */
final class AgentsPrivateKeyRevocationLockTest extends TestCase
{
    private const TABLE = 'public.aia_lab_service_keys_f248';
    private const NONCES = 'public.aia_lab_nonce_atomic_probe';
    private const ROLE = 'crm_aia_keylock_f248_ci';
    private const NOW = 1794690010;

    private bool $roleCreated=false;
    private bool $keysCreated=false;
    private bool $noncesCreated=false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F248_ROW_LOCK_LAB') !== 'true') {
            $this->markTestSkipped('F248 requires isolated disposable PostgreSQL CI.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || DB::connection()->getDriverName() !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'imagine_crm_ci'
            || DB::connection()->getConfig('host') !== '127.0.0.1'
            || DB::connection()->getConfig('username') !== 'postgres'
            || DB::connection()->transactionLevel() !== 0) {
            throw new \RuntimeException('F248_UNSAFE_PG_ENVIRONMENT_DENIED');
        }
        $who=DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$who || $who->dbname !== 'imagine_crm_ci'
            || $who->username !== 'postgres') {
            throw new \RuntimeException('F248_DATABASE_IDENTITY_DENIED');
        }
        $fixture=base_path('_aia_f248_lock_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
        ] as $file) {
            self::assertFileExists($fixture.'/'.$file);
        }
        spl_autoload_register(static function(string $class) use($fixture): void {
            $prefix='ImagineAI\\';
            if(str_starts_with($class,$prefix)) {
                $file=$fixture.'/src/'.str_replace(
                    '\\','/',substr($class,strlen($prefix))
                ).'.php';
                if(is_file($file)) require_once $file;
            }
        });

        DB::statement("CREATE ROLE crm_aia_keylock_f248_ci LOGIN
            PASSWORD 'f248-disposable-lock-role'
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
            revision integer NOT NULL,
            lock_marker integer NOT NULL DEFAULT 0
        )');
        $this->keysCreated=true;
        DB::statement('CREATE TABLE '.self::NONCES.' (
            issuer varchar(90) NOT NULL,
            audience varchar(90) NOT NULL,
            nonce varchar(90) NOT NULL,
            tenant_ref varchar(90) NOT NULL,
            claimed_at bigint NOT NULL,
            PRIMARY KEY(issuer,audience,nonce)
        )');
        $this->noncesCreated=true;

        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE '.self::TABLE.' FORCE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY f248_read_active_key ON ".self::TABLE."
            FOR SELECT TO crm_aia_keylock_f248_ci
            USING (
                issuer='SYNTHETIC_AGENT_SERVICE'
                AND audience='SYNTHETIC_CRM_API'
                AND subject='SYNTHETIC_AGENTS_READ_ONLY'
                AND enabled=true AND revoked_at IS NULL
            )");
        DB::statement("CREATE POLICY f248_lock_but_never_change ON ".self::TABLE."
            FOR UPDATE TO crm_aia_keylock_f248_ci
            USING (
                issuer='SYNTHETIC_AGENT_SERVICE'
                AND audience='SYNTHETIC_CRM_API'
                AND subject='SYNTHETIC_AGENTS_READ_ONLY'
                AND enabled=true AND revoked_at IS NULL
            )
            WITH CHECK (false)");
        DB::statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO '.self::ROLE);
        DB::statement('GRANT USAGE ON SCHEMA public TO '.self::ROLE);
        DB::statement('GRANT SELECT ON TABLE '.self::TABLE.' TO '.self::ROLE);
        DB::statement('GRANT UPDATE (lock_marker) ON TABLE '.self::TABLE.' TO '.self::ROLE);

        $settings=config('database.connections.pgsql');
        $settings['url']=null;
        $locked=$settings;
        $locked['username']=self::ROLE;
        $locked['password']='f248-disposable-lock-role';
        config(['database.connections.aia_f248_lock'=>$locked]);
        config(['database.connections.aia_f248_revoker'=>$settings]);
        DB::purge('aia_f248_lock');
        DB::purge('aia_f248_revoker');
    }

    protected function tearDown(): void
    {
        try {
            DB::disconnect('aia_f248_lock');
            DB::purge('aia_f248_lock');
            DB::disconnect('aia_f248_revoker');
            DB::purge('aia_f248_revoker');
            if ($this->noncesCreated) DB::statement('DROP TABLE IF EXISTS '.self::NONCES);
            if ($this->keysCreated) DB::statement('DROP TABLE IF EXISTS '.self::TABLE);
            if ($this->roleCreated) {
                DB::statement('REVOKE USAGE ON SCHEMA public FROM '.self::ROLE);
                DB::statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM '.self::ROLE);
                DB::statement('DROP ROLE IF EXISTS '.self::ROLE);
            }
        } finally {
            parent::tearDown();
        }
    }

    private static function keyPair(): array
    {
        $pair=sodium_crypto_sign_seed_keypair(
            hash('sha256','PUBLIC_F248_LOCK_TEST_ONLY',true)
        );
        return [
            sodium_crypto_sign_publickey($pair),
            sodium_crypto_sign_secretkey($pair),
        ];
    }

    private function insertKey(): void
    {
        [$public]=self::keyPair();
        DB::table('aia_lab_service_keys_f248')->insert([
            'key_id'=>'SYNTHETIC_F248_KEY_A',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'public_key_base64'=>base64_encode($public),
            'enabled'=>true,'revoked_at'=>null,
            'not_before'=>self::NOW-10,'expires_at'=>self::NOW+30,
            'revision'=>1,'lock_marker'=>0,
        ]);
    }

    private static function fixture(string $nonce): array
    {
        [, $secret]=self::keyPair();
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F248_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_F248_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F248_LEAD',
            'purpose_ref'=>'SYNTHETIC_F248_PURPOSE',
            'fields'=>['ref','stage'],
        ]);
        $claims=json_encode([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>'SYNTHETIC_F248_KEY_A',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>$nonce,
            'issued_at'=>self::NOW-5,
            'expires_at'=>self::NOW+10,
            'request_sha256'=>hash('sha256',$body),
        ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $signature=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claims,$secret
        ));
        return [$body,$claims,$signature];
    }

    public function test_key_row_lock_serializes_signed_nonce_with_independent_revoker(): void
    {
        $this->insertKey();
        $lockedDb=DB::connection('aia_f248_lock');
        $revoker=DB::connection('aia_f248_revoker');
        self::assertNotSame(
            $lockedDb->selectOne('SELECT pg_backend_pid() AS pid')->pid,
            $revoker->selectOne('SELECT pg_backend_pid() AS pid')->pid
        );
        $gate=new AgentsPrivateKeyRevocationLockLab($lockedDb);
        $first=self::fixture('SYNTHETIC_F248_NONCE_FIRST');
        $blockedRevocation=false;
        $proof=$gate->withLockedKey(
            'SYNTHETIC_F248_KEY_A',self::NOW,
            function(array $trust) use($first,$revoker,&$blockedRevocation): bool {
                self::assertSame('SYNTHETIC_TENANT_A',$trust['tenant_ref']);
                [$body,$claims,$sig]=$first;
                $valid=(new \ImagineAI\MockBridge\MockServiceIdentityPreflight())
                    ->verifyOnce(
                        $body,$claims,$sig,$trust['trusted_keys'],
                        $trust['tenant_ref'],self::NOW
                    );
                self::assertTrue($valid);
                $claimed=(new AiaPgNonceCandidateLedger(DB::connection()))->claim(
                    'SYNTHETIC_F248_NONCE_FIRST',
                    $trust['tenant_ref'],self::NOW
                );
                self::assertTrue($claimed);

                // Separate physical PG connection tries to revoke the EXACT
                // key while verifier holds transaction row lock.
                $revoker->statement("SET lock_timeout='160ms'");
                try {
                    $revoker->table('public.aia_lab_service_keys_f248')
                        ->where('key_id','SYNTHETIC_F248_KEY_A')
                        ->update(['revoked_at'=>self::NOW]);
                } catch (\Throwable) {
                    $blockedRevocation=true;
                } finally {
                    $revoker->statement('RESET lock_timeout');
                }
                return $valid && $claimed && $blockedRevocation;
            }
        );
        self::assertTrue($proof);
        self::assertTrue($blockedRevocation);
        self::assertSame(0,$lockedDb->transactionLevel());
        self::assertSame(0,$revoker->transactionLevel());
        self::assertSame(1,(int)DB::table('aia_lab_nonce_atomic_probe')->count());

        // With the lock released, revocation wins. Future signed nonce must
        // never get to the callback or consume a new DB nonce.
        $revoker->table('public.aia_lab_service_keys_f248')
            ->where('key_id','SYNTHETIC_F248_KEY_A')
            ->update(['revoked_at'=>self::NOW]);
        $called=0;
        self::assertFalse($gate->withLockedKey(
            'SYNTHETIC_F248_KEY_A',self::NOW,
            static function() use(&$called): bool {
                ++$called;
                return true;
            }
        ));
        self::assertSame(0,$called);
        self::assertSame(1,(int)DB::table('aia_lab_nonce_atomic_probe')->count());
        self::assertSame(
            'DENIED_OR_NOT_FOUND',
            AiaReadOnlyMockBoundary::inspectCanonicalJson('')['state']
        );
    }

    public function test_lock_role_cannot_modify_trust_bytes_even_with_marker_update_privilege(): void
    {
        $this->insertKey();
        $db=DB::connection('aia_f248_lock');
        $gate=new AgentsPrivateKeyRevocationLockLab($db);
        self::assertTrue($gate->withLockedKey(
            'SYNTHETIC_F248_KEY_A',self::NOW,
            static fn(array $trusted): bool =>
                $trusted['tenant_ref'] === 'SYNTHETIC_TENANT_A'
        ));

        $denied=[];
        foreach ([
            ['lock_marker'=>2],
            ['tenant_ref'=>'SYNTHETIC_TENANT_B'],
            ['revoked_at'=>self::NOW],
            ['public_key_base64'=>base64_encode(str_repeat('X',32))],
        ] as $mutation) {
            $bad=false;
            try {
                $db->table('public.aia_lab_service_keys_f248')
                    ->where('key_id','SYNTHETIC_F248_KEY_A')
                    ->update($mutation);
            } catch (\Throwable) {
                $bad=true;
            }
            $denied[]=$bad;
        }
        self::assertSame([true,true,true,true],$denied);
        self::assertSame(0,$db->transactionLevel());

        DB::statement('ALTER TABLE '.self::TABLE.' DISABLE ROW LEVEL SECURITY');
        self::assertFalse($gate->withLockedKey(
            'SYNTHETIC_F248_KEY_A',self::NOW,
            static fn(): bool => true
        ));
    }
}
