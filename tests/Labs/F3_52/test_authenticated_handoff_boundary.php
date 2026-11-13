<?php
declare(strict_types=1);
require_once __DIR__.'/AuthenticatedHandoffBoundary.php';

use Tests\Labs\F3_52\AuthenticatedHandoffBoundary;

$key = random_bytes(48);
$now = 1763000000;
$binding = [
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$frame = [
    'protocol'=>'imagine.lead-intake', 'schema_version'=>1,
    'organization_reference'=>$binding['organization_reference'],
    'binding_reference'=>$binding['binding_reference'],
    'submission_reference'=>'f9f9f9f9-f9f9-4f9f-8f9f-f9f9f9f9f9f9',
    'site_reference'=>$binding['site_reference'],
    'form_reference'=>$binding['form_reference'],
    'destination_reference'=>$binding['destination_reference'],
];
function authFrame(array $frame, string $key, int $now): array {
    $nonce='77777777-7777-4777-8777-777777777777';
    $fields=['organization_reference','binding_reference','site_reference','form_reference','destination_reference'];
    $parts=['imagine.portal.operational-preparation.v1'];
    foreach($fields as $field) $parts[]=$frame[$field];
    $parts[]=$frame['submission_reference'];
    array_push($parts, (string)$now, (string)($now+60), $nonce);
    return ['issued_at'=>$now,'expires_at'=>$now+60,'nonce'=>$nonce,'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)];
}
$checks=0;
function verifyResult(array $result, string $reason): void {
    global $checks;
    ++$checks;
    if (($result['reasonCode']??'')!==$reason) throw new RuntimeException("FAIL: expected $reason got ".($result['reasonCode']??'missing'));
    foreach(['serviceAuthenticatedForProduction','tenantAuthenticatedForProduction','nonceClaimedDurably','leadPersisted','canAcknowledgeVisitor','canDeploy'] as $flag) {
        if (($result[$flag]??null)!==false) throw new RuntimeException("unsafe flag: $flag");
    }
}
$boundary=new AuthenticatedHandoffBoundary();
$auth=authFrame($frame,$key,$now);
verifyResult($boundary->inspect($frame,$binding,$auth,$key,$now),'SIGNED_FRAME_REQUIRES_ATOMIC_NONCE_CLAIM');
$mutations = [
    [$frame,$binding,[...$auth,'mac'=>str_repeat('0',64)],$key,$now,'AUTHENTICATION_REJECTED'],
    [$frame,$binding,$auth,$key,$now+121,'AUTHENTICATION_REJECTED'],
    [$frame,$binding,$auth,random_bytes(48),$now,'AUTHENTICATION_REJECTED'],
    [$frame,$binding,[...$auth,'issued_at'=>$now+90],$key,$now,'AUTHENTICATION_REJECTED'],
    [$frame,$binding,[...$auth,'nonce'=>'invalid'], $key,$now,'AUTHENTICATION_REJECTED'],
    [[...$frame,'email'=>'private@example.invalid'],$binding,$auth,$key,$now,'CONTRACT_REJECTED'],
    [[...$frame,'schema_version'=>2],$binding,$auth,$key,$now,'CONTRACT_REJECTED'],
    [[...$frame,'organization_reference'=>'99999999-9999-4999-8999-999999999999'],$binding,$auth,$key,$now,'PINNED_CONTEXT_REJECTED'],
    [$frame,[...$binding,'destination_reference'=>'99999999-9999-4999-8999-999999999999'],$auth,$key,$now,'PINNED_CONTEXT_REJECTED'],
    [$frame,[],$auth,$key,$now,'SERVER_BINDING_REQUIRED'],
    [$frame,$binding,$auth,'weak',$now,'SERVICE_KEY_UNAVAILABLE'],
];
foreach($mutations as [$f,$b,$a,$k,$t,$reason]) verifyResult($boundary->inspect($f,$b,$a,$k,$t),$reason);
foreach(array_keys($binding) as $field) {
    $f=$frame;
    $f[$field]='99999999-9999-4999-8999-999999999999';
    verifyResult($boundary->inspect($f,$binding,authFrame($f,$key,$now),$key,$now),'PINNED_CONTEXT_REJECTED');
}
echo "F3-52 PASS: $checks positive/negative checks; no operational claims or persistence.\n";
