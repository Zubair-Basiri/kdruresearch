<?php

// use Illuminate\Foundation\Application;
// use Illuminate\Foundation\Configuration\Exceptions;
// use Illuminate\Foundation\Configuration\Middleware;

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         web: __DIR__.'/../routes/web.php',
//         commands: __DIR__.'/../routes/console.php',
//         health: '/up',
//     )
//     ->withMiddleware(function (Middleware $middleware) {
//         //
//     })
//     ->withExceptions(function (Exceptions $exceptions) {
//         //
//     })->create();

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Keep Laravel's default web/api middleware groups intact. Overwriting
        // them removes cookies and StartSession, which makes login appear to
        // succeed but leaves every subsequent API request unauthenticated.
        $middleware->statefulApi();

        // This application has no server-rendered login route. API callers
        // must receive a 401 instead of an exception while Laravel tries to
        // generate a redirect to a missing named route.
        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('api/*') ? null : '/';
        });

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'guest.has.university' => \App\Http\Middleware\EnsureGuestHasUniversity::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API clients must receive JSON 401/403 responses. Without this,
        // unauthenticated requests try to redirect to a non-existent `login`
        // route and become misleading 500 errors.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
