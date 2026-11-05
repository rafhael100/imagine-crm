<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use Illuminate\Auth\Access\AuthorizationException;

final class MembershipDenied extends AuthorizationException
{
    public function __construct() { parent::__construct('Tenant access denied.'); }
}
