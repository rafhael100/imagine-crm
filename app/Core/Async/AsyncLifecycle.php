<?php

declare(strict_types=1);

namespace App\Core\Async;

use LogicException;

/** Inert transition contract for synthetic traces. No scheduler, transport or effect. */
final readonly class AsyncLifecycle
{
    private function __construct(public AsyncState $state) {}

    public static function received(): self { return new self(AsyncState::Received); }

    public function transition(AsyncState $next): self
    {
        $possible = match ($this->state) {
            AsyncState::Received => [AsyncState::FormatValidated, AsyncState::Denied],
            AsyncState::FormatValidated => [AsyncState::Denied, AsyncState::Inconclusive],
            AsyncState::Inconclusive => [AsyncState::ReconciliationRequired],
            default => [],
        };
        if (!in_array($next, $possible, true)) { throw new LogicException('Invalid experimental transition.'); }
        return new self($next);
    }

    /** A reported 2xx/timeout is untrusted transport information, never official confirmation. */
    public function observeTransport(?int $httpStatus): self
    {
        return $this->transition(AsyncState::Inconclusive);
    }
}
