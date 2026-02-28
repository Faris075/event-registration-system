<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register 'admin' as a named alias for the IsAdmin middleware so
        // routes can be protected with ->middleware('admin') or Route::middleware('admin').
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // Append EnsureSecurityQuestion to the 'web' group so every
        // authenticated page redirects users who have never set a security
        // question to the setup form before they can continue.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureSecurityQuestion::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
