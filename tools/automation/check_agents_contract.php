<?php
/** Bilateral CI fixture generator; existing Agents receiver is authoritative. No transport or n8n delivery. */
declare(strict_types=1);
$root=getenv('AGENTS_CONTRACT_ROOT');
if(!$root || !is_file($root.'/tools/auto_ia/MockS2SEnvelopeVerifier.php')) throw new RuntimeException('PINNED_AGENTS_REQUIRED');
require $root.'/tools/auto_ia/MockS2SEnvelopeVerifier.php';
use ImagineAI\AutomationFirst\MockS2SEnvelopeVerifier;
$key='SYNTHETIC_OFFLINE_HMAC_TEST_KEY_A_32_BYTES_ONLY';
$now=time();
$event=['schema'=>'AUTO_IA_S2S_V1','issuer'=>'imagine-crm','audience'=>'imagine-ai-agents',
 'product'=>'imagine-crm','operation'=>'lead.ingest','tenant_ref'=>'SYNTHETIC_TENANT_A',
 'event_ref'=>'SYNTHETIC_CRM_AUTO_EVENT','nonce_ref'=>'SYNTHETIC_CRM_AUTO_NONCE',
 'request_ref'=>'SYNTHETIC_CRM_AUTO_REQUEST','issued_at'=>$now-1,'expires_at'=>$now+120,
 'payload_hash'=>hash('sha256','SYNTHETIC_NATIVE_INGEST_RECEIPT'),'key_id'=>'SYNTHETIC_KEY_A','signature'=>''];
function sign(array $event,string $key): array {
 $unsigned=$event;unset($unsigned['signature']);
 $event['signature']=hash_hmac('sha256',implode("\n",array_map(strval(...),array_values($unsigned))),$key);return $event;
}
function emit(array $event,string $expected): void {
 $args=[]; foreach($event as $value) {
  if(is_int($value)) $args[]=(string)$value;
  elseif(is_string($value) && preg_match('/\A[A-Za-z0-9_.-]+\z/D',$value)) $args[]="'".$value."'";
  else throw new RuntimeException('UNSAFE_FIXTURE');
 }
 echo "DO \$\$ DECLARE result text; BEGIN result:=auto_ia_lab.receive_signed_no_ai(",implode(',',$args),"); IF result<>'$expected' THEN RAISE EXCEPTION 'contract expected $expected, got %',result; END IF; END \$\$;\n";
}
$event=sign($event,$key);
$check=MockS2SEnvelopeVerifier::verify($event,$now,['SYNTHETIC_KEY_A'=>$key]);
if($check['decision']!=='LAB_VERIFIED_NO_AUTHORIZATION') throw new RuntimeException('PHP_CONTRACT_REJECTED');
echo "\\set ON_ERROR_STOP on\nSET ROLE aia_lab_tenant_a;\n";
emit($event,'LAB_RECEIVED_NO_DELIVERY'); emit($event,'LAB_DUPLICATE_NO_DELIVERY');
$bad=$event;$bad['tenant_ref']='SYNTHETIC_TENANT_B';emit(sign($bad,$key),'LAB_DENY_SERVICE_TENANT');
$bad=$event;$bad['payload_hash']=str_repeat('0',64);emit($bad,'LAB_DENY_MAC');
$bad=$event;$bad['operation']='billing.charge';emit(sign($bad,$key),'LAB_DENY_AI_OR_UNKNOWN_OPERATION');
$bad=$event;$bad['operation']='lead.message.interpret';emit(sign($bad,$key),'LAB_DENY_AI_OR_UNKNOWN_OPERATION');
// Revocation applies to a previously accepted exact replay, not only a new nonce.
echo "RESET ROLE; SET ROLE aia_lab_owner; UPDATE auto_ia_lab.mock_s2s_keys SET enabled=false WHERE issuer='imagine-crm' AND key_id='SYNTHETIC_KEY_A'; RESET ROLE; SET ROLE aia_lab_tenant_a;\n";
emit($event,'LAB_DENY_KEY');
echo "RESET ROLE;\n";
echo "DO \$\$ BEGIN IF (SELECT count(*) FROM auto_ia_lab.event_inbox WHERE event_ref='SYNTHETIC_CRM_AUTO_EVENT')<>1 THEN RAISE EXCEPTION 'unexpected inbox effects'; END IF; END \$\$;\n";
echo "\\echo CRM_AGENTS_CONTRACT_PASS cases=7 ai_calls=0 service_effects=0\n";
