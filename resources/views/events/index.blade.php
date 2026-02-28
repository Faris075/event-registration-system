@extends('layouts.app')

@section('content')
<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Events</h1>
            <p class="page-subtitle">Browse upcoming events and register in seconds.</p>
        </div>
        @auth
            @if(auth()->user()->is_admin)
                <a href="{{ route('events.create') }}" class="btn btn-primary">+ Create Event</a>
            @endif
        @endauth
    </div>

    @if($events->isEmpty())
        <div class="empty-state">No events are currently available.</div>
    @else
        <div class="event-grid">
            @foreach($events as $event)
                @php
                    $badgeClass = match($event->status) {
                        'published'  => 'badge badge-published',
                        'draft'      => 'badge badge-draft',
                        'cancelled'  => 'badge badge-cancelled',
                        'completed'  => 'badge badge-completed',
                        default      => 'badge',
                    };
                @endphp
                <article class="event-card">
                    <div class="event-card-head">
                        <h2 class="event-card-title">{{ $event->title }}</h2>
                        @auth
                            @if(auth()->user()->is_admin)
                                <span class="{{ $badgeClass }}">{{ ucfirst($event->status) }}</span>
                            @endif
                        @endauth
                    </div>

                    <div class="event-card-meta">
                        <span>📍 {{ $event->location }}</span>
                        <span>🗓 {{ $event->date_time->format('M d, Y · g:i A') }}</span>
                        @if($event->remaining_spot > 0)
                            <span>🎟 {{ $event->remaining_spot }} spot{{ $event->remaining_spot === 1 ? '' : 's' }} left</span>
                        @else
                            <span style="color:var(--danger);">🎟 Fully Booked</span>
                        @endif
                        @if($event->price)
                            <span>💰 {{ $currencySymbol }}{{ number_format(\App\Models\User::convertPrice($event->price, $currencyCode), 2) }}</span>
                        @else
                            <span>💰 Free</span>
                        @endif
                        @auth
                            @if(auth()->user()->is_admin)
                                @if($event->date_time->isPast())
                                    <span class="badge badge-completed" style="font-size:0.7rem;">Past</span>
                                @endif
                            @endif
                        @endauth
                    </div>

                    <div class="event-card-actions">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-ghost btn-sm">View</a>

                        @if($event->status === 'published' && !$event->date_time->isPast())
                            @if(in_array($event->id, $bookedEventIds->toArray() ?? []))
                                <span class="badge badge-confirmed" style="padding:0.35rem 0.75rem;">Booked</span>
                            @elseif($event->remaining_spot > 0)
                                <a href="{{ route('events.register.page', $event) }}" class="btn btn-success btn-sm">Register</a>
                            @else
                                <a href="{{ route('events.register.page', $event) }}" class="btn btn-ghost btn-sm" style="border-color:#f59e0b;color:#92400e;">Join Waitlist</a>
                            @endif
                        @elseif($event->date_time->isPast())
                            <span class="badge badge-completed" style="padding:0.3rem 0.65rem;font-size:0.75rem;">Event Ended</span>
                        @endif

                        @auth
                            @if(auth()->user()->is_admin)
                                <a href="{{ route('events.edit', $event) }}" class="btn btn-accent btn-sm">Edit</a>
                                <form action="{{ route('events.destroy', $event) }}" method="POST" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this event?')">Delete</button>
                                </form>
                            @endif
                        @endauth
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div style="margin-top:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        {{ $events->links() }}
        @if($events->lastPage() > 1)
        <form method="GET" action="{{ route('events.index') }}" style="display:flex;align-items:center;gap:0.4rem;">
            @foreach(request()->except('page') as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <label for="events-page-jump" style="font-size:0.82rem;color:var(--muted);white-space:nowrap;">Go to page:</label>
            <select id="events-page-jump" name="page" class="form-select" style="padding:0.25rem 0.5rem;font-size:0.82rem;width:auto;" onchange="this.form.submit()">
                @for($p = 1; $p <= $events->lastPage(); $p++)
                    <option value="{{ $p }}" {{ $events->currentPage() == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endfor
            </select>
        </form>
        @endif
    </div>
</div>
@endsection
