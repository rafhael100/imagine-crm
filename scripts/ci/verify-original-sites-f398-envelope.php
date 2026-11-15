<?php

declare(strict_types=1);

/**
 * CRM-SITES-F398: run the ORIGINAL Sites F398 emitter against ORIGINAL
 * CRM PG05 canonicalization (no reimplementation of either method).
 * No DB, HTTP, credentials, real PII or operational effects.
 */
if (PHP_SAPI !== 'cli' ||
    getenv('GITHUB_ACTIONS') !== 'true' ||
    getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm' ||
    getenv('CRM_SITES_F398_CI') !== '1' ||
    getenv('SITES_F398_CRM_CAPTURE_CI') !== '1') {
    fwrite(STDERR, "CRM/Sites F398 is only executable in disposable CI.\n");
    exit(2);
}
$sitesPath = getenv('F398_SITES_SOURCE');
if (!is_string($sitesPath) || $sitesPath === '' ||
    !is_file($sitesPath.'/src/Integration/SyntheticCrmCaptureEnvelope.php')) {
    throw new RuntimeException('Original pinned Sites F398 emitter unavailable.');
}

require_once $sitesPath.'/src/Integration/SyntheticCrmCaptureEnvelope.php';
require_once __DIR__.'/../../app/Core/Leads/LeadSubmissionIdentity.php';
require_once __DIR__.'/../../app/Core/Leads/SyntheticIdempotencyClaim.php';
require_once __DIR__.'/../../app/Core/Leads/LaboratorySignedSiteCapture.php';

use Imagine\Sites\Integration\SyntheticCrmCaptureEnvelope as SitesEmitter;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\LaboratorySignedSiteCapture as CrmReceiver;

$count=0;
function check398(bool $ok, string $what): void {
    global $count;
    if (!$ok) throw new RuntimeException('CRM/Sites F398 FAIL: '.$what);
    ++$count;
    echo 'PASS '.$what."\n";
}
function crmCanonical398(array $wire): string {
    $identity=new LeadSubmissionIdentity(
        $wire['organizationReference'],
        $wire['bindingReference'],
        $wire['submissionReference']
    );
    $claim=new SyntheticIdempotencyClaim(
        $identity,
        $wire['siteReference'],
        $wire['formReference'],
        $wire['destinationReference']
    );
    return CrmReceiver::canonicalForCi(
        $claim,
        $wire['leadId'],
        $wire['syntheticTitle'],
        $wire['sourceCode'],
        $wire['nonce'],
        $wire['issuedAt'],
        $wire['expiresAt']
    );
}

$now=1790000000;
$base=[
    'organizationReference'=>'A1111111-1111-4111-8111-111111111111',
    'bindingReference'=>'a2222222-2222-4222-8222-222222222222',
    'submissionReference'=>'a3333333-3333-4333-8333-333333333333',
    'siteReference'=>'a4444444-4444-4444-8444-444444444444',
    'formReference'=>'a5555555-5555-4555-8555-555555555555',
    'destinationReference'=>'a6666666-6666-4666-8666-666666666666',
    'leadId'=>'a7777777-7777-4777-8777-777777777777',
    'syntheticTitle'=>'DEMO_F398_01',
    'sourceCode'=>'onepage',
    'nonce'=>'a8888888-8888-4888-8888-888888888888',
    'issuedAt'=>$now-10,
    'expiresAt'=>$now+40,
];
check398(hash_equals(
    SitesEmitter::CI_KEY,
    getenv('CRM_LEADS_SIGNING_KEY_CI') ?: ''
), 'only the original public fake CI signing key used');
foreach (['onepage','landing','sites'] as $source) {
    $input=$base;
    $input['sourceCode']=$source;
    $wire=SitesEmitter::sign($input,$now);
    $crmCanonical=crmCanonical398($wire);
    check398(hash_equals(SitesEmitter::canonical($wire),$crmCanonical),
        'original Sites and CRM produce byte-identical canonical input for '.$source);
    check398(hash_equals($wire['signature'],
        hash_hmac('sha256',$crmCanonical,SitesEmitter::CI_KEY)),
        'original CRM verifies Site-origin HMAC for '.$source);
    check398($wire===SitesEmitter::sign($input,$now),
        'same event retried without drifting payload for '.$source);
}
$wire=SitesEmitter::sign($base,$now);
$changes=[
    'organizationReference'=>'b1111111-1111-4111-8111-111111111111',
    'bindingReference'=>'b2222222-2222-4222-8222-222222222222',
    'submissionReference'=>'b3333333-3333-4333-8333-333333333333',
    'siteReference'=>'b4444444-4444-4444-8444-444444444444',
    'formReference'=>'b5555555-5555-4555-8555-555555555555',
    'destinationReference'=>'b6666666-6666-4666-8666-666666666666',
    'leadId'=>'b7777777-7777-4777-8777-777777777777',
    'syntheticTitle'=>'DEMO_F398_02',
    'sourceCode'=>'landing',
    'nonce'=>'b8888888-8888-4888-8888-888888888888',
    'issuedAt'=>$now-11,
    'expiresAt'=>$now+39,
];
foreach ($changes as $field=>$replacement) {
    $tampered=$wire;
    $tampered[$field]=$replacement;
    check398(!hash_equals($wire['signature'],
        hash_hmac('sha256',crmCanonical398($tampered),SitesEmitter::CI_KEY)),
        'original CRM canonicalization refuses tampering of '.$field);
}
$another=$base;
$another['submissionReference']='c3333333-3333-4333-8333-333333333333';
$otherWire=SitesEmitter::sign($another,$now);
check398(!hash_equals($wire['signature'],$otherWire['signature']),
    'distinct lead submission cannot collapse into same signed event');
putenv('SITES_F398_CRM_CAPTURE_CI=0');
try { SitesEmitter::sign($base,$now); throw new RuntimeException('Unsafe non-CI signer was not denied.'); }
catch (LogicException) { check398(true,'Sites emitter fails closed without explicit CI flag'); }
echo $count." original Sites / original CRM canonical interoperability checks passed; NO operational authority.\n";
