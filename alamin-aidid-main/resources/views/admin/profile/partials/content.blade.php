<main class="adm-wrap adm-wrap--wide app-page-shell" data-admin-page-shell data-admin-width="wide">
    <header class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">My Profile</h1>
                <p class="adm-subtitle">Your administrator account information and recent proposal-review activity.</p>
            </div>
        </div>
    </header>

    <div class="pf-workspace">
        <section class="pf-card pf-identity" aria-labelledby="profile-identity-title">
            <div class="pf-identity-main">
                <h2 class="pf-identity-name" id="profile-identity-title">{{ $staffName }}</h2>
                <p class="pf-identity-email">{{ $staff->staff_email ?: 'Not available' }}</p>
            </div>
        </section>

        <section class="pf-card pf-section" aria-labelledby="review-summary-title">
            <div class="pf-section-head">
                <div>
                    <h2 class="pf-section-title" id="review-summary-title">Review Summary</h2>
                    <p class="pf-section-subtitle">Final proposal decisions recorded under your administrator account.</p>
                </div>
            </div>

            <div class="pf-stats">
                <article class="pf-stat reviewed" data-review-metric="reviewed">
                    <div class="pf-stat-icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></div>
                    <div>
                        <div class="pf-stat-value">{{ $proposalsReviewed }}</div>
                        <div class="pf-stat-label">Proposals Reviewed</div>
                    </div>
                </article>

                <article class="pf-stat approved" data-review-metric="approved">
                    <div class="pf-stat-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></div>
                    <div>
                        <div class="pf-stat-value">{{ $proposalsApproved }}</div>
                        <div class="pf-stat-label">Approved</div>
                    </div>
                </article>

                <article class="pf-stat rejected" data-review-metric="rejected">
                    <div class="pf-stat-icon"><i class="bi bi-x-circle" aria-hidden="true"></i></div>
                    <div>
                        <div class="pf-stat-value">{{ $proposalsRejected }}</div>
                        <div class="pf-stat-label">Rejected</div>
                    </div>
                </article>

                <article class="pf-stat rate" data-review-metric="approval-rate">
                    <div class="pf-stat-icon"><i class="bi bi-percent" aria-hidden="true"></i></div>
                    <div>
                        <div class="pf-stat-value">{{ !is_null($approvalRate) ? $approvalRate.'%' : 'Not available' }}</div>
                        <div class="pf-stat-label">Approval Rate</div>
                    </div>
                </article>
            </div>
        </section>

        <section class="pf-card pf-section pf-account" aria-labelledby="account-details-title">
            <div class="pf-section-head">
                <div>
                    <h2 class="pf-section-title" id="account-details-title">Account Details</h2>
                    <p class="pf-section-subtitle">Your registered administrator and employment information.</p>
                </div>
            </div>

            <dl class="pf-detail-grid">
                <div class="pf-detail-item">
                    <dt class="pf-detail-label"><i class="bi bi-person" aria-hidden="true"></i> Full Name</dt>
                    <dd class="pf-detail-value">{{ $staffName ?: 'Not available' }}</dd>
                </div>

                <div class="pf-detail-item">
                    <dt class="pf-detail-label"><i class="bi bi-envelope" aria-hidden="true"></i> Email Address</dt>
                    <dd class="pf-detail-value">{{ $staff->staff_email ?: 'Not available' }}</dd>
                </div>

                <div class="pf-detail-item">
                    <dt class="pf-detail-label"><i class="bi bi-shield-check" aria-hidden="true"></i> System Role</dt>
                    <dd class="pf-detail-value">{{ $staffRole ?: 'Not available' }}</dd>
                </div>

                <div class="pf-detail-item">
                    <dt class="pf-detail-label"><i class="bi bi-calendar2-check" aria-hidden="true"></i> Hire Date</dt>
                    <dd class="pf-detail-value">{{ $fmtDate($staff->hire_date ?? null) ?? 'Not available' }}</dd>
                </div>

                <div class="pf-detail-item">
                    <dt class="pf-detail-label"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Service Duration</dt>
                    <dd class="pf-detail-value">{{ $staff->service_duration ?: 'Not available' }}</dd>
                </div>

                <div class="pf-detail-item">
                    <dt class="pf-detail-label"><i class="bi bi-cake2" aria-hidden="true"></i> Date of Birth</dt>
                    <dd class="pf-detail-value">
                        {{ $fmtDate($staff->date_of_birth ?? null) ?? 'Not available' }}
                        @if (!is_null($staff->current_age))
                            <span class="pf-detail-hint">Age {{ $staff->current_age }}</span>
                        @endif
                    </dd>
                </div>

                @if (!empty($staff->retirement_date))
                    <div class="pf-detail-item pf-detail-item--retirement">
                        <dt class="pf-detail-label"><i class="bi bi-calendar2-x" aria-hidden="true"></i> Retirement Date</dt>
                        <dd class="pf-detail-value">
                            {{ $fmtDate($staff->retirement_date) ?? 'Not available' }}
                            @if (!is_null($daysToRetirement))
                                <span class="pf-detail-hint">{{ number_format($daysToRetirement) }} days remaining</span>
                            @endif
                        </dd>
                    </div>
                @endif

                @if (isset($staff->total_credit_hours))
                    <div class="pf-detail-item pf-detail-item--credit {{ empty($staff->retirement_date) ? 'full-row' : '' }}">
                        <dt class="pf-detail-label"><i class="bi bi-award" aria-hidden="true"></i> Total Credit Hours</dt>
                        <dd class="pf-detail-value">
                            {{ is_numeric($staff->total_credit_hours) ? number_format((float) $staff->total_credit_hours, 0) : 'Not available' }}
                            @if (is_numeric($staff->total_credit_hours)) hours @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="pf-card pf-section pf-activity" aria-labelledby="recent-activity-title">
            <div class="pf-section-head">
                <div>
                    <h2 class="pf-section-title" id="recent-activity-title">Recent Review Activity</h2>
                    <p class="pf-section-subtitle">Your last 10 proposal reviews, newest first.</p>
                </div>
            </div>

            <div class="pf-activity-feed">
                @forelse ($recentReviews as $review)
                    @php
                        $isApproved = strtolower(trim((string) $review->status)) === 'approved';
                        $outcome = $isApproved ? 'Approved' : 'Rejected';
                        $reviewTime = $fmtReviewTime($review->reviewed_at ?? null);
                    @endphp
                    <article class="pf-activity-row">
                        <div class="pf-outcome-icon {{ $isApproved ? 'approved' : 'rejected' }}" aria-hidden="true">
                            <i class="bi {{ $isApproved ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                        </div>
                        <div class="pf-activity-main">
                            <div class="pf-activity-heading">
                                <span class="pf-outcome-badge {{ $isApproved ? 'approved' : 'rejected' }}">{{ $outcome }}</span>
                                <span class="visually-hidden">proposal</span>
                            </div>
                            <h3 class="pf-activity-title">{{ $review->proposed_title ?: 'Untitled proposal' }}</h3>
                            <p class="pf-activity-meta">Proposed by {{ $review->trainer_name ?: 'Unknown trainer' }}</p>
                        </div>
                        <div class="pf-activity-time">
                            @if ($reviewTime)
                                <time datetime="{{ $reviewTime->toIso8601String() }}" title="{{ $reviewTime->format('d M Y, g:i A') }}">
                                    <span>{{ $reviewTime->diffForHumans() }}</span>
                                    <small>{{ $reviewTime->format('d M Y') }}</small>
                                </time>
                            @else
                                <span>Not available</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="pf-empty" role="status">
                        <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                        <div class="pf-empty-title">No proposal reviews yet</div>
                        <div class="pf-empty-sub">Approved or rejected proposals will appear here after your first review.</div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>
