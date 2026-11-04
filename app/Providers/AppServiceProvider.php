<?php

namespace App\Providers;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // F0: politica de autorizacao sempre nega ate contratos CORE/SEG.
        $this->app->bind(Authorizer::class, DenyByDefaultAuthorizer::class);
    }

    public function boot(): void
    {
        //
    }
}
