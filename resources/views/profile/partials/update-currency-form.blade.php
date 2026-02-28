<section>
    <h2 style="font-size:1.05rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem;">Display Currency</h2>
    <p style="font-size:0.875rem;color:var(--muted);margin-bottom:1.25rem;">Event prices are stored in USD and converted at the indicative rates shown below.</p>

    <form method="POST" action="{{ route('profile.currency.update') }}" class="form-grid">
        @csrf
        @method('PATCH')

        <div class="form-field" style="max-width:360px;">
            <label for="currency" class="form-label">Currency</label>
            <select id="currency" name="currency" class="form-input form-select" required onchange="updateRatePreview(this.value)">
                @foreach($currencies as $code => $info)
                    <option value="{{ $code }}"
                            data-rate="{{ $info['rate'] }}"
                            data-symbol="{{ $info['symbol'] }}"
                            {{ ($user->currency ?? 'USD') === $code ? 'selected' : '' }}>
                        {{ $info['symbol'] }} — {{ $info['name'] }} ({{ $code }})
                        @if($info['rate'] != 1)
                            · 1 USD = {{ $info['rate'] }} {{ $code }}
                        @endif
                    </option>
                @endforeach
            </select>
            @error('currency') <p class="form-error">{{ $message }}</p> @enderror

            {{-- Live rate preview --}}
            <p id="rate-preview" style="margin:0.5rem 0 0;font-size:0.82rem;color:var(--muted);"></p>
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="submit" class="btn btn-primary">Save</button>
            @if(session('status') === 'currency-updated')
                <span style="font-size:0.875rem;color:var(--success);">✓ Saved.</span>
            @endif
        </div>
    </form>
</section>

<script>
function updateRatePreview(selectedCode) {
    const select  = document.getElementById('currency');
    const preview = document.getElementById('rate-preview');
    if (!select || !preview) return;

    const opt    = select.querySelector('option[value="' + selectedCode + '"]');
    const rate   = parseFloat(opt?.dataset.rate ?? 1);
    const symbol = opt?.dataset.symbol ?? '';

    if (rate === 1) {
        preview.textContent = 'Prices shown in their base USD amount.';
    } else {
        preview.textContent = 'Exchange rate: 1 USD = ' + symbol + rate.toFixed(2) + ' ' + selectedCode + ' (indicative)';
    }
}

// Initialise on page load with the currently selected currency.
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('currency');
    if (sel) updateRatePreview(sel.value);
});
</script>
