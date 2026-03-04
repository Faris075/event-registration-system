<section>
    <h2 style="font-size:1.05rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem;">Profile Information</h2>
    <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Update your account's display name.</p>

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
            <input id="email" type="email" class="form-input" value="{{ $user->email }}" disabled
                   style="opacity:0.6;cursor:not-allowed;background:var(--surface,#f9fafb);" />
            <p style="font-size:0.78rem;color:var(--muted);margin-top:0.3rem;">Email cannot be changed.</p>
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn btn-primary">Save</button>
            @if(session('status') === 'profile-updated')
                <span style="font-size:0.875rem;color:var(--muted);">Saved.</span>
            @endif
        </div>
    </form>
</section>
