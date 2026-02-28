<x-guest-layout>
    <div class="auth-card">
        <h1 class="auth-title">Confirm Password</h1>
        <p class="auth-subtitle">This is a secure area. Please confirm your password before continuing.</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="password" class="auth-label">Password</label>
                <input id="password" type="password" name="password" class="auth-input" required autocomplete="current-password" />
                @error('password')<p class="auth-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="auth-btn">Confirm</button>
        </form>
    </div>
</x-guest-layout>
