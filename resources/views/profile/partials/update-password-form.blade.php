<section>
    <h2 style="font-size:1.05rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem;">Update Password</h2>
    <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Ensure your account is using a long, random password to stay secure.</p>

    <form method="post" action="{{ route('password.update') }}" class="form-grid">
        @csrf
        @method('put')

        <div class="form-field">
            <label for="update_password_current_password" class="form-label">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-input" autocomplete="current-password" />
            @if($errors->updatePassword->has('current_password'))
                <p class="form-error">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div class="form-field">
            <label for="update_password_password" class="form-label">New Password</label>
            <input id="update_password_password" name="password" type="password" class="form-input" autocomplete="new-password" />
            @if($errors->updatePassword->has('password'))
                <p class="form-error">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div class="form-field">
            <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password" />
            @if($errors->updatePassword->has('password_confirmation'))
                <p class="form-error">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn btn-primary">Save</button>
            @if(session('status') === 'password-updated')
                <span style="font-size:0.875rem;color:var(--muted);">Saved.</span>
            @endif
        </div>
    </form>
</section>
