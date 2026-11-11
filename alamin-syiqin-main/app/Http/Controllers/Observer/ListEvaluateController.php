<?php

namespace App\Http\Controllers\Observer;

use App\Http\Controllers\Controller;
use App\Models\Observer;
use App\Models\ExternalObserver;
use App\Models\PreResponse;
use App\Models\PdpcResponse;
use App\Models\PostResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ListEvaluateController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();

        abort_if(!$teacher, 403, 'Unauthorized access.');

        $observer = Observer::where(
            'teacherID',
            $teacher->teacherID
        )->first();

        $externalObserver = ExternalObserver::where(
            'teacherID',
            $teacher->teacherID
        )->first();

        $hasPostAssignment = DB::table('observer_assignment')
            ->where('assignmentType', 'post')
            ->where('postTeacherID', $teacher->teacherID)
            ->exists();

        abort_if(
            !$observer && !$externalObserver && !$hasPostAssignment,
            403,
            'You are not registered as an evaluator.'
        );

        $isObserver = !is_null($observer) || $hasPostAssignment;

        $status = $request->get('status', 'active');

        if (!in_array($status, ['active', 'completed', 'repeat'], true)) {
            $status = 'active';
        }

        if ($isObserver && $status === 'repeat') {
            $status = 'active';
        }

        $listRoute = $isObserver
            ? 'observer.list.evaluate'
            : 'external.list.evaluate';


        // Get assignments belonging to the logged-in user
        $query = DB::table('observer_assignment')
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
            ->select(
                'observer_assignment.*',
                'guru_new.gn_name',
                'school.schoolName'
            );

        if ($isObserver) {

            $query->where(function ($q) use ($observer, $teacher) {

                if ($observer) {
                    $q->where(function ($pre) use ($observer) {
                        $pre->where(
                            'observer_assignment.assignmentType',
                            'pre'
                        )->where(
                            'observer_assignment.observerID',
                            $observer->observerID
                        );
                    });
                }

                $method = $observer ? 'orWhere' : 'where';

                $q->{$method}(function ($post) use ($teacher) {
                    $post->where(
                        'observer_assignment.assignmentType',
                        'post'
                    )->where(
                        'observer_assignment.postTeacherID',
                        $teacher->teacherID
                    );
                });
            });
        } else {

            $query->where(
                'observer_assignment.assignmentType',
                'external'
            )->where(
                'observer_assignment.externalObserverID',
                $externalObserver->externalObserverID
            );
        }

        $allAssignments = $query
            ->orderByDesc('observer_assignment.assignedDate')
            ->orderByDesc('observer_assignment.assignmentID')
            ->get();

        // Load evaluation information for each assignment
        $overallCache = [];
        $latestAssignmentCache = [];

        foreach ($allAssignments as $assignment) {

            $gnId = $assignment->gn_id;

            $type = strtolower(
                (string) ($assignment->assignmentType ?? '')
            );

            $attemptNo = max(
                1,
                (int) ($assignment->attemptNo ?? 1)
            );

            $assignment->attempt_no = $attemptNo;

            $assignment->pre_status = 'Pending';
            $assignment->pdpc_status = 'Pending';
            $assignment->feedback_status = 'Pending';

            $assignment->completed_count = 0;
            $assignment->total_forms = 0;
            $assignment->post_completed_count = 0;

            $assignment->has_draft = false;
            $assignment->is_repeat = false;
            $assignment->is_fully_completed = false;

            $assignment->external_result = null;
            $assignment->latest_external_result = null;

            if (!isset($overallCache[$gnId])) {
                $overallCache[$gnId] =
                    $this->getOverallEvaluationStatus($gnId);
            }

            $overall = $overallCache[$gnId];

            $assignment->overall_completed =
                $overall['completed'];

            $assignment->overall_status =
                $overall['status'];

            $assignment->passed_at =
                $overall['passed_at'];

            // Check whether this assignment is the latest
            // assignment of the same evaluation stage.
            $cacheKey = $gnId . ':' . $type;

            if (!isset($latestAssignmentCache[$cacheKey])) {

                $latestAssignmentCache[$cacheKey] =
                    DB::table('observer_assignment')
                    ->where('gn_id', $gnId)
                    ->where('assignmentType', $type)
                    ->orderByDesc('attemptNo')
                    ->orderByDesc('assignmentID')
                    ->first();
            }

            $latestAssignment =
                $latestAssignmentCache[$cacheKey];

            $assignment->is_latest_attempt =
                $latestAssignment
                && (string) $latestAssignment->assignmentID
                === (string) $assignment->assignmentID;


            // PRE assignment
            if ($type === 'pre') {

                $preResponse = PreResponse::where('gn_id', $gnId)
                    ->where('observation_stage', 'PRE')
                    ->where(
                        'observerID',
                        $assignment->observerID
                    )
                    ->orderByDesc('responseID')
                    ->first();

                $assignment->pre_status =
                    $this->getFormStatus($preResponse);

                $assignment->completed_count =
                    $assignment->pre_status === 'Completed'
                    ? 1
                    : 0;

                $assignment->total_forms = 1;

                $assignment->has_draft =
                    $assignment->pre_status === 'Draft';

                $assignment->is_fully_completed =
                    $assignment->completed_count === 1;

                continue;
            }


            // POST assignment
            if ($type === 'post') {

                $preResponse = PreResponse::where('gn_id', $gnId)
                    ->where('observation_stage', 'PRE')
                    ->where('status', 'Submitted')
                    ->orderByDesc('responseID')
                    ->first();

                $assignment->pre_status =
                    $this->getFormStatus($preResponse);

                $postObserver = Observer::where(
                    'teacherID',
                    $assignment->postTeacherID
                )->first();

                $pdpcResponse = null;
                $feedbackResponse = null;

                if ($postObserver) {

                    $pdpcResponse = PdpcResponse::where(
                        'gn_id',
                        $gnId
                    )
                        ->where('observation_stage', 'POST')
                        ->where(
                            'observerID',
                            $postObserver->observerID
                        )
                        ->where('attempt_no', $attemptNo)
                        ->orderByDesc('responseID')
                        ->first();

                    $feedbackResponse = PostResponse::where(
                        'gn_id',
                        $gnId
                    )
                        ->where('observation_stage', 'POST')
                        ->where(
                            'observerID',
                            $postObserver->observerID
                        )
                        ->where('attempt_no', $attemptNo)
                        ->orderByDesc('responseID')
                        ->first();
                }

                $assignment->pdpc_status =
                    $this->getFormStatus($pdpcResponse);

                $assignment->feedback_status =
                    $this->getFormStatus($feedbackResponse);

                $assignment->post_completed_count =
                    ($assignment->pdpc_status === 'Completed' ? 1 : 0)
                    +
                    ($assignment->feedback_status === 'Completed' ? 1 : 0);

                $assignment->completed_count =
                    $assignment->post_completed_count;

                $assignment->total_forms = 2;

                $assignment->has_draft =
                    $assignment->pdpc_status === 'Draft'
                    ||
                    $assignment->feedback_status === 'Draft';

                $assignment->is_fully_completed =
                    $assignment->completed_count === 2;

                continue;
            }


            // EXTERNAL assignment

            if ($type === 'external') {

                $currentPdpcResponse = PdpcResponse::where(
                    'gn_id',
                    $gnId
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where(
                        'externalObserverID',
                        $assignment->externalObserverID
                    )
                    ->where('attempt_no', $attemptNo)
                    ->orderByDesc('responseID')
                    ->first();

                $currentFeedbackResponse = PostResponse::where(
                    'gn_id',
                    $gnId
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where(
                        'externalObserverID',
                        $assignment->externalObserverID
                    )
                    ->where('attempt_no', $attemptNo)
                    ->orderByDesc('responseID')
                    ->first();

                $latestPdpcSubmitted = PdpcResponse::where(
                    'gn_id',
                    $gnId
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->orderByDesc('attempt_no')
                    ->orderByDesc('responseID')
                    ->first();

                $assignment->pdpc_status =
                    $this->getFormStatus($currentPdpcResponse);

                $assignment->feedback_status =
                    $this->getFormStatus($currentFeedbackResponse);

                $assignment->external_result =
                    $this->getPdpcResult($currentPdpcResponse);

                $assignment->latest_external_result =
                    $this->getPdpcResult($latestPdpcSubmitted);

                $assignment->completed_count =
                    ($assignment->pdpc_status === 'Completed' ? 1 : 0)
                    +
                    ($assignment->feedback_status === 'Completed' ? 1 : 0);

                $assignment->total_forms = 2;

                $assignment->has_draft =
                    $assignment->pdpc_status === 'Draft'
                    ||
                    $assignment->feedback_status === 'Draft';

                $assignment->is_repeat =
                    $currentPdpcResponse?->status === 'Submitted'
                    &&
                    $assignment->external_result === 'REPEAT';

                $assignment->is_fully_completed =
                    $currentPdpcResponse?->status === 'Submitted'
                    &&
                    $assignment->external_result === 'PASS'
                    &&
                    $currentFeedbackResponse?->status === 'Submitted';

                continue;
            }
        }


        // Filter assignments
        if ($isObserver) {

            if ($status === 'completed') {

                $filtered = $allAssignments
                    ->filter(
                        fn($assignment) =>
                        $assignment->is_fully_completed
                    )
                    ->values();
            } else {

                $filtered = $allAssignments
                    ->filter(
                        fn($assignment) =>
                        !$assignment->is_fully_completed
                    )
                    ->values();
            }
        } else {

            if ($status === 'completed') {

                $filtered = $allAssignments
                    ->filter(function ($assignment) {

                        return $assignment->overall_completed;
                    })
                    ->values();
            } elseif ($status === 'repeat') {

                $filtered = $allAssignments
                    ->filter(function ($assignment) {

                        return
                            !$assignment->overall_completed
                            &&
                            $assignment->is_latest_attempt
                            &&
                            $assignment->is_repeat
                            &&
                            $assignment->feedback_status === 'Completed';
                    })
                    ->values();
            } else {

                $filtered = $allAssignments
                    ->filter(function ($assignment) {

                        return
                            !$assignment->overall_completed
                            &&
                            $assignment->is_latest_attempt
                            &&
                            !(
                                $assignment->is_repeat
                                &&
                                $assignment->feedback_status === 'Completed'
                            );
                    })
                    ->values();
            }
        }


        $page = max(1, (int) $request->get('page', 1));

        $perPage = 10;

        $assignments = new LengthAwarePaginator(
            $filtered
                ->forPage($page, $perPage)
                ->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view(
            'observer.list-evaluate',
            compact(
                'assignments',
                'status',
                'isObserver',
                'listRoute'
            )
        );
    }

    // Determine overall GN evaluation status
    private function getOverallEvaluationStatus(string $gnId): array
    {
        $result = [
            'status' => 'In Progress',
            'completed' => false,
            'passed_at' => null,
        ];

        // PRE must be submitted first.
        $preSubmitted = PreResponse::where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->exists();

        if (!$preSubmitted) {
            return $result;
        }

        // Load all submitted EXTERNAL attempts.
        $externalResponses = PdpcResponse::where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        $hasExternalRepeatWithFeedback = false;

        foreach ($externalResponses as $pdpc) {

            $feedbackSubmitted = PostResponse::where(
                'gn_id',
                $gnId
            )
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

            // A PASS by any assigned External Observer
            // completes the GN's EXTERNAL 

            if ($pdpcResult === 'PASS') {

                return [
                    'status' => 'Completed',
                    'completed' => true,
                    'passed_at' => 'EXTERNAL',
                ];
            }

            if ($pdpcResult === 'REPEAT') {
                $hasExternalRepeatWithFeedback = true;
            }
        }

        // POST can complete the workflow only after
        // a valid EXTERNAL REPEAT with feedback.

        if ($hasExternalRepeatWithFeedback) {

            $postResponses = PdpcResponse::where(
                'gn_id',
                $gnId
            )
                ->where('observation_stage', 'POST')
                ->where('status', 'Submitted')
                ->orderByDesc('attempt_no')
                ->orderByDesc('responseID')
                ->get();

            foreach ($postResponses as $pdpc) {

                if ($this->getPdpcResult($pdpc) !== 'PASS') {
                    continue;
                }

                $feedbackSubmitted = PostResponse::where(
                    'gn_id',
                    $gnId
                )
                    ->where('observation_stage', 'POST')
                    ->where(
                        'observerID',
                        $pdpc->observerID
                    )
                    ->where('attempt_no', $pdpc->attempt_no)
                    ->where('status', 'Submitted')
                    ->exists();

                if ($feedbackSubmitted) {

                    return [
                        'status' => 'Completed',
                        'completed' => true,
                        'passed_at' => 'POST',
                    ];
                }
            }
        }

        // Determine whether a repeat is still needed.
        $latestExternal = $externalResponses->first();

        $latestPost = PdpcResponse::where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        if ($latestPost) {

            $postFeedbackSubmitted = PostResponse::where(
                'gn_id',
                $gnId
            )
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
        }

        if ($latestExternal) {

            $externalFeedbackSubmitted = PostResponse::where(
                'gn_id',
                $gnId
            )
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
            }
        }

        return $result;
    }


    // Convert response into form status
    private function getFormStatus($response): string
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
