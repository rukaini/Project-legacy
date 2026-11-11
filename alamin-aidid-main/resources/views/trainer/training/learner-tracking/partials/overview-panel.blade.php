@php $metrics = $overview['metrics']; $events = $overview['events']; @endphp

@if (! $selectedParticipant)
    <div class="lt-empty"><i class="bi bi-people"></i><h2>No participants are enrolled in this training</h2><p>Participants will appear here when they are added to the course enrollment source.</p></div>
@else
    <section class="lt-card lt-person-head">
        <span class="lt-avatar is-large">{{ strtoupper(substr($selectedParticipant->participant_name ?? 'U', 0, 1)) }}</span>
        <div><h2>{{ $selectedParticipant->participant_name }}</h2><p>{{ $selectedParticipant->participant_email ?: 'No email recorded' }}</p></div>
        <a href="{{ $trackingQuery(['tab' => 'engagement', 'pivot' => 'participant', 'participant' => $selectedParticipant->enrollment_id, 'search' => null]) }}" class="lt-button" data-tracking-link data-tracking-action="tab" data-tracking-regions="tabs navigator main">View engagement logs</a>
    </section>

    <section class="lt-metrics" aria-label="Participant activity summary">
        <article class="lt-metric"><span>Materials accessed</span><strong>{{ $metrics['materials_accessed'] ?? '—' }}</strong><small>{{ $tracking['available'] ? 'Unique recorded materials' : 'Not recorded' }}</small></article>
        <article class="lt-metric"><span>Quiz attempts</span><strong>{{ $metrics['quiz_attempts'] ?? '—' }}</strong><small>{{ $quizAttemptsAvailable ? 'Actual persisted attempts' : 'Not recorded' }}</small></article>
        <article class="lt-metric"><span>Last activity</span><strong class="is-date">{{ $metrics['last_activity'] ? \Carbon\Carbon::parse($metrics['last_activity'])->format('d M Y') : '—' }}</strong><small>{{ $metrics['last_activity'] ? \Carbon\Carbon::parse($metrics['last_activity'])->format('h:i A') : 'Not recorded' }}</small></article>
        <article class="lt-metric"><span>Tracked time</span><strong>{{ $metrics['tracked_seconds'] !== null ? \App\Services\Lms\TrainerLmsLearnerTrackingReportService::formatDuration($metrics['tracked_seconds']) : '—' }}</strong><small>{{ $tracking['duration'] ? 'Recorded material duration' : 'Not recorded' }}</small></article>
    </section>

    <section class="lt-card">
        <header class="lt-card-head"><div><h2>Recent activity</h2><p>Recorded material access and Quiz attempts, newest first.</p></div></header>
        <div data-tracking-region="activity-results">
            @if ($events->isEmpty())
                <div class="lt-empty is-compact"><i class="bi bi-clock-history"></i><h3>No recorded activity for this period</h3><p>No trustworthy material access or Quiz attempt has been recorded for this participant in the selected period.</p></div>
            @else
                <div class="lt-activity-list">
                    @foreach ($events as $event)
                        <article class="lt-activity">
                            <span class="lt-activity-icon"><i class="bi {{ $event->kind === 'quiz' ? 'bi-ui-checks-grid' : 'bi-file-earmark-arrow-down' }}"></i></span>
                            <div><strong>{{ $event->title }}</strong><p>{{ $event->context }} · {{ $event->meta }}</p></div>
                            <time>{{ \Carbon\Carbon::parse($event->activity_at)->format('d M Y, h:i A') }}</time>
                        </article>
                    @endforeach
                </div>
                @include('trainer.training.learner-tracking.partials.pagination', ['paginator' => $events, 'pageName' => 'activity_page', 'trackingRegions' => 'activity-results'])
            @endif
        </div>
    </section>
@endif
