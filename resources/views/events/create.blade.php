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
        <form action="{{ route('events.store') }}" method="POST" class="form-grid">
            @csrf

            <div class="form-field">
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-input" placeholder="Event title" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input form-textarea" placeholder="Describe the event...">{{ old('description') }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Date &amp; Time</label>
                    <input type="datetime-local" name="date_time" value="{{ old('date_time') }}" class="form-input" required>
                    @error('date_time') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" value="{{ old('location') }}" class="form-input" placeholder="Venue or city" required>
                    @error('location') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" value="{{ old('capacity') }}" class="form-input" placeholder="100" required>
                    @error('capacity') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Price <span class="text-muted" style="font-weight:400;">(leave blank = free)</span></label>
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="form-input" placeholder="0.00">
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-field">
                <label class="form-label">Status</label>
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
@endsection
