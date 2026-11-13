<?php
declare(strict_types=1);

/**
 * The CI server has already started and has a valid HMAC key. A DBA-only
 * synthetic policy change must stop BOTH delivery and readback immediately.
 */
if (getenv('GITHUB_ACTIONS')!=='true'
    ||getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    ||getenv('CRM_F363_REVOCATION_LAB')!=='1'
    ||getenv('CRM_F356_HTTP_LAB')!=='1'
    ||getenv('DB_HOST')!=='127.0.0.1'
    ||getenv('DB_DATABASE')!=='f353_nonce_ci') {
    throw new RuntimeException('F3-63 disposable CI only');
}
$requestKey=hex2bin((string)getenv('CRM_F356_REQUEST_KEY_HEX'));
$readbackKey=hex2bin((string)getenv('CRM_F356_LOOKUP_KEY_HEX'));
if(!is_string($requestKey)||!is_string($readbackKey)
    ||strlen($requestKey)!==48||strlen($readbackKey)!==48
    ||hash_equals($requestKey,$readbackKey)) {
    throw new RuntimeException('F3-63 needs independent ephemeral keys');
}
$frame=[
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'submission_reference'=>'ffffffff-ffff-4fff-8fff-ffffffffffff',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
function signedRequest(array $frame,string $key,string $operation):array {
    $now=time();
    $nonce='33333333-3333-4333-8333-333333333333';
    if($operation==='deliver') {
        $parts=['imagine.portal.operational-preparation.v1'];
        foreach(['organization_reference','binding_reference','site_reference',
                 'form_reference','destination_reference','submission_reference'] as $f) {
            $parts[]=$frame[$f];
        }
        array_push($parts,(string)$now,(string)($now+60),$nonce);
        $mac=hash_hmac('sha256',implode("\n",$parts),$key);
    } else {
        $parts=['imagine.crm.f355.synthetic-readback.v1'];
        foreach($frame as $value)$parts[]=(string)$value;
        array_push($parts,(string)$now,(string)($now+60),$nonce);
        $mac=hash_hmac('sha256',implode("\n",$parts),$key);
    }
    return [
        'X-Lab-Issued-At: '.$now,
        'X-Lab-Expires-At: '.($now+60),
        'X-Lab-Nonce: '.$nonce,
        'X-Lab-Signature: '.$mac,
    ];
}
function forbidden(string $route,array $frame,string $key,string $operation):void {
    $headers=array_merge(['Content-Type: application/json'],
        signedRequest($frame,$key,$operation));
    $ctx=stream_context_create(['http'=>[
        'method'=>'POST','header'=>implode("\r\n",$headers),
        'content'=>json_encode($frame,JSON_THROW_ON_ERROR),
        'ignore_errors'=>true,'timeout'=>6,'follow_location'=>0,
    ]]);
    $raw=@file_get_contents('http://127.0.0.1:19156'.$route,false,$ctx);
    $statusLine=$http_response_header[0]??'';
    if(!is_string($raw)||!is_string($statusLine)
        ||preg_match('/\AHTTP\/[0-9.]+ 503\b/',$statusLine)!==1) {
        throw new RuntimeException('F3-63 accepted revoked/expired principal');
    }
    $body=json_decode($raw,true,8,JSON_THROW_ON_ERROR);
    if(($body['reasonCode']??null)!=='SERVER_BINDING_NOT_VERIFIED'
        ||isset($body['technicalReceipt'])
        ||($body['syntheticMetadataLocated']??null)!==false) {
        throw new RuntimeException('F3-63 leaked technical receipt after revocation');
    }
    foreach(['realCrmLeadPersisted','tenantAuthenticatedForProduction',
              'canAcknowledgeVisitor','canAutoRetryUnknownOutcome','canDeploy'] as $flag) {
        if(($body[$flag]??null)!==false)
            throw new RuntimeException('F3-63 claims operational authority');
    }
}
$db=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f353_nonce_ci',
    'f353_sender_a','f353-synthetic-a-only',[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
    ]);
$before=(int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.synthetic_delivery_receipts')->fetchColumn();
forbidden('/__lab/f356/deliver',$frame,$requestKey,'deliver');
forbidden('/__lab/f356/readback',$frame,$readbackKey,'readback');
$after=(int)$db->query('SELECT count(*) FROM crm_f353_nonce_lab.synthetic_delivery_receipts')->fetchColumn();
if($after!==$before)throw new RuntimeException('F3-63 revoked service mutated receipt ledger');
echo "CRM F3-63 PASS: revoked/expired binding denied for both routes without restart; no new receipt.\n";
