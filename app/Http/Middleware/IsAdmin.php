<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard admin-only routes.
 *
 * Aborts with a 403 Forbidden response if the current user is not authenticated
 * or does not have the `is_admin` flag set.  Aliased as 'admin' middleware in
 * bootstrap/app.php so routes can be protected with `->middleware('admin')`.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Reject unauthenticated requests and non-admin users.
        if (!Auth::check() || !Auth::user()->is_admin) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
