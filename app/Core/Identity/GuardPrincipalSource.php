<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;

/** The injected guard/provider is trusted infrastructure, never request input. */
final readonly class GuardPrincipalSource
{
    public function __construct(private Guard $guard) {}

    public function current(): PrincipalReference
    {
        $user = $this->guard->user();

        // Only this explicit mapping may identify a CRM global identity.
        if (!$user instanceof GlobalIdentity || !Str::isUuid($user->getAuthIdentifier())) {
            throw new AuthenticationException('Authenticated CRM identity required.');
        }

        return new PrincipalReference(strtolower($user->getAuthIdentifier()));
    }
}
