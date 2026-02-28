<x-app-layout>
    <div class="page-wrap">

        <div class="welcome-card">
            <h1>Welcome back, {{ Auth::user()->name }}! 👋</h1>
            <p>Manage your events and registrations from here.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
            <a href="{{ route('events.index') }}" class="btn btn-primary">Browse Events</a>
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('events.create') }}" class="btn btn-accent">Create Event</a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Manage Users</a>
                @endif
            @endauth
            <a href="{{ route('profile.edit') }}" class="btn btn-ghost">My Profile</a>
        </div>

        {{-- My Registrations --}}
        @if(!auth()->user()->is_admin)
            <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:1rem;">My Registrations</h2>

            @if($registrations->isEmpty())
                <div class="card" style="text-align:center;color:var(--muted);padding:2rem;">
                    <p>You haven\'t registered for any events yet.</p>
                    <a href="{{ route('events.index') }}" class="btn btn-primary" style="margin-top:1rem;">Browse Events</a>
                </div>
            @else
                <div class="card" style="padding:0;overflow:hidden;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Registered On</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registrations as $reg)
                                <tr>
                                    <td style="font-weight:600;">{{ $reg->event?->title ?? '—' }}</td>
                                    <td>{{ $reg->event?->date_time ? \Carbon\Carbon::parse($reg->event->date_time)->format('M d, Y') : '—' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($reg->status) {
                                                'confirmed'  => 'badge-confirmed',
                                                'waitlisted' => 'badge-waitlisted',
                                                'cancelled'  => 'badge-cancelled',
                                                default      => 'badge-pending',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($reg->status) }}</span>
                                        @if($reg->status === 'waitlisted' && $reg->waitlist_position)
                                            <span style="font-size:0.75rem;color:var(--muted);"> #{{ $reg->waitlist_position }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($reg->registration_date)->format('M d, Y') }}</td>
                                    <td>
                                        @if($reg->event)
                                            <a href="{{ route('events.show', $reg->event) }}" class="btn btn-ghost btn-sm">View Event</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

    </div>
</x-app-layout>
