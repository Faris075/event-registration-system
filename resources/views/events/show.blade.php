@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:820px;">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->has('registration'))
        <div class="alert alert-error">{{ $errors->first('registration') }}</div>
    @endif

    <div class="card">
        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
            <div>
                <h1 style="margin:0;font-size:1.7rem;font-weight:800;color:var(--text);">{{ $event->title }}</h1>
                <p style="margin:0.3rem 0 0;font-size:0.9rem;color:var(--muted);">{{ $event->location }}</p>
            </div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                @php
                    $badgeClass = match($event->status) {
                        'published'  => 'badge badge-published',
                        'draft'      => 'badge badge-draft',
                        'cancelled'  => 'badge badge-cancelled',
                        'completed'  => 'badge badge-completed',
                        default      => 'badge',
                    };
                @endphp
                <span class="{{ $badgeClass }}">{{ ucfirst($event->status) }}</span>
                @if($event->remaining_spot <= 0 && $event->status === 'published')
                    <span class="badge badge-booked">Fully Booked</span>
                @endif
            </div>
        </div>

        <hr class="divider">

        <p style="color:#374151;line-height:1.7;">{{ $event->description }}</p>

        <hr class="divider">

        {{-- Meta grid --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;">
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.05em;">Date &amp; Time</p>
                <p style="margin:0.25rem 0 0;font-size:0.95rem;font-weight:600;">{{ $event->date_time->format('M d, Y · g:i A') }}</p>
            </div>
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.05em;">Location</p>
                <p style="margin:0.25rem 0 0;font-size:0.95rem;font-weight:600;">{{ $event->location }}</p>
            </div>
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.05em;">Capacity</p>
                <p style="margin:0.25rem 0 0;font-size:0.95rem;font-weight:600;">{{ $event->capacity }}</p>
            </div>
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.05em;">Spots Remaining</p>
                <p style="margin:0.25rem 0 0;font-size:0.95rem;font-weight:600;">{{ $event->remaining_spot }}</p>
            </div>
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.05em;">Price</p>
                <p style="margin:0.25rem 0 0;font-size:0.95rem;font-weight:600;">{{ $event->price ? $currencySymbol.number_format($event->price, 2) : 'Free' }}</p>
            </div>
        </div>

        <hr class="divider">

        {{-- Actions --}}
        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;align-items:center;">
            <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm">← Back</a>

            @if(!auth()->check() || !auth()->user()->is_admin)
                @if($isBooked ?? false)
                    <span class="badge badge-confirmed" style="padding:0.5rem 1rem;font-size:0.82rem;">✅ You're Registered</span>
                @elseif($event->status === 'published' && $event->remaining_spot > 0 && !$event->isCompleted())
                    <a href="{{ route('events.register.page', $event) }}" class="btn btn-success">Register Now</a>
                @elseif($event->status === 'published' && $event->remaining_spot <= 0)
                    <a href="{{ route('events.register.page', $event) }}" class="btn btn-primary">Join Waitlist</a>
                @else
                    <span class="badge badge-completed" style="padding:0.5rem 1rem;font-size:0.82rem;">Registration Unavailable</span>
                @endif
            @endif

            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-accent btn-sm">Edit Event</a>
                    <a href="{{ route('admin.events.registrations.index', $event) }}" class="btn btn-primary btn-sm">Manage Registrations</a>
                @endif
            @endauth
        </div>
    </div>
</div>
@endsection
