@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:700px;">

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Event</h1>
            <p class="page-subtitle">Update the event details below.</p>
        </div>
        <a href="{{ route('events.show', $event) }}" class="btn btn-ghost btn-sm">← Back</a>
    </div>

    <div class="card">
        <form action="{{ route('events.update', $event) }}" method="POST" class="form-grid">
            @csrf
            @method('PUT')

            <div class="form-field">
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title', $event->title) }}" class="form-input" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input form-textarea" required>{{ old('description', $event->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Date &amp; Time</label>
                    <input type="datetime-local" name="date_time" value="{{ old('date_time', optional($event->date_time)->format('Y-m-d\TH:i')) }}" class="form-input" required>
                    @error('date_time') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" value="{{ old('location', $event->location) }}" class="form-input" required>
                    @error('location') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $event->capacity) }}" class="form-input" required>
                    @error('capacity') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-field">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" value="{{ old('price', $event->price) }}" class="form-input" placeholder="0.00">
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-field">
                <label class="form-label">Status</label>
                <select name="status" class="form-input form-select" required>
                    <option value="draft" @selected(old('status', $event->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $event->status) === 'published')>Published</option>
                    <option value="cancelled" @selected(old('status', $event->status) === 'cancelled')>Cancelled</option>
                    <option value="completed" @selected(old('status', $event->status) === 'completed')>Completed</option>
                </select>
                @error('status') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div style="display:flex;gap:0.6rem;padding-top:0.5rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('events.show', $event) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
