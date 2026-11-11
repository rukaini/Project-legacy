<?php

namespace App\Http\Controllers;

use App\Services\TeachingPlanService;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function trainerDashboard(TeachingPlanService $teachingPlans)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerReference = (string) session('user_id');
        $trainerId = SharedTrainingSchema::currentTrainerId();

        // Trainer name for the welcome line (trainer table is Nureen's; we just read it)
        $trainer = SharedTrainingSchema::trainer($trainerReference);
        $trainerName = $trainer->trainer_name ?? 'Trainer';

        // A course is "assigned" to this trainer when they appear in session_trainer
        // for at least one of that course's sessions (the two-trainer rule).
        $courseIds = SharedTrainingSchema::assignedCourseIds($trainerReference)->all();

        $totalAssignedCourses = count($courseIds);

        $currentTeachingPlans = $teachingPlans->currentForCourses(collect($courseIds));
        $teachingPlansSubmitted = collect($courseIds)
            ->filter(fn ($courseId) => $currentTeachingPlans->has(
                $teachingPlans->scopeKey((string) $courseId, $trainerId)
            ))
            ->count();

        // Pending review = submitted work still needing MANUAL grading.
        // Quizzes are auto-scored, so they are excluded. Adjust the type rule
        // later if your assessment.type values differ.
        $pendingGrades = DB::table('assessment_submission')
            ->join('assessment', 'assessment_submission.assessment_id', '=', 'assessment.assessment_id')
            ->whereIn('assessment.course_id', $courseIds ?: [0])
            ->whereNull('assessment_submission.graded_at')
            ->where(function ($q) {
                $q->whereNull('assessment.type')
                    ->orWhere('assessment.type', '<>', 'quiz');
            })
            ->count();

        // Trainer's own training proposals (recent list + pending count)
        $recentProposals = DB::table('course_proposal')
            ->where('trainer_id', $trainerId)
            ->select(
                'proposal_id',
                'proposed_title',
                'status',
                'proposed_start_date',
                'proposed_end_date',
                'created_at'
            )
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $pendingProposals = DB::table('course_proposal')
            ->where('trainer_id', $trainerId)
            ->where('status', 'Pending')
            ->count();

        // Shared column list for the session queries
        $assignedSessions = function () use ($trainerReference) {
            $query = DB::table('course as course');
            SharedTrainingSchema::joinCourseSessions($query);
            SharedTrainingSchema::joinSessionAssignments($query);
            SharedTrainingSchema::constrainAssignmentToTrainer($query, $trainerReference);

            return $query
                ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
                ->selectRaw(SharedTrainingSchema::sessionDateExpression().' AS date')
                ->selectRaw(SharedTrainingSchema::sessionStartExpression().' AS time')
                ->selectRaw(SharedTrainingSchema::sessionStartExpression().' AS start_time')
                ->selectRaw(SharedTrainingSchema::sessionEndExpression().' AS end_time')
                ->addSelect('course_session.location')
                ->selectRaw('NULL AS session_type')
                ->selectRaw(SharedTrainingSchema::sessionNameExpression().' AS session_title')
                ->selectRaw(SharedTrainingSchema::courseInternalExpression().' AS course_id')
                ->selectRaw(SharedTrainingSchema::courseNameExpression().' AS course_name')
                ->selectRaw(SharedTrainingSchema::courseCategoryExpression().' AS course_category')
                ->selectRaw(SharedTrainingSchema::courseTypeExpression().' AS training_mode');
        };

        $nextSession = $assignedSessions()
            ->whereRaw('DATE('.SharedTrainingSchema::sessionDateExpression().') >= ?', [now()->toDateString()])
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->first();

        $upcomingSessions = $assignedSessions()
            ->whereRaw('DATE('.SharedTrainingSchema::sessionDateExpression().') >= ?', [now()->toDateString()])
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->limit(4)
            ->get();

        // Assigned training table — real session + enrolment counts, no fake progress.
        $assignedCourses = CanonicalTrainingQuery::courses()
            ->whereIn(SharedTrainingSchema::courseInternalExpression(), $courseIds ?: [''])
            ->orderByRaw(SharedTrainingSchema::courseNameExpression())
            ->limit(8)
            ->get()
            ->each(function (object $course): void {
                $course->total_sessions = SharedTrainingSchema::constrainSessionsToCourse(
                    DB::table('course_session as course_session'),
                    $course
                )->count();
                $course->total_participants = SharedTrainingSchema::constrainEnrollmentToCourse(
                    CanonicalEnrollment::query('enrollment'),
                    $course
                )->count();
            });

        return view('trainer.dashboard', compact(
            'trainerName',
            'totalAssignedCourses',
            'teachingPlansSubmitted',
            'pendingGrades',
            'recentProposals',
            'pendingProposals',
            'nextSession',
            'upcomingSessions',
            'assignedCourses'
        ));
    }
    // =============================================================================
    //  Replace the existing adminDashboard() method inside
    //  app/Http/Controllers/DashboardController.php with this version.
    //  Same access guard, more data — every number comes from real tables.
    // =============================================================================

    public function adminDashboard(TeachingPlanService $teachingPlans)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $today = Carbon::now()->startOfDay();
        $weekAgo = Carbon::now()->subDays(7);
        $monthAgo = Carbon::now()->subDays(30);

        // ---- KPI counts ----
        $totalTrainers = DB::table('trainer')->count();
        $totalCourses = DB::table('course')->count();
        $totalParticipants = CanonicalEnrollment::query()->count();
        $pendingProposals = DB::table('course_proposal')->where('status', 'Pending')->count();
        $teachingPlansNeedReview = $teachingPlans->adminNeedsReviewCount();

        // ---- Active-this-week trainers (from trainer_activity_log) ----
        $activeTrainersWeek = 0;
        if (Schema::hasTable('trainer_activity_log')) {
            $activeTrainersWeek = TrainerActivityLogger::scopeMeaningful(DB::table('trainer_activity_log'))
                ->where('created_at', '>=', $weekAgo)
                ->distinct('trainer_id')
                ->count('trainer_id');
        }

        // ---- Trainings: ongoing vs completed-this-month ----
        // Ongoing = a course that has at least one held session AND at least one upcoming session
        $courseRows = CanonicalTrainingQuery::courses()->get()->each(function (object $course): void {
            $sessions = SharedTrainingSchema::constrainSessionsToCourse(
                CanonicalTrainingQuery::sessions('course_session'),
                $course
            )->get();
            $course->total_sessions = $sessions->count();
            $course->held_sessions = $sessions->filter(
                fn (object $session): bool => $session->date && Carbon::parse($session->date)->lte(now())
            )->count();
            $course->last_date = $sessions->pluck('date')->filter()->max();
        });
        $ongoingTrainings = 0;
        $completedThisMonth = 0;
        foreach ($courseRows as $c) {
            $t = (int) $c->total_sessions;
            $h = (int) $c->held_sessions;
            if ($t > 0 && $h > 0 && $h < $t) {
                $ongoingTrainings++;
            }
            if ($t > 0 && $h >= $t && $c->last_date && Carbon::parse($c->last_date)->gte($monthAgo)) {
                $completedThisMonth++;
            }
        }

        // ---- Participants joined this month ----
        $newParticipantsMonth = CanonicalEnrollment::query()
            ->where('enrolled_at', '>=', $monthAgo)
            ->count();

        // ---- Pending proposals older than 7 days (the urgency number) ----
        $stalePendingProposals = DB::table('course_proposal')
            ->where('status', 'Pending')
            ->where('created_at', '<=', $weekAgo)
            ->count();

        // ---- Today's sessions + the next upcoming ones in a single sensible list ----
        $sessionListing = function () {
            $query = DB::table('course as course');
            SharedTrainingSchema::joinCourseSessions($query);

            return $query
                ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
                ->selectRaw(SharedTrainingSchema::sessionNameExpression().' AS session_title')
                ->selectRaw(SharedTrainingSchema::sessionDateExpression().' AS date')
                ->selectRaw(SharedTrainingSchema::sessionStartExpression().' AS start_time')
                ->selectRaw('NULL AS session_type')
                ->selectRaw(SharedTrainingSchema::courseInternalExpression().' AS course_id')
                ->selectRaw(SharedTrainingSchema::courseNameExpression().' AS course_name');
        };

        $todaySessions = $sessionListing()
            ->whereRaw('DATE('.SharedTrainingSchema::sessionDateExpression().') = ?', [$today->toDateString()])
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->get();

        // Upcoming sessions: next 7 days first; if none, fall back to the next 8 future ones
        $upcomingSessions = $sessionListing()
            ->whereRaw('DATE('.SharedTrainingSchema::sessionDateExpression().') > ?', [$today->toDateString()])
            ->whereRaw('DATE('.SharedTrainingSchema::sessionDateExpression().') <= ?', [$today->copy()->addDays(7)->toDateString()])
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->limit(8)->get();

        $upcomingWindow = 'this week';
        if ($upcomingSessions->isEmpty()) {
            $upcomingSessions = $sessionListing()
                ->whereRaw('DATE('.SharedTrainingSchema::sessionDateExpression().') > ?', [$today->toDateString()])
                ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
                ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
                ->limit(8)->get();
            $upcomingWindow = $upcomingSessions->isEmpty() ? 'this week' : 'soon';
        }

        // ---- Trainers per session (so we can show who's running each) ----
        $sessionIds = $todaySessions->pluck('session_id')->merge($upcomingSessions->pluck('session_id'))->unique()->values();
        $trainerMap = collect();
        if ($sessionIds->isNotEmpty()) {
            $trainerQuery = DB::table('course_session as course_session');
            SharedTrainingSchema::joinSessionAssignments($trainerQuery);
            SharedTrainingSchema::joinAssignmentTrainers($trainerQuery);
            $trainerMap = $trainerQuery
                ->whereIn(SharedTrainingSchema::sessionInternalExpression(), $sessionIds)
                ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
                ->selectRaw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
                ->get()
                ->groupBy('session_id');
        }
        $attachTrainers = function ($rows) use ($trainerMap) {
            return $rows->map(function ($s) use ($trainerMap) {
                $s->trainer_names = isset($trainerMap[$s->session_id])
                    ? $trainerMap[$s->session_id]->pluck('trainer_name')->all()
                    : [];

                return $s;
            });
        };
        $todaySessions = $attachTrainers($todaySessions);
        $upcomingSessions = $attachTrainers($upcomingSessions);

        // ---- Proposals awaiting review (newest first, with submitting trainer) ----
        $proposalsToReview = DB::table('course_proposal')
            ->leftJoin('trainer', 'course_proposal.trainer_id', '=', 'trainer.'.SharedTrainingSchema::trainerInternalColumn())
            ->where('course_proposal.status', 'Pending')
            ->select(
                'course_proposal.proposal_id',
                'course_proposal.proposed_title',
                'course_proposal.training_mode',
                'course_proposal.delivery_mode',
                'course_proposal.created_at',
                DB::raw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
            )
            ->orderBy('course_proposal.created_at') // OLDEST pending first — backlog clearing
            ->limit(6)
            ->get();

        // ---- Recent trainer activity ----
        $recentActivity = collect();
        if (Schema::hasTable('trainer_activity_log')) {
            $recentActivity = TrainerActivityLogger::scopeMeaningful(DB::table('trainer_activity_log'))
                ->leftJoin('trainer', 'trainer_activity_log.trainer_id', '=', 'trainer.'.SharedTrainingSchema::trainerInternalColumn())
                ->select(
                    'trainer_activity_log.action',
                    'trainer_activity_log.detail',
                    'trainer_activity_log.created_at',
                    DB::raw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
                )
                ->orderByDesc('trainer_activity_log.created_at')
                ->limit(12)
                ->get();
        }

        return view('admin.dashboard', compact(
            'totalTrainers', 'totalCourses', 'totalParticipants', 'pendingProposals',
            'activeTrainersWeek', 'ongoingTrainings', 'completedThisMonth',
            'newParticipantsMonth', 'stalePendingProposals', 'teachingPlansNeedReview',
            'todaySessions', 'upcomingSessions', 'upcomingWindow',
            'proposalsToReview', 'recentActivity'
        ));
    }
}
