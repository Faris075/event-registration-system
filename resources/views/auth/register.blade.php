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
                <label for="password" class="auth-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters" class="auth-input" minlength="8" />
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

    document.getElementById('name')?.addEventListener('input', function () {
        this.value.trim().length < 2 ? showE('name-error', 'Name must be at least 2 characters.') : clearE('name-error');
    });
    document.getElementById('email')?.addEventListener('input', function () {
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim()) ? clearE('email-error') : showE('email-error', 'Enter a valid email address.');
    });
    document.getElementById('password')?.addEventListener('input', function () {
        this.value.length < 8 ? showE('password-error', 'Password must be at least 8 characters.') : clearE('password-error');
    });
    document.getElementById('password_confirmation')?.addEventListener('input', function () {
        this.value !== document.getElementById('password')?.value
            ? showE('confirm-error', 'Passwords do not match.')
            : clearE('confirm-error');
    });

    document.getElementById('signup-form')?.addEventListener('submit', function (e) {
        let valid = true;
        const name = document.getElementById('name');
        if (!name?.value.trim() || name.value.trim().length < 2)    { showE('name-error',    'Name is required (at least 2 characters).'); valid = false; }
        const email = document.getElementById('email');
        if (!email?.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) { showE('email-error',   'A valid email is required.');               valid = false; }
        const pw = document.getElementById('password');
        if (!pw?.value || pw.value.length < 8)                       { showE('password-error','Password must be at least 8 characters.');           valid = false; }
        const pc = document.getElementById('password_confirmation');
        if (pc?.value !== pw?.value)                                 { showE('confirm-error', 'Passwords do not match.');                            valid = false; }
        const sq = document.getElementById('security_question');
        if (!sq?.value)                                              { showE('sq-error',      'Please select a security question.');                 valid = false; }
        const sa = document.getElementById('security_answer');
        if (!sa?.value.trim())                                       { showE('sa-error',      'Please provide an answer to the security question.'); valid = false; }
        if (!valid) e.preventDefault();
    });
})();
</script>
