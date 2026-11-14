<?php
declare(strict_types=1);

/**
 * CRM-side F231/OP-01 interoperability: signed synthetic service proof
 * from the exact, reviewed Agents SHA, but CRM still DENIES every read.
 *
 * No Laravel boot, routes, database, secrets, IAM, clients or HTTP.
 */
use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use ImagineAI\MockBridge\{MockCrmReadRequest, MockServiceIdentityPreflight};

$root=dirname(__DIR__, 2);
$agents=$root.'/_aia_f231_fixture';
if (!is_file($agents.'/src/MockBridge/MockServiceIdentityPreflight.php')
    || !is_file($root.'/app/Support/Synthetic/AiaReadOnlyMockBoundary.php')) {
    fwrite(STDERR, "F231_CRM_SOURCES_UNAVAILABLE operational_decision=NO_GO\n");
    exit(2);
}
require_once $root.'/app/Support/Synthetic/AiaReadOnlyMockBoundary.php';
spl_autoload_register(static function (string $class) use ($agents): void {
    $prefix='ImagineAI\\';
    if (str_starts_with($class,$prefix)) {
        require $agents.'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    }
});
function f231CrmAssert(bool $ok): void {
    if (!$ok) throw new RuntimeException('F231_CRM_DENIED');
}
function f231CrmRequest(string $tenant): string {
    return MockCrmReadRequest::encodeCanonicalJson([
        'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
        'request_ref'=>'SYNTHETIC_F231_CRM_REQ',
        'operation'=>'crm.lead.read_minimal',
        'tenant_ref'=>$tenant,
        'principal_ref'=>'SYNTHETIC_F231_AGENT',
        'resource_ref'=>'SYNTHETIC_F231_CRM_LEAD',
        'purpose_ref'=>'SYNTHETIC_F231_CRM_TEST',
        'fields'=>['ref','stage'],
    ]);
}
function f231CrmClaims(string $body,string $tenant,string $nonce): array {
    return [
        'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
        'issuer'=>'SYNTHETIC_AGENT_SERVICE',
        'audience'=>'SYNTHETIC_CRM_API',
        'key_id'=>'SYNTHETIC_F231_CRM_KID',
        'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
        'tenant_ref'=>$tenant,
        'operation'=>'crm.lead.read_minimal',
        'nonce'=>$nonce,
        'issued_at'=>1794690000,
        'expires_at'=>1794690020,
        'request_sha256'=>hash('sha256',$body),
    ];
}
function f231CrmSign(array $claims,string $private): array {
    $payload=json_encode(
        $claims, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR
    );
    return [$payload,base64_encode(sodium_crypto_sign_detached(
        "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$payload,$private
    ))];
}
function f231CrmDenial(string $body): bool {
    return AiaReadOnlyMockBoundary::inspectCanonicalJson($body) === [
        'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
        'state'=>'DENIED_OR_NOT_FOUND',
        'projection'=>null,
        'real_authorization'=>false,
        'external_calls'=>0,
    ];
}
try {
    f231CrmAssert(function_exists('sodium_crypto_sign_verify_detached'));
    // Deterministic, intentionally public fixture. No production private key.
    $pair=sodium_crypto_sign_seed_keypair(
        hash('sha256','PUBLIC_F231_CRM_ED25519_LAB_ONLY',true)
    );
    $secret=sodium_crypto_sign_secretkey($pair);
    $public=sodium_crypto_sign_publickey($pair);
    $kid='SYNTHETIC_F231_CRM_KID';
    $trusted=[$kid=>['public_key'=>$public,'enabled'=>true]];
    $tenants=['SYNTHETIC_TENANT_A','SYNTHETIC_TENANT_B','SYNTHETIC_TENANT_C'];
    $positives=0; $denials=0;
    foreach($tenants as $i=>$tenant) {
        $body=f231CrmRequest($tenant);
        $claims=f231CrmClaims($body,$tenant,'SYNTHETIC_F231_CRM_NONCE_'.($i+1));
        [$payload,$sig]=f231CrmSign($claims,$secret);
        $guard=new MockServiceIdentityPreflight();
        f231CrmAssert($guard->verifyOnce(
            $body,$payload,$sig,$trusted,$tenant,1794690010
        ));
        // A legitimate signature is only identity evidence, not CRM permission.
        f231CrmAssert(f231CrmDenial($body));
        f231CrmAssert(!$guard->verifyOnce(
            $body,$payload,$sig,$trusted,$tenant,1794690010
        ));
        f231CrmAssert($guard->countConsumed()===1);
        f231CrmAssert(f231CrmDenial($body));
        $changed=json_encode(array_replace($claims,[
            'tenant_ref'=>$tenants[($i+1)%count($tenants)]
        ]), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $badSigned=f231CrmSign(array_replace($claims,[
            'tenant_ref'=>$tenants[($i+1)%count($tenants)]
        ]),$secret);
        $fresh=new MockServiceIdentityPreflight();
        f231CrmAssert(!$fresh->verifyOnce(
            $body,$badSigned[0],$badSigned[1],$trusted,$tenant,1794690010
        ));
        f231CrmAssert($fresh->countConsumed()===0);
        f231CrmAssert(f231CrmDenial($body));
        ++$denials;
        $disabled=[$kid=>['public_key'=>$public,'enabled'=>false]];
        $fresh=new MockServiceIdentityPreflight();
        f231CrmAssert(!$fresh->verifyOnce(
            $body,$payload,$sig,$disabled,$tenant,1794690010
        ));
        f231CrmAssert(f231CrmDenial($body));
        ++$denials;
        $fresh=new MockServiceIdentityPreflight();
        f231CrmAssert(!$fresh->verifyOnce(
            $body,$payload,$sig,[],$tenant,1794690010
        ));
        f231CrmAssert(f231CrmDenial($body));
        ++$denials;
        $fresh=new MockServiceIdentityPreflight();
        f231CrmAssert(!$fresh->verifyOnce(
            $body,$payload,$sig,$trusted,$tenant,1794690020
        ));
        f231CrmAssert(f231CrmDenial($body));
        ++$denials;
        ++$positives;
    }
    f231CrmAssert($positives===3 && $denials===12);
    echo "CRM_F231_ED25519_BILATERAL_DENY_PASS tenants=3 negatives=12 ".
         "operational_decision=NO_GO real_authorization=false external_calls=0\n";
} catch (\Throwable) {
    fwrite(STDERR,"CRM_F231_ED25519_BILATERAL_DENIED operational_decision=NO_GO\n");
    exit(1);
}
