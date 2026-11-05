<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncDenial;
use App\Core\Async\AsyncDiagnostic;
use App\Core\Async\ReconciliationKind;
use App\Core\Async\ReconciliationTrace;
use Illuminate\Support\Str;

/** No rows, input identifiers, errors, payload or permission can enter this signal. */
final readonly class LedgerIntegrityReport
{
    public array $findings;
    public AsyncDenial $denial;
    public AsyncDiagnostic $diagnostic;
    public ReconciliationTrace $reconciliation;

    public function __construct(array $findings = [], ?AsyncDiagnostic $diagnostic = null, ?AsyncDenial $denial = null)
    {
        $closed = [];
        foreach ($findings as $finding) {
            if (!$finding instanceof LedgerGap) { throw new \InvalidArgumentException('Closed laboratory finding required.'); }
            $closed[$finding->value] = $finding;
        }
        ksort($closed); $this->findings = array_values($closed);
        $this->diagnostic = $diagnostic ?? AsyncDiagnostic::reconciliation();
        $this->denial = $denial ?? new AsyncDenial((string) Str::uuid(), ['OWNER_POLICY_CONFIRMATION'], diagnostic: $this->diagnostic);
        $this->reconciliation = ReconciliationTrace::pending(ReconciliationKind::EvidenceIncomplete, $this->denial->observation);
    }

    public function signal(): array
    {
        return ['type' => 'synthetic_ledger_integrity', 'coverage' => 'laboratory_partial',
            'assessment' => $this->findings === [] ? 'inconclusive' : 'reconciliation_required',
            'findings' => array_map(static fn (LedgerGap $gap): string => $gap->value, $this->findings),
            'causal_order' => 'unknown', 'owner_review' => 'needs_owner', 'result' => 'deny',
            'classification' => $this->diagnostic->kind->value,
            'correlation_id' => $this->denial->observation->correlationId,
            'state' => $this->reconciliation->lifecycle->state->value];
    }
}
