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

    @if(session('conflict_warning'))
        <div class="alert alert-warn" style="display:flex;gap:0.75rem;align-items:flex-start;">
            <span style="font-size:1.2rem;flex-shrink:0;">⚠️</span>
            <div>{{ session('conflict_warning') }}</div>
        </div>
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
                    {{ $currencySymbol }}{{ number_format(\App\Models\User::convertPrice($event->price, $currencyCode), 2) }}
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

        <form action="{{ route('events.payment.process', $event) }}" method="POST" class="form-grid" id="payment-form" novalidate>
            @csrf

            <div class="form-field">
                <label class="form-label">Cardholder Name <span style="color:var(--danger);">*</span></label>
                <input type="text" name="card_name" id="card-name-input" value="{{ old('card_name', $pending['name']) }}"
                       class="form-input" placeholder="John Doe"
                       required minlength="2" maxlength="255" autocomplete="cc-name">
                <span class="form-error" id="card-name-error" style="display:none;"></span>
                @error('card_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Card Number <span style="color:var(--danger);">*</span></label>
                <div style="position:relative;">
                    <input type="text" name="card_number" value="{{ old('card_number') }}"
                           class="form-input" placeholder="1234 5678 9012 3456"
                           maxlength="19" id="card-number-input" required autocomplete="cc-number"
                           inputmode="numeric" style="padding-right:6rem;">
                    {{-- Brand badge --}}
                    <span id="card-brand-badge" style="
                        position:absolute;right:0.6rem;top:50%;transform:translateY(-50%);
                        font-size:0.7rem;font-weight:700;letter-spacing:0.05em;
                        padding:2px 6px;border-radius:4px;
                        background:#e5e7eb;color:#6b7280;
                        white-space:nowrap;pointer-events:none;
                    ">????</span>
                </div>
                <span class="form-error" id="card-number-error" style="display:none;"></span>
                @error('card_number') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Expiry Date <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="card_expiry" value="{{ old('card_expiry') }}"
                           class="form-input" placeholder="MM/YY" maxlength="5"
                           id="card-expiry-input" required pattern="^(0[1-9]|1[0-2])\/\d{2}$"
                           inputmode="numeric" autocomplete="cc-exp">
                    <span class="form-error" id="card-expiry-error" style="display:none;"></span>
                    @error('card_expiry') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">CVV <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="card_cvv" id="card-cvv-input" value="{{ old('card_cvv') }}"
                           class="form-input" placeholder="123" maxlength="4"
                           required pattern="^\d{3,4}$" inputmode="numeric" autocomplete="cc-csc">
                    <span class="form-error" id="card-cvv-error" style="display:none;"></span>
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
(function () {
    /* ── Card brand detection ───────────────────────────────────── */
    const BRANDS = [
        { name: 'VISA',       bg: '#1a1f71', color: '#fff', pattern: /^4/ },
        { name: 'MASTERCARD', bg: '#eb001b', color: '#fff', pattern: /^5[1-5]|^2(2[2-9]|[3-6]\d|7[01])/ },
        { name: 'AMEX',       bg: '#2e77bc', color: '#fff', pattern: /^3[47]/ },
        { name: 'DISCOVER',   bg: '#e65c00', color: '#fff', pattern: /^6(?:011|22(?:1(?:2[6-9]|[3-9])|[2-8])|4[4-9]|5)/ },
        { name: 'JCB',        bg: '#003087', color: '#fff', pattern: /^35/ },
        { name: 'UNIONPAY',   bg: '#c0392b', color: '#fff', pattern: /^62/ },
    ];

    function detectBrand(digits) {
        return BRANDS.find(b => b.pattern.test(digits)) || null;
    }

    const badge    = document.getElementById('card-brand-badge');
    const cardInput = document.getElementById('card-number-input');

    function updateBadge(digits) {
        const brand = detectBrand(digits);
        if (!digits) {
            badge.textContent = '????';
            badge.style.background = '#e5e7eb';
            badge.style.color = '#6b7280';
        } else if (brand) {
            badge.textContent = brand.name;
            badge.style.background = brand.bg;
            badge.style.color = brand.color;
        } else {
            badge.textContent = 'UNKNOWN';
            badge.style.background = '#fef3c7';
            badge.style.color = '#92400e';
        }
    }

    /* ── Error helpers ─────────────────────────────────────────── */
    function showError(inputId, errorId, msg) {
        const el = document.getElementById(errorId);
        const inp = document.getElementById(inputId);
        if (el) { el.textContent = msg; el.style.display = 'block'; }
        if (inp) inp.style.outline = '2px solid var(--danger, #ef4444)';
    }
    function clearError(inputId, errorId) {
        const el = document.getElementById(errorId);
        const inp = document.getElementById(inputId);
        if (el) { el.textContent = ''; el.style.display = 'none'; }
        if (inp) inp.style.outline = '';
    }

    /* ── Card number: format + brand ───────────────────────────── */
    function formatCardNumber(raw) {
        // Strip non-digits, cap at 16, then insert a space after every 4th digit.
        const digits = raw.replace(/\D/g, '').substring(0, 16);
        return digits.replace(/(\d{4})(?=\d)/g, '$1 ');
    }

    function validateCardNumber(digits) {
        if (!digits) {
            clearError('card-number-input', 'card-number-error');
            return;
        }
        // Real-time: don't nag about length while the user is still typing.
        // Only flag an unrecognised brand (we have enough prefix after 4 digits).
        if (!detectBrand(digits)) {
            showError('card-number-input', 'card-number-error', 'Card number not recognised — please check your card details.');
        } else {
            clearError('card-number-input', 'card-number-error');
        }
    }

    if (cardInput) {
        // Handle both typing and paste.
        ['input', 'paste'].forEach(function (evt) {
            cardInput.addEventListener(evt, function () {
                // Use setTimeout so paste value is available in the handler.
                setTimeout(() => {
                    const digits = this.value.replace(/\D/g, '').substring(0, 16);
                    this.value = formatCardNumber(this.value);
                    updateBadge(digits);
                    // Only validate brand once the user has typed enough digits to detect.
                    if (digits.length >= 4) validateCardNumber(digits);
                    else clearError('card-number-input', 'card-number-error');
                }, 0);
            });
        });
        updateBadge(''); // initialise on page load
    }

    /* ── Expiry: auto-slash + validation ───────────────────────── */
    const expiryInput = document.getElementById('card-expiry-input');
    if (expiryInput) {
        expiryInput.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 3) v = v.substring(0, 2) + '/' + v.substring(2);
            this.value = v;
            if (v.length === 5) {
                const [mm, yy] = v.split('/').map(Number);
                const now = new Date();
                const expYear = 2000 + yy;
                const expMonth = mm;
                const valid = mm >= 1 && mm <= 12 &&
                    (expYear > now.getFullYear() ||
                     (expYear === now.getFullYear() && expMonth >= now.getMonth() + 1));
                valid
                    ? clearError('card-expiry-input', 'card-expiry-error')
                    : showError('card-expiry-input', 'card-expiry-error', 'Card has expired or date is invalid.');
            } else {
                clearError('card-expiry-input', 'card-expiry-error');
            }
        });
    }

    /* ── CVV: digits only ──────────────────────────────────────── */
    const cvvInput = document.getElementById('card-cvv-input');
    if (cvvInput) {
        cvvInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
            if (this.value && (this.value.length < 3)) {
                showError('card-cvv-input', 'card-cvv-error', 'CVV must be 3 or 4 digits.');
            } else {
                clearError('card-cvv-input', 'card-cvv-error');
            }
        });
    }

    /* ── Cardholder name ───────────────────────────────────────── */
    const nameInput = document.getElementById('card-name-input');
    if (nameInput) {
        nameInput.addEventListener('input', function () {
            this.value.trim().length < 2
                ? showError('card-name-input', 'card-name-error', 'Cardholder name is required.')
                : clearError('card-name-input', 'card-name-error');
        });
    }

    /* ── Form submit validation ────────────────────────────────── */
    const form = document.getElementById('payment-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            let valid = true;

            const nameVal = nameInput?.value.trim() ?? '';
            if (nameVal.length < 2) {
                showError('card-name-input', 'card-name-error', 'Cardholder name is required.');
                valid = false;
            }

            const digits = (cardInput?.value ?? '').replace(/\s/g, '');
            if (digits.length < 13 || digits.length > 16) {
                showError('card-number-input', 'card-number-error', 'Enter a valid 13–16 digit card number.');
                valid = false;
            } else if (!detectBrand(digits)) {
                showError('card-number-input', 'card-number-error', 'Card number not recognised — please check your card details.');
                valid = false;
            }

            const expVal = expiryInput?.value ?? '';
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expVal)) {
                showError('card-expiry-input', 'card-expiry-error', 'Enter a valid expiry date (MM/YY).');
                valid = false;
            } else {
                const [mm, yy] = expVal.split('/').map(Number);
                const now = new Date();
                const expYear = 2000 + yy;
                if (expYear < now.getFullYear() || (expYear === now.getFullYear() && mm < now.getMonth() + 1)) {
                    showError('card-expiry-input', 'card-expiry-error', 'Card has expired.');
                    valid = false;
                }
            }

            const cvvVal = cvvInput?.value ?? '';
            if (!/^\d{3,4}$/.test(cvvVal)) {
                showError('card-cvv-input', 'card-cvv-error', 'CVV must be 3 or 4 digits.');
                valid = false;
            }

            if (!valid) { e.preventDefault(); return; }

            // Strip spaces from card number before submitting
            if (cardInput) cardInput.value = digits;
        });
    }
})();
</script>
@endsection
