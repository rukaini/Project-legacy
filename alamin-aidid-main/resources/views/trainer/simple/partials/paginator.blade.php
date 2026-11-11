@if ($paginator->total() > 0)
    @php
        $queryFor = function (int $page) use ($state) {
            return array_filter(array_merge($state, ['page' => $page]), fn ($value) => $value !== '' && $value !== null);
        };
    @endphp
    <div class="sw-pager" data-simple-paginator>
        <label class="sw-rows-control"><span>Rows</span><select data-simple-rows aria-label="Rows per page">
            @foreach ([10, 25, 50] as $option)
                <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }}</option>
            @endforeach
        </select></label>
        <span class="sw-pager-info">Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> {{ $noun }}</span>
        <div class="sw-pager-controls">
            @if ($paginator->onFirstPage())
                <button type="button" class="sw-pager-btn" disabled><i class="bi bi-chevron-left"></i> Previous</button>
            @else
                <a class="sw-pager-btn" href="{{ $reportPath.'?'.http_build_query($queryFor($paginator->currentPage() - 1)) }}" data-simple-report-link><i class="bi bi-chevron-left"></i> Previous</a>
            @endif
            <span class="sw-pager-page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a class="sw-pager-btn" href="{{ $reportPath.'?'.http_build_query($queryFor($paginator->currentPage() + 1)) }}" data-simple-report-link>Next <i class="bi bi-chevron-right"></i></a>
            @else
                <button type="button" class="sw-pager-btn" disabled>Next <i class="bi bi-chevron-right"></i></button>
            @endif
        </div>
    </div>
@endif
