<?php
// ============================================================
// Middleware: EnsureSecurityQuestion
// ------------------------------------------------------------
// Intercepts every authenticated web request and redirects users
// who have not yet set a security question to the setup page.
//
// Appended to the 'web' middleware group in bootstrap/app.php
// so it runs automatically on every web route without needing
// to be applied individually per route.
//
// Best practices applied:
//  ✔ Multiple negative conditions combined cleanly with short-circuit &&
//  ✔ routeIs() used instead of URL matching (robust to route prefix changes)
//  ✔ Logout route excluded to prevent trapping users in a redirect loop
//    when they try to sign out without having set a question
//  ✔ Only fires for authenticated users (checks Auth::check() first)
//  ✔ Does not alter the response for users who pass all conditions
// ============================================================

namespace App\Http\Middleware;

use Closure;                                           // Next-handler in the pipeline
use Illuminate\Http\Request;                           // Current HTTP request
use Illuminate\Support\Facades\Auth;                   // Authentication guard
use Symfony\Component\HttpFoundation\Response;         // Return type

/**
 * Redirect authenticated users who have not yet set a security question
 * to the security-question setup page.
 */
class EnsureSecurityQuestion
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The current HTTP request
     * @param  Closure  $next     Next middleware/handler in the pipeline
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            Auth::check() &&                                // 1. Only act on authenticated users
            ! Auth::user()->security_question &&            // 2. Who haven't set a security question
            ! $request->routeIs('security-question.*') &&  // 3. Not already on the setup page (loop guard)
            ! $request->routeIs('logout')                  // 4. Allow sign-out without interception
        ) {
            // Redirect to the security question setup form.
            // The user will be returned here after saving their question.
            return redirect()->route('security-question.edit');
        }

        // All conditions pass — continue to the intended route.
        return $next($request);
    }
}
