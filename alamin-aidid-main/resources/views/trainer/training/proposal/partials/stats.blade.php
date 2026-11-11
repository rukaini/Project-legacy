{{-- Dashboard-standard metric cards --}}
<section class="row g-3 mb-4" aria-label="Proposal summary">
    <div class="col-md-3 col-6">
        <article class="pp-stat-card" data-proposal-metric="total">
            <div class="pp-stat-icon pp-stat-icon-blue" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></div>
            <div class="pp-stat-label">Total Proposals</div>
            <h2 class="pp-stat-value">{{ $stats['total'] }}</h2>
        </article>
    </div>
    <div class="col-md-3 col-6">
        <article class="pp-stat-card" data-proposal-metric="pending">
            <div class="pp-stat-icon pp-stat-icon-amber" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
            <div class="pp-stat-label">Pending Review</div>
            <h2 class="pp-stat-value">{{ $stats['pending'] }}</h2>
        </article>
    </div>
    <div class="col-md-3 col-6">
        <article class="pp-stat-card" data-proposal-metric="approved">
            <div class="pp-stat-icon pp-stat-icon-green" aria-hidden="true"><i class="bi bi-check-circle"></i></div>
            <div class="pp-stat-label">Approved</div>
            <h2 class="pp-stat-value">{{ $stats['approved'] }}</h2>
        </article>
    </div>
    <div class="col-md-3 col-6">
        <article class="pp-stat-card" data-proposal-metric="rejected">
            <div class="pp-stat-icon pp-stat-icon-red" aria-hidden="true"><i class="bi bi-x-circle"></i></div>
            <div class="pp-stat-label">Rejected</div>
            <h2 class="pp-stat-value">{{ $stats['rejected'] }}</h2>
        </article>
    </div>
</section>
