<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationConfirmationMail;
use App\Mail\WaitlistPromotedMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventRegistrationController extends Controller
{
    public function create(Event $event): View|RedirectResponse
    {
        if (now()->greaterThanOrEqualTo($event->date_time)) {
            return redirect()
                ->route('events.show', $event)
                ->withErrors(['registration' => 'Registration is closed — this event has already taken place.']);
        }

        $conflictEvents = $this->sameDayConflicts($event);

        return view('events.register', compact('event', 'conflictEvents'));
    }

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
            'phone'          => ['nullable', 'string', 'regex:/^[0-9]{9,11}$/'],
            'company'        => ['nullable', 'string', 'max:255'],
            'terms_accepted' => ['accepted'],
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms & Conditions to register.',
            'phone.regex'             => 'Phone number must be between 9 and 11 digits (numbers only).',
        ]);

        session(['pending_registration.' . $event->id => $validated]);

        $conflicts = $this->sameDayConflicts($event);
        if ($conflicts->isNotEmpty()) {
            $conflictTitles = $conflicts->pluck('title')->join(', ');
            session()->flash(
                'conflict_warning',
                'Note: you are already registered for another event on this day (' . $conflictTitles . '). Proceeding anyway.'
            );
        }

        return redirect()->route('events.payment.page', $event);
    }

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
            'card_number.digits'      => 'Card number must be exactly 16 digits.',
            'card_expiry.regex'       => 'Expiry must be in MM/YY format.',
            'card_cvv.digits_between' => 'CVV must be 3 or 4 digits.',
        ]);

        $validated = $pending;

        try {
            // lockForUpdate() on each query prevents concurrent overbooking by serialising
            // capacity reads/writes within the transaction.
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

                // wasRecentlyCreated: true = INSERT, false = UPDATE
                $infoStatus = $attendee->wasRecentlyCreated ? 'saved' : 'updated';

                $existingRegistration = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('attendee_id', $attendee->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingRegistration) {
                    return [
                        'event'             => $lockedEvent,
                        'attendee_name'     => $attendee->name,
                        'attendee_email'    => $attendee->email,
                        'info_status'       => $infoStatus,
                        'status'            => $existingRegistration->status,
                        'waitlist_position' => $existingRegistration->waitlist_position,
                        'duplicate'         => true,
                    ];
                }

                $confirmedCount = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->where('status', 'confirmed')
                    ->lockForUpdate()
                    ->count();

                $status           = 'confirmed';
                $waitlistPosition = null;

                if ($confirmedCount >= $lockedEvent->capacity) {
                    $waitlistCap = $lockedEvent->waitlistCapacity();

                    $currentWaitlisted = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->count();

                    if ($currentWaitlisted >= $waitlistCap) {
                        // Sentinel: avoids a custom exception class for this business-rule violation.
                        throw new \RuntimeException('waitlist_full');
                    }

                    $status = 'waitlisted';

                    $maxWaitlistPosition = Registration::query()
                        ->where('event_id', $lockedEvent->id)
                        ->where('status', 'waitlisted')
                        ->lockForUpdate()
                        ->max('waitlist_position');

                    $waitlistPosition = ($maxWaitlistPosition ?? 0) + 1;
                }

                Registration::create([
                    'event_id'          => $lockedEvent->id,
                    'attendee_id'       => $attendee->id,
                    'status'            => $status,
                    'waitlist_position' => $waitlistPosition,
                    'payment_status'    => 'paid',
                    'is_admin_override' => false,
                ]);

                return [
                    'event'             => $lockedEvent,
                    'attendee_name'     => $attendee->name,
                    'attendee_email'    => $attendee->email,
                    'info_status'       => $infoStatus,
                    'status'            => $status,
                    'waitlist_position' => $waitlistPosition,
                    'duplicate'         => false,
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

    public function confirmation(Event $event): View|RedirectResponse
    {
        $confirmation = session('registration_confirmation');

        if (! $confirmation) {
            return redirect()
                ->route('events.register.page', $event)
                ->withErrors(['registration' => 'Please complete registration first.']);
        }

        return view('events.registration-confirmation', [
            'event'        => $event,
            'confirmation' => $confirmation,
        ]);
    }

    public function adminForceAdd(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'regex:/^[0-9]{9,11}$/'],
            'company' => ['nullable', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Phone number must be between 9 and 11 digits (numbers only).',
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
                return back()->withErrors([
                    'force_add' => 'Override limit reached. Admins may only force-add up to 5 users per event.',
                ]);
            }
            throw $e;
        }

        return redirect()
            ->route('admin.events.registrations.index', $event)
            ->with('success', 'User force-added and confirmed successfully.');
    }

    public function index(Event $event): View
    {
        $filters = request()->validate([
            'status'         => ['nullable', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,refunded'],
        ]);

        $registrationsQuery = $event->registrations()
            ->with('attendee')
            ->latest('registration_date');

        if (! empty($filters['status'])) {
            $registrationsQuery->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $registrationsQuery->where('payment_status', $filters['payment_status']);
        }

        $registrations = $registrationsQuery->paginate(20)->withQueryString();

        return view('events.registrations', [
            'event'         => $event,
            'registrations' => $registrations,
            'filters'       => $filters,
        ]);
    }

    public function export(Request $request, Event $event): StreamedResponse
    {
        $filters = $request->validate([
            'status'         => ['nullable', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,refunded'],
        ]);

        $registrationsQuery = $event->registrations()
            ->with('attendee')
            ->latest('registration_date');

        if (! empty($filters['status'])) {
            $registrationsQuery->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
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

    public function update(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status'         => ['required', 'in:confirmed,waitlisted,cancelled'],
            'payment_status' => ['required', 'in:pending,paid,refunded'],
        ]);

        // Once paid, payment_status cannot be reverted.
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
                $newStatus      = $validated['status'];

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

        if ($validated['status'] === 'cancelled') {
            $registration->refresh();
            if ($validated['payment_status'] !== $registration->payment_status) {
                $registration->update(['payment_status' => $validated['payment_status']]);
            }
        }

        if ($promotedAttendeeName) {
            $successMessage .= ' '.$promotedAttendeeName.' was promoted from the waitlist.';
        }

        return redirect()
            ->route('admin.events.registrations.index', $event)
            ->with('success', $successMessage);
    }

    public function cancelMyRegistration(Event $event, Registration $registration): RedirectResponse
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        $attendee = Attendee::where('email', Auth::user()->email)->first();

        if (! $attendee || $registration->attendee_id !== $attendee->id) {
            abort(403, 'You may only cancel your own registrations.');
        }

        if ($registration->status === 'cancelled') {
            return redirect()->route('dashboard')->with('info', 'This registration is already cancelled.');
        }

        $wasConfirmed = $registration->status === 'confirmed';
        $wasPaid      = $registration->payment_status === 'paid';

        $this->cancelAndPromote($event, $registration);

        if ($wasPaid) {
            $registration->refresh();
            $registration->update(['payment_status' => 'refunded']);
        }

        $message = $wasConfirmed
            ? 'Registration cancelled.' . ($wasPaid ? ' A refund has been initiated.' : '')
            : 'You have been removed from the waitlist.';

        return redirect()->route('dashboard')->with('success', $message);
    }

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
                'status'            => 'cancelled',
                'waitlist_position' => null,
            ]);

            if ($previousStatus !== 'confirmed') {
                return null;
            }

            // COALESCE(waitlist_position, 999999) prevents nulls from sorting to the front.
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
                'status'            => 'confirmed',
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

    protected function sameDayConflicts(Event $event): Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        $attendee = Attendee::where('email', Auth::user()->email)->first();

        if (! $attendee) {
            return collect();
        }

        $eventDate = $event->date_time->toDateString();

        return Event::query()
            ->whereHas('registrations', function ($q) use ($attendee) {
                $q->where('attendee_id', $attendee->id)
                  ->where('status', 'confirmed');
            })
            ->whereDate('date_time', $eventDate)
            ->where('id', '!=', $event->id)
            ->get(['id', 'title', 'date_time']);
    }
}
