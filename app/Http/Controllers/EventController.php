<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display all events for public and authenticated users.
     */
    public function index()
    {
        $eventsQuery = Event::query();

        if (! Auth::check() || ! Auth::user()->is_admin) {
            $eventsQuery->where('status', 'published');
        }

        $events = $eventsQuery
            ->latest('date_time')
            ->paginate(10);

        // For logged-in non-admin users: which event IDs they already have an active registration for
        $bookedEventIds = collect();
        if (Auth::check() && ! Auth::user()->is_admin) {
            $userEmail = Auth::user()->email;
            $attendee = \App\Models\Attendee::where('email', $userEmail)->first();
            if ($attendee) {
                $bookedEventIds = Registration::query()
                    ->where('attendee_id', $attendee->id)
                    ->whereIn('status', ['confirmed', 'waitlisted'])
                    ->pluck('event_id');
            }
        }

        return view('events.index', compact('events', 'bookedEventIds'));
    }

    /**
     * Show a single event with details and registration access.
     */
    public function show(Event $event)
    {
        $isBooked = false;
        if (Auth::check() && ! Auth::user()->is_admin) {
            $userEmail = Auth::user()->email;
            $attendee = \App\Models\Attendee::where('email', $userEmail)->first();
            if ($attendee) {
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
     * Show admin form to create a new event.
     */
    public function create()
    {
        return view('events.create');
    }

    /**
     * Persist a newly created event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date_time' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,published,cancelled,completed',
        ]);

        Event::create($validated);
        return redirect()->route('events.index')->with('success', 'Event created!');
    }

    /**
     * Show admin form to edit an existing event.
     */
    public function edit(Event $event)
    {
        return view('events.edit', compact('event'));
    }

    /**
     * Persist updates to an existing event.
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date_time' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,published,cancelled,completed',
        ]);

        $event->update($validated);
        return redirect()->route('events.index')->with('success', 'Event updated!');
    }

    /**
     * Remove an event from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('events.index')->with('success', 'Event deleted!');
    }
}
