@php
    $showProposalFilters = $stats['total'] > 0
        || $proposalFilters['search'] !== ''
        || $nonSearchFiltersActive;
    $statusUrl = function (string $status) use ($proposalFilters) {
        $query = [
            'status' => $status !== 'all' ? $status : null,
            'search' => $proposalFilters['search'] ?: null,
            'submitted_period' => $proposalFilters['submitted_period'] !== 'all' ? $proposalFilters['submitted_period'] : null,
            'submitted_from' => $proposalFilters['submitted_from'] ?: null,
            'submitted_to' => $proposalFilters['submitted_to'] ?: null,
            'rows' => $proposalFilters['rows'] !== 10 ? $proposalFilters['rows'] : null,
        ];

        return route('trainer.proposals', array_filter($query, fn ($value) => $value !== null));
    };
@endphp

<section class="pp-card pp-proposals-panel" aria-labelledby="my-proposals-title">
    <div class="pp-card-header">
        <div class="header-mark" aria-hidden="true"><i class="bi bi-collection"></i></div>
        <div>
            <h2 class="pp-card-title" id="my-proposals-title">My Proposals</h2>
            <div class="pp-card-sub">Review your submitted proposals and their review status.</div>
        </div>
    </div>

    @if ($showProposalFilters)
        <div class="pp-report-controls" id="proposalReportControls">
            <nav class="pp-status-tabs" aria-label="Proposal status">
                @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $statusKey => $statusLabel)
                    <a href="{{ $statusUrl($statusKey) }}"
                       class="pp-status-tab {{ $proposalFilters['status'] === $statusKey ? 'active' : '' }}"
                       data-proposal-status="{{ $statusKey }}"
                       @if ($proposalFilters['status'] === $statusKey) aria-current="page" @endif>
                        <span>{{ $statusLabel }}</span>
                        <span class="pp-status-count">{{ $statusKey === 'all' ? $stats['total'] : $stats[$statusKey] }}</span>
                    </a>
                @endforeach
            </nav>

            <form id="proposalReportFilters" method="GET" action="{{ route('trainer.proposals') }}" class="pp-filter-toolbar" aria-label="Proposal history filters">
                <input type="hidden" name="status" value="{{ $proposalFilters['status'] }}">

                <div class="pp-filter-field pp-search-field">
                    <label for="proposalSearch">Search Proposals</label>
                    <div class="pp-search-control">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="proposalSearch"
                               type="search"
                               name="search"
                               value="{{ $proposalFilters['search'] }}"
                               placeholder="Search proposals..."
                               autocomplete="off">
                        <button type="button"
                                class="pp-search-clear"
                                data-proposal-search-clear
                                aria-label="Clear proposal search"
                                @if ($proposalFilters['search'] === '') hidden @endif>
                            <i class="bi bi-x" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="pp-filter-field">
                    <label for="proposalSubmittedPeriod">Submitted Period</label>
                    <select id="proposalSubmittedPeriod" name="submitted_period">
                        <option value="all" @selected($proposalFilters['submitted_period'] === 'all')>All time</option>
                        <option value="30" @selected($proposalFilters['submitted_period'] === '30')>Last 30 days</option>
                        <option value="90" @selected($proposalFilters['submitted_period'] === '90')>Last 3 months</option>
                        <option value="365" @selected($proposalFilters['submitted_period'] === '365')>Last year</option>
                        <option value="custom" @selected($proposalFilters['submitted_period'] === 'custom')>Custom</option>
                    </select>
                </div>

                <div class="pp-custom-dates" data-proposal-custom-dates @if ($proposalFilters['submitted_period'] !== 'custom') hidden @endif>
                    <div class="pp-filter-field">
                        <label for="proposalSubmittedFrom">From</label>
                        <input id="proposalSubmittedFrom" type="date" name="submitted_from" value="{{ $proposalFilters['submitted_from'] }}">
                    </div>
                    <div class="pp-filter-field">
                        <label for="proposalSubmittedTo">To</label>
                        <input id="proposalSubmittedTo" type="date" name="submitted_to" value="{{ $proposalFilters['submitted_to'] }}">
                    </div>
                </div>

                <button type="button"
                        class="pp-reset-link"
                        data-proposal-reset
                        @if (! $nonSearchFiltersActive) hidden @endif>
                    Reset Filters
                </button>
            </form>
        </div>
    @endif

    @include('trainer.training.proposal.partials.results')
</section>
