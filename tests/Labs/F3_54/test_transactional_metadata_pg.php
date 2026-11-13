<?php
declare(strict_types=1);
// CI-only PG transaction proof, never inserts lead or visitor PII.
if(getenv('GITHUB_ACTIONS')!=='true'
 ||getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
 ||getenv('CRM_F354_PG_LAB')!=='1'
 ||getenv('DB_HOST')!=='127.0.0.1'
 ||getenv('DB_DATABASE')!=='f353_nonce_ci'
 ||!extension_loaded('pdo_pgsql')) throw new RuntimeException('F3-54 requires disposable CI');
$db=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f353_nonce_ci',
 'f353_sender_a','f353-synthetic-a-only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
$org='11111111-1111-4111-8111-111111111111';
$nonce='77777777-7777-4777-8777-777777777777';
$sub='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$checks=0;
function check(bool $ok,string $why):void{global $checks;$checks++;if(!$ok)throw new RuntimeException('F3-54 FAIL: '.$why);}
function n(PDO $db,string $table):int{
 $allow=['request_nonces','synthetic_delivery_receipts'];
 if(!in_array($table,$allow,true))throw new RuntimeException('not allowed');
 return (int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.'.$table)->fetchColumn();
}
function nonce(PDO $db,string $org,string $nonce):bool{
 $stmt=$db->prepare('INSERT INTO crm_f353_nonce_lab.request_nonces VALUES (CAST(:org AS uuid), CAST(:nonce AS uuid), 1763000000) ON CONFLICT DO NOTHING RETURNING request_nonce');
 $stmt->execute(['org'=>$org,'nonce'=>$nonce]);
 return $stmt->fetchColumn()!==false;
}
function receipt(PDO $db,string $org,string $sub,string $nonce,string $state):void {
 $stmt=$db->prepare('INSERT INTO crm_f353_nonce_lab.synthetic_delivery_receipts VALUES (CAST(:org AS uuid),CAST(:sub AS uuid),CAST(:nonce AS uuid),:state)');
 $stmt->execute(['org'=>$org,'sub'=>$sub,'nonce'=>$nonce,'state'=>$state]);
}
check(n($db,'request_nonces')===0&&n($db,'synthetic_delivery_receipts')===0,'empty independent stores');
$db->beginTransaction();
check(nonce($db,$org,$nonce),'synthetic first request claims nonce');
try {
 receipt($db,$org,$sub,$nonce,'INVALID_STATE_CAUSES_TRANSACTION_ROLLBACK');
 throw new RuntimeException('broken constraint accepted');
} catch(PDOException $e) {
 check($e->getCode()==='23514','CHECK constraint rejects malformed delivery');
 $db->rollBack();
}
check(n($db,'request_nonces')===0&&n($db,'synthetic_delivery_receipts')===0,'rollback removes BOTH nonce and receipt');
$db->beginTransaction();
check(nonce($db,$org,$nonce),'nonce claim available again after rollback');
receipt($db,$org,$sub,$nonce,'SYNTHETIC_COMMITTED_NO_REAL_LEAD');
$db->commit();
check(n($db,'request_nonces')===1&&n($db,'synthetic_delivery_receipts')===1,'both synthetic rows committed atomically');
$db->beginTransaction();
check(!nonce($db,$org,$nonce),'replayed nonce cannot claim delivery again');
$db->rollBack();
check(n($db,'request_nonces')===1&&n($db,'synthetic_delivery_receipts')===1,'replay leaves committed state unchanged');
$second='88888888-8888-4888-8888-888888888888';
$db->beginTransaction();
check(nonce($db,$org,$second),'new nonce claim possible');
try {
 receipt($db,$org,$sub,$second,'SYNTHETIC_COMMITTED_NO_REAL_LEAD');
 throw new RuntimeException('duplicate logical submission accepted');
} catch(PDOException $e) {
 check($e->getCode()==='23505','unique logical submission prevents second synthetic record');
 $db->rollBack();
}
check(n($db,'request_nonces')===1&&n($db,'synthetic_delivery_receipts')===1,'failed second submission does not leave orphan nonce');
echo "CRM F3-54 PASS: $checks atomic commit/rollback/duplicate checks; synthetic metadata only.\n";
