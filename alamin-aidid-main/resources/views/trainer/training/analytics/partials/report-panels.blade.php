@php($attendanceLabels = ['Recorded', 'Not Recorded'])
@php($analyticsFilterFormId = $analyticsFilterFormId ?? 'analyticsFilterForm')
@php($isAdminAnalyticsReport = $isAdminAnalyticsReport ?? false)
@php($quizChartData = collect())
@if($selectedTraining->training_type === 'LMS' && $hasQuizAssessments)
    @php($quizChartData = $quizResults->map(fn ($q) => ['title' => $q->title ?: 'Quiz '.$q->assessment_id, 'attempts' => $q->attempt_count, 'average' => $q->average_percent, 'highest' => $q->highest_percent, 'lowest' => $q->lowest_percent])->values())
@endif
@php($gradedQuizCount = $quizResults->filter(fn ($quiz) => $quiz->average_percent !== null)->count())
<section class="ana-report-container" aria-label="Training analytics reports">
    <article class="ana-panel ana-attendance-panel">
        <div class="ana-panel-head ana-report-head">
            <div>@if(!$isAdminAnalyticsReport)<span class="ana-eyebrow">Training report</span>@endif<h2>Attendance Report</h2><p>Attendance is externally recorded and read-only.</p></div>
            @if($attendanceSummary['available'])<span class="ana-rate">{{ $attendanceSummary['rate'] }}% records captured</span>@endif
            @if($selectedTraining && $sessionOptions->count() > 1)
                <div class="ana-report-control"><label class="ana-flabel" for="sessionSelector">Session</label><select id="sessionSelector" name="session_id" form="{{ $analyticsFilterFormId }}" class="ana-input"><option value="0">All sessions</option>@foreach ($sessionOptions as $session)<option value="{{ $session->session_id }}" @selected((string) $selectedSessionId === (string) $session->session_id)>{{ $session->session_title ?: 'Session '.$session->session_id }} - {{ $session->date_label ?: 'No date' }}</option>@endforeach</select></div>
            @endif
        </div>
        @if($attendanceSummary['available'])
            <div class="ana-attendance-charts"><div class="ana-chart-box ana-donut-box trainer-analytics-donut-box">@if(!$isAdminAnalyticsReport)<h3 class="trainer-analytics-chart-title">Attendance Record Coverage</h3>@endif<div class="ana-canvas-wrap {{ $isAdminAnalyticsReport ? 'admin-chart-canvas-wrap admin-chart-canvas-wrap--detail-donut' : '' }}"><canvas id="attendanceDonutChart" aria-label="Attendance record coverage doughnut chart" data-values='@json(array_values($attendanceSummary['counts']))' data-labels='@json($attendanceLabels)' data-rate="{{ $attendanceSummary['rate'] }}"></canvas></div><div class="ana-chart-legend ana-attendance-legend">@foreach($attendanceLabels as $label)<span class="{{ strtolower(str_replace(' ','-',$label)) }}">{{ $label }}: {{ $attendanceSummary['counts'][$label] }} ({{ round($attendanceSummary['counts'][$label] / max(1,$attendanceSummary['total']) * 100) }}%)</span>@endforeach</div><p class="ana-chart-note">Canonical attendance row existence determines Recorded or Not Recorded.</p></div><div class="ana-chart-box trainer-analytics-bar-box {{ $isAdminAnalyticsReport ? 'admin-chart-canvas-wrap admin-chart-canvas-wrap--detail-attendance' : '' }}">@if(!$isAdminAnalyticsReport)<h3 class="trainer-analytics-chart-title">Recorded and Not Recorded</h3>@endif<canvas id="attendanceBarChart" aria-label="Recorded and not recorded attendance bar chart" data-values='@json(array_values($attendanceSummary['counts']))' data-labels='@json($attendanceLabels)'></canvas></div></div>
        @else
            <div class="ana-compact-empty"><i class="bi bi-calendar2-minus" aria-hidden="true"></i><span>No enrolled participant-session attendance slots are available for this selection.</span></div>
        @endif
        @include('trainer.training.analytics.partials.attendance-results')
    </article>
    @if($selectedTraining->training_type === 'LMS' && $hasQuizAssessments)
        <article class="ana-panel ana-quiz-section">
            <div class="ana-panel-head ana-report-head"><div>@if(!$isAdminAnalyticsReport)<span class="ana-eyebrow">LMS performance</span>@endif<h2>Quiz Performance</h2><p>{{ $selectedAssessmentId ? 'Detailed results for the selected quiz.' : 'Compare average marks across all quizzes.' }}</p></div><div class="ana-report-control"><label class="ana-flabel" for="assessmentSelector">Quiz</label><select id="assessmentSelector" name="assessment_id" form="{{ $analyticsFilterFormId }}" class="ana-input"><option value="0">All quizzes</option>@foreach ($assessmentOptions->where('type_family','quiz') as $assessment)<option value="{{ $assessment->assessment_id }}" @selected((int) $selectedAssessmentId === (int) $assessment->assessment_id)>{{ $assessment->title ?: 'Quiz '.$assessment->assessment_id }}</option>@endforeach</select></div></div>
            @if($selectedAssessmentId > 0)
                @php($selectedQuiz = $assessmentOptions->firstWhere('assessment_id', $selectedAssessmentId))
                @if($quizAttempts->isNotEmpty())
                    @php($specificAverage = round((float) $quizAttempts->avg('percentage'), 1))
                    @php($specificHighest = round((float) $quizAttempts->max('percentage'), 1))
                    @php($specificLowest = round((float) $quizAttempts->min('percentage'), 1))
                    @php($quizSummaryCards = collect([
                        ['label' => 'Graded attempts', 'value' => $quizAttempts->count()],
                        ['label' => 'Average mark', 'value' => number_format($specificAverage, 1).'%'],
                        ['label' => 'Highest mark', 'value' => number_format($specificHighest, 1).'%'],
                        ['label' => 'Lowest mark', 'value' => number_format($specificLowest, 1).'%'],
                    ]))
                    @include('trainer.training.analytics.partials.quiz-summary')
                    <div class="ana-chart-box ana-quiz-chart-box is-specific {{ $isAdminAnalyticsReport ? 'admin-chart-canvas-wrap admin-chart-canvas-wrap--detail-quiz' : '' }}"><canvas id="quizComparisonChart" aria-label="Quiz score distribution chart" data-specific="1" data-results='@json($quizAttempts->map(fn ($attempt) => ['percentage' => $attempt->percentage])->values())'></canvas></div>
                    <div class="ana-table-divider"><div class="ana-table-wrap"><table class="ana-table" data-noun="attempts"><thead><tr><th>Student</th><th>Attempt</th><th>Mark</th><th>Percentage</th><th>Submitted at</th></tr></thead><tbody>@foreach($quizAttempts as $attempt)<tr><td>{{ $attempt->participant_name }}</td><td>{{ $attempt->attempt_number }}</td><td>{{ rtrim(rtrim(number_format((float) $attempt->grade, 2, '.', ''), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) ($selectedQuiz->total_marks ?? 0), 2, '.', ''), '0'), '.') }}</td><td>{{ number_format($attempt->percentage,1) }}%</td><td>{{ $attempt->submitted_label }}</td></tr>@endforeach</tbody></table></div></div>
                @else
                    <div class="ana-compact-empty">No graded attempts are available for this quiz.</div>
                @endif
            @elseif($quizResults->isNotEmpty())
                @php($bestQuiz = $quizResults->sortByDesc('average_percent')->first())
                @php($quizSummaryCards = collect([
                    ['label' => 'Total quizzes', 'value' => $quizResults->count()],
                    ['label' => 'Graded attempts', 'value' => $quizTotals['attempts']],
                    ['label' => 'Overall average', 'value' => $quizTotals['average_score'] !== null ? number_format($quizTotals['average_score'], 1).'%' : '—'],
                    ['label' => 'Best-performing quiz', 'value' => optional($bestQuiz)->title ?: '—', 'detail' => optional($bestQuiz)->average_percent !== null ? number_format(optional($bestQuiz)->average_percent, 1).'%' : '—', 'title' => true],
                ]))
                @include('trainer.training.analytics.partials.quiz-summary')
                @if($gradedQuizCount > 1)<div class="ana-chart-box ana-quiz-chart-box is-ranking {{ $isAdminAnalyticsReport ? 'admin-chart-canvas-wrap admin-chart-canvas-wrap--detail-quiz' : '' }}" style="--quiz-count: {{ min(8, max(1, $gradedQuizCount)) }}"><canvas id="quizComparisonChart" aria-label="Average mark by quiz" data-specific="0" data-results='@json($quizChartData)'></canvas></div>@else<div class="ana-compact-empty ana-quiz-unavailable">Quiz comparison becomes available when at least two quizzes have graded attempts.</div>@endif
                @if(!$selectedAssessmentId && $quizResults->count() > 8)<p class="ana-chart-note">Chart shows the top 8 quizzes by average mark. All quizzes are listed below.</p>@endif
                <div class="ana-table-divider"><div class="ana-table-wrap"><table class="ana-table" data-noun="quizzes"><thead><tr><th>Quiz</th><th>Attempts</th><th>Average mark</th><th>Highest mark</th><th>Lowest mark</th></tr></thead><tbody>@foreach($quizResults as $quiz)<tr><td>{{ $quiz->title ?: 'Quiz '.$quiz->assessment_id }}</td><td>{{ $quiz->attempt_count }}</td><td>{{ $quiz->average_percent !== null ? number_format($quiz->average_percent,1).'%' : '—' }}</td><td>{{ $quiz->highest_percent !== null ? number_format($quiz->highest_percent,1).'%' : '—' }}</td><td>{{ $quiz->lowest_percent !== null ? number_format($quiz->lowest_percent,1).'%' : '—' }}</td></tr>@endforeach</tbody></table></div></div>
            @else<div class="ana-compact-empty">No graded quiz attempts are available for this selection.</div>@endif
        </article>
    @endif
</section>
