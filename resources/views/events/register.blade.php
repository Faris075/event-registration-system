@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:600px;">

    @if($errors->has('registration'))
        <div class="alert alert-error">{{ $errors->first('registration') }}</div>
    @endif

    {{-- Same-day conflict warning: user already has a confirmed registration on this day --}}
    @if(!empty($conflictEvents) && $conflictEvents->isNotEmpty())
        <div class="alert alert-warn" style="display:flex;gap:0.75rem;align-items:flex-start;" id="conflict-warning">
            <span style="font-size:1.2rem;flex-shrink:0;">⚠️</span>
            <div>
                <strong>Scheduling conflict:</strong> You already have a confirmed registration for
                <strong>{{ $conflictEvents->pluck('title')->join(', ') }}</strong>
                on this same day ({{ $event->date_time->format('M d, Y') }}).
                You can still register, but you may not be able to attend both events.
            </div>
        </div>
    @endif

    @if($event->remaining_spot <= 0 && $event->status === 'published')
        <div class="alert alert-warn">This event is fully booked. Registering will add you to the waitlist.</div>
    @endif

    <div class="card">
        <h1 class="page-title">{{ $event->remaining_spot <= 0 ? 'Join Waitlist' : 'Register for Event' }}</h1>
        <p class="page-subtitle" style="margin-bottom:1.5rem;">{{ $event->title }}</p>

        <form action="{{ route('events.register', $event) }}" method="POST" class="form-grid" id="register-form" novalidate>
            @csrf

            <div class="form-field">
                <label class="form-label">Full Name <span style="color:var(--danger);">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', auth()->user()?->name) }}" class="form-input" placeholder="John Doe" required minlength="2" maxlength="255" autocomplete="name">
                <span class="form-error" id="name-error" style="display:none;"></span>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Email Address <span style="color:var(--danger);">*</span></label>
                <input type="email" name="email" id="reg-email" value="{{ old('email', auth()->user()?->email) }}" class="form-input" placeholder="name@mail.com" required maxlength="255" {{ auth()->check() ? 'readonly style=background:var(--bg);cursor:not-allowed;' : '' }} autocomplete="email">
                <span class="form-error" id="email-error" style="display:none;"></span>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Phone <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="form-input" placeholder="+1 555 000 0000"
                           pattern="^\+?[0-9\s\-\(\)]{7,20}$" maxlength="20" autocomplete="tel">
                    <span class="form-error" id="phone-error" style="display:none;"></span>
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Company <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                    <input type="text" name="company" value="{{ old('company') }}" class="form-input" placeholder="Acme Inc." maxlength="255" autocomplete="organization">
                    @error('company') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Terms & Conditions --}}
            <div class="form-field" style="margin-top:0.25rem;">
                <label style="display:flex;align-items:flex-start;gap:0.6rem;cursor:pointer;">
                    <input type="checkbox" name="terms_accepted" id="terms_accepted" value="1" required style="margin-top:3px;flex-shrink:0;" {{ old('terms_accepted') ? 'checked' : '' }}>
                    <span style="font-size:0.88rem;color:var(--muted);line-height:1.5;">
                        I have read and agree to the
                        <a href="{{ route('terms') }}" target="_blank" class="auth-link" style="color:var(--accent);">Terms &amp; Conditions</a>.
                    </span>
                </label>
                <span class="form-error" id="terms-error" style="display:none;">You must accept the Terms &amp; Conditions to register.</span>
                @error('terms_accepted') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div style="display:flex;gap:0.6rem;margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary" id="register-submit-btn">{{ $event->remaining_spot <= 0 ? 'Join Waitlist' : 'Submit Registration' }}</button>
                <a href="{{ route('events.show', $event) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('register-form');
    if (!form) return;

    function showError(inputId, errorId, msg) {
        const el = document.getElementById(errorId);
        const input = document.getElementById(inputId);
        if (el) { el.textContent = msg; el.style.display = 'block'; }
        if (input) input.style.borderColor = 'var(--danger, #ef4444)';
    }
    function clearError(inputId, errorId) {
        const el = document.getElementById(errorId);
        const input = document.getElementById(inputId);
        if (el) { el.textContent = ''; el.style.display = 'none'; }
        if (input) input.style.borderColor = '';
    }

    // Real-time validation
    document.getElementById('name')?.addEventListener('input', function () {
        this.value.trim().length < 2
            ? showError('name', 'name-error', 'Name must be at least 2 characters.')
            : clearError('name', 'name-error');
    });

    document.getElementById('reg-email')?.addEventListener('input', function () {
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());
        valid ? clearError('reg-email', 'email-error') : showError('reg-email', 'email-error', 'Enter a valid email address.');
    });

    document.getElementById('phone')?.addEventListener('input', function () {
        if (!this.value) { clearError('phone', 'phone-error'); return; }
        const valid = /^\+?[0-9\s\-\(\)]{7,20}$/.test(this.value.trim());
        valid ? clearError('phone', 'phone-error') : showError('phone', 'phone-error', 'Enter a valid phone number (7–20 digits, +, spaces, dashes allowed).');
    });

    form.addEventListener('submit', function (e) {
        let valid = true;

        const name = document.getElementById('name');
        if (!name?.value.trim() || name.value.trim().length < 2) {
            showError('name', 'name-error', 'Name is required (at least 2 characters).');
            valid = false;
        }

        const email = document.getElementById('reg-email');
        if (email && !email.readOnly && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email?.value.trim())) {
            showError('reg-email', 'email-error', 'A valid email address is required.');
            valid = false;
        }

        const phone = document.getElementById('phone');
        if (phone?.value && !/^\+?[0-9\s\-\(\)]{7,20}$/.test(phone.value.trim())) {
            showError('phone', 'phone-error', 'Enter a valid phone number.');
            valid = false;
        }

        const terms = document.getElementById('terms_accepted');
        if (!terms?.checked) {
            document.getElementById('terms-error').style.display = 'block';
            valid = false;
        } else {
            document.getElementById('terms-error').style.display = 'none';
        }

        if (!valid) e.preventDefault();
    });
})();
</script>
@endsection
