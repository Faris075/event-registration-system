<?php
// ============================================================
// Application Bootstrap  (bootstrap/app.php)
// ------------------------------------------------------------
// This file is the entry point for Laravel's application factory.
// It configures routing, middleware, and exception handling
// before the application is booted for each HTTP request.
//
// Best practices applied:
//  ✔ Middleware alias ('admin') keeps route definitions clean
//  ✔ appendToGroup() adds EnsureSecurityQuestion globally to the
//    'web' group so it cannot be accidentally omitted from a route
//  ✔ health endpoint enabled at /up for load-balancer checks
//  ✔ Named closures with typed parameters for IDE type-inference
// ============================================================

use Illuminate\Foundation\Application;                  // Core Laravel application class
use Illuminate\Foundation\Configuration\Exceptions;     // Exception handler builder
use Illuminate\Foundation\Configuration\Middleware;     // Middleware pipeline builder

// Application::configure() creates and configures the application instance.
// basePath: dirname(__DIR__) = the project root directory (one level above bootstrap/).
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',      // Main web routes (controllers, middleware groups)
        commands: __DIR__.'/../routes/console.php',  // Artisan command closures
        health:   '/up',                             // Health-check endpoint for load-balancers / uptime monitors
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register 'admin' as a named ALIAS for the IsAdmin middleware.
        // This allows routes to use ->middleware('admin') or Route::middleware('admin')
        // instead of the fully-qualified class name.
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // Append EnsureSecurityQuestion to the 'web' middleware group.
        // appendToGroup() runs AFTER existing web middleware (sessions, auth, etc.)
        // so Auth::check() is already available when EnsureSecurityQuestion executes.
        // Applied globally so no individual route can accidentally bypass the check.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureSecurityQuestion::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom exception rendering/reporting goes here.
        // Currently empty: Laravel's default error handling is used.
        // Example: $exceptions->render(fn (AccessDeniedHttpException $e) => response()->view('errors.403'));
    })->create(); // Build and return the configured Application instance
