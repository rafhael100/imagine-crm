<?php

declare(strict_types=1);

// PHP 8.3 compatibility smoke without Composer: Laravel 13's current lock
// requires PHP >=8.4.1 for Symfony components, so PHPUnit runs on PHP 8.4.
// This exercise deliberately loads only the pure CRM Core PHP classes.
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadEnvelopeV1Assessment.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadEnvelopeV1Inspector.php';
require_once __DIR__.'/../../../app/Core/Leads/PortalServiceHandoffAssessment.php';
require_once __DIR__.'/../../../app/Core/Leads/PortalServiceHandoffInspector.php';

use App\Core\Leads\PortalServiceHandoffInspector;

$frame = [
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'submission_reference'=>'33333333-3333-4333-8333-333333333333',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$binding = array_diff_key($frame, ['protocol'=>true,'schema_version'=>true,'submission_reference'=>true]);
$key = random_bytes(48);
$nonce = 'abcdefab-cdef-4abc-8abc-abcdefabcdef';
$now=1800000000;
function sign(array $frame, string $domain,string $key,string $nonce,int $iat): array
{
    $parts=[$domain];
    foreach (['organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'] as $name) {
        $parts[]=strtolower($frame[$name]);
    }
    array_push($parts,(string)$iat,(string)($iat+120),$nonce);
    return ['issued_at'=>$iat,'expires_at'=>$iat+120,'nonce'=>$nonce,
        'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)];
}
$inspector = new PortalServiceHandoffInspector();
$valid = sign($frame,'imagine.portal.synthetic-service-request.v1',$key,$nonce,$now);
$matches = $inspector->inspect($frame,$binding,$valid,$now+30,$key,'deliver');
if ($matches->reasonCode!=='LAB_REQUEST_MAC_MATCH_UNTRUSTED'
    || !$matches->isSyntacticallyConsistent()
    || $matches->canAuthenticateService() || $matches->canAcceptLead()
    || $matches->canEnableRoute() || $matches->canAcknowledgeVisitor()) {
    throw new RuntimeException('F3-46 PHP83: consistent fixture must remain inert');
}
$lookup = sign($frame,'imagine.portal.synthetic-crm-lookup.v1',$key,$nonce,$now);
if ($inspector->inspect($frame,$binding,$lookup,$now+30,$key,'deliver')->reasonCode
    !=='AUTH_MAC_MISMATCH') {
    throw new RuntimeException('F3-46 PHP83: domain confused');
}
$forged=$frame;
$forged['organization_reference']='99999999-9999-4999-8999-999999999999';
$forgeAuth=sign($forged,'imagine.portal.synthetic-service-request.v1',$key,$nonce,$now);
if ($inspector->inspect($forged,$binding,$forgeAuth,$now+30,$key,'deliver')->reasonCode
    !=='PINNED_CONTEXT_MISMATCH') {
    throw new RuntimeException('F3-46 PHP83: forged organization accepted');
}
foreach (['name','email','message','consent'] as $field) {
    if ($inspector->inspect(
        [...$frame,$field=>'forbidden'], $binding,$valid,$now+30,$key,'deliver'
    )->reasonCode!=='ENVELOPE_INVALID') {
        throw new RuntimeException('F3-46 PHP83: PII field accepted');
    }
}
echo "F3-46 PHP 8.3 standalone PASS: signed domain, fixed binding, PII denial, no authority.\n";
