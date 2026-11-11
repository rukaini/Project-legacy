<section class="pf-profile-section" aria-labelledby="trainer-summary-title">
    <div class="pf-section-head">
        <div>
            <h2 class="pf-section-title" id="trainer-summary-title">Trainer Summary</h2>
            <p class="pf-section-subtitle">Current training assignments, proposals, credentials, and feedback.</p>
        </div>
    </div>

    <div class="pf-stats">
        <article class="pf-stat trainings" data-trainer-metric="trainings">
            <div class="pf-stat-icon"><i class="bi bi-journal-check" aria-hidden="true"></i></div>
            <div>
                <div class="pf-stat-value">{{ $assignedTrainingCount }}</div>
                <div class="pf-stat-label">Trainings</div>
            </div>
        </article>

        <article class="pf-stat proposals" data-trainer-metric="proposals">
            <div class="pf-stat-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div>
            <div>
                <div class="pf-stat-value">{{ $proposalCount }}</div>
                <div class="pf-stat-label">Proposals</div>
            </div>
        </article>

        <article class="pf-stat certificates" data-trainer-metric="certificates">
            <div class="pf-stat-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></div>
            <div>
                <div class="pf-stat-value">{{ $certificates->count() }}</div>
                <div class="pf-stat-label">Certificates</div>
            </div>
        </article>

        <article class="pf-stat rating" data-trainer-metric="feedback-rating">
            <div class="pf-stat-icon"><i class="bi bi-star" aria-hidden="true"></i></div>
            <div>
                <div class="pf-stat-value {{ $rating === null || $rating <= 0 ? 'muted' : '' }}">
                    @if ($rating !== null && $rating > 0)
                        {{ number_format($rating, 1) }}<span class="pf-stat-unit"> / 5.0</span>
                    @else
                        Not rated
                    @endif
                </div>
                <div class="pf-stat-label">Feedback Rating</div>
            </div>
        </article>
    </div>
</section>
