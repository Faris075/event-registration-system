<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /** The super admin is the oldest admin by ID and can never be demoted or deleted. */
    private function superAdminId(): ?int
    {
        return User::where('is_admin', true)->orderBy('id')->value('id');
    }

    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $searchScope = fn ($q) => $q->where(fn ($q2) => $q2
            ->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
        );

        $admins = User::where('is_admin', true)
            ->when($search, $searchScope)
            ->orderBy('id')
            ->get();

        $users = User::where('is_admin', false)
            ->when($search, $searchScope)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('admins', 'users', 'search'));
    }

    public function promote(User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('error', "{$user->name} is already an admin.");
        }

        $user->update(['is_admin' => true]);

        return back()->with('success', "{$user->name} has been promoted to admin.");
    }

    public function demote(User $user): RedirectResponse
    {
        if ($user->id === $this->superAdminId()) {
            return back()->with('error', 'The super admin cannot be demoted.');
        }

        if (! $user->is_admin) {
            return back()->with('error', "{$user->name} is not an admin.");
        }

        $user->update(['is_admin' => false]);

        return back()->with('success', "{$user->name} has been demoted to user.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === $this->superAdminId()) {
            return back()->with('error', 'The super admin account cannot be deleted.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', "{$user->name}'s account has been deleted.");
    }
}
