<section>
    <h2 style="font-size:1.05rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem;">Display Currency</h2>
    <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Event prices will be shown in your chosen currency. Rates are indicative only.</p>

    <form method="POST" action="{{ route('profile.currency.update') }}" class="form-grid">
        @csrf
        @method('PATCH')

        <div class="form-field" style="max-width:320px;">
            <label for="currency" class="form-label">Currency</label>
            <select id="currency" name="currency" class="form-input form-select" required>
                @foreach($currencies as $code => $info)
                    <option value="{{ $code }}" {{ ($user->currency ?? 'USD') === $code ? 'selected' : '' }}>
                        {{ $info['symbol'] }} — {{ $info['name'] }} ({{ $code }})
                    </option>
                @endforeach
            </select>
            @error('currency') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn btn-primary">Save</button>
            @if(session('status') === 'currency-updated')
                <span style="font-size:0.875rem;color:var(--muted);">Saved.</span>
            @endif
        </div>
    </form>
</section>
