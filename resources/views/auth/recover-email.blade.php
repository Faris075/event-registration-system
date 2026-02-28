<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Recover Password</h2>
        <p class="auth-subtitle">Enter the email address linked to your account and we'll ask your security question.</p>

        @if(session('error'))
            <div class="auth-error" style="margin-bottom:1rem;">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('security-question.recover.lookup') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="email" class="auth-label">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="email" placeholder="name@mail.com" class="auth-input" />
                @error('email')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth-btn">Continue</button>
        </form>

        <p class="auth-center">
            <a href="{{ route('login') }}" class="auth-link">← Back to Sign In</a>
        </p>
    </div>
</x-guest-layout>
