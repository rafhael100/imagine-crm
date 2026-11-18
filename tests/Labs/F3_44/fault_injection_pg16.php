<?php

declare(strict_types=1);

/**
 * F3-44 — real PostgreSQL 16 fault injection against F3-40's ACTUAL
 * synthetic CRM receiver, with no Laravel route, client data or external DB.
 *
 * The test-only database is intentionally destroyed after the CI job.
 * NO grant, migration, actual CRM lead, message, secret, endpoint or ACK.
 */

require_once __DIR__.'/../F3_40/SyntheticPortalHandoffReceiver.php';

use Tests\Labs\F3_40\SyntheticPortalHandoffReceiver as Receiver;

if (getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('RUNNER_ENVIRONMENT') !== 'github-hosted'
    || getenv('CRM_F340_LAB') !== '1'
    || getenv('CRM_F344_FAULT_LAB') !== '1'
    || getenv('DB_HOST') !== '127.0.0.1'
    || getenv('DB_DATABASE') !== 'imagine_crm_handoff_ci'
    || getenv('DB_PASSWORD') !== 'f344-fixture-only-not-a-real-secret'
    || !extension_loaded('pdo_pgsql')) {
    fwrite(STDERR, "F3-44 refuses any non-disposable GitHub-hosted PostgreSQL database.\n");
    exit(2);
}

$checks=0;
function check(bool $condition,string $description): void
{
    global $checks;
    ++$checks;
    if (!$condition) {
        throw new RuntimeException('F3-44 FAIL: '.$description);
    }
}
function envelope(string $submission): array
{
    return [
        'protocol'=>'imagine.lead-intake','schema_version'=>1,
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'submission_reference'=>$submission,
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666',
    ];
}
function binding(array $envelope): array
{
    $server=[
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666',
    ];
    return $server; // Never derive trusted binding from input!
}
function sign(array $envelope,string $key,string $nonce,int $iat,int $exp,string $domain): array
{
    $parts=[$domain];
    foreach ([
        'organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'
    ] as $name) {
        $parts[]=strtolower($envelope[$name]);
    }
    array_push($parts,(string)$iat,(string)$exp,$nonce);
    return [
        'issued_at'=>$iat,'expires_at'=>$exp,'nonce'=>$nonce,
        'mac'=>hash_hmac('sha256',implode("\n",$parts),$key),
    ];
}
function countRecords(PDO $db,string $table,string $submission): int
{
    check(in_array($table,['metadata_receipts','request_nonces'],true),'fixed table');
    check(preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D',$submission)===1,
        'fixed UUID format');
    $stmt=$db->prepare('SELECT count(*) FROM crm_portal_handoff_ci.'.$table.
        ' WHERE organization_reference=CAST(:org AS uuid) AND submission_reference=CAST(:sub AS uuid)');
    $stmt->execute([
        'org'=>'11111111-1111-4111-8111-111111111111',
        'sub'=>$submission,
    ]);
    return (int)$stmt->fetchColumn();
}
function expectDenial(array $response,string $reason): void
{
    check($response['reasonCode']===$reason,'denial reason '.$reason);
    check(!array_key_exists('receipt',$response),'no receipt on failure');
    check($response['mockCommitted']===false,'no fictional commit on failure');
    foreach ([
        'realCrmPersisted','tenantAuthenticatedForProduction',
        'canAcknowledgeVisitor','canAutoRetryUnknownOutcome','productionAuthorized'
    ] as $key)check($response[$key]===false,'no operational authorization '.$key);
}

$db=new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=imagine_crm_handoff_ci',
    'postgres',(string)getenv('DB_PASSWORD'),
    [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]
);
check($db->query('SELECT current_database()')->fetchColumn()==='imagine_crm_handoff_ci',
    'database identity exact');
check((int)$db->query('SHOW server_version_num')->fetchColumn()>=160000,
    'PostgreSQL 16 or newer');
check((int)$db->query("SELECT count(*) FROM pg_namespace WHERE nspname='crm_portal_handoff_ci'")->fetchColumn()===1,
    'F3-40 fixture schema already created');
check((int)$db->query("SELECT count(*) FROM information_schema.triggers WHERE trigger_schema='crm_portal_handoff_ci'")->fetchColumn()===0,
    'no trigger from a previous run');

$raise='e4e4e4e4-e4e4-4e4e-8e4e-e4e4e4e4e4e4';
$timeout='e5e5e5e5-e5e5-4e5e-8e5e-e5e5e5e5e5e5';
$conflict='e6e6e6e6-e6e6-4e6e-8e6e-e6e6e6e6e6e6';

$db->exec(<<<'SQL'
CREATE FUNCTION crm_portal_handoff_ci.f344_inject_failure() RETURNS trigger
LANGUAGE plpgsql AS $body$
BEGIN
  IF NEW.submission_reference='e4e4e4e4-e4e4-4e4e-8e4e-e4e4e4e4e4e4'::uuid THEN
    RAISE EXCEPTION 'F344_INJECTED_FAILURE_ONLY' USING ERRCODE='P0001';
  ELSIF NEW.submission_reference='e5e5e5e5-e5e5-4e5e-8e5e-e5e5e5e5e5e5'::uuid THEN
    PERFORM pg_sleep(0.45);
  END IF;
  RETURN NEW;
END;
$body$;
CREATE TRIGGER f344_before_metadata_receipt
BEFORE INSERT ON crm_portal_handoff_ci.metadata_receipts
FOR EACH ROW EXECUTE FUNCTION crm_portal_handoff_ci.f344_inject_failure();
SQL);

$requestKey=random_bytes(48);
$receiptKey=random_bytes(48);
check(!hash_equals($requestKey,$receiptKey),'separate test-only request and receipt keys');
$receiver=new Receiver(binding([]),$requestKey,$receiptKey);
$iat=time();
$exp=$iat+120;
$requestDomain='imagine.portal.synthetic-service-request.v1';
$lookupDomain='imagine.portal.synthetic-crm-lookup.v1';

$first=envelope($raise);
$nonce1='d1d1d1d1-d1d1-4d1d-8d1d-d1d1d1d1d1d1';
$auth1=sign($first,$requestKey,$nonce1,$iat,$exp,$requestDomain);
expectDenial($receiver->deliver($db,$first,$auth1,$iat),'LAB_DATABASE_UNVERIFIED');
check(!$db->inTransaction(),'raised trigger fully rolled back');
check(countRecords($db,'request_nonces',$raise)===0,'injected exception left no orphan nonce');
check(countRecords($db,'metadata_receipts',$raise)===0,'injected exception left no receipt row');
$lookup1=sign($first,$requestKey,'d2d2d2d2-d2d2-4d2d-8d2d-d2d2d2d2d2d2',
    $iat,$exp,$lookupDomain);
expectDenial($receiver->lookup($db,$first,$lookup1,$iat),'LAB_RECORD_NOT_VERIFIED');

// Real PostgreSQL statement_timeout occurs during the RECEIVER transaction,
// after the nonce CTE has been attempted but before the metadata insert.
// Nothing may commit; the caller must not infer whether a remote HTTP write
// succeeded simply from this local timeout laboratory.
$second=envelope($timeout);
$auth2=sign($second,$requestKey,'d3d3d3d3-d3d3-4d3d-8d3d-d3d3d3d3d3d3',
    $iat,$exp,$requestDomain);
$db->exec("SET statement_timeout = '110ms'");
try {
    expectDenial($receiver->deliver($db,$second,$auth2,$iat),'LAB_DATABASE_UNVERIFIED');
} finally {
    $db->exec('RESET statement_timeout');
}
check(!$db->inTransaction(),'cancelled statement does not leave open PDO transaction');
check(countRecords($db,'request_nonces',$timeout)===0,
    'statement timeout rolled back nonce insert');
check(countRecords($db,'metadata_receipts',$timeout)===0,
    'statement timeout rolled back receipt insert');
$lookup2=sign($second,$requestKey,'d4d4d4d4-d4d4-4d4d-8d4d-d4d4d4d4d4d4',
    $iat,$exp,$lookupDomain);
expectDenial($receiver->lookup($db,$second,$lookup2,$iat),'LAB_RECORD_NOT_VERIFIED');

// Do NOT resume or automatically retry after an uncertain failure in a
// real system. Here we first checked the standalone mock DB by readback.
// Explicitly drop fault fixture and rerun in this disposable test only.
$db->exec('DROP TRIGGER f344_before_metadata_receipt ON crm_portal_handoff_ci.metadata_receipts');
$db->exec('DROP FUNCTION crm_portal_handoff_ci.f344_inject_failure()');
$good=$receiver->deliver($db,$first,$auth1,$iat);
check($good['reasonCode']==='LAB_MOCK_COMMITTED_FIRST',
    'manual test-only retry allowed only after verified absence and disabled fault');
check(isset($good['receipt'])&&count($good['receipt'])===4,
    'mock receipt is returned only after actual PG commit');
check($good['realCrmPersisted']===false && $good['canAcknowledgeVisitor']===false,
    'receipt is not proof of CRM lead delivery');
check(countRecords($db,'request_nonces',$raise)===1,
    'one nonce inserted after successful atomic commit');
check(countRecords($db,'metadata_receipts',$raise)===1,
    'one metadata row after commit');
expectDenial($receiver->deliver($db,$first,$auth1,$iat),'REQUEST_NONCE_REPLAY');
check(countRecords($db,'request_nonces',$raise)===1,'replayed nonce never duplicated');

$success2=$receiver->deliver($db,$second,$auth2,$iat);
check($success2['reasonCode']==='LAB_MOCK_COMMITTED_FIRST',
    'cancelled statement was atomic and may be manually retried after verification');
check(countRecords($db,'request_nonces',$timeout)===1,'cancelled nonce recovered once');
check(countRecords($db,'metadata_receipts',$timeout)===1,'cancelled row recovered once');

// Different server-bound destination for the SAME logical submission must
// not silently modify the first payload or consume a new nonce.
$original=envelope($conflict);
$authOriginal=sign($original,$requestKey,'d5d5d5d5-d5d5-4d5d-8d5d-d5d5d5d5d5d5',
    $iat,$exp,$requestDomain);
$stored=$receiver->deliver($db,$original,$authOriginal,$iat);
check($stored['reasonCode']==='LAB_MOCK_COMMITTED_FIRST','original pinned context');
$other=$original;
$other['destination_reference']='77777777-7777-4777-8777-777777777777';
$otherServerBinding=binding([]);
$otherServerBinding['destination_reference']=$other['destination_reference'];
$otherReceiver=new Receiver($otherServerBinding,$requestKey,$receiptKey);
$authDifferent=sign($other,$requestKey,'d6d6d6d6-d6d6-4d6d-8d6d-d6d6d6d6d6d6',
    $iat,$exp,$requestDomain);
expectDenial($otherReceiver->deliver($db,$other,$authDifferent,$iat),
    'PINNED_REPLAY_CONFLICT');
check(countRecords($db,'request_nonces',$conflict)===1,
    'destination conflict rolled back new nonce');
check(countRecords($db,'metadata_receipts',$conflict)===1,
    'destination conflict preserved original metadata');

// Even a correctly signed technical request MUST NOT elevate a runtime
// deployment or produce a PII payload; no such functions exist in this lab.
putenv('DB_DATABASE=forged_environment');
try {
    $another=envelope('e7e7e7e7-e7e7-4e7e-8e7e-e7e7e7e7e7e7');
    $authAnother=sign($another,$requestKey,'d7d7d7d7-d7d7-4d7d-8d7d-d7d7d7d7d7d7',
        $iat,$exp,$requestDomain);
    expectDenial($receiver->deliver($db,$another,$authAnother,$iat),'DISPOSABLE_CI_REQUIRED');
} finally {
    putenv('DB_DATABASE=imagine_crm_handoff_ci');
}
check(countRecords($db,'metadata_receipts','e7e7e7e7-e7e7-4e7e-8e7e-e7e7e7e7e7e7')===0,
    'forged environment did not persist synthetic metadata');
echo "CRM F3-44 PASS: {$checks} real PG16 fault-injection, timeout, rollback and signed readback checks, no operational CRM ACK.\n";
