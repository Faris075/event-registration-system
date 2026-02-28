<section>
    <h2 style="font-size:1.05rem;font-weight:700;color:#be123c;margin-bottom:0.25rem;">Delete Account</h2>
    <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Once your account is deleted, all of its resources and data will be permanently deleted.</p>

    <button
        type="button"
        class="btn btn-danger"
        onclick="document.getElementById('delete-confirm-modal').style.display='flex'"
    >Delete Account</button>

    <div id="delete-confirm-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div class="card" style="max-width:480px;width:100%;margin:1rem;">
            <h3 style="font-size:1rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;">Are you sure you want to delete your account?</h3>
            <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Once deleted, all your data will be permanently removed. Enter your password to confirm.</p>

            <form method="post" action="{{ route('profile.destroy') }}" class="form-grid">
                @csrf
                @method('delete')

                <div class="form-field">
                    <label for="del-password" class="form-label">Password</label>
                    <input id="del-password" name="password" type="password" class="form-input" placeholder="Your password" />
                    @if($errors->userDeletion->has('password'))
                        <p class="form-error">{{ $errors->userDeletion->first('password') }}</p>
                    @endif
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.6rem;">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('delete-confirm-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</section>
