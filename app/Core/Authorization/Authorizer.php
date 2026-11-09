<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/** Interface F0. Nenhuma decisao ALLOW esta implementada. */
interface Authorizer
{
    public function decide(AccessRequest $request): AuthorizationDecision;
}
