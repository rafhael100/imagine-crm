<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/** F0: nunca concede acesso. RLS e politica real ainda nao homologadas. */
final class DenyByDefaultAuthorizer implements Authorizer
{
    public function decide(AccessRequest $request): AuthorizationDecision
    {
        $envelope = $request->envelope;
        $correlation = $request->context->correlationId;
        if ($envelope === null) {
            return AuthorizationDecision::deny(correlationId: $correlation);
        }
        $intent = $envelope->intent;
        if ($envelope->context !== $request->context || $intent->resource !== $request->resource
            || $intent->action !== $request->action || $request->field !== null
            || !(new ExperimentalResourceCatalog())->describes($intent)
            || ($intent->scope === 'tenant' && $intent->scopeId !== $request->context->tenantId)
            || ($intent->scope === 'record' && $intent->scopeId !== $intent->resourceId)) {
            return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
        }
        if ($intent->channel !== 'interactive') {
            return AuthorizationDecision::deny('CHANNEL_AUTHORITY_MISSING', $correlation);
        }
        if ($intent->scope !== 'tenant') {
            return AuthorizationDecision::deny('SCOPE_NOT_VERIFIED', $correlation);
        }

        // Even a complete synthetic description has NO homologated policy,
        // entitlement, owner rules or grants. No ALLOW constructor exists.
        return AuthorizationDecision::deny(correlationId: $correlation);
    }
}
