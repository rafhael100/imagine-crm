<?php

declare(strict_types=1);

// F3-41: real HTTP against the actual CRM PHP metadata/receipt classes, with
// only synthetic data, CI ephemeral PostgreSQL 16, 127.0.0.1 and no Laravel API.
require_once __DIR__.'/../F3_40/SyntheticPortalHandoffReceiver.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadReceiptMacAssessment.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadReceiptMacInspector.php';

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadReceiptMacInspector;

if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F341_HTTP_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='imagine_crm_handoff_ci'
    || !extension_loaded('pdo_pgsql')) {
    fwrite(STDERR,"F3-41 refuses non-disposable GitHub CI.\n");
    exit(2);
}
$reqHex=getenv('CRM_F341_REQUEST_KEY_HEX');
$receiptHex=getenv('CRM_F341_RECEIPT_KEY_HEX');
if (!is_string($reqHex)||!is_string($receiptHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$reqHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$receiptHex)
    || hash_equals($reqHex,$receiptHex)) {
    throw new RuntimeException('F3-41 requires distinct CI-only keys');
}
$reqKey=hex2bin($reqHex);
$receiptKey=hex2bin($receiptHex);
if (!is_string($reqKey)||!is_string($receiptKey)) {
    throw new RuntimeException('F3-41 invalid fixture-only keys');
}
$checks=0;
function check(bool $value,string $message): void
{
    global $checks;
    ++$checks;
    if (!$value) {
        throw new RuntimeException('F3-41 FAIL: '.$message);
    }
}
function uuid(): string
{
    $v=bin2hex(random_bytes(16));
    $v[12]='4';
    $v[16]=dechex((hexdec($v[16])&3)|8);
    return substr($v,0,8).'-'.substr($v,8,4).'-'.substr($v,12,4)
        .'-'.substr($v,16,4).'-'.substr($v,20,12);
}
function envelope(string $sub): array
{
    return [
        'protocol'=>'imagine.lead-intake',
        'schema_version'=>1,
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'submission_reference'=>$sub,
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666',
    ];
}
function sign(array $e,string $secret,string $domain,string $nonce,int $iat,int $exp): array
{
    $parts=[$domain];
    foreach ([
        'organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'
    ] as $k) {
        $parts[]=strtolower($e[$k]);
    }
    array_push($parts,(string)$iat,(string)$exp,$nonce);
    return [
        'X-Lab-Issued-At'=>(string)$iat,
        'X-Lab-Expires-At'=>(string)$exp,
        'X-Lab-Nonce'=>$nonce,
        'X-Lab-Signature'=>hash_hmac('sha256',implode("\n",$parts),$secret)
    ];
}
function send(array $e,array $auth=[],string $path='/__lab/crm/ingest',bool $drop=false,
    string $method='POST',string $type='application/json',?string $raw=null): array
{
    $headers=['Content-Type: '.$type,'Connection: close'];
    foreach ($auth as $key=>$val) {
        check(preg_match('/\AX-Lab-[A-Za-z-]+\z/D',$key)===1,'safe header key');
        check(is_string($val)&&preg_match('/\A[0-9a-f-]+\z/iD',$val)===1,'safe header value');
        $headers[]=$key.': '.$val;
    }
    if ($drop) {
        $headers[]='X-Lab-Drop-Receipt: 1';
    }
    $context=stream_context_create([
        'http'=>[
            'method'=>$method,'header'=>implode("\r\n",$headers),
            'content'=>$raw??json_encode($e,JSON_THROW_ON_ERROR),
            'ignore_errors'=>true,'timeout'=>7,'follow_location'=>0
        ],
    ]);
    $response=@file_get_contents('http://127.0.0.1:19141'.$path,false,$context);
    $received=$http_response_header??[];
    check(is_string($response)&&isset($received[0]),'HTTP fixture responded');
    check(preg_match('/\AHTTP\/[0-9.]+\s+([0-9]{3})\b/',$received[0],$match)===1,
        'HTTP response code parsed');
    $data=json_decode($response,true,16,JSON_THROW_ON_ERROR);
    check(is_array($data),'HTTP JSON object');
    check($data['schema']==='imagine.crm.synthetic-portal-http.v1','test-only schema');
    check($data['mode']==='CI_ONLY_REAL_PG16_NOT_REAL_CRM_RECEIPT','not production CRM');
    foreach ([
        'realCrmPersisted','tenantAuthenticatedForProduction','canAcknowledgeVisitor',
        'canAutoRetryUnknownOutcome','productionAuthorized'
    ] as $flag) {
        check($data[$flag]===false,'never authorize '.$flag);
    }
    $encoded=json_encode($data,JSON_THROW_ON_ERROR);
    check(!str_contains($encoded,$e['submission_reference']),'response never leaks submitted UUID');
    return [(int)$match[1],$data];
}
function expectResult(array $reply,int $status,string $reason): void
{
    check($reply[0]===$status,'HTTP status '.$status.' for '.$reason);
    check($reply[1]['reasonCode']===$reason,'HTTP reason '.$reason);
}
function verifyFixture(array $response,array $e,string $key,int $now): string
{
    $r=$response['receipt']??null;
    check(is_array($r)&&count($r)===4,'signed synthetic receipt after PG commit');
    $claim=new SyntheticIdempotencyClaim(
        new LeadSubmissionIdentity(
            $e['organization_reference'],$e['binding_reference'],$e['submission_reference']
        ),
        $e['site_reference'],$e['form_reference'],$e['destination_reference']
    );
    $assessment=(new SyntheticLeadReceiptMacInspector())->inspect(
        $claim,$claim,$r['issued_at'],$r['expires_at'],$now,$r['nonce'],$r['mac'],$key
    );
    check(!$assessment->canAcknowledgeDurableReceipt(),'matching MAC never ACKs live CRM');
    return $assessment->reasonCode;
}
function stored(PDO $db,string $table,string $sub): int
{
    check(in_array($table,['metadata_receipts','request_nonces'],true),'table allowlist');
    $stmt=$db->prepare('SELECT count(*) FROM crm_portal_handoff_ci.'.$table
        .' WHERE organization_reference=CAST(:org AS uuid) AND submission_reference=CAST(:sub AS uuid)');
    $stmt->execute([
        'org'=>'11111111-1111-4111-8111-111111111111',
        'sub'=>$sub,
    ]);
    return (int)$stmt->fetchColumn();
}
$db=new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=imagine_crm_handoff_ci',
    'postgres',(string)getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_EMULATE_PREPARES=>false]
);
check($db->query('SELECT current_database()')->fetchColumn()==='imagine_crm_handoff_ci',
    'test database matches');

$now=time();
$a=envelope('c1c1c1c1-c1c1-4c1c-8c1c-c1c1c1c1c1c1');
$nonce1=uuid();
$first=sign($a,$reqKey,'imagine.portal.synthetic-service-request.v1',$nonce1,$now,$now+120);
check(stored($db,'metadata_receipts',$a['submission_reference'])===0,'fresh F3-41 submission');
expectResult(send($a),401,'SYNTHETIC_AUTH_REQUIRED');
$wrong=sign($a,random_bytes(48),'imagine.portal.synthetic-service-request.v1',
    uuid(),$now,$now+120);
expectResult(send($a,$wrong),401,'SYNTHETIC_AUTH_REJECTED');
$expired=sign($a,$reqKey,'imagine.portal.synthetic-service-request.v1',
    uuid(),$now-400,$now-100);
expectResult(send($a,$expired),401,'SYNTHETIC_AUTH_REJECTED');
expectResult(send($a,$first,'/__lab/crm/ingest',false,'GET'),404,'NO_PUBLIC_ROUTE');
expectResult(send($a,$first,'/__lab/crm/ingest?unsafe=1'),404,'NO_PUBLIC_ROUTE');
expectResult(send($a,$first,'/__lab/crm/ingest',false,'POST','text/plain'),
    415,'JSON_CONTENT_TYPE_REQUIRED');
expectResult(send($a,$first,'/__lab/crm/ingest',false,'POST','application/json',
    '{"truncated":'),400,'INVALID_JSON');
expectResult(send($a,$first,'/__lab/crm/ingest',false,'POST','application/json',
    json_encode([...$a,'extra'=>str_repeat('x',5000)],JSON_THROW_ON_ERROR)),
    413,'SYNTHETIC_BODY_TOO_LARGE');
expectResult(send([...$a,'email'=>'synthetic@example.invalid'],$first),
    422,'ENVELOPE_REJECTED');
$forged=$a;
$forged['organization_reference']='99999999-9999-4999-8999-999999999999';
expectResult(send($forged,sign($forged,$reqKey,'imagine.portal.synthetic-service-request.v1',
    uuid(),$now,$now+120)),403,'PINNED_CONTEXT_CONFLICT');
check(stored($db,'metadata_receipts',$a['submission_reference'])===0,
    'rejected HTTP requests wrote nothing');

$received=send($a,$first);
expectResult($received,202,'LAB_MOCK_COMMITTED_FIRST');
check($received[1]['mockCommitted']===true,'mock DB commit confirmed only');
check(verifyFixture($received[1],$a,$receiptKey,$now)==='LAB_MAC_MATCH_UNTRUSTED',
    'existing CRM class validates F121 format');
check(verifyFixture($received[1],$a,$reqKey,$now)==='MAC_MISMATCH',
    'request key cannot verify mock receipt');
check(stored($db,'metadata_receipts',$a['submission_reference'])===1,
    'one stored synthetic submission');
check(stored($db,'request_nonces',$a['submission_reference'])===1,'one nonce');
expectResult(send($a,$first),409,'REQUEST_NONCE_REPLAY');
$again=send($a,sign($a,$reqKey,'imagine.portal.synthetic-service-request.v1',
    uuid(),$now,$now+120));
expectResult($again,202,'LAB_MOCK_COMMITTED_LOGICAL_REPLAY');
check(stored($db,'metadata_receipts',$a['submission_reference'])===1,
    'different nonce did not duplicate logical submission');
check(stored($db,'request_nonces',$a['submission_reference'])===2,
    'different signed attempt recorded separately');

// After mock PG commit, transport explicitly drops ACK. Do not retry the
// outbound send. Perform a readback signed with a DISTINCT domain instead.
$b=envelope('c2c2c2c2-c2c2-4c2c-8c2c-c2c2c2c2c2c2');
$lost=send($b,sign($b,$reqKey,'imagine.portal.synthetic-service-request.v1',
    uuid(),$now,$now+120),'/__lab/crm/ingest',true);
expectResult($lost,503,'UNKNOWN_OUTCOME_NO_RECEIPT');
check(!isset($lost[1]['receipt']),'no receipt on simulated lost response');
check(stored($db,'metadata_receipts',$b['submission_reference'])===1,
    'source of truth mock PG committed despite no receipt returned');
check(stored($db,'request_nonces',$b['submission_reference'])===1,
    'failed response was not automatically retried');
$lookupAuth=sign($b,$reqKey,'imagine.portal.synthetic-crm-lookup.v1',
    uuid(),$now,$now+120);
$verified=send($b,$lookupAuth,'/__lab/crm/readback');
expectResult($verified,200,'LAB_MOCK_READBACK_SIGNED');
check(verifyFixture($verified[1],$b,$receiptKey,$now)==='LAB_MAC_MATCH_UNTRUSTED',
    'readback signed only after stored record checked');
check(stored($db,'request_nonces',$b['submission_reference'])===1,
    'lookup never creates another request nonce');
expectResult(send($b,$lookupAuth,'/__lab/crm/ingest'),401,'SYNTHETIC_AUTH_REJECTED');
$absent=envelope('c3c3c3c3-c3c3-4c3c-8c3c-c3c3c3c3c3c3');
expectResult(send($absent,sign($absent,$reqKey,'imagine.portal.synthetic-crm-lookup.v1',
    uuid(),$now,$now+120),'/__lab/crm/readback'),404,'LAB_RECORD_NOT_VERIFIED');
echo "CRM F3-41 PASS: ".$checks." private HTTP/real CRM PHP/PG16 test assertions; no live CRM ACK.\n";
