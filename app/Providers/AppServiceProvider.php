<?php

namespace App\Providers;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // F0: politica de autorizacao sempre nega ate contratos CORE/SEG.
        $this->app->bind(Authorizer::class, DenyByDefaultAuthorizer::class);
        // Resolve per operation; never retain a principal/tenant across worker requests.
        // Only a dedicated, explicitly mapped CRM session guard may be resolved.
        // It is deliberately absent until the CORE04/SEG provider contract is reviewed.
        $this->app->bind(GuardPrincipalSource::class, fn ($app) =>
            GuardPrincipalSource::fromConfiguration($app['auth'], $app['config']));
        $this->app->bind(ActiveMembershipStore::class, fn ($app) =>
            new PostgresActiveMembershipStore($app['db']->connection()));
    }

    public function boot(): void
    {
        //
    }
}
