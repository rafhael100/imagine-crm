<?php
declare(strict_types=1);
/** F3-AIA-HMAC offline-only, PHP 8.3/8.4, no Laravel or external services. */
require dirname(__DIR__,2).'/app/Support/Synthetic/AiaReadOnlyMockBoundary.php';
require dirname(__DIR__,2).'/app/Support/Synthetic/AiaFixtureNonceLedger.php';
require dirname(__DIR__,2).'/app/Support/Synthetic/AiaHmacFixturePreflight.php';

use App\Support\Synthetic\{
    AiaReadOnlyMockBoundary, AiaHmacFixturePreflight, AiaFixtureNonceLedger
};

function f3Check(bool $yes): void {
    if (!$yes) throw new RuntimeException('AIA_HMAC_FIXTURE_DENIED');
}
function f3Proof(string $json, string $nonce, string $key): array {
    $proof = [
        'issuer' => 'SYNTHETIC_AGENT_SERVICE',
        'audience' => 'SYNTHETIC_CRM_API',
        'tenant_ref' => 'SYNTHETIC_TENANT_A',
        'nonce' => $nonce, 'issued_at' => 1794690000,
        'expires_at' => 1794690025,
        'body_sha256' => hash('sha256', $json),
    ];
    $proof['signature'] = hash_hmac('sha256', implode("\n", [
        'SYNTHETIC_AIA_HMAC_PROOF_V1', ...array_values($proof),
    ]), $key);
    return $proof;
}
try {
    $fixtureKey='SYNTHETIC_PUBLIC_TEST_ONLY_HMAC_KEY_NOT_SECRET_123456789';
    $wire=[
        'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
        'request_ref'=>'SYNTHETIC_HMAC_REQ',
        'operation'=>'crm.lead.read_minimal',
        'tenant_ref'=>'SYNTHETIC_TENANT_A',
        'principal_ref'=>'SYNTHETIC_AGENT_USER',
        'resource_ref'=>'SYNTHETIC_LEAD_A',
        'purpose_ref'=>'SYNTHETIC_TEST',
        'fields'=>['ref','stage'],
    ];
    $json=json_encode($wire,JSON_UNESCAPED_UNICODE
        |JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $ledger=new AiaFixtureNonceLedger(2);
    $proof=f3Proof($json,'SYNTHETIC_NONCE_TEST_A',$fixtureKey);
    $verify=static fn(string $body,array $p,int $now=1794690010): bool=>
        AiaHmacFixturePreflight::matchesFixture($body,$p,
            'SYNTHETIC_TENANT_A',$now,$fixtureKey,$ledger);
    f3Check($verify($json,$proof));
    f3Check(!$verify($json,$proof));
    $foreign=str_replace('SYNTHETIC_LEAD_A','SYNTHETIC_LEAD_B',$json);
    f3Check(!$verify($foreign,f3Proof(
        $json,'SYNTHETIC_NONCE_TEST_B',$fixtureKey)));
    f3Check(!$verify($json,f3Proof(
        $json,'SYNTHETIC_NONCE_TEST_B',$fixtureKey),1794690025));
    f3Check($ledger->countConsumed()===1);
    $next=f3Proof($json,'SYNTHETIC_NONCE_TEST_B',$fixtureKey);
    f3Check($verify($json,$next));
    f3Check(!$verify($json,$next));
    f3Check(AiaHmacFixturePreflight::inspectFixture(
        $json,$next,'SYNTHETIC_TENANT_A',1794690010,$fixtureKey,$ledger)
        === AiaReadOnlyMockBoundary::inspectCanonicalJson($json));
    f3Check($ledger->countConsumed()===2);
    $ledger->setAvailable(false);
    f3Check(!$verify($json,f3Proof(
        $json,'SYNTHETIC_NONCE_TEST_C',$fixtureKey)));
    $ledger->setAvailable(true);
    f3Check(!$verify($json,f3Proof(
        $json,'SYNTHETIC_NONCE_TEST_C',$fixtureKey)));
    f3Check(AiaReadOnlyMockBoundary::inspectCanonicalJson($json)[
        'real_authorization']===false);
    echo "CRM_F3_AIA_HMAC_LAB_PASS operational_decision=NO_GO "
      ."real_authorization=false external_calls=0\n";
} catch(Throwable) {
    fwrite(STDERR,"CRM_F3_AIA_HMAC_LAB_DENIED operational_decision=NO_GO\n");
    exit(1);
}
