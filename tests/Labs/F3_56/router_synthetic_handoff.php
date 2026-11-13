<?php
declare(strict_types=1);
/** Disposable PHP HTTP router. NEVER register as a Laravel route. */
require_once dirname(__DIR__,3).'/app/Core/Leads/SignedServiceHandoffBoundary.php';
require_once __DIR__.'/../F3_55/SignedSyntheticReadback.php';
use App\Core\Leads\SignedServiceHandoffBoundary;
use Tests\Labs\F3_55\SignedSyntheticReadback;
function reply(int $code,string $reason,bool $found=false):void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode([
        'schema'=>'imagine.crm.f356.synthetic-http.v1',
        'reasonCode'=>$reason,
        'syntheticMetadataLocated'=>$found,
        'realCrmLeadPersisted'=>false,
        'tenantAuthenticatedForProduction'=>false,
        'canAcknowledgeVisitor'=>false,
        'canAutoRetryUnknownOutcome'=>false,
        'canDeploy'=>false,
    ],JSON_THROW_ON_ERROR);
}
if(PHP_SAPI!=='cli-server'
    ||getenv('GITHUB_ACTIONS')!=='true'
    ||getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    ||getenv('CRM_F356_HTTP_LAB')!=='1'
    ||getenv('DB_HOST')!=='127.0.0.1'
    ||getenv('DB_DATABASE')!=='f353_nonce_ci'
    ||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'
    ||(isset($_SERVER['SERVER_ADDR'])
        && $_SERVER['SERVER_ADDR']!=='127.0.0.1')) {
    reply(503,'CI_ONLY_LOOPBACK_REQUIRED');return;
}
$path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'
    ||!in_array($path,['/__lab/f356/deliver','/__lab/f356/readback'],true)){
    reply(404,'NO_OPERATIONAL_ROUTE');return;
}
if(($_SERVER['CONTENT_TYPE']??'')!=='application/json'){
    reply(415,'JSON_REQUIRED');return;
}
$length=$_SERVER['CONTENT_LENGTH']??null;
if($length!==null&&(!ctype_digit((string)$length)||(int)$length>2048)){
    reply(413,'BODY_TOO_LARGE');return;
}
$raw=file_get_contents('php://input',false,null,0,2049);
if(!is_string($raw)||strlen($raw)>2048){reply(413,'BODY_TOO_LARGE');return;}
try {$frame=json_decode($raw,true,16,JSON_THROW_ON_ERROR);}
catch(Throwable){reply(400,'INVALID_JSON');return;}
if(!is_array($frame)){reply(422,'CONTRACT_DENIED');return;}
$canonical=(new SignedServiceHandoffBoundary())->decodeCanonicalJson($raw);
if($canonical===null){reply(422,'CANONICAL_JSON_REQUIRED');return;}
$frame=$canonical;
function stamp(string $name):?int {
    $v=$_SERVER[$name]??null;
    if(!is_string($v)||preg_match('/\A[1-9][0-9]{0,11}\z/D',$v)!==1)return null;
    $i=(int)$v;
    return (string)$i===$v?$i:null;
}
$auth=[
    'issued_at'=>stamp('HTTP_X_LAB_ISSUED_AT'),
    'expires_at'=>stamp('HTTP_X_LAB_EXPIRES_AT'),
    'nonce'=>$_SERVER['HTTP_X_LAB_NONCE']??null,
    'mac'=>$_SERVER['HTTP_X_LAB_SIGNATURE']??null
];
$requestHex=getenv('CRM_F356_REQUEST_KEY_HEX');
$lookupHex=getenv('CRM_F356_LOOKUP_KEY_HEX');
if(!is_string($requestHex)||!is_string($lookupHex)
    ||preg_match('/\A[a-f0-9]{96}\z/D',$requestHex)!==1
    ||preg_match('/\A[a-f0-9]{96}\z/D',$lookupHex)!==1
    ||hash_equals($requestHex,$lookupHex)){
    reply(503,'CI_KEYS_UNAVAILABLE');return;
}
$requestKey=hex2bin($requestHex);
$lookupKey=hex2bin($lookupHex);
if(!is_string($requestKey)||!is_string($lookupKey)){
    reply(503,'CI_KEYS_UNAVAILABLE');return;
}
$binding=[
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
try{
    $db=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f353_nonce_ci',
        'f353_sender_a','f353-synthetic-a-only',[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
        ]);
    $now=time();
    if($path==='/__lab/f356/readback'){
        $result=(new SignedSyntheticReadback())->inspect(
            $db,$frame,$binding,$auth,$lookupKey,$now
        );
        $reason=$result['reasonCode'];
        $code=match($reason){
            'SYNTHETIC_METADATA_FOUND_NO_REAL_LEAD'=>200,
            'NOT_FOUND_HOLD_UNKNOWN'=>404,
            'CONTRACT_DENIED'=>422,
            'SERVER_CONTEXT_DENIED'=>403,
            'READBACK_AUTH_DENIED'=>401,
            default=>503
        };
        reply($code,$reason,$result['syntheticMetadataLocated']);return;
    }
    $decision=(new SignedServiceHandoffBoundary())->inspect(
        $frame,$binding,$auth,$requestKey,$now
    );
    $reason=$decision['reasonCode'];
    if($reason!=='SIGNED_TECHNICAL_FRAME_ONLY'){
        reply(match($reason){
            'FRAME_CONTRACT_DENIED'=>422,
            'SERVER_TENANT_CONTEXT_DENIED'=>403,
            default=>401
        },$reason);return;
    }
    // One transaction: neither nonce nor technical metadata can commit alone.
    $db->beginTransaction();
    $stmt=$db->prepare(
        'INSERT INTO crm_f353_nonce_lab.request_nonces
          (organization_reference,request_nonce,issued_at)
          VALUES(CAST(:org AS uuid),CAST(:nonce AS uuid),:iat)
          ON CONFLICT DO NOTHING RETURNING request_nonce'
    );
    $stmt->execute([
        'org'=>$binding['organization_reference'],
        'nonce'=>$auth['nonce'],'iat'=>$auth['issued_at']
    ]);
    if($stmt->fetchColumn()===false){
        $db->rollBack();
        reply(409,'NONCE_REPLAY_HOLD_READBACK');return;
    }
    $stmt=$db->prepare(
        'INSERT INTO crm_f353_nonce_lab.synthetic_delivery_receipts
         (organization_reference,submission_reference,request_nonce,delivery_state)
         VALUES(CAST(:org AS uuid),CAST(:sub AS uuid),CAST(:nonce AS uuid),:state)'
    );
    $stmt->execute([
        'org'=>$binding['organization_reference'],
        'sub'=>$frame['submission_reference'],
        'nonce'=>$auth['nonce'],
        'state'=>'SYNTHETIC_COMMITTED_NO_REAL_LEAD'
    ]);
    $db->commit();
    // Simulate response loss AFTER durable PG commit; never issue an invented ACK.
    if(($_SERVER['HTTP_X_LAB_DROP_REPLY']??null)==='1'){
        reply(503,'COMMITTED_REPLY_LOST_READBACK_REQUIRED');return;
    }
    reply(202,'SYNTHETIC_METADATA_COMMITTED_NO_REAL_LEAD',true);
} catch(PDOException $e){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    if($e->getCode()==='23505')reply(409,'LOGICAL_DUPLICATE_HOLD_READBACK');
    else reply(503,'UNKNOWN_OUTCOME_HOLD_READBACK');
} catch(Throwable){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    reply(503,'UNKNOWN_OUTCOME_HOLD_READBACK');
}
