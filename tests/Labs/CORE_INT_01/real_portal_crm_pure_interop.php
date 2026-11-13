<?php

declare(strict_types=1);

/**
 * CRM CORE-INT-01: real immutable Portal F117/F119 source + real CRM Core.
 *
 * Pure technical metadata: no PDO connection, HTTP route, request body with
 * PII, cross-repo vendor code copied into app/, service IAM, AUTH, or delivery.
 * Invocation is CI-only on the exact Portal Git revision pinned by workflow.
 */
$root=dirname(__DIR__,3);
foreach ([
    'SyntheticLeadEnvelopeV1Assessment.php',
    'SyntheticLeadEnvelopeV1Inspector.php',
    'PortalServiceHandoffAssessment.php',
    'PortalServiceHandoffInspector.php',
    'PortalTechnicalEnvelopeDecoder.php',
    'PortalIngressPreparationAssessment.php',
    'PortalIngressPreparationBoundary.php',
] as $file) require_once $root.'/app/Core/Leads/'.$file;
$portal=$root.'/portal-source/apps/portal/labs';
require_once $portal.'/commercial-intake-f117/PortalCommercialMetadataInspector.php';
require_once $portal.'/commercial-intake-f119/PortalF119SyntheticServiceReservation.php';

use App\Core\Leads\PortalIngressPreparationBoundary;
use Imagine\Portal\Labs\CommercialIntakeF117\PortalCommercialMetadataInspector as PortalF117;
use Imagine\Portal\Labs\CommercialIntakeF119\PortalF119SyntheticServiceReservation as PortalF119;

if (getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('RUNNER_ENVIRONMENT') !== 'github-hosted'
    || !is_file($portal.'/commercial-intake-f117/PortalCommercialMetadataInspector.php')) {
    throw new RuntimeException('CORE-INT-01 requires pinned GitHub-hosted Portal source only');
}
$assertions=0;
function requireContract(bool $value,string $code): void
{
    global $assertions;
    ++$assertions;
    if(!$value) throw new RuntimeException('CORE-INT-01: '.$code);
}
$frame=[
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'submission_reference'=>'33333333-3333-4333-8333-333333333333',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$binding=$frame;
unset($binding['protocol'],$binding['schema_version'],$binding['submission_reference']);
$now=1800000000;$issued=$now;$expires=$issued+120;
$nonce='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$key=random_bytes(48);
$domain='imagine.portal.synthetic-service-request.v1';
requireContract(PortalF119::DOMAIN===$domain,'Portal F119 signed-domain mismatch');
$portalResult=PortalF117::inspect($frame,$binding);
requireContract($portalResult['reasonCode']==='STRUCTURE_MATCH_UNTRUSTED',
    'real Portal F117 valid synthetic fixture');
foreach(['canAcceptVisitorData','tenantAuthorized','canCreateLead',
    'canQueueOutbox','canDeliverToCrm','crmReceiptProven',
    'canAcknowledgeVisitor','productionAuthorized'] as $flag) {
    requireContract($portalResult[$flag]===false,'Portal F117 must deny '.$flag);
}

// Invoke the ACTUAL Portal F119 signature canonicalization method, without
// constructing PDO, crossing the network, or invoking its SQL reservation.
// Reflection is test-only; never imported into runtime.
$method=new ReflectionMethod(PortalF119::class,'canonical');
$canonical=$method->invoke(null,$frame,$issued,$expires,$nonce);
requireContract(is_string($canonical),'Portal real F119 canonical string');
requireContract(str_starts_with($canonical,$domain."\n"),'Portal F119 request domain');
$auth=[
  'issued_at'=>$issued,'expires_at'=>$expires,'nonce'=>$nonce,
  'mac'=>hash_hmac('sha256',$canonical,$key)
];
$pipeline=new PortalIngressPreparationBoundary();
$json=json_encode($frame,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
$good=$pipeline->inspect($json,$binding,$auth,$now+10,$key,'deliver');
requireContract($good->reasonCode==='LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED',
    'actual Portal F119 MAC accepted by actual CRM Core');
foreach(['canAcceptLead','canAuthenticateService','canPersistLead',
    'canQueueDelivery','canAcknowledgeVisitor','canRetryUnknownOutcome',
    'canOpenPublicRoute'] as $methodName) {
    requireContract($good->$methodName()===false,'CRM never authorizes '.$methodName);
}

$lookup=$pipeline->inspect($json,$binding,$auth,$now+10,$key,'lookup');
requireContract($lookup->reasonCode==='AUTH_MAC_MISMATCH',
    'Portal delivery MAC cannot be repurposed as lookup MAC');
$repeat=$pipeline->inspect($json,$binding,$auth,$now+10,$key,'deliver');
requireContract($repeat->reasonCode==='LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED'
    && !$repeat->canAcceptLead(),'repeat MAC has no persistent nonce evidence');

// Even if a synthetic signer knows the fixture key, a forged tenant/site/
// destination fails on both independent structural binding boundaries.
foreach(['organization_reference','binding_reference','site_reference',
    'form_reference','destination_reference'] as $field) {
    $forged=$frame;
    $forged[$field]='77777777-7777-4777-8777-777777777777';
    $portalRejected=PortalF117::inspect($forged,$binding);
    requireContract($portalRejected['reasonCode']==='PINNED_BINDING_CONFLICT',
        'Portal independently rejects forged '.$field);
    $forgedCanonical=$method->invoke(null,$forged,$issued,$expires,$nonce);
    $forgedAuth=[...$auth,'mac'=>hash_hmac('sha256',$forgedCanonical,$key)];
    $crmRejected=$pipeline->inspect(
        json_encode($forged,JSON_THROW_ON_ERROR),$binding,$forgedAuth,
        $now+10,$key,'deliver'
    );
    requireContract($crmRejected->reasonCode==='PINNED_CONTEXT_MISMATCH',
        'CRM independently rejects forged '.$field);
    requireContract(!$crmRejected->canAcceptLead(),'forged '.$field.' remains denied');
}
foreach (['email'=>'person@example.invalid','phone'=>'5550199',
    'message'=>'a fictitious note','consent'=>true,'payload'=>['extra'=>'no']] as $keyName=>$value) {
    $p=PortalF117::inspect([...$frame,$keyName=>$value],$binding);
    requireContract($p['reasonCode']==='ENVELOPE_STRUCTURE_INVALID',
        'Portal rejects extra '.$keyName);
    $c=$pipeline->inspect(json_encode([...$frame,$keyName=>$value],
        JSON_THROW_ON_ERROR),$binding,$auth,$now+10,$key,'deliver');
    requireContract($c->reasonCode==='TECHNICAL_JSON_REJECTED',
        'CRM rejects extra '.$keyName);
}
$duplicate='{"\u0070rotocol":"fake",'.substr($json,1);
$bad=$pipeline->inspect($duplicate,$binding,$auth,$now+10,$key,'deliver');
requireContract($bad->reasonCode==='DUPLICATE_JSON_KEY_REJECTED',
    'CRM parser rejects JSON key alias by escaped Unicode');
requireContract(!$bad->canAcceptLead(),'duplicate metadata cannot authorize');

echo "CORE-INT-01 real pinned Portal PHP F117/F119 and CRM app/Core PHP PASS: {$assertions} checks, no I/O, no PII, no ALLOW.\n";
