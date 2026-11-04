<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;

/** The injected guard/provider is trusted infrastructure, never request input. */
final readonly class GuardPrincipalSource
{
    public function __construct(private ?SessionGuard $guard = null) {}

    /** No fallback to auth.defaults.guard, web or a request-based guard. */
    public static function fromConfiguration(Factory $auth, Repository $config): self
    {
        $settings = $config->get('auth.guards.crm_identity');
        $providerName = is_array($settings) ? ($settings['provider'] ?? null) : null;
        $provider = is_string($providerName) && $providerName !== ''
            ? $config->get('auth.providers.'.$providerName) : null;

        if (!is_array($settings) || ($settings['driver'] ?? null) !== 'session'
            || !is_array($provider) || ($provider['driver'] ?? null) !== 'eloquent'
            || ($provider['model'] ?? null) !== GlobalIdentity::class) {
            return new self();
        }

        $guard = $auth->guard('crm_identity');

        return new self($guard instanceof SessionGuard ? $guard : null);
    }

    public function current(): PrincipalReference
    {
        $provider = $this->guard?->getProvider();
        if (!$provider instanceof EloquentUserProvider || $provider->getModel() !== GlobalIdentity::class) {
            throw new AuthenticationException('Authenticated CRM identity required.');
        }

        $user = $this->guard->user();
        $id = $user?->getAuthIdentifier();

        // Only this explicit mapping may identify a CRM global identity.
        if (!$user instanceof GlobalIdentity || !is_string($id) || !Str::isUuid($id)) {
            throw new AuthenticationException('Authenticated CRM identity required.');
        }

        return new PrincipalReference(strtolower($id));
    }
}
