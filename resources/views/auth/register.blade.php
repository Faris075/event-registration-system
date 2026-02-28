<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Sign Up</h2>
        <p class="auth-subtitle">Nice to meet you! Enter your details to register.</p>

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="name" class="auth-label">Your Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe" class="auth-input" />
                @error('name')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="email" class="auth-label">Your Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@mail.com" class="auth-input" />
                @error('email')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="********" class="auth-input" />
                @error('password')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="********" class="auth-input" />
                @error('password_confirmation')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="security_question" class="auth-label">Security Question</label>
                <select id="security_question" name="security_question" class="auth-input" required>
                    <option value="">— Select a question —</option>
                    @foreach([
                        "What is your mother's maiden name?",
                        "What was the name of your first pet?",
                        "What city were you born in?",
                        "What was the name of your first school?",
                        "What is your favourite movie?",
                        "What is the name of your oldest sibling?",
                    ] as $q)
                        <option value="{{ $q }}" {{ old('security_question') === $q ? 'selected' : '' }}>{{ $q }}</option>
                    @endforeach
                </select>
                @error('security_question')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="security_answer" class="auth-label">Your Answer</label>
                <input id="security_answer" type="text" name="security_answer" value="{{ old('security_answer') }}" required autocomplete="off" placeholder="Answer (case-insensitive)" class="auth-input" />
                @error('security_answer')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth-btn">Sign Up</button>
        </form>

        <p class="auth-center">
            Already have an account?
            <a href="{{ route('login') }}" class="auth-link">Sign In</a>
        </p>
    </div>
</x-guest-layout>
