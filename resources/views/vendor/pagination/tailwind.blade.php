@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pagination-nav">

        {{-- Mobile: just prev / next --}}
        <div class="pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm pagination-disabled">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-ghost btn-sm">{!! __('pagination.previous') !!}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-ghost btn-sm">{!! __('pagination.next') !!}</a>
            @else
                <span class="btn btn-ghost btn-sm pagination-disabled">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        {{-- Desktop: results count + full page controls --}}
        <div class="pagination-desktop">

            <p class="pagination-info">
                Showing
                @if ($paginator->firstItem())
                    <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong>
                @else
                    {{ $paginator->count() }}
                @endif
                of <strong>{{ $paginator->total() }}</strong> results
            </p>

            <div class="pagination-controls">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="btn btn-ghost btn-sm pagination-disabled" aria-disabled="true">&#8592; Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-ghost btn-sm" aria-label="{{ __('pagination.previous') }}">&#8592; Previous</a>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="pagination-ellipsis">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="btn btn-primary btn-sm pagination-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-ghost btn-sm" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-ghost btn-sm" aria-label="{{ __('pagination.next') }}">Next &#8594;</a>
                @else
                    <span class="btn btn-ghost btn-sm pagination-disabled" aria-disabled="true">Next &#8594;</span>
                @endif

            </div>
        </div>
    </nav>
@endif
