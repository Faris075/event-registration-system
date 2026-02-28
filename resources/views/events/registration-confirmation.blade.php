@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:680px;">

    <div class="card">
        <h1 class="page-title">Registration Confirmed</h1>

        <div class="alert alert-success" style="margin-top:1rem;">{{ $confirmation['success_message'] }}</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1.25rem;">
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.04em;">Attendee</p>
                <p style="margin:0.2rem 0 0;font-weight:600;">{{ $confirmation['attendee_name'] }}</p>
            </div>
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.04em;">Event</p>
                <p style="margin:0.2rem 0 0;font-weight:600;">{{ $confirmation['event_title'] }}</p>
            </div>
            <div>
                <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.04em;">Status</p>
                @php
                    $sc = $confirmation['registration_status'] === 'confirmed' ? 'badge badge-confirmed' : 'badge badge-waitlisted';
                @endphp
                <span class="{{ $sc }}" style="margin-top:0.2rem;display:inline-flex;">{{ ucfirst($confirmation['registration_status']) }}</span>
            </div>
            @if($confirmation['registration_status'] === 'waitlisted')
                <div>
                    <p style="margin:0;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.04em;">Waitlist Position</p>
                    <p style="margin:0.2rem 0 0;font-weight:600;">#{{ $confirmation['waitlist_position'] }}</p>
                </div>
            @endif
        </div>

        <hr class="divider">

        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
            <a href="{{ route('events.index') }}" class="btn btn-ghost">Browse Events</a>
            <a href="{{ route('events.show', $event) }}" class="btn btn-primary">Back to Event</a>
        </div>
    </div>
</div>
@endsection
