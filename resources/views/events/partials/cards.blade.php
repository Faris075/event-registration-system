@foreach($events as $event)
    @php
        $badgeClass = match($event->status) {
            'published'  => 'badge badge-published',
            'draft'      => 'badge badge-draft',
            'cancelled'  => 'badge badge-cancelled',
            'completed'  => 'badge badge-completed',
            default      => 'badge',
        };
        $bookedIds     = $bookedEventIds ?? collect();
        $waitlistedIds = $waitlistedEventIds ?? collect();
        $isConfirmed   = in_array($event->id, $bookedIds->toArray());
        $isWaitlisted  = in_array($event->id, $waitlistedIds->toArray());
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
                @if(auth()->user()->is_admin && $event->date_time->isPast())
                    <span class="badge badge-completed" style="font-size:0.7rem;">Past</span>
                @endif
            @endauth
        </div>

        <div class="event-card-actions">
            <a href="{{ route('events.show', $event) }}" class="btn btn-ghost btn-sm">View</a>

            @if($event->status === 'published' && !$event->date_time->isPast())
                @if($isConfirmed)
                    <span class="badge badge-confirmed" style="padding:0.35rem 0.75rem;">✅ Registered</span>
                @elseif($isWaitlisted)
                    <span class="badge badge-waitlisted" style="padding:0.35rem 0.75rem;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;">⏳ Waitlisted</span>
                @elseif($event->remaining_spot > 0)
                    <a href="{{ route('events.register.page', $event) }}" class="btn btn-success btn-sm">Register</a>
                @else
                    <a href="{{ route('events.register.page', $event) }}" class="btn btn-ghost btn-sm" style="border-color:#f59e0b;color:#92400e;">Join Waitlist</a>
                @endif
            @elseif($event->date_time->isPast())
                <span class="badge badge-completed" style="padding:0.35rem 0.75rem;font-size:0.75rem;">Event Ended</span>
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
