<?php

use App\Http\Controllers\SecurityQuestionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminUserController;
use App\Models\Attendee;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public routes ─────────────────────────────────────────────────────────────

Route::view('/', 'home')->name('home');
Route::view('/terms', 'terms')->name('terms');

// ── Dashboard (authenticated users) ───────────────────────────────────────────
// Resolves the logged-in user's attendee record by email, then loads their
// registration history with the associated event eager-loaded.

Route::get('dashboard', function () {
    $attendee = Attendee::where('email', Auth::user()?->email)->first();
    $registrations = $attendee
        ? Registration::with('event')
            ->where('attendee_id', $attendee->id)
            ->latest('registration_date')
            ->get()
        : collect();
    return view('dashboard', compact('registrations'));
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Event listing (public) ────────────────────────────────────────────────────

Route::get('events', [EventController::class, 'index'])->name('events.index');

// ── Admin-only event management ───────────────────────────────────────────────
// Static routes must come before the {event} wildcard route to avoid clashing.

Route::middleware('admin')->group(function () {
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
});

// ── Event detail (public) ─────────────────────────────────────────────────────

Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

// ── Registration & payment flow (authenticated users) ─────────────────────────
// Registration details are held in the session between the register form and
// the payment page; the DB record is only written after payment succeeds.

Route::middleware('auth')->group(function () {
    Route::get('events/{event}/register', [EventRegistrationController::class, 'create'])->name('events.register.page');
    Route::post('events/{event}/register', [EventRegistrationController::class, 'store'])->name('events.register');
    Route::get('events/{event}/payment', [EventRegistrationController::class, 'showPayment'])->name('events.payment.page');
    Route::post('events/{event}/payment', [EventRegistrationController::class, 'processPayment'])->name('events.payment.process');
    Route::delete('events/{event}/registrations/{registration}/cancel', [EventRegistrationController::class, 'cancelMyRegistration'])->name('events.registration.cancel');
});

// Confirmation page is guest-accessible (session-driven) so shareable links work.
Route::get('events/{event}/registration-confirmation', [EventRegistrationController::class, 'confirmation'])->name('events.registration.confirmation');

// ── Admin event & registration management ─────────────────────────────────────

Route::middleware('admin')->group(function () {
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::get('admin/events/{event}/registrations', [EventRegistrationController::class, 'index'])
        ->name('admin.events.registrations.index');
    Route::get('admin/events/{event}/registrations/export', [EventRegistrationController::class, 'export'])
        ->name('admin.events.registrations.export');
    Route::patch('admin/events/{event}/registrations/{registration}', [EventRegistrationController::class, 'update'])
        ->name('admin.events.registrations.update');
    Route::post('admin/events/{event}/registrations/force-add', [EventRegistrationController::class, 'adminForceAdd'])
        ->name('admin.events.registrations.force-add');

    // ── Admin user management ─────────────────────────────────────────────────

    Route::get('admin/users', [AdminUserController::class, 'index'])
        ->name('admin.users.index');
    Route::patch('admin/users/{user}/promote', [AdminUserController::class, 'promote'])
        ->name('admin.users.promote');
    Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy'])
        ->name('admin.users.destroy');
});

// ── Security question setup (authenticated users without a question) ───────────

Route::middleware('auth')->group(function () {
    Route::get('security-question', [SecurityQuestionController::class, 'edit'])->name('security-question.edit');
    Route::patch('security-question', [SecurityQuestionController::class, 'update'])->name('security-question.update');
});

// ── Password recovery via security question (guest-accessible) ────────────────

Route::get('recover-password', [SecurityQuestionController::class, 'recoverForm'])->name('security-question.recover');
Route::post('recover-password', [SecurityQuestionController::class, 'recoverLookup'])->name('security-question.recover.lookup');
Route::get('recover-password/answer', [SecurityQuestionController::class, 'answerForm'])->name('security-question.answer-form');
Route::post('recover-password/answer', [SecurityQuestionController::class, 'verifyAnswer'])->name('security-question.verify');
Route::get('recover-password/reset', [SecurityQuestionController::class, 'resetForm'])->name('security-question.reset-form');
Route::post('recover-password/reset', [SecurityQuestionController::class, 'resetPassword'])->name('security-question.reset');

// ── Profile management (authenticated users) ──────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/currency', [ProfileController::class, 'updateCurrency'])->name('profile.currency.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
