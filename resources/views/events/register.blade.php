@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:600px;">

    @if($errors->has('registration'))
        <div class="alert alert-error">{{ $errors->first('registration') }}</div>
    @endif

    @if($event->remaining_spot <= 0 && $event->status === 'published')
        <div class="alert alert-warn">This event is fully booked. Registering will add you to the waitlist.</div>
    @endif

    <div class="card">
        <h1 class="page-title">{{ $event->remaining_spot <= 0 ? 'Join Waitlist' : 'Register for Event' }}</h1>
        <p class="page-subtitle" style="margin-bottom:1.5rem;">{{ $event->title }}</p>

        <form action="{{ route('events.register', $event) }}" method="POST" class="form-grid">
            @csrf

            <div class="form-field">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" class="form-input" placeholder="John Doe" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="form-input" placeholder="name@mail.com" required {{ auth()->check() ? 'readonly style=background:var(--bg);cursor:not-allowed;' : '' }}>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Phone <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="+1 555 000 0000">
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Company <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                    <input type="text" name="company" value="{{ old('company') }}" class="form-input" placeholder="Acme Inc.">
                    @error('company') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display:flex;gap:0.6rem;margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary">{{ $event->remaining_spot <= 0 ? 'Join Waitlist' : 'Submit Registration' }}</button>
                <a href="{{ route('events.show', $event) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
