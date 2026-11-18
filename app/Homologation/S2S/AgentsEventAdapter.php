<?php

declare(strict_types=1);
namespace App\Homologation\S2S;
use PDO;
use RuntimeException;
use Throwable;
/** Product-owned, closed lead.created -> lead.ingest adapter. No rule compiler or n8n dispatch. */
final class AgentsEventAdapter
{
 public function send(string $company,string $event):void {
  if(!app()->environment('staging')||getenv('CRM_AGENTS_S2S_PROFILE')!=='synthetic-private-v1')throw new RuntimeException('SENDER_DISABLED');
  $db=new PDO(getenv('CRM_AUTO_S2S_DSN'),'crm_auto_sender',trim(file_get_contents('/certs/sender.password')),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
  $db->exec("SET statement_timeout='2000ms'; SET lock_timeout='1000ms'; SET idle_in_transaction_session_timeout='5000ms'");
  $stage=function()use($db,$company,$event){$q=$db->prepare('SELECT crm_auto_service.stage_outbound(CAST(? AS uuid),CAST(? AS uuid))');$q->execute([$company,$event]);return json_decode($q->fetchColumn(),true,12,JSON_THROW_ON_ERROR);};
  // Durable staging precedes I/O; a lost acknowledgement retries the identical persisted envelope.
  $stage();
  $db->beginTransaction();
  try {
   // Revalidate current source grant, then hold its lock and the outbox row through I/O/ack commit.
   $row=$stage();if($row['state']==='accepted'){$db->commit();return;}
   $key=file_get_contents('/certs/agents-signing.key');if(!is_string($key)||strlen($key)<32)throw new RuntimeException('SENDER_KEY_UNAVAILABLE');
   $e=['schema'=>'AUTO_IA_S2S_V1','issuer'=>'imagine-crm','audience'=>'imagine-ai-agents','product'=>'imagine-crm','operation'=>'lead.ingest',
    'tenant_ref'=>$row['tenant_ref'],'event_ref'=>'SYNTHETIC_'.strtoupper(str_replace('-','',$event)),'nonce_ref'=>$row['nonce_ref'],'request_ref'=>$row['request_ref'],
    'issued_at'=>$row['issued_at'],'expires_at'=>$row['expires_at'],'payload_hash'=>hash('sha256',$row['payload']),'key_id'=>'SYNTHETIC_KEY_A'];
   $e['signature']=hash_hmac('sha256',implode("\n",$e),$key);
   $body=json_encode(['company_id'=>$company,'envelope'=>$e,'payload_b64'=>base64_encode($row['payload'])],JSON_THROW_ON_ERROR);
   $ctx=stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false,'peer_name'=>'agents-s2s','cafile'=>'/certs/ca.crt','local_cert'=>'/certs/crm.crt','local_pk'=>'/certs/crm.key'],
    'http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nConnection: close\r\n",'content'=>$body,'timeout'=>3,'follow_location'=>0,'max_redirects'=>0,'ignore_errors'=>true]]);
   $raw=@file_get_contents('https://agents-s2s:8443/private/events',false,$ctx,0,4097);
   if(!is_string($raw)||strlen($raw)>4096||!preg_match('/\AHTTP\/\S+ 200(?: |$)/',$http_response_header[0]??''))throw new RuntimeException('AGENTS_TRANSPORT_DENIED');
   $ackHash=AgentsAcknowledgement::validate($raw,$company,$e['event_ref'],$e['payload_hash'],$key);
   $q=$db->prepare('SELECT crm_auto_service.ack_outbound(CAST(? AS uuid),CAST(? AS uuid),?)');$q->execute([$company,$event,$ackHash]);$db->commit();
  } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
}
