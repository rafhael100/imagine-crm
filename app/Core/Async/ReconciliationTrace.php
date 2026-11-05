<?php

declare(strict_types=1);

namespace App\Core\Async;

use Illuminate\Support\Str;

/** Separate inert trace: it never reopens a denied inspection or confirms/retries an effect. */
final readonly class ReconciliationTrace
{
    private function __construct(public string $id, public string $causeId, public ReconciliationKind $kind,
        public AsyncLifecycle $lifecycle, public AsyncDiagnostic $diagnostic) {}

    public static function pending(ReconciliationKind $kind, AsyncObservation $parent): self
    {
        $trace = AsyncLifecycle::received()->transition(AsyncState::FormatValidated)
            ->transition(AsyncState::Inconclusive)->requireReconciliation();
        return new self((string) Str::uuid(), $parent->id, $kind, $trace, AsyncDiagnostic::reconciliation());
    }

    public function signal(): array
    {
        return ['type' => 'synthetic_reconciliation', 'trace_id' => $this->id, 'causation_id' => $this->causeId,
            'classification' => $this->diagnostic->kind->value, 'source' => 'synthetic_trace',
            'reason' => $this->kind->value, 'state' => $this->lifecycle->state->value,
            'coverage' => 'laboratory_partial'];
    }
}
