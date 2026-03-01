<?php
// ============================================================
// Controller: AdminUserController
// ------------------------------------------------------------
// Provides admin-only user management endpoints:
//   GET  /admin/users            → index()   – paginated user list
//   PATCH /admin/users/{user}/promote → promote() – grant admin role
//   DELETE /admin/users/{user}   → destroy() – delete account
//
// All routes in web.php are wrapped in ->middleware('admin'), so this
// controller does NOT need to re-check auth / is_admin itself.
//
// Best practices applied:
//  ✔ Explicit return types on every method (View / RedirectResponse)
//  ✔ Route-model binding on {user} — Laravel auto-resolves User by ID
//  ✔ Idempotent promote() — silently skips if already admin
//  ✔ Self-deletion guard — admins cannot accidentally delete themselves
//  ✔ Single-responsibility: no auth logic, only user management
// ============================================================

namespace App\Http\Controllers;

use App\Models\User;                          // Eloquent model for the users table
use Illuminate\Http\RedirectResponse;         // Return type for redirect actions
use Illuminate\Support\Facades\Auth;          // Resolves current authenticated user ID
use Illuminate\View\View;                     // Return type for view-rendering actions

class AdminUserController extends Controller
{
    /**
     * Display a paginated list of all users for role management.
     *
     * Ordering: admins are shown first (is_admin DESC), then alphabetical by name,
     * making it easy to see who already has elevated privileges.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('is_admin') // Admins to the top of the list
            ->orderBy('name')         // Secondary alphabetic sort within each group
            ->paginate(20);           // 20 per page; appends ?page=N to URLs automatically

        // Passes a LengthAwarePaginator; Blade uses $users->links() for pagination UI.
        return view('admin.users.index', compact('users'));
    }

    /**
     * Grant admin privileges to a user.
     *
     * Idempotent: calling promote() on an already-admin user is safe — no DB
     * write is issued, and the success flash is still shown for UX consistency.
     */
    public function promote(User $user): RedirectResponse
    {
        // Guard: only write to DB if the flag needs to change (avoids a pointless UPDATE).
        if (! $user->is_admin) {
            $user->update(['is_admin' => true]); // Mass-assign via $fillable whitelist
        }

        // back() redirects to the HTTP_REFERER; safe here because the route is admin-only.
        return back()->with('success', $user->name.' now has admin permissions.');
    }

    /**
     * Permanently delete a user account.
     *
     * Safety check: admins must not be able to delete themselves, which would
     * lock the entire admin panel if they were the only admin.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Use Auth::id() (integer comparison) rather than Auth::user()->id to
        // avoid an extra accessor call and prevent potential null-dereference.
        if ($user->id === Auth::id()) {
            // Return an error flash; do NOT throw an exception so the session stays intact.
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Soft-delete would be safer in production; currently using hard-delete.
        // Associated registrations are cascade-deleted by the DB foreign key constraint.
        $user->delete();

        return back()->with('success', $user->name.'\'s account has been deleted.');
    }
}
