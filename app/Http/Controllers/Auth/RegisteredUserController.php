<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Handles new-user registration.
 *
 * Validates the incoming registration request (including a mandatory security
 * question and answer), creates the User record, fires the Registered event,
 * and logs the user in.  The security answer is normalised to lowercase before
 * hashing so comparison is case-insensitive.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone'             => ['nullable', 'string', 'regex:/^[0-9]{9,11}$/'],
            'password'          => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'security_question' => ['required', 'integer', 'between:1,6'],
            'security_answer'   => ['required', 'string', 'max:255'],
        ], [
            'phone.regex'      => 'Phone number must be 9–11 digits only (no spaces or symbols).',
            'password.mixed'   => 'Password must contain at least one uppercase and one lowercase letter.',
            'password.numbers' => 'Password must contain at least one number.',
            'password.symbols' => 'Password must contain at least one symbol (e.g. @, #, !).',
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'security_question' => $request->security_question,
            'security_answer'   => Hash::make(strtolower(trim($request->security_answer))),
        ]);

        if ($request->filled('phone')) {
            \App\Models\Attendee::updateOrCreate(
                ['email' => $user->email],
                ['name' => $user->name, 'phone' => $request->phone]
            );
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
