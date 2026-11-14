<?php
declare(strict_types=1);
/**
 * F3-AIA-F227: actual PHP signer from SHA-pinned Agents to original CRM
 * verifier. Public, fictitious key; no live IAM, route, SQL, network or grant.
 */
use App\Support\Synthetic\{
    AiaReadOnlyMockBoundary, AiaHmacFixturePreflight, AiaFixtureNonceLedger
};
use ImagineAI\MockBridge\MockCrmReadRequest;

$root=dirname(__DIR__,2);
$agents=$root.'/_synthetic_agents_fixture';
foreach ([
    $root.'/app/Support/Synthetic/AiaReadOnlyMockBoundary.php',
    $root.'/app/Support/Synthetic/AiaFixtureNonceLedger.php',
    $root.'/app/Support/Synthetic/AiaHmacFixturePreflight.php',
] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR,"CRM_AIA_F227_ORIGINAL_SOURCES_MISSING_NO_GO\n");
        exit(2);
    }
    require_once $file;
}
spl_autoload_register(static function(string $class) use ($agents): void {
    $prefix='ImagineAI\\';
    if (str_starts_with($class,$prefix)) {
        $file=$agents.'/src/'.str_replace('\\','/',
            substr($class,strlen($prefix))).'.php';
        if (is_file($file)) require $file;
    }
});
function f227CrmAssert(bool $ok): void {
    if (!$ok) throw new RuntimeException('CRM_AIA_F227_DENIED');
}
try {
    $key='SYNTHETIC_PUBLIC_TEST_ONLY_CRM_F227_RECIPROCAL_KNOWN_KEY_123456';
    $wire=[
        'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
        'request_ref'=>'SYNTHETIC_F227_RECIPROCAL_REQ',
        'operation'=>'crm.lead.read_minimal',
        'tenant_ref'=>'SYNTHETIC_TENANT_A',
        'principal_ref'=>'SYNTHETIC_PRINCIPAL',
        'resource_ref'=>'SYNTHETIC_LEAD_A',
        'purpose_ref'=>'SYNTHETIC_TEST',
        'fields'=>['ref','stage'],
    ];
    $issued=MockCrmReadRequest::issueSyntheticSignedFixture(
        $wire,'SYNTHETIC_NONCE_RECIPROCAL_A',1794690000,1794690025,$key
    );
    f227CrmAssert(array_keys($issued)===['body','proof']);
    $body=$issued['body'];
    $proof=$issued['proof'];
    f227CrmAssert($body===MockCrmReadRequest::encodeCanonicalJson($wire));
    f227CrmAssert(MockCrmReadRequest::decodeCanonicalJson($body)===$wire);
    f227CrmAssert(AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($body));

    $ledger=new AiaFixtureNonceLedger(2);
    $check=static fn(
        string $b,array $p,AiaFixtureNonceLedger $l,
        int $now=1794690010,string $tenant='SYNTHETIC_TENANT_A',
        string $givenKey='',
    ): bool => AiaHmacFixturePreflight::matchesFixture(
        $b,$p,$tenant,$now,$givenKey===''?$key:$givenKey,$l
    );
    f227CrmAssert($check($body,$proof,$ledger));
    f227CrmAssert($ledger->countConsumed()===1);
    f227CrmAssert(!$check($body,$proof,$ledger));

    $denial=AiaReadOnlyMockBoundary::inspectCanonicalJson($body);
    f227CrmAssert($denial===[
        'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
        'state'=>'DENIED_OR_NOT_FOUND',
        'projection'=>null,'real_authorization'=>false,'external_calls'=>0,
    ]);
    $second=MockCrmReadRequest::issueSyntheticSignedFixture(
        $wire,'SYNTHETIC_NONCE_RECIPROCAL_B',1794690000,1794690025,$key
    );
    f227CrmAssert($second['body']===$body
        && $second['proof']['signature']!==$proof['signature']);
    f227CrmAssert(AiaHmacFixturePreflight::inspectFixture(
        $second['body'],$second['proof'],'SYNTHETIC_TENANT_A',
        1794690010,$key,$ledger
    )===$denial);
    f227CrmAssert($ledger->countConsumed()===2);

    $mutatedBody=str_replace('SYNTHETIC_LEAD_A','SYNTHETIC_LEAD_B',$body);
    $duplicate=substr($body,0,-1).',"tenant_ref":"SYNTHETIC_TENANT_A"}';
    $wrongIssuer=$proof; $wrongIssuer['issuer']='SYNTHETIC_UNTRUSTED';
    $wrongAudience=$proof; $wrongAudience['audience']='SYNTHETIC_OTHER_APP';
    $wrongNonce=$proof; $wrongNonce['nonce']='SYNTHETIC_NONCE_REWRITE';
    $badSig=$proof; $badSig['signature']=str_repeat('0',64);
    $badDigest=$proof; $badDigest['body_sha256']=str_repeat('0',64);
    $extra=$proof; $extra['is_admin']=true;
    $invalid=[
        'TAMPER_BODY'=>[$mutatedBody,$proof,1794690010,'SYNTHETIC_TENANT_A',$key],
        'DUPLICATE_KEY'=>[$duplicate,$proof,1794690010,'SYNTHETIC_TENANT_A',$key],
        'WRONG_ISSUER'=>[$body,$wrongIssuer,1794690010,'SYNTHETIC_TENANT_A',$key],
        'WRONG_AUDIENCE'=>[$body,$wrongAudience,1794690010,'SYNTHETIC_TENANT_A',$key],
        'WRONG_NONCE'=>[$body,$wrongNonce,1794690010,'SYNTHETIC_TENANT_A',$key],
        'WRONG_TENANT'=>[$body,$proof,1794690010,'SYNTHETIC_TENANT_B',$key],
        'WRONG_KEY'=>[$body,$proof,1794690010,'SYNTHETIC_TENANT_A',
            'SYNTHETIC_PUBLIC_TEST_ONLY_DIFFERENT_KNOWN_KEY_123456789'],
        'NOT_YET_VALID'=>[$body,$proof,1794689999,'SYNTHETIC_TENANT_A',$key],
        'EXPIRED'=>[$body,$proof,1794690025,'SYNTHETIC_TENANT_A',$key],
        'BAD_SIGNATURE'=>[$body,$badSig,1794690010,'SYNTHETIC_TENANT_A',$key],
        'BAD_DIGEST'=>[$body,$badDigest,1794690010,'SYNTHETIC_TENANT_A',$key],
        'EXTRA_CLAIM'=>[$body,$extra,1794690010,'SYNTHETIC_TENANT_A',$key],
        'TRAILING_JSON'=>[$body.'{}',$proof,1794690010,'SYNTHETIC_TENANT_A',$key],
    ];
    $failures=0;
    foreach ($invalid as [$badBody,$badProof,$now,$tenant,$givenKey]) {
        $newLedger=new AiaFixtureNonceLedger();
        f227CrmAssert(!$check($badBody,$badProof,$newLedger,$now,$tenant,$givenKey));
        f227CrmAssert($newLedger->countConsumed()===0);
        ++$failures;
    }
    f227CrmAssert($failures===13);

    // Prove and prominently expose the reason this object cannot be used
    // for real multi-worker replay protection: another instance has no
    // knowledge of the first instance's consumed nonce.
    $otherInstance=new AiaFixtureNonceLedger();
    f227CrmAssert($check($body,$proof,$otherInstance));
    f227CrmAssert($otherInstance->countConsumed()===1);

    echo "CRM_AIA_F227_ORIGINAL_SIGNER_PASS negatives=$failures ".
        "cross_instance_replay_unsafe=true operational_decision=NO_GO ".
        "real_authorization=false external_calls=0\n";
} catch(Throwable) {
    fwrite(STDERR,"CRM_AIA_F227_ORIGINAL_SIGNER_DENIED operational_decision=NO_GO\n");
    exit(1);
}
