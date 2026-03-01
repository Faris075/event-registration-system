<?php
// ============================================================
// Controller: ProfileController
// ------------------------------------------------------------
// Manages the authenticated user's own profile:
//   GET    /profile            → edit()           – show edit form
//   PATCH  /profile            → update()         – save name/email
//   PATCH  /profile/currency   → updateCurrency() – change display currency
//   DELETE /profile            → destroy()        – close account
//
// All routes require 'auth' middleware (enforced in web.php).
//
// Best practices applied:
//  ✔ ProfileUpdateRequest encapsulates validation + unique-email rule
//  ✔ isDirty('email') detects changes before clearing email_verified_at
//  ✔ validateWithBag() scopes password errors to 'userDeletion' bag
//  ✔ Session invalidated + token regenerated after account deletion
//  ✔ Currency validated against User::CURRENCIES constant (single source of truth)
// ============================================================

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest; // Form request with email-uniqueness validation
use Illuminate\Http\RedirectResponse;       // Return type for redirects
use Illuminate\Http\Request;                // Generic HTTP request (for simple validations)
use Illuminate\Support\Facades\Auth;        // Logout + re-authentication helpers
use Illuminate\Support\Facades\Redirect;   // Fluent redirect helper with named routes
use Illuminate\View\View;                   // Return type for view-rendering actions

class ProfileController extends Controller
{
    /**
     * Display the profile edit form.
     * Pass the user via $request->user() rather than Auth::user() to
     * keep the method testable with a custom request stub.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(), // Resolves to the authenticated User model instance
        ]);
    }

    /**
     * Persist changes to the user's name and email.
     *
     * ProfileUpdateRequest handles:
     *  - required fields (name, email)
     *  - unique email rule ignoring the current user's own record
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // fill() assigns validated data without saving to the DB yet.
        $request->user()->fill($request->validated());

        // If the email address changed, clear its verified timestamp.
        // This forces re-verification and prevents unauthorized email hijacking.
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null; // Will trigger re-verification flow
        }

        // save() persists the model with a single UPDATE query.
        $request->user()->save();

        // 'profile-updated' status is read by the Blade view to show a success banner.
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Permanently delete the authenticated user's account.
     *
     * Uses validateWithBag('userDeletion') to scope validation errors
     * to a named error bag, preventing them from leaking into other form
     * error displays on the same page.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Validate current password before allowing deletion (re-auth guard).
        // 'current_password' rule confirms it matches the stored bcrypt hash.
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user(); // Cache to avoid re-fetching after logout

        Auth::logout(); // Invalidates the current auth session guard

        $user->delete(); // Hard-delete: removes the user row from the DB

        $request->session()->invalidate();       // Destroy the PHP session data
        $request->session()->regenerateToken();  // Issue a new CSRF token to prevent replay

        return Redirect::to('/'); // Send to homepage after account removal
    }

    /**
     * Update the user's preferred display currency.
     *
     * Validates against the keys of User::CURRENCIES to ensure only supported
     * ISO codes are accepted (no arbitrary string injection).
     */
    public function updateCurrency(Request $request): RedirectResponse
    {
        $request->validate([
            // implode produces 'USD,EUR,GBP,...' for the in: rule — auto-syncs with CURRENCIES
            'currency' => [
                'required',
                'string',
                'in:' . implode(',', array_keys(\App\Models\User::CURRENCIES)),
            ],
        ]);

        // update() is safe: 'currency' is in $fillable on the User model.
        $request->user()->update(['currency' => $request->currency]);

        // 'currency-updated' status is read by the profile Blade view.
        return Redirect::route('profile.edit')->with('status', 'currency-updated');
    }
}
