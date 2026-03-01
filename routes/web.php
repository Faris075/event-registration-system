<?php
// ============================================================
// Web Routes  (routes/web.php)
// ------------------------------------------------------------
// All HTTP routes for the web interface are defined here.
// Auth routes (login, register, password reset) are in auth.php
// and included via require at the bottom of this file.
//
// Route organisation:
//  1. Public           — accessible without authentication
//  2. Dashboard        — authenticated users only (+ email verified)
//  3. Event listing    — public
//  4. Admin event mgmt — 'admin' middleware (IsAdmin) required
//  5. Event detail     — public
//  6. Registration flow— 'auth' middleware required
//  7. Confirmation     — public (session-driven, shareable URL)
//  8. Admin reg. mgmt  — 'admin' middleware required
//  9. Admin user mgmt  — 'admin' middleware required
// 10. Security question setup — 'auth' middleware
// 11. Password recovery       — public (guest flow)
// 12. Profile management      — 'auth' middleware
//
// Best practices applied:
//  ✔ Named routes throughout → route() helper; no hard-coded URLs
//  ✔ Static routes (/events/create) defined BEFORE {event} wildcard
//    so "create" is never mistaken for an event ID
//  ✔ Middleware groups reduce per-route repetition
//  ✔ Namespaced route names (admin.events.*, admin.users.*)
//  ✔ Correct HTTP verbs: DELETE for remove, PATCH for partial update,
//    PUT for full replacement, POST for creation
// ============================================================

use App\Http\Controllers\SecurityQuestionController;     // Security Q setup + recovery flow
use App\Http\Controllers\EventController;                // Event CRUD
use App\Http\Controllers\EventRegistrationController;    // Full registration lifecycle
use App\Http\Controllers\ProfileController;              // User profile management
use App\Http\Controllers\AdminUserController;            // Admin: manage users
use App\Models\Attendee;                                 // Needed in the dashboard route closure
use App\Models\Registration;                             // Needed in the dashboard route closure
use Illuminate\Support\Facades\Auth;                     // Auth::user() inside closures
use Illuminate\Support\Facades\Route;                    // Route definition facade

// ── Public routes ─────────────────────────────────────────────────────────────

// Route::view() is a convenience shorthand for routes that just return a view.
Route::view('/', 'home')->name('home');       // Landing page
Route::view('/terms', 'terms')->name('terms'); // Static terms & conditions page

// ── Dashboard (authenticated users) ───────────────────────────────────────────
// Resolves the logged-in user's attendee record by email, then loads their
// registration history with the associated event eager-loaded.

Route::get('dashboard', function () {
    // Find the Attendee linked to this User by matching email fields.
    // Null-safe ?-> operator handles edge cases where Auth::user() may be null.
    $attendee = Attendee::where('email', Auth::user()?->email)->first();

    $registrations = $attendee
        ? Registration::with('event')              // Eager-load associated event (N+1 prevention)
            ->where('attendee_id', $attendee->id)
            ->latest('registration_date')          // Most recent first
            ->get()
        : collect();                               // Empty collection for users with no registrations yet

    return view('dashboard', compact('registrations'));
})->middleware(['auth', 'verified'])->name('dashboard'); // 'verified' enforces email confirmation

// ── Event listing (public) ────────────────────────────────────────────────────

// No middleware — guests can see the published event list.
// Controller applies additional filters for non-admin users (future, available seats only).
Route::get('events', [EventController::class, 'index'])->name('events.index');

// ── Admin-only event management ───────────────────────────────────────────────
// ⚠️  Static routes MUST come BEFORE the {event} wildcard route below, otherwise
// the string "create" would be resolved as an event ID by route-model binding.

Route::middleware('admin')->group(function () {
    Route::get('events/create', [EventController::class, 'create'])->name('events.create'); // Show create form
    Route::post('events', [EventController::class, 'store'])->name('events.store');         // Persist new event
});

// ── Event detail (public) ─────────────────────────────────────────────────────
// Route-model binding: {event} → resolved to Event instance by ID automatically.

Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

// ── Registration & payment flow (authenticated users) ─────────────────────────
// Registration details are held in the session between the register form and
// the payment page; the DB record is only written after payment succeeds.

Route::middleware('auth')->group(function () {
    Route::get('events/{event}/register', [EventRegistrationController::class, 'create'])->name('events.register.page');           // Step 1: form
    Route::post('events/{event}/register', [EventRegistrationController::class, 'store'])->name('events.register');                // Step 2: validate + session
    Route::get('events/{event}/payment', [EventRegistrationController::class, 'showPayment'])->name('events.payment.page');        // Step 3: payment form
    Route::post('events/{event}/payment', [EventRegistrationController::class, 'processPayment'])->name('events.payment.process'); // Step 4: write DB record
    // DELETE verb correctly expresses "remove this resource" for cancellations.
    Route::delete('events/{event}/registrations/{registration}/cancel', [EventRegistrationController::class, 'cancelMyRegistration'])->name('events.registration.cancel');
});

// Step 5 confirmation is public (session-driven) — the session data is only readable by its owner.
Route::get('events/{event}/registration-confirmation', [EventRegistrationController::class, 'confirmation'])->name('events.registration.confirmation');

// ── Admin event & registration management ─────────────────────────────────────

Route::middleware('admin')->group(function () {
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');          // Show edit form
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');           // PUT = full resource update
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');      // Hard-delete event (cascade deletes registrations)

    // Admin registration management (paginated list, CSV export, status update, force-add)
    Route::get('admin/events/{event}/registrations', [EventRegistrationController::class, 'index'])
        ->name('admin.events.registrations.index');
    Route::get('admin/events/{event}/registrations/export', [EventRegistrationController::class, 'export'])
        ->name('admin.events.registrations.export');       // Streams CSV; no memory buffering
    Route::patch('admin/events/{event}/registrations/{registration}', [EventRegistrationController::class, 'update'])
        ->name('admin.events.registrations.update');       // PATCH = partial status/payment_status update
    Route::post('admin/events/{event}/registrations/force-add', [EventRegistrationController::class, 'adminForceAdd'])
        ->name('admin.events.registrations.force-add');    // Bypass capacity (max 5 overrides/event)

    // ── Admin user management ─────────────────────────────────────────────────

    Route::get('admin/users', [AdminUserController::class, 'index'])
        ->name('admin.users.index');                       // Paginated user list with role info
    Route::patch('admin/users/{user}/promote', [AdminUserController::class, 'promote'])
        ->name('admin.users.promote');                     // Grant admin role (idempotent)
    Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy'])
        ->name('admin.users.destroy');                     // Hard-delete user (guards self-deletion)
});

// ── Security question setup (authenticated users without a question) ───────────
// EnsureSecurityQuestion middleware (web group) redirects here automatically.

Route::middleware('auth')->group(function () {
    Route::get('security-question', [SecurityQuestionController::class, 'edit'])->name('security-question.edit');       // Setup form
    Route::patch('security-question', [SecurityQuestionController::class, 'update'])->name('security-question.update'); // Save question + hashed answer
});

// ── Password recovery via security question (guest-accessible) ────────────────
// 6-step session-gated flow for users who cannot log in.

Route::get('recover-password', [SecurityQuestionController::class, 'recoverForm'])->name('security-question.recover');            // Step 1
Route::post('recover-password', [SecurityQuestionController::class, 'recoverLookup'])->name('security-question.recover.lookup'); // Step 2
Route::get('recover-password/answer', [SecurityQuestionController::class, 'answerForm'])->name('security-question.answer-form'); // Step 3
Route::post('recover-password/answer', [SecurityQuestionController::class, 'verifyAnswer'])->name('security-question.verify');   // Step 4
Route::get('recover-password/reset', [SecurityQuestionController::class, 'resetForm'])->name('security-question.reset-form');   // Step 5
Route::post('recover-password/reset', [SecurityQuestionController::class, 'resetPassword'])->name('security-question.reset');   // Step 6

// ── Profile management (authenticated users) ──────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');                        // Show profile form
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');                  // Update name/email
    Route::patch('/profile/currency', [ProfileController::class, 'updateCurrency'])->name('profile.currency.update'); // Change display currency
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');               // Close account
});

// Include Breeze-generated auth routes (login, register, password reset, email verification).
require __DIR__.'/auth.php';
