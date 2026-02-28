<x-guest-layout>
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

        {{-- Try another way --}}
        <div style="margin-top:1rem;">
            <button id="try-another-toggle" type="button" class="auth-link" style="background:none;border:none;cursor:pointer;font-size:0.88rem;width:100%;text-align:center;padding:0.4rem 0;color:var(--muted);display:flex;align-items:center;justify-content:center;gap:0.4rem;">
                <span>Try another way</span>
                <svg id="try-another-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition:transform 0.2s;"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="try-another-panel" style="display:none;margin-top:0.5rem;border:1px solid var(--border);border-radius:0.5rem;padding:1rem;background:var(--bg);">
                <p style="font-size:0.82rem;color:var(--muted);margin:0 0 0.75rem;">Alternative sign-in options:</p>
                <a href="{{ route('security-question.recover') }}" style="display:flex;align-items:center;gap:0.6rem;padding:0.55rem 0.7rem;border-radius:0.4rem;border:1px solid var(--border);text-decoration:none;color:var(--text);font-size:0.88rem;transition:background 0.15s;" onmouseover="this.style.background='var(--bg-secondary, #f3f4f6)'" onmouseout="this.style.background=''">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent);flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <div style="font-weight:600;line-height:1.2;">Answer security question</div>
                        <div style="font-size:0.78rem;color:var(--muted);margin-top:1px;">Verify your identity with your preset question</div>
                    </div>
                </a>
            </div>
        </div>

        <p class="auth-center">
            Don’t have an account?
            <a href="{{ route('register') }}" class="auth-link">Sign Up</a>
        </p>
    </div>
</x-guest-layout>
<script>
(function () {
    const btn = document.getElementById('try-another-toggle');
    const panel = document.getElementById('try-another-panel');
    const chevron = document.getElementById('try-another-chevron');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
        const open = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : 'block';
        if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
    });
})();
</script>
