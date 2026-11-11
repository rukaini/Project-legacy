@if ($paginator->total() > 0)
    <footer class="lt-pagination" data-tracking-pagination>
        <label class="lt-rows-control">Rows
            <select data-tracking-rows data-tracking-regions="{{ $trackingRegions ?? 'main' }}">
                @foreach ([10, 25, 50] as $size)<option value="{{ $size }}" {{ $rows === $size ? 'selected' : '' }}>{{ $size }}</option>@endforeach
            </select>
        </label>
        <span class="lt-pager-info">Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong></span>
        <div class="lt-pager-controls">
            @php $previous = $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null; $next = $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null; @endphp
            @if ($previous)<a class="lt-pager-btn" href="{{ $trackingQuery([$pageName => $previous]) }}" data-tracking-link data-tracking-regions="{{ $trackingRegions ?? 'main' }}"><i class="bi bi-chevron-left"></i> Previous</a>@else<button type="button" class="lt-pager-btn" disabled><i class="bi bi-chevron-left"></i> Previous</button>@endif
            <span class="lt-pager-page">{{ $paginator->currentPage() }} / {{ max(1, $paginator->lastPage()) }}</span>
            @if ($next)<a class="lt-pager-btn" href="{{ $trackingQuery([$pageName => $next]) }}" data-tracking-link data-tracking-regions="{{ $trackingRegions ?? 'main' }}">Next <i class="bi bi-chevron-right"></i></a>@else<button type="button" class="lt-pager-btn" disabled>Next <i class="bi bi-chevron-right"></i></button>@endif
        </div>
    </footer>
@endif
