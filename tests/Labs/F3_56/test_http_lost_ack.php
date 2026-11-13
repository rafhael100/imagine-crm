<?php
declare(strict_types=1);
require_once __DIR__.'/../F3_55/SignedSyntheticReadback.php';
use Tests\Labs\F3_55\SignedSyntheticReadback;
if(getenv('GITHUB_ACTIONS')!=='true'
    ||getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    ||getenv('CRM_F356_HTTP_LAB')!=='1'
    ||getenv('DB_HOST')!=='127.0.0.1'
    ||getenv('DB_DATABASE')!=='f353_nonce_ci')throw new RuntimeException('CI only');
$request=hex2bin((string)getenv('CRM_F356_REQUEST_KEY_HEX'));
$lookup=hex2bin((string)getenv('CRM_F356_LOOKUP_KEY_HEX'));
if(!is_string($request)||!is_string($lookup)||strlen($request)!==48||strlen($lookup)!==48)
    throw new RuntimeException('Synthetic keys required');
$org='11111111-1111-4111-8111-111111111111';
$frame=[
 'protocol'=>'imagine.lead-intake','schema_version'=>1,
 'organization_reference'=>$org,
 'binding_reference'=>'22222222-2222-4222-8222-222222222222',
 'submission_reference'=>'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
 'site_reference'=>'44444444-4444-4444-8444-444444444444',
 'form_reference'=>'55555555-5555-4555-8555-555555555555',
 'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
function token(array $frame,string $kind,string $key,int $iat,string $nonce):array {
 $exp=$iat+60;
 if($kind==='deliver'){
  $parts=['imagine.portal.operational-preparation.v1'];
  foreach(['organization_reference','binding_reference','site_reference','form_reference','destination_reference','submission_reference'] as $field)
   $parts[]=$frame[$field];
  array_push($parts,(string)$iat,(string)$exp,$nonce);
  $message=implode("\n",$parts);
 }else{
  $message=SignedSyntheticReadback::message($frame,$iat,$exp,$nonce);
 }
 return ['issued_at'=>$iat,'expires_at'=>$exp,'nonce'=>$nonce,
   'mac'=>hash_hmac('sha256',$message,$key)];
}
function send(string $route,array $frame,array $token,bool $drop=false,string $method='POST',?string $rawOverride=null):array {
 $headers=['Content-Type: application/json','Connection: close'];
 foreach(['issued_at'=>'X-Lab-Issued-At','expires_at'=>'X-Lab-Expires-At',
          'nonce'=>'X-Lab-Nonce','mac'=>'X-Lab-Signature'] as $key=>$name)
  $headers[]=$name.': '.$token[$key];
 if($drop)$headers[]='X-Lab-Drop-Reply: 1';
 $ctx=stream_context_create(['http'=>[
    'method'=>$method,'header'=>implode("\r\n",$headers),
    'content'=>$rawOverride??json_encode($frame,JSON_THROW_ON_ERROR),
    'ignore_errors'=>true,'timeout'=>6,'follow_location'=>0
 ]]);
 $raw=@file_get_contents('http://127.0.0.1:19156'.$route,false,$ctx);
 $statusLine=$http_response_header[0]??null;
 if(!is_string($raw)||!is_string($statusLine)
    ||preg_match('/\AHTTP\/[0-9.]+\s+([0-9]{3})\b/',$statusLine,$match)!==1)
     throw new RuntimeException('HTTP loopback unavailable');
 $body=json_decode($raw,true,8,JSON_THROW_ON_ERROR);
 if(($body['schema']??null)!=='imagine.crm.f356.synthetic-http.v1')
     throw new RuntimeException('Not the expected synthetic router');
 foreach(['realCrmLeadPersisted','tenantAuthenticatedForProduction',
          'canAcknowledgeVisitor','canAutoRetryUnknownOutcome','canDeploy'] as $flag)
   if(($body[$flag]??null)!==false)throw new RuntimeException('Unsafe claim: '.$flag);
 if(str_contains($raw,'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee'))
   throw new RuntimeException('Reply echoes submission identifier');
 return [(int)$match[1],$body];
}
$checks=0;
function checkResult(array $reply,int $http,string $reason,bool $found=false):void {
 global $checks;$checks++;
 if($reply[0]!==$http||($reply[1]['reasonCode']??null)!==$reason
    ||($reply[1]['syntheticMetadataLocated']??null)!==$found)
   throw new RuntimeException('F3-56 result mismatch: '.json_encode($reply));
}
$now=time();
$nonce='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$delivery=token($frame,'deliver',$request,$now,$nonce);
$readback=token($frame,'readback',$lookup,$now,'cccccccc-cccc-4ccc-8ccc-cccccccccccc');
// The transaction committed but response was dropped: never re-send the lead.
checkResult(send('/__lab/f356/deliver',$frame,$delivery,true),
  503,'COMMITTED_REPLY_LOST_READBACK_REQUIRED');
checkResult(send('/__lab/f356/readback',$frame,$readback),
  200,'SYNTHETIC_METADATA_FOUND_NO_REAL_LEAD',true);
checkResult(send('/__lab/f356/readback',$frame,$readback),
  200,'SYNTHETIC_METADATA_FOUND_NO_REAL_LEAD',true);
checkResult(send('/__lab/f356/deliver',$frame,$delivery),
  409,'NONCE_REPLAY_HOLD_READBACK');
$next=token($frame,'deliver',$request,$now,'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
checkResult(send('/__lab/f356/deliver',$frame,$next),
  409,'LOGICAL_DUPLICATE_HOLD_READBACK');
checkResult(send('/__lab/f356/readback',$frame,$delivery),
  401,'READBACK_AUTH_DENIED');
checkResult(send('/__lab/f356/deliver',$frame,$readback),
  401,'SIGNED_REQUEST_DENIED');
$missing=[...$frame,'submission_reference'=>'ffffffff-ffff-4fff-8fff-ffffffffffff'];
checkResult(send('/__lab/f356/readback',$missing,token($missing,'readback',$lookup,$now,'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee')),
  404,'NOT_FOUND_HOLD_UNKNOWN');
$forged=[...$frame,'organization_reference'=>'99999999-9999-4999-8999-999999999999'];
checkResult(send('/__lab/f356/deliver',$forged,token($forged,'deliver',$request,$now,'abababab-abab-4aba-8aba-abababababab')),
  403,'SERVER_TENANT_CONTEXT_DENIED');
checkResult(send('/__lab/f356/deliver',[...$frame,'email'=>'synthetic@example.invalid'],$delivery),
  422,'CANONICAL_JSON_REQUIRED');
checkResult(send('/__lab/f356/deliver',$frame,$delivery,false,'GET'),
  404,'NO_OPERATIONAL_ROUTE');
$db=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f353_nonce_ci',
  'f353_sender_a','f353-synthetic-a-only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$n=(int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.request_nonces')->fetchColumn();
$r=(int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.synthetic_delivery_receipts')->fetchColumn();
if($n!==1||$r!==1)throw new RuntimeException('Lost reply/replay caused duplicate or orphan');
echo "CRM F3-56 PASS: $checks HTTP signed sender/receiver tests; one nonce, one technical receipt, no lead.\n";
