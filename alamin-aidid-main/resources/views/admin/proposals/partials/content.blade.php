<main class="adm-wrap adm-wrap--wide app-page-shell" data-admin-page-shell data-admin-width="wide">
    <div class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">Proposal Review</h1>
                <p class="adm-subtitle">Review training proposals submitted by trainers. Pending proposals are shown first for approval or rejection with feedback.</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="adm-alert error" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><div>{{ $errors->first() }}</div></div>
    @endif

    @if ($proposals->isEmpty())
        <section class="adm-proposals-panel" aria-labelledby="admin-proposals-title">
            <div class="adm-proposals-panel-head">
                <span class="adm-proposals-panel-mark" aria-hidden="true"><i class="bi bi-collection"></i></span>
                <div>
                    <h2 id="admin-proposals-title">Trainer Proposals</h2>
                    <p>Review submitted training proposals and record an administrative decision.</p>
                </div>
            </div>
            <div class="adm-empty">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <div class="t">No proposals submitted yet</div>
                <div class="s">Training proposals will appear here when trainers submit them for review.</div>
            </div>
        </section>
    @else
        <section class="adm-proposal-stats" aria-label="Proposal summary">
            <article class="adm-stat" data-proposal-metric="total">
                <div class="adm-stat-icon total" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></div>
                <div class="adm-stat-label">Total Proposals</div>
                <div class="adm-stat-value">{{ $stats['total'] }}</div>
            </article>
            <article class="adm-stat" data-proposal-metric="pending">
                <div class="adm-stat-icon pending" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
                <div class="adm-stat-label">Pending Review</div>
                <div class="adm-stat-value">{{ $stats['pending'] }}</div>
            </article>
            <article class="adm-stat" data-proposal-metric="approved">
                <div class="adm-stat-icon approved" aria-hidden="true"><i class="bi bi-check-circle"></i></div>
                <div class="adm-stat-label">Approved</div>
                <div class="adm-stat-value">{{ $stats['approved'] }}</div>
            </article>
            <article class="adm-stat" data-proposal-metric="rejected">
                <div class="adm-stat-icon rejected" aria-hidden="true"><i class="bi bi-x-circle"></i></div>
                <div class="adm-stat-label">Rejected</div>
                <div class="adm-stat-value">{{ $stats['rejected'] }}</div>
            </article>
        </section>

        <section class="adm-proposals-panel" aria-labelledby="admin-proposals-title">
            <div class="adm-proposals-panel-head">
                <span class="adm-proposals-panel-mark" aria-hidden="true"><i class="bi bi-collection"></i></span>
                <div>
                    <h2 id="admin-proposals-title">Trainer Proposals</h2>
                    <p>Review submitted training proposals and record an administrative decision.</p>
                </div>
            </div>

            <div class="adm-report-controls">
                <nav class="adm-status-tabs" aria-label="Proposal status">
                    @foreach (['all' => ['All', $stats['total']], 'pending' => ['Pending', $stats['pending']], 'approved' => ['Approved', $stats['approved']], 'rejected' => ['Rejected', $stats['rejected']]] as $filterKey => [$filterLabel, $filterCount])
                        <button type="button"
                                class="adm-filter-btn {{ $filterKey === 'pending' ? 'active' : '' }}"
                                data-filter="{{ $filterKey }}"
                                aria-pressed="{{ $filterKey === 'pending' ? 'true' : 'false' }}">
                            <span>{{ $filterLabel }}</span>
                            <span class="adm-status-count">{{ $filterCount }}</span>
                        </button>
                    @endforeach
                </nav>

                <div class="adm-filter-toolbar" role="group" aria-label="Proposal list controls">
                    <div class="adm-filter-field adm-search-field">
                        <label for="admSearch">Search Proposals</label>
                        <div class="adm-search-control">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search"
                                   id="admSearch"
                                   placeholder="Search title or trainer..."
                                   autocomplete="off">
                            <button type="button"
                                    class="adm-search-clear"
                                    data-admin-proposal-search-clear
                                    aria-label="Clear proposal search"
                                    hidden>
                                <i class="bi bi-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="adm-filter-field adm-sort-field">
                        <label for="admSort">Sort By</label>
                        <select id="admSort" class="adm-sort">
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                            <option value="trainer">Trainer (A–Z)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="adm-list-anchor"></div>
            <div id="admProposalList" class="adm-proposal-list">
                @foreach ($proposals as $p)
                    @php
                        $statusKey = $p->status_key ?? 'pending';
                        $badgeIcon = $statusKey === 'approved' ? 'bi-check-circle-fill'
                            : ($statusKey === 'rejected' ? 'bi-x-circle-fill' : 'bi-clock-fill');
                        $badgeLabel = $statusKey === 'approved' ? 'Approved'
                            : ($statusKey === 'rejected' ? 'Rejected' : 'Pending');

                        $start = $p->proposed_start_date ? \Carbon\Carbon::parse($p->proposed_start_date)->format('d M Y') : null;
                        $end = $p->proposed_end_date ? \Carbon\Carbon::parse($p->proposed_end_date)->format('d M Y') : null;
                        if ($start && $end) { $dateText = $start.' – '.$end; }
                        elseif ($start) { $dateText = $start; }
                        else { $dateText = 'Not specified'; }

                        $created = $p->created_at ? \Carbon\Carbon::parse($p->created_at) : null;
                        $waitDays = ($created && $statusKey === 'pending')
                            ? max(0, (int) $created->diffInDays(\Carbon\Carbon::now()))
                            : null;
                        if ($waitDays === null) { $waitClass = ''; $waitText = ''; }
                        elseif ($waitDays >= 14) { $waitClass = 'urgent'; $waitText = $waitDays.'d waiting'; }
                        elseif ($waitDays >= 7) { $waitClass = 'warn'; $waitText = $waitDays.'d waiting'; }
                        else { $waitClass = 'fresh'; $waitText = $waitDays === 0 ? 'Today' : $waitDays.'d waiting'; }

                        $sortCreated = $created ? $created->timestamp : 0;
                        $sortTrainer = strtolower($p->trainer_name ?? 'zzz');
                        $detailsExpanded = $statusKey === 'pending';
                    @endphp

                    <article class="adm-prop-card"
                             data-status="{{ $statusKey }}"
                             data-created="{{ $sortCreated }}"
                             data-trainer="{{ $sortTrainer }}"
                             data-search="{{ $p->proposed_title }} {{ $p->trainer_name }}">
                        <div class="adm-prop-head">
                            <div class="adm-prop-primary">
                                <h3>{{ $p->proposed_title }}</h3>
                                <p>
                                    <span><i class="bi bi-person" aria-hidden="true"></i>{{ $p->trainer_name ?? 'Unknown trainer' }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ $p->delivery_mode ?: ($p->training_mode ?: 'Format not set') }}</span>
                                </p>
                            </div>

                            <div class="adm-prop-dates">
                                <span><strong>Submitted</strong>{{ $created ? $created->format('d M Y') : 'Recently' }}</span>
                                <span><strong>Training</strong>{{ $dateText }}</span>
                            </div>

                            <div class="adm-head-side">
                                <span class="adm-badge {{ $statusKey }}"><i class="bi {{ $badgeIcon }}" aria-hidden="true"></i>{{ $badgeLabel }}</span>
                                @if ($statusKey === 'pending' && $waitText !== '')
                                    <span class="adm-wait {{ $waitClass }}"><i class="bi bi-clock-history" aria-hidden="true"></i>{{ $waitText }}</span>
                                @endif
                                @if ($statusKey === 'approved' || $statusKey === 'rejected')
                                    <button type="button"
                                            class="adm-toggle"
                                            data-toggle-body
                                            aria-expanded="false"
                                            aria-controls="proposal-detail-{{ $p->proposal_id }}">
                                        <span data-toggle-label>View details</span>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div id="proposal-detail-{{ $p->proposal_id }}"
                             class="adm-body {{ $detailsExpanded ? '' : 'collapsed' }}"
                             data-body>
                            <div class="adm-meta-grid">
                                <div><div class="adm-meta-label">Proposed Date</div><div class="adm-meta-value">{{ $dateText }}</div></div>
                                <div><div class="adm-meta-label">Capacity</div><div class="adm-meta-value">{{ $p->estimated_capacity ? $p->estimated_capacity.' participants' : 'Not set' }}</div></div>
                                <div><div class="adm-meta-label">Format</div><div class="adm-meta-value">{{ $p->training_mode ?: 'Not set' }}</div></div>
                                <div><div class="adm-meta-label">Delivery</div><div class="adm-meta-value">{{ $p->delivery_mode ?: 'Not set' }}</div></div>
                            </div>

                            @if (! empty($p->target_audience_list))
                                <div class="adm-pills">
                                    @foreach ($p->target_audience_list as $audience)
                                        <span class="adm-pill"><i class="bi bi-people" aria-hidden="true"></i>{{ \App\Support\ParticipantTypeLabel::display($audience) }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if (! empty($p->objective))
                                <section class="adm-block">
                                    <h4 class="adm-block-label">Training Objective</h4>
                                    <div class="adm-block-text">{{ $p->objective }}</div>
                                </section>
                            @endif

                            @if (! empty($p->description))
                                <section class="adm-block">
                                    <h4 class="adm-block-label">Description / Justification</h4>
                                    <div class="adm-block-text">{{ $p->description }}</div>
                                </section>
                            @endif

                            @if (! empty($p->document_url))
                                <a href="{{ $p->document_url }}" target="_blank" rel="noopener" class="adm-doc">
                                    <i class="bi bi-paperclip" aria-hidden="true"></i>{{ $p->document_name ?: 'View attached document' }}
                                </a>
                            @endif
                        </div>

                        @if ($statusKey === 'approved' || $statusKey === 'rejected')
                            <div class="adm-comment {{ $statusKey }}">
                                <div class="adm-comment-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i>Review Feedback</div>
                                <div class="adm-comment-text">
                                    @if (! empty($p->admin_comment))
                                        {{ $p->admin_comment }}
                                    @elseif ($statusKey === 'approved')
                                        Approved. No comment was added.
                                    @else
                                        Rejected. No comment was added.
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="adm-prop-foot">
                            <span class="adm-submitted">
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                Submitted {{ $created ? $created->format('d M Y') : 'recently' }}
                                @if (! empty($p->reviewed_at))
                                    · Reviewed {{ \Carbon\Carbon::parse($p->reviewed_at)->format('d M Y') }}
                                @endif
                            </span>

                            @if ($statusKey === 'pending')
                                <div class="adm-actions">
                                    <button type="button"
                                            class="adm-act-approve"
                                            data-action="approve"
                                            data-url="{{ route('admin.proposals.approve', $p->proposal_id) }}"
                                            data-title="{{ $p->proposed_title }}">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i>Approve
                                    </button>
                                    <button type="button"
                                            class="adm-act-reject"
                                            data-action="reject"
                                            data-url="{{ route('admin.proposals.reject', $p->proposal_id) }}"
                                            data-title="{{ $p->proposed_title }}">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>Reject
                                    </button>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div id="admFilterEmpty" class="adm-empty" role="status">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <div class="t">No proposals match these controls</div>
                <div class="s">Try another status, sort order, or search term.</div>
            </div>

            <div id="admPager" class="adm-pager" data-admin-proposal-paginator>
                <label class="adm-rows-control" for="admRows">
                    <span>Rows</span>
                    <select id="admRows" data-admin-proposal-rows>
                        @foreach ([10, 25, 50] as $rowOption)
                            <option value="{{ $rowOption }}">{{ $rowOption }}</option>
                        @endforeach
                    </select>
                </label>
                <span class="adm-page-info" id="admCount" aria-live="polite"></span>
                <div class="adm-pager-controls" id="admPagerControls"></div>
            </div>
        </section>
    @endif
</main>

<div class="modal fade adm-review-modal" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="reviewForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <div class="adm-modal-eyebrow">Administrative review</div>
                        <h2 class="modal-title" id="reviewModalTitle">Review proposal</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close review"></button>
                </div>
                <div class="modal-body">
                    <div class="adm-modal-prop" id="reviewModalProp"></div>
                    <label class="adm-label" id="reviewCommentLabel" for="reviewComment">Comment</label>
                    <textarea class="adm-textarea" id="reviewComment" name="admin_comment" rows="4"></textarea>
                    <div class="adm-modal-hint" id="reviewCommentHint"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn" id="reviewSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
