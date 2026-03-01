<?php
// ============================================================
// Middleware: IsAdmin
// ------------------------------------------------------------
// Protects admin-only routes by aborting with 403 Forbidden
// if the authenticated user does not have is_admin = true.
//
// Registered as the 'admin' alias in bootstrap/app.php so routes
// can be protected concisely with ->middleware('admin') or
// Route::middleware('admin')->group(...).
//
// Best practices applied:
//  ✔ Checks both Auth::check() AND is_admin in one guard to handle
//    unauthenticated requests (no null-dereference on Auth::user())
//  ✔ abort(403) returns a proper HTTP status and halts the pipeline
//  ✔ Typed Closure and Response hints for IDE support
//  ✔ No redirect — 403 is semantically correct for a missing privilege
// ============================================================

namespace App\Http\Middleware;

use Closure;                                              // PSR-15 next-handler type
use Illuminate\Http\Request;                              // HTTP request abstraction
use Illuminate\Support\Facades\Auth;                      // Authentication guard helper
use Symfony\Component\HttpFoundation\Response;            // Return type for the handle method

/**
 * Guard admin-only routes.
 *
 * Aborts with a 403 Forbidden response if the current user is not authenticated
 * or does not have the `is_admin` flag set.  Aliased as 'admin' middleware in
 * bootstrap/app.php so routes can be protected with `->middleware('admin')`.
 */
class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The current HTTP request
     * @param  Closure  $next     The next middleware/handler in the pipeline
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Auth::check() returns false for unauthenticated (guest) requests.
        // Auth::user()->is_admin must be cast to bool; DB stores it as tinyint(1).
        // The short-circuit && prevents a null-pointer call on Auth::user() for guests.
        if (! Auth::check() || ! Auth::user()->is_admin) {
            // abort(403) throws an HttpException which Laravel converts to a 403 response.
            // The second argument appears in the response body / error page.
            abort(403, 'Unauthorized access');
        }

        // Pass the request to the next layer in the middleware pipeline.
        return $next($request);
    }
}
