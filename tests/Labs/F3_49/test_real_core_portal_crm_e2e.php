<?php

declare(strict_types=1);

/**
 * F3-49 — REAL CRM application-core JSON/HMAC/pinned-context code with
 * REAL Portal F119 PHP and REAL CRM F3-41 test HTTP, in one Github-hosted
 * CI run against two independent disposable PG18/PG16 containers.
 *
 * NO production API, lead payload, real tenant IAM, KMS or business ACK.
 */
$root = dirname(__DIR__,3);
foreach ([
    'SyntheticLeadEnvelopeV1Assessment.php',
    'SyntheticLeadEnvelopeV1Inspector.php',
    'PortalServiceHandoffAssessment.php',
    'PortalServiceHandoffInspector.php',
    'PortalTechnicalEnvelopeDecoder.php',
    'PortalIngressPreparationAssessment.php',
    'PortalIngressPreparationBoundary.php',
    'LeadSubmissionIdentity.php',
    'SyntheticIdempotencyClaim.php',
    'SyntheticLeadReceiptMacAssessment.php',
    'SyntheticLeadReceiptMacInspector.php',
] as $name) {
    require_once $root.'/app/Core/Leads/'.$name;
}
require_once $root.'/portal-source/apps/portal/labs/commercial-intake-f119/PortalF119SyntheticServiceReservation.php';

use App\Core\Leads\PortalIngressPreparationBoundary;
use App\Core\Leads\PortalTechnicalEnvelopeDecoder;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadReceiptMacInspector;
use Imagine\Portal\Labs\CommercialIntakeF119\PortalF119SyntheticServiceReservation as Portal;

if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F342_CROSS_REPO_LAB')!=='1'
    || getenv('CRM_F341_HTTP_LAB')!=='1'
    || getenv('PORTAL_F119_PG_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='portal_commercial_lab') {
    throw new RuntimeException('F3-49 requires disposable pinned cross-repo CI only');
}
$requestHex=getenv('CRM_F341_REQUEST_KEY_HEX');
$receiptHex=getenv('CRM_F341_RECEIPT_KEY_HEX');
if (!is_string($requestHex)||!is_string($receiptHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$requestHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$receiptHex)
    || hash_equals($requestHex,$receiptHex)) {
    throw new RuntimeException('F3-49 requires independent generated CI HMAC keys');
}
$requestKey=hex2bin($requestHex);
$receiptKey=hex2bin($receiptHex);
if (!is_string($requestKey)||!is_string($receiptKey)) {
    throw new RuntimeException('F3-49 invalid key fixture');
}
$checks=0;
function check(bool $ok,string $description): void
{
    global $checks;
    ++$checks;
    if (!$ok)throw new RuntimeException('CRM F3-49 FAIL: '.$description);
}
function envelope(string $submission): array
{
    return [
        'protocol'=>'imagine.lead-intake',
        'schema_version'=>1,
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'submission_reference'=>$submission,
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666'
    ];
}
function binding(): array
{
    // Important: server-side fixture MUST NOT be extracted from the
    // untrusted inbound body, even when the client knows the test key.
    return [
        'organization_reference'=>'11111111-1111-4111-8111-111111111111',
        'binding_reference'=>'22222222-2222-4222-8222-222222222222',
        'site_reference'=>'44444444-4444-4444-8444-444444444444',
        'form_reference'=>'55555555-5555-4555-8555-555555555555',
        'destination_reference'=>'66666666-6666-4666-8666-666666666666'
    ];
}
function auth(array $frame,string $key,string $domain,string $nonce,int $issued): array
{
    $expires=$issued+120;
    $parts=[$domain];
    foreach (['organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'] as $name) {
        $parts[]=strtolower($frame[$name]);
    }
    array_push($parts,(string)$issued,(string)$expires,$nonce);
    return [
        'issued_at'=>$issued,'expires_at'=>$expires,'nonce'=>$nonce,
        'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)
    ];
}
function pg(string $name,int $port): PDO
{
    check(($name==='portal_commercial_lab' && $port===5433)
        || ($name==='imagine_crm_handoff_ci' && $port===5432),
        'strict mapping of two independent CI servers');
    $db=new PDO(
        'pgsql:host=127.0.0.1;port='.$port.';dbname='.$name,
        'postgres',(string)getenv('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES=>false]
    );
    check($db->query('SELECT current_database()')->fetchColumn()===$name,
        'DB name verified independently');
    return $db;
}
function countDb(PDO $db,string $schema,string $table,string $submission): int
{
    $allow=($schema==='portal_commercial_ci'
        && in_array($table,['metadata_ledger','pending_outbox'],true))
      || ($schema==='crm_portal_handoff_ci'
        && in_array($table,['metadata_receipts','request_nonces'],true));
    check($allow,'only allowed metadata CI tables');
    $s=$db->prepare('SELECT count(*) FROM '.$schema.'.'.$table.
       ' WHERE organization_reference=CAST(:org AS uuid) AND submission_reference=CAST(:sub AS uuid)');
    $s->execute([
        'org'=>'11111111-1111-4111-8111-111111111111','sub'=>$submission
    ]);
    return (int)$s->fetchColumn();
}
function http(array $frame,array $auth,string $route='/__lab/crm/ingest'): array
{
    $headers=[
        'Content-Type: application/json',
        'Connection: close',
        'X-Lab-Issued-At: '.$auth['issued_at'],
        'X-Lab-Expires-At: '.$auth['expires_at'],
        'X-Lab-Nonce: '.$auth['nonce'],
        'X-Lab-Signature: '.$auth['mac'],
    ];
    $ctx=stream_context_create([
        'http'=>[
            'method'=>'POST',
            'header'=>implode("\r\n",$headers),
            'content'=>json_encode($frame,JSON_THROW_ON_ERROR),
            'ignore_errors'=>true,
            'timeout'=>8,
            'follow_location'=>0,
        ]
    ]);
    $raw=@file_get_contents('http://127.0.0.1:19141'.$route,false,$ctx);
    $headersReceived=$http_response_header??[];
    check(is_string($raw)&&isset($headersReceived[0]),'local CRM response present');
    check(preg_match('/\AHTTP\/[0-9.]+\s+([0-9]{3})\b/',$headersReceived[0],$match)===1,
        'CRM status parse');
    $decoded=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
    check(is_array($decoded)
       && $decoded['schema']==='imagine.crm.synthetic-portal-http.v1',
       'actual F3-41 CRM PHP route responded');
    foreach (['realCrmPersisted','tenantAuthenticatedForProduction',
        'canAcknowledgeVisitor','canAutoRetryUnknownOutcome','productionAuthorized'] as $flag) {
        check($decoded[$flag]===false,'no operational claim: '.$flag);
    }
    return [(int)$match[1],$decoded];
}
function inspectReceipt(array $receipt,array $frame,string $receiptKey,int $now): string
{
    $identity=new LeadSubmissionIdentity(
        $frame['organization_reference'],$frame['binding_reference'],$frame['submission_reference']
    );
    $claim=new SyntheticIdempotencyClaim(
        $identity,$frame['site_reference'],$frame['form_reference'],$frame['destination_reference']
    );
    $sig=(new SyntheticLeadReceiptMacInspector())->inspect(
        $claim,$claim,$receipt['issued_at'],$receipt['expires_at'],$now,
        $receipt['nonce'],$receipt['mac'],$receiptKey
    );
    check(!$sig->canAcknowledgeDurableReceipt(),'mock receipt cannot authorize live CRM ACK');
    return $sig->reasonCode;
}

$source=pg('portal_commercial_lab',5433);
$receiver=pg('imagine_crm_handoff_ci',5432);
check((int)$source->query(
    "SELECT count(*) FROM information_schema.tables WHERE table_schema='crm_portal_handoff_ci'"
)->fetchColumn()===0,'no CRM schema in Portal PG18');
check((int)$receiver->query(
    "SELECT count(*) FROM information_schema.tables WHERE table_schema='portal_commercial_ci'"
)->fetchColumn()===0,'no Portal schema in CRM PG16');

$pipeline=new PortalIngressPreparationBoundary();
$portalKey=random_bytes(48);
$timestamp=time();
$sub='f9f9f9f9-f9f9-4f9f-8f9f-f9f9f9f9f9f9';
$frame=envelope($sub);
$raw=json_encode($frame,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
$sent=auth($frame,$requestKey,'imagine.portal.synthetic-service-request.v1',
    'c9c9c9c9-c9c9-4c9c-8c9c-c9c9c9c9c9c9',$timestamp);
$assessment=$pipeline->inspect(
    $raw,binding(),$sent,$timestamp+2,$requestKey,'deliver'
);
check($assessment->reasonCode==='LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED',
    'actual CRM F3-48 app/Core accepts actual Portal-compatible technical frame');
check(!$assessment->canAuthenticateService()&&!$assessment->canAcceptLead()
    && !$assessment->canQueueDelivery()&&!$assessment->canPersistLead(),
    'app Core pipeline is inert despite syntactic match');
check((new PortalTechnicalEnvelopeDecoder())->decode($raw)===$frame,
    'real CRM F3-47 lexer round-trips Portal compatible metadata');
check(countDb($receiver,'crm_portal_handoff_ci','metadata_receipts',$sub)===0,
    'CRM not yet written');

$portalSigned=auth($frame,$portalKey,Portal::DOMAIN,
    'd9d9d9d9-d9d9-4d9d-8d9d-d9d9d9d9d9d9',$timestamp);
$portal=new Portal($portalKey);
$stage=$portal->observe($source,$frame,binding(),$portalSigned);
check($stage['reasonCode']==='LAB_METADATA_AND_OUTBOX_WRITTEN',
    'real Portal F119 staged metadata and outbox before send');
check($stage['crmPersisted']===false,'Portal staging not CRM receipt');
check(countDb($source,'portal_commercial_ci','pending_outbox',$sub)===1,
    'source outbox exists');

$delivered=http($frame,$sent);
check($delivered[0]===202
    && $delivered[1]['reasonCode']==='LAB_MOCK_COMMITTED_FIRST',
    'real CRM F3-41 accepted signed frame and committed independent PG16 metadata');
check(isset($delivered[1]['receipt'])&&count($delivered[1]['receipt'])===4,
    'mock HTTP receipt has exactly four metadata fields');
check(inspectReceipt($delivered[1]['receipt'],$frame,$receiptKey,$timestamp+2)
    ==='LAB_MAC_MATCH_UNTRUSTED','real CRM receipt inspector matches mock PG16 receipt');
check(inspectReceipt($delivered[1]['receipt'],$frame,$requestKey,$timestamp+2)
    ==='MAC_MISMATCH','service request key cannot verify receipt');
check(countDb($receiver,'crm_portal_handoff_ci','metadata_receipts',$sub)===1,
    'CRM DB independent one logical submission');
check(countDb($receiver,'crm_portal_handoff_ci','request_nonces',$sub)===1,
    'CRM DB independent one attempt nonce');

$replayed=http($frame,$sent);
check($replayed[0]===409
    && $replayed[1]['reasonCode']==='REQUEST_NONCE_REPLAY',
    'actual CRM HTTP rejects second use of same nonce');
check(countDb($receiver,'crm_portal_handoff_ci','metadata_receipts',$sub)===1,
    'no duplicated CRM logical record');

// A forged destination/organization must fail in the new application Core,
// in the actual Portal F119 PHP and in the actual CRM F3-41 HTTP receiver,
// even when the attacker knows this *synthetic* request key.
foreach (['organization_reference','binding_reference','site_reference',
    'form_reference','destination_reference'] as $key) {
    $forged=$frame;
    $forged[$key]='77777777-7777-4777-8777-777777777777';
    $authForForged=auth($forged,$requestKey,
        'imagine.portal.synthetic-service-request.v1',
        'e8e8e8e8-e8e8-4e8e-8e8e-e8e8e8e8e8e8',$timestamp);
    $denied=$pipeline->inspect(
        json_encode($forged,JSON_THROW_ON_ERROR),binding(),$authForForged,
        $timestamp+2,$requestKey,'deliver'
    );
    check($denied->reasonCode==='PINNED_CONTEXT_MISMATCH',
        'app Core rejects forged '.$key);
    $forgedPortalAuth=auth($forged,$portalKey,Portal::DOMAIN,
        'e9e9e9e9-e9e9-4e9e-8e9e-e9e9e9e9e9e9',$timestamp);
    $portalDenied=$portal->observe($source,$forged,binding(),$forgedPortalAuth);
    check($portalDenied['reasonCode']==='PINNED_METADATA_REJECTED',
        'real Portal refuses forged '.$key);
    $httpDenied=http($forged,$authForForged);
    check($httpDenied[0]===403
        && $httpDenied[1]['reasonCode']==='PINNED_CONTEXT_CONFLICT',
        'real CRM HTTP refuses forged '.$key);
}
$duplicate='{"\u0070rotocol":"bad",'.substr($raw,1);
check($pipeline->inspect($duplicate,binding(),$sent,
    $timestamp+2,$requestKey,'deliver')->reasonCode==='DUPLICATE_JSON_KEY_REJECTED',
    'CRM Core refuses escaped duplicate before any DB access');
$withEmail=json_encode([...$frame,'email'=>'synthetic@example.invalid'],JSON_THROW_ON_ERROR);
check($pipeline->inspect($withEmail,binding(),$sent,
    $timestamp+2,$requestKey,'deliver')->reasonCode==='TECHNICAL_JSON_REJECTED',
    'CRM Core rejects personal data in metadata envelope');
check(countDb($receiver,'crm_portal_handoff_ci','metadata_receipts',$sub)===1,
    'all negative probes left the original mock receiver record intact');
echo "CRM F3-49 PASS: {$checks} checks of actual Portal F119 + CRM app Core F3-46/47/48 + CRM F3-41 HTTP, independent PG18/PG16; NO real lead.\n";
