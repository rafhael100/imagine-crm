<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\{AiaPgServiceDenialAuditLab, AiaReadOnlyMockBoundary};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ImagineAI\MockBridge\MockCrmReadRequest;
use RuntimeException;
use Tests\TestCase;

/**
 * F236: append-only audit under a separate non-owner INSERT-only PG16 role.
 * The public CRM read result remains DENIED_OR_NOT_FOUND in every case.
 */
final class AiaServiceDenialAuditLabTest extends TestCase
{
    private const TABLE = 'public.aia_lab_service_denial_audit';
    private bool $ready = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_F236_AUDIT_LAB') !== 'true') {
            $this->markTestSkipped('F236 is restricted to the disposable PG16 CI runner.');
        }
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci') {
            throw new RuntimeException('F236_NON_DISPOSABLE_AUDIT_DENIED');
        }
        $who=DB::selectOne(
            'SELECT current_database() AS dbname, current_user AS username'
        );
        if (!$who || $who->dbname!=='imagine_crm_ci' || $who->username!=='postgres') {
            throw new RuntimeException('F236_DATABASE_IDENTITY_DENIED');
        }
        $agents=dirname(__DIR__, 2).'/_aia_f231_fixture';
        if (!is_file($agents.'/src/MockBridge/MockCrmReadRequest.php')) {
            throw new RuntimeException('F236_PINNED_AGENTS_SOURCE_MISSING');
        }
        spl_autoload_register(static function(string $name) use($agents): void {
            $prefix='ImagineAI\\';
            if (str_starts_with($name,$prefix)) {
                require_once $agents.'/src/'.str_replace(
                    '\\','/',substr($name,strlen($prefix))
                ).'.php';
            }
        });

        DB::statement('CREATE TABLE '.self::TABLE.' (
            event_id uuid NOT NULL PRIMARY KEY,
            issuer varchar(40) NOT NULL CHECK (issuer = \'SYNTHETIC_AGENT_SERVICE\'),
            event_type varchar(40) NOT NULL CHECK (event_type = \'DENIED_OR_NOT_FOUND\'),
            request_sha256 char(64) NOT NULL,
            tenant_sha256 char(64) NOT NULL,
            occurred_at bigint NOT NULL CHECK (occurred_at BETWEEN 0 AND 4102444800)
        )');
        DB::statement(
            "CREATE ROLE crm_aia_audit_ci LOGIN PASSWORD 'ci-aia-audit-only'
             NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS"
        );
        $this->ready=true;
        DB::statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_aia_audit_ci');
        DB::statement('GRANT USAGE ON SCHEMA public TO crm_aia_audit_ci');
        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE '.self::TABLE.' FORCE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY aia_f236_insert_denial ON ".self::TABLE."
            FOR INSERT TO crm_aia_audit_ci
            WITH CHECK (issuer='SYNTHETIC_AGENT_SERVICE'
                        AND event_type='DENIED_OR_NOT_FOUND')");
        DB::statement('GRANT INSERT ON TABLE '.self::TABLE.' TO crm_aia_audit_ci');

        $settings=config('database.connections.pgsql');
        $settings['url']=null;
        $settings['username']='crm_aia_audit_ci';
        $settings['password']='ci-aia-audit-only';
        config(['database.connections.aia_f236_audit_one'=>$settings]);
        config(['database.connections.aia_f236_audit_two'=>$settings]);
        DB::purge('aia_f236_audit_one');
        DB::purge('aia_f236_audit_two');
    }

    protected function tearDown(): void
    {
        if ($this->ready) {
            DB::disconnect('aia_f236_audit_one');
            DB::disconnect('aia_f236_audit_two');
            DB::purge('aia_f236_audit_one');
            DB::purge('aia_f236_audit_two');
            DB::statement('DROP TABLE IF EXISTS '.self::TABLE);
            DB::statement('REVOKE USAGE ON SCHEMA public FROM crm_aia_audit_ci');
            DB::statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM crm_aia_audit_ci');
            DB::statement('DROP ROLE IF EXISTS crm_aia_audit_ci');
        }
        parent::tearDown();
    }

    private static function request(): string
    {
        return MockCrmReadRequest::encodeCanonicalJson([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F236_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_F236_SERVICE_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F236_LEAD',
            'purpose_ref'=>'SYNTHETIC_F236_AUDIT_TEST',
            'fields'=>['ref','stage'],
        ]);
    }

    private static function assertPublicDenial(string $request): void
    {
        self::assertSame([
            'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state'=>'DENIED_OR_NOT_FOUND',
            'projection'=>null,
            'real_authorization'=>false,
            'external_calls'=>0,
        ],AiaReadOnlyMockBoundary::inspectCanonicalJson($request));
    }

    public function test_insert_only_role_logs_minimal_denials_and_unique_replay(): void
    {
        $request=self::request();
        self::assertPublicDenial($request);
        $one=new AiaPgServiceDenialAuditLab(DB::connection('aia_f236_audit_one'));
        $two=new AiaPgServiceDenialAuditLab(DB::connection('aia_f236_audit_two'));

        $firstPid=(int) DB::connection('aia_f236_audit_one')
            ->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $secondPid=(int) DB::connection('aia_f236_audit_two')
            ->selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $this->assertNotSame($firstPid,$secondPid);
        $event=(string) Str::uuid();
        $this->assertTrue($one->appendDenial($request,$event,1794690010));
        $this->assertFalse($two->appendDenial($request,$event,1794690011));
        $this->assertSame(1,(int) DB::table('aia_lab_service_denial_audit')->count());

        $entry=DB::table('aia_lab_service_denial_audit')->first();
        $this->assertSame($event,$entry->event_id);
        $this->assertSame('SYNTHETIC_AGENT_SERVICE',$entry->issuer);
        $this->assertSame('DENIED_OR_NOT_FOUND',$entry->event_type);
        $this->assertSame(hash('sha256',$request),$entry->request_sha256);
        $this->assertSame(hash(
            'sha256',"SYNTHETIC_AIA_TENANT_AUDIT_V1\nSYNTHETIC_TENANT_A"
        ),$entry->tenant_sha256);
        $this->assertFalse(
            in_array('principal_ref',array_keys((array)$entry),true)
        );
        $this->assertFalse(
            in_array('resource_ref',array_keys((array)$entry),true)
        );
        $this->assertFalse(
            in_array('email',array_keys((array)$entry),true)
        );

        // The dedicated role cannot see or modify the evidence it inserts.
        foreach ([
            static fn()=>DB::connection('aia_f236_audit_one')
                ->table('public.aia_lab_service_denial_audit')->count(),
            static fn()=>DB::connection('aia_f236_audit_one')
                ->table('public.aia_lab_service_denial_audit')
                ->where('event_id',$event)->update(['event_type'=>'ALLOWED']),
            static fn()=>DB::connection('aia_f236_audit_one')
                ->table('public.aia_lab_service_denial_audit')->delete(),
        ] as $forbidden) {
            $rejected=false;
            try { $forbidden(); }
            catch (\Throwable) { $rejected=true; }
            $this->assertTrue($rejected,'F236_AUDIT_PRIVILEGE_ESCALATION');
        }
        $this->assertSame(1,(int) DB::table('aia_lab_service_denial_audit')->count());
        self::assertPublicDenial($request);
    }

    public function test_invalid_request_non_uuid_role_tampering_and_backend_outage_fail_closed(): void
    {
        $request=self::request();
        $audit=new AiaPgServiceDenialAuditLab(DB::connection('aia_f236_audit_one'));
        $event=(string) Str::uuid();
        $this->assertFalse($audit->appendDenial($request,'SYNTHETIC_EVENT',1794690010));
        $this->assertFalse($audit->appendDenial($request,$event,-1));
        $this->assertFalse($audit->appendDenial('NOT_CANONICAL_JSON',$event,1794690010));
        $this->assertFalse($audit->appendDenial(
            str_replace('SYNTHETIC_TENANT_A','customer@example.test',$request),
            $event,1794690010
        ));
        $this->assertSame(0,(int) DB::table('aia_lab_service_denial_audit')->count());

        // Never fall back to the privileged fixture connection when the
        // expected INSERT-only audit role is missing.
        $wrongRole=new AiaPgServiceDenialAuditLab(DB::connection());
        $this->assertFalse($wrongRole->appendDenial($request,$event,1794690010));
        $this->assertSame(0,(int) DB::table('aia_lab_service_denial_audit')->count());

        DB::statement('DROP TABLE '.self::TABLE);
        $this->assertFalse($audit->appendDenial($request,$event,1794690010));
        self::assertPublicDenial($request);
    }
}
