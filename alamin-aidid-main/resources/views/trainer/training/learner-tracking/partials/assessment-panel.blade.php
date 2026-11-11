@php $quizRows = $quizTracking['rows']; $summary = $quizTracking['summary']; @endphp

@if (! $quizTracking['available'])
    <div class="lt-empty lt-card"><i class="bi bi-database-slash"></i><h2>Quiz attempts are not available</h2><p>The optional assessment attempt source is not available or is incompatible with this report.</p></div>
@elseif (! $selectedQuiz)
    <div class="lt-empty lt-card"><i class="bi bi-ui-checks-grid"></i><h2>{{ $search ? 'No quizzes match this search.' : 'No quizzes are available for this training.' }}</h2><p>{{ $search ? 'Clear the search or try another Quiz title.' : 'Quizzes created in Assessment Builder will appear here automatically.' }}</p></div>
@else
    <section class="lt-card">
        <header class="lt-card-head lt-result-head">
            <div><p class="lt-eyebrow">Quiz tracking</p><h2>{{ $selectedQuiz->title ?: 'Untitled Quiz' }}</h2><p>{{ $selectedQuiz->availability['context'] }}</p></div>
            <span class="lt-badge is-{{ $selectedQuiz->availability['key'] }}">{{ $selectedQuiz->availability['label'] }}</span>
        </header>

        @if ($selectedQuiz->question_count === 0)
            <div class="lt-source-note lt-inline-note"><i class="bi bi-info-circle"></i><span>This Quiz has no questions. Attempt records remain visible, but it has no scorable question structure.</span></div>
        @endif

        <div class="lt-metrics is-inside">
            <article class="lt-metric"><span>Attempted participants</span><strong>{{ $summary['attempted'] }}</strong><small>Distinct enrolled learners</small></article>
            <article class="lt-metric"><span>Not attempted</span><strong>{{ $summary['not_attempted'] }}</strong><small>Enrolled learners</small></article>
            <article class="lt-metric"><span>Total attempts</span><strong>{{ $summary['total_attempts'] }}</strong><small>All persisted attempts</small></article>
            <article class="lt-metric"><span>Latest attempt</span><strong class="is-date">{{ $summary['latest_attempt'] ? \Carbon\Carbon::parse($summary['latest_attempt'])->format('d M Y') : '—' }}</strong><small>{{ $summary['latest_attempt'] ? \Carbon\Carbon::parse($summary['latest_attempt'])->format('h:i A') : 'No attempts' }}</small></article>
        </div>

        <div class="lt-toolbar">
            <label class="lt-search lt-result-search">
                <i class="bi bi-search"></i>
                <span class="sr-only">Search quiz participants</span>
                <input type="search" value="{{ $participantSearch }}" placeholder="Search participants" data-tracking-search="participant_search" data-tracking-action="quiz-search" data-tracking-regions="quiz-results attempt-detail" autocomplete="off">
                <button type="button" class="lt-search-clear {{ $participantSearch === '' ? 'is-hidden' : '' }}" data-search-clear aria-label="Clear participant search"><i class="bi bi-x"></i></button>
            </label>
            <label class="lt-filter">Attempt status
                <select data-tracking-filter="status" data-tracking-regions="quiz-results attempt-detail">
                    <option value="">All participants</option>
                    <option value="attempted" {{ request('status') === 'attempted' ? 'selected' : '' }}>Attempted</option>
                    <option value="not_attempted" {{ request('status') === 'not_attempted' ? 'selected' : '' }}>Not attempted</option>
                </select>
            </label>
        </div>

        <div data-tracking-region="quiz-results">
            @if ($quizRows->isEmpty())
                <div class="lt-empty is-compact"><i class="bi bi-search"></i><h3>{{ $participantSearch ? 'No quiz participants match this search.' : 'No participants match this filter.' }}</h3><p>{{ $participantSearch ? 'Clear the search or try another participant name or email.' : 'Change the attempt status filter to see other participants.' }}</p></div>
            @else
                <div class="lt-table-scroll">
                <table class="lt-table">
                    <thead><tr><th>Participant</th><th class="t-center">Attempt status</th><th class="t-center">Attempts</th><th>Latest result</th><th>Latest attempt</th><th class="t-center">Action</th></tr></thead>
                    <tbody>
                        @foreach ($quizRows as $participant)
                            @php $attempts = $quizTracking['attempts']->get($participant->enrollment_id, collect()); $latest = $attempts->last(); @endphp
                            <tr>
                                <td><div class="lt-person-cell"><span class="lt-avatar">{{ strtoupper(substr($participant->participant_name ?? 'U', 0, 1)) }}</span><span><strong>{{ $participant->participant_name }}</strong><small>{{ $participant->participant_email ?: 'No email recorded' }}</small></span></div></td>
                                <td class="t-center"><span class="lt-badge {{ (int) $participant->attempt_count > 0 ? 'is-open' : 'is-neutral' }}">{{ (int) $participant->attempt_count > 0 ? 'Attempted' : 'Not attempted' }}</span></td>
                                <td class="t-center">{{ (int) $participant->attempt_count }}</td>
                                <td>
                                    @if (! $latest || $latest->grade === null)<span>—<small class="lt-cell-sub">{{ $latest ? 'Not graded' : 'No attempt' }}</small></span>
                                    @elseif ((float) $selectedQuiz->total_marks <= 0)<span>{{ $latest->grade }}<small class="lt-cell-sub">Not scored · total marks is 0</small></span>
                                    @else<span>{{ $latest->grade }} / {{ $selectedQuiz->total_marks }}<small class="lt-cell-sub">{{ $latest->percentage }}%</small></span>@endif
                                </td>
                                <td>{{ $latest?->submitted_at ? \Carbon\Carbon::parse($latest->submitted_at)->format('d M Y, h:i A') : '—' }}</td>
                                <td class="t-center">
                                    @if ($latest)
                                        <a class="lt-detail-link" href="{{ $trackingQuery(['attempt' => $latest->submission_id]) }}" data-tracking-link data-tracking-regions="attempt-detail" data-attempt-opener>View details</a>
                                    @else — @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                @include('trainer.training.learner-tracking.partials.pagination', ['paginator' => $quizRows, 'pageName' => 'quiz_page', 'trackingRegions' => 'quiz-results'])
            @endif
        </div>
    </section>
    @include('trainer.training.learner-tracking.partials.attempt-detail')
@endif
