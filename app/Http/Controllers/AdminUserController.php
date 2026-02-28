<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * List users for admin role management.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Promote a user to admin if not already an admin.
     */
    public function promote(User $user): RedirectResponse
    {
        if (! $user->is_admin) {
            $user->update(['is_admin' => true]);
        }

        return back()->with('success', $user->name.' now has admin permissions.');
    }

    /**
     * Delete a user account (admins cannot delete themselves).
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', $user->name.'\'s account has been deleted.');
    }
}
