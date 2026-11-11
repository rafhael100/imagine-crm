<?php

declare(strict_types=1);

/**
 * F3-42 actual code from TWO public GitHub repositories in one disposable
 * integration run. Portal PHP F119/F122 -> CRM PHP F3-41 over HTTP loopback.
 * Two isolated PostgreSQL containers (Portal PG18 :5433, CRM PG16 :5432).
 *
 * Absolutely NO user data, shared production identity, operational service,
 * migration, real CRM receipt or visitor ACK. CI-only demonstration.
 */
$root=dirname(__DIR__,3);
$portalFile=$root.'/portal-source/apps/portal/labs/commercial-intake-f119/PortalF119SyntheticServiceReservation.php';
if (!is_file($portalFile)) {
    throw new RuntimeException('F3-42 requires pinned real Portal checkout, not a copied mock');
}
require_once $portalFile;
require_once $root.'/app/Core/Leads/LeadSubmissionIdentity.php';
require_once $root.'/app/Core/Leads/SyntheticIdempotencyClaim.php';
require_once $root.'/app/Core/Leads/SyntheticLeadReceiptMacAssessment.php';
require_once $root.'/app/Core/Leads/SyntheticLeadReceiptMacInspector.php';

use Imagine\Portal\Labs\CommercialIntakeF119\PortalF119SyntheticServiceReservation as PortalStage;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadReceiptMacInspector;

if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('PORTAL_F119_PG_LAB')!=='1'
    || getenv('CRM_F342_CROSS_REPO_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='portal_commercial_lab'
    || !extension_loaded('pdo_pgsql')) {
    throw new RuntimeException('F3-42 refuses non-disposable two-store GitHub CI');
}
$requestHex=getenv('CRM_F341_REQUEST_KEY_HEX');
$receiptHex=getenv('CRM_F341_RECEIPT_KEY_HEX');
if (!is_string($requestHex)||!is_string($receiptHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$requestHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$receiptHex)
    || hash_equals($requestHex,$receiptHex)) {
    throw new RuntimeException('F3-42 requires two distinct generated-only CI keys');
}
$requestKey=hex2bin($requestHex);
$receiptKey=hex2bin($receiptHex);
if (!is_string($requestKey)||!is_string($receiptKey)) {
    throw new RuntimeException('F3-42 invalid ephemeral keys');
}

$checks=0;
function check(bool $true,string $message): void
{
    global $checks;
    ++$checks;
    if (!$true) {
        throw new RuntimeException('F3-42 FAIL: '.$message);
    }
}
function pg(string $name,int $port): PDO
{
    check(
        ($name==='portal_commercial_lab' && $port===5433)
        || ($name==='imagine_crm_handoff_ci' && $port===5432),
        'fixed mapping of two independent database containers'
    );
    $pdo=new PDO(
        'pgsql:host=127.0.0.1;port='.$port.';dbname='.$name,
        'postgres',(string)getenv('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_EMULATE_PREPARES=>false,
         PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    check($pdo->query('SELECT current_database()')->fetchColumn()===$name,
        'database identity checked independently');
    check((int)$pdo->query('SHOW server_version_num')->fetchColumn()
        >=($port===5433?180000:160000),'real PostgreSQL version expected');
    return $pdo;
}
function envelope(string $submission): array
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
function binding(array $e): array
{
    // CRITICAL: the authoritatively pinned TEST tenant must NOT be copied
    // from a potentially forged envelope. Derive it from a separate fixture.
    // A real service would obtain these values from authenticated server DB.
    return [
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666',
    ];
}
function uuid(): string
{
    $raw=bin2hex(random_bytes(16));
    $raw[12]='4';
    $raw[16]=dechex((hexdec($raw[16])&3)|8);
    return substr($raw,0,8).'-'.substr($raw,8,4).'-'.substr($raw,12,4)
        .'-'.substr($raw,16,4).'-'.substr($raw,20,12);
}
function signed(array $e,string $key,string $domain,string $nonce,int $iat,int $exp): array
{
    $parts=[$domain];
    foreach ([
        'organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'
    ] as $field) {
        $parts[]=strtolower($e[$field]);
    }
    array_push($parts,(string)$iat,(string)$exp,$nonce);
    return [
        'issued_at'=>$iat,'expires_at'=>$exp,'nonce'=>$nonce,
        'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)
    ];
}
function stagePortal(PDO $db,array $e,string $key): string
{
    $now=time();
    $auth=signed($e,$key,PortalStage::DOMAIN,uuid(),$now,$now+120);
    $stage=new PortalStage($key);
    $result=$stage->observe($db,$e,binding($e),$auth);
    check($result['crmPersisted']===false,'actual Portal class never claims real CRM');
    check($result['canAcknowledgeVisitor']===false,'no visitor acknowledgment');
    return $result['reasonCode'];
}
function countDb(PDO $db,string $schema,string $table,string $submission): int
{
    $valid=(
        $schema==='portal_commercial_ci' && in_array($table,[
            'metadata_ledger','pending_outbox','service_request_nonce','delivery_gate'
        ],true)
    ) || (
        $schema==='crm_portal_handoff_ci' && in_array($table,[
            'metadata_receipts','request_nonces'
        ],true)
    );
    check($valid,'only allowlisted synthetic tables from two schemas');
    $statement=$db->prepare(
        'SELECT count(*) FROM '.$schema.'.'.$table.
        ' WHERE organization_reference=CAST(:org AS uuid) AND submission_reference=CAST(:sub AS uuid)'
    );
    $statement->execute([
        'org'=>'11111111-1111-4111-8111-111111111111',
        'sub'=>$submission
    ]);
    return (int)$statement->fetchColumn();
}
function httpToCrm(array $e,array $auth,string $route='/__lab/crm/ingest',bool $drop=false): array
{
    $headers=['Content-Type: application/json','Connection: close'];
    $map=[
        'issued_at'=>'X-Lab-Issued-At',
        'expires_at'=>'X-Lab-Expires-At',
        'nonce'=>'X-Lab-Nonce',
        'mac'=>'X-Lab-Signature'
    ];
    foreach ($map as $field=>$header) {
        check(isset($auth[$field]),'outbound auth complete '.$field);
        $headers[]=$header.': '.$auth[$field];
    }
    if ($drop) {
        $headers[]='X-Lab-Drop-Receipt: 1';
    }
    $context=stream_context_create([
        'http'=>[
            'method'=>'POST','header'=>implode("\r\n",$headers),
            'content'=>json_encode($e,JSON_THROW_ON_ERROR),
            'ignore_errors'=>true,'timeout'=>8,'follow_location'=>0
        ]
    ]);
    $raw=@file_get_contents('http://127.0.0.1:19141'.$route,false,$context);
    $responseHeaders=$http_response_header??[];
    check(is_string($raw)&&isset($responseHeaders[0]),'real HTTP CRM lab response');
    check(preg_match('/\AHTTP\/[0-9.]+\s+([0-9]{3})\b/',$responseHeaders[0],$match)===1,
        'real HTTP status');
    $body=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
    check(is_array($body)&&$body['schema']==='imagine.crm.synthetic-portal-http.v1',
        'actual CRM F3-41 router not another fake');
    check($body['mode']==='CI_ONLY_REAL_PG16_NOT_REAL_CRM_RECEIPT','lab provenance');
    foreach ([
        'realCrmPersisted','tenantAuthenticatedForProduction',
        'canAcknowledgeVisitor','canAutoRetryUnknownOutcome',
        'productionAuthorized'
    ] as $flag) {
        check($body[$flag]===false,'no operational authority '.$flag);
    }
    check(!str_contains(json_encode($body,JSON_THROW_ON_ERROR),$e['submission_reference']),
        'HTTP response does not echo logical identity');
    return [(int)$match[1],$body];
}
function receiptCode(array $response,array $e,string $key,int $now): string
{
    $r=$response['receipt']??null;
    check(is_array($r)&&count($r)===4,'mock receipt exactly four fields');
    $claim=new SyntheticIdempotencyClaim(
        new LeadSubmissionIdentity(
            $e['organization_reference'],$e['binding_reference'],$e['submission_reference']
        ),
        $e['site_reference'],$e['form_reference'],$e['destination_reference']
    );
    $result=(new SyntheticLeadReceiptMacInspector())->inspect(
        $claim,$claim,$r['issued_at'],$r['expires_at'],$now,
        $r['nonce'],$r['mac'],$key
    );
    check(!$result->canAcknowledgeDurableReceipt(),'real CRM class never issues an operational ACK');
    return $result->reasonCode;
}
function gate(PDO $db,string $function,string $submission,int $version): string|null
{
    check(in_array($function,['claim_lab_a','note_unknown_lab_a'],true),
        'use only fixed Portal F122 funcs');
    $db->beginTransaction();
    try {
        $db->exec('SET LOCAL ROLE portal_f122_worker_a');
        $s=$db->prepare(
            'SELECT portal_commercial_ci.'.$function.
            '(CAST(:sub AS uuid),CAST(:v AS bigint))'
        );
        $s->execute(['sub'=>$submission,'v'=>$version]);
        $value=$s->fetchColumn();
        $db->commit();
        if (is_bool($value)) {
            return $value?'t':'f';
        }
        return $value===null?null:(string)$value;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

$portal=pg('portal_commercial_lab',5433);
$crm=pg('imagine_crm_handoff_ci',5432);
check((int)$portal->query(
    "SELECT count(*) FROM information_schema.tables WHERE table_schema='crm_portal_handoff_ci'"
)->fetchColumn()===0,'CRM test schema does not exist on Portal PG18');
check((int)$crm->query(
    "SELECT count(*) FROM information_schema.tables WHERE table_schema='portal_commercial_ci'"
)->fetchColumn()===0,'Portal test schema does not exist on CRM PG16');

$portalKey=random_bytes(48); // NOT shared with CRM lab transport key.
$a=envelope('d4d4d4d4-d4d4-4d4d-8d4d-d4d4d4d4d4d4');
check(stagePortal($portal,$a,$portalKey)==='LAB_METADATA_AND_OUTBOX_WRITTEN',
    'REAL Portal F119 PHP staged record and event on independent PG18');
check(countDb($portal,'portal_commercial_ci','metadata_ledger',$a['submission_reference'])===1,
    'Portal ledger committed');
check(countDb($portal,'portal_commercial_ci','pending_outbox',$a['submission_reference'])===1,
    'Portal pending outbox committed');
check(countDb($crm,'crm_portal_handoff_ci','metadata_receipts',$a['submission_reference'])===0,
    'CRM PG16 starts independent and empty');

$iat=time();
$send=signed($a,$GLOBALS['requestKey'],'imagine.portal.synthetic-service-request.v1',
    uuid(),$iat,$iat+120);
$reply=httpToCrm($a,$send);
check($reply[0]===202&&$reply[1]['reasonCode']==='LAB_MOCK_COMMITTED_FIRST',
    'real CRM F3-41 HTTP committed synthetic metadata into its own PG16');
check(receiptCode($reply[1],$a,$GLOBALS['receiptKey'],$iat)==='LAB_MAC_MATCH_UNTRUSTED',
    'existing actual CRM receipt inspector agrees with Portal domain contract');
check(receiptCode($reply[1],$a,$GLOBALS['requestKey'],$iat)==='MAC_MISMATCH',
    'request signature must not be mistaken for receiver receipt');
check(countDb($crm,'crm_portal_handoff_ci','metadata_receipts',$a['submission_reference'])===1,
    'actual CRM PHP class committed own PG16 metadata');
check(countDb($crm,'crm_portal_handoff_ci','request_nonces',$a['submission_reference'])===1,
    'CRM request nonce committed independently');
check(countDb($portal,'portal_commercial_ci','service_request_nonce',$a['submission_reference'])===1,
    'Portal has a separate nonce ledger');
$repeat=httpToCrm($a,$send);
check($repeat[0]===409&&$repeat[1]['reasonCode']==='REQUEST_NONCE_REPLAY',
    'replay rejected by CRM independent PG16');
check(stagePortal($portal,$a,$portalKey)==='LAB_EXISTING_SUBMISSION_NOT_DELIVERED',
    'retry with a new Portal nonce is not a new lead');
check(countDb($portal,'portal_commercial_ci','metadata_ledger',$a['submission_reference'])===1,
    'Portal retry left one logical submission');

// Lost reply after CRM commits. Source must hold uncertainty until readback,
// never automatically retry the delivery.
$b=envelope('d5d5d5d5-d5d5-4d5d-8d5d-d5d5d5d5d5d5');
check(stagePortal($portal,$b,$portalKey)==='LAB_METADATA_AND_OUTBOX_WRITTEN',
    'Portal F119 staged second pending request');
check(gate($portal,'claim_lab_a',$b['submission_reference'],0)==='1',
    'Portal F122 lease acquired by one synthetic worker');
$lostAuth=signed($b,$GLOBALS['requestKey'],
    'imagine.portal.synthetic-service-request.v1',uuid(),$iat,$iat+120);
$lost=httpToCrm($b,$lostAuth,'/__lab/crm/ingest',true);
check($lost[0]===503 && $lost[1]['reasonCode']==='UNKNOWN_OUTCOME_NO_RECEIPT'
    && !isset($lost[1]['receipt']),'no fabricated receipt after simulated lost ACK');
check(countDb($crm,'crm_portal_handoff_ci','metadata_receipts',$b['submission_reference'])===1,
    'CRM PG16 committed record despite dropped HTTP response');
check(gate($portal,'note_unknown_lab_a',$b['submission_reference'],1)==='t',
    'Portal F122 durable unknown hold after lost HTTP reply');
check(gate($portal,'claim_lab_a',$b['submission_reference'],1)===null,
    'unknown state blocks blind retry');
$lookupAuth=signed($b,$GLOBALS['requestKey'],
    'imagine.portal.synthetic-crm-lookup.v1',uuid(),$iat,$iat+120);
$lookup=httpToCrm($b,$lookupAuth,'/__lab/crm/readback');
check($lookup[0]===200&&$lookup[1]['reasonCode']==='LAB_MOCK_READBACK_SIGNED',
    'distinct-domain signed readback from CRM PHP');
check(receiptCode($lookup[1],$b,$GLOBALS['receiptKey'],$iat)==='LAB_MAC_MATCH_UNTRUSTED',
    'read-back matched pinned synthetic context');
check(countDb($crm,'crm_portal_handoff_ci','request_nonces',$b['submission_reference'])===1,
    'readback did not create new CRM attempt');
check(gate($portal,'claim_lab_a',$b['submission_reference'],1)===null,
    'mock readback alone never clears Portal unknown hold');
check(countDb($portal,'portal_commercial_ci','delivery_gate',$b['submission_reference'])===1,
    'Portal persists unknown gate after CRM mock readback');
$forged=$b;
$forged['destination_reference']='77777777-7777-4777-8777-777777777777';
check(stagePortal($portal,$forged,$portalKey)==='PINNED_METADATA_REJECTED',
    'real Portal F119 refuses changed server binding before HTTP');
$otherOrg=$b;
$otherOrg['organization_reference']='99999999-9999-4999-8999-999999999999';
$otherAuth=signed($otherOrg,$GLOBALS['requestKey'],
    'imagine.portal.synthetic-service-request.v1',uuid(),$iat,$iat+120);
$denied=httpToCrm($otherOrg,$otherAuth);
check($denied[0]===403&&$denied[1]['reasonCode']==='PINNED_CONTEXT_CONFLICT',
    'real CRM F3-41 refuses forged tenant despite syntactically valid MAC');
echo "CRM F3-42 PASS: ".$checks." cross-repository PHP compatibility assertions, two real disposable PostgreSQL containers, HTTP + unknown hold; NO real lead.\n";
