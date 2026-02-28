<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect authenticated users who have not yet set a security question
 * to the security-question setup page.
 */
class EnsureSecurityQuestion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            Auth::check() &&                           // only act on authenticated users
            ! Auth::user()->security_question &&       // who haven't set a security question yet
            ! $request->routeIs('security-question.*') && // avoid redirect loops on the setup routes
            ! $request->routeIs('logout')              // allow logging out without being intercepted
        ) {
            return redirect()->route('security-question.edit');
        }

        return $next($request);
    }
}
