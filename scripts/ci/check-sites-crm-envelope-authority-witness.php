<?php

declare(strict_types=1);

/**
 * F3-51: Run REAL Imagine Sites PHP classes from a pinned checkout against
 * the CRM's existing Core envelope inspector. No API, customer data, database,
 * contact payload, grants, credentials, network transport or production ACK.
 */
$root = dirname(__DIR__, 2);
$sites = $root.'/_synthetic_sites_fixture';

$paths = [
    $root.'/app/Core/Leads/SyntheticLeadEnvelopeV1Assessment.php',
    $root.'/app/Core/Leads/SyntheticLeadEnvelopeV1Inspector.php',
];
foreach ([
    'SyntheticCrmLeadEnvelopeV1Draft',
    'SyntheticLeadBindingAuthoritySnapshot',
    'SyntheticLeadBindingAuthoritySource',
    'SyntheticLeadBindingAuthorityAssessment',
    'SyntheticLeadBindingAuthorityPreflight',
] as $class) {
    $paths[] = $sites.'/src/Integration/'.$class.'.php';
}
foreach ($paths as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "F3-51 missing pinned Sites/CRM PHP source\n");
        exit(1);
    }
    require_once $file;
}

use App\Core\Leads\SyntheticLeadEnvelopeV1Inspector;
use Imagine\Sites\Integration\SyntheticCrmLeadEnvelopeV1Draft as Draft;
use Imagine\Sites\Integration\SyntheticLeadBindingAuthorityAssessment as SiteAssessment;
use Imagine\Sites\Integration\SyntheticLeadBindingAuthorityPreflight as Preflight;
use Imagine\Sites\Integration\SyntheticLeadBindingAuthoritySnapshot as Snapshot;
use Imagine\Sites\Integration\SyntheticLeadBindingAuthoritySource as Source;

$checks = 0;
function verify(bool $ok, string $message): void
{
    global $checks;
    ++$checks;
    if (!$ok) {
        throw new RuntimeException('F3-51 fail closed: '.$message);
    }
}

/** @param array<string,mixed> $override */
function snapshot(array $override = []): Snapshot
{
    $fields = [
        'org' => '11111111-1111-4111-8111-111111111111',
        'binding' => '22222222-2222-4222-8222-222222222222',
        'site' => '44444444-4444-4444-8444-444444444444',
        'form' => '55555555-5555-4555-8555-555555555555',
        'destination' => '66666666-6666-4666-8666-666666666666',
        'revision' => 1,
        'active' => true,
    ];
    $fields = array_replace($fields, $override);
    return new Snapshot(
        $fields['org'], $fields['binding'], $fields['site'],
        $fields['form'], $fields['destination'],
        $fields['revision'], $fields['active'],
    );
}

function source(?Snapshot $authority, bool $throws = false): Source
{
    return new class($authority, $throws) implements Source {
        public function __construct(
            private readonly ?Snapshot $authority,
            private readonly bool $throws,
        ) {}

        public function currentBinding(
            string $organizationReference, string $bindingReference,
        ): ?Snapshot {
            if ($this->throws) {
                throw new RuntimeException('synthetic authority source unavailable');
            }
            // This is not an authenticated service, only a controlled test.
            return $this->authority;
        }
    };
}

function assertDeniedSite(SiteAssessment $assessment, string $expectedCode): void
{
    verify($assessment->reasonCode === $expectedCode, 'Sites reason '.$expectedCode);
    verify(!$assessment->canAcceptLead()
        && !$assessment->canAcknowledgeVisitor()
        && !$assessment->canDeliverToCrm()
        && !$assessment->canRetryAutomatically(),
        'Sites assessment cannot grant CRM delivery');
    $json = json_encode($assessment, JSON_THROW_ON_ERROR);
    foreach (['11111111-1111', '22222222-2222', 'sensitive@example.test'] as $marker) {
        verify(!str_contains($json, $marker), 'site assessment must not echo context');
    }
}

$draft = new Draft(
    '11111111-1111-4111-8111-111111111111',
    '22222222-2222-4222-8222-222222222222',
    '33333333-3333-4333-8333-333333333333',
    '44444444-4444-4444-8444-444444444444',
    '55555555-5555-4555-8555-555555555555',
    '66666666-6666-4666-8666-666666666666',
);
$crm = new SyntheticLeadEnvelopeV1Inspector();
$envelope = $draft->metadata();
verify(array_keys($envelope) === [
    'protocol', 'schema_version', 'organization_reference', 'binding_reference',
    'submission_reference', 'site_reference', 'form_reference', 'destination_reference',
], 'Sites draft must emit exact CRM F3-11 field list');
verify($envelope['schema_version'] === 1 && $envelope['protocol'] === 'imagine.lead-intake',
    'Sites and CRM protocol version');
$base = $crm->inspect($envelope);
verify($base->reasonCode === 'STRUCTURE_ONLY_UNVERIFIED'
    && $base->isStructurallyCompatible(), 'real CRM parser accepts draft syntax only');
verify(!$base->canAcceptSubmission()
    && !$base->canDeliverToCrm()
    && !$base->canAcknowledgeDurableReceipt(), 'CRM shape does not confer access');
verify(!$draft->canAuthorizeDelivery(), 'Sites draft is a claim, not authority');

assertDeniedSite((new Preflight())->inspect($draft), 'AUTHORITY_UNAVAILABLE');
assertDeniedSite((new Preflight(source(null, true)))->inspect($draft), 'AUTHORITY_UNAVAILABLE');
assertDeniedSite((new Preflight(source(null)))->inspect($draft), 'BINDING_NOT_FOUND');
assertDeniedSite((new Preflight(source(snapshot(['active'=>false]))))->inspect($draft),
    'BINDING_INACTIVE');
assertDeniedSite((new Preflight(source(snapshot(['revision'=>0]))))->inspect($draft),
    'BINDING_INACTIVE');
assertDeniedSite((new Preflight(source(snapshot())))->inspect($draft),
    'AUTHORITY_MATCH_UNVERIFIED');

foreach (['org','binding','site','form','destination'] as $field) {
    $changed = snapshot([$field => '77777777-7777-4777-8777-777777777777']);
    assertDeniedSite((new Preflight(source($changed)))->inspect($draft),
        'BINDING_CONTEXT_MISMATCH');
}

$forgedDraft = new Draft(
    '99999999-9999-4999-8999-999999999999',
    $draft->bindingReference, $draft->submissionReference,
    $draft->siteReference, $draft->formReference, $draft->destinationReference,
);
verify($crm->inspect($forgedDraft->metadata())->reasonCode === 'STRUCTURE_ONLY_UNVERIFIED',
    'CRM cannot authenticate tenant merely from a valid UUID');
assertDeniedSite((new Preflight(source(snapshot())))->inspect($forgedDraft),
    'BINDING_CONTEXT_MISMATCH');

// Extra customer fields, unexpected contact data, downgrade and fake grants
// are not even syntactically valid input to the CRM metadata-only parser.
foreach ([
    ['email' => 'sensitive@example.test'],
    ['name' => 'SYNTHETIC_PERSON'],
    ['phone' => 'SYNTHETIC_NUMBER'],
    ['consent' => true],
    ['jwt' => 'SYNTHETIC_FAKE_JWT'],
    ['real_authorization' => true],
    ['schema_version' => 2],
    ['protocol' => 'imagine.lead-intake-v2'],
] as $change) {
    $mutated = array_replace($envelope, $change);
    verify($crm->inspect($mutated)->reasonCode !== 'STRUCTURE_ONLY_UNVERIFIED',
        'CRM must not accept PII, grants, version downgrades');
}
foreach (['organization_reference', 'binding_reference', 'submission_reference',
    'site_reference', 'form_reference', 'destination_reference'] as $key) {
    $missing = $envelope;
    unset($missing[$key]);
    verify($crm->inspect($missing)->reasonCode !== 'STRUCTURE_ONLY_UNVERIFIED',
        'missing required identifier '.$key);
}

verify($crm->inspect(array_replace($envelope, [
    'submission_reference' => '00000000-0000-0000-0000-000000000000',
]))->reasonCode === 'REFERENCE_FORMAT_INVALID', 'null UUID denied');
echo "F3-51 PASS: {$checks} CRM/Sites actual PHP envelope + authority preflight assertions; no real lead delivery.\n";
