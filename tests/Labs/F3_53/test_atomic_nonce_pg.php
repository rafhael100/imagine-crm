<?php
declare(strict_types=1);
/**
 * F3-53 disposable PostgreSQL nonce replay test.
 * Isolated synthetic DB, fixed CI-only roles, no lead table or operational route.
 */
if (getenv('GITHUB_ACTIONS')!=='true'
 || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
 || getenv('CRM_F353_NONCE_PG_LAB')!=='1'
 || getenv('DB_HOST')!=='127.0.0.1'
 || getenv('DB_DATABASE')!=='f353_nonce_ci'
 || !extension_loaded('pdo_pgsql')) {
    throw new RuntimeException('F3-53 must execute only in disposable PostgreSQL CI');
}
function db(string $role, string $password): PDO {
    return new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f353_nonce_ci',
      $role,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function claim(PDO $db, string $org, string $nonce): bool {
    $sql='INSERT INTO crm_f353_nonce_lab.request_nonces (organization_reference,request_nonce,issued_at)
      VALUES (CAST(:org AS uuid), CAST(:nonce AS uuid), 1763000000)
      ON CONFLICT (organization_reference, request_nonce) DO NOTHING
      RETURNING request_nonce';
    $stmt=$db->prepare($sql);
    $stmt->execute(['org'=>$org,'nonce'=>$nonce]);
    return $stmt->fetchColumn()!==false;
}
function readCount(PDO $db): int {
    return (int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.request_nonces')->fetchColumn();
}
function check(bool $condition, string $description): void {
    global $count;
    $count++;
    if (!$condition) throw new RuntimeException('F3-53 FAIL: '.$description);
}
$count=0;
$a='11111111-1111-4111-8111-111111111111';
$b='99999999-9999-4999-8999-999999999999';
$nonce='77777777-7777-4777-8777-777777777777';
$senderA=db('f353_sender_a','f353-synthetic-a-only');
$senderB=db('f353_sender_b','f353-synthetic-b-only');
check($senderA->query('SELECT current_user')->fetchColumn()==='f353_sender_a','non-superuser role A');
check($senderB->query('SELECT current_user')->fetchColumn()==='f353_sender_b','non-superuser role B');
check(claim($senderA,$a,$nonce),'first nonce claim commits');
check(!claim($senderA,$a,$nonce),'replay cannot claim same unique nonce');
check(readCount($senderA)===1,'tenant A has one durable row');
check(readCount($senderB)===0,'tenant B cannot read tenant A row');
try {claim($senderA,$b,'88888888-8888-4888-8888-888888888888');throw new RuntimeException('forged tenant accepted');}
catch (PDOException $e) {check($e->getCode()==='42501','RLS denies forged tenant claim');}
check(claim($senderB,$b,$nonce),'different tenant can claim same nonce independently');
check(readCount($senderA)===1 && readCount($senderB)===1,'separate RLS scopes');
try {$senderA->exec('DELETE FROM crm_f353_nonce_lab.request_nonces');throw new RuntimeException('delete was allowed');}
catch (PDOException $e) {check($e->getCode()==='42501','app role cannot delete nonce ledger');}
try {$senderA->exec('UPDATE crm_f353_nonce_lab.request_nonces SET issued_at=0');throw new RuntimeException('update was allowed');}
catch (PDOException $e) {check($e->getCode()==='42501','app role cannot rewrite nonce ledger');}
check(!claim($senderB,$b,$nonce),'independent tenant replay rejected');
check(readCount($senderA)===1 && readCount($senderB)===1,'ledger stable after replay and deny');
echo "CRM F3-53 PASS: $count PostgreSQL replay/RLS/privilege checks. NO-GO for production.\n";
