<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Until real IAM has an approved public login route, never redirect
        // unauthenticated web users to a non-existent named "login" route.
        // Return a uniform denied response with no credential/session detail.
        $exceptions->render(static function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => 'Unauthenticated.'], 401)
                ->header('Cache-Control', 'no-store')
                ->header('X-Content-Type-Options', 'nosniff');
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
