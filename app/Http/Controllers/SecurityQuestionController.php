<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles security question setup (for existing users) and
 * password recovery via security question.
 */
class SecurityQuestionController extends Controller
{
    /** Predefined list of security questions. */
    public static array $questions = [
        "What is your mother's maiden name?",
        "What was the name of your first pet?",
        "What city were you born in?",
        "What was the name of your first school?",
        "What is your favourite movie?",
        "What is the name of your oldest sibling?",
    ];

    /**
     * Show the security question setup form for existing users.
     */
    public function edit(): View
    {
        return view('auth.security-question', [
            'questions' => self::$questions,
            'user'      => Auth::user(),
        ]);
    }

    /**
     * Save the security question and hashed answer.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'security_question' => ['required', 'string', 'max:255'],
            'security_answer'   => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'security_question' => $request->security_question,
            'security_answer'   => Hash::make(strtolower(trim($request->security_answer))),
        ]);

        return redirect()->route('dashboard')->with('success', 'Security question saved successfully.');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Password recovery via security question
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Show the "enter your email" form for security-question-based recovery.
     */
    public function recoverForm(): View
    {
        return view('auth.recover-email');
    }

    /**
     * Look up the user by email and, if they have a security question, redirect
     * them to the answer page.
     */
    public function recoverLookup(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! $user->security_question) {
            // Vague message to avoid user enumeration.
            return back()->with('error', 'No account with a security question was found for that email.');
        }

        // Store email in session to carry through the recovery flow.
        session(['recovery_email' => $request->email]);

        return redirect()->route('security-question.answer-form');
    }

    /**
     * Show the security question to the user so they can type their answer.
     */
    public function answerForm(): View|RedirectResponse
    {
        $email = session('recovery_email');
        $user  = $email ? \App\Models\User::where('email', $email)->first() : null;

        if (! $user) {
            return redirect()->route('security-question.recover');
        }

        return view('auth.recover-answer', ['question' => $user->security_question]);
    }

    /**
     * Verify the answer; if correct, show the new-password form.
     */
    public function verifyAnswer(Request $request): RedirectResponse
    {
        $request->validate(['security_answer' => ['required', 'string']]);

        $email = session('recovery_email');
        $user  = $email ? \App\Models\User::where('email', $email)->first() : null;

        if (! $user) {
            return redirect()->route('security-question.recover');
        }

        if (! Hash::check(strtolower(trim($request->security_answer)), $user->security_answer)) {
            return back()->withErrors(['security_answer' => 'Incorrect answer. Please try again.']);
        }

        // Mark as verified so the reset form is accessible.
        session(['recovery_verified' => true]);

        return redirect()->route('security-question.reset-form');
    }

    /**
     * Show the new-password form.
     */
    public function resetForm(): View|RedirectResponse
    {
        if (! session('recovery_verified')) {
            return redirect()->route('security-question.recover');
        }

        return view('auth.recover-reset');
    }

    /**
     * Save the new password and log the user in.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        if (! session('recovery_verified')) {
            return redirect()->route('security-question.recover');
        }

        $request->validate([
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $email = session('recovery_email');
        $user  = \App\Models\User::where('email', $email)->firstOrFail();

        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);

        // Clear recovery session data.
        session()->forget(['recovery_email', 'recovery_verified']);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Password reset successfully. Welcome back!');
    }
}
