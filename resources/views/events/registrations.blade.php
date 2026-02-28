@extends('layouts.app')

@section('content')
<style>
.data-table th { padding: 0.75rem 1rem; white-space:nowrap; }
.data-table td { padding: 0.75rem 1rem; vertical-align: middle; }
.reg-action-row { display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; margin:0; }
</style>
<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1 class="page-title">Registrations — {{ $event->title }}</h1>
        </div>
        <a href="{{ route('admin.events.registrations.export', array_merge(['event' => $event], request()->query())) }}" class="btn btn-success btn-sm">Export CSV</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="stats-row">
        <div class="stat-chip"><span>📅 Date</span><strong>{{ $event->date_time }}</strong></div>
        <div class="stat-chip"><span>🎟 Capacity</span><strong>{{ $event->capacity }}</strong></div>
        <div class="stat-chip"><span>✅ Confirmed</span><strong>{{ $event->confirmed_count }}</strong></div>
        <div class="stat-chip"><span>⏳ Waitlist cap</span><strong>{{ $event->waitlistCapacity() }} spots</strong></div>
        <div class="stat-chip"><span>🔓 Admin overrides</span><strong>{{ $event->adminOverrideCount() }} / 5</strong></div>
    </div>

    {{-- Admin Force-Add Form --}}
    @if($errors->has('force_add'))
        <div class="alert alert-error">{{ $errors->first('force_add') }}</div>
    @endif
    <details class="card" style="margin-bottom:1rem;">
        <summary style="cursor:pointer;font-weight:700;color:var(--accent);padding:0.5rem 0;">+ Force-Add User (Admin Override — {{ 5 - $event->adminOverrideCount() }} remaining)</summary>
        <form method="POST" action="{{ route('admin.events.registrations.force-add', $event) }}" class="form-grid" style="margin-top:1rem;">
            @csrf
            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Phone <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Company <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                    <input type="text" name="company" value="{{ old('company') }}" class="form-input">
                </div>
            </div>
            <div>
                <button type="submit" class="btn btn-accent">Force-Add & Confirm</button>
            </div>
        </form>
    </details>

    <form method="GET" action="{{ route('admin.events.registrations.index', $event) }}" class="filter-bar">
        <div class="form-field">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmed</option>
                <option value="waitlisted" @selected(($filters['status'] ?? '') === 'waitlisted')>Waitlisted</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select">
                <option value="">All</option>
                <option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Pending</option>
                <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
                <option value="refunded" @selected(($filters['payment_status'] ?? '') === 'refunded')>Refunded</option>
            </select>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('admin.events.registrations.index', $event) }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    @if($registrations->isEmpty())
        <div class="empty-state">
            <p>No registrations found for this event yet.</p>
        </div>
    @else
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered At</th>
                        <th>Status</th>
                        <th>Waitlist #</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registrations as $registration)
                        @php
                            $sBadge = match($registration->status) {
                                'confirmed' => 'badge badge-confirmed',
                                'waitlisted' => 'badge badge-waitlisted',
                                'cancelled' => 'badge badge-cancelled',
                                default => 'badge',
                            };
                            $pBadge = match($registration->payment_status) {
                                'paid' => 'badge badge-confirmed',
                                'pending' => 'badge badge-waitlisted',
                                'refunded' => 'badge badge-cancelled',
                                default => 'badge',
                            };
                        @endphp
                        <tr>
                            <td style="font-weight:600;">
                                {{ $registration->attendee?->name }}
                                @if($registration->is_admin_override)
                                    <span class="badge badge-admin" style="font-size:0.65rem;margin-left:0.3rem;">Override</span>
                                @endif
                            </td>
                            <td>{{ $registration->attendee?->email }}</td>
                            <td>{{ $registration->attendee?->phone }}</td>
                            <td>{{ $registration->registration_date }}</td>
                            <td><span class="{{ $sBadge }}">{{ ucfirst($registration->status) }}</span></td>
                            <td>{{ $registration->waitlist_position ?? '—' }}</td>
                            <td><span class="{{ $pBadge }}">{{ ucfirst($registration->payment_status) }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.events.registrations.update', [$event, $registration]) }}" class="reg-action-row">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select" style="padding:0.3rem 0.6rem;font-size:0.82rem;" aria-label="Update status">
                                        <option value="confirmed" @selected($registration->status === 'confirmed')>Confirmed</option>
                                        <option value="waitlisted" @selected($registration->status === 'waitlisted')>Waitlisted</option>
                                        <option value="cancelled" @selected($registration->status === 'cancelled')>Cancelled</option>
                                    </select>
                                    <select name="payment_status" class="form-select" style="padding:0.3rem 0.6rem;font-size:0.82rem;{{ $registration->payment_status === 'paid' ? 'opacity:0.6;cursor:not-allowed;' : '' }}" aria-label="Update payment status" {{ $registration->payment_status === 'paid' ? 'disabled title="Payment already received — cannot be changed"' : '' }}>
                                        <option value="pending"  @selected($registration->payment_status === 'pending')>Pending</option>
                                        <option value="paid"     @selected($registration->payment_status === 'paid')>Paid</option>
                                        <option value="refunded" @selected($registration->payment_status === 'refunded')>Refunded</option>
                                    </select>
                                    @if($registration->payment_status === 'paid')
                                        {{-- hidden field so form still submits the paid value when the select is disabled --}}
                                        <input type="hidden" name="payment_status" value="paid">
                                    @endif
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            {{ $registrations->links() }}
            @if($registrations->lastPage() > 1)
            <form method="GET" action="{{ route('admin.events.registrations.index', $event) }}" style="display:flex;align-items:center;gap:0.4rem;">
                @foreach(request()->except('page') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label for="page-jump" style="font-size:0.82rem;color:var(--muted);white-space:nowrap;">Go to page:</label>
                <select id="page-jump" name="page" class="form-select" style="padding:0.25rem 0.5rem;font-size:0.82rem;width:auto;" onchange="this.form.submit()">
                    @for($p = 1; $p <= $registrations->lastPage(); $p++)
                        <option value="{{ $p }}" {{ $registrations->currentPage() == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endfor
                </select>
            </form>
            @endif
        </div>
    @endif

    <div style="margin-top:1.25rem;">
        <a href="{{ route('events.show', $event) }}" class="btn btn-ghost">← Back to Event</a>
    </div>
</div>
@endsection
