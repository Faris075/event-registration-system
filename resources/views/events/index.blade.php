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
                        <span class="{{ $badgeClass }}">{{ ucfirst($event->status) }}</span>
                    </div>

                    <div class="event-card-meta">
                        <span>📍 {{ $event->location }}</span>
                        <span>🗓 {{ $event->date_time->format('M d, Y · g:i A') }}</span>
                        <span>🎟 {{ $event->remaining_spot }} spot{{ $event->remaining_spot === 1 ? '' : 's' }} left</span>
                    </div>

                    <div class="event-card-actions">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-ghost btn-sm">View</a>

                        @if($event->status === 'published')
                            @if(in_array($event->id, $bookedEventIds->toArray() ?? []))
                                <span class="badge badge-confirmed" style="padding:0.35rem 0.75rem;">Booked</span>
                            @elseif($event->remaining_spot > 0)
                                <a href="{{ route('events.register.page', $event) }}" class="btn btn-success btn-sm">Register</a>
                            @else
                                <a href="{{ route('events.register.page', $event) }}" class="btn btn-ghost btn-sm" style="border-color:#f59e0b;color:#92400e;">Join Waitlist</a>
                            @endif
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

    <div style="margin-top:1.5rem;">{{ $events->links() }}</div>
</div>
@endsection
