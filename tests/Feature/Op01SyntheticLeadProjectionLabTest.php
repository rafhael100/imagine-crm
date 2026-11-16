<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Agents\Op01SyntheticLeadProjectionLab;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class Op01SyntheticLeadProjectionLabTest extends TestCase
{
    private const TABLE = 'public.aia_lab_op01_leads_p2';
    private const ROLE = 'crm_aia_leadreader_p2_ci';
    private const ALFA = 'SYNTHETIC_COMPANY_ALFA';
    private const BETA = 'SYNTHETIC_COMPANY_BETA';
    private bool $createdRole = false;
    private bool $createdTable = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_OP01_P2_PG_LAB') !== 'true') {
            $this->markTestSkipped('P2 requires disposable PostgreSQL 16 CI.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_USERNAME') !== 'postgres'
            || DB::getDriverName() !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'imagine_crm_ci'
            || DB::connection()->getConfig('host') !== '127.0.0.1'
            || DB::connection()->getConfig('username') !== 'postgres'
            || DB::connection()->transactionLevel() !== 0) {
            throw new \RuntimeException('OP01_P2_UNSAFE_CI_ENVIRONMENT_DENIED');
        }
        $identity=DB::selectOne(
            'SELECT current_database() AS dbname,current_user AS username'
        );
        if (!$identity || $identity->dbname !== 'imagine_crm_ci'
            || $identity->username !== 'postgres') {
            throw new \RuntimeException('OP01_P2_DB_IDENTITY_DENIED');
        }

        DB::statement("CREATE ROLE crm_aia_leadreader_p2_ci LOGIN
            PASSWORD 'p2-disposable-ci-only'
            NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS");
        $this->createdRole=true;
        DB::statement('CREATE TABLE '.self::TABLE.' (
            tenant_ref varchar(90) NOT NULL,
            ref varchar(90) NOT NULL,
            stage varchar(24) NOT NULL,
            contact_email varchar(100) NOT NULL,
            secret_note varchar(100) NOT NULL,
            PRIMARY KEY(tenant_ref,ref)
        )');
        $this->createdTable=true;
        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE '.self::TABLE.' FORCE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY p2_tenant_only ON ".self::TABLE."
            FOR SELECT TO crm_aia_leadreader_p2_ci
            USING (tenant_ref = current_setting('app.tenant_id', true))");
        DB::statement('GRANT CONNECT ON DATABASE imagine_crm_ci TO '.self::ROLE);
        DB::statement('GRANT USAGE ON SCHEMA public TO '.self::ROLE);
        DB::statement('GRANT SELECT (tenant_ref,ref,stage) ON TABLE '.self::TABLE.' TO '.self::ROLE);

        $config=config('database.connections.pgsql');
        $config['url']=null;
        $config['username']=self::ROLE;
        $config['password']='p2-disposable-ci-only';
        config(['database.connections.aia_p2_reader'=>$config]);
        DB::purge('aia_p2_reader');

        DB::table('aia_lab_op01_leads_p2')->insert([
            ['tenant_ref'=>self::ALFA,'ref'=>'SYNTHETIC_LEAD_SHARED',
                'stage'=>'qualified','contact_email'=>'SYNTHETIC_SECRET_EMAIL',
                'secret_note'=>'SYNTHETIC_SECRET_NOTE'],
            ['tenant_ref'=>self::BETA,'ref'=>'SYNTHETIC_LEAD_SHARED',
                'stage'=>'proposal','contact_email'=>'SYNTHETIC_OTHER_EMAIL',
                'secret_note'=>'SYNTHETIC_OTHER_NOTE'],
            ['tenant_ref'=>self::BETA,'ref'=>'SYNTHETIC_LEAD_BETA_ONLY',
                'stage'=>'new','contact_email'=>'SYNTHETIC_ONLY_EMAIL',
                'secret_note'=>'SYNTHETIC_ONLY_NOTE'],
        ]);
    }

    protected function tearDown(): void
    {
        try {
            DB::disconnect('aia_p2_reader');
            DB::purge('aia_p2_reader');
            if ($this->createdTable) DB::statement('DROP TABLE IF EXISTS '.self::TABLE);
            if ($this->createdRole) {
                DB::statement('REVOKE USAGE ON SCHEMA public FROM '.self::ROLE);
                DB::statement('REVOKE CONNECT ON DATABASE imagine_crm_ci FROM '.self::ROLE);
                DB::statement('DROP ROLE IF EXISTS '.self::ROLE);
            }
        } finally {
            parent::tearDown();
        }
    }

    private function reader(): Connection
    {
        return DB::connection('aia_p2_reader');
    }

    private function projection(): Op01SyntheticLeadProjectionLab
    {
        return new Op01SyntheticLeadProjectionLab($this->reader());
    }

    public function test_positive_p01_p02_projection_is_bounded_and_field_order_independent(): void
    {
        $projection=$this->projection();
        self::assertSame(['ref'=>'SYNTHETIC_LEAD_SHARED','stage'=>'qualified'],
            $projection->project(self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref','stage']));
        self::assertSame(['ref'=>'SYNTHETIC_LEAD_SHARED','stage'=>'qualified'],
            $projection->project(self::ALFA,'SYNTHETIC_LEAD_SHARED',['stage','ref']));
        self::assertSame(['ref'=>'SYNTHETIC_LEAD_SHARED'],
            $projection->project(self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']));
        self::assertSame(['stage'=>'qualified'],
            $projection->project(self::ALFA,'SYNTHETIC_LEAD_SHARED',['stage']));

        // Same opaque reference in two companies yields different data only
        // when the server-side synthetic test scope is explicitly changed.
        self::assertSame(['ref'=>'SYNTHETIC_LEAD_SHARED','stage'=>'proposal'],
            $projection->project(self::BETA,'SYNTHETIC_LEAD_SHARED',['stage','ref']));
        self::assertSame(0,$this->reader()->transactionLevel());
        $marker=$this->reader()->selectOne(
            "SELECT current_setting('app.tenant_id',true) AS tenant"
        );
        self::assertTrue(in_array($marker->tenant,[null,''],true));
    }

    public function test_negative_cross_tenant_missing_and_unapproved_fields(): void
    {
        $projection=$this->projection();
        self::assertNull($projection->project(
            self::ALFA,'SYNTHETIC_LEAD_BETA_ONLY',['ref','stage']
        ));
        self::assertNull($projection->project(
            self::ALFA,'SYNTHETIC_NOT_FOUND',['ref','stage']
        ));
        foreach ([
            [],['contact_email'],['secret_note'],
            ['ref','contact_email'],['stage','stage'],
            ['ref','stage','contact_email'],[1], [['ref']],
            ['stage','ref','ref'], ['stage'=>'ref'],
        ] as $fields) {
            self::assertNull($projection->project(
                self::ALFA,'SYNTHETIC_LEAD_SHARED',$fields
            ));
        }
        self::assertNull($projection->project(
            'INVALID_TENANT','SYNTHETIC_LEAD_SHARED',['ref']
        ));
        self::assertNull($projection->project(
            self::ALFA,'../../etc/passwd',['ref']
        ));
    }

    public function test_pg_role_cannot_read_contact_data_or_modify_rows(): void
    {
        $reader=$this->reader();
        foreach ([
            'SELECT contact_email FROM '.self::TABLE,
            'SELECT secret_note FROM '.self::TABLE,
            'SELECT * FROM '.self::TABLE,
            "UPDATE ".self::TABLE." SET stage='won'",
            'DELETE FROM '.self::TABLE,
        ] as $sql) {
            $denied=false;
            try { $reader->statement($sql); }
            catch (\Throwable) { $denied=true; }
            self::assertTrue($denied, 'Role must not execute restricted SQL.');
        }
        self::assertSame(['stage'=>'qualified'],
            $this->projection()->project(
                self::ALFA,'SYNTHETIC_LEAD_SHARED',['stage']
            ));
    }

    public function test_rls_and_privilege_drift_are_fail_closed(): void
    {
        self::assertNotNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));
        DB::statement('ALTER TABLE '.self::TABLE.' DISABLE ROW LEVEL SECURITY');
        self::assertNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));
        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');

        DB::statement(
            'GRANT SELECT(contact_email) ON TABLE '.self::TABLE.' TO '.self::ROLE
        );
        self::assertNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));
        DB::statement(
            'REVOKE SELECT(contact_email) ON TABLE '.self::TABLE.' FROM '.self::ROLE
        );
        self::assertNotNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));

        // A connection contaminated with a prior tenant must never be
        // silently reused for a different synthetic request.
        $reader=$this->reader();
        $reader->selectOne("SELECT set_config('app.tenant_id', ?, false)",
            [self::BETA]);
        self::assertNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));
        $reader->selectOne("SELECT set_config('app.tenant_id', '', false)");
        self::assertNotNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));
    }

    public function test_missing_table_or_wrong_database_role_denies_without_fallback(): void
    {
        self::assertNull(
            (new Op01SyntheticLeadProjectionLab(DB::connection()))->project(
                self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
            )
        );
        DB::statement('DROP TABLE '.self::TABLE);
        $this->createdTable=false;
        self::assertNull($this->projection()->project(
            self::ALFA,'SYNTHETIC_LEAD_SHARED',['ref']
        ));
    }
}
