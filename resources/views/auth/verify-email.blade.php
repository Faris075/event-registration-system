<x-guest-layout>
    <div class="auth-card">
        <h1 class="auth-title">Verify Your Email</h1>
        <p class="auth-subtitle">Thanks for signing up! Please verify your email address by clicking the link we sent you.</p>

        @if(session('status') == 'verification-link-sent')
            <div class="alert alert-success" style="margin-bottom:1rem;">A new verification link has been sent to your email address.</div>
        @endif

        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="auth-btn" style="width:100%;">Resend Verification Email</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="auth-btn" style="width:100%;background:transparent;color:var(--muted);border:1px solid #e5e7eb;">Log Out</button>
            </form>
        </div>
    </div>
</x-guest-layout>
