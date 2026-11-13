<?php
declare(strict_types=1);

require_once __DIR__.'/SignedSyntheticReadback.php';
use Tests\Labs\F3_55\SignedSyntheticReadback;

if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F355_READBACK_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='f353_nonce_ci'
    || !extension_loaded('pdo_pgsql')) {
    throw new RuntimeException('F3-55 refuses non-disposable runner');
}
function conn(string $name,string $password):PDO {
    return new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f353_nonce_ci',
        $name,$password,[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        ]);
}
function same(array $f,array $b,string $k,int $now):array {
    $iat=$now;
    $exp=$now+60;
    $nonce='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    return [
      'issued_at'=>$iat,'expires_at'=>$exp,'nonce'=>$nonce,
      'mac'=>hash_hmac('sha256',SignedSyntheticReadback::message($f,$iat,$exp,$nonce),$k)
    ];
}
function verifyResult(array $r,string $reason,bool $found=false):void {
    global $checks;
    ++$checks;
    if(($r['reasonCode']??null)!==$reason
      ||($r['syntheticMetadataLocated']??null)!==$found
      ||($r['schema']??null)!=='imagine.crm.f355.synthetic-readback-result.v1') {
        throw new RuntimeException('F3-55 unexpected result: '.json_encode($r));
    }
    foreach(['realCrmLeadPersisted','tenantAuthenticatedForProduction','canAcknowledgeVisitor',
             'canAutoRetryUnknownOutcome','canDeploy'] as $flag) {
        if(($r[$flag]??null)!==false)throw new RuntimeException('Unsafe claim: '.$flag);
    }
}
function countTable(PDO $db,string $table):int {
    if(!in_array($table,['request_nonces','synthetic_delivery_receipts'],true))
        throw new RuntimeException('Unsafe table');
    return (int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.'.$table)->fetchColumn();
}
$checks=0;
$now=1763000000;
$orgA='11111111-1111-4111-8111-111111111111';
$orgB='99999999-9999-4999-8999-999999999999';
$bindingA=[
    'organization_reference'=>$orgA,
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$frameA=[
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>$orgA,
    'binding_reference'=>$bindingA['binding_reference'],
    'submission_reference'=>'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
    'site_reference'=>$bindingA['site_reference'],
    'form_reference'=>$bindingA['form_reference'],
    'destination_reference'=>$bindingA['destination_reference'],
];
$dbA=conn('f353_sender_a','f353-synthetic-a-only');
$dbB=conn('f353_sender_b','f353-synthetic-b-only');
$lookupKey=random_bytes(48);
$deliveryKey=random_bytes(48);
$reader=new SignedSyntheticReadback();
$auth=same($frameA,$bindingA,$lookupKey,$now);
verifyResult($reader->inspect($dbA,$frameA,$bindingA,$auth,$lookupKey,$now),'NOT_FOUND_HOLD_UNKNOWN');
if(countTable($dbA,'request_nonces')!==0||countTable($dbA,'synthetic_delivery_receipts')!==0) {
    throw new RuntimeException('Readback must not write');
}
// Simulate a durable CRM technical commit and a LOST HTTP acknowledgment.
$nonce='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$dbA->beginTransaction();
$insert=$dbA->prepare('INSERT INTO crm_f353_nonce_lab.request_nonces VALUES
 (CAST(:org AS uuid), CAST(:nonce AS uuid), :iat)');
$insert->execute(['org'=>$orgA,'nonce'=>$nonce,'iat'=>$now]);
$receipt=$dbA->prepare('INSERT INTO crm_f353_nonce_lab.synthetic_delivery_receipts
 VALUES(CAST(:org AS uuid), CAST(:sub AS uuid), CAST(:nonce AS uuid), :state)');
$receipt->execute([
    'org'=>$orgA,'sub'=>$frameA['submission_reference'],'nonce'=>$nonce,
    'state'=>'SYNTHETIC_COMMITTED_NO_REAL_LEAD'
]);
$dbA->commit();
// No sender-visible ACK: use separate read-only query (not another INSERT).
verifyResult($reader->inspect($dbA,$frameA,$bindingA,$auth,$lookupKey,$now),
  'SYNTHETIC_METADATA_FOUND_NO_REAL_LEAD',true);
verifyResult($reader->inspect($dbA,$frameA,$bindingA,$auth,$lookupKey,$now),
  'SYNTHETIC_METADATA_FOUND_NO_REAL_LEAD',true);
verifyResult($reader->inspect($dbA,$frameA,$bindingA,$auth,$deliveryKey,$now),
  'READBACK_AUTH_DENIED');
verifyResult($reader->inspect($dbA,$frameA,$bindingA,[...$auth,'mac'=>str_repeat('0',64)],$lookupKey,$now),
  'READBACK_AUTH_DENIED');
verifyResult($reader->inspect($dbA,$frameA,$bindingA,$auth,$lookupKey,$now+121),
  'READBACK_AUTH_DENIED');
verifyResult($reader->inspect($dbA,$frameA,$bindingA,[...$auth,'nonce'=>'incorrect'],$lookupKey,$now),
  'READBACK_AUTH_DENIED');
verifyResult($reader->inspect($dbA,[...$frameA,'email'=>'synthetic@example.invalid'],$bindingA,$auth,$lookupKey,$now),
  'CONTRACT_DENIED');
verifyResult($reader->inspect($dbA,$frameA,[...$bindingA,'organization_reference'=>$orgB],$auth,$lookupKey,$now),
  'SERVER_CONTEXT_DENIED');
verifyResult($reader->inspect($dbB,$frameA,$bindingA,$auth,$lookupKey,$now),
  'NOT_FOUND_HOLD_UNKNOWN');
$absent=[...$frameA,'submission_reference'=>'ffffffff-ffff-4fff-8fff-ffffffffffff'];
verifyResult($reader->inspect($dbA,$absent,$bindingA,same($absent,$bindingA,$lookupKey,$now),$lookupKey,$now),
  'NOT_FOUND_HOLD_UNKNOWN');
if(countTable($dbA,'request_nonces')!==1||countTable($dbA,'synthetic_delivery_receipts')!==1
   ||countTable($dbB,'request_nonces')!==0||countTable($dbB,'synthetic_delivery_receipts')!==0) {
   throw new RuntimeException('Readback mutated tables or leaked RLS rows');
}
echo "F3-55 PASS: $checks signed readback/negative assertions; no lead or visitor acknowledgment.\n";
