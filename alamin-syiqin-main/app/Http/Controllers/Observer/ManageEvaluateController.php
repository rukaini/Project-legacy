<?php

namespace App\Http\Controllers\Observer;

use App\Http\Controllers\Controller;
use App\Models\Observer;
use App\Models\ExternalObserver;
use App\Models\GuruNew;
use App\Models\PostResponse;
use App\Models\PreResponse;
use App\Models\PreForm;
use App\Models\PostForm;
use App\Models\PdpcForm;
use App\Models\PdpcResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageEvaluateController extends Controller
{
    public function index($gn_id)
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


        // 2. GET ASSIGNMENTS
        $preAssignment = null;
        $postAssignment = null;
        $externalAssignment = null;

        if ($observer) {
            $preAssignment = DB::table('observer_assignment')
                ->where('gn_id', $gn_id)
                ->where('assignmentType', 'pre')
                ->where('observerID', $observer->observerID)
                ->orderByDesc('assignedDate')
                ->orderByDesc('assignmentID')
                ->first();
        }

        $postAssignment = DB::table('observer_assignment')
            ->where('gn_id', $gn_id)
            ->where('assignmentType', 'post')
            ->where('postTeacherID', $teacher->teacherID)
            ->orderByDesc('attemptNo')
            ->orderByDesc('assignedDate')
            ->orderByDesc('assignmentID')
            ->first();

        if ($externalObserver) {
            $externalAssignment = DB::table('observer_assignment')
                ->where('gn_id', $gn_id)
                ->where('assignmentType', 'external')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                )
                ->orderByDesc('attemptNo')
                ->orderByDesc('assignedDate')
                ->orderByDesc('assignmentID')
                ->first();
        }

        abort_if(
            !$preAssignment &&
                !$postAssignment &&
                !$externalAssignment,
            403,
            'This teacher is not assigned to you.'
        );


        // 3. DETERMINE ROLE FROM ROUTE
        $routeName = request()->route()?->getName() ?? '';

        if (str_starts_with($routeName, 'external.')) {
            $isObserver = false;
            $isExternal = (bool) $externalAssignment;

            abort_unless(
                $isExternal,
                403,
                'You are not assigned as an External Observer.'
            );
        } else {
            $isObserver = (bool) (
                $preAssignment || $postAssignment
            );
            $isExternal = false;

            abort_unless(
                $isObserver,
                403,
                'You are not assigned as an Observer.'
            );
        }


        // 4. GET GN AND ACTIVE FORMS
        $guruNew = GuruNew::with('school')
            ->where('gn_id', $gn_id)
            ->firstOrFail();

        $preForm = PreForm::where('status', 'Active')
            ->latest('formID')
            ->first();

        $postForm = PostForm::where('status', 'Active')
            ->latest('formID')
            ->first();

        $pdpcForm = PdpcForm::where('status', 'Active')
            ->latest('formID')
            ->first();


        // 5. INITIALIZE BLADE VARIABLES
        $preResponse = null;
        $pdpcPostResponse = null;
        $latestExternalResponse = null;
        $latestExternalSubmitted = null;
        $feedbackResponse = null;

        $externalAttemptNo = null;
        $postAttemptNo = null;

        $externalHistory = collect();

        $canAccessPost = false;
        $canManagePre = false;
        $canManagePost = false;
        $canManageExternal = false;
        $canManageExternalFeedback = false;


        // 6. OBSERVER - PRE AND POST
        if ($isObserver) {

            // PRE RESPONSE

            if ($preAssignment && $observer) {
                $preResponse = PreResponse::where('gn_id', $gn_id)
                    ->where('observation_stage', 'PRE')
                    ->where('observerID', $observer->observerID)
                    ->orderByRaw(
                        "CASE WHEN status = 'Submitted' THEN 0 ELSE 1 END"
                    )
                    ->orderByDesc('responseID')
                    ->first();
            }

            $preAlreadySubmitted = PreResponse::where('gn_id', $gn_id)
                ->where('observation_stage', 'PRE')
                ->where('status', 'Submitted')
                ->exists();

            $canManagePre =
                (bool) $preAssignment &&
                strcasecmp(
                    (string) $preAssignment->status,
                    'Active'
                ) === 0 &&
                !$preAlreadySubmitted;

            // POST RESPONSE
            if ($postAssignment) {
                $postAttemptNo = max(
                    1,
                    (int) ($postAssignment->attemptNo ?? 1)
                );

                $postObserver = Observer::where(
                    'teacherID',
                    $postAssignment->postTeacherID
                )->first();

                if ($postObserver) {
                    $pdpcPostResponse = PdpcResponse::where(
                        'gn_id',
                        $gn_id
                    )
                        ->where('observation_stage', 'POST')
                        ->where(
                            'observerID',
                            $postObserver->observerID
                        )
                        ->where('attempt_no', $postAttemptNo)
                        ->latest('responseID')
                        ->first();

                    $feedbackResponse = PostResponse::where(
                        'gn_id',
                        $gn_id
                    )
                        ->where('observation_stage', 'POST')
                        ->where(
                            'observerID',
                            $postObserver->observerID
                        )
                        ->where('attempt_no', $postAttemptNo)
                        ->latest('responseID')
                        ->first();
                }

                $latestExternalForPost = PdpcResponse::where(
                    'gn_id',
                    $gn_id
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->orderByDesc('attempt_no')
                    ->orderByDesc('responseID')
                    ->first();

                // EXTERNAL REPEAT must have matching Feedback.
                $completedRepeatAttempts = DB::table(
                    'pdpc_response as pdpc'
                )
                    ->where('pdpc.gn_id', $gn_id)
                    ->where(
                        'pdpc.observation_stage',
                        'EXTERNAL'
                    )
                    ->where('pdpc.status', 'Submitted')
                    ->where('pdpc.result', 'REPEAT')
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('post_response as feedback')
                            ->whereColumn(
                                'feedback.gn_id',
                                'pdpc.gn_id'
                            )
                            ->whereColumn(
                                'feedback.attempt_no',
                                'pdpc.attempt_no'
                            )
                            ->whereColumn(
                                'feedback.externalObserverID',
                                'pdpc.externalObserverID'
                            )
                            ->where(
                                'feedback.observation_stage',
                                'EXTERNAL'
                            )
                            ->where(
                                'feedback.status',
                                'Submitted'
                            );
                    })
                    ->distinct()
                    ->count('pdpc.attempt_no');

                $hasPassedExternalForPost = PdpcResponse::where(
                    'gn_id',
                    $gn_id
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->where('result', 'PASS')
                    ->exists();

                $postPdpcSubmitted = $pdpcPostResponse &&
                    $pdpcPostResponse->status === 'Submitted';

                $postFeedbackSubmitted = $feedbackResponse &&
                    $feedbackResponse->status === 'Submitted';

                $postCompleted =
                    $postPdpcSubmitted &&
                    $pdpcPostResponse->result === 'PASS' &&
                    $postFeedbackSubmitted;

                // Block any new POST PdPc after a POST PASS.
                $hasPassedPost = PdpcResponse::where(
                    'gn_id',
                    $gn_id
                )
                    ->where('observation_stage', 'POST')
                    ->where('status', 'Submitted')
                    ->where('result', 'PASS')
                    ->exists();

                // Previous POST attempt must be REPEAT
                // and matching Feedback must be Submitted.
                $previousPostEligible = true;

                if ($postAttemptNo > 1) {
                    $previousPost = PdpcResponse::where(
                        'gn_id',
                        $gn_id
                    )
                        ->where('observation_stage', 'POST')
                        ->where('status', 'Submitted')
                        ->where('attempt_no', $postAttemptNo - 1)
                        ->orderByDesc('responseID')
                        ->first();

                    $previousPostFeedbackSubmitted = false;

                    if ($previousPost) {
                        $previousPostFeedbackSubmitted =
                            PostResponse::where('gn_id', $gn_id)
                            ->where(
                                'observation_stage',
                                'POST'
                            )
                            ->where(
                                'observerID',
                                $previousPost->observerID
                            )
                            ->where(
                                'attempt_no',
                                $previousPost->attempt_no
                            )
                            ->where('status', 'Submitted')
                            ->exists();
                    }

                    $previousPostEligible =
                        $previousPost !== null &&
                        $previousPost->result === 'REPEAT' &&
                        $previousPostFeedbackSubmitted;
                }

                $canManagePost =
                    strcasecmp(
                        (string) $postAssignment->status,
                        'Active'
                    ) === 0 &&
                    $completedRepeatAttempts >= 1 &&
                    $latestExternalForPost &&
                    $latestExternalForPost->result === 'REPEAT' &&
                    !$hasPassedExternalForPost &&
                    !$hasPassedPost &&
                    !$postCompleted &&
                    $previousPostEligible &&
                    !$postPdpcSubmitted;

                $canAccessPost = true;
            }
        }

        // 7. POST OBSERVER - EXTERNAL HISTORY
        if ($isObserver && $postAssignment) {
            $externalHistory = $this->getExternalHistory(
                $gn_id
            );
        }


        // 8. EXTERNAL OBSERVER
        if ($isExternal) {

            $latestExternalSubmitted = PdpcResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->orderByDesc('attempt_no')
                ->orderByDesc('responseID')
                ->first();

            $externalAttemptNo = max(
                1,
                (int) ($externalAssignment->attemptNo ?? 1)
            );

            // CURRENT EXTERNAL PDPC
            $latestExternalResponse = PdpcResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                )
                ->where('attempt_no', $externalAttemptNo)
                ->latest('responseID')
                ->first();

            // CURRENT EXTERNAL FEEDBACK
            $feedbackResponse = PostResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                )
                ->where('attempt_no', $externalAttemptNo)
                ->latest('responseID')
                ->first();

            // LATEST ASSIGNMENT
            $latestGnExternalAssignment = DB::table(
                'observer_assignment'
            )
                ->where('gn_id', $gn_id)
                ->where('assignmentType', 'external')
                ->orderByDesc('attemptNo')
                ->orderByDesc('assignmentID')
                ->first();

            $isLatestExternalAssignment =
                $latestGnExternalAssignment &&
                $latestGnExternalAssignment->assignmentID ==
                $externalAssignment->assignmentID;

            // PRE must be Submitted before EXTERNAL.
            $preSubmittedForExternal = PreResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'PRE')
                ->where('status', 'Submitted')
                ->exists();

            $assignmentActive = strcasecmp(
                (string) $externalAssignment->status,
                'Active'
            ) === 0;

            // CHECK PREVIOUS EXTERNAL ATTEMPT
            $previousAttemptEligible = true;

            if ($externalAttemptNo > 1) {
                $previousAttemptNo = $externalAttemptNo - 1;

                $previousSubmitted = PdpcResponse::where(
                    'gn_id',
                    $gn_id
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->where('attempt_no', $previousAttemptNo)
                    ->orderByDesc('responseID')
                    ->first();

                $previousFeedbackSubmitted = false;

                if ($previousSubmitted) {
                    $previousFeedbackSubmitted = PostResponse::where(
                        'gn_id',
                        $gn_id
                    )
                        ->where(
                            'observation_stage',
                            'EXTERNAL'
                        )
                        ->where(
                            'externalObserverID',
                            $previousSubmitted->externalObserverID
                        )
                        ->where(
                            'attempt_no',
                            $previousAttemptNo
                        )
                        ->where('status', 'Submitted')
                        ->exists();
                }

                $previousAttemptEligible =
                    $previousSubmitted !== null &&
                    $previousSubmitted->result === 'REPEAT' &&
                    $previousFeedbackSubmitted;
            }

            // POST PASS + matching Feedback = Completed.
            $postPathCompleted = DB::table('pdpc_response as pdpc')
                ->where('pdpc.gn_id', $gn_id)
                ->where('pdpc.observation_stage', 'POST')
                ->where('pdpc.status', 'Submitted')
                ->where(function ($query) {
                    $query->where('pdpc.result', 'PASS')
                        ->orWhere(function ($q) {
                            $q->whereNull('pdpc.result')
                                ->where('pdpc.percentage', '>=', 85);
                        });
                })
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('post_response as feedback')
                        ->whereColumn('feedback.gn_id', 'pdpc.gn_id')
                        ->whereColumn('feedback.attempt_no', 'pdpc.attempt_no')
                        ->whereColumn('feedback.observerID', 'pdpc.observerID')
                        ->where('feedback.observation_stage', 'POST')
                        ->where('feedback.status', 'Submitted');
                })
                ->exists();

            $overallCompletedThroughPost = $postPathCompleted;

            $hasPassedExternal = PdpcResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->where('result', 'PASS')
                ->exists();

            $currentPdpcSubmitted = $latestExternalResponse &&
                $latestExternalResponse->status === 'Submitted';

            $newerPdpcSubmitted =
                $latestExternalSubmitted &&
                (int) $latestExternalSubmitted->attempt_no >
                $externalAttemptNo;

            $hasPassedPost = PdpcResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'POST')
                ->where('status', 'Submitted')
                ->where('result', 'PASS')
                ->exists();

            // CAN MANAGE EXTERNAL PDPC?
            $canManageExternal =
                $preSubmittedForExternal &&
                $assignmentActive &&
                $isLatestExternalAssignment &&
                $previousAttemptEligible &&
                !$hasPassedExternal &&
                !$hasPassedPost &&
                !$overallCompletedThroughPost &&
                !$currentPdpcSubmitted &&
                !$newerPdpcSubmitted;

            // CAN MANAGE EXTERNAL FEEDBACK?
            $currentFeedbackSubmitted = $feedbackResponse &&
                $feedbackResponse->status === 'Submitted';

            $canManageExternalFeedback =
                $assignmentActive &&
                $isLatestExternalAssignment &&
                $previousAttemptEligible &&
                !$overallCompletedThroughPost &&
                !$hasPassedPost &&
                $currentPdpcSubmitted &&
                !$currentFeedbackSubmitted &&
                !$newerPdpcSubmitted &&
                (
                    !$hasPassedExternal ||
                    $latestExternalResponse->result === 'PASS'
                );

            // EXTERNAL EVALUATION HISTORY
            $externalHistory = $this->getExternalHistory(
                $gn_id,
                $externalAttemptNo
            );
        }

        $evaluationRemark = null;

        if ($isExternal) {

            $passedExternal = PdpcResponse::where('gn_id', $gn_id)
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->where('result', 'PASS')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('post_response as feedback')
                        ->whereColumn('feedback.gn_id', 'pdpc_response.gn_id')
                        ->whereColumn('feedback.attempt_no', 'pdpc_response.attempt_no')
                        ->whereColumn('feedback.externalObserverID', 'pdpc_response.externalObserverID')
                        ->where('feedback.observation_stage', 'EXTERNAL')
                        ->where('feedback.status', 'Submitted');
                })
                ->orderByDesc('attempt_no')
                ->first();

            if ($passedExternal) {

                $evaluationRemark =
                    $passedExternal->externalObserverID === $externalObserver->externalObserverID
                    ? 'This teacher has passed your evaluation.'
                    : 'This teacher has passed with another evaluator.';
            } elseif ($overallCompletedThroughPost ?? false) {

                $evaluationRemark =
                    'This teacher has passed at Post-Observation stage.';
            }
        }

        // 9. RETURN MANAGE EVALUATION PAGE
        return view(
            'observer.manage',
            compact(
                'guruNew',
                'isObserver',
                'isExternal',
                'preResponse',
                'feedbackResponse',
                'preForm',
                'postForm',
                'pdpcForm',
                'pdpcPostResponse',
                'latestExternalResponse',
                'latestExternalSubmitted',
                'externalAttemptNo',
                'externalHistory',
                'canAccessPost',
                'canManagePre',
                'canManagePost',
                'canManageExternal',
                'canManageExternalFeedback',
                'postAttemptNo',
                'preAssignment',
                'postAssignment',
                'evaluationRemark',
                'externalAssignment'
            )
        );
    }


    // EXTERNAL EVALUATION HISTORY
    private function getExternalHistory(
        $gn_id,
        $beforeAttemptNo = null
    ) {
        $query = DB::table('pdpc_response')
            ->leftJoin(
                'external_observer',
                'pdpc_response.externalObserverID',
                '=',
                'external_observer.externalObserverID'
            )
            ->leftJoin(
                'teacher',
                'external_observer.teacherID',
                '=',
                'teacher.teacherID'
            )
            ->leftJoin(
                'post_response as feedback',
                function ($join) {
                    $join->on(
                        'pdpc_response.gn_id',
                        '=',
                        'feedback.gn_id'
                    )
                        ->on(
                            'pdpc_response.externalObserverID',
                            '=',
                            'feedback.externalObserverID'
                        )
                        ->on(
                            'pdpc_response.attempt_no',
                            '=',
                            'feedback.attempt_no'
                        )
                        ->where(
                            'feedback.observation_stage',
                            '=',
                            'EXTERNAL'
                        )
                        ->where(
                            'feedback.status',
                            '=',
                            'Submitted'
                        );
                }
            )
            ->where('pdpc_response.gn_id', $gn_id)
            ->where(
                'pdpc_response.observation_stage',
                'EXTERNAL'
            )
            ->where(
                'pdpc_response.status',
                'Submitted'
            );

        if ($beforeAttemptNo !== null) {
            $query->where(
                'pdpc_response.attempt_no',
                '<',
                $beforeAttemptNo
            );
        }

        return $query
            ->select(
                'pdpc_response.responseID',
                'pdpc_response.attempt_no',
                'pdpc_response.observation_date',
                'pdpc_response.percentage',
                'pdpc_response.achievement_level',
                'pdpc_response.result',
                'pdpc_response.externalObserverID',
                'teacher.teacherName as evaluator_name',
                'feedback.responseID as feedback_responseID',
                'feedback.observation_date as feedback_date',
                'feedback.status as feedback_status'
            )
            ->orderByDesc('pdpc_response.attempt_no')
            ->orderByDesc('pdpc_response.responseID')
            ->get();
    }
}
