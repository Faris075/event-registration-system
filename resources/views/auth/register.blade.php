<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Sign Up</h2>
        <p class="auth-subtitle">Nice to meet you! Enter your details to register.</p>

        <form method="POST" action="{{ route('register') }}" class="auth-form" id="signup-form" novalidate>
            @csrf

            <div class="auth-field">
                <label for="name" class="auth-label">Your Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe" class="auth-input" minlength="2" maxlength="255" />
                <div class="auth-error" id="name-error" style="display:none;"></div>
                @error('name') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label for="email" class="auth-label">Your Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@mail.com" class="auth-input" maxlength="255" />
                <div class="auth-error" id="email-error" style="display:none;"></div>
                @error('email') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label for="phone" class="auth-label">Phone <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="e.g. 0501234567" class="auth-input" maxlength="11" inputmode="numeric" />
                <div class="auth-error" id="phone-error" style="display:none;"></div>
                @error('phone') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 chars, upper+lower+number+symbol" class="auth-input" />
                {{-- Strength meter --}}
                <div id="pw-strength-bar" style="height:4px;border-radius:2px;margin-top:6px;background:#e5e7eb;transition:background 0.3s;">
                    <div id="pw-strength-fill" style="height:100%;width:0%;border-radius:2px;transition:width 0.3s,background 0.3s;"></div>
                </div>
                <div id="pw-strength-label" style="font-size:0.78rem;margin-top:4px;color:var(--muted);"></div>
                <div class="auth-error" id="password-error" style="display:none;"></div>
                @error('password') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password" class="auth-input" />
                <div class="auth-error" id="confirm-error" style="display:none;"></div>
                @error('password_confirmation') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label for="security_question" class="auth-label">Security Question</label>
                <select id="security_question" name="security_question" class="auth-input" required>
                    <option value="">— Select a question —</option>
                    @foreach([
                        1 => "What is your mother's maiden name?",
                        2 => "What was the name of your first pet?",
                        3 => "What city were you born in?",
                        4 => "What was the name of your first school?",
                        5 => "What is your favourite movie?",
                        6 => "What is the name of your oldest sibling?",
                    ] as $id => $q)
                        <option value="{{ $id }}" {{ old('security_question') == $id ? 'selected' : '' }}>{{ $q }}</option>
                    @endforeach
                </select>
                <div class="auth-error" id="sq-error" style="display:none;"></div>
                @error('security_question') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label for="security_answer" class="auth-label">Your Answer</label>
                <input id="security_answer" type="text" name="security_answer" value="{{ old('security_answer') }}" required autocomplete="off" placeholder="Answer (case-insensitive)" class="auth-input" minlength="1" maxlength="255" />
                <div class="auth-error" id="sa-error" style="display:none;"></div>
                @error('security_answer') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="auth-btn">Sign Up</button>
        </form>

        <p class="auth-center">
            Already have an account?
            <a href="{{ route('login') }}" class="auth-link">Sign In</a>
        </p>
    </div>
</x-guest-layout>

<script>
(function () {
    function showE(id, msg) { const el = document.getElementById(id); if (el) { el.textContent = msg; el.style.display = 'block'; } }
    function clearE(id) { const el = document.getElementById(id); if (el) { el.textContent = ''; el.style.display = 'none'; } }

    // ── Password strength scorer ──────────────────────────────────────────
    function scorePassword(pw) {
        let score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[a-z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return score; // 0–6
    }

    function updateStrength(pw) {
        const fill  = document.getElementById('pw-strength-fill');
        const label = document.getElementById('pw-strength-label');
        if (!fill || !label) return;
        if (!pw) { fill.style.width = '0%'; label.textContent = ''; return; }
        const score = scorePassword(pw);
        let pct, color, text;
        if (score <= 2)      { pct = '25%';  color = '#ef4444'; text = 'Weak'; }
        else if (score <= 4) { pct = '60%';  color = '#f59e0b'; text = 'Medium'; }
        else                 { pct = '100%'; color = '#22c55e'; text = 'Strong'; }
        fill.style.width      = pct;
        fill.style.background = color;
        label.style.color     = color;
        label.textContent     = text;
    }

    function isStrongEnough(pw) {
        return pw.length >= 8
            && /[A-Z]/.test(pw)
            && /[a-z]/.test(pw)
            && /[0-9]/.test(pw)
            && /[^A-Za-z0-9]/.test(pw);
    }

    // ── Real-time handlers ────────────────────────────────────────────────
    document.getElementById('name')?.addEventListener('input', function () {
        this.value.trim().length < 2 ? showE('name-error', 'Name must be at least 2 characters.') : clearE('name-error');
    });
    document.getElementById('email')?.addEventListener('input', function () {
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim()) ? clearE('email-error') : showE('email-error', 'Enter a valid email address.');
    });
    document.getElementById('phone')?.addEventListener('input', function () {
        if (!this.value) { clearE('phone-error'); return; }
        /^[0-9]{9,11}$/.test(this.value.trim())
            ? clearE('phone-error')
            : showE('phone-error', 'Phone must be 9–11 digits only.');
    });
    document.getElementById('password')?.addEventListener('input', function () {
        updateStrength(this.value);
        if (!isStrongEnough(this.value)) {
            showE('password-error', 'Password must be ≥8 chars with uppercase, lowercase, a number, and a symbol.');
        } else {
            clearE('password-error');
        }
        // Re-check confirm match live
        const pc = document.getElementById('password_confirmation');
        if (pc?.value) {
            pc.value !== this.value ? showE('confirm-error', 'Passwords do not match.') : clearE('confirm-error');
        }
    });
    document.getElementById('password_confirmation')?.addEventListener('input', function () {
        this.value !== document.getElementById('password')?.value
            ? showE('confirm-error', 'Passwords do not match.')
            : clearE('confirm-error');
    });

    // ── Submit guard ──────────────────────────────────────────────────────
    document.getElementById('signup-form')?.addEventListener('submit', function (e) {
        let valid = true;
        const name = document.getElementById('name');
        if (!name?.value.trim() || name.value.trim().length < 2)         { showE('name-error',     'Name is required (at least 2 characters).'); valid = false; }
        const email = document.getElementById('email');
        if (!email?.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) { showE('email-error', 'A valid email is required.');             valid = false; }
        const phone = document.getElementById('phone');
        if (phone?.value && !/^[0-9]{9,11}$/.test(phone.value.trim()))   { showE('phone-error',    'Phone must be 9–11 digits only.');         valid = false; }
        const pw = document.getElementById('password');
        if (!pw?.value || !isStrongEnough(pw.value))                      { showE('password-error', 'Password does not meet the requirements.'); valid = false; }
        const pc = document.getElementById('password_confirmation');
        if (pc?.value !== pw?.value)                                       { showE('confirm-error',  'Passwords do not match.');                  valid = false; }
        const sq = document.getElementById('security_question');
        if (!sq?.value)                                                    { showE('sq-error',       'Please select a security question.');       valid = false; }
        const sa = document.getElementById('security_answer');
        if (!sa?.value.trim())                                             { showE('sa-error',       'Please provide an answer.');               valid = false; }
        if (!valid) e.preventDefault();
    });
})();
</script>
