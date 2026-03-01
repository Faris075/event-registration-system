<?php
// ============================================================
// Controller: SecurityQuestionController
// ------------------------------------------------------------
// Two distinct feature areas:
//
//  1. SETUP (authenticated users):
//       GET  /security-question  → edit()   – show question picker
//       PATCH /security-question → update() – save hashed answer
//
//  2. PASSWORD RECOVERY (guests — 6-step flow):
//       GET  /recover-password          → recoverForm()    – enter email
//       POST /recover-password          → recoverLookup()  – find user
//       GET  /recover-password/answer   → answerForm()     – show question
//       POST /recover-password/answer   → verifyAnswer()   – check answer
//       GET  /recover-password/reset    → resetForm()      – new password
//       POST /recover-password/reset    → resetPassword()  – save + login
//
// Security design:
//  - Answers stored as bcrypt hash (Hash::make) normalised to lowercase
//  - Email lookup returns a vague error to prevent user enumeration
//  - Session gates (recovery_verified) prevent step-skipping
//  - Security question IDs stored (not full text) to allow future i18n
//
// Best practices applied:
//  ✔ Hash::make / Hash::check for answer hashing (never plaintext)
//  ✔ strtolower(trim()) normalises answers before compare/store
//  ✔ Vague error messages on email lookup (anti-enumeration)
//  ✔ Session-gated recovery steps prevent direct URL access
//  ✔ Session cleared after successful reset (tokens not reusable)
//  ✔ Auth::login() immediately after reset for seamless UX
// ============================================================

namespace App\Http\Controllers;

use App\Models\User;                   // Eloquent user model
use Illuminate\Http\RedirectResponse; // Return type for redirect responses
use Illuminate\Http\Request;          // HTTP input / validation
use Illuminate\Support\Facades\Auth;  // Auth guard (login + current user)
use Illuminate\Support\Facades\Hash;  // bcrypt hashing and verification
use Illuminate\View\View;             // Return type for view responses

/**
 * Handles security question setup (for existing users) and
 * password recovery via security question.
 */
class SecurityQuestionController extends Controller
{
    /**
     * Predefined question catalogue keyed by integer ID.
     * Storing the numeric ID in user.security_question (not the full string)
     * keeps the DB column short and allows questions to be updated/translated
     * without migrating user rows. The questionText() helper resolves IDs to text.
     *
     * @var array<int, string>
     */
    public static array $questions = [
        1 => "What is your mother's maiden name?",
        2 => "What was the name of your first pet?",
        3 => "What city were you born in?",
        4 => "What was the name of your first school?",
        5 => "What is your favourite movie?",
        6 => "What is the name of your oldest sibling?",
    ];

    /**
     * Resolve a stored security_question value to human-readable text.
     *
     * Handles two formats for backwards compatibility:
     *  - Numeric string (e.g. "3")  → look up in $questions array
     *  - Legacy full text           → return as-is (migration path)
     *
     * @param  string|null  $value  Raw value from users.security_question column
     * @return string|null          Question text or null if no question set
     */
    public static function questionText(?string $value): ?string
    {
        if ($value === null) {
            return null; // User has not yet set a security question
        }

        // is_numeric check prevents non-integer strings from being cast as array keys.
        if (isset(self::$questions[(int) $value]) && is_numeric($value)) {
            return self::$questions[(int) $value]; // Numeric ID → resolved question text
        }

        // Legacy fallback: full question text was stored pre-refactor — return unchanged.
        return $value;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Setup (authenticated users)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Show the security question setup/edit form for an already-logged-in user.
     * Passes the question catalogue so the Blade view can render a <select>.
     */
    public function edit(): View
    {
        return view('auth.security-question', [
            'questions' => self::$questions, // Array of id → question text for the <select>
            'user'      => Auth::user(),     // Pre-fill current question if already set
        ]);
    }

    /**
     * Persist the chosen security question and hashed answer.
     *
     * The answer is normalised (lowercase + trim) before hashing so that
     * "London", "london", and " London " all produce the same stored hash.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'security_question' => ['required', 'integer', 'between:1,6'], // Must be a valid question ID
            'security_answer'   => ['required', 'string', 'max:255'],      // Plain text; will be hashed
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user(); // Typed for IDE auto-completion

        $user->update([
            'security_question' => $request->security_question, // Store numeric ID (not full text)
            // Hash::make bcrypts the answer; strtolower+trim normalises case/whitespace first.
            'security_answer'   => Hash::make(strtolower(trim($request->security_answer))),
        ]);

        return redirect()->route('dashboard')->with('success', 'Security question saved successfully.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Password recovery via security question (guest-accessible 4-step flow)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Step 1: Show the email-entry form.
     * Guest-accessible — no auth middleware needed for this route.
     */
    public function recoverForm(): View
    {
        return view('auth.recover-email'); // Simple single-field form (email)
    }

    /**
     * Step 2: Look up the user by email, store in session, redirect to answer form.
     *
     * Anti-enumeration: uses a vague error message whether the email doesn't
     * exist OR the account has no security question set. This prevents attackers
     * from discovering which emails are registered.
     */
    public function recoverLookup(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // Intentionally vague: same message for "no account" and "no security question".
        if (! $user || ! $user->security_question) {
            return back()->with('error', 'No account with a security question was found for that email.');
        }

        // Persist email in session to carry it through subsequent recovery steps.
        // The session is PHP-level (server-side) — not stored in the cookie value itself.
        session(['recovery_email' => $request->email]);

        return redirect()->route('security-question.answer-form'); // Proceed to step 3
    }

    /**
     * Step 3: Display the security question so the user can type their answer.
     *
     * If recovery_email is missing from session (direct URL access), redirect
     * back to step 1 to enforce proper flow order.
     */
    public function answerForm(): View|RedirectResponse
    {
        $email = session('recovery_email');                                    // Read step-2 session
        $user  = $email ? User::where('email', $email)->first() : null; // Look up user

        if (! $user) {
            // Session expired or user navigated directly — restart from the beginning.
            return redirect()->route('security-question.recover');
        }

        return view('auth.recover-answer', [
            // questionText() handles both numeric IDs and legacy full-text values.
            'question' => self::questionText($user->security_question),
        ]);
    }

    /**
     * Step 4: Verify the submitted answer against the stored hash.
     *
     * On success: set recovery_verified flag in session and redirect to reset form.
     * On failure: return an error without revealing the correct answer.
     */
    public function verifyAnswer(Request $request): RedirectResponse
    {
        $request->validate(['security_answer' => ['required', 'string']]);

        $email = session('recovery_email');
        $user  = $email ? User::where('email', $email)->first() : null;

        if (! $user) {
            return redirect()->route('security-question.recover'); // Session expired
        }

        // Normalise the submitted answer the same way it was normalised on save.
        // Hash::check compares plain text against the stored bcrypt hash.
        if (! Hash::check(strtolower(trim($request->security_answer)), $user->security_answer)) {
            return back()->withErrors(['security_answer' => 'Incorrect answer. Please try again.']);
        }

        // Gate flag: resetForm() and resetPassword() check this before proceeding.
        session(['recovery_verified' => true]);

        return redirect()->route('security-question.reset-form'); // Proceed to step 5
    }

    /**
     * Step 5: Show the new-password form.
     * Guards against direct URL access by checking the recovery_verified session flag.
     */
    public function resetForm(): View|RedirectResponse
    {
        // If recovery_verified is not in session, the user skipped step 4 — restart flow.
        if (! session('recovery_verified')) {
            return redirect()->route('security-question.recover');
        }

        return view('auth.recover-reset'); // Simple new-password + confirm form
    }

    /**
     * Step 6: Validate and persist the new password, then log the user in.
     *
     * Password::defaults() enforces the application-wide password complexity rules
     * (configured in AppServiceProvider or AuthServiceProvider).
     * After reset, all recovery session keys are cleared so the tokens cannot be reused.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        // Re-check session gate in case the user opened the form in a second tab.
        if (! session('recovery_verified')) {
            return redirect()->route('security-question.recover');
        }

        $request->validate([
            // 'confirmed' rule requires a matching password_confirmation field in the form.
            // Password::defaults() applies min-length, mixed-case, symbols rules.
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $email = session('recovery_email');
        // firstOrFail throws a 404 if the user was deleted between steps — safe fallback.
        $user  = User::where('email', $email)->firstOrFail();

        // Hash::make bcrypts the new password before storage.
        $user->update(['password' => Hash::make($request->password)]);

        // Clear all recovery session keys — tokens are single-use by design.
        session()->forget(['recovery_email', 'recovery_verified']);

        // Log the user in immediately for seamless UX (no need to re-enter credentials).
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Password reset successfully. Welcome back!');
    }
}
