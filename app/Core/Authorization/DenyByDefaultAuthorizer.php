<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/** F0: nunca concede acesso. RLS e politica real ainda nao homologadas. */
final class DenyByDefaultAuthorizer implements Authorizer
{
    public function decide(AccessRequest $request): AuthorizationDecision
    {
        return AuthorizationDecision::deny();
    }
}
