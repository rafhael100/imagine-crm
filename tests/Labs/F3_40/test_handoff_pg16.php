<?php

declare(strict_types=1);

// F3-40 standalone integration of ACTUAL CRM PHP metadata/receipt classes
// and a PG16 throwaway test DB. No Laravel app boot, migrations or CRM endpoint.
require_once __DIR__.'/SyntheticPortalHandoffReceiver.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadReceiptMacAssessment.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadReceiptMacInspector.php';

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadReceiptMacInspector;
use Tests\Labs\F3_40\SyntheticPortalHandoffReceiver as Receiver;

if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F340_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='imagine_crm_handoff_ci'
    || !extension_loaded('pdo_pgsql')) {
    fwrite(STDERR,"F3-40 refuses non-disposable CI PostgreSQL.\n");
    exit(2);
}
$checks=0;
function check(bool $condition,string $message): void
{
    global $checks;
    ++$checks;
    if (!$condition) {
        throw new RuntimeException('F3-40 FAIL: '.$message);
    }
}
function fixture(string $submission='33333333-3333-4333-8333-333333333333'): array
{
    return [
        'protocol'=>'imagine.lead-intake',
        'schema_version'=>1,
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'submission_reference'=>$submission,
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666'
    ];
}
function binding(array $env): array
{
    return array_intersect_key($env,array_fill_keys([
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference'
    ],true));
}
function nonce(): string
{
    $raw=bin2hex(random_bytes(16));
    $raw[12]='4';
    $raw[16]=dechex((hexdec($raw[16])&3)|8);
    return substr($raw,0,8).'-'.substr($raw,8,4).'-'.substr($raw,12,4)
        .'-'.substr($raw,16,4).'-'.substr($raw,20,12);
}
function auth(array $env,string $key,string $domain,string $nonce,int $iat,int $exp): array
{
    $parts=[$domain];
    foreach ([
        'organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'
    ] as $field) {
        $parts[]=strtolower($env[$field]);
    }
    array_push($parts,(string)$iat,(string)$exp,$nonce);
    return [
        'issued_at'=>$iat,
        'expires_at'=>$exp,
        'nonce'=>$nonce,
        'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)
    ];
}
function resultCheck(array $outcome,string $reason,bool $committed=false): void
{
    check($outcome['reasonCode']===$reason,$reason.' got '.$outcome['reasonCode']);
    check($outcome['evidenceClass']==='CRM_TEST_ONLY_PG16_NOT_REAL_DELIVERY','evidence only');
    check($outcome['mockCommitted']===$committed,'mock commit evidence only');
    foreach ([
        'realCrmPersisted','tenantAuthenticatedForProduction',
        'canAcknowledgeVisitor','canAutoRetryUnknownOutcome','productionAuthorized'
    ] as $key) {
        check($outcome[$key]===false,'no real operation: '.$key);
    }
    check(isset($outcome['receipt'])===$committed,'no receipt without DB read-back');
    $encoded=json_encode($outcome,JSON_THROW_ON_ERROR);
    foreach ([
        '11111111-1111-4111-8111-111111111111',
        '33333333-3333-4333-8333-333333333333'
    ] as $id) {
        check(!str_contains($encoded,$id),'do not echo identity: '.$id);
    }
}
function num(PDO $db,string $table,string $submission): int
{
    check(in_array($table,['metadata_receipts','request_nonces'],true),'fixed tables');
    $stmt=$db->prepare('SELECT count(*) FROM crm_portal_handoff_ci.'.$table
      .' WHERE organization_reference=CAST(:org AS uuid) AND submission_reference=CAST(:sub AS uuid)');
    $stmt->execute([
        'org'=>'11111111-1111-4111-8111-111111111111',
        'sub'=>$submission
    ]);
    return (int)$stmt->fetchColumn();
}
function verifyReceipt(array $outcome,array $expected,string $key,int $now): string
{
    $r=$outcome['receipt']??[];
    check(is_array($r)&&count($r)===4,'receipt strictly metadata only');
    check(is_int($r['issued_at']??null)&&is_int($r['expires_at']??null)
        &&is_string($r['nonce']??null)&&is_string($r['mac']??null),'receipt types');
    $identity=new LeadSubmissionIdentity(
        $expected['organization_reference'],
        $expected['binding_reference'],
        $expected['submission_reference']
    );
    $claim=new SyntheticIdempotencyClaim(
        $identity,$expected['site_reference'],$expected['form_reference'],
        $expected['destination_reference']
    );
    $mac=(new SyntheticLeadReceiptMacInspector())->inspect(
        $claim,$claim,$r['issued_at'],$r['expires_at'],$now,
        $r['nonce'],$r['mac'],$key
    );
    check(!$mac->canAcknowledgeDurableReceipt(),'even matching CRM class cannot approve real ACK');
    check(!$mac->canDeliverToCrm(),'MAC not a CRM operation');
    return $mac->reasonCode;
}
function onlyRole(PDO $db,string $role,string $sql): array
{
    check(in_array($role,['crm_f340_receiver_a','crm_f340_receiver_b'],true),'role allowlist');
    $db->beginTransaction();
    try {
        $db->exec('SET LOCAL ROLE '.$role);
        $rows=$db->query($sql)->fetchAll(PDO::FETCH_NUM);
        $db->commit();
        return $rows;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}
$db=new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=imagine_crm_handoff_ci',
    'postgres',(string)getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_EMULATE_PREPARES=>false,
     PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);
check($db->query('SELECT current_database()')->fetchColumn()==='imagine_crm_handoff_ci',
    'exact disposable database');
check((int)$db->query("SELECT count(*) FROM pg_namespace WHERE nspname='crm_portal_handoff_ci'")->fetchColumn()===0,
    'refuse reusing a schema');
$db->exec((string)file_get_contents(__DIR__.'/crm_handoff_pg16.sql'));
$env=fixture();
$tenant=binding($env);
$requestKey=random_bytes(48);
$receiptKey=random_bytes(48);
$service=new Receiver($tenant,$requestKey,$receiptKey);
$now=time();
$requestDomain='imagine.portal.synthetic-service-request.v1';
$lookupDomain='imagine.portal.synthetic-crm-lookup.v1';
$firstNonce='88888888-8888-4888-8888-888888888888';
$firstAuth=auth($env,$requestKey,$requestDomain,$firstNonce,$now,$now+120);
check(num($db,'metadata_receipts',$env['submission_reference'])===0,'fresh metadata DB');

// Fail closed before any SQL writes.
$wrongKey=new Receiver($tenant,random_bytes(48),$receiptKey);
resultCheck($wrongKey->deliver($db,$env,$firstAuth,$now),'SIGNED_REQUEST_MAC_INVALID');
$result=$service->deliver($db,$env,
    auth($env,$requestKey,$lookupDomain,nonce(),$now,$now+120),$now);
resultCheck($result,'SIGNED_REQUEST_MAC_INVALID');
$result=$service->deliver($db,$env,auth($env,$requestKey,$requestDomain,
    nonce(),$now-400,$now-100),$now);
resultCheck($result,'SIGNED_REQUEST_WINDOW_INVALID');
$badVersion=$env;
$badVersion['schema_version']='1';
resultCheck($service->deliver($db,$badVersion,$firstAuth,$now),'ENVELOPE_REJECTED');
$extra=$env;
$extra['email']='synthetic@example.invalid';
resultCheck($service->deliver($db,$extra,$firstAuth,$now),'ENVELOPE_REJECTED');
$forge=$env;
$forge['organization_reference']='99999999-9999-4999-8999-999999999999';
resultCheck($service->deliver($db,$forge,
    auth($forge,$requestKey,$requestDomain,nonce(),$now,$now+120),$now),
    'PINNED_CONTEXT_CONFLICT');
check(num($db,'metadata_receipts',$env['submission_reference'])===0,
    'all bad inputs left mock CRM DB empty');

// A successful fixture writes an actual PG16 transaction, not a live CRM lead.
$first=$service->deliver($db,$env,$firstAuth,$now);
resultCheck($first,'LAB_MOCK_COMMITTED_FIRST',true);
check(verifyReceipt($first,$env,$receiptKey,$now)==='LAB_MAC_MATCH_UNTRUSTED',
    'existing real CRM class parses F121-style synthetic signed receipt');
check(verifyReceipt($first,$env,$requestKey,$now)==='MAC_MISMATCH',
    'distinct request key cannot verify receipt');
$changed=$env;
$changed['destination_reference']='77777777-7777-4777-8777-777777777777';
check(verifyReceipt($first,$changed,$receiptKey,$now)==='MAC_MISMATCH',
    'changed destination cannot validate original MAC');
$badReceipt=$first;
$badReceipt['receipt']['mac']=str_repeat('a',64);
check(verifyReceipt($badReceipt,$env,$receiptKey,$now)==='MAC_MISMATCH',
    'tampered receipt fails');
check(num($db,'metadata_receipts',$env['submission_reference'])===1,'one durable mock row');
check(num($db,'request_nonces',$env['submission_reference'])===1,'one request nonce');

// Same nonce is never a second delivery, even across another submission.
resultCheck($service->deliver($db,$env,$firstAuth,$now),'REQUEST_NONCE_REPLAY');
$second=fixture('77777777-7777-4777-8777-777777777777');
resultCheck($service->deliver($db,$second,
    auth($second,$requestKey,$requestDomain,$firstNonce,$now,$now+120),$now),
    'REQUEST_NONCE_REPLAY');
check(num($db,'metadata_receipts',$second['submission_reference'])===0,
    'replayed nonce cannot create another logical submission');
$secondNonce='99999999-9999-4999-8999-999999999999';
$replay=$service->deliver($db,$env,
    auth($env,$requestKey,$requestDomain,$secondNonce,$now,$now+120),$now);
resultCheck($replay,'LAB_MOCK_COMMITTED_LOGICAL_REPLAY',true);
check(num($db,'metadata_receipts',$env['submission_reference'])===1,
    'new nonce is not new lead');
check(num($db,'request_nonces',$env['submission_reference'])===2,
    'new nonce recorded as distinct attempt');

// An operator-supplied fixture cannot silently change the original destination.
$changedBinding=binding($changed);
$changedService=new Receiver($changedBinding,$requestKey,$receiptKey);
resultCheck($changedService->deliver($db,$changed,
    auth($changed,$requestKey,$requestDomain,nonce(),$now,$now+120),$now),
    'PINNED_REPLAY_CONFLICT');
check(num($db,'request_nonces',$env['submission_reference'])===2,
    'destination conflict rolls back attempted nonce');

// Simulate HTTP ACK loss AFTER PG commit: do not resend. A distinct
// domain-signed read-only lookup can attest ONLY to the mock's DB state.
$lost=fixture('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
$lostAuth=auth($lost,$requestKey,$requestDomain,nonce(),$now,$now+120);
$discarded=$service->deliver($db,$lost,$lostAuth,$now);
resultCheck($discarded,'LAB_MOCK_COMMITTED_FIRST',true);
unset($discarded); // client did not receive the lab receipt
check(num($db,'request_nonces',$lost['submission_reference'])===1,
    'unknown outcome has one outbound attempt, not retry');
resultCheck($service->lookup($db,$lost,$lostAuth,$now),'SIGNED_REQUEST_MAC_INVALID');
$lookupAuth=auth($lost,$requestKey,$lookupDomain,nonce(),$now,$now+120);
$readback=$service->lookup($db,$lost,$lookupAuth,$now);
resultCheck($readback,'LAB_MOCK_READBACK_SIGNED',true);
check(verifyReceipt($readback,$lost,$receiptKey,$now)==='LAB_MAC_MATCH_UNTRUSTED',
    'signed readback follows mock DB write only');
check(num($db,'request_nonces',$lost['submission_reference'])===1,
    'lookup is read-only and does not reserve another nonce');
$absent=fixture('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
resultCheck($service->lookup($db,$absent,
    auth($absent,$requestKey,$lookupDomain,nonce(),$now,$now+120),$now),
    'LAB_RECORD_NOT_VERIFIED');

// Role isolation and destructive privileges: test uses only temporary SQL roles.
check((int)onlyRole($db,'crm_f340_receiver_b',
    'SELECT count(*) FROM crm_portal_handoff_ci.metadata_receipts')[0][0]===0,
    'tenant B cannot read A receipts');
foreach ([
    'UPDATE crm_portal_handoff_ci.metadata_receipts SET destination_reference=destination_reference',
    'DELETE FROM crm_portal_handoff_ci.request_nonces',
    'TRUNCATE crm_portal_handoff_ci.metadata_receipts',
] as $sql) {
    try {
        onlyRole($db,'crm_f340_receiver_a',$sql);
        throw new RuntimeException('F3-40 FAIL: writable/read role escaped grants');
    } catch (PDOException) {
        ++$checks;
    }
}
echo "CRM F3-40 PASS: ".$checks." independent PG16 + actual CRM classes assertions; no real lead or CRM ACK.\n";
