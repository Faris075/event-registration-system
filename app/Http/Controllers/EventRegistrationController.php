<?php
// ============================================================
// Controller: EventRegistrationController
// ------------------------------------------------------------
// Manages the entire event-registration lifecycle for attendees
// and provides admin helpers for managing registrations.
//
// â”€â”€ Public registration flow (5 steps) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  1. create()         â€“ show registration details form
//  2. store()          â€“ validate + hold details in session
//  3. showPayment()    â€“ render mock payment page
//  4. processPayment() â€“ validate card, persist DB record
//                        inside a pessimistic-lock transaction,
//                        send confirmation email
//  5. confirmation()   â€“ session-driven post-registration summary
//
// â”€â”€ Admin helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  - adminForceAdd()          â€“ bypass capacity (â‰¤5 overrides/event)
//  - index()                  â€“ admins view and filter registrations
//  - export()                 â€“ stream CSV download of registrations
//  - update()                 â€“ change status; cancel auto-promotes waitlist
//  - cancelMyRegistration()   â€“ users cancel own booking; triggers refund label
//
// Best practices applied:
//  âœ” DB::transaction + lockForUpdate() prevents double-booking (race conditions)
//  âœ” Mail errors caught + reported without aborting the HTTP request
//  âœ” Session-scoped pending data (keyed by event ID) supports multiple tabs
//  âœ” updateOrCreate for Attendee avoids duplicate contact rows
//  âœ” wasRecentlyCreated flag provides "saved/updated" feedback without extra query
//  âœ” COALESCE in waitlist ordering handles null positions gracefully
//  âœ” exists() used where a boolean check is all that's needed (cheaper than first())
//  âœ” sameDayConflicts() only warns â€” does not block (user autonomy preserved)
//  âœ” Explicit return types on all public/protected methods
// ============================================================

namespace App\Http\Controllers;

use App\Mail\RegistrationConfirmationMail; // Confirmation email sent after successful payment
use App\Mail\WaitlistPromotedMail;         // Notification email when waitlisted attendee is promoted
use App\Models\Attendee;                   // Contact record: name, email, phone, company
use App\Models\Event;                      // Event model; route-model binding resolves {event}
use App\Models\Registration;               // Join-table between Event and Attendee
use Illuminate\Http\RedirectResponse;      // Return type for redirect responses
use Illuminate\Http\Request;               // HTTP input, validation, session helpers
use Illuminate\Support\Collection;         // Typed collection; used as sameDayConflicts() return
use Illuminate\Support\Facades\Auth;       // Current-user auth helpers
use Illuminate\Support\Facades\DB;         // Raw transactions + query builder
use Illuminate\Support\Facades\Mail;       // Driver-agnostic mail dispatcher
use Illuminate\View\View;                  // Return type for view-rendering actions
use Symfony\Component\HttpFoundation\StreamedResponse; // Return type for streamed CSV downloads

/**
 * Handles the full event-registration lifecycle.
 *
 * Flow:
 *  1. `create`          â€” show the registration details form
 *  2. `store`           â€” validate details and hold them in the session
 *  3. `showPayment`     â€” show the mock payment page
 *  4. `processPayment`  â€” validate card details, write the DB record inside a
 *                         pessimistic-locking transaction, send confirmation mail
 *  5. `confirmation`    â€” display the post-registration summary (session-driven)
 *
 * Admin helpers:
 *  - `adminForceAdd`  â€” bypass capacity limits (up to 5 overrides per event)
 *  - `index`          â€” paginated, filterable registration list for an event
 *  - `export`         â€” stream a filtered CSV download
 *  - `update`         â€” change registration status; cancellation auto-promotes
 *                       the next waitlisted attendee
 */
class EventRegistrationController extends Controller
{
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Registration flow â€“ Steps 1â€“5
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Step 1: Show the registration details form.
     *
     * Guards against attempting to register for a past event (time-of-request check).
     * Passes any same-day conflict events so the Blade view can display a warning banner.
     */
    public function create(Event $event): View|RedirectResponse
    {
        // Server-side past-event guard (the event listing already filters these out,
        // but a direct URL could still reach this page after the event passes).
        if (now()->greaterThanOrEqualTo($event->date_time)) {
            return redirect()
                ->route('events.show', $event)
                ->withErrors(['registration' => 'Registration is closed â€” this event has already taken place.']);
        }

        // Detect other confirmed events on the same calendar day for this user.
        // sameDayConflicts() returns an empty Collection for guests or users with no attendee record.
        $conflictEvents = $this->sameDayConflicts($event);

        return view('events.register', compact('event', 'conflictEvents'));
    }

    /**
     * Step 2: Validate registration details and persist them in the session.
     *
     * The DB record is NOT written here â€” data is held in session until
     * payment succeeds in processPayment(). This prevents orphaned registrations
     * from users who abandon the payment page.
     *
     * Session key is scoped by event ID to support multiple browser tabs.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        // Re-check past-event guard in case time passed between showing the form and submitting.
        if (now()->greaterThanOrEqualTo($event->date_time)) {
            return back()->withErrors([
                'registration' => 'Registration is closed because this event date has passed.',
            ]);
        }

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            // Regex accepts international formats: +44 7700 900000, (123) 456-7890, etc.
            'phone'          => ['nullable', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
            'company'        => ['nullable', 'string', 'max:255'],
            // 'accepted' rule ensures the value is '1', 'true', 'on', or 'yes'.
            'terms_accepted' => ['accepted'],
        ], [
            // Custom message overrides the default "The terms accepted field must be accepted."
            'terms_accepted.accepted' => 'You must accept the Terms & Conditions to register.',
        ]);

        // Store validated data in session under an event-scoped key.
        // Using event ID in the key prevents cross-event data leakage if two tabs are open.
        session(['pending_registration.' . $event->id => $validated]);

        // Surface a same-day conflict warning on the payment page.
        // flash() means the value survives exactly ONE redirect (discarded after).
        $conflicts = $this->sameDayConflicts($event);
        if ($conflicts->isNotEmpty()) {
            $conflictTitles = $conflicts->pluck('title')->join(', ');
            session()->flash(
                'conflict_warning',
                'Note: you are already registered for another event on this day (' . $conflictTitles . '). Proceeding anyway.'
            );
        }

        return redirect()->route('events.payment.page', $event); // Proceed to step 3
    }

    /**
     * Step 3: Display the mock payment form.
     *
     * Requires a pending registration in session. If missing (user navigated
     * directly to /payment), redirect back to the registration form.
     */
    public function showPayment(Event $event): View|RedirectResponse
    {
        // Read the session data stored in step 2.
        $pending = session('pending_registration.' . $event->id);

        if (! $pending) {
            // No pending data â€” user may have opened the URL directly or session expired.
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Please fill in your registration details first.']);
        }

        // Pass $pending to pre-populate any attendee info displayed on the payment page.
        return view('events.payment', compact('event', 'pending'));
    }

    /**
     * Step 4: Validate payment details and finalise the registration in the DB.
     *
     * Concurrency safety:
     *  All reads and writes within the DB::transaction use lockForUpdate() (SELECT â€¦ FOR UPDATE).
     *  This acquires a row-level exclusive lock and prevents two simultaneous requests
     *  from both seeing "seats available" and creating duplicate confirmed registrations.
     *
     * Duplicate handling:
     *  If the attendee already has a registration for this event, the method returns
     *  existing status data without creating a duplicate â€” idempotent by design.
     *
     * Waitlist logic:
     *  confirmed seats â‰¥ capacity  â†’ waitlisted (if waitlist slots remain)
     *  waitlisted seats â‰¥ waitlistCapacity â†’ throws RuntimeException('waitlist_full')
     */
    public function processPayment(Request $request, Event $event): RedirectResponse
    {
        // Re-check session gate (could have expired between step 3 and step 4).
        $pending = session('pending_registration.' . $event->id);

        if (! $pending) {
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Session expired. Please re-enter your details.']);
        }

        // Validate mock card details. In production, replace with a real payment gateway.
        $request->validate([
            'card_name'   => ['required', 'string', 'max:255'],
            'card_number' => ['required', 'digits:16'],              // Exactly 16 numeric digits
            'card_expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'], // MM/YY format
            'card_cvv'    => ['required', 'digits_between:3,4'],     // 3 digits (Visa/MC) or 4 (Amex)
        ], [
            'card_number.digits'          => 'Card number must be exactly 16 digits.',
            'card_expiry.regex'           => 'Expiry must be in MM/YY format.',
            'card_cvv.digits_between'     => 'CVV must be 3 or 4 digits.',
        ]);

        $validated = $pending; // Attendee data from step-2 session

        try {
            // All DB operations inside this closure execute within a single transaction.
            // lockForUpdate() prevents other transactions from reading/modifying the same rows
            // until this transaction commits â€” the key concurrency safety mechanism.
            $result = DB::transaction(function () use ($event, $validated) {
                // Lock the event row to prevent capacity from changing mid-transaction.
                $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                // updateOrCreate either inserts a new Attendee or updates the existing one
                // (matched by email). This deduplicates contact records in the attendees table.
                $attendee = Attendee::updateOrCreate(
                    ['email' => $validated['email']],       // Search key
                    [
                        'name'    => $validated['name'],    // Update or set name
                        'phone'   => $validated['phone'] ?? null,
                        'company' => $validated['company'] ?? null,
                    ]
                );

                // wasRecentlyCreated is set by Eloquent after updateOrCreate:
                //   true  = INSERT occurred (new attendee)
                //   false = UPDATE occurred (returning attendee)
                $infoStatus = $attendee->wasRecentlyCreated ? 'saved' : 'updated';

                // Lock the existing registration row (if any) to prevent concurrent cancellation
                // racing against this INSERT decision.
                $existingRegistration = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('attendee_id', $attendee->id)
                    ->lockForUpdate()
                    ->first();

                // Duplicate-registration guard: if a registration already exists, return its
                // current state without creating another row.
                if ($existingRegistration) {
                    return [
                        'event'             => $lockedEvent,
                        'attendee_name'     => $attendee->name,
                        'attendee_email'    => $attendee->email,
                        'info_status'       => $infoStatus,
                        'status'            => $existingRegistration->status,
                        'waitlist_position' => $existingRegistration->waitlist_position,
                        'duplicate'         => true, // Flag consumed post-transaction for UX message
                    ];
                }

                // Lock and count confirmed registrations to make the capacity decision.
                // Must be locked to prevent two concurrent transactions both reading "N < capacity".
                $confirmedCount = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('status', 'confirmed')
                    ->lockForUpdate()
                    ->count();

                $status           = 'confirmed'; // Optimistic default
                $waitlistPosition = null;         // Only set when waitlisting

                if ($confirmedCount >= $lockedEvent->capacity) {
                    // Event is full â€” attempt to place on waitlist.
                    $waitlistCap = $lockedEvent->waitlistCapacity(); // 25% of capacity, min 1

                    $currentWaitlisted = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->count();

                    if ($currentWaitlisted >= $waitlistCap) {
                        // Both event and waitlist are full â€” throw to trigger a user-facing error.
                        // Using RuntimeException with a message sentinel avoids a custom exception class.
                        throw new \RuntimeException('waitlist_full');
                    }

                    $status = 'waitlisted'; // Downgrade from confirmed to waitlisted

                    // Calculate the next sequential waitlist position.
                    // max('waitlist_position') returns null if no waitlisted rows exist â†’ defaults to 0.
                    $maxWaitlistPosition = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->max('waitlist_position');

                    $waitlistPosition = ($maxWaitlistPosition ?? 0) + 1; // 1-based queue position
                }

                // Persist the new registration. All fields must be in Registration::$fillable.
                Registration::create([
                    'event_id'          => $lockedEvent->id,
                    'attendee_id'       => $attendee->id,
                    'status'            => $status,            // confirmed OR waitlisted
                    'waitlist_position' => $waitlistPosition,  // null for confirmed
                    'payment_status'    => 'paid',             // Mock payment always succeeds
                    'is_admin_override' => false,              // Normal public registration
                ]);

                // Return a plain array (not model instances beyond $lockedEvent)
                // because the closure cannot close over the outer $result variable.
                return [
                    'event'             => $lockedEvent,
                    'attendee_name'     => $attendee->name,
                    'attendee_email'    => $attendee->email,
                    'info_status'       => $infoStatus,
                    'status'            => $status,
                    'waitlist_position' => $waitlistPosition,
                    'duplicate'         => false,
                ];
            }); // DB::transaction auto-commits here; rolls back on any exception
        } catch (\RuntimeException $e) {
            // Handle known sentinel error without exposing internal exception details.
            if ($e->getMessage() === 'waitlist_full') {
                return back()->withErrors([
                    'payment' => 'This event is fully booked and the waitlist ('.$event->waitlistCapacity().' spots) is also full.',
                ]);
            }
            throw $e; // Re-throw unexpected RuntimeExceptions for global error handling
        }

        // Step 4 succeeded â€” clear session data so the pending key cannot be replayed.
        session()->forget('pending_registration.' . $event->id);

        // Send the confirmation email AFTER the transaction commits (outside the lock).
        // Wrapped in try/catch because a mail failure should not void a successful registration.
        try {
            Mail::to($result['attendee_email'])->send(new RegistrationConfirmationMail(
                $result['event'],
                $result['attendee_name'],
                $result['status'],
                $result['waitlist_position']
            ));
        } catch (\Throwable $exception) {
            // report() logs the error and forwards to error-tracking services (Sentry, etc.)
            // without re-throwing, so the registration flow still completes gracefully.
            report($exception);
        }

        // Compose the user-facing success message based on the transaction outcome.
        if ($result['duplicate']) {
            // Idempotent: user re-submitted payment for a registration that already existed.
            $successMessage = 'You are already registered for this event. Your attendee information was updated.';
        } else {
            $successMessage = $result['status'] === 'confirmed'
                ? 'Registration completed successfully.'
                : 'Event is full. You were added to the waitlist at position '.$result['waitlist_position'].'.';
            // Append whether the attendee record was newly created or updated.
            $successMessage .= ' Your information was '.$result['info_status'].' to the database.';
        }

        // Redirect to the confirmation page with session-flashed data.
        // The confirmation page is accessible to guests (for shareable confirmation URLs),
        // so data is carried in the session rather than URL parameters.
        return redirect()
            ->route('events.registration.confirmation', $event)
            ->with('registration_confirmation', [
                'attendee_name'       => $result['attendee_name'],
                'event_title'         => $event->title,
                'registration_status' => $result['status'],
                'waitlist_position'   => $result['waitlist_position'],
                'success_message'     => $successMessage,
            ]);
    }

    /**
     * Step 5: Display the registration confirmation summary.
     *
     * Reads data from the 'registration_confirmation' session flash set in processPayment().
     * If the flash is missing (direct URL access or browser refresh), redirects back to form.
     */
    public function confirmation(Event $event): View|RedirectResponse
    {
        $confirmation = session('registration_confirmation'); // Read once; consumed after this request

        if (! $confirmation) {
            // Missing confirmation data â€” user likely hit refresh or accessed URL directly.
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Please complete registration first.']);
        }

        return view('events.registration-confirmation', [
            'event'        => $event,
            'confirmation' => $confirmation, // Array with attendee_name, status, message
        ]);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Admin helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Admin force-add: confirm an attendee ignoring capacity limits.
     *
     * Hard limit: admins may only force-add up to 5 overrides per event to prevent
     * unbounded capacity inflation. The count is checked inside a locked transaction.
     *
     * If the attendee already has a registration, it is upgraded to confirmed + flagged.
     * payment_status is set to 'pending' for force-adds (no payment flow used).
     */
    public function adminForceAdd(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($event, $validated) {
                // Lock the event row first to serialize all override operations for this event.
                $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                // Count active admin overrides; lock rows to prevent concurrent additions.
                $overrideCount = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('is_admin_override', true) // Only admin-placed registrations
                    ->where('status', 'confirmed')     // Cancelled overrides don't count toward limit
                    ->lockForUpdate()
                    ->count();

                if ($overrideCount >= 5) {
                    // Sentinel exception to communicate a business-rule violation to the catch block.
                    throw new \RuntimeException('override_limit');
                }

                // Upsert the attendee record (same pattern as the normal registration flow).
                $attendee = Attendee::updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name'    => $validated['name'],
                        'phone'   => $validated['phone'] ?? null,
                        'company' => $validated['company'] ?? null,
                    ]
                );

                // Check for an existing registration for this attendee + event.
                $existing = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('attendee_id', $attendee->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    // Upgrade the existing registration (e.g. waitlisted â†’ confirmed via override).
                    $existing->update([
                        'status'            => 'confirmed',
                        'waitlist_position' => null,        // Remove from queue
                        'is_admin_override' => true,        // Flag as admin-placed
                    ]);
                } else {
                    // Create a fresh admin-override registration bypassing capacity check.
                    Registration::create([
                        'event_id'          => $lockedEvent->id,
                        'attendee_id'       => $attendee->id,
                        'status'            => 'confirmed',
                        'waitlist_position' => null,
                        'payment_status'    => 'pending',  // No payment collected via this flow
                        'is_admin_override' => true,       // Differentiated from normal bookings
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'override_limit') {
                return back()->withErrors([
                    'force_add' => 'Override limit reached. Admins may only force-add up to 5 users per event.',
                ]);
            }
            throw $e; // Unexpected error â€” let the global handler take over
        }

        return redirect()
            ->route('admin.events.registrations.index', $event)
            ->with('success', 'User force-added and confirmed successfully.');
    }

    /**
     * Admin listing: paginated and filterable list of registrations for an event.
     *
     * Filters are validated to prevent injection via the status/payment_status params.
     * withQueryString() appends current filter parameters to paginator links.
     */
    public function index(Event $event): View
    {
        // Validate optional query-string filters (GET request, no CSRF needed).
        $filters = request()->validate([
            'status'         => ['nullable', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,refunded'],
        ]);

        $registrationsQuery = $event->registrations()
            ->with('attendee')             // Eager-load to avoid N+1 in the table rows
            ->latest('registration_date'); // Most recent first

        // Apply status filter if provided (empty string counts as unset via !empty check).
        if (! empty($filters['status'])) {
            $registrationsQuery->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $registrationsQuery->where('payment_status', $filters['payment_status']);
        }

        // Paginate and preserve current query string so filter state survives page navigation.
        $registrations = $registrationsQuery->paginate(20)->withQueryString();

        return view('events.registrations', [
            'event'         => $event,
            'registrations' => $registrations, // LengthAwarePaginator; use $registrations->links()
            'filters'       => $filters,        // Pass back to repopulate filter form inputs
        ]);
    }

    /**
     * Stream a CSV export of filtered registrations for an event.
     *
     * Uses streamDownload() to write directly to php://output, avoiding the need
     * to buffer the entire file in memory (important for large registration lists).
     * The filename includes a timestamp to prevent name collisions on re-exports.
     */
    public function export(Request $request, Event $event): StreamedResponse
    {
        // Same filter validation as index() â€” consistent server-side sanitisation.
        $filters = $request->validate([
            'status'         => ['nullable', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,refunded'],
        ]);

        $registrationsQuery = $event->registrations()
            ->with('attendee')             // Eager-load to avoid N+1 in the CSV loop
            ->latest('registration_date');

        if (! empty($filters['status'])) {
            $registrationsQuery->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $registrationsQuery->where('payment_status', $filters['payment_status']);
        }

        // get() fetches all matching rows (no pagination for CSV exports).
        // Consider chunking if event registration counts can be very large.
        $registrations = $registrationsQuery->get();

        // Include timestamp in filename to make each export uniquely identifiable.
        $fileName = 'event-'.$event->id.'-registrations-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($registrations): void {
            // php://output writes directly to the HTTP response stream (no temp file).
            $handle = fopen('php://output', 'w');

            // Write the CSV header row.
            fputcsv($handle, [
                'Registration ID',
                'Event ID',
                'Attendee Name',
                'Attendee Email',
                'Attendee Phone',
                'Status',
                'Waitlist Position',
                'Payment Status',
                'Registration Date',
            ]);

            foreach ($registrations as $registration) {
                // Null-safe operator (?->) used because attendee may be null in edge cases
                // (e.g. attendee deleted after registration was created).
                fputcsv($handle, [
                    $registration->id,
                    $registration->event_id,
                    $registration->attendee?->name,
                    $registration->attendee?->email,
                    $registration->attendee?->phone,
                    $registration->status,
                    $registration->waitlist_position, // null for confirmed registrations
                    $registration->payment_status,
                    $registration->registration_date,
                ]);
            }

            fclose($handle); // Flush and close the output stream
        }, $fileName, [
            'Content-Type' => 'text/csv', // Browser/client should offer file download
        ]);
    }

    /**
     * Admin: update the status and/or payment_status of a registration.
     *
     * Cancellation path is delegated to cancelAndPromote() which:
     *  1. Cancels the registration inside a transaction
     *  2. Promotes the first waitlisted attendee (if any) to confirmed
     *  3. Sends a WaitlistPromotedMail to the promoted attendee
     *
     * Non-cancellation path handles waitlist position assignment if status
     * transitions to 'waitlisted'.
     */
    public function update(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        // Route-model binding resolves {registration} by ID but doesn't scope it to
        // the event â€” manually verify the foreign key matches the route's {event}.
        if ($registration->event_id !== $event->id) {
            abort(404); // Registration doesn't belong to this event
        }

        $validated = $request->validate([
            'status'         => ['required', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['required', 'in:pending,paid,refunded'],
        ]);

        // Immutability rule: once payment is confirmed as 'paid', it cannot be reverted.
        // This prevents admins from accidentally un-acknowledging a completed payment.
        if ($registration->payment_status === 'paid') {
            $validated['payment_status'] = 'paid'; // Override any submitted value
        }

        // Cancellation delegates to the shared helper (also used by cancelMyRegistration).
        // Non-cancellation updates are handled inline with a pessimistic-lock transaction.
        $promotedAttendeeName = $validated['status'] === 'cancelled'
            ? $this->cancelAndPromote($event, $registration) // Returns promoted attendee name or null
            : DB::transaction(function () use ($event, $registration, $validated) {
                // Lock both the event and the registration to ensure consistent state.
                $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                $lockedRegistration = Registration::query()
                    ->where('id', $registration->id)
                    ->where('event_id', $lockedEvent->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $previousStatus = $lockedRegistration->status;
                $newStatus      = $validated['status'];

                $updatePayload = [
                    'status'         => $newStatus,
                    'payment_status' => $validated['payment_status'],
                ];

                // Assign a new waitlist position if moving INTO the waitlisted state.
                if ($newStatus === 'waitlisted' && $previousStatus !== 'waitlisted') {
                    $maxWaitlistPosition = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->max('waitlist_position');

                    // Append to end of queue; max returns null if no waitlisted rows exist.
                    $updatePayload['waitlist_position'] = ($maxWaitlistPosition ?? 0) + 1;
                }

                // Clear waitlist_position when moving OUT of waitlisted state.
                if ($newStatus !== 'waitlisted') {
                    $updatePayload['waitlist_position'] = null;
                }

                $lockedRegistration->update($updatePayload);

                return null; // No promoted attendee in a non-cancellation update
            });

        $successMessage = 'Registration updated successfully.';

        // cancelAndPromote() handles status; update payment_status separately if changed.
        // This separation keeps the transaction in cancelAndPromote() focused and minimal.
        if ($validated['status'] === 'cancelled' && $validated['payment_status'] !== $registration->payment_status) {
            $registration->update(['payment_status' => $validated['payment_status']]);
        }

        if ($promotedAttendeeName) {
            // Append promotion notice to the success message for admin visibility.
            $successMessage .= ' '.$promotedAttendeeName.' was promoted from the waitlist.';
        }

        return redirect()
            ->route('admin.events.registrations.index', $event)
            ->with('success', $successMessage);
    }

    /**
     * Allow the authenticated user to cancel their own registration.
     *
     * Authorization: the registration must belong to the user's attendee record
     * (matched by email). Admins use the admin update route instead.
     *
     * Refund: if the registration was paid, its payment_status is set to 'refunded'
     * to indicate a refund is due (actual payment reversal not implemented in mock).
     */
    public function cancelMyRegistration(Event $event, Registration $registration): RedirectResponse
    {
        // Verify the registration belongs to the correct event (route-model binding scope).
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        // Ownership check: look up the authenticated user's attendee record by email.
        $attendee = Attendee::where('email', Auth::user()->email)->first();

        if (! $attendee || $registration->attendee_id !== $attendee->id) {
            // 403 rather than 404 to inform the user they can't perform this action.
            abort(403, 'You may only cancel your own registrations.');
        }

        if ($registration->status === 'cancelled') {
            // Idempotent: already cancelled â€” no action needed; show informational message.
            return redirect()->route('dashboard')->with('info', 'This registration is already cancelled.');
        }

        // Capture status flags BEFORE cancelAndPromote() modifies the registration row.
        $wasConfirmed = $registration->status === 'confirmed';
        $wasPaid      = $registration->payment_status === 'paid';

        // Delegate the cancellation (and potential waitlist promotion) to the shared helper.
        $this->cancelAndPromote($event, $registration);

        if ($wasPaid) {
            // refresh() reloads the model from DB in case cancelAndPromote() changed it.
            $registration->refresh();
            // Mark as refunded to signal that a refund is owed (mock â€” no actual gateway call).
            $registration->update(['payment_status' => 'refunded']);
        }

        // Build the confirmation message based on the original registration status.
        $message = $wasConfirmed
            ? 'Registration cancelled.' . ($wasPaid ? ' A refund has been initiated.' : '')
            : 'You have been removed from the waitlist.'; // Waitlisted cancellations don't offer refunds

        return redirect()->route('dashboard')->with('success', $message);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Protected helpers (shared internal logic)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Cancel a registration and promote the first waitlisted attendee within a
     * single pessimistic-lock transaction.
     *
     * Auto-promotion logic:
     *  - Only triggers if the cancelled registration was 'confirmed' (not waitlisted).
     *  - Picks the waitlisted registration with the lowest waitlist_position.
     *  - COALESCE(waitlist_position, 999999) handles any null positions safely.
     *  - Sends a WaitlistPromotedMail to the newly confirmed attendee.
     *
     * @return string|null  Name of the promoted attendee, or null if no promotion occurred.
     */
    protected function cancelAndPromote(Event $event, Registration $registration): ?string
    {
        return DB::transaction(function () use ($event, $registration) {
            // Lock the event and the target registration to prevent concurrent modifications.
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $lockedRegistration = Registration::query()
                ->where('id', $registration->id)
                ->where('event_id', $lockedEvent->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $lockedRegistration->status; // Needed to decide whether to promote

            // Cancel the registration and clear its waitlist position.
            $lockedRegistration->update([
                'status'            => 'cancelled',
                'waitlist_position' => null, // No longer in any queue
            ]);

            // Only promote from waitlist if a CONFIRMED seat just opened up.
            // Cancelling a waitlisted registration doesn't free a confirmed seat.
            if ($previousStatus !== 'confirmed') {
                return null; // No promotion needed
            }

            /** @var Registration|null $nextWaitlistedRegistration */
            // Find the first in queue: lowest waitlist_position, with registration_date as tiebreaker.
            // COALESCE prevents null positions from floating to the front of the ORDER BY.
            $nextWaitlistedRegistration = Registration::query()
                ->with('attendee')                                      // Pre-load for mail sending
                ->where('event_id', $lockedEvent->id)
                ->where('status', 'waitlisted')
                ->orderByRaw('COALESCE(waitlist_position, 999999) asc') // Null-safe ordering
                ->orderBy('registration_date')                          // FIFO tiebreaker
                ->lockForUpdate()                                       // Lock the promoted row
                ->first();

            if (! $nextWaitlistedRegistration) {
                return null; // Waitlist is empty â€” no promotion needed
            }

            // Promote: change status to confirmed and clear the queue position.
            $nextWaitlistedRegistration->update([
                'status'            => 'confirmed',
                'waitlist_position' => null, // No longer in the waitlist queue
            ]);

            $promotedAttendee = $nextWaitlistedRegistration->attendee; // Pre-loaded above

            if ($promotedAttendee) {
                try {
                    // Notify the promoted attendee outside the transaction commit but still
                    // inside the closure. In production, consider using a queued Mailable.
                    Mail::to($promotedAttendee->email)->send(
                        new WaitlistPromotedMail($lockedEvent, $promotedAttendee->name)
                    );
                } catch (\Throwable $e) {
                    // Mail failure must not roll back the promotion â€” log and continue.
                    report($e);
                }
            }

            // Return the name so callers can append it to a success flash message.
            return $promotedAttendee?->name; // Null-safe: returns null if attendee is missing
        });
    }

    /**
     * Find confirmed events on the same calendar day as the given event
     * for the currently authenticated user's attendee record.
     *
     * Used to display an informational warning â€” registration is still allowed.
     * Returns an empty Collection for guests (not logged in) or users without
     * an attendee record (brand-new accounts who have never registered).
     *
     * @return Collection<int, Event>  Partial columns: id, title, date_time
     */
    protected function sameDayConflicts(Event $event): Collection
    {
        if (! Auth::check()) {
            return collect(); // Guests cannot have existing registrations
        }

        // Link the User to their Attendee record via shared email field.
        $attendee = Attendee::where('email', Auth::user()->email)->first();

        if (! $attendee) {
            return collect(); // No attendee record = no past registrations to conflict with
        }

        $eventDate = $event->date_time->toDateString(); // Converts Carbon to 'Y-m-d' string

        return Event::query()
            // whereHas('registrations', ...) adds a correlated sub-query:
            //   EXISTS (SELECT 1 FROM registrations WHERE attendee_id = ? AND status = 'confirmed')
            ->whereHas('registrations', function ($q) use ($attendee) {
                $q->where('attendee_id', $attendee->id)
                  ->where('status', 'confirmed'); // Only confirmed (waitlisted doesn't block the day)
            })
            ->whereDate('date_time', $eventDate) // Match same calendar day (ignores time component)
            ->where('id', '!=', $event->id)      // Exclude the event being registered for itself
            ->get(['id', 'title', 'date_time']); // Select only needed columns (reduce payload)
    }
}
