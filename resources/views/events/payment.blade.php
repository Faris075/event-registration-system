@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:560px;">

    {{-- Progress steps --}}
    <div style="display:flex;align-items:center;gap:0;margin-bottom:2rem;">
        <div style="display:flex;align-items:center;gap:0.5rem;color:var(--success);font-weight:600;font-size:0.9rem;">
            <span style="width:26px;height:26px;border-radius:50%;background:var(--success);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;">✓</span>
            Details
        </div>
        <div style="flex:1;height:2px;background:var(--accent);margin:0 0.5rem;"></div>
        <div style="display:flex;align-items:center;gap:0.5rem;color:var(--accent);font-weight:700;font-size:0.9rem;">
            <span style="width:26px;height:26px;border-radius:50%;background:var(--accent);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;">2</span>
            Payment
        </div>
        <div style="flex:1;height:2px;background:var(--border);margin:0 0.5rem;"></div>
        <div style="display:flex;align-items:center;gap:0.5rem;color:var(--muted);font-size:0.9rem;">
            <span style="width:26px;height:26px;border-radius:50%;border:2px solid var(--border);color:var(--muted);display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;">3</span>
            Confirmation
        </div>
    </div>

    @if($errors->has('payment'))
        <div class="alert alert-error">{{ $errors->first('payment') }}</div>
    @endif

    {{-- Order summary --}}
    <div class="card" style="margin-bottom:1rem;padding:1.2rem 1.5rem;">
        <p style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);margin:0 0 0.4rem;">Order Summary</p>
        <p style="font-weight:700;font-size:1.05rem;margin:0;">{{ $event->title }}</p>
        <p style="color:var(--muted);font-size:0.88rem;margin:0.2rem 0 0;">
            {{ \Carbon\Carbon::parse($event->date_time)->format('D, M d Y · g:i A') }}
            &mdash; {{ $pending['name'] }} ({{ $pending['email'] }})
        </p>
        <div style="margin-top:0.8rem;padding-top:0.8rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.9rem;color:var(--muted);">Registration fee</span>
            <span style="font-weight:700;font-size:1.1rem;color:var(--accent);">
                @if($event->price ?? 0 > 0)
                    ${{ number_format($event->price, 2) }}
                @else
                    Free
                @endif
            </span>
        </div>
    </div>

    {{-- Payment form --}}
    <div class="card">
        <h1 class="page-title" style="font-size:1.3rem;">Payment Details</h1>
        <p class="page-subtitle" style="margin-bottom:1.5rem;font-size:0.88rem;">
            🔒 This is a <strong>demo payment form</strong>. No real charges will be made.
        </p>

        <form action="{{ route('events.payment.process', $event) }}" method="POST" class="form-grid" id="payment-form">
            @csrf

            <div class="form-field">
                <label class="form-label">Cardholder Name</label>
                <input type="text" name="card_name" value="{{ old('card_name', $pending['name']) }}"
                       class="form-input" placeholder="John Doe" required autocomplete="cc-name">
                @error('card_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Card Number</label>
                <div style="position:relative;">
                    <input type="text" name="card_number" value="{{ old('card_number') }}"
                           class="form-input" placeholder="1234 5678 9012 3456"
                           maxlength="19" id="card-number-input" required autocomplete="cc-number"
                           style="padding-right:3rem;">
                    <span style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);font-size:1.4rem;" id="card-brand-icon">💳</span>
                </div>
                @error('card_number') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Expiry Date</label>
                    <input type="text" name="card_expiry" value="{{ old('card_expiry') }}"
                           class="form-input" placeholder="MM/YY" maxlength="5"
                           id="card-expiry-input" required autocomplete="cc-exp">
                    @error('card_expiry') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">CVV</label>
                    <input type="text" name="card_cvv" value="{{ old('card_cvv') }}"
                           class="form-input" placeholder="123" maxlength="4"
                           required autocomplete="cc-csc">
                    @error('card_cvv') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display:flex;gap:0.6rem;margin-top:0.5rem;">
                <button type="submit" class="btn btn-success" id="pay-btn">
                    Pay &amp; Confirm Registration
                </button>
                <a href="{{ route('events.register.page', $event) }}" class="btn btn-ghost">← Back</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Card number formatting (spaces every 4 digits) + brand icon
    const cardInput = document.getElementById('card-number-input');
    const brandIcon = document.getElementById('card-brand-icon');
    if (cardInput) {
        cardInput.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').substring(0, 16);
            this.value = v.replace(/(.{4})/g, '$1 ').trim();
            // Strip spaces before form submit
            const first = v[0];
            brandIcon.textContent = first === '4' ? '💳' : first === '5' ? '💳' : first === '3' ? '💳' : '💳';
        });
        // Remove spaces on submit so only digits are sent
        cardInput.closest('form').addEventListener('submit', function () {
            cardInput.value = cardInput.value.replace(/\s/g, '');
        });
    }

    // Expiry auto-slash
    const expiryInput = document.getElementById('card-expiry-input');
    if (expiryInput) {
        expiryInput.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 3) v = v.substring(0, 2) + '/' + v.substring(2);
            this.value = v;
        });
    }
</script>
@endsection
