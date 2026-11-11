<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ParticipantAttendanceReportService;
use App\Support\CanonicalAttendance;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\SharedTrainingSchema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    public function analytics(Request $request, ParticipantAttendanceReportService $participantAttendanceReport)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $assignedCourseIds = SharedTrainingSchema::assignedCourseIds($trainerId);

        $trainings = $this->assignedTrainings($assignedCourseIds);
        $requestedCourseId = mb_substr(trim((string) $request->query('course_id', '')), 0, 20);
        $selectedTraining = $requestedCourseId !== ''
            ? $trainings->first(fn ($training) => (string) $training->course_id === $requestedCourseId)
            : null;
        $selectedTraining ??= $trainings->first();

        $headlineMetrics = collect();
        $attendanceSummary = $this->emptyAttendanceSummary();
        $sessionOptions = collect();
        $sessionResults = collect();
        $participantAttendance = collect();
        $selectedSessionId = '';
        $sessionSort = 'date';
        $assessmentType = 'all';
        $assessmentOptions = collect();
        $selectedAssessmentId = 0;
        $quizResults = collect();
        $quizAttempts = collect();
        $quizSort = 'name';
        $quizTotals = ['attempts' => 0, 'average_score' => null];
        $hasQuizAssessments = false;
        $submissionResults = collect();
        $submissionSort = 'name';
        $submissionTotals = ['submitted' => 0, 'reviewed' => 0, 'pending' => 0];
        $hasSubmissionAssessments = false;
        $pendingReviewCount = 0;
        $dateRange = $this->resolveDateRange($request);
        $reportDate = $dateRange['preset'];
        $section = $this->validatedOption((string) $request->query('section', 'overview'), ['overview', 'attendance', 'assessments'], 'overview');
        $trainingUrl = null;
        $contextFilterActive = false;
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

        if ($selectedTraining) {
            $courseId = (string) $selectedTraining->course_id;
            $isLms = $selectedTraining->training_type === 'LMS';
            if (! $isLms && $section === 'assessments') {
                $section = 'overview';
            }

            $selectedTraining->participant_count = SharedTrainingSchema::constrainEnrollmentToCourse(
                CanonicalEnrollment::query(),
                $selectedTraining
            )->count();
            $selectedTraining->session_count = $this->assignedSessions($trainerId, $courseId)->count();

            $trainingUrl = $isLms
                ? route('trainer.courses.show', $courseId)
                : route('trainer.simple.overview', $courseId);

            if ($isLms) {
                $assessmentType = $this->validatedOption(
                    (string) $request->query('assessment_type', 'all'),
                    ['all', 'quiz', 'submission'],
                    'all'
                );
                $quizSort = $assessmentType === 'submission'
                    ? 'name'
                    : $this->validatedOption(
                        (string) $request->query('quiz_sort', 'name'),
                        ['name', 'attempts', 'average'],
                        'name'
                    );
                $submissionSort = $assessmentType === 'quiz'
                    ? 'name'
                    : $this->validatedOption(
                        (string) $request->query('submission_sort', 'name'),
                        ['name', 'submitted', 'pending', 'latest'],
                        'name'
                    );

                $assessments = $this->courseAssessments($courseId);
                $assessmentOptions = $assessmentType === 'all'
                    ? $assessments
                    : $assessments->where('type_family', $assessmentType)->values();

                if ($assessmentOptions->count() > 0) {
                    $requestedAssessmentId = (int) $request->query('assessment_id', 0);
                    $selectedAssessmentId = $assessmentOptions->contains(
                        fn ($assessment) => (int) $assessment->assessment_id === $requestedAssessmentId
                    ) ? $requestedAssessmentId : 0;
                }

                $visibleAssessments = $selectedAssessmentId > 0
                    ? $assessmentOptions->where('assessment_id', $selectedAssessmentId)->values()
                    : $assessmentOptions;
                $quizAssessments = $visibleAssessments->where('type_family', 'quiz')->values();
                $submissionAssessments = $visibleAssessments->where('type_family', 'submission')->values();

                $hasQuizAssessments = $quizAssessments->isNotEmpty();
                $hasSubmissionAssessments = $submissionAssessments->isNotEmpty();
                [$quizResults, $quizTotals] = $this->quizSummary($courseId, $quizAssessments, $quizSort, $dateRange);
                if ($selectedAssessmentId > 0 && $quizAssessments->contains('assessment_id', $selectedAssessmentId)) {
                    $quizAttempts = $this->quizAttempts($courseId, $selectedAssessmentId, $quizAssessments->firstWhere('assessment_id', $selectedAssessmentId), $dateRange);
                }
                [$submissionResults, $submissionTotals] = $this->submissionSummary(
                    $courseId,
                    $submissionAssessments,
                    $submissionSort,
                    $dateRange
                );
                $pendingReviewCount = $submissionTotals['pending'];
                $sessionOptions = $this->assignedSessions($trainerId, $courseId);
                $sessionSort = $sessionOptions->count() > 1
                    ? $this->validatedOption((string) $request->query('session_sort', 'date'), ['date', 'rate', 'missing'], 'date')
                    : 'date';
                $selectedSessionId = $this->validatedSessionId($request, $sessionOptions);
                [$attendanceSummary, $sessionResults] = $this->attendanceSummary(
                    $courseId,
                    $sessionOptions,
                    $selectedSessionId,
                    (int) $selectedTraining->participant_count,
                    $sessionSort,
                    $dateRange
                );
                $contextFilterActive = $assessmentType !== 'all'
                    || $selectedAssessmentId > 0
                    || $quizSort !== 'name'
                    || $submissionSort !== 'name'
                    || $selectedSessionId !== ''
                    || $reportDate !== 'all'
                    || $section !== 'overview';
            } else {
                $sessionOptions = $this->assignedSessions($trainerId, $courseId);
                $sessionSort = $sessionOptions->count() > 1
                    ? $this->validatedOption(
                        (string) $request->query('session_sort', 'date'),
                        ['date', 'rate', 'missing'],
                        'date'
                    )
                    : 'date';

                if ($sessionOptions->count() > 1) {
                    $requestedSessionId = mb_substr(trim((string) $request->query('session_id', '')), 0, 20);
                    $selectedSessionId = $sessionOptions->contains(
                        fn ($session) => (string) $session->session_id === $requestedSessionId
                    ) ? $requestedSessionId : '';
                }

                $selectedSessionId = $this->validatedSessionId($request, $sessionOptions);
                [$attendanceSummary, $sessionResults] = $this->attendanceSummary(
                    $courseId,
                    $sessionOptions,
                    $selectedSessionId,
                    (int) $selectedTraining->participant_count,
                    $sessionSort,
                    $dateRange
                );
                $contextFilterActive = $selectedSessionId !== '' || $sessionSort !== 'date' || $reportDate !== 'all' || $dateRange['custom'] || $section !== 'overview';
            }

            if ($selectedSessionId !== '') {
                $participantAttendance = $participantAttendanceReport->forSession($courseId, $selectedSessionId);
            }

            $headlineMetrics = $this->headlineMetrics(
                $selectedTraining,
                $attendanceSummary,
                $quizTotals,
                $pendingReviewCount,
                $sessionOptions->count()
            );
        }

        return view('trainer.training.analytics', compact(
            'trainings',
            'selectedTraining',
            'headlineMetrics',
            'attendanceSummary',
            'sessionOptions',
            'sessionResults',
            'participantAttendance',
            'selectedSessionId',
            'sessionSort',
            'assessmentType',
            'assessmentOptions',
            'selectedAssessmentId',
            'quizResults',
            'quizAttempts',
            'quizSort',
            'quizTotals',
            'hasQuizAssessments',
            'submissionResults',
            'submissionSort',
            'submissionTotals',
            'hasSubmissionAssessments',
            'pendingReviewCount',
            'reportDate',
            'trainingUrl',
            'contextFilterActive', 'section', 'dateFrom', 'dateTo', 'dateRange'
        ));
    }

    private function assignedTrainings(Collection $assignedCourseIds): Collection
    {
        if ($assignedCourseIds->isEmpty()) {
            return collect();
        }

        return CanonicalTrainingQuery::courses()
            ->whereIn(SharedTrainingSchema::courseInternalExpression(), $assignedCourseIds)
            ->orderByRaw(SharedTrainingSchema::courseNameExpression())
            ->get()
            ->map(function ($training) {
                $sessions = SharedTrainingSchema::constrainSessionsToCourse(
                    CanonicalTrainingQuery::sessions(),
                    $training
                )->get();
                $training->training_type = strtolower((string) ($training->training_mode ?? 'standard')) === 'lms'
                    ? 'LMS'
                    : 'Simple Training';
                $training->period_label = $this->formatPeriod(
                    $sessions->min('date'),
                    $sessions->max('date')
                );

                return $training;
            });
    }

    private function assignedSessions(string $trainerId, string $courseId): Collection
    {
        $course = SharedTrainingSchema::course($courseId);
        if (! $course) {
            return collect();
        }

        $query = CanonicalTrainingQuery::sessions();
        SharedTrainingSchema::joinSessionAssignments($query);
        SharedTrainingSchema::constrainSessionsToCourse($query, $course);
        SharedTrainingSchema::constrainAssignmentToTrainer($query, $trainerId);

        return $query
            ->distinct()
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionInternalExpression())
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
        string $sort,
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
        });

        $sessionResults = match ($sort) {
            'rate' => $sessionResults->sortByDesc(
                fn ($session) => $session->attendance_summary['rate'] ?? -1
            )->values(),
            'missing' => $sessionResults->sortByDesc(
                fn ($session) => $session->attendance_summary['counts']['Not Recorded']
            )->values(),
            default => $sessionResults->sortBy(
                fn ($session) => ($session->date ?? '9999-12-31').'-'.str_pad((string) $session->session_id, 12, '0', STR_PAD_LEFT)
            )->values(),
        };

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

    private function sessionsInDateRange(Collection $sessions, array $dateRange): Collection
    {
        if (! $dateRange['from'] && ! $dateRange['to']) {
            return $sessions;
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

    private function validatedSessionId(Request $request, Collection $sessions): string
    {
        if ($sessions->count() < 2) {
            return '';
        }
        $requested = mb_substr(trim((string) $request->query('session_id', '')), 0, 20);

        return $sessions->contains(fn ($session) => (string) $session->session_id === $requested) ? $requested : '';
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

    private function courseAssessments(string $courseId): Collection
    {
        if (! Schema::hasTable('assessment')) {
            return collect();
        }

        return DB::table('assessment')
            ->where('course_id', $courseId)
            ->whereIn(DB::raw('LOWER(type)'), ['quiz', 'submission'])
            ->select('assessment_id', 'title', 'type', 'total_marks')
            ->orderBy('title')
            ->get()
            ->map(function ($assessment) {
                $assessment->type_family = strtolower(trim((string) $assessment->type));

                return $assessment;
            });
    }

    private function quizSummary(string $courseId, Collection $assessments, string $sort, array $dateRange): array
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
        $totals = [
            'attempts' => (int) $results->sum('attempt_count'),
            'average_score' => $allScores->isNotEmpty() ? round((float) $allScores->avg(), 1) : null,
        ];
        $results = $results->filter(fn ($assessment) => $assessment->attempt_count > 0);
        $results = match ($sort) {
            'attempts' => $results->sortByDesc('attempt_count')->values(),
            'average' => $results->sortByDesc(fn ($assessment) => $assessment->average_percent ?? -1)->values(),
            default => $results->sortBy(fn ($assessment) => strtolower((string) $assessment->title))->values(),
        };

        return [$results, $totals];
    }

    private function quizAttempts(string $courseId, int $assessmentId, $assessment, array $dateRange): Collection
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

        return $rows
            ->map(function ($row) use (&$attemptsByLearner) {
                $key = (string) $row->enrollment_id;
                $attemptsByLearner[$key] = ($attemptsByLearner[$key] ?? 0) + 1;
                $row->attempt_number = $attemptsByLearner[$key];

                return $row;
            })
            ->sort(function ($left, $right) {
                $percentageOrder = $right->percentage <=> $left->percentage;
                if ($percentageOrder !== 0) {
                    return $percentageOrder;
                }

                $submittedOrder = strcmp((string) $right->submitted_at, (string) $left->submitted_at);

                return $submittedOrder !== 0
                    ? $submittedOrder
                    : ((int) $right->submission_id <=> (int) $left->submission_id);
            })
            ->values();
    }

    private function submissionSummary(string $courseId, Collection $assessments, string $sort, array $dateRange): array
    {
        $emptyTotals = ['submitted' => 0, 'reviewed' => 0, 'pending' => 0];

        if ($assessments->isEmpty() || ! Schema::hasTable('assessment_submission')) {
            return [collect(), $emptyTotals];
        }

        $rows = DB::table('assessment_submission as submission')
            ->join('enroll_guru_baru as enrollment', 'submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.course_id', $courseId)
            ->whereIn('submission.assessment_id', $assessments->pluck('assessment_id'))
            ->select('submission.assessment_id', 'submission.submitted_at', 'submission.status')
            ->get()
            ->filter(fn ($row) => $this->dateMatches($row->submitted_at, $dateRange))
            ->groupBy('assessment_id');

        $results = $assessments->map(function ($assessment) use ($rows) {
            $submitted = $rows->get($assessment->assessment_id, collect())
                ->filter(fn ($row) => ! empty($row->submitted_at))
                ->values();
            $reviewed = $submitted->filter(
                fn ($row) => strtolower(trim((string) ($row->status ?? ''))) === 'reviewed'
            );

            $assessment->submitted_count = $submitted->count();
            $assessment->reviewed_count = $reviewed->count();
            $assessment->pending_count = $submitted->count() - $reviewed->count();
            $assessment->latest_submission_at = $submitted->max('submitted_at');
            $assessment->latest_submission_label = $this->formatDateTime($assessment->latest_submission_at);

            return $assessment;
        });

        $totals = [
            'submitted' => (int) $results->sum('submitted_count'),
            'reviewed' => (int) $results->sum('reviewed_count'),
            'pending' => (int) $results->sum('pending_count'),
        ];
        $results = $results->filter(fn ($assessment) => $assessment->submitted_count > 0);
        $results = match ($sort) {
            'submitted' => $results->sortByDesc('submitted_count')->values(),
            'pending' => $results->sortByDesc('pending_count')->values(),
            'latest' => $results->sortByDesc(fn ($assessment) => $assessment->latest_submission_at ?? '')->values(),
            default => $results->sortBy(fn ($assessment) => strtolower((string) $assessment->title))->values(),
        };

        return [$results, $totals];
    }

    private function headlineMetrics(
        object $training,
        array $attendance,
        array $quizTotals,
        int $pendingReviewCount,
        int $sessionCount
    ): Collection {
        $metrics = collect([[
            'label' => 'Participants',
            'value' => (string) $training->participant_count,
            'note' => 'Enrolled in this training',
            'tone' => 'blue',
            'icon' => 'bi-people',
        ]]);

        $metrics->push([
            'label' => 'Sessions',
            'value' => (string) $sessionCount,
            'note' => 'Assigned sessions',
            'tone' => 'blue',
            'icon' => 'bi-calendar3',
        ]);

        if ($training->training_type === 'Simple Training') {
            if ($attendance['available']) {
                $metrics->push([
                    'label' => 'Attendance Coverage',
                    'value' => $attendance['rate'].'%',
                    'note' => 'Canonical records / participant-session slots',
                    'tone' => 'green',
                    'icon' => 'bi-person-check',
                ]);
                $metrics->push([
                    'label' => 'Recorded Attendance',
                    'value' => (string) $attendance['counts']['Recorded'],
                    'note' => 'Canonical owner records',
                    'tone' => 'blue',
                    'icon' => 'bi-clipboard-check',
                ]);
            }

            return $metrics->take(4);
        }

        if ($quizTotals['attempts'] > 0) {
            $metrics->push([
                'label' => 'Quiz Attempts',
                'value' => (string) $quizTotals['attempts'],
                'note' => 'Submitted quiz attempts',
                'tone' => 'blue',
                'icon' => 'bi-ui-checks-grid',
            ]);
        }

        if ($quizTotals['average_score'] !== null) {
            $metrics->push([
                'label' => 'Average Quiz Score',
                'value' => number_format($quizTotals['average_score'], 1).'%',
                'note' => 'Across graded attempts',
                'tone' => 'green',
                'icon' => 'bi-graph-up-arrow',
            ]);
        }

        if ($pendingReviewCount > 0) {
            $metrics->push([
                'label' => 'Awaiting Review',
                'value' => (string) $pendingReviewCount,
                'note' => 'Submitted work to review',
                'tone' => 'amber',
                'icon' => 'bi-inbox',
            ]);
        }

        return $metrics->take(4);
    }

    private function emptyAttendanceSummary(): array
    {
        return [
            'available' => false,
            'total' => 0,
            'rate' => null,
            'counts' => [
                'Recorded' => 0,
                'Not Recorded' => 0,
            ],
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

    private function resolveDateRange(Request $request): array
    {
        $preset = $this->validatedOption((string) $request->query('report_date', 'all'), ['all', '7', '30', 'custom'], 'all');
        if ($preset === '7' || $preset === '30') {
            return ['preset' => $preset, 'from' => Carbon::today()->subDays((int) $preset - 1)->toDateString(), 'to' => Carbon::today()->toDateString(), 'custom' => false];
        }
        if ($preset === 'custom') {
            try {
                $from = Carbon::createFromFormat('Y-m-d', (string) $request->query('date_from'))->startOfDay();
                $to = Carbon::createFromFormat('Y-m-d', (string) $request->query('date_to'))->startOfDay();
                if ($from->gt($to)) {
                    return ['preset' => 'all', 'from' => null, 'to' => null, 'custom' => false];
                }

                return ['preset' => 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'custom' => true];
            } catch (\Throwable $e) {
                return ['preset' => 'all', 'from' => null, 'to' => null, 'custom' => false];
            }
        }

        return ['preset' => 'all', 'from' => null, 'to' => null, 'custom' => false];
    }

    private function validatedOption(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function formatPeriod($start, $end): ?string
    {
        if (! $start && ! $end) {
            return null;
        }

        $startDate = $this->formatDate($start);
        $endDate = $this->formatDate($end);

        return $startDate === $endDate || ! $endDate
            ? $startDate
            : $startDate.' - '.$endDate;
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            return null;
        }
    }
}
