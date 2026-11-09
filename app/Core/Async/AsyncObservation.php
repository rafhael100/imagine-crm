<?php

declare(strict_types=1);

namespace App\Core\Async;

use Illuminate\Support\Str;
use InvalidArgumentException;

/** Inert sanitized observation. Not durable evidence, authentication or business confirmation. */
final readonly class AsyncObservation
{
    private function __construct(
        public string $id, public string $correlationId, public string $causationId,
        public AsyncDiagnostic $diagnostic, public ?ExecutionClass $executionClass, public int $occurredAt,
    ) {}

    public static function inspection(string $backendCorrelation, AsyncDiagnostic $diagnostic, ?ExecutionClass $class): self
    {
        if (!Str::isUuid($backendCorrelation)) { throw new InvalidArgumentException('Invalid observation correlation.'); }
        return new self((string) Str::uuid(), $backendCorrelation, $backendCorrelation, $diagnostic, $class, now()->getTimestamp());
    }

    public function signal(): array
    {
        return ['schema_version' => 1, 'type' => 'async_inspection', 'observation_id' => $this->id,
            'correlation_id' => $this->correlationId, 'causation_id' => $this->causationId,
            'stage' => $this->diagnostic->stage, 'classification' => $this->diagnostic->kind->value,
            'source' => $this->diagnostic->source, 'execution_class_claim' => $this->executionClass?->value,
            'class_evidence' => 'unverified_claim', 'result' => 'deny', 'occurred_at' => $this->occurredAt,
            'coverage' => 'laboratory_partial'];
    }
}
