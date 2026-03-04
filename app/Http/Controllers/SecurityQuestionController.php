<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SecurityQuestionController extends Controller
{
    /** @var array<int, string> Predefined question catalogue keyed by ID. */
    public static array $questions = [
        1 => "What is your mother's maiden name?",
        2 => "What was the name of your first pet?",
        3 => "What city were you born in?",
        4 => "What was the name of your first school?",
        5 => "What is your favourite movie?",
        6 => "What is the name of your oldest sibling?",
    ];

    /** Resolve a stored security_question ID (or legacy text) to human-readable text. */
    public static function questionText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && isset(self::$questions[(int) $value])) {
            return self::$questions[(int) $value];
        }

        return $value; // legacy full-text fallback
    }

    // ── Setup (authenticated users) ───────────────────────────────────────────

    public function edit(): View
    {
        return view('auth.security-question', [
            'questions' => self::$questions,
            'user'      => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'security_question' => ['required', 'integer', 'between:1,6'],
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

    // ── Password recovery (guest 6-step flow) ─────────────────────────────────

    public function recoverForm(): View
    {
        return view('auth.recover-email');
    }

    public function recoverLookup(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->security_question) {
            return back()->with('error', 'No account with a security question was found for that email.');
        }

        session(['recovery_email' => $request->email]);

        return redirect()->route('security-question.answer-form');
    }

    public function answerForm(): View|RedirectResponse
    {
        $email = session('recovery_email');
        $user  = $email ? User::where('email', $email)->first() : null;

        if (! $user) {
            return redirect()->route('security-question.recover');
        }

        return view('auth.recover-answer', [
            'question' => self::questionText($user->security_question),
        ]);
    }

    public function verifyAnswer(Request $request): RedirectResponse
    {
        $request->validate(['security_answer' => ['required', 'string']]);

        $email = session('recovery_email');
        $user  = $email ? User::where('email', $email)->first() : null;

        if (! $user) {
            return redirect()->route('security-question.recover');
        }

        if (! Hash::check(strtolower(trim($request->security_answer)), $user->security_answer)) {
            return back()->withErrors(['security_answer' => 'Incorrect answer. Please try again.']);
        }

        session(['recovery_verified' => true]);

        return redirect()->route('security-question.reset-form');
    }

    public function resetForm(): View|RedirectResponse
    {
        if (! session('recovery_verified')) {
            return redirect()->route('security-question.recover');
        }

        return view('auth.recover-reset');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        if (! session('recovery_verified')) {
            return redirect()->route('security-question.recover');
        }

        $request->validate([
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $email = session('recovery_email');
        $user  = User::where('email', $email)->firstOrFail();

        $user->update(['password' => Hash::make($request->password)]);

        session()->forget(['recovery_email', 'recovery_verified']);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Password reset successfully. Welcome back!');
    }
}
