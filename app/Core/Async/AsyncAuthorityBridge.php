<?php

declare(strict_types=1);

namespace App\Core\Async;

use App\Core\Authorization\ExperimentalResourceCatalog;
use App\Core\Tenancy\TenantAccessBoundary;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/** Format/dependency inspection only. Cannot receive an effect or dispatch anything. */
final readonly class AsyncAuthorityBridge
{
    public function __construct(private TenantAccessBoundary $humanBoundary) {}

    public function inspect(array $input): AsyncDenial
    {
        // Input correlation is a claim; only a fresh backend correlation enters a signal.
        $correlation = (string) Str::uuid();
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) {
            return new AsyncDenial($correlation, ['LABORATORY_ONLY']);
        }
        try { $request = AsyncAuthorityRequest::fromArray($input); }
        catch (\InvalidArgumentException) { return new AsyncDenial($correlation, ['CONTRACT_FORMAT']); }
        $dependencies = $request->executionClass->dependencies();
        $now = now()->getTimestamp();
        if ($request->issuedAt > $now || $request->expiresAt <= $now
            || !(new ExperimentalResourceCatalog())->describes($request->intent)
            || strtolower($request->executor['tenant_id']) !== $request->tenantId
            || ($request->intent->scope === 'tenant' && $request->intent->scopeId !== $request->tenantId)) {
            return new AsyncDenial($correlation, ['CONTRACT_CONTEXT', ...$dependencies], true);
        }
        if ($request->executionClass === ExecutionClass::DeferredHuman) {
            try {
                // Rehearsal ONLY with the current human. This is not a worker
                // credential and does not prove technical execution/delegation.
                $this->humanBoundary->executeContract($request->tenantId, $input['intent'],
                    static fn () => throw new LogicException('No asynchronous effect exists.'),
                    expectedPrincipalId: strtolower($request->causalActor['reference']));
            } catch (Throwable) {
                // Registry, membership, RLS and the real authorizer all fail closed.
                // No exception/input/PII is returned or logged by this inspector.
                return new AsyncDenial($correlation, $dependencies, true);
            }
        }
        // B/C/D/E have their OWN pending authority contracts. Do not invent a
        // human TenantContext/guard or invoke human authorization without proof.
        // Denial reuses F1-05's closed result; no second permission engine.
        return new AsyncDenial($correlation, $dependencies, true);
    }
}
