<?php

declare(strict_types=1);
namespace App\Homologation\S2S;
use Illuminate\Http\Request;
use PDO;
use Throwable;

/** Private staging service boundary. No guard, cookie, human session or browser tenant authority. */
final class StateReceiver
{
 public function __invoke(Request $r) {
  $headers=['Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff'];
  $deny=fn(int $status)=>response()->json(['error'=>'AUTOMATION_SERVICE_UNAVAILABLE'],$status,$headers);
  if(!app()->environment('staging') || getenv('CRM_AUTO_S2S_PROFILE')!=='synthetic-private-v1')return $deny(404);
  // Set ONLY by the dedicated nginx FastCGI listener, never mapped from HTTP headers.
  if($r->server('IMAGINE_MTLS_VERIFY')!=='SUCCESS' || $r->headers->has('Cookie') || $r->headers->has('Authorization')
   || $r->query()!==[] || $r->header('Content-Type')!=='application/json')return $deny(403);
  try {
   $pem=rawurldecode((string)$r->server('IMAGINE_MTLS_CERT',''));
   if(strlen($pem)>8192 || !($fingerprint=openssl_x509_fingerprint($pem,'sha256')))return $deny(403);
   $raw=$r->getContent(); if(strlen($raw)>4096)return $deny(413);
   try {$v=StrictJson::decodeObject($raw,4096,8);} catch(\JsonException) {return $deny(403);}
   $keys=array_keys($v);sort($keys);
   if($keys!==['audience','company_id','issued_at','issuer','mac','nonce','operation','request_id','schema'])return $deny(403);
   foreach(['audience','company_id','issuer','mac','nonce','operation','request_id','schema'] as $key)if(!is_string($v[$key]))return $deny(403);
   if($v['schema']!=='CENTRAL_AUTO_MTLS_REQUEST_V2' || $v['issuer']!=='portal-automation-admin'
    || $v['audience']!=='crm-automation-state' || $v['operation']!=='automation.state.read'
    || !is_int($v['issued_at']) || abs(time()-$v['issued_at'])>60)return $deny(403);
   foreach(['company_id','request_id'] as $key)if(!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$v[$key]))return $deny(403);
   foreach(['nonce','mac'] as $key)if(!preg_match('/\A[a-f0-9]{64}\z/D',$v[$key]))return $deny(403);
   $key=file_get_contents((string)getenv('CRM_AUTO_S2S_KEY_FILE'));if(!is_string($key)||strlen($key)<32||strlen($key)>256)return $deny(503);
   $canonical=implode('|',array_map(fn($k)=>(string)$v[$k],['schema','issuer','audience','operation','company_id','request_id','nonce','issued_at']));
   if(!hash_equals(hash_hmac('sha256',$canonical,$key),$v['mac']))return $deny(403);
   $password=file_get_contents((string)getenv('CRM_AUTO_S2S_DB_PASSWORD_FILE'));
   $db=new PDO((string)getenv('CRM_AUTO_S2S_DSN'),'crm_auto_transport',trim($password),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
   $db->exec("SET statement_timeout='2000ms'; SET lock_timeout='1000ms'");
   $profile=$db->query("SELECT current_user,rolsuper,rolbypassrls FROM pg_roles WHERE rolname=current_user")->fetch(PDO::FETCH_ASSOC);
   if($profile['current_user']!=='crm_auto_transport' || $profile['rolsuper'] || $profile['rolbypassrls'])return $deny(503);
   $q=$db->prepare('SELECT crm_auto_service.read_state(CAST(? AS uuid),?,?,?,?,?,?,?)');
   $q->execute([$v['company_id'],$fingerprint,$v['issuer'],$v['operation'],$v['request_id'],$v['nonce'],hash('sha256',$canonical.'|'.$v['mac']),$v['issued_at']]);
   $result=json_decode($q->fetchColumn(),true,16,JSON_THROW_ON_ERROR);
   if($result['decision']!=='READ_OK')return $deny($result['decision']==='REPLAY_DENIED'?409:403);
   $body=base64_encode(json_encode($result['body'],JSON_THROW_ON_ERROR));
   if(strlen($body)>11000)return $deny(503);
   $reply=['schema'=>'CENTRAL_AUTO_MTLS_RESPONSE_V2','request_id'=>$v['request_id'],'nonce'=>$v['nonce'],'company_id'=>$v['company_id'],'operation'=>$v['operation'],'body_b64'=>$body];
   $reply['mac']=hash_hmac('sha256',implode('|',$reply),$key);
   return response()->json($reply,200,$headers);
  } catch(Throwable) { return $deny(503); }
 }
}
