<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\PrincipalReference;
use App\Core\Leads\LaboratorySiteBindingProvisioner;
use App\Core\Leads\LaboratorySignedSiteCapture;
use App\Core\Leads\LaboratoryTenantLeadLedger;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * CI-only PG16 F399. Real original Sites F398 byte source + server-owned
 * synthetic provisioning authority + original CRM RLS and signed capture.
 * Not a production authorization / consent / payment implementation.
 */
final class PostgresSitesProvisioningF399Test extends TestCase
{
    private const ORG_A='a1111111-1111-4111-8111-111111111111';
    private const ORG_B='b2222222-2222-4222-8222-222222222222';
    private const USER='c3333333-3333-4333-8333-333333333333';
    private const LEAD='a5555555-5555-4555-8555-555555555555';
    private const ORDER='order-fictional-f399-01';
    private const BINDING='23232323-2222-4222-8222-555555555551';
    private const SITE='34343434-2222-4222-8222-555555555551';
    private const FORM='45454545-2222-4222-8222-555555555551';
    private const DEST='56565656-2222-4222-8222-555555555551';
    private const SUBMISSION='12121212-2222-4222-8222-555555555551';
    private const NONCE='a8888888-8888-4888-8888-888888888888';
    private Connection $owner;
    private Connection $appRole;

    protected function setUp(): void
    {
        parent::setUp();
        $source=getenv('F398_SITES_SOURCE');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_SITES_F399_PROVISION_CI') !== '1'
            || getenv('SITES_F398_CRM_CAPTURE_CI') !== '1'
            || !is_string($source)
            || !is_file($source.'/src/Integration/SyntheticCrmCaptureEnvelope.php')
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci') {
            $this->markTestSkipped('F399 isolated PostgreSQL/Sites CI only.');
        }
        require_once $source.'/src/Integration/SyntheticCrmCaptureEnvelope.php';
        $this->owner=DB::connection();
        $this->assertSame('imagine_crm_ci',$this->owner->getDatabaseName());

        $now=now();
        $this->owner->table('crm_global_identities')->insert([
            'id'=>self::USER, 'created_at'=>$now,'updated_at'=>$now,
        ]);
        foreach ([self::ORG_A,self::ORG_B] as $i=>$org) {
            $this->owner->table('crm_organizations')->insert([
                'id'=>$org,'created_at'=>$now,'updated_at'=>$now,
            ]);
            $this->owner->table('crm_memberships')->insert([
                'id'=>$i===0
                    ? 'e7777777-7777-4777-8777-777777777777'
                    : 'f8888888-8888-4888-8888-888888888888',
                'organization_id'=>$org,
                'global_identity_id'=>self::USER,
                'status'=>'active',
                'version'=>1,
                'created_at'=>$now,
                'updated_at'=>$now,
            ]);
        }

        $pg=config('database.connections.pgsql');
        $this->assertIsArray($pg);
        $pg['url']=null;
        $pg['username']=getenv('RLS_TEST_USERNAME');
        $pg['password']=getenv('RLS_TEST_PASSWORD');
        config(['database.connections.f399_restricted'=>$pg]);
        DB::purge('f399_restricted');
        $this->appRole=DB::connection('f399_restricted');
    }

    protected function tearDown(): void
    {
        if (isset($this->appRole)) {
            while($this->appRole->transactionLevel()>0) {
                $this->appRole->rollBack();
            }
            DB::purge('f399_restricted');
        }
        if (isset($this->owner)) {
            $this->owner->table('crm_lab_lead_capture_keys')->delete();
            $this->owner->table('crm_lab_site_provision_events')->delete();
            $this->owner->table('crm_lab_site_delivery_bindings')->delete();
            $this->owner->table('crm_lab_site_provision_orders')->delete();
            $this->owner->table('crm_lab_lead_events')->delete();
            $this->owner->table('crm_lab_leads')->delete();
            $this->owner->table('crm_memberships')
                ->whereIn('organization_id',[self::ORG_A,self::ORG_B])->delete();
            $this->owner->table('crm_organizations')
                ->whereIn('id',[self::ORG_A,self::ORG_B])->delete();
            $this->owner->table('crm_global_identities')
                ->where('id',self::USER)->delete();
        }
        parent::tearDown();
    }

    private function authority(string $state='draft',string $payment='pending',
                               string $consent='missing'): void
    {
        $this->owner->table('crm_lab_site_provision_orders')->insert([
            'organization_id'=>self::ORG_A,
            'order_reference'=>self::ORDER,
            'binding_reference'=>self::BINDING,
            'site_reference'=>self::SITE,
            'form_reference'=>self::FORM,
            'destination_reference'=>self::DEST,
            'product_code'=>'onepage',
            'source_code'=>'onepage',
            'contract_revision'=>1,
            'payment_state'=>$payment,
            'consent_state'=>$consent,
            'state'=>$state,
            'version'=>1,
        ]);
    }

    private static function claim(string $org): SyntheticIdempotencyClaim
    {
        return new SyntheticIdempotencyClaim(
            new LeadSubmissionIdentity($org,self::BINDING,self::SUBMISSION),
            self::SITE,self::FORM,self::DEST,
        );
    }

    /** @return array<string,mixed> */
    private static function message(string $org,string $lead,string $nonce): array
    {
        $now=1790000000;
        return \Imagine\Sites\Integration\SyntheticCrmCaptureEnvelope::sign([
            'organizationReference'=>$org,
            'bindingReference'=>self::BINDING,
            'submissionReference'=>self::SUBMISSION,
            'siteReference'=>self::SITE,
            'formReference'=>self::FORM,
            'destinationReference'=>self::DEST,
            'leadId'=>$lead,
            'syntheticTitle'=>'DEMO_F399',
            'sourceCode'=>'onepage',
            'nonce'=>$nonce,
            'issuedAt'=>$now-10,
            'expiresAt'=>$now+40,
        ],$now);
    }

    private function capture(string $org,string $lead,array $message,int $now=1790000000): array
    {
        return (new LaboratorySignedSiteCapture(
            new LaboratoryTenantLeadLedger($this->appRole),
            (string)getenv('CRM_LEADS_SIGNING_KEY_CI'),
        ))->captureSigned(
            new PrincipalReference(self::USER),
            $org,$lead,
            $message['syntheticTitle'],$message['sourceCode'],
            self::claim($org),
            $message['nonce'],$message['issuedAt'],$message['expiresAt'],
            $now,$message['signature'],
        );
    }

    public function test_original_sites_event_follows_server_owned_provisioning_and_atomic_revocation(): void
    {
        $provision=new LaboratorySiteBindingProvisioner($this->owner);
        $msg=self::message(self::ORG_A,self::LEAD,self::NONCE);

        $this->assertSame('synthetic_provision_denied',
            $provision->provision(self::ORG_A,self::ORDER)['status_code']);
        $this->assertSame('synthetic_conflict_denied',
            $this->capture(self::ORG_A,self::LEAD,$msg)['status_code']);
        $this->authority();
        $this->assertSame('synthetic_provision_denied',
            $provision->provision(self::ORG_A,self::ORDER)['status_code']);
        $this->owner->table('crm_lab_site_provision_orders')
            ->update(['payment_state'=>'settled','version'=>2]);
        $this->assertSame('synthetic_provision_denied',
            $provision->provision(self::ORG_A,self::ORDER)['status_code']);
        $this->owner->table('crm_lab_site_provision_orders')
            ->update(['consent_state'=>'recorded','state'=>'approved','version'=>3]);
        $this->assertSame('synthetic_provision_denied',
            $provision->provision(self::ORG_B,self::ORDER)['status_code']);
        $this->assertSame(0,$this->owner->table('crm_lab_site_delivery_bindings')->count());

        $first=$provision->provision(self::ORG_A,self::ORDER);
        $retry=$provision->provision(self::ORG_A,self::ORDER);
        $this->assertSame('synthetic_provisioned',$first['status_code']);
        $this->assertSame('synthetic_already_provisioned',$retry['status_code']);
        foreach ([$first,$retry] as $response) {
            $this->assertFalse($response['can_publish']);
            $this->assertFalse($response['can_contact']);
            $this->assertFalse($response['can_activate']);
        }
        $row=$this->owner->table('crm_lab_site_delivery_bindings')->first();
        $this->assertSame(self::FORM,(string)$row->form_reference);
        $this->assertSame(self::DEST,(string)$row->destination_reference);
        $this->assertSame('active',$row->state);
        $this->assertSame(1,$this->owner->table('crm_lab_site_provision_events')->count());

        $captured=$this->capture(self::ORG_A,self::LEAD,$msg);
        $again=$this->capture(self::ORG_A,self::LEAD,$msg,1790000001);
        $this->assertSame('synthetic_captured',$captured['status_code']);
        $this->assertSame('synthetic_replayed',$again['status_code']);
        $this->assertFalse($captured['can_contact']);
        $this->assertFalse($captured['can_activate']);
        $this->assertSame(1,$this->owner->table('crm_lab_leads')->count());

        // B cannot use A's site even if it signs a matching fake envelope.
        $msgB=self::message(self::ORG_B,
            'b6666666-6666-4666-8666-666666666666',
            'b8888888-8888-4888-8888-888888888888');
        $this->assertSame('synthetic_conflict_denied',
            $this->capture(self::ORG_B,$msgB['leadId'],$msgB)['status_code']);
        $this->assertFalse((bool)$this->owner->selectOne(
            "SELECT has_table_privilege('crm_app_ci', 'public.crm_lab_site_provision_orders', 'SELECT') AS allowed"
        )->allowed);
        $this->assertFalse((bool)$this->owner->selectOne(
            "SELECT has_table_privilege('crm_app_ci', 'public.crm_lab_site_provision_events', 'INSERT') AS allowed"
        )->allowed);

        // Independent order revocation updates its Sites delivery binding in
        // the SAME PostgreSQL transaction via a trigger + PG07 advisory lock.
        $revoked=$provision->revoke(self::ORG_A,self::ORDER);
        $this->assertSame('synthetic_revoked',$revoked['status_code']);
        $this->assertSame('revoked',
            $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));
        $this->assertSame(2,
            $this->owner->table('crm_lab_site_delivery_bindings')->value('version'));
        $this->assertSame(2,$this->owner->table('crm_lab_site_provision_events')->count());
        $this->assertSame('synthetic_conflict_denied',
            $this->capture(self::ORG_A,self::LEAD,$msg,1790000002)['status_code']);
        $this->assertSame('synthetic_already_revoked',
            $provision->revoke(self::ORG_A,self::ORDER)['status_code']);
        $this->assertSame('synthetic_provision_denied',
            $provision->provision(self::ORG_A,self::ORDER)['status_code']);

        try {
            $this->owner->table('crm_lab_site_provision_orders')
                ->update(['state'=>'approved','version'=>5]);
            $this->fail('Revoked authoritative order cannot be reactivated');
        } catch (QueryException $e) {
            $this->assertSame('23514',$e->errorInfo[0]??null);
        }
        $this->assertSame(1,$this->owner->table('crm_lab_leads')->count());
        $this->assertSame(1,$this->owner->table('crm_lab_lead_events')->count());
        $this->assertSame(1,$this->owner->table('crm_lab_lead_capture_keys')->count());
        $this->assertSame([],$this->appRole->table('public.crm_lab_leads')->get()->all());

        // Refuse attempts to execute fixture provisioning outside allowed CI.
        putenv('CRM_SITES_F399_PROVISION_CI=0');
        try {
            $provision->provision(self::ORG_A,self::ORDER);
            $this->fail('CI guard bypass');
        } catch (LogicException) {
            $this->assertTrue(true);
        }
    }
}
