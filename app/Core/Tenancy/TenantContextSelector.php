<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Identity\PrincipalReference;

/**
 * F0: invariante de consistencia entre contexto e membership.
 * Nunca expor como endpoint: snapshots construidos pelo cliente nao sao prova.
 * Mesmo quando retorna um TenantContext, o Authorizer continua NEGANDO tudo.
 */
final class TenantContextSelector
{
    public function select(
        PrincipalReference $principal,
        MembershipSnapshot $membership,
        string $requestedTenantId,
        string $correlationId,
    ): ?TenantContext {
        if ($membership->status !== MembershipStatus::Active) {
            return null;
        }

        if ($membership->principalId !== $principal->principalId) {
            return null;
        }

        if ($membership->tenantId !== $requestedTenantId) {
            return null;
        }

        return new TenantContext(
            $membership->tenantId,
            $principal->principalId,
            $correlationId,
        );
    }
}
