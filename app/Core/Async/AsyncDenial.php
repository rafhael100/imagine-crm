<?php

declare(strict_types=1);

namespace App\Core\Async;

use App\Core\Authorization\AuthorizationDecision;

/** Only a rejection can be produced. Dependencies are fixed codes, not proven rights. */
final readonly class AsyncDenial
{
    public AuthorizationDecision $decision;
    public AsyncLifecycle $lifecycle;
    public AsyncDiagnostic $diagnostic;
    public AsyncObservation $observation;

    public function __construct(string $correlationId, public array $dependencies, private bool $formatValidated = false,
        ?AsyncDiagnostic $diagnostic = null, ?ExecutionClass $executionClass = null,
        public bool $observationFailed = false, ?AsyncObservation $observation = null)
    {
        $this->diagnostic = $diagnostic ?? AsyncDiagnostic::authority();
        $this->observation = $observation ?? AsyncObservation::inspection($correlationId, $this->diagnostic, $executionClass);
        $this->decision = AuthorizationDecision::deny('CHANNEL_AUTHORITY_MISSING', $correlationId);
        $trace = AsyncLifecycle::received();
        if ($formatValidated) { $trace = $trace->transition(AsyncState::FormatValidated); }
        $this->lifecycle = $trace->transition(AsyncState::Denied);
    }

    /** Retain the primary diagnosis and attempted observation; no observer retry. */
    public function withObservationFailure(): self
    {
        return new self($this->decision->correlationId, $this->dependencies, $this->formatValidated,
            $this->diagnostic, $this->observation->executionClass, true, $this->observation);
    }

    public function observabilityFailure(): ?AsyncFailureKind
    {
        return $this->observationFailed ? AsyncFailureKind::ObserverUnavailable : null;
    }
}
