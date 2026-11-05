<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Auth\AuthenticationException;

/** Emitted only after matching the locked registry row to subject and session binding. */
final class SessionRevoked extends AuthenticationException
{
    public function __construct() { parent::__construct('CRM authentication required.'); }
}
