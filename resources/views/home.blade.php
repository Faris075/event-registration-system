<x-guest-layout>
    @auth
        <div class="auth-card">
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">You are already signed in.</p>

            <div class="auth-form">
                <a href="{{ route('events.index') }}" class="auth-btn" style="display:inline-block;text-align:center;text-decoration:none;">Go to Events</a>
                <a href="{{ route('dashboard') }}" class="auth-btn" style="display:inline-block;text-align:center;text-decoration:none;">Open Dashboard</a>
            </div>
        </div>
    @else
        <div class="auth-card">
            <h2 class="auth-title">Sign In</h2>
            <p class="auth-subtitle">Nice to see you again! Enter your details to continue.</p>

            @if(session('status'))
                <div class="alert alert-success" style="margin-bottom:0.75rem;">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="auth-field">
                    <label for="email" class="auth-label">Your Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@mail.com" class="auth-input" />
                    @error('email')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password" class="auth-label">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="********" class="auth-input" />
                    @error('password')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-row">
                    <label for="remember" class="auth-check">
                        <input id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300">
                        <span class="ms-2">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="auth-link" href="{{ route('password.request') }}">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <button type="submit" class="auth-btn">Sign In</button>
            </form>

            <p class="auth-center">
                Don’t have an account?
                <a href="{{ route('register') }}" class="auth-link">Sign Up</a>
            </p>

            <p class="auth-center" style="margin-top:0.5rem;font-size:0.85rem;">
                By signing in, you agree to the
                <a href="#" class="auth-link">Terms and Conditions</a>.
            </p>
        </div>
    @endauth
</x-guest-layout>
