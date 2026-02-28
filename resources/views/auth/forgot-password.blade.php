<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Forgot Password</h2>
        <p class="auth-subtitle">
            Forgot your password? No problem. Enter your email address and we will send you a password reset link.
        </p>

        @if(session('status'))
            <div class="alert alert-success" style="margin-bottom:0.75rem;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="email" class="auth-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="auth-input" placeholder="name@mail.com" />
                @error('email')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth-btn">Email Password Reset Link</button>
        </form>
    </div>
</x-guest-layout>
