{{-- ─── Cancel-registration confirmation modal ──────────────────────────────── --}}
<div id="cancel-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:var(--card-bg,#fff);border-radius:0.75rem;padding:2rem;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <h3 style="font-size:1.1rem;font-weight:700;margin:0 0 0.5rem;">Cancel Registration?</h3>
        <p id="cancel-modal-body" style="color:var(--muted);margin:0 0 1.5rem;line-height:1.5;"></p>
        <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
            <button type="button" onclick="closeCancelModal()"
                    style="padding:0.5rem 1.25rem;border-radius:0.5rem;border:1.5px solid var(--border,#e5e7eb);background:transparent;cursor:pointer;font-size:0.9rem;">
                Keep Registration
            </button>
            <button type="button" id="cancel-modal-confirm"
                    style="padding:0.5rem 1.25rem;border-radius:0.5rem;border:none;background:#ef4444;color:#fff;cursor:pointer;font-size:0.9rem;font-weight:600;">
                Yes, Cancel It
            </button>
        </div>
    </div>
</div>

<script>
    function openCancelModal(form, eventTitle, isPaid) {
        const modal = document.getElementById('cancel-modal');
        const body  = document.getElementById('cancel-modal-body');
        let msg = 'Are you sure you want to cancel your registration for <strong>' + eventTitle + '</strong>? This cannot be undone.';
        if (isPaid) msg += '<br><br>💳 <strong>Your payment will be refunded.</strong>';
        body.innerHTML = msg;
        document.getElementById('cancel-modal-confirm').onclick = function () { form.submit(); };
        modal.style.display = 'flex';
    }
    function closeCancelModal() {
        document.getElementById('cancel-modal').style.display = 'none';
    }
    // Close if backdrop clicked
    document.getElementById('cancel-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });
</script>

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
                                <th>Payment</th>
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
                                    <td>
                                        @php
                                            $payBadge = match($reg->payment_status ?? 'pending') {
                                                'paid'     => 'badge-confirmed',
                                                'refunded' => 'badge-cancelled',
                                                default    => 'badge-waitlisted',
                                            };
                                        @endphp
                                        <span class="badge {{ $payBadge }}">{{ ucfirst($reg->payment_status ?? 'pending') }}</span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($reg->registration_date)->format('M d, Y') }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                                        @if($reg->event)
                                            <a href="{{ route('events.show', $reg->event) }}" class="btn btn-ghost btn-sm">View</a>
                                        @endif
                                        @if($reg->status !== 'cancelled' && $reg->event && \Carbon\Carbon::parse($reg->event->date_time)->isFuture())
                                            @php $isPaid = ($reg->payment_status === 'paid') ? 'true' : 'false'; @endphp
                                            <form method="POST" action="{{ route('events.registration.cancel', [$reg->event_id, $reg->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        onclick="openCancelModal(this.closest('form'), '{{ addslashes($reg->event->title) }}', {{ $isPaid }})"
                                                        class="btn btn-ghost btn-sm"
                                                        style="color:var(--danger,#ef4444);border-color:var(--danger,#ef4444);">
                                                    Cancel
                                                </button>
                                            </form>
                                        @endif
                                        </div>
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
