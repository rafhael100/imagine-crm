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
use App\Core\Identity\IamOperationContext;
use App\Core\Identity\IamOperationRunner;
use App\Core\Identity\IamAbuseGuard;
use App\Core\Identity\IamAudit;
use App\Core\Identity\TrustedPeerOrigin;
use App\Core\Identity\LaboratoryProvisioner;
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
        $this->app->scoped(IamOperationContext::class, fn () => new IamOperationContext());
        $this->app->bind(DatabaseSessionAuthority::class, fn ($app) =>
            new DatabaseSessionAuthority($app['db']->connection('crm_iam'), $app['session.store'], $app->make(IamOperationContext::class), $app->make(IamAudit::class)));
        $this->app->bind(TrustedPeerOrigin::class, fn ($app) => new TrustedPeerOrigin($app['request']));
        $this->app->bind(IamAbuseGuard::class, fn ($app) => new IamAbuseGuard($app['cache']->store(config('identity.abuse_store'))));
        $this->app->bind(IamOperationRunner::class, fn ($app) => new IamOperationRunner(
            $app->make(DatabaseSessionAuthority::class), $app->make(IamOperationContext::class),
            $app['session.store'], $app['auth']->guard('crm_identity'), $app->make(IamAudit::class),
            $app->make(IamAbuseGuard::class), $app->make(TrustedPeerOrigin::class),
        ));
        $this->app->bind(LaboratoryProvisioner::class, function ($app) {
            if (!config('identity.experimental') || !$app->environment('local', 'testing')) {
                throw new \LogicException('Isolated laboratory provisioner only.');
            }
            return new LaboratoryProvisioner(
                $app->make(GuardPrincipalSource::class),
                new DatabaseSessionAuthority($app['db']->connection('crm_provision'), $app['session.store']),
                $app['hash'], $app['encrypter'], $app->make(IamAudit::class), new Totp(),
                $app->make(IamAbuseGuard::class), $app->make(TrustedPeerOrigin::class),
            );
        });
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
                $app->make(\Illuminate\Cache\RateLimiter::class), new Totp(), $app->make(IamOperationRunner::class),
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
