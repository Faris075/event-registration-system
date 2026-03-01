<?php
// ============================================================
// Controller: EventController
// ------------------------------------------------------------
// Handles CRUD for the events table plus the public event listing.
//
// Route summary:
//   GET    /events              → index()   (public)
//   GET    /events/create       → create()  (admin)
//   POST   /events              → store()   (admin)
//   GET    /events/{event}      → show()    (public)
//   GET    /events/{event}/edit → edit()    (admin)
//   PUT    /events/{event}      → update()  (admin)
//   DELETE /events/{event}      → destroy() (admin)
//
// Best practices applied:
//  ✔ withCount() prevents N+1 queries on the event listing
//  ✔ loadCount() on show() avoids extra query in the accessor
//  ✔ Route-model binding ({event}) auto-resolves Event by ID
//  ✔ Mail errors are caught + reported without breaking the request
//  ✔ 'after:now' validation blocks creating events in the past
// ============================================================

namespace App\Http\Controllers;

use App\Mail\EventCancelledMail;              // Mailable sent when an event is cancelled
use App\Models\Event;                         // Eloquent model for the events table
use App\Models\Registration;                  // Used to check if the current user is booked
use Illuminate\Http\Request;                  // HTTP input, validation, session helpers
use Illuminate\Support\Facades\Auth;          // Current-user auth checks
use Illuminate\Support\Facades\Mail;          // Driver-agnostic mail dispatcher

class EventController extends Controller
{
    /**
     * Display the event listing page.
     *
     * Logic differences between admin and normal users:
     *  - Admins: see ALL events in any status (draft, cancelled, past, etc.)
     *  - Public: see only published, future, non-fully-booked events
     *
     * N+1 prevention:
     *  withCount(['registrations as confirmed_count' => ...]) adds a single
     *  GROUP BY sub-query that attaches confirmed_count to every Event row —
     *  no extra query per card in the loop.
     */
    public function index()
    {
        // Determine role once to avoid repeated Auth::user() calls in the method.
        $isAdmin = Auth::check() && Auth::user()->is_admin;

        $eventsQuery = Event::query(); // Start an unconstrained builder

        if (! $isAdmin) {
            $eventsQuery
                ->where('status', 'published')                    // Only publicly-visible events
                ->where('date_time', '>', now())                  // Exclude past events
                // Subquery filter: hide events where confirmed seats are all taken.
                // Using whereRaw here because Eloquent doesn't have a native correlated-subquery
                // scope for "column > COUNT(related rows)". The bound parameter prevents SQL injection.
                ->whereRaw(
                    'capacity > (SELECT COUNT(*) FROM registrations WHERE event_id = events.id AND `status` = ?)',
                    ['confirmed']
                );
        }

        // withCount pre-loads confirmed_count for every event in one aggregated query.
        // The alias 'confirmed_count' becomes $event->confirmed_count via the accessor in Event.php.
        $events = $eventsQuery
            ->withCount(['registrations as confirmed_count' => fn ($q) => $q->where('status', 'confirmed')])
            ->latest('date_time') // Most recent/upcoming first
            ->paginate(10);       // 10 per page; $events->links() renders pagination in Blade

        // Build a collection of event IDs the current user is already registered/waitlisted for.
        // Used in the view to disable "Register" buttons on already-booked events.
        $bookedEventIds = collect(); // Default to empty collection for guests and admins

        if (Auth::check() && ! Auth::user()->is_admin) {
            $userEmail = Auth::user()->email; // Match user↔attendee by shared email field

            // Find the attendee record linked to this user (may not exist for brand-new accounts).
            $attendee = \App\Models\Attendee::where('email', $userEmail)->first();

            if ($attendee) {
                // pluck returns a flat Collection of event_id integers.
                $bookedEventIds = Registration::query()
                    ->where('attendee_id', $attendee->id)
                    ->whereIn('status', ['confirmed', 'waitlisted']) // Cancelled = can re-register
                    ->pluck('event_id');
            }
        }

        return view('events.index', compact('events', 'bookedEventIds', 'isAdmin'));
    }

    /**
     * Show a single event detail page.
     *
     * loadCount() is called here (rather than withCount on the query) because
     * route-model binding already fetched the Event; loadCount appends the
     * aggregate to the existing model instance without a full re-fetch.
     */
    public function show(Event $event)
    {
        // Populate $event->confirmed_count so the remaining_spot accessor (Event.php)
        // doesn't need to fire an extra COUNT query for every capacity display.
        $event->loadCount([
            'registrations as confirmed_count' => fn ($q) => $q->where('status', 'confirmed'),
        ]);

        // Check whether the current user already has an active registration for this event.
        $isBooked = false;

        if (Auth::check() && ! Auth::user()->is_admin) {
            $userEmail = Auth::user()->email;
            $attendee  = \App\Models\Attendee::where('email', $userEmail)->first();

            if ($attendee) {
                // exists() is cheaper than get()/first() — returns bool without hydration.
                $isBooked = Registration::query()
                    ->where('event_id', $event->id)
                    ->where('attendee_id', $attendee->id)
                    ->whereIn('status', ['confirmed', 'waitlisted'])
                    ->exists();
            }
        }

        return view('events.show', compact('event', 'isBooked'));
    }

    /**
     * Show the admin form for creating a new event.
     * No data is passed — the form is self-contained (no dropdowns from DB).
     */
    public function create()
    {
        return view('events.create');
    }

    /**
     * Validate and persist a new event record.
     *
     * 'after:now' ensures admins cannot create events that are already in the past.
     * 'price' is nullable so free events (null = $0.00) are supported.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'date_time'   => 'required|date|after:now', // Past dates rejected at creation time
            'location'    => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1',  // At least 1 seat required
            'price'       => 'nullable|numeric|min:0',  // null = free; min:0 blocks negative prices
            'status'      => 'required|in:draft,published,cancelled,completed',
        ]);

        // Event::create() uses $fillable to whitelist columns — safe from mass-assignment.
        Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Event created!');
    }

    /**
     * Show the admin edit form for an existing event.
     * Route-model binding passes the resolved Event instance directly.
     */
    public function edit(Event $event)
    {
        return view('events.edit', compact('event'));
    }

    /**
     * Validate and persist updated event data.
     *
     * Side-effect: if status transitions to 'cancelled', all confirmed and
     * waitlisted attendees receive an EventCancelledMail. Mail failures are
     * caught and reported via report() without aborting the HTTP response.
     *
     * NOTE: 'date_time' validation does NOT include 'after:now' here because
     * admins may legitimately edit past events (e.g., fix a typo in the title).
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'date_time'   => 'required|date',           // No after:now — edits on past events are valid
            'location'    => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1',
            'price'       => 'nullable|numeric|min:0',
            'status'      => 'required|in:draft,published,cancelled,completed',
        ]);

        // Capture old status BEFORE the update to detect a transition.
        $oldStatus = $event->status;
        $newStatus = $validated['status'];

        $event->update($validated); // Persist all validated fields in a single UPDATE query

        // Send cancellation emails only when the status changes TO 'cancelled'.
        // Checking oldStatus prevents re-sending on subsequent saves of a cancelled event.
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            // Eager-load attendee to avoid one SELECT per registration in the loop.
            $registrations = $event->registrations()
                ->with('attendee')                             // N+1 prevention
                ->whereIn('status', ['confirmed', 'waitlisted']) // Only active registrations
                ->get();

            foreach ($registrations as $reg) {
                if ($reg->attendee) { // Guard null-attendee (orphaned registrations)
                    try {
                        // Wrap in try/catch — a mail-driver failure must not rollback the event update.
                        Mail::to($reg->attendee->email)->send(
                            new EventCancelledMail($event, $reg->attendee->name, $reg->status)
                        );
                    } catch (\Throwable $e) {
                        // report() logs the exception AND forwards to Sentry/Bugsnag if configured,
                        // without re-throwing, so remaining registrations still get their emails.
                        report($e);
                    }
                }
            }
        }

        return redirect()->route('events.index')->with('success', 'Event updated!');
    }

    /**
     * Permanently delete an event.
     * Associated registrations are cascade-deleted by the DB foreign key constraint
     * (defined in the registrations migration with onDelete('cascade')).
     */
    public function destroy(Event $event)
    {
        $event->delete(); // Hard delete; consider SoftDeletes in production for audit trail

        return redirect()->route('events.index')->with('success', 'Event deleted!');
    }
}
