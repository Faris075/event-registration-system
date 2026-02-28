<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Reset Password</h2>
        <p class="auth-subtitle">Enter your email and new password below.</p>

        <form method="POST" action="{{ route('password.store') }}" class="auth-form">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="auth-field">
                <label for="email" class="auth-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="auth-input" placeholder="name@mail.com" />
                @error('email')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="auth-input" placeholder="********" />
                @error('password')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="auth-input" placeholder="********" />
                @error('password_confirmation')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth-btn">Reset Password</button>
        </form>
    </div>
</x-guest-layout>
