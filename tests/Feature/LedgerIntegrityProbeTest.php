<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Async\AsyncDenial;
use App\Core\Async\AsyncDiagnostic;
use App\Core\Async\AsyncState;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\AsyncCases;
use Tests\Fixtures\AuthorizationCases;
use Tests\Fixtures\LedgerDescription;
use Tests\Fixtures\LedgerFixtureManifest;
use Tests\Fixtures\LedgerGap as Gap;
use Tests\Fixtures\LedgerIntegrityProbe;
use Tests\Fixtures\LedgerIntegrityReport;
use Tests\Fixtures\LedgerReceipt;
use Tests\Fixtures\SyntheticCausalProbe;
use Tests\TestCase;

final class LedgerIntegrityProbeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); config(['identity.experimental' => true]); $this->travelTo(now()->setTimestamp(1800000000));
    }

    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }

    private function fixture(): array
    {
        $description = LedgerDescription::parse(AsyncCases::request('B'), 1, 'lab.alpha');
        $receipt = new LedgerReceipt('first_seen', (string) Str::uuid(), new AsyncDenial((string) Str::uuid(), []), AsyncDiagnostic::reconciliation());
        $ledger = ['id' => AuthorizationCases::RECORD, 'tenant_id' => AuthorizationCases::TENANT,
            'operation_id' => AsyncCases::OPERATION, 'idempotency_key' => AsyncCases::OPERATION, 'revision' => 1,
            'scope_kind' => 'tenant', 'scope_id' => AuthorizationCases::TENANT, 'policy_version' => 'lab-async-deny-v1',
            'digest' => $description->digest, 'state' => 'registered_for_test', 'created_at' => 1800000000, 'expires_at' => 1800000060];
        $attempt = ['id' => $receipt->attemptId, 'tenant_id' => AuthorizationCases::TENANT, 'ledger_id' => $ledger['id'],
            'state' => 'reconciliation_required', 'outcome' => 'first_seen', 'correlation_id' => $receipt->denial->observation->correlationId,
            'causation_id' => $receipt->denial->observation->id, 'created_at' => 1800000000];
        $association = ['id' => (string) Str::uuid(), 'tenant_id' => AuthorizationCases::TENANT, 'ledger_id' => $ledger['id'], 'state' => 'registered_for_test'];
        return [['ledger' => [$ledger], 'attempts' => [$attempt], 'inbox' => [$association], 'outbox' => [$association]],
            new LedgerFixtureManifest([[$description, [$receipt]]])];
    }

    public function test_complete_snapshot_is_only_an_inconclusive_consultation_and_is_not_mutated(): void
    {
        [$sample, $manifest] = $this->fixture(); $before = serialize($sample);
        $report = (new LedgerIntegrityProbe())->inspect($sample, $manifest, AuthorizationCases::TENANT);
        $this->assertSame([], $report->findings); $this->assertSame($before, serialize($sample));
        $this->assertSame('inconclusive', $report->signal()['assessment']); $this->assertClosed($report);
        foreach ([AuthorizationCases::TENANT, AsyncCases::OPERATION, $sample['ledger'][0]['digest'], $sample['attempts'][0]['id']] as $input) {
            $this->assertStringNotContainsString($input, json_encode($report->signal()));
        }
    }

    public static function corruptions(): array
    {
        return [
            'missing ledger' => ['ledger', 'remove', Gap::MissingLedger],
            'missing attempt' => ['attempts', 'remove', Gap::MissingAttempt],
            'missing inbox' => ['inbox', 'remove', Gap::MissingInbox],
            'missing outbox' => ['outbox', 'remove', Gap::MissingOutbox],
            'double association' => ['inbox', 'duplicate', Gap::DoubleAssociation],
            'orphan attempt' => ['attempts', 'orphan', Gap::OrphanAttempt],
            'orphan association' => ['outbox', 'orphan', Gap::OrphanAssociation],
            'logical duplicate' => ['ledger', 'duplicate', Gap::ApparentDuplicate],
            'digest changed' => ['ledger', 'digest', Gap::DigestMismatch],
            'revision changed' => ['ledger', 'revision', Gap::VersionMismatch],
            'policy changed' => ['ledger', 'policy', Gap::VersionMismatch],
            'correlation changed' => ['attempts', 'correlation', Gap::CorrelationMismatch],
            'causation changed' => ['attempts', 'causation', Gap::CorrelationMismatch],
            'unknown record' => ['ledger', 'unexpected', Gap::UnexpectedRecord],
            'forged confirmed state' => ['ledger', 'state', Gap::InvalidState],
            'expired' => ['ledger', 'expired', Gap::Expired],
            'future' => ['ledger', 'future', Gap::InvalidValidity],
            'changed attempt' => ['attempts', 'outcome', Gap::AttemptMismatch],
            'duplicate attempt id' => ['attempts', 'duplicate', Gap::ApparentDuplicate],
        ];
    }

    #[DataProvider('corruptions')]
    public function test_corrupt_or_truncated_snapshot_requires_consultative_reconciliation(string $table, string $change, Gap $gap): void
    {
        [$sample, $manifest] = $this->fixture();
        switch ($change) {
            case 'remove': $sample[$table] = []; break;
            case 'duplicate': $copy = $sample[$table][0]; if ($table !== 'attempts') { $copy['id'] = (string) Str::uuid(); } $sample[$table][] = $copy; break;
            case 'orphan': $sample[$table][0]['ledger_id'] = (string) Str::uuid(); break;
            case 'digest': $sample[$table][0]['digest'] = str_repeat('0', 64); break;
            case 'revision': $sample[$table][0]['revision'] = 2; break;
            case 'policy': $sample[$table][0]['policy_version'] = 'lab-unknown-v2'; break;
            case 'correlation': $sample[$table][0]['correlation_id'] = (string) Str::uuid(); break;
            case 'causation': $sample[$table][0]['causation_id'] = (string) Str::uuid(); break;
            case 'unexpected': $sample[$table][0]['operation_id'] = (string) Str::uuid(); break;
            case 'state': $sample[$table][0]['state'] = 'officially_confirmed'; break;
            case 'expired': $this->travelTo(now()->setTimestamp(1800000060)); break;
            case 'future': $sample[$table][0]['created_at'] += 30; break;
            case 'outcome': $sample[$table][0]['outcome'] = 'duplicate'; break;
        }
        $before = serialize($sample); $report = (new LedgerIntegrityProbe())->inspect($sample, $manifest, AuthorizationCases::TENANT);
        $this->assertContains($gap, $report->findings); $this->assertSame($before, serialize($sample));
        $this->assertSame('reconciliation_required', $report->signal()['assessment']); $this->assertClosed($report);
    }

    public function test_empty_or_cross_tenant_samples_do_not_prove_completion_or_disclose_other_tenant(): void
    {
        [$sample, $manifest] = $this->fixture(); $probe = new LedgerIntegrityProbe();
        $empty = array_fill_keys(array_keys($sample), []);
        $this->assertContains(Gap::MissingLedger, $probe->inspect($empty, $manifest, AuthorizationCases::TENANT)->findings);
        foreach (array_keys($sample) as $table) {
            $bad = $sample; $bad[$table][0]['tenant_id'] = AuthorizationCases::OTHER;
            $report = $probe->inspect($bad, $manifest, AuthorizationCases::TENANT);
            $this->assertSame([Gap::CrossTenant], $report->findings);
            $this->assertStringNotContainsString(AuthorizationCases::OTHER, json_encode($report->signal()));
        }
    }

    public function test_unknown_columns_free_text_and_unbounded_samples_never_enter_signal(): void
    {
        [$sample, $manifest] = $this->fixture(); $probe = new LedgerIntegrityProbe();
        $sample['ledger'][0]['payload'] = 'synthetic-secret@example.invalid';
        $report = $probe->inspect($sample, $manifest, AuthorizationCases::TENANT);
        $this->assertSame([Gap::InvalidSample], $report->findings);
        $this->assertStringNotContainsString('synthetic-secret', json_encode($report->signal()));
        [$sample] = $this->fixture(); $sample['ledger'] = array_fill(0, 201, $sample['ledger'][0]);
        $this->assertSame([Gap::InvalidSample], $probe->inspect($sample, $manifest, AuthorizationCases::TENANT)->findings);
        $this->expectException(\InvalidArgumentException::class); new LedgerIntegrityReport(['synthetic-sql-or-payload']);
    }

    public function test_disabled_profile_and_nonlaboratory_environments_do_not_inspect_samples(): void
    {
        [$sample, $manifest] = $this->fixture();
        foreach ([['testing', false], ['production', true], ['staging', true]] as [$env, $enabled]) {
            $this->app->instance('env', $env); config(['identity.experimental' => $enabled]);
            $this->assertSame([Gap::LaboratoryDisabled], (new LedgerIntegrityProbe())->inspect($sample, $manifest, AuthorizationCases::TENANT)->findings);
            $this->assertSame([Gap::LaboratoryDisabled], (new SyntheticCausalProbe())->inspect([], AuthorizationCases::TENANT)->findings);
        }
        $this->app->instance('env', 'testing');
    }

    private function event(int $revision = 1): array
    {
        return ['event_id' => (string) Str::uuid(), 'tenant_id' => AuthorizationCases::TENANT,
            'operation_id' => AsyncCases::OPERATION, 'revision' => $revision, 'attempt_id' => (string) Str::uuid(),
            'predecessor_id' => null, 'content' => 'lab.alpha'];
    }

    public function test_out_of_order_and_old_versions_are_observations_without_global_sequencing_or_merging(): void
    {
        $older = $this->event(); $newer = $this->event(2); $newer['predecessor_id'] = $older['event_id'];
        $report = (new SyntheticCausalProbe())->inspect([$newer, $older], AuthorizationCases::TENANT);
        $this->assertContains(Gap::PredecessorUnobserved, $report->findings);
        $this->assertContains(Gap::OlderVersionObserved, $report->findings); $this->assertClosed($report);
        $ordered = (new SyntheticCausalProbe())->inspect([$older, $newer], AuthorizationCases::TENANT);
        $this->assertSame([], $ordered->findings); $this->assertClosed($ordered);
    }

    public function test_new_attempt_duplicate_and_conflict_do_not_merge_logical_intentions(): void
    {
        $first = $this->event(); $next = $this->event(); $conflict = $this->event(); $conflict['content'] = 'lab.beta';
        $report = (new SyntheticCausalProbe())->inspect([$first, $next, $next, $conflict], AuthorizationCases::TENANT);
        foreach ([Gap::SameIntentNewAttempt, Gap::DuplicateAttempt, Gap::CausalConflict] as $gap) { $this->assertContains($gap, $report->findings); }
        $this->assertClosed($report);
        $other = $this->event(); $other['operation_id'] = (string) Str::uuid();
        $this->assertSame([], (new SyntheticCausalProbe())->inspect([$first, $other], AuthorizationCases::TENANT)->findings);
    }

    public function test_causal_claims_are_closed_and_cross_tenant_is_not_an_ordering_hint(): void
    {
        $event = $this->event(); $event['tenant_id'] = AuthorizationCases::OTHER;
        $this->assertSame([Gap::CrossTenant], (new SyntheticCausalProbe())->inspect([$event], AuthorizationCases::TENANT)->findings);
        $event = $this->event(); $event['predecessor_id'] = $event['event_id'];
        $this->assertContains(Gap::CausalConflict, (new SyntheticCausalProbe())->inspect([$event], AuthorizationCases::TENANT)->findings);
        $event['url'] = 'https://synthetic.invalid';
        $this->assertSame([Gap::InvalidSample], (new SyntheticCausalProbe())->inspect([$event], AuthorizationCases::TENANT)->findings);
    }

    private function assertClosed(LedgerIntegrityReport $report): void
    {
        $this->assertFalse($report->denial->decision->allowed);
        $this->assertSame(AsyncState::Denied, $report->denial->lifecycle->state);
        $this->assertSame(AsyncState::ReconciliationRequired, $report->reconciliation->lifecycle->state);
        $this->assertSame('unknown', $report->signal()['causal_order']); $this->assertSame('needs_owner', $report->signal()['owner_review']);
    }
}
