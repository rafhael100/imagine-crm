<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/**
 * F0: nao autoriza NENHUMA operacao.
 *
 * Substituicao futura exige modulo proprietario, tenant validado,
 * entitlement, finalidade, politica por recurso/acao/campo, auditoria,
 * RLS efetivo e testes negativos documentados. Nao usar em producao.
 */
final class DenyByDefaultAuthorizer
{
    public function decide(AccessRequest $request): AuthorizationDecision
    {
        return AuthorizationDecision::deny();
    }
}
