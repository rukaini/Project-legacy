<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Training\TrainingProgressService;

class PrincipalDashboardController extends Controller
{
    public function index(Request $request, TrainingProgressService $trainingProgressService)
    {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403, 'Unauthorized access');

        $school = DB::table('school')
            ->where('schoolID', $principal->schoolID)
            ->first();

        $search = trim((string) $request->input('search', ''));
        $month = $request->input('month', 'all');
        $year = $request->input('year', 'all');
        $status = $request->input('status', 'all');


        $teachers = DB::table('guru_new')
            ->where('schoolID', $principal->schoolID)
            ->whereIn('current_status', ['Active', 'Complete'])
            ->orderBy('gn_name')
            ->get();

        foreach ($teachers as $teacher) {
            $evaluation = $this->getEvaluationData($teacher->gn_id);

            foreach ($evaluation as $key => $value) {
                $teacher->{$key} = $value;
            }
        }


        // DASHBOARD SUMMARY
        $summary = [
            'total' => $teachers->count(),

            'completed' => $teachers
                ->where('evaluation_status', 'Completed')
                ->count(),

            'in_progress' => $teachers
                ->where('evaluation_status', 'In Progress')
                ->count(),

            'repeat_required' => $teachers
                ->where('evaluation_status', 'Repeat Required')
                ->count(),
        ];


        // TRAINING & FEEDBACK OVERVIEW
        // Include Active and Complete teachers from Principal's school.

        $trainingTeachers = DB::table('guru_new')
            ->where('schoolID', $principal->schoolID)
            ->whereIn('current_status', ['Active', 'Complete'])
            ->select(
                'gn_id',
                'gn_name',
                'current_status',
                'appointed_date'
            )
            ->get();

        $trainingSummary = [
            'in_progress' => 0,
            'completed' => 0,
            'feedback_completed' => 0,
        ];

        $trainingRows = collect();

        foreach ($trainingTeachers as $trainingTeacher) {

            $courses = collect(
                $trainingProgressService->getProgress(
                    $trainingTeacher->gn_id
                )
            );

            $totalCourses = $courses->count();

            // TRAINING COMPLETION
            // All sessions must have Approved attendance.
            $completedTraining = $courses
                ->filter(function ($course) {

                    $totalSessions = (int) (
                        $course['total_sessions'] ?? 0
                    );

                    $approvedSessions = (int) (
                        $course['approved_sessions'] ?? 0
                    );

                    return $totalSessions > 0
                        && $approvedSessions >= $totalSessions;
                })
                ->count();

            // FEEDBACK COMPLETION
            $completedFeedback = $courses
                ->filter(function ($course) {
                    return (bool) (
                        $course['feedback_completed'] ?? false
                    );
                })
                ->count();

            $trainingDone = $totalCourses > 0
                && $completedTraining === $totalCourses;

            $feedbackDone = $totalCourses > 0
                && $completedFeedback === $totalCourses;

            $hasStarted = $courses->contains(
                function ($course) {
                    return (int) (
                        $course['approved_sessions'] ?? 0
                    ) > 0
                        || (bool) (
                            $course['feedback_completed'] ?? false
                        );
                }
            );

            // SUMMARY COUNTS
            if ($trainingDone) {
                $trainingSummary['completed']++;
            } elseif ($hasStarted) {
                $trainingSummary['in_progress']++;
            }

            if ($feedbackDone) {
                $trainingSummary['feedback_completed']++;
            }

            // TRAINING PROGRESS
            $trainingTeacher->total_training = $totalCourses;
            $trainingTeacher->completed_training = $completedTraining;

            $trainingTeacher->training_percentage = $totalCourses > 0
                ? round(($completedTraining / $totalCourses) * 100)
                : 0;

            // FEEDBACK PROGRESS
            $trainingTeacher->total_feedback = $totalCourses;
            $trainingTeacher->completed_feedback = $completedFeedback;

            $trainingTeacher->feedback_percentage = $totalCourses > 0
                ? round(($completedFeedback / $totalCourses) * 100)
                : 0;

            // OVERALL TRAINING STATUS
            if ($trainingDone && $feedbackDone) {

                $trainingTeacher->overall_training_status = 'Completed';
            } elseif ($hasStarted) {

                $trainingTeacher->overall_training_status = 'In Progress';
            } else {

                $trainingTeacher->overall_training_status = 'Not Started';
            }

            // Get latest available completion-related timestamp.
            // This assumes the tables have updated_at columns.
            // A dedicated completion timestamp is more accurate.
            $attendanceDate = null;
            $feedbackDate = null;

            if (\Illuminate\Support\Facades\Schema::hasColumn(
                'attendance_guru_baru',
                'updated_at'
            )) {
                $attendanceDate = DB::table('attendance_guru_baru')
                    ->where('gn_id', $trainingTeacher->gn_id)
                    ->whereRaw(
                        'LOWER(TRIM(attendance_status)) = ?',
                        ['approved']
                    )
                    ->max('updated_at');
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn(
                'course_participant',
                'updated_at'
            )) {
                $feedbackDate = DB::table('course_participant')
                    ->where('gn_id', $trainingTeacher->gn_id)
                    ->where('isFeedbackCompleted', 1)
                    ->max('updated_at');
            }

            $trainingTeacher->training_completed_at = collect([
                $attendanceDate,
                $feedbackDate,
            ])->filter()->max();

            $trainingRows->push($trainingTeacher);
        }

        // LATEST 5 COMPLETED TRAINING + FEEDBACK
        $latestTrainingTeachers = $trainingRows
            ->filter(function ($teacher) {
                return $teacher->overall_training_status === 'Completed';
            })
            ->sort(function ($a, $b) {

                $dateCompare = strcmp(
                    (string) $b->training_completed_at,
                    (string) $a->training_completed_at
                );

                return $dateCompare !== 0
                    ? $dateCompare
                    : strcmp($b->gn_id, $a->gn_id);
            })
            ->take(5)
            ->values();


        // EVALUATION REPORT
        $reportCollection = $teachers->values();

        $reportPage = LengthAwarePaginator::resolveCurrentPage(
            'report_page'
        );

        $reportPerPage = 10;

        $reportTeachers = new LengthAwarePaginator(
            $reportCollection
                ->forPage($reportPage, $reportPerPage)
                ->values(),
            $reportCollection->count(),
            $reportPerPage,
            $reportPage,
            [
                'path' => $request->url(),
                'pageName' => 'report_page',
                'query' => $request->except('report_page'),
            ]
        );


        // AVAILABLE YEARS
        $preYears = DB::table('pre_response')
            ->join(
                'guru_new',
                'pre_response.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->where('guru_new.schoolID', $principal->schoolID)
            ->whereIn(
                'guru_new.current_status',
                ['Active', 'Complete']
            )
            ->where('pre_response.observation_stage', 'PRE')
            ->where('pre_response.status', 'Submitted')
            ->whereNotNull('pre_response.observation_date')
            ->selectRaw(
                'YEAR(pre_response.observation_date) as year'
            )
            ->pluck('year');

        $pdpcYears = DB::table('pdpc_response')
            ->join(
                'guru_new',
                'pdpc_response.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->where('guru_new.schoolID', $principal->schoolID)
            ->whereIn(
                'guru_new.current_status',
                ['Active', 'Complete']
            )
            ->where('pdpc_response.status', 'Submitted')
            ->whereIn(
                'pdpc_response.observation_stage',
                ['EXTERNAL', 'POST']
            )
            ->whereNotNull('pdpc_response.observation_date')
            ->selectRaw(
                'YEAR(pdpc_response.observation_date) as year'
            )
            ->pluck('year');

        $years = collect()
            ->merge($preYears)
            ->merge($pdpcYears)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $chartYear = $year !== 'all'
            ? (int) $year
            : (int) now()->year;


        // FILTER TEACHERS BY CURRENT EVALUATION STATUS
        $filteredTeacherIds = $teachers
            ->filter(function ($teacher) use ($status) {
                return $status === 'all'
                    || $teacher->evaluation_status === $status;
            })
            ->pluck('gn_id')
            ->values()
            ->all();


        // PRE PERFORMANCE QUERY
        $preQuery = DB::table('pre_response')
            ->join(
                'guru_new',
                'pre_response.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->where('guru_new.schoolID', $principal->schoolID)
            ->whereIn(
                'guru_new.current_status',
                ['Active', 'Complete']
            )
            ->where('pre_response.observation_stage', 'PRE')
            ->where('pre_response.status', 'Submitted')
            ->whereIn(
                'pre_response.gn_id',
                $filteredTeacherIds
            );

        if ($search !== '') {
            $preQuery->where(
                'guru_new.gn_name',
                'like',
                '%' . $search . '%'
            );
        }

        if ($month !== 'all') {
            $preQuery->whereMonth(
                'pre_response.observation_date',
                (int) $month
            );
        }

        if ($year !== 'all') {
            $preQuery->whereYear(
                'pre_response.observation_date',
                (int) $year
            );
        }

        $preResults = $preQuery
            ->select(
                'pre_response.gn_id',
                'pre_response.percentage',
                'pre_response.achievement_level',
                'pre_response.observation_date'
            )
            ->get();

        // PRE PERFORMANCE CHART
        $prePerformance = [
            'labels' => [
                'Weak (0-39%)',
                'Satisfactory (40-59%)',
                'Good (60-79%)',
                'Very Good (80-89%)',
                'Excellent (90-100%)',
            ],
            'values' => [0, 0, 0, 0, 0],
            'total' => 0,
        ];

        foreach ($preResults as $result) {
            if ($result->percentage === null) {
                continue;
            }

            $percentage = (float) $result->percentage;

            if ($percentage >= 90) {
                $prePerformance['values'][4]++;
            } elseif ($percentage >= 80) {
                $prePerformance['values'][3]++;
            } elseif ($percentage >= 60) {
                $prePerformance['values'][2]++;
            } elseif ($percentage >= 40) {
                $prePerformance['values'][1]++;
            } else {
                $prePerformance['values'][0]++;
            }

            $prePerformance['total']++;
        }


        // PDPC PERFORMANCE QUERY
        $pdpcQuery = DB::table('pdpc_response')
            ->join(
                'guru_new',
                'pdpc_response.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->where('guru_new.schoolID', $principal->schoolID)
            ->whereIn(
                'guru_new.current_status',
                ['Active', 'Complete']
            )
            ->where('pdpc_response.status', 'Submitted')
            ->whereIn(
                'pdpc_response.observation_stage',
                ['EXTERNAL', 'POST']
            )
            ->whereIn(
                'pdpc_response.gn_id',
                $filteredTeacherIds
            );

        if ($search !== '') {
            $pdpcQuery->where(
                'guru_new.gn_name',
                'like',
                '%' . $search . '%'
            );
        }

        if ($month !== 'all') {
            $pdpcQuery->whereMonth(
                'pdpc_response.observation_date',
                (int) $month
            );
        }

        if ($year !== 'all') {
            $pdpcQuery->whereYear(
                'pdpc_response.observation_date',
                (int) $year
            );
        } else {
            $pdpcQuery->whereYear(
                'pdpc_response.observation_date',
                $chartYear
            );
        }

        $pdpcResults = $pdpcQuery
            ->select(
                'pdpc_response.gn_id',
                'pdpc_response.observation_stage',
                'pdpc_response.percentage',
                'pdpc_response.observation_date'
            )
            ->get();

        // PDPC PERFORMANCE TREND
        $pdpcTrend = [
            'labels' => [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ],
            'external' => array_fill(0, 12, null),
            'post' => array_fill(0, 12, null),
            'external_count' => array_fill(0, 12, 0),
            'post_count' => array_fill(0, 12, 0),
        ];

        for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {

            $externalMonthly = $pdpcResults
                ->filter(function ($result) use ($monthNumber) {
                    return $result->observation_stage === 'EXTERNAL'
                        && $result->observation_date
                        && Carbon::parse(
                            $result->observation_date
                        )->month === $monthNumber
                        && $result->percentage !== null;
                });

            $postMonthly = $pdpcResults
                ->filter(function ($result) use ($monthNumber) {
                    return $result->observation_stage === 'POST'
                        && $result->observation_date
                        && Carbon::parse(
                            $result->observation_date
                        )->month === $monthNumber
                        && $result->percentage !== null;
                });

            if ($externalMonthly->isNotEmpty()) {
                $pdpcTrend['external'][$monthNumber - 1] =
                    round(
                        $externalMonthly->avg(
                            fn($result) => (float) $result->percentage
                        ),
                        2
                    );

                $pdpcTrend['external_count'][$monthNumber - 1] =
                    $externalMonthly
                    ->pluck('gn_id')
                    ->unique()
                    ->count();
            }

            if ($postMonthly->isNotEmpty()) {
                $pdpcTrend['post'][$monthNumber - 1] =
                    round(
                        $postMonthly->avg(
                            fn($result) => (float) $result->percentage
                        ),
                        2
                    );

                $pdpcTrend['post_count'][$monthNumber - 1] =
                    $postMonthly
                    ->pluck('gn_id')
                    ->unique()
                    ->count();
            }
        }

        $pdpcTrendHasData =
            collect($pdpcTrend['external'])
            ->filter(fn($value) => $value !== null)
            ->isNotEmpty()
            ||
            collect($pdpcTrend['post'])
            ->filter(fn($value) => $value !== null)
            ->isNotEmpty();


        // RETURN DASHBOARD
        return view('principal.dashboard', compact(
            'principal',
            'school',
            'summary',
            'reportTeachers',
            'search',
            'month',
            'year',
            'status',
            'years',
            'chartYear',
            'prePerformance',
            'pdpcTrend',
            'pdpcTrendHasData',
            'trainingSummary',
            'latestTrainingTeachers'
        ));
    }


    // CALCULATE EVALUATION PROGRESS FOR EACH GN
    private function getEvaluationData(string $gnId): array
    {

        // PRE
        $pre = DB::table('pre_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->orderByDesc('responseID')
            ->first();

        $preSubmitted = $pre !== null;

        // LATEST SUBMITTED EXTERNAL PDPC
        $externalPdpc = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        $externalFeedback = null;

        if ($externalPdpc) {
            $externalFeedback = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->where(
                    'attempt_no',
                    $externalPdpc->attempt_no
                )
                ->where(
                    'externalObserverID',
                    $externalPdpc->externalObserverID
                )
                ->orderByDesc('responseID')
                ->first();
        }

        $externalFeedbackSubmitted =
            $externalFeedback !== null;

        $externalPassed =
            $externalPdpc !== null &&
            $externalPdpc->result === 'PASS';

        $externalRepeat =
            $externalPdpc !== null &&
            $externalPdpc->result === 'REPEAT';

        $externalPathCompleted =
            $externalPassed &&
            $externalFeedbackSubmitted;

        // LATEST SUBMITTED POST PDPC
        $postPdpc = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        $postFeedback = null;

        if ($postPdpc) {
            $postFeedback = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'POST')
                ->where('status', 'Submitted')
                ->where(
                    'attempt_no',
                    $postPdpc->attempt_no
                )
                ->where(
                    'observerID',
                    $postPdpc->observerID
                )
                ->orderByDesc('responseID')
                ->first();
        }

        $postFeedbackSubmitted = $postFeedback !== null;

        $postPassed =
            $postPdpc !== null &&
            $postPdpc->percentage !== null &&
            (float) $postPdpc->percentage >= 85;

        $postRepeat =
            $postPdpc !== null &&
            $postPdpc->percentage !== null &&
            (float) $postPdpc->percentage < 85;

        $postPathCompleted =
            $postPassed &&
            $postFeedbackSubmitted;


        // STAGE PROGRESS
        $preCompleted = $preSubmitted ? 1 : 0;
        $preTotal = 1;

        $externalCompleted =
            ($externalPdpc !== null ? 1 : 0) +
            ($externalFeedbackSubmitted ? 1 : 0);

        $externalTotal = 2;

        $postCompleted =
            ($postPdpc !== null ? 1 : 0) +
            ($postFeedbackSubmitted ? 1 : 0);

        $postTotal = 2;

        // OVERALL PROGRESS
        // PRE + SUCCESSFUL PDPC + MATCHING FEEDBACK
        $preComponentCompleted = $preSubmitted;

        $successfulPathCompleted =
            $externalPathCompleted ||
            $postPathCompleted;

        $pdpcComponentCompleted =
            $successfulPathCompleted;

        $feedbackComponentCompleted =
            $successfulPathCompleted;

        $completedForms =
            ($preComponentCompleted ? 1 : 0) +
            ($pdpcComponentCompleted ? 1 : 0) +
            ($feedbackComponentCompleted ? 1 : 0);

        $totalForms = 3;

        $overallProgress = round(
            ($completedForms / $totalForms) * 100
        );


        // OVERALL STATUS
        if (
            $preSubmitted &&
            ($externalPathCompleted || $postPathCompleted)
        ) {
            $evaluationStatus = 'Completed';
        } elseif (
            $postRepeat &&
            $postFeedbackSubmitted
        ) {
            $evaluationStatus = 'Repeat Required';
        } elseif (
            $postRepeat
        ) {
            $evaluationStatus = 'In Progress';
        } elseif (
            $externalRepeat &&
            $externalFeedbackSubmitted &&
            !$postPdpc
        ) {
            $evaluationStatus = 'Repeat Required';
        } elseif (
            !$preSubmitted &&
            !$externalPdpc &&
            !$postPdpc
        ) {
            $evaluationStatus = 'Pending';
        } else {
            $evaluationStatus = 'In Progress';
        }

        // LAST EVALUATION DATE

        $dates = collect([
            $pre->observation_date ?? null,
            $externalPdpc->observation_date ?? null,
            $externalFeedback->observation_date ?? null,
            $postPdpc->observation_date ?? null,
            $postFeedback->observation_date ?? null,
        ])->filter();

        $lastEvaluationDate = $dates->isNotEmpty()
            ? $dates->max()
            : null;

        return [
            'pre_completed' => $preCompleted,
            'pre_total' => $preTotal,

            'external_completed' => $externalCompleted,
            'external_total' => $externalTotal,

            'post_completed' => $postCompleted,
            'post_total' => $postTotal,

            'completed_forms' => $completedForms,
            'total_forms' => $totalForms,
            'overall_progress' => $overallProgress,

            'evaluation_status' => $evaluationStatus,
            'last_evaluation_date' => $lastEvaluationDate,
        ];
    }
}
