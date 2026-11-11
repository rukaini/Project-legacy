<?php

namespace App\Services\Analytics;

use App\Support\CanonicalAttendance;
use App\Support\CanonicalEnrollment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAnalyticsReportService
{
    public function __construct(
        private readonly ParticipantAttendanceReportService $participantAttendanceReport
    ) {}

    public function trainings(): Collection
    {
        $periods = DB::table('course_session')
            ->select(
                'courseID as course_id',
                DB::raw('MIN(sessionDate) as period_start'),
                DB::raw('MAX(sessionDate) as period_end')
            )
            ->groupBy('courseID')
            ->get()
            ->keyBy('course_id');

        return DB::table('course')
            ->select('courseID as course_id', 'courseName as course_name', 'courseCategory as course_category', 'status', 'courseType as training_mode')
            ->orderBy('courseName')
            ->get()
            ->map(function ($training) use ($periods) {
                $period = $periods->get($training->course_id);
                $training->training_type = strtolower(trim((string) ($training->training_mode ?? ''))) === 'lms'
                    ? 'LMS'
                    : 'Simple Training';
                $training->period_label = $this->formatPeriod(
                    $period->period_start ?? null,
                    $period->period_end ?? null
                );

                return $training;
            });
    }

    public function resolveDateRange(Request $request): array
    {
        $preset = $this->validatedOption(
            (string) $request->query('report_date', 'all'),
            ['all', '7', '30', 'custom'],
            'all'
        );

        if ($preset === '7' || $preset === '30') {
            return [
                'preset' => $preset,
                'from' => Carbon::today()->subDays((int) $preset - 1)->toDateString(),
                'to' => Carbon::today()->toDateString(),
                'custom' => false,
            ];
        }

        if ($preset === 'custom') {
            try {
                $from = Carbon::createFromFormat('Y-m-d', (string) $request->query('date_from'))->startOfDay();
                $to = Carbon::createFromFormat('Y-m-d', (string) $request->query('date_to'))->startOfDay();
                if ($from->gt($to)) {
                    return $this->allDates();
                }

                return [
                    'preset' => 'custom',
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'custom' => true,
                ];
            } catch (\Throwable) {
                return $this->allDates();
            }
        }

        return $this->allDates();
    }

    public function validatedTrainingType(Request $request): string
    {
        return $this->validatedOption(
            (string) $request->query('training_type', 'all'),
            ['all', 'simple', 'lms'],
            'all'
        );
    }

    public function organizationReport(Collection $trainings, array $dateRange): array
    {
        $courseIds = $trainings->pluck('course_id')->map(fn ($id) => (string) $id)->values();
        if ($courseIds->isEmpty()) {
            return [
                'organizationMetrics' => $this->organizationMetrics(0, 0, 0, 0),
                'organizationAttendance' => $this->emptyAttendanceSummary(),
                'organizationAttendanceRows' => collect(),
                'organizationQuizRows' => collect(),
            ];
        }

        $sessions = DB::table('course_session')
            ->whereIn('courseID', $courseIds)
            ->select('sessionID as session_id', 'courseID as course_id', 'sessionName as session_title', 'sessionDate as date')
            ->orderBy('sessionDate')
            ->orderBy('sessionID')
            ->get();
        $eligibleSessions = $this->sessionsInDateRange($sessions, $dateRange);
        $participantCounts = CanonicalEnrollment::query('enrollment')
            ->whereIn('course_id', $courseIds)
            ->select('course_id', DB::raw('COUNT(*) as participant_count'))
            ->groupBy('course_id')
            ->pluck('participant_count', 'course_id');

        $recordCounts = $this->canonicalAttendanceCounts($eligibleSessions->pluck('session_id'));
        $sessionsByCourse = $eligibleSessions->groupBy('course_id');
        $organizationAttendanceRows = $trainings->map(function ($training) use (
            $participantCounts,
            $recordCounts,
            $sessionsByCourse
        ) {
            $courseSessions = $sessionsByCourse->get($training->course_id, collect());
            $summary = $this->attendanceCoverage(
                (int) ($participantCounts[$training->course_id] ?? 0),
                $courseSessions->count(),
                (int) $courseSessions->sum(
                    fn ($session) => $recordCounts[(string) $session->session_id] ?? 0
                )
            );

            return (object) [
                'course_id' => (string) $training->course_id,
                'course_name' => $training->course_name,
                'training_type' => $training->training_type,
                'participant_count' => (int) ($participantCounts[$training->course_id] ?? 0),
                'session_count' => $courseSessions->count(),
                'attendance_summary' => $summary,
            ];
        })->values();

        $organizationQuizRows = $this->quizTrainingSummary(
            $trainings->where('training_type', 'LMS')->values(),
            $dateRange
        );

        return [
            'organizationMetrics' => $this->organizationMetrics(
                $trainings->count(),
                (int) $participantCounts->sum(),
                $eligibleSessions->count(),
                (int) $organizationQuizRows->sum('graded_attempts')
            ),
            'organizationAttendance' => $this->attendanceCoverage(
                (int) $organizationAttendanceRows->sum(fn ($training) => $training->attendance_summary['total']),
                1,
                (int) $organizationAttendanceRows->sum(fn ($training) => $training->attendance_summary['counts']['Recorded'])
            ),
            'organizationAttendanceRows' => $organizationAttendanceRows,
            'organizationQuizRows' => $organizationQuizRows,
        ];
    }

    public function trainingReport(object $training, Request $request, array $dateRange): array
    {
        $courseId = (string) $training->course_id;
        $training->participant_count = CanonicalEnrollment::query()->where('course_id', $courseId)->count();
        $sessionOptions = $this->courseSessions($courseId);
        $training->session_count = $sessionOptions->count();
        $selectedSessionId = $this->validatedSessionId($request, $sessionOptions);
        [$attendanceSummary, $sessionResults] = $this->attendanceSummary(
            $courseId,
            $sessionOptions,
            $selectedSessionId,
            (int) $training->participant_count,
            $dateRange
        );
        $participantAttendance = $selectedSessionId !== ''
            ? $this->participantAttendanceReport->forSession($courseId, $selectedSessionId)
            : collect();

        $assessmentOptions = $training->training_type === 'LMS'
            ? $this->courseQuizAssessments($courseId)
            : collect();
        $requestedAssessmentId = (int) $request->query('assessment_id', 0);
        $selectedAssessmentId = $assessmentOptions->contains(
            fn ($assessment) => (int) $assessment->assessment_id === $requestedAssessmentId
        ) ? $requestedAssessmentId : 0;
        $visibleAssessments = $selectedAssessmentId > 0
            ? $assessmentOptions->where('assessment_id', $selectedAssessmentId)->values()
            : $assessmentOptions;
        [$quizResults, $quizTotals] = $this->quizSummary($courseId, $visibleAssessments, $dateRange);
        $quizAttempts = $selectedAssessmentId > 0
            ? $this->quizAttempts(
                $courseId,
                $selectedAssessmentId,
                $assessmentOptions->firstWhere('assessment_id', $selectedAssessmentId),
                $dateRange
            )
            : collect();

        return [
            'selectedTraining' => $training,
            'headlineMetrics' => $this->headlineMetrics(
                $training,
                $attendanceSummary,
                $quizTotals,
                $sessionOptions->count()
            ),
            'attendanceSummary' => $attendanceSummary,
            'sessionOptions' => $sessionOptions,
            'sessionResults' => $sessionResults,
            'participantAttendance' => $participantAttendance,
            'selectedSessionId' => $selectedSessionId,
            'assessmentOptions' => $assessmentOptions,
            'selectedAssessmentId' => $selectedAssessmentId,
            'quizResults' => $quizResults,
            'quizAttempts' => $quizAttempts,
            'quizTotals' => $quizTotals,
            'hasQuizAssessments' => $assessmentOptions->isNotEmpty(),
        ];
    }

    private function organizationMetrics(int $trainings, int $participants, int $sessions, int $attempts): Collection
    {
        return collect([
            ['label' => 'Total Trainings', 'value' => $trainings, 'note' => 'Included in this report', 'icon' => 'bi-mortarboard', 'tone' => 'blue'],
            ['label' => 'Total Participants', 'value' => $participants, 'note' => 'Enrollment records', 'icon' => 'bi-people', 'tone' => 'blue'],
            ['label' => 'Total Sessions', 'value' => $sessions, 'note' => 'Within the analysis period', 'icon' => 'bi-calendar3', 'tone' => 'blue'],
            ['label' => 'Graded Quiz Attempts', 'value' => $attempts, 'note' => 'Valid submitted quiz attempts', 'icon' => 'bi-ui-checks-grid', 'tone' => 'green'],
        ]);
    }

    private function quizTrainingSummary(Collection $trainings, array $dateRange): Collection
    {
        if ($trainings->isEmpty() || ! Schema::hasTable('assessment_submission')) {
            return collect();
        }

        $trainingMap = $trainings->keyBy('course_id');
        $rows = DB::table('assessment_submission as submission')
            ->join('assessment', 'submission.assessment_id', '=', 'assessment.assessment_id')
            ->join('enroll_guru_baru as enrollment', 'submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->whereIn('assessment.course_id', $trainings->pluck('course_id'))
            ->whereColumn('enrollment.course_id', 'assessment.course_id')
            ->whereRaw('LOWER(assessment.type) = ?', ['quiz'])
            ->where('assessment.total_marks', '>', 0)
            ->whereNotNull('submission.grade')
            ->whereNotNull('submission.submitted_at')
            ->select(
                'assessment.course_id',
                'assessment.assessment_id',
                'assessment.total_marks',
                'submission.grade',
                'submission.submitted_at'
            )
            ->get()
            ->filter(fn ($row) => $this->dateMatches($row->submitted_at, $dateRange))
            ->filter(fn ($row) => is_numeric($row->grade) && is_numeric($row->total_marks) && (float) $row->total_marks > 0)
            ->map(function ($row) {
                $row->percentage = ((float) $row->grade / (float) $row->total_marks) * 100;

                return $row;
            });

        return $rows->groupBy('course_id')->map(function (Collection $trainingRows, $courseId) use ($trainingMap) {
            $quizAverages = $trainingRows->groupBy('assessment_id')->map(
                fn (Collection $quizRows) => (float) $quizRows->avg('percentage')
            );
            $training = $trainingMap->get($courseId);

            return (object) [
                'course_id' => (string) $courseId,
                'course_name' => $training->course_name,
                'quiz_count' => $quizAverages->count(),
                'graded_attempts' => $trainingRows->count(),
                'average_percent' => round((float) $trainingRows->avg('percentage'), 1),
                'highest_quiz_average' => round((float) $quizAverages->max(), 1),
                'lowest_quiz_average' => round((float) $quizAverages->min(), 1),
            ];
        })->sortByDesc('average_percent')->values();
    }

    private function courseSessions(string $courseId): Collection
    {
        return DB::table('course_session')
            ->where('courseID', $courseId)
            ->select('sessionID as session_id', 'sessionName as session_title', 'sessionDate as date')
            ->orderBy('sessionDate')
            ->orderBy('sessionID')
            ->get()
            ->map(function ($session) {
                $session->date_label = $this->formatDate($session->date ?? null);

                return $session;
            });
    }

    private function attendanceSummary(
        string $courseId,
        Collection $sessions,
        string $selectedSessionId,
        int $participantCount,
        array $dateRange
    ): array {
        $eligibleSessions = $this->sessionsInDateRange($sessions, $dateRange);
        $recordCounts = $this->canonicalAttendanceCounts($eligibleSessions->pluck('session_id'));
        $sessionResults = $eligibleSessions->map(function ($session) use ($participantCount, $recordCounts) {
            $session->participant_count = $participantCount;
            $session->attendance_summary = $this->attendanceCoverage(
                $participantCount,
                1,
                (int) ($recordCounts[(string) $session->session_id] ?? 0)
            );

            return $session;
        })->values();

        $summarySessions = $selectedSessionId !== ''
            ? $sessionResults->where('session_id', $selectedSessionId)
            : $sessionResults;

        return [
            $this->attendanceCoverage(
                $participantCount,
                $summarySessions->count(),
                (int) $summarySessions->sum(fn ($session) => $session->attendance_summary['counts']['Recorded'])
            ),
            $sessionResults,
        ];
    }

    private function validatedSessionId(Request $request, Collection $sessions): string
    {
        if ($sessions->count() < 2) {
            return '';
        }

        $requested = mb_substr(trim((string) $request->query('session_id', '')), 0, 20);

        return $sessions->contains(fn ($session) => (string) $session->session_id === $requested)
            ? $requested
            : '';
    }

    private function courseQuizAssessments(string $courseId): Collection
    {
        if (! Schema::hasTable('assessment')) {
            return collect();
        }

        return DB::table('assessment')
            ->where('course_id', $courseId)
            ->whereRaw('LOWER(type) = ?', ['quiz'])
            ->select('assessment_id', 'title', 'type', 'total_marks')
            ->orderBy('title')
            ->get()
            ->map(function ($assessment) {
                $assessment->type_family = 'quiz';

                return $assessment;
            });
    }

    private function quizSummary(string $courseId, Collection $assessments, array $dateRange): array
    {
        if ($assessments->isEmpty() || ! Schema::hasTable('assessment_submission')) {
            return [collect(), ['attempts' => 0, 'average_score' => null]];
        }

        $rows = DB::table('assessment_submission as submission')
            ->join('enroll_guru_baru as enrollment', 'submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.course_id', $courseId)
            ->whereIn('submission.assessment_id', $assessments->pluck('assessment_id'))
            ->select('submission.assessment_id', 'submission.grade', 'submission.submitted_at')
            ->get()
            ->filter(fn ($row) => $this->dateMatches($row->submitted_at, $dateRange))
            ->groupBy('assessment_id');

        $results = $assessments->map(function ($assessment) use ($rows) {
            $attempts = $rows->get($assessment->assessment_id, collect())
                ->filter(fn ($row) => ! empty($row->submitted_at))
                ->values();
            $totalMarks = is_numeric($assessment->total_marks) ? (float) $assessment->total_marks : 0.0;
            $scores = $totalMarks > 0
                ? $attempts
                    ->filter(fn ($row) => is_numeric($row->grade))
                    ->map(fn ($row) => ((float) $row->grade / $totalMarks) * 100)
                    ->values()
                : collect();

            $assessment->attempt_count = $scores->count();
            $assessment->average_percent = $scores->isNotEmpty() ? round((float) $scores->avg(), 1) : null;
            $assessment->highest_percent = $scores->isNotEmpty() ? round((float) $scores->max(), 1) : null;
            $assessment->lowest_percent = $scores->isNotEmpty() ? round((float) $scores->min(), 1) : null;
            $assessment->score_values = $scores;

            return $assessment;
        });
        $allScores = $results->flatMap(fn ($assessment) => $assessment->score_values)->values();

        return [
            $results->filter(fn ($assessment) => $assessment->attempt_count > 0)
                ->sortBy(fn ($assessment) => strtolower((string) $assessment->title))
                ->values(),
            [
                'attempts' => (int) $results->sum('attempt_count'),
                'average_score' => $allScores->isNotEmpty() ? round((float) $allScores->avg(), 1) : null,
            ],
        ];
    }

    private function quizAttempts(string $courseId, int $assessmentId, object $assessment, array $dateRange): Collection
    {
        if (! Schema::hasTable('assessment_submission')) {
            return collect();
        }

        $totalMarks = is_numeric($assessment->total_marks ?? null) ? (float) $assessment->total_marks : 0.0;
        if ($totalMarks <= 0) {
            return collect();
        }

        $rows = DB::table('assessment_submission as submission')
            ->join('assessment', 'submission.assessment_id', '=', 'assessment.assessment_id')
            ->join('enroll_guru_baru as enrollment', 'submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->join('guru_new', 'enrollment.gn_id', '=', 'guru_new.gn_id')
            ->where('assessment.course_id', $courseId)
            ->where('assessment.assessment_id', $assessmentId)
            ->where('enrollment.course_id', $courseId)
            ->whereRaw('LOWER(assessment.type) = ?', ['quiz'])
            ->whereNotNull('submission.grade')
            ->whereNotNull('submission.submitted_at')
            ->select(
                'submission.submission_id',
                'submission.enrollment_id',
                'submission.grade',
                'submission.submitted_at',
                'guru_new.gn_name as participant_name'
            )
            ->orderBy('submission.submitted_at')
            ->orderBy('submission.submission_id')
            ->get()
            ->filter(fn ($row) => $this->dateMatches($row->submitted_at, $dateRange))
            ->filter(fn ($row) => is_numeric($row->grade))
            ->map(function ($row) use ($totalMarks) {
                $row->percentage = round(((float) $row->grade / $totalMarks) * 100, 1);
                $row->submitted_label = $this->formatDateTime($row->submitted_at);

                return $row;
            });

        $attemptsByLearner = [];

        return $rows->map(function ($row) use (&$attemptsByLearner) {
            $key = (string) $row->enrollment_id;
            $attemptsByLearner[$key] = ($attemptsByLearner[$key] ?? 0) + 1;
            $row->attempt_number = $attemptsByLearner[$key];

            return $row;
        })->sort(function ($left, $right) {
            $percentageOrder = $right->percentage <=> $left->percentage;
            if ($percentageOrder !== 0) {
                return $percentageOrder;
            }

            $submittedOrder = strcmp((string) $right->submitted_at, (string) $left->submitted_at);

            return $submittedOrder !== 0
                ? $submittedOrder
                : ((int) $right->submission_id <=> (int) $left->submission_id);
        })->values();
    }

    private function headlineMetrics(
        object $training,
        array $attendance,
        array $quizTotals,
        int $sessionCount
    ): Collection {
        $metrics = collect([
            ['label' => 'Participants', 'value' => (string) $training->participant_count, 'note' => 'Enrolled in this training', 'tone' => 'blue', 'icon' => 'bi-people'],
            ['label' => 'Sessions', 'value' => (string) $sessionCount, 'note' => 'Training sessions', 'tone' => 'blue', 'icon' => 'bi-calendar3'],
        ]);

        if ($training->training_type === 'Simple Training') {
            if ($attendance['available']) {
                $metrics->push(['label' => 'Attendance Coverage', 'value' => $attendance['rate'].'%', 'note' => 'Canonical records / participant-session slots', 'tone' => 'green', 'icon' => 'bi-person-check']);
                $metrics->push(['label' => 'Recorded Attendance', 'value' => (string) $attendance['counts']['Recorded'], 'note' => 'Canonical owner records', 'tone' => 'blue', 'icon' => 'bi-clipboard-check']);
            }

            return $metrics->take(4);
        }

        if ($quizTotals['attempts'] > 0) {
            $metrics->push(['label' => 'Quiz Attempts', 'value' => (string) $quizTotals['attempts'], 'note' => 'Submitted quiz attempts', 'tone' => 'blue', 'icon' => 'bi-ui-checks-grid']);
        }
        if ($quizTotals['average_score'] !== null) {
            $metrics->push(['label' => 'Average Quiz Score', 'value' => number_format($quizTotals['average_score'], 1).'%', 'note' => 'Across graded attempts', 'tone' => 'green', 'icon' => 'bi-graph-up-arrow']);
        }

        return $metrics->take(4);
    }

    private function sessionsInDateRange(Collection $sessions, array $dateRange): Collection
    {
        if (! $dateRange['from'] && ! $dateRange['to']) {
            return $sessions->values();
        }

        return $sessions->filter(function ($session) use ($dateRange) {
            if (! $session->date) {
                return false;
            }

            $date = Carbon::parse($session->date)->startOfDay();

            return (! $dateRange['from'] || $date->gte(Carbon::parse($dateRange['from'])))
                && (! $dateRange['to'] || $date->lte(Carbon::parse($dateRange['to'])));
        })->values();
    }

    private function canonicalAttendanceCounts(Collection $sessionIds): Collection
    {
        $sessionIds = $sessionIds->map(fn ($id) => (string) $id)->filter()->unique()->values();
        if ($sessionIds->isEmpty()) {
            return collect();
        }

        return CanonicalAttendance::query()
            ->whereIn('session_id', $sessionIds)
            ->select('session_id', DB::raw('COUNT(*) as recorded_count'))
            ->groupBy('session_id')
            ->pluck('recorded_count', 'session_id');
    }

    private function attendanceCoverage(int $participantCount, int $sessionCount, int $recordedCount): array
    {
        $expectedRecords = max(0, $participantCount) * max(0, $sessionCount);
        $total = max($expectedRecords, $recordedCount);

        return [
            'available' => $total > 0,
            'total' => $total,
            'rate' => $total > 0 ? (int) round(($recordedCount / $total) * 100) : null,
            'counts' => [
                'Recorded' => $recordedCount,
                'Not Recorded' => max(0, $expectedRecords - $recordedCount),
            ],
        ];
    }

    private function emptyAttendanceSummary(): array
    {
        return [
            'available' => false,
            'total' => 0,
            'rate' => null,
            'counts' => ['Recorded' => 0, 'Not Recorded' => 0],
        ];
    }

    private function dateMatches($value, array $dateRange): bool
    {
        if (! $dateRange['from'] && ! $dateRange['to']) {
            return true;
        }
        if (! $value) {
            return false;
        }

        $date = Carbon::parse($value);

        return (! $dateRange['from'] || $date->gte(Carbon::parse($dateRange['from'])->startOfDay()))
            && (! $dateRange['to'] || $date->lte(Carbon::parse($dateRange['to'])->endOfDay()));
    }

    private function validatedOption(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function allDates(): array
    {
        return ['preset' => 'all', 'from' => null, 'to' => null, 'custom' => false];
    }

    private function formatPeriod($start, $end): ?string
    {
        if (! $start && ! $end) {
            return null;
        }

        $startDate = $this->formatDate($start);
        $endDate = $this->formatDate($end);

        return $startDate === $endDate || ! $endDate ? $startDate : $startDate.' - '.$endDate;
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return null;
        }
    }
}
