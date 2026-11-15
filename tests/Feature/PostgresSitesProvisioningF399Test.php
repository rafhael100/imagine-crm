<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\PrincipalReference;
use App\Core\Leads\LaboratorySiteBindingProvisioner;
use App\Core\Leads\LaboratoryPortalS2sBindingGateway;
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
            // F400 uses an immutable S2S receipt ledger; the entire PG16
            // container is disposable and destroyed at the end of this job.
            // Do not bypass append-only protections to clean the test state.
            if (getenv('CRM_SITES_F400_S2S_CI') === '1') {
                parent::tearDown();
                return;
            }
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

    /**
     * F400: signed fake Portal commands are an additional independent gate.
     * The F399 owner registry still decides what may actually be provisioned.
     */
    public function test_portal_s2s_scoped_commands_replay_rotation_and_atomic_revoke(): void
    {
        if (getenv('CRM_SITES_F400_S2S_CI') !== '1') {
            $this->markTestSkipped('F400 dedicated GitHub Actions/PG16 fixture only.');
        }

        $gateway = new LaboratoryPortalS2sBindingGateway($this->owner);
        $now = 1790000000;

        $this->authority('approved', 'settled', 'recorded');
        foreach ([
            ['lab-portal-crm-provision-a', 'provision'],
            ['lab-portal-crm-provision-b', 'provision'],
            ['lab-portal-crm-revoke-c', 'revoke'],
        ] as [$key, $action]) {
            $this->owner->table('crm_lab_site_s2s_keys')->insert([
                'key_id' => $key,
                'source_service' => 'imagine-portal',
                'audience_service' => 'imagine-crm',
                'allowed_action' => $action,
                'state' => 'active',
                'valid_from' => $now - 100,
                'valid_until' => $now + 300,
                'version' => 1,
            ]);
        }
        $claim = [
            'protocol' => 'imagine-s2s-f400-ci-v1',
            'sourceService' => 'imagine-portal',
            'audienceService' => 'imagine-crm',
            'keyId' => 'lab-portal-crm-provision-a',
            'action' => 'provision',
            'organizationReference' => self::ORG_A,
            'orderReference' => self::ORDER,
            'bindingReference' => self::BINDING,
            'expectedOrderVersion' => 1,
            'nonce' => '40404040-4040-4040-8040-404040404041',
            'issuedAt' => $now - 10,
            'expiresAt' => $now + 40,
        ];
        $signed = $gateway->signForCi($claim);
        $unsigned = $signed;
        unset($unsigned['signature']);
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($unsigned, $now)['status_code']);

        foreach ([
            ['signature' => str_repeat('0',64)],
            ['sourceService' => 'imagine-sites'],
            ['audienceService' => 'imagine-sites'],
            ['action' => 'revoke'],
            ['organizationReference' => self::ORG_B],
            ['bindingReference' => '00000000-0000-4000-8000-000000000001'],
            ['expectedOrderVersion' => 2],
            ['expiresAt' => $now + 400],
            ['issuedAt' => $now + 40],
            ['unexpectedField' => 'x'],
        ] as $changes) {
            $tampered = array_replace($signed, $changes);
            $this->assertSame('synthetic_s2s_denied',
                $gateway->consume($tampered, $now)['status_code']);
        }

        // Even with a fresh signature, a claim for another company, binding,
        // order revision or source service is not an authorization.
        foreach ([
            ['organizationReference' => self::ORG_B],
            ['bindingReference' => '00000000-0000-4000-8000-000000000001'],
            ['expectedOrderVersion' => 2],
        ] as $changes) {
            $reSigned = $gateway->signForCi(array_replace($claim, $changes));
            $this->assertSame('synthetic_s2s_denied',
                $gateway->consume($reSigned, $now)['status_code']);
        }
        $noKey = $signed;
        $noKey['keyId'] = 'unknown-forged-credential';
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($noKey, $now)['status_code']);

        $this->assertSame(0,$this->owner->table('crm_lab_site_s2s_inbox')->count());
        $this->assertSame(0,$this->owner->table('crm_lab_site_delivery_bindings')->count());

        $first = $gateway->consume($signed, $now);
        $this->assertSame('synthetic_provisioned', $first['status_code']);
        $this->assertFalse($first['can_publish']);
        $this->assertFalse($first['can_contact']);
        $this->assertFalse($first['can_activate']);
        $this->assertSame('synthetic_s2s_replayed',
            $gateway->consume($signed, $now+1)['status_code']);
        $this->assertSame(1,$this->owner->table('crm_lab_site_s2s_inbox')->count());

        // Exact nonce is source-global, independent of credential rotation.
        $reusingNonce = $claim;
        $reusingNonce['keyId'] = 'lab-portal-crm-provision-b';
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($gateway->signForCi($reusingNonce), $now)['status_code']);

        $siteEvent = self::message(self::ORG_A, self::LEAD, self::NONCE);
        $this->assertSame('synthetic_captured',
            $this->capture(self::ORG_A,self::LEAD,$siteEvent)['status_code']);
        $this->assertSame('synthetic_replayed',
            $this->capture(self::ORG_A,self::LEAD,$siteEvent,$now+1)['status_code']);
        $this->assertSame(1,$this->owner->table('crm_lab_leads')->count());

        // A provision credential cannot perform a revoke operation,
        // regardless of the validity of its own HMAC.
        $wrongRole = array_replace($claim, [
            'action' => 'revoke',
            'nonce' => '40404040-4040-4040-8040-404040404042',
        ]);
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($gateway->signForCi($wrongRole), $now)['status_code']);

        $rotated = array_replace($claim, [
            'keyId'=>'lab-portal-crm-provision-b',
            'nonce'=>'40404040-4040-4040-8040-404040404043',
        ]);
        $this->assertSame('synthetic_already_provisioned',
            $gateway->consume($gateway->signForCi($rotated), $now)['status_code']);
        $this->owner->table('crm_lab_site_s2s_keys')
            ->where('key_id','lab-portal-crm-provision-a')
            ->update(['state'=>'revoked','version'=>2]);
        $oldKey = array_replace($claim, [
            'nonce'=>'40404040-4040-4040-8040-404040404044',
        ]);
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($gateway->signForCi($oldKey), $now)['status_code']);
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($signed, $now)['status_code']);
        try {
            $this->owner->table('crm_lab_site_s2s_keys')
                ->where('key_id','lab-portal-crm-provision-a')
                ->update(['state'=>'active','version'=>3]);
            $this->fail('Revoked S2S key was reactivated.');
        } catch (QueryException $exception) {
            $this->assertSame('23514', $exception->errorInfo[0] ?? null);
        }

        $revoke = array_replace($claim, [
            'keyId'=>'lab-portal-crm-revoke-c',
            'action'=>'revoke',
            'nonce'=>'40404040-4040-4040-8040-404040404045',
        ]);
        $this->assertSame('synthetic_revoked',
            $gateway->consume($gateway->signForCi($revoke), $now)['status_code']);
        $this->assertSame('revoked',
            $this->owner->table('crm_lab_site_delivery_bindings')->value('state'));
        $this->assertSame('revoked',
            $this->owner->table('crm_lab_site_provision_orders')->value('state'));
        $this->assertSame('synthetic_conflict_denied',
            $this->capture(self::ORG_A,self::LEAD,$siteEvent,$now+2)['status_code']);
        $this->assertSame(1,$this->owner->table('crm_lab_leads')->count());
        $this->assertSame(2,$this->owner->table('crm_lab_site_provision_events')->count());
        $this->assertSame(3,$this->owner->table('crm_lab_site_s2s_inbox')->count());
        $this->assertSame('synthetic_s2s_replayed',
            $gateway->consume($gateway->signForCi($revoke), $now+2)['status_code']);

        $afterRevoke = array_replace($rotated, [
            'expectedOrderVersion'=>2,
            'nonce'=>'40404040-4040-4040-8040-404040404046',
        ]);
        $this->assertSame('synthetic_s2s_denied',
            $gateway->consume($gateway->signForCi($afterRevoke), $now+3)['status_code']);
        $this->assertSame(3,$this->owner->table('crm_lab_site_s2s_inbox')->count());

        foreach ([
            ['crm_lab_site_s2s_keys','SELECT'],
            ['crm_lab_site_s2s_inbox','SELECT'],
            ['crm_lab_site_s2s_keys','UPDATE'],
            ['crm_lab_site_s2s_inbox','INSERT'],
        ] as [$table,$action]) {
            $this->assertFalse((bool)$this->owner->selectOne(
                'SELECT has_table_privilege(?, ?, ?) AS allowed',
                ['crm_app_ci','public.'.$table,$action],
            )->allowed);
        }

        // No fake authorization can be invoked when its CI gate is disabled.
        putenv('CRM_SITES_F400_S2S_CI=0');
        try {
            $gateway->consume($signed, $now);
            $this->fail('F400 CI-only guard was bypassed.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }
    }
}
