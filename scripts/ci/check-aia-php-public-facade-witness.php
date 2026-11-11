<?php

declare(strict_types=1);

/**
 * F3-47 — cross-repo real PHP facade witness, lab only.
 *
 * The Agents response is a fictional mock: READ_SYNTHETIC_ONLY must never
 * become a CRM allow decision. Runs against a pinned checkout exclusively
 * in GitHub Actions; no Laravel route, database, token, HTTP or client data.
 */
require_once dirname(__DIR__, 2).'/app/Support/Synthetic/AiaReadOnlyMockBoundary.php';
require_once dirname(__DIR__, 2).'/app/Core/Leads/AgentsReadResponseAssessment.php';
require_once dirname(__DIR__, 2).'/app/Core/Leads/AgentsReadResponseInspector.php';

use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use App\Core\Leads\AgentsReadResponseInspector;

function f347Check(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException('F3-47 CONTRACT DRIFT: '.$label);
    }
}

$root = dirname(__DIR__, 2);
$agents = $root.'/_synthetic_agents_fixture';
$probe = $agents.'/tools/op01_php_public_response_probe.php';
f347Check(is_file($probe), 'pinned real Agents PHP probe missing');

// Only the exact pinned source is run; no untrusted user-supplied command,
// network, shell interpolation, inputs or credentials.
$process = proc_open(
    [PHP_BINARY, $probe],
    [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ],
    $pipes,
    $agents,
);
f347Check(is_resource($process), 'failed to start offline Agents probe');
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1], 16385);
fclose($pipes[1]);
$stderr = stream_get_contents($pipes[2], 1025);
fclose($pipes[2]);
$exit = proc_close($process);
f347Check($exit === 0, 'real Agents facade probe failed');
f347Check(is_string($stdout) && strlen($stdout) < 16385, 'probe output too large');
f347Check(is_string($stderr) && $stderr === '', 'unexpected probe diagnostics');

$record = json_decode($stdout, true, 32, JSON_THROW_ON_ERROR);
f347Check(is_array($record) && !array_is_list($record), 'probe report must be object');
$keys = array_keys($record);
sort($keys, SORT_STRING);
f347Check($keys === ['cases', 'external_calls', 'probe', 'real_authorization'], 'probe report closed keys');
f347Check(($record['probe'] ?? null) === 'SYNTHETIC_OP01_PUBLIC_RESPONSE_PROBE_V1', 'probe version');
f347Check(($record['real_authorization'] ?? null) === false, 'probe must never authorize');
f347Check(($record['external_calls'] ?? null) === 0, 'probe must not call external services');

$expected = [
    'single_ref' => ['fields' => ['ref'], 'state' => 'READ_SYNTHETIC_ONLY'],
    'replay' => ['fields' => ['ref'], 'state' => 'DENIED_OR_NOT_FOUND'],
    'ordered_two_fields' => ['fields' => ['stage', 'ref'], 'state' => 'READ_SYNTHETIC_ONLY'],
    'ungranted' => ['fields' => ['stage'], 'state' => 'DENIED_OR_NOT_FOUND'],
    'cross_tenant' => ['fields' => ['ref'], 'state' => 'DENIED_OR_NOT_FOUND'],
    'unknown_field' => ['fields' => ['email'], 'state' => 'DENIED_OR_NOT_FOUND'],
];
f347Check(is_array($record['cases']) && array_is_list($record['cases'])
    && count($record['cases']) === count($expected), 'fixed six-case coverage');

$positiveMocks = 0;
foreach ($record['cases'] as $index => $entry) {
    $caseName = array_keys($expected)[$index];
    f347Check(is_array($entry) && !array_is_list($entry), 'case object '.$index);
    $entryKeys = array_keys($entry);
    sort($entryKeys, SORT_STRING);
    f347Check($entryKeys === ['case', 'fields', 'response'], 'case closed keys '.$index);
    f347Check($entry['case'] === $caseName, 'case order '.$index);
    f347Check($entry['fields'] === $expected[$caseName]['fields'], 'requested fields '.$caseName);

    $out = $entry['response'];
    f347Check(is_array($out) && !array_is_list($out), 'response must be object '.$caseName);
    f347Check(array_keys($out) === [
        'contract', 'state', 'projection', 'real_authorization', 'external_calls',
    ], 'response exact shape/order '.$caseName);
    f347Check($out['contract'] === 'SYNTHETIC_CRM_AIA_PUBLIC_V1', 'public version '.$caseName);
    f347Check($out['state'] === $expected[$caseName]['state'], 'state '.$caseName);
    f347Check($out['real_authorization'] === false && $out['external_calls'] === 0,
        'no real permission or calls '.$caseName);

    if ($out['state'] === 'DENIED_OR_NOT_FOUND') {
        f347Check($out['projection'] === null, 'denial must not expose a projection '.$caseName);
    } else {
        ++$positiveMocks;
        $projection = $out['projection'];
        f347Check(is_array($projection) && !array_is_list($projection)
            && array_keys($projection) === $entry['fields'], 'projection exact fields '.$caseName);
        foreach ($projection as $value) {
            f347Check(is_string($value)
                && preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $value) === 1,
                'projection is invented-only '.$caseName);
        }
    }

    // F3-50 exercises actual reusable CRM Core against actual Agents PHP
    // outputs. Syntactic success must NEVER become CRM identity or ACL.
    $assessment = (new AgentsReadResponseInspector())->inspect(
        $out, $entry['fields'],
    );
    f347Check($assessment->reasonCode === (
        $out['state'] === 'READ_SYNTHETIC_ONLY'
            ? 'SYNTHETIC_PROJECTION_UNTRUSTED'
            : 'SYNTHETIC_DENIAL_ONLY'
    ), 'CRM Core did not classify the upstream fiction '.$caseName);
    f347Check($assessment->isSyntheticFormatConsistent(), 'synthetic format drift '.$caseName);
    f347Check(!$assessment->canReadRealLead()
        && !$assessment->canUseCustomerData()
        && !$assessment->canGrantAgentAccess()
        && !$assessment->canWriteLead()
        && !$assessment->canExportLead()
        && !$assessment->canEnableRoute(),
        'CRM Core must not elevate a synthetic response '.$caseName);
    f347Check(array_keys((array) $assessment) === ['reasonCode'],
        'CRM Core must never echo projection '.$caseName);

    // Critical ownership boundary: a synthetic success from Agents is NOT a
    // service identity, CRM authorization, membership or business data source.
    $crm = AiaReadOnlyMockBoundary::inspect($out);
    f347Check($crm === [
        'contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V1',
        'state' => 'DENIED_OR_NOT_FOUND',
        'projection' => null,
        'real_authorization' => false,
        'external_calls' => 0,
    ], 'CRM did not quarantine the Agents synthetic output '.$caseName);
}
f347Check($positiveMocks === 2, 'two positive Agent mocks must remain non-operational');
foreach ([
    'SYNTHETIC_EVID10_REQUEST', 'SYNTHETIC_EVID10_NONCE',
    'SYNTHETIC_EVID10_OWNER', 'SYNTHETIC_PRINCIPAL',
    'access_token', 'stack_trace', 'Bearer ',
] as $sensitive) {
    f347Check(!str_contains($stdout, $sensitive), 'probe must not expose '.$sensitive);
}

echo 'F3-47/F3-50 PASS: real Agents PHP facade and CRM Core; 2 synthetic '
    .'reads, 4 denials, CRM denied all 6 without external calls.'.PHP_EOL;
