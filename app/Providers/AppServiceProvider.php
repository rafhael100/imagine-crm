<?php

namespace App\Providers;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\GlobalIdentity;
use App\Core\Identity\SessionAuthority;
use App\Core\Identity\DatabaseSessionAuthority;
use App\Core\Identity\ExperimentalAuthenticator;
use App\Core\Identity\Totp;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // F0: politica de autorizacao sempre nega ate contratos CORE/SEG.
        $this->app->bind(Authorizer::class, DenyByDefaultAuthorizer::class);
        // Candidate profile is disabled by default and can never run in production.
        if (config('identity.experimental') && $this->app->environment('local', 'testing')) {
            config([
                'auth.guards.crm_identity' => ['driver' => 'session', 'provider' => 'crm_global_identities'],
                'auth.providers.crm_global_identities' => ['driver' => 'eloquent', 'model' => GlobalIdentity::class],
                'session.encrypt' => true, 'session.secure' => true, 'session.http_only' => true,
                'session.same_site' => 'strict', 'session.expire_on_close' => true,
            ]);
        }
        $this->app->bind(DatabaseSessionAuthority::class, fn ($app) =>
            new DatabaseSessionAuthority($app['db']->connection('crm_iam'), $app['session.store']));
        $this->app->bind(SessionAuthority::class, DatabaseSessionAuthority::class);
        $this->app->bind(GuardPrincipalSource::class, function ($app) {
            if (!config('identity.experimental') || !$app->environment('local', 'testing')) {
                return new GuardPrincipalSource();
            }
            return GuardPrincipalSource::fromConfiguration($app['auth'], $app['config'], $app->make(SessionAuthority::class));
        });
        $this->app->bind(ExperimentalAuthenticator::class, function ($app) {
            if (!config('identity.experimental') || !$app->environment('local', 'testing')
                || config('session.driver') === 'cookie'
                || (config('session.driver') === 'array' && !$app->environment('testing'))) {
                throw new \LogicException('Experimental server-side authentication only.');
            }
            return new ExperimentalAuthenticator(
                $app->make(DatabaseSessionAuthority::class), $app['auth']->guard('crm_identity'),
                $app['session.store'], $app['hash'], $app['encrypter'],
                $app->make(\Illuminate\Cache\RateLimiter::class), new Totp(),
            );
        });
        $this->app->bind(ActiveMembershipStore::class, fn ($app) =>
            new PostgresActiveMembershipStore($app['db']->connection()));
    }

    public function boot(): void
    {
        //
    }
}
