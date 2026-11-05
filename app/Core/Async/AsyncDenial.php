<?php

declare(strict_types=1);

namespace App\Core\Async;

use App\Core\Authorization\AuthorizationDecision;

/** Only a rejection can be produced. Dependencies are fixed codes, not proven rights. */
final readonly class AsyncDenial
{
    public AuthorizationDecision $decision;
    public AsyncLifecycle $lifecycle;

    public function __construct(string $correlationId, public array $dependencies, bool $formatValidated = false)
    {
        $this->decision = AuthorizationDecision::deny('CHANNEL_AUTHORITY_MISSING', $correlationId);
        $trace = AsyncLifecycle::received();
        if ($formatValidated) { $trace = $trace->transition(AsyncState::FormatValidated); }
        $this->lifecycle = $trace->transition(AsyncState::Denied);
    }
}
