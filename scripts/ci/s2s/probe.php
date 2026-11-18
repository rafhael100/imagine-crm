<?php
// Actual HTTP adversarial requests to the product; never a fake product server.
$mode=$argv[1];$key=file_get_contents('/certs/signing.key');
$v=['schema'=>'CENTRAL_AUTO_MTLS_REQUEST_V2','issuer'=>'portal-automation-admin','audience'=>'crm-automation-state','operation'=>'automation.state.read',
'company_id'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','request_id'=>'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee','nonce'=>bin2hex(random_bytes(32)),'issued_at'=>time()];
$v['mac']=hash_hmac('sha256',implode('|',$v),$key);
if($mode==='wrong-mac')$v['mac']=str_repeat('0',64);
if($mode==='wrong-operation')$v['operation']='automation.execute';
$headers="Content-Type: application/json\r\nConnection: close\r\n";
if($mode==='cookie')$headers.="Cookie: synthetic_human_session=NEVER_FORWARD\r\n";
$ssl=['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>'crm-s2s','cafile'=>'/certs/ca.crt'];
if($mode!=='no-cert-forged-header'){$ssl['local_cert']='/certs/portal.crt';$ssl['local_pk']='/certs/portal.key';}
else $headers.="IMAGINE_MTLS_VERIFY: SUCCESS\r\nIMAGINE_MTLS_CERT: FORGED\r\n";
$ctx=stream_context_create(['ssl'=>$ssl,'http'=>['method'=>'POST','header'=>$headers,'content'=>json_encode($v),'timeout'=>3,'follow_location'=>0,'ignore_errors'=>true]]);
$result=@file_get_contents('https://crm-s2s:8443/private/automations/state',false,$ctx,0,16385);
if(preg_match('/HTTP\/\S+ ([0-9]{3})/',$http_response_header[0]??'',$m))echo $m[1];else echo 'TLS_DENIED';
