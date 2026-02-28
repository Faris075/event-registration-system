@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:700px;">

    <div class="page-header">
        <div>
            <h1 class="page-title">Create Event</h1>
            <p class="page-subtitle">Fill in the details below to create a new event.</p>
        </div>
        <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm">← Back</a>
    </div>

    <div class="card">
        <form action="{{ route('events.store') }}" method="POST" class="form-grid" id="create-event-form" novalidate>
            @csrf

            <div class="form-field">
                <label class="form-label">Title <span style="color:var(--danger);">*</span></label>
                <input type="text" name="title" id="ev-title" value="{{ old('title') }}" class="form-input" placeholder="Event title" required minlength="2" maxlength="255">
                <span class="form-error" id="ev-title-error" style="display:none;"></span>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input form-textarea" placeholder="Describe the event..." maxlength="5000">{{ old('description') }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Date &amp; Time <span style="color:var(--danger);">*</span></label>
                    <input type="datetime-local" name="date_time" id="ev-date" value="{{ old('date_time') }}" class="form-input" required
                           min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                    <span class="form-error" id="ev-date-error" style="display:none;"></span>
                    @error('date_time') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Location <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="location" id="ev-location" value="{{ old('location') }}" class="form-input" placeholder="Venue or city" required minlength="2" maxlength="255">
                    <span class="form-error" id="ev-location-error" style="display:none;"></span>
                    @error('location') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Capacity <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="capacity" id="ev-capacity" value="{{ old('capacity') }}" class="form-input" placeholder="100" required min="1" max="100000">
                    <span class="form-error" id="ev-capacity-error" style="display:none;"></span>
                    @error('capacity') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Price <span class="text-muted" style="font-weight:400;">(leave blank = free)</span></label>
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="form-input" placeholder="0.00" min="0" max="99999">
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-field">
                <label class="form-label">Status <span style="color:var(--danger);">*</span></label>
                <select name="status" class="form-input form-select" required>
                    <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status') === 'published')>Published</option>
                </select>
                @error('status') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div style="display:flex;gap:0.6rem;padding-top:0.5rem;">
                <button type="submit" class="btn btn-primary">Create Event</button>
                <a href="{{ route('events.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function showE(id, msg) { const el = document.getElementById(id); if (el) { el.textContent = msg; el.style.display = 'block'; } }
    function clearE(id) { const el = document.getElementById(id); if (el) { el.textContent = ''; el.style.display = 'none'; } }

    document.getElementById('ev-title')?.addEventListener('input', function () {
        this.value.trim().length < 2 ? showE('ev-title-error', 'Title must be at least 2 characters.') : clearE('ev-title-error');
    });
    document.getElementById('ev-location')?.addEventListener('input', function () {
        this.value.trim().length < 2 ? showE('ev-location-error', 'Location is required.') : clearE('ev-location-error');
    });
    document.getElementById('ev-capacity')?.addEventListener('input', function () {
        parseInt(this.value) < 1 ? showE('ev-capacity-error', 'Capacity must be at least 1.') : clearE('ev-capacity-error');
    });

    document.getElementById('create-event-form')?.addEventListener('submit', function (e) {
        let valid = true;
        const title = document.getElementById('ev-title');
        if (!title?.value.trim() || title.value.trim().length < 2) { showE('ev-title-error', 'Title is required (at least 2 characters).'); valid = false; }
        const loc = document.getElementById('ev-location');
        if (!loc?.value.trim()) { showE('ev-location-error', 'Location is required.'); valid = false; }
        const cap = document.getElementById('ev-capacity');
        if (!cap?.value || parseInt(cap.value) < 1) { showE('ev-capacity-error', 'Capacity must be at least 1.'); valid = false; }
        const dt = document.getElementById('ev-date');
        if (!dt?.value) { showE('ev-date-error', 'Date & Time is required.'); valid = false; }
        if (!valid) e.preventDefault();
    });
})();
</script>
@endsection
