<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationConfirmationMail;
use App\Mail\WaitlistPromotedMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles the full event-registration lifecycle.
 *
 * Flow:
 *  1. `create`          — show the registration details form
 *  2. `store`           — validate details and hold them in the session
 *  3. `showPayment`     — show the mock payment page
 *  4. `processPayment`  — validate card details, write the DB record inside a
 *                         pessimistic-locking transaction, send confirmation mail
 *  5. `confirmation`    — display the post-registration summary (session-driven)
 *
 * Admin helpers:
 *  - `adminForceAdd`  — bypass capacity limits (up to 5 overrides per event)
 *  - `index`          — paginated, filterable registration list for an event
 *  - `export`         — stream a filtered CSV download
 *  - `update`         — change registration status; cancellation auto-promotes
 *                       the next waitlisted attendee
 */
class EventRegistrationController extends Controller
{
    /**
     * Show the dedicated registration form for an event.
     */
    public function create(Event $event): View|RedirectResponse
    {
        if (now()->greaterThanOrEqualTo($event->date_time)) {
            return redirect()
                ->route('events.show', $event)
                ->withErrors(['registration' => 'Registration is closed — this event has already taken place.']);
        }

        return view('events.register', compact('event'));
    }

    /**
     * Register an attendee — validate input, hold data in session, redirect to payment.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        if (now()->greaterThanOrEqualTo($event->date_time)) {
            return back()->withErrors([
                'registration' => 'Registration is closed because this event date has passed.',
            ]);
        }

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'phone'          => ['nullable', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
            'company'        => ['nullable', 'string', 'max:255'],
            'terms_accepted' => ['accepted'],
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms & Conditions to register.',
        ]);

        // Hold registration details in session until payment is completed.
        session(['pending_registration.' . $event->id => $validated]);

        return redirect()->route('events.payment.page', $event);
    }

    /**
     * Show the payment page (requires a pending registration in session).
     */
    public function showPayment(Event $event): View|RedirectResponse
    {
        $pending = session('pending_registration.' . $event->id);

        if (! $pending) {
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Please fill in your registration details first.']);
        }

        return view('events.payment', compact('event', 'pending'));
    }

    /**
     * Process mock payment and finalise the registration in the database.
     */
    public function processPayment(Request $request, Event $event): RedirectResponse
    {
        $pending = session('pending_registration.' . $event->id);

        if (! $pending) {
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Session expired. Please re-enter your details.']);
        }

        $request->validate([
            'card_name'   => ['required', 'string', 'max:255'],
            'card_number' => ['required', 'digits:16'],
            'card_expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card_cvv'    => ['required', 'digits_between:3,4'],
        ], [
            'card_number.digits'  => 'Card number must be exactly 16 digits.',
            'card_expiry.regex'   => 'Expiry must be in MM/YY format.',
            'card_cvv.digits_between' => 'CVV must be 3 or 4 digits.',
        ]);

        $validated = $pending;

        try {
            $result = DB::transaction(function () use ($event, $validated) {
                $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                $attendee = Attendee::updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name'    => $validated['name'],
                        'phone'   => $validated['phone'] ?? null,
                        'company' => $validated['company'] ?? null,
                    ]
                );

                $infoStatus = $attendee->wasRecentlyCreated ? 'saved' : 'updated';

                $existingRegistration = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('attendee_id', $attendee->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingRegistration) {
                    return [
                        'event'            => $lockedEvent,
                        'attendee_name'    => $attendee->name,
                        'attendee_email'   => $attendee->email,
                        'info_status'      => $infoStatus,
                        'status'           => $existingRegistration->status,
                        'waitlist_position'=> $existingRegistration->waitlist_position,
                        'duplicate'        => true,
                    ];
                }

                $confirmedCount = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('status', 'confirmed')
                    ->lockForUpdate()
                    ->count();

                $status          = 'confirmed';
                $waitlistPosition = null;

                if ($confirmedCount >= $lockedEvent->capacity) {
                    $waitlistCap      = $lockedEvent->waitlistCapacity();
                    $currentWaitlisted = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->count();

                    if ($currentWaitlisted >= $waitlistCap) {
                        throw new \RuntimeException('waitlist_full');
                    }

                    $status           = 'waitlisted';
                    $maxWaitlistPosition = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->max('waitlist_position');

                    $waitlistPosition = ($maxWaitlistPosition ?? 0) + 1;
                }

                Registration::create([
                    'event_id'         => $lockedEvent->id,
                    'attendee_id'      => $attendee->id,
                    'status'           => $status,
                    'waitlist_position'=> $waitlistPosition,
                    'payment_status'   => 'paid',
                    'is_admin_override'=> false,
                ]);

                return [
                    'event'            => $lockedEvent,
                    'attendee_name'    => $attendee->name,
                    'attendee_email'   => $attendee->email,
                    'info_status'      => $infoStatus,
                    'status'           => $status,
                    'waitlist_position'=> $waitlistPosition,
                    'duplicate'        => false,
                ];
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'waitlist_full') {
                return back()->withErrors([
                    'payment' => 'This event is fully booked and the waitlist ('.$event->waitlistCapacity().' spots) is also full.',
                ]);
            }
            throw $e;
        }

        // Clear pending session data.
        session()->forget('pending_registration.' . $event->id);

        try {
            Mail::to($result['attendee_email'])->send(new RegistrationConfirmationMail(
                $result['event'],
                $result['attendee_name'],
                $result['status'],
                $result['waitlist_position']
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }

        if ($result['duplicate']) {
            $successMessage = 'You are already registered for this event. Your attendee information was updated.';
        } else {
            $successMessage = $result['status'] === 'confirmed'
                ? 'Registration completed successfully.'
                : 'Event is full. You were added to the waitlist at position '.$result['waitlist_position'].'.';
            $successMessage .= ' Your information was '.$result['info_status'].' to the database.';
        }

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
     * Display registration confirmation details from session state.
     */
    public function confirmation(Event $event): View|RedirectResponse
    {
        $confirmation = session('registration_confirmation');

        if (! $confirmation) {
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Please complete registration first.']);
        }

        return view('events.registration-confirmation', [
            'event' => $event,
            'confirmation' => $confirmation,
        ]);
    }

    /**
     * Admin force-add: bypass capacity limits up to 5 override slots per event.
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
                $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                $overrideCount = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('is_admin_override', true)
                    ->where('status', 'confirmed')
                    ->lockForUpdate()
                    ->count();

                if ($overrideCount >= 5) {
                    throw new \RuntimeException('override_limit');
                }

                $attendee = Attendee::updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name'    => $validated['name'],
                        'phone'   => $validated['phone'] ?? null,
                        'company' => $validated['company'] ?? null,
                    ]
                );

                $existing = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('attendee_id', $attendee->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->update([
                        'status'            => 'confirmed',
                        'waitlist_position' => null,
                        'is_admin_override' => true,
                    ]);
                } else {
                    Registration::create([
                        'event_id'          => $lockedEvent->id,
                        'attendee_id'       => $attendee->id,
                        'status'            => 'confirmed',
                        'waitlist_position' => null,
                        'payment_status'    => 'pending',
                        'is_admin_override' => true,
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'override_limit') {
                return back()->withErrors(['force_add' => 'Override limit reached. Admins may only force-add up to 5 users per event.']);
            }
            throw $e;
        }

        return redirect()
            ->route('admin.events.registrations.index', $event)
            ->with('success', 'User force-added and confirmed successfully.');
    }

    /**
     * List registrations for an event with optional admin filters.
     */
    public function index(Event $event): View
    {
        $filters = request()->validate([
            'status' => ['nullable', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,refunded'],
        ]);

        $registrationsQuery = $event->registrations()
            ->with('attendee')
            ->latest('registration_date');

        if (!empty($filters['status'])) {
            $registrationsQuery->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $registrationsQuery->where('payment_status', $filters['payment_status']);
        }

        $registrations = $registrationsQuery->paginate(20)->withQueryString();

        return view('events.registrations', [
            'event' => $event,
            'registrations' => $registrations,
            'filters' => $filters,
        ]);
    }

    /**
     * Export filtered event registrations to CSV.
     */
    public function export(Request $request, Event $event): StreamedResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,refunded'],
        ]);

        $registrationsQuery = $event->registrations()
            ->with('attendee')
            ->latest('registration_date');

        if (!empty($filters['status'])) {
            $registrationsQuery->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $registrationsQuery->where('payment_status', $filters['payment_status']);
        }

        $registrations = $registrationsQuery->get();

        $fileName = 'event-'.$event->id.'-registrations-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($registrations): void {
            $handle = fopen('php://output', 'w');

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
                fputcsv($handle, [
                    $registration->id,
                    $registration->event_id,
                    $registration->attendee?->name,
                    $registration->attendee?->email,
                    $registration->attendee?->phone,
                    $registration->status,
                    $registration->waitlist_position,
                    $registration->payment_status,
                    $registration->registration_date,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Update a registration status and promote waitlist on cancellation.
     */
    public function update(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status'         => ['required', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['required', 'in:pending,paid,refunded'],
        ]);

        // Prevent changing payment_status once it is 'paid'.
        if ($registration->payment_status === 'paid') {
            $validated['payment_status'] = 'paid';
        }

        $promotedAttendeeName = $validated['status'] === 'cancelled'
            ? $this->cancelAndPromote($event, $registration)
            : DB::transaction(function () use ($event, $registration, $validated) {
                $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                $lockedRegistration = Registration::query()
                    ->where('id', $registration->id)
                    ->where('event_id', $lockedEvent->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $previousStatus = $lockedRegistration->status;
                $newStatus = $validated['status'];

                $updatePayload = [
                    'status'         => $newStatus,
                    'payment_status' => $validated['payment_status'],
                ];

                if ($newStatus === 'waitlisted' && $previousStatus !== 'waitlisted') {
                    $maxWaitlistPosition = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->max('waitlist_position');

                    $updatePayload['waitlist_position'] = ($maxWaitlistPosition ?? 0) + 1;
                }

                if ($newStatus !== 'waitlisted') {
                    $updatePayload['waitlist_position'] = null;
                }

                $lockedRegistration->update($updatePayload);

                return null;
            });

        $successMessage = 'Registration updated successfully.';

        // When cancelling, payment_status is updated outside the cancelAndPromote
        // transaction since that helper only manages status and waitlist promotion.
        if ($validated['status'] === 'cancelled' && $validated['payment_status'] !== $registration->payment_status) {
            $registration->update(['payment_status' => $validated['payment_status']]);
        }

        if ($promotedAttendeeName) {
            $successMessage .= ' '.$promotedAttendeeName.' was promoted from the waitlist.';
        }

        return redirect()
            ->route('admin.events.registrations.index', $event)
            ->with('success', $successMessage);
    }

    /**
     * Allow an authenticated user to cancel their own registration.
     * Paid registrations are automatically marked as refunded.
     */
    public function cancelMyRegistration(Event $event, Registration $registration): RedirectResponse
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        // Verify the registration belongs to the authenticated user's attendee record.
        $attendee = Attendee::where('email', Auth::user()->email)->first();
        if (! $attendee || $registration->attendee_id !== $attendee->id) {
            abort(403, 'You may only cancel your own registrations.');
        }

        if ($registration->status === 'cancelled') {
            return redirect()->route('dashboard')->with('info', 'This registration is already cancelled.');
        }

        $wasConfirmed = $registration->status === 'confirmed';
        $wasPaid = $registration->payment_status === 'paid';

        $this->cancelAndPromote($event, $registration);

        // Mark as refunded if the user had paid.
        if ($wasPaid) {
            $registration->refresh();
            $registration->update(['payment_status' => 'refunded']);
        }

        $message = $wasConfirmed
            ? 'Registration cancelled.' . ($wasPaid ? ' A refund has been initiated.' : '')
            : 'You have been removed from the waitlist.';

        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Cancel a registration and auto-promote the first waitlisted attendee.
     */
    protected function cancelAndPromote(Event $event, Registration $registration): ?string
    {
        return DB::transaction(function () use ($event, $registration) {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $lockedRegistration = Registration::query()
                ->where('id', $registration->id)
                ->where('event_id', $lockedEvent->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $lockedRegistration->status;

            $lockedRegistration->update([
                'status' => 'cancelled',
                'waitlist_position' => null,
            ]);

            if ($previousStatus !== 'confirmed') {
                return null;
            }

            /** @var Registration|null $nextWaitlistedRegistration */
            $nextWaitlistedRegistration = Registration::query()
                ->with('attendee')
                ->where('event_id', $lockedEvent->id)
                ->where('status', 'waitlisted')
                ->orderByRaw('COALESCE(waitlist_position, 999999) asc')
                ->orderBy('registration_date')
                ->lockForUpdate()
                ->first();

            if (! $nextWaitlistedRegistration) {
                return null;
            }

            $nextWaitlistedRegistration->update([
                'status' => 'confirmed',
                'waitlist_position' => null,
            ]);

            $promotedAttendee = $nextWaitlistedRegistration->attendee;

            if ($promotedAttendee) {
                try {
                    Mail::to($promotedAttendee->email)->send(
                        new WaitlistPromotedMail($lockedEvent, $promotedAttendee->name)
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $promotedAttendee?->name;
        });
    }
}
