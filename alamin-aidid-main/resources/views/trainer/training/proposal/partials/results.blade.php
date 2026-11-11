@php
    $proposalStateQuery = [
        'status' => $proposalFilters['status'] !== 'all' ? $proposalFilters['status'] : null,
        'search' => $proposalFilters['search'] ?: null,
        'submitted_period' => $proposalFilters['submitted_period'] !== 'all' ? $proposalFilters['submitted_period'] : null,
        'submitted_from' => $proposalFilters['submitted_from'] ?: null,
        'submitted_to' => $proposalFilters['submitted_to'] ?: null,
        'rows' => $proposalFilters['rows'] !== 10 ? $proposalFilters['rows'] : null,
        'page' => $proposalFilters['page'] > 1 ? $proposalFilters['page'] : null,
        'proposal_id' => $proposalFilters['proposal_id'] > 0 ? $proposalFilters['proposal_id'] : null,
    ];
    $proposalUrl = function (array $changes = [], array $remove = []) use ($proposalStateQuery) {
        $query = array_merge($proposalStateQuery, $changes);
        foreach ($remove as $key) {
            unset($query[$key]);
        }

        return route('trainer.proposals', array_filter($query, fn ($value) => $value !== null && $value !== ''));
    };
    $formatProposalDate = function ($value, bool $withTime = false) {
        if (! $value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format($withTime ? 'd M Y, g:i A' : 'd M Y');
        } catch (\Throwable) {
            return null;
        }
    };
@endphp

<div id="proposalResultsRegion"
     class="pp-results-region"
     data-proposal-page="{{ $proposalFilters['page'] }}"
     data-proposal-selected="{{ $proposalFilters['proposal_id'] }}"
     aria-live="polite">
    <span class="pp-loading-label" role="status">Updating proposals…</span>

    @if ($proposalPaginator->total() === 0)
        <div class="proposal-empty">
            <i class="bi {{ $stats['total'] === 0 ? 'bi-inbox' : 'bi-funnel' }}" aria-hidden="true"></i>
            @if ($stats['total'] === 0)
                <div class="empty-title">No proposals submitted yet.</div>
                <div class="empty-sub">Your submitted training proposals will appear here.</div>
            @else
                <div class="empty-title">No proposals match these filters.</div>
                <div class="empty-sub">Try changing the selected status, period, or search.</div>
            @endif
        </div>
    @else
        <div class="pp-proposal-list" data-compact-proposal-list>
            @foreach ($proposalPaginator as $proposal)
                @php
                    $statusKey = $proposal->status_key ?? 'pending';
                    $statusLabel = $statusKey === 'approved' ? 'Approved' : ($statusKey === 'rejected' ? 'Rejected' : 'Pending');
                    $statusIcon = $statusKey === 'approved' ? 'bi-check-circle-fill' : ($statusKey === 'rejected' ? 'bi-x-circle-fill' : 'bi-clock-fill');
                    $startLabel = $formatProposalDate($proposal->proposed_start_date ?? null);
                    $endLabel = $formatProposalDate($proposal->proposed_end_date ?? null);
                    $trainingDates = $startLabel && $endLabel && $startLabel !== $endLabel
                        ? $startLabel.' – '.$endLabel
                        : ($startLabel ?: ($endLabel ?: 'Not specified'));
                    $isExpanded = $selectedProposal && (int) $selectedProposal->proposal_id === (int) $proposal->proposal_id;
                @endphp

                <article class="pp-proposal-entry {{ $isExpanded ? 'is-expanded' : '' }}" data-proposal-row="{{ $proposal->proposal_id }}">
                    <div class="pp-proposal-summary">
                        <div class="pp-proposal-primary">
                            <h3>{{ $proposal->proposed_title }}</h3>
                            <p>{{ $proposal->delivery_mode ?: ($proposal->training_mode ?: 'Format not set') }}</p>
                        </div>
                        <div class="pp-proposal-dates">
                            <span><strong>Submitted</strong> {{ $formatProposalDate($proposal->created_at ?? null) ?? 'Recently' }}</span>
                            <span><strong>Training</strong> {{ $trainingDates }}</span>
                        </div>
                        <div class="pp-proposal-actions">
                            <span class="pp-badge {{ $statusKey }}"><i class="bi {{ $statusIcon }}" aria-hidden="true"></i>{{ $statusLabel }}</span>
                            <a href="{{ $isExpanded
                                    ? $proposalUrl([], ['proposal_id'])
                                    : $proposalUrl(['proposal_id' => $proposal->proposal_id]) }}"
                               class="pp-detail-toggle"
                               data-proposal-report-link
                               data-proposal-detail="{{ $proposal->proposal_id }}"
                               aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                               aria-controls="proposal-detail-{{ $proposal->proposal_id }}">
                                {{ $isExpanded ? 'Hide details' : 'View details' }}
                                <i class="bi {{ $isExpanded ? 'bi-chevron-up' : 'bi-chevron-down' }}" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    @if ($isExpanded)
                        @include('trainer.training.proposal.partials.detail', ['proposal' => $selectedProposal])
                    @endif
                </article>
            @endforeach
        </div>

        <div class="pp-pager" data-proposal-paginator>
            <label class="pp-rows-control" for="proposalRows">
                <span>Rows</span>
                <select id="proposalRows" data-proposal-rows>
                    @foreach ([10, 25, 50] as $rowOption)
                        <option value="{{ $rowOption }}" @selected($proposalFilters['rows'] === $rowOption)>{{ $rowOption }}</option>
                    @endforeach
                </select>
            </label>

            <span class="pp-pager-info">
                Showing <strong>{{ $proposalPaginator->firstItem() }}–{{ $proposalPaginator->lastItem() }}</strong>
                of <strong>{{ $proposalPaginator->total() }}</strong> proposals
            </span>

            <div class="pp-pager-controls">
                @if ($proposalPaginator->onFirstPage())
                    <button type="button" class="pp-pager-btn" disabled><i class="bi bi-chevron-left" aria-hidden="true"></i> Previous</button>
                @else
                    <a href="{{ $proposalUrl(['page' => $proposalPaginator->currentPage() - 1], ['proposal_id']) }}" class="pp-pager-btn" data-proposal-report-link><i class="bi bi-chevron-left" aria-hidden="true"></i> Previous</a>
                @endif
                <span class="pp-pager-page">{{ $proposalPaginator->currentPage() }} / {{ $proposalPaginator->lastPage() }}</span>
                @if ($proposalPaginator->hasMorePages())
                    <a href="{{ $proposalUrl(['page' => $proposalPaginator->currentPage() + 1], ['proposal_id']) }}" class="pp-pager-btn" data-proposal-report-link>Next <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                @else
                    <button type="button" class="pp-pager-btn" disabled>Next <i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                @endif
            </div>
        </div>
    @endif
</div>
