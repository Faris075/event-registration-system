<section>
    <h2 style="font-size:1.05rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem;">Profile Information</h2>
    <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Update your account's profile information and email address.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="form-grid">
        @csrf
        @method('patch')

        <div class="form-field">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')<p class="form-error">{{ $message }}</p>@enderror

            @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <p style="font-size:0.85rem;margin-top:0.5rem;color:var(--muted);">
                    Your email address is unverified.
                    <button form="send-verification" style="background:none;border:none;padding:0;color:var(--accent);cursor:pointer;text-decoration:underline;">Re-send verification email.</button>
                </p>
                @if(session('status') === 'verification-link-sent')
                    <p class="alert alert-success" style="margin-top:0.5rem;">A new verification link has been sent.</p>
                @endif
            @endif
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn btn-primary">Save</button>
            @if(session('status') === 'profile-updated')
                <span style="font-size:0.875rem;color:var(--muted);">Saved.</span>
            @endif
        </div>
    </form>
</section>
