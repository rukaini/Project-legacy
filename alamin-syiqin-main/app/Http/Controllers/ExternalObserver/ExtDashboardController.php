<?php

namespace App\Http\Controllers\ExternalObserver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExtDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();

        abort_if(!$teacher, 403, 'Unauthorized access');

        $externalObserver = DB::table('external_observer')
            ->where('teacherID', $teacher->teacherID)
            ->first();

        abort_if(!$externalObserver, 404);

        $search = trim((string) $request->input('search', ''));
        $month = $request->input('month', 'all');
        $year = $request->input('year', 'all');
        $level = $request->input('level', 'all');


        // Get this External Observer's assignments
        $assignments = DB::table('observer_assignment')
            ->join(
                'guru_new',
                'observer_assignment.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->leftJoin(
                'school',
                'guru_new.schoolID',
                '=',
                'school.schoolID'
            )
            ->where(
                'observer_assignment.assignmentType',
                'external'
            )
            ->where(
                'observer_assignment.externalObserverID',
                $externalObserver->externalObserverID
            )
            ->select(
                'observer_assignment.*',
                'guru_new.gn_name',
                'school.schoolName'
            )
            ->orderByDesc('observer_assignment.assignedDate')
            ->orderByDesc('observer_assignment.assignmentID')
            ->get();

        $overallCache = [];

        // evaluation information
        foreach ($assignments as $assignment) {

            $gnId = $assignment->gn_id;

            $attemptNo = max(
                1,
                (int) ($assignment->attemptNo ?? 1)
            );

            $assignment->attempt_no = $attemptNo;

            // Only get PDPC belonging to this assignment,
            $ownExternal = DB::table('pdpc_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $assignment->externalObserverID
                )
                ->where('attempt_no', $attemptNo)
                ->orderByDesc('responseID')
                ->first();

            // Only get matching Feedback for this attempt.
            $ownFeedback = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $assignment->externalObserverID
                )
                ->where('attempt_no', $attemptNo)
                ->orderByDesc('responseID')
                ->first();

            // Form statuses describe submission only.
            // A submitted REPEAT PDPC is still a completed form.
            $assignment->pdpc_status =
                $this->getStatus($ownExternal);

            $assignment->feedback_status =
                $this->getStatus($ownFeedback);

            // Preserve this evaluator's original result.
            $assignment->external_result =
                $ownExternal?->status === 'Submitted'
                ? $this->getPdpcResult($ownExternal)
                : null;

            $assignment->result =
                $assignment->external_result;

            // Form completion for this assignment.
            $assignment->completed_count =
                ($assignment->pdpc_status === 'Completed' ? 1 : 0)
                +
                ($assignment->feedback_status === 'Completed' ? 1 : 0);

            $assignment->total_forms = 2;

            $assignment->has_draft =
                $assignment->pdpc_status === 'Draft'
                ||
                $assignment->feedback_status === 'Draft';

            $assignment->progress = round(
                ($assignment->completed_count / 2) * 100
            );

            // Check the GN's overall evaluation across
            // all External Observers and POST evaluators.

            if (!isset($overallCache[$gnId])) {

                $overallCache[$gnId] =
                    $this->getOverallEvaluationStatus($gnId);
            }

            $overall = $overallCache[$gnId];

            $assignment->overall_status =
                $overall['status'];

            $assignment->overall_completed =
                $overall['completed'];

            $assignment->passed_at =
                $overall['passed_at'];

            $assignment->latest_external_result =
                $overall['latest_external_result'];

            $assignment->is_completed =
                $assignment->overall_completed;

            $assignment->is_repeat =
                $assignment->overall_status === 'Repeat Required';

            // Preserve the original assignment-specific
            // completion flag for compatibility.

            $assignment->is_fully_completed =
                $assignment->external_result === 'PASS'
                &&
                $assignment->pdpc_status === 'Completed'
                &&
                $assignment->feedback_status === 'Completed';
        }

        // Dashboard summary
        $latestAssignments = $assignments
            ->unique('gn_id')
            ->values();

        $totalAssigned = $latestAssignments->count();

        $completedCount = $latestAssignments
            ->filter(
                fn($assignment) => $assignment->is_completed
            )
            ->count();

        $repeatCount = $latestAssignments
            ->filter(
                fn($assignment) => $assignment->is_repeat
            )
            ->count();

        $ongoingCount = $latestAssignments
            ->filter(
                fn($assignment) =>
                !$assignment->is_completed
                    &&
                    !$assignment->is_repeat
            )
            ->count();

        $recentEvaluations = $latestAssignments->take(5);

        // Available PDPC result years
        $years = DB::table('pdpc_response')
            ->where(
                'externalObserverID',
                $externalObserver->externalObserverID
            )
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->whereNotNull('observation_date')
            ->selectRaw('YEAR(observation_date) AS year')
            ->pluck('year')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();


        // Historical EXTERNAL PDPC results
        $pdpcResultQuery = DB::table('pdpc_response')
            ->join(
                'guru_new',
                'pdpc_response.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->where(
                'pdpc_response.externalObserverID',
                $externalObserver->externalObserverID
            )
            ->where(
                'pdpc_response.observation_stage',
                'EXTERNAL'
            )
            ->where(
                'pdpc_response.status',
                'Submitted'
            );

        if ($search !== '') {

            $pdpcResultQuery->where(
                'guru_new.gn_name',
                'like',
                "%{$search}%"
            );
        }

        if ($month !== 'all') {

            $pdpcResultQuery->whereMonth(
                'pdpc_response.observation_date',
                (int) $month
            );
        }

        if ($year !== 'all') {

            $pdpcResultQuery->whereYear(
                'pdpc_response.observation_date',
                (int) $year
            );
        }

        // Filter by achievement level.
        if ($level !== 'all') {

            $pdpcResultQuery->where(
                'pdpc_response.achievement_level',
                $level
            );
        }

        $pdpcResults = $pdpcResultQuery
            ->select(
                'pdpc_response.responseID',
                'pdpc_response.attempt_no',
                'pdpc_response.observation_date',
                'pdpc_response.class_name',
                'pdpc_response.subject_name',
                'pdpc_response.percentage',
                'pdpc_response.achievement_level',
                'pdpc_response.result',
                'guru_new.gn_id',
                'guru_new.gn_name'
            )
            ->orderByDesc('pdpc_response.observation_date')
            ->orderByDesc('pdpc_response.responseID')
            ->paginate(10, ['*'], 'result_page')
            ->withQueryString();

        return view(
            'external.dashboard',
            compact(
                'totalAssigned',
                'ongoingCount',
                'completedCount',
                'repeatCount',
                'recentEvaluations',
                'pdpcResults',
                'years',
                'search',
                'month',
                'year',
                'level'
            )
        );
    }

    // Overall evaluation status
    private function getOverallEvaluationStatus(string $gnId): array
    {
        $result = [
            'status' => 'In Progress',
            'completed' => false,
            'passed_at' => null,
            'latest_external_result' => null,
        ];

        // PRE is mandatory.
        $preSubmitted = DB::table('pre_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->exists();

        if (!$preSubmitted) {
            return $result;
        }

        // Get all submitted EXTERNAL attempts,including attempts by other evaluators.

        $externalResponses = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        $latestExternal = $externalResponses->first();

        $result['latest_external_result'] =
            $this->getPdpcResult($latestExternal);

        $hasExternalRepeatWithFeedback = false;

        foreach ($externalResponses as $pdpc) {

            // Match Feedback to the same evaluator
            // and the same EXTERNAL attempt.
            $feedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $pdpc->externalObserverID
                )
                ->where('attempt_no', $pdpc->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            if (!$feedbackSubmitted) {
                continue;
            }

            $pdpcResult = $this->getPdpcResult($pdpc);

            // PASS by any External Observer completes
            // the EXTERNAL pathway.
            if ($pdpcResult === 'PASS') {

                $result['status'] = 'Completed';
                $result['completed'] = true;
                $result['passed_at'] = 'EXTERNAL';

                return $result;
            }

            if ($pdpcResult === 'REPEAT') {
                $hasExternalRepeatWithFeedback = true;
            }
        }

        // POST can only complete the workflow after
        // a valid EXTERNAL REPEAT with Feedback.
        $postResponses = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        if ($hasExternalRepeatWithFeedback) {

            foreach ($postResponses as $pdpc) {

                if ($this->getPdpcResult($pdpc) !== 'PASS') {
                    continue;
                }

                $feedbackSubmitted = DB::table('post_response')
                    ->where('gn_id', $gnId)
                    ->where('observation_stage', 'POST')
                    ->where(
                        'observerID',
                        $pdpc->observerID
                    )
                    ->where('attempt_no', $pdpc->attempt_no)
                    ->where('status', 'Submitted')
                    ->exists();

                if ($feedbackSubmitted) {

                    $result['status'] = 'Completed';
                    $result['completed'] = true;
                    $result['passed_at'] = 'POST';

                    return $result;
                }
            }
        }

        // Check latest submitted POST attempt.
        $latestPost = $postResponses->first();

        if ($latestPost) {

            $postFeedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'POST')
                ->where(
                    'observerID',
                    $latestPost->observerID
                )
                ->where('attempt_no', $latestPost->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            if (
                $this->getPdpcResult($latestPost) === 'REPEAT'
                &&
                $postFeedbackSubmitted
            ) {

                $result['status'] = 'Repeat Required';

                return $result;
            }

            // POST PASS without Feedback is not completed.
            if (
                $this->getPdpcResult($latestPost) === 'PASS'
                &&
                !$postFeedbackSubmitted
            ) {

                $result['status'] = 'In Progress';

                return $result;
            }
        }

        // Check latest submitted EXTERNAL attempt.
        if ($latestExternal) {

            $externalFeedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $latestExternal->externalObserverID
                )
                ->where('attempt_no', $latestExternal->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            if (
                $this->getPdpcResult($latestExternal) === 'REPEAT'
                &&
                $externalFeedbackSubmitted
            ) {

                $result['status'] = 'Repeat Required';

                return $result;
            }
        }

        // No submitted EXTERNAL evaluation yet.

        if (!$latestExternal) {

            $result['status'] = 'Pending';
        }

        return $result;
    }


    // Convert form response into display status
    private function getStatus($response): string
    {
        if (!$response) {
            return 'Pending';
        }

        if ($response->status === 'Draft') {
            return 'Draft';
        }

        if ($response->status === 'Submitted') {
            return 'Completed';
        }

        return 'Pending';
    }

    // Read PDPC result without changing stored data
    private function getPdpcResult($response): ?string
    {
        if (!$response) {
            return null;
        }

        $result = strtoupper(
            trim((string) ($response->result ?? ''))
        );

        if (in_array($result, ['PASS', 'REPEAT'], true)) {
            return $result;
        }

        if (
            !isset($response->percentage)
            ||
            $response->percentage === null
        ) {
            return null;
        }

        return (float) $response->percentage >= 85
            ? 'PASS'
            : 'REPEAT';
    }
}
