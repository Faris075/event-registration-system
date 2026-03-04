@extends('layouts.app')

@section('content')
<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Events</h1>
            <p class="page-subtitle">Browse upcoming events and register in seconds.</p>
        </div>
        @auth
            @if(auth()->user()->is_admin)
                <a href="{{ route('events.create') }}" class="btn btn-primary">+ Create Event</a>
            @endif
        @endauth
    </div>

    {{-- Search & Filter bar --}}
    <form method="GET" action="{{ route('events.index') }}" id="events-filter-form"
          style="display:flex;flex-wrap:wrap;gap:0.6rem;align-items:flex-end;margin-bottom:1.25rem;padding:1rem;background:var(--surface);border:1px solid var(--border);border-radius:0.6rem;">

        <div style="flex:1;min-width:180px;">
            <label style="font-size:0.8rem;color:var(--muted);display:block;margin-bottom:0.25rem;">Search</label>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Title or location…"
                   class="form-input" style="width:100%;padding:0.45rem 0.7rem;">
        </div>

        <div style="min-width:140px;">
            <label style="font-size:0.8rem;color:var(--muted);display:block;margin-bottom:0.25rem;">From</label>
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="form-input" style="padding:0.45rem 0.7rem;">
        </div>

        <div style="min-width:140px;">
            <label style="font-size:0.8rem;color:var(--muted);display:block;margin-bottom:0.25rem;">To</label>
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="form-input" style="padding:0.45rem 0.7rem;">
        </div>

        @auth
            @if(auth()->user()->is_admin)
            <div style="min-width:140px;">
                <label style="font-size:0.8rem;color:var(--muted);display:block;margin-bottom:0.25rem;">Status</label>
                <select name="status" class="form-input" style="padding:0.45rem 0.7rem;">
                    <option value="">All statuses</option>
                    @foreach(['published','draft','cancelled','completed'] as $s)
                        <option value="{{ $s }}" {{ ($statusFilter ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        @endauth

        <div style="display:flex;gap:0.4rem;align-self:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(($search ?? '') || ($dateFrom ?? '') || ($dateTo ?? '') || ($statusFilter ?? ''))
                <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm">Clear</a>
            @endif
        </div>
    </form>

    @if($events->isEmpty() && ($events->currentPage() === 1))
        <div class="empty-state">No events match your search.</div>
    @else
        <div class="event-grid" id="events-grid">
            @include('events.partials.cards', ['events' => $events, 'bookedEventIds' => $bookedEventIds, 'waitlistedEventIds' => $waitlistedEventIds, 'isAdmin' => $isAdmin])
        </div>

        {{-- Infinite scroll sentinel --}}
        <div id="scroll-sentinel" style="height:1px;margin-top:1.5rem;"></div>
        <div id="scroll-loader" style="display:none;text-align:center;padding:1rem;color:var(--muted);font-size:0.9rem;">
            Loading more events…
        </div>
        <div id="scroll-end" style="display:none;text-align:center;padding:1rem;color:var(--muted);font-size:0.85rem;">
            — No more events —
        </div>
    @endif

</div>

<script>
(function () {
    let nextPage  = {{ $events->currentPage() + 1 }};
    let hasMore   = {{ $events->hasMorePages() ? 'true' : 'false' }};
    let loading   = false;

    const grid     = document.getElementById('events-grid');
    const sentinel = document.getElementById('scroll-sentinel');
    const loader   = document.getElementById('scroll-loader');
    const endMsg   = document.getElementById('scroll-end');

    if (!grid || !sentinel || !hasMore) {
        if (endMsg && !hasMore && grid) endMsg.style.display = 'block';
        return;
    }

    const baseUrl   = '{{ route('events.index') }}';
    const params    = new URLSearchParams(window.location.search);

    function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        loader.style.display = 'block';

        params.set('page', nextPage);

        fetch(baseUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            loader.style.display = 'none';
            if (data.html) {
                const tmp = document.createElement('div');
                tmp.innerHTML = data.html;
                while (tmp.firstChild) grid.appendChild(tmp.firstChild);
            }
            hasMore  = data.hasMore;
            nextPage = data.nextPage;
            loading  = false;
            if (!hasMore) endMsg.style.display = 'block';
        })
        .catch(() => { loader.style.display = 'none'; loading = false; });
    }

    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '200px' });

    observer.observe(sentinel);
})();
</script>
@endsection
