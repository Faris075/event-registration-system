<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Set New Password</h2>
        <p class="auth-subtitle">Identity verified! Enter and confirm your new password.</p>

        <form method="POST" action="{{ route('security-question.reset') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="password" class="auth-label">New Password</label>
                <input id="password" type="password" name="password" required
                       autocomplete="new-password" placeholder="********" class="auth-input" />
                @error('password')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       required autocomplete="new-password" placeholder="********" class="auth-input" />
            </div>

            <button type="submit" class="auth-btn">Reset Password</button>
        </form>
    </div>
</x-guest-layout>
