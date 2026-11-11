<?php

declare(strict_types=1);

/**
 * F3-45: execute both repositories' own synthetic OP-01 PHP classes together.
 *
 * Offline/CI-only. No Laravel runtime, HTTP, credentials, customer data, ALLOW,
 * RLS validation, persistence or remote calls. The checkout SHA is pinned in
 * .github/workflows/ci-crm-aia-synthetic-parity.yml.
 */
$crmRoot = dirname(__DIR__, 2);
$agentsRoot = $crmRoot.'/_synthetic_agents_fixture';

foreach ([
    $crmRoot.'/app/Support/Synthetic/AiaReadOnlyMockBoundary.php',
    $agentsRoot.'/src/Domain/MockError.php',
    $agentsRoot.'/src/Domain/TenantContext.php',
    $agentsRoot.'/src/MockBridge/MockCrmReadRequest.php',
] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "F3-45 missing pinned synthetic fixture\n");
        exit(1);
    }
    require_once $file;
}

use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use ImagineAI\Domain\MockError;
use ImagineAI\Domain\TenantContext;
use ImagineAI\MockBridge\MockCrmReadRequest;

function check(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException('F3-45 mismatch: '.$description);
    }
}

/** @return array<string,mixed> */
function validRequest(): array
{
    return [
        'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
        'request_ref' => 'SYNTHETIC_REQ_1',
        'operation' => 'crm.lead.read_minimal',
        'tenant_ref' => 'SYNTHETIC_TENANT_A',
        'principal_ref' => 'SYNTHETIC_USER_A',
        'resource_ref' => 'SYNTHETIC_LEAD_1',
        'purpose_ref' => 'SYNTHETIC_TEST',
        'fields' => ['ref', 'stage'],
    ];
}

function agentsAccepts(array $wire): bool
{
    try {
        new MockCrmReadRequest($wire);
        return true;
    } catch (MockError $e) {
        return false;
    }
}

function checkUniformDeny(array $wire): void
{
    $actual = AiaReadOnlyMockBoundary::inspect($wire);
    check($actual === [
        'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
        'state' => 'DENIED_OR_NOT_FOUND',
        'projection' => null,
        'real_authorization' => false,
        'external_calls' => 0,
    ], 'CRM must deny with a fixed no-data response');
    $encoded = json_encode($actual, JSON_THROW_ON_ERROR);
    foreach (['SYNTHETIC_USER_A', 'SYNTHETIC_LEAD_1', 'SYNTHETIC_TENANT_A'] as $marker) {
        check(!str_contains($encoded, $marker), 'synthetic context leaked');
    }
}

$schemaFile = $agentsRoot.'/contracts/crm-aia-readonly.v1.synthetic.schema.json';
check(is_file($schemaFile), 'Agents source schema exists');
$schema = json_decode((string) file_get_contents($schemaFile), true, 32, JSON_THROW_ON_ERROR);
check(($schema['additionalProperties'] ?? null) === false, 'schema closed properties');
$keys = $schema['required'] ?? [];
sort($keys, SORT_STRING);
$expected = array_keys(validRequest());
sort($expected, SORT_STRING);
check($keys === $expected, 'schema exact required keys');
check(($schema['properties']['contract']['const'] ?? null) === 'SYNTHETIC_CRM_AIA_READ_V1', 'contract version');
check(($schema['properties']['operation']['const'] ?? null) === 'crm.lead.read_minimal', 'operation');
check(($schema['properties']['fields']['minItems'] ?? null) === 1, 'minimum field count');
check(($schema['properties']['fields']['maxItems'] ?? null) === 2, 'maximum field count');
check(($schema['properties']['fields']['uniqueItems'] ?? null) === true, 'unique fields');
$allowed = $schema['properties']['fields']['items']['enum'] ?? [];
sort($allowed, SORT_STRING);
check($allowed === ['ref', 'stage'], 'minimal field names');

$cases = [];
$cases[] = [validRequest(), true, 'valid minimum'];
foreach ([['ref'], ['stage'], ['stage', 'ref']] as $fields) {
    $request = validRequest();
    $request['fields'] = $fields;
    $cases[] = [$request, true, 'valid fields'];
}
$request = validRequest();
$request['tenant_ref'] = 'SYNTHETIC_TENANT_B';
$cases[] = [$request, true, 'another syntactically valid tenant'];

foreach ([
    ['contract' => 'SYNTHETIC_CRM_AIA_READ_V2'],
    ['operation' => 'crm.lead.write'],
    ['tenant_ref' => 'SYNTHETIC_'],
    ['principal_ref' => 'user@example.test'],
    ['purpose_ref' => str_repeat('A', 120)],
    ['resource_ref' => 42],
    ['fields' => []],
    ['fields' => ['ref', 'ref']],
    ['fields' => ['email']],
    ['fields' => ['ref', 'stage', 'email']],
    ['fields' => [0 => 'ref', 2 => 'stage']],
    ['fields' => 'ref'],
    ['is_admin' => true],
    ['role' => 'owner'],
    ['allowed' => true],
    ['signature' => 'fake'],
] as $change) {
    $cases[] = [array_replace(validRequest(), $change), false, 'invalid field or grant'];
}
foreach (array_keys(validRequest()) as $key) {
    $request = validRequest();
    unset($request[$key]);
    $cases[] = [$request, false, 'missing required field'];
}

foreach ($cases as [$request, $expectedAccept, $name]) {
    $crmAccept = AiaReadOnlyMockBoundary::hasFixtureShape($request);
    $agentsAccept = agentsAccepts($request);
    check($crmAccept === $expectedAccept, $name.' rejected/accepted incorrectly by CRM');
    check($agentsAccept === $expectedAccept, $name.' rejected/accepted incorrectly by Agents');
    check($crmAccept === $agentsAccept, $name.' parser drift between repositories');
    checkUniformDeny($request);
}

$valid = new MockCrmReadRequest(validRequest());
$matched = new TenantContext(
    'SYNTHETIC_CRM', 'SYNTHETIC_TENANT_A',
    'SYNTHETIC_USER_A', 'SYNTHETIC_TEST', 'SYNTHETIC_LEAD_1',
);
$valid->assertMatches($matched);
checkUniformDeny(validRequest()); // Context equality is never a CRM grant.

$wrong = new TenantContext(
    'SYNTHETIC_CRM', 'SYNTHETIC_TENANT_B',
    'SYNTHETIC_USER_A', 'SYNTHETIC_TEST', 'SYNTHETIC_LEAD_1',
);
$deniedMismatch = false;
try {
    $valid->assertMatches($wrong);
} catch (MockError $e) {
    $deniedMismatch = true;
}
check($deniedMismatch, 'Agents context mismatch must deny');

echo 'F3-45 synthetic parity passed: '.count($cases).' cases, uniform CRM denial, zero external calls.'.PHP_EOL;
