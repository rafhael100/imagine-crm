<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/Core/Leads/SignedServiceHandoffBoundary.php';
use App\Core\Leads\SignedServiceHandoffBoundary;

$binding=[
 'organization_reference'=>'11111111-1111-4111-8111-111111111111',
 'binding_reference'=>'22222222-2222-4222-8222-222222222222',
 'site_reference'=>'44444444-4444-4444-8444-444444444444',
 'form_reference'=>'55555555-5555-4555-8555-555555555555',
 'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$frame=[
 'protocol'=>'imagine.lead-intake','schema_version'=>1,
 'organization_reference'=>$binding['organization_reference'],
 'binding_reference'=>$binding['binding_reference'],
 'submission_reference'=>'f9f9f9f9-f9f9-4f9f-8f9f-f9f9f9f9f9f9',
 'site_reference'=>$binding['site_reference'],
 'form_reference'=>$binding['form_reference'],
 'destination_reference'=>$binding['destination_reference'],
];
$key=random_bytes(48);$now=1763000000;
$nonce='77777777-7777-4777-8777-777777777777';
function signFrame(array $f,string $k,int $t,string $n):array {
 return [
   'issued_at'=>$t,'expires_at'=>$t+60,'nonce'=>$n,
   'mac'=>hash_hmac('sha256',SignedServiceHandoffBoundary::canonical($f,$t,$t+60,$n),$k)
 ];
}
$checks=0;
function expected(array $result,string $code):void {
 global $checks;$checks++;
 if(($result['reasonCode']??null)!==$code)throw new RuntimeException('Unexpected Core result: '.json_encode($result));
 foreach(['authenticatedServicePrincipal','authenticatedOperationalTenant',
          'atomicNonceClaimed','realLeadPersisted','canAcknowledgeVisitor','canDeploy'] as $flag)
    if(($result[$flag]??null)!==false)throw new RuntimeException('Unauthorized flag '.$flag);
}
$gate=new SignedServiceHandoffBoundary();
$auth=signFrame($frame,$key,$now,$nonce);
expected($gate->inspect($frame,$binding,$auth,$key,$now),'SIGNED_TECHNICAL_FRAME_ONLY');
expected($gate->inspect($frame,$binding,[...$auth,'mac'=>str_repeat('0',64)],$key,$now),'SIGNED_REQUEST_DENIED');
expected($gate->inspect($frame,$binding,$auth,random_bytes(48),$now),'SIGNED_REQUEST_DENIED');
expected($gate->inspect($frame,$binding,$auth,$key,$now+121),'SIGNED_REQUEST_DENIED');
expected($gate->inspect($frame,$binding,[...$auth,'nonce'=>'invalid'],$key,$now),'SIGNED_REQUEST_DENIED');
expected($gate->inspect([...$frame,'email'=>'nobody@example.invalid'],$binding,$auth,$key,$now),'FRAME_CONTRACT_DENIED');
expected($gate->inspect([...$frame,'schema_version'=>2],$binding,$auth,$key,$now),'FRAME_CONTRACT_DENIED');
expected($gate->inspect($frame,[],$auth,$key,$now),'TRUSTED_SERVER_BINDING_REQUIRED');
expected($gate->inspect($frame,$binding,$auth,'weak',$now),'SERVICE_KEY_UNAVAILABLE');
foreach(array_keys($binding) as $field){
 $fake=[...$frame,$field=>'99999999-9999-4999-8999-999999999999'];
 expected($gate->inspect($fake,$binding,signFrame($fake,$key,$now,$nonce),$key,$now),'SERVER_TENANT_CONTEXT_DENIED');
}
echo "F3-57 PASS: $checks Core HMAC/tenant binding checks, all operational claims false.\n";
