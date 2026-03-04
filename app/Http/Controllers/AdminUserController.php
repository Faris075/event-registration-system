<?php
// ============================================================
// Controller: AdminUserController
// ------------------------------------------------------------
// Provides admin-only user management endpoints:
//   GET    /admin/users            → index()   – paginated user list
//   DELETE /admin/users/{user}     → destroy() – delete account
//
// Role hierarchy:
//   Super Admin — the single seeded admin account; cannot be deleted
//                 or demoted by anyone, including themselves.
//   User        — standard authenticated account.
//
// Promotion to admin is intentionally disabled: the super admin is the
// only admin in the system. The promote() method is kept as a route
// stub that returns 403 to prevent direct PATCH requests.
//
// Best practices applied:
//  ✔ Explicit return types on every method (View / RedirectResponse)
//  ✔ Route-model binding on {user} — Laravel auto-resolves User by ID
//  ✔ Super-admin guard — the admin account can never be deleted
//  ✔ Self-deletion guard — current user cannot delete their own account
//  ✔ Single-responsibility: no auth logic, only user management
// ============================================================

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Display a paginated list of all users for role management.
     *
     * Ordering: admins are shown first (is_admin DESC), then alphabetical by name,
     * making it easy to see who already has elevated privileges.
     */
    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $admins = User::where('is_admin', true)
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->get();

        $users = User::where('is_admin', false)
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('admins', 'users', 'search'));
    }

    /**
     * Promote is disabled.
     *
     * The super admin is the only admin in the system; promotion through the
     * UI is not permitted. This stub exists so the named route resolves
     * without a 404 in case of a direct PATCH request.
     */
    public function promote(User $user): RedirectResponse
    {
        abort(403, 'Promotion to admin is not permitted.');
    }

    /**
     * Permanently delete a user account.
     *
     * Safety check: admins must not be able to delete themselves, which would
     * lock the entire admin panel if they were the only admin.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Guard: the super admin (is_admin = true) can never be deleted.
        // This is the single privileged account in the system.
        if ($user->is_admin) {
            return back()->with('error', 'The super admin account cannot be deleted.');
        }

        // Use Auth::id() (integer comparison) rather than Auth::user()->id to
        // avoid an extra accessor call and prevent potential null-dereference.
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Soft-delete would be safer in production; currently using hard-delete.
        // Associated registrations are cascade-deleted by the DB foreign key constraint.
        $user->delete();

        return back()->with('success', $user->name.'\'s account has been deleted.');
    }
}
