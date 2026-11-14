<?php
declare(strict_types=1);

/**
 * F3-AIA-F221: execute the real SHA-pinned Agents in-process JSON edge and
 * inspect with the CRM's own PHP classes. Zero network, HTTP, SQL, credentials,
 * customer data, real IAM, routes, writes or deployment. Fail closed.
 */
use App\Core\Leads\AgentsReadResponseInspector;
use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use ImagineAI\Domain\TenantContext;
use ImagineAI\MockBridge\{
    MockCrmOwnerPolicy, MockCrmReadPublicFacade, MockCrmReadRequest,
    MockCrmReadRequestBuilder, MockCrmPublicResponseContract
};
use ImagineAI\MockAudit\MockCrmReadAuditTrail;
use ImagineAI\MockTelemetry\{MockCrmReadEdgeBoundary, MockCrmReadEdgeCounters};
use ImagineAI\MockTrust\{
    MockTrustGateway, MockTrustEnvelope, MockOwnerScopeEvidence
};
use ImagineAI\Mocks\MockCrmReader;

$root = dirname(__DIR__, 2);
$agents = $root . '/_synthetic_agents_fixture';
foreach ([
    $root . '/app/Support/Synthetic/AiaReadOnlyMockBoundary.php',
    $root . '/app/Core/Leads/AgentsReadResponseAssessment.php',
    $root . '/app/Core/Leads/AgentsReadResponseInspector.php',
] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "F3_AIA_F221_SOURCES_MISSING_NO_GO\n");
        exit(1);
    }
    require_once $file;
}
spl_autoload_register(static function (string $class) use ($agents): void {
    $prefix = 'ImagineAI\\';
    if (str_starts_with($class, $prefix)) {
        $file = $agents . '/src/'
            . str_replace('\\', '/', substr($class, strlen($prefix)))
            . '.php';
        if (is_file($file)) require $file;
    }
});
function f3AiaCheck(bool $ok): void
{
    if (!$ok) throw new RuntimeException('F3_AIA_F221_CONTRACT_DENIED');
}
function f3AiaProof(TenantContext $ctx, string $id): array
{
    $envelope = new MockTrustEnvelope(
        'SYNTHETIC_F3AIA_ENV_' . $id,
        'SYNTHETIC_AGENT_SERVICE', 'SYNTHETIC_CRM_API',
        $ctx, 'SYNTHETIC_F3AIA_NONCE_' . $id,
        ['SYNTHETIC_LEAD_READ'], 10, 40
    );
    $owner = new MockOwnerScopeEvidence(
        'SYNTHETIC_F3AIA_OWNER_' . $id,
        $envelope->digest, $ctx, 'SYNTHETIC_LEAD_READ',
        10, 40, true
    );
    return [$envelope, $owner];
}
function f3AiaFixture(string $id, bool $grant): array
{
    $ctx = new TenantContext(
        'SYNTHETIC_CRM', 'SYNTHETIC_A', 'SYNTHETIC_PRINCIPAL',
        'SYNTHETIC_SUMMARY', 'SYNTHETIC_A_LEAD'
    );
    [$envelope, $owner] = f3AiaProof($ctx, $id);
    $policy = new MockCrmOwnerPolicy();
    if ($grant) $policy->grantFixture($ctx, ['ref','stage']);
    $audit = new MockCrmReadAuditTrail();
    $counter = new MockCrmReadEdgeCounters();
    $edge = new MockCrmReadEdgeBoundary(
        new MockCrmReadPublicFacade(
            new MockTrustGateway(
                'SYNTHETIC_AGENT_SERVICE','SYNTHETIC_CRM_API',
                ['SYNTHETIC_LEAD_READ']
            ), new MockCrmReader(), $policy, $audit
        ),
        $counter
    );
    $wire = MockCrmReadRequestBuilder::fromContext(
        $ctx, 'SYNTHETIC_F3AIA_REQ', ['ref', 'stage']
    );
    return [$edge, $counter, $audit, $ctx, $envelope, $owner, $wire];
}
function f3AiaNoRights(AgentsReadResponseInspector $inspector,
                       array $out, array $fields): string
{
    $assessment = $inspector->inspect($out, $fields);
    f3AiaCheck(
        !$assessment->canReadRealLead()
        && !$assessment->canUseCustomerData()
        && !$assessment->canGrantAgentAccess()
        && !$assessment->canWriteLead()
        && !$assessment->canExportLead()
        && !$assessment->canEnableRoute()
        && array_keys((array) $assessment) === ['reasonCode']
    );
    return $assessment->reasonCode;
}
try {
    $inspector = new AgentsReadResponseInspector();
    $denial = MockCrmPublicResponseContract::denial();
    $denialBytes = json_encode(
        $denial, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_THROW_ON_ERROR
    );
    $n = 0;

    // F221 positive lab request is still merely an UNTRUSTED CRM format.
    [$edge, $counter, $audit, $ctx, $env, $owner, $wire] =
        f3AiaFixture('POSITIVE', true);
    $request = new MockCrmReadRequest($wire);
    $bytes = MockCrmReadRequest::encodeCanonicalJson($wire);
    $raw = $edge->exchangeCanonicalJson(
        $bytes, $env, $owner, $ctx, 15
    );
    $out = MockCrmPublicResponseContract::decodeCanonicalJson(
        $raw, $request, $ctx
    );
    f3AiaCheck(is_array($out)
        && $out['state'] === 'READ_SYNTHETIC_ONLY'
        && $out['projection'] === [
            'ref' => 'SYNTHETIC_A_LEAD',
            'stage' => 'SYNTHETIC_NEW',
        ]);
    f3AiaCheck(
        f3AiaNoRights($inspector, $out, ['ref','stage'])
        === 'SYNTHETIC_PROJECTION_UNTRUSTED'
    );
    // CRM's boundary itself NEVER accepts a successful Agents mock as a
    // source of service identity or real authorization.
    f3AiaCheck(AiaReadOnlyMockBoundary::inspect($out) === $denial);
    f3AiaCheck($edge->recheck($wire, $env, $owner, $ctx, 16));
    f3AiaCheck($audit->count() === 1);
    ++$n;

    // Same service proof is replayed: CRM must receive uniform denial.
    f3AiaCheck($edge->exchangeCanonicalJson(
        $bytes, $env, $owner, $ctx, 16
    ) === $denialBytes);
    f3AiaCheck(!$edge->recheck($wire, $env, $owner, $ctx, 17));
    f3AiaCheck(
        f3AiaNoRights($inspector, $denial, ['ref','stage'])
        === 'SYNTHETIC_DENIAL_ONLY'
    );
    f3AiaCheck($audit->count() === 2);
    ++$n;

    // Hostile byte input is rejected before it reaches the synthetic reader.
    [$edge, $counter, $audit, $ctx, $env, $owner, $wire] =
        f3AiaFixture('DUPLICATE', true);
    $bytes = MockCrmReadRequest::encodeCanonicalJson($wire);
    $duplicate = substr($bytes, 0, -1)
        . ',"tenant_ref":"SYNTHETIC_A"}';
    f3AiaCheck($edge->exchangeCanonicalJson(
        $duplicate, $env, $owner, $ctx, 15
    ) === $denialBytes);
    f3AiaCheck(
        $counter->snapshot()['outcomes']['SCHEMA_DENIED'] === 1
        && $audit->count() === 0
    );
    ++$n;

    // Even syntactically valid JSON with an ungranted resource cannot read.
    [$edge, $counter, $audit, $ctx, $env, $owner, $wire] =
        f3AiaFixture('UNGRANTED', false);
    f3AiaCheck($edge->exchangeCanonicalJson(
        MockCrmReadRequest::encodeCanonicalJson($wire),
        $env, $owner, $ctx, 15
    ) === $denialBytes);
    f3AiaCheck($audit->count() === 1);
    ++$n;

    // Missing service identity cannot be invented by a matching wire shape.
    [$edge, $counter, $audit, $ctx, $env, $owner, $wire] =
        f3AiaFixture('MISSING_AUTH', true);
    f3AiaCheck($edge->exchangeCanonicalJson(
        MockCrmReadRequest::encodeCanonicalJson($wire),
        null, $owner, $ctx, 15
    ) === $denialBytes);
    f3AiaCheck($audit->count() === 1);
    ++$n;

    f3AiaCheck($n === 5);
    echo 'CRM_F3_AIA_F221_RECIPROCAL_PASS cases=5 '
        . 'operational_decision=NO_GO real_authorization=false '
        . 'external_calls=0 write_calls=0' . PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR,
        "CRM_F3_AIA_F221_RECIPROCAL_DENIED operational_decision=NO_GO\n");
    exit(1);
}
