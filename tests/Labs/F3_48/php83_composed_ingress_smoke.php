<?php

declare(strict_types=1);

// F3-48 standalone PHP 8.3+ smoke, no Composer or Laravel dependencies.
foreach ([
    'SyntheticLeadEnvelopeV1Assessment.php',
    'SyntheticLeadEnvelopeV1Inspector.php',
    'PortalServiceHandoffAssessment.php',
    'PortalServiceHandoffInspector.php',
    'PortalTechnicalEnvelopeDecoder.php',
    'PortalIngressPreparationAssessment.php',
    'PortalIngressPreparationBoundary.php',
] as $file)require_once __DIR__.'/../../../app/Core/Leads/'.$file;

use App\Core\Leads\PortalIngressPreparationBoundary;

$frame=[
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'submission_reference'=>'33333333-3333-4333-8333-333333333333',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666'
];
$server=$frame;
unset($server['protocol'],$server['schema_version'],$server['submission_reference']);
$key=random_bytes(48);$nonce='abababab-abab-4aba-8aba-abababababab';
$iat=1800000000;$exp=$iat+120;
function auth(array $frame,string $key,string $nonce,int $iat,int $exp): array
{
    $parts=['imagine.portal.synthetic-service-request.v1'];
    foreach (['organization_reference','binding_reference','submission_reference',
      'site_reference','form_reference','destination_reference'] as $f)$parts[]=$frame[$f];
    array_push($parts,(string)$iat,(string)$exp,$nonce);
    return ['issued_at'=>$iat,'expires_at'=>$exp,'nonce'=>$nonce,
      'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)];
}
$boundary=new PortalIngressPreparationBoundary();
$goodAuth=auth($frame,$key,$nonce,$iat,$exp);
$raw=json_encode($frame,JSON_THROW_ON_ERROR);
$good=$boundary->inspect($raw,$server,$goodAuth,$iat+10,$key,'deliver');
if ($good->reasonCode!=='LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED'
    || $good->canAuthenticateService() || $good->canAcceptLead()
    || $good->canPersistLead() || $good->canQueueDelivery()) {
    throw new RuntimeException('F3-48 PHP8.3 failed-closed outcome invalid');
}
$duplicate='{"\u0070rotocol":"fake",'.substr($raw,1);
if ($boundary->inspect($duplicate,$server,$goodAuth,$iat+10,$key,'deliver')->reasonCode
    !=='DUPLICATE_JSON_KEY_REJECTED') {
    throw new RuntimeException('F3-48 PHP8.3 accepted escaped duplicate key');
}
$forged=$frame;
$forged['organization_reference']='99999999-9999-4999-8999-999999999999';
$forgedAuth=auth($forged,$key,$nonce,$iat,$exp);
if ($boundary->inspect(json_encode($forged,JSON_THROW_ON_ERROR),$server,
    $forgedAuth,$iat+10,$key,'deliver')->reasonCode!=='PINNED_CONTEXT_MISMATCH') {
    throw new RuntimeException('F3-48 PHP8.3 accepted changed organization');
}
if ($boundary->inspect($raw,$server,$goodAuth,$iat+10,$key,'lookup')->reasonCode
    !=='AUTH_MAC_MISMATCH') {
    throw new RuntimeException('F3-48 PHP8.3 reused MAC in other domain');
}
echo "F3-48 PHP8.3 standalone PASS: compositional JSON+HMAC+binding gate stays inert.\n";
