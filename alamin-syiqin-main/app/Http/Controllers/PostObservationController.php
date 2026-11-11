<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostObservationController extends Controller
{

    // CREATE FEEDBACK
    public function create(Request $request, $gn_id)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $guru = $this->getGuruNew($gn_id);

        $assignment = $this->getAssignment(
            $gn_id,
            $role,
            $externalObserver
        );

        $attemptNo = (int) $assignment->attemptNo;

        $existingResponse = $this->getAttemptResponse(
            $gn_id,
            $stage,
            $attemptNo
        );

        if ($existingResponse) {
            $this->checkOwnership(
                $existingResponse,
                $role,
                $observer,
                $externalObserver
            );

            if ($existingResponse->status === 'Draft') {
                return redirect()->route(
                    $role === 'observer'
                        ? 'observer.post.edit'
                        : 'external.post.edit',
                    $existingResponse->responseID
                );
            }

            return redirect()->route(
                $role === 'observer'
                    ? 'observer.post.view'
                    : 'external.post.view',
                $existingResponse->responseID
            );
        }

        $this->checkStageEligibility(
            $gn_id,
            $stage,
            $attemptNo,
            $role,
            $observer,
            $externalObserver
        );

        $this->requirePostObserverRecord($role, $observer);

        $form = $this->getActiveForm();

        return view('post-observation.form', compact(
            'form',
            'guru',
            'gn_id',
            'role',
            'stage',
            'attemptNo'
        ));
    }


    // STORE FEEDBACK
    public function store(Request $request, $gn_id)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $this->getGuruNew($gn_id);

        $assignment = $this->getAssignment(
            $gn_id,
            $role,
            $externalObserver
        );

        $this->requirePostObserverRecord($role, $observer);

        $request->validate([
            'formID' => [
                'required',
                'exists:post_form,formID',
            ],
        ]);

        $form = $this->getForm($request->formID);

        $this->validateForm($request, $form);

        DB::transaction(function () use (
            $request,
            $gn_id,
            $form,
            $observer,
            $externalObserver,
            $role,
            $stage,
            $assignment
        ) {
            $guru = DB::table('guru_new')
                ->where('gn_id', $gn_id)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $guru,
                404,
                'Teacher not found.'
            );

            $currentAssignment = $this->getAssignment(
                $gn_id,
                $role,
                $externalObserver
            );

            abort_if(
                $currentAssignment->assignmentID !=
                    $assignment->assignmentID,
                409,
                'The evaluation assignment has changed.'
            );

            $attemptNo = (int) $currentAssignment->attemptNo;

            $this->checkStageEligibility(
                $gn_id,
                $stage,
                $attemptNo,
                $role,
                $observer,
                $externalObserver
            );

            abort_if(
                $this->getAttemptResponse(
                    $gn_id,
                    $stage,
                    $attemptNo
                ),
                409,
                'A Feedback response already exists for this attempt.'
            );

            $responseID = DB::table('post_response')
                ->insertGetId([
                    'gn_id' => $gn_id,
                    'formID' => $form->formID,
                    'observation_stage' => $stage,
                    'attempt_no' => $attemptNo,

                    'observerID' => $role === 'observer'
                        ? $observer->observerID
                        : null,

                    'externalObserverID' => $role === 'external'
                        ? $externalObserver->externalObserverID
                        : null,

                    'class_name' => $request->class_name,
                    'subject_name' => $request->subject_name,
                    'observation_date' => $request->observation_date,
                    'observation_time' => $request->observation_time,
                    'status' => $request->submit_action,
                ]);

            $this->saveAnswers(
                $request,
                $form,
                $responseID
            );

            if ($request->submit_action === 'Submitted') {
                $this->saveAudit(
                    $gn_id,
                    $stage,
                    $role,
                    $form,
                    $externalObserver
                );
            }
        });

        return redirect()
            ->route(
                $role === 'observer'
                    ? 'observer.manage'
                    : 'external.manage',
                $gn_id
            )
            ->with(
                'success',
                $request->submit_action === 'Submitted'
                    ? 'Form submitted successfully'
                    : 'Draft saved successfully'
            );
    }

    // EDIT FEEDBACK DRAFT
    public function edit(Request $request, $responseID)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $response = DB::table('post_response')
            ->where('responseID', $responseID)
            ->where('observation_stage', $stage)
            ->first();

        abort_unless(
            $response,
            404,
            'Feedback response not found.'
        );

        abort_if(
            $response->status === 'Submitted',
            403,
            'Submitted Feedback cannot be edited.'
        );

        $this->checkOwnership(
            $response,
            $role,
            $observer,
            $externalObserver
        );

        $assignment = $this->getAssignment(
            $response->gn_id,
            $role,
            $externalObserver
        );

        abort_if(
            (int) $response->attempt_no !==
                (int) $assignment->attemptNo,
            403,
            'This draft does not belong to your current assignment.'
        );

        $this->checkStageEligibility(
            $response->gn_id,
            $stage,
            (int) $assignment->attemptNo,
            $role,
            $observer,
            $externalObserver
        );

        $guru = $this->getGuruNew($response->gn_id);
        $gn_id = $response->gn_id;
        $form = $this->getForm($response->formID);

        $existingAnswers = $this->getExistingAnswers(
            $response->responseID,
            $form
        );

        return view('post-observation.edit', compact(
            'form',
            'guru',
            'gn_id',
            'role',
            'stage',
            'response',
            'existingAnswers'
        ));
    }


    // UPDATE FEEDBACK DRAFT
    public function update(Request $request, $responseID)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $response = DB::table('post_response')
            ->where('responseID', $responseID)
            ->where('observation_stage', $stage)
            ->first();

        abort_unless(
            $response,
            404,
            'Feedback response not found.'
        );

        abort_if(
            $response->status === 'Submitted',
            403,
            'Submitted Feedback cannot be edited.'
        );

        $this->checkOwnership(
            $response,
            $role,
            $observer,
            $externalObserver
        );

        $assignment = $this->getAssignment(
            $response->gn_id,
            $role,
            $externalObserver
        );

        abort_if(
            (int) $response->attempt_no !==
                (int) $assignment->attemptNo,
            403,
            'This draft does not belong to your current assignment.'
        );

        $form = $this->getForm($response->formID);

        $this->validateForm($request, $form);

        DB::transaction(function () use (
            $request,
            $response,
            $form,
            $observer,
            $externalObserver,
            $role,
            $stage,
            $assignment
        ) {
            $guru = DB::table('guru_new')
                ->where('gn_id', $response->gn_id)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $guru,
                404,
                'Teacher not found.'
            );

            $lockedResponse = DB::table('post_response')
                ->where('responseID', $response->responseID)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $lockedResponse,
                404,
                'Feedback response not found.'
            );

            abort_if(
                $lockedResponse->status === 'Submitted',
                403,
                'Submitted Feedback cannot be edited.'
            );

            $this->checkOwnership(
                $lockedResponse,
                $role,
                $observer,
                $externalObserver
            );

            $currentAssignment = $this->getAssignment(
                $lockedResponse->gn_id,
                $role,
                $externalObserver
            );

            abort_if(
                $currentAssignment->assignmentID !=
                    $assignment->assignmentID ||
                    (int) $lockedResponse->attempt_no !==
                    (int) $currentAssignment->attemptNo,
                403,
                'This draft is no longer part of your current assignment.'
            );

            $this->checkStageEligibility(
                $lockedResponse->gn_id,
                $stage,
                (int) $currentAssignment->attemptNo,
                $role,
                $observer,
                $externalObserver
            );

            DB::table('post_response')
                ->where('responseID', $lockedResponse->responseID)
                ->update([
                    'class_name' => $request->class_name,
                    'subject_name' => $request->subject_name,
                    'observation_date' => $request->observation_date,
                    'observation_time' => $request->observation_time,
                    'status' => $request->submit_action,
                ]);

            $fieldIDs = $form->sections
                ->flatMap(fn($section) => $section->fields)
                ->where('field_type', '!=', 'display')
                ->pluck('fieldID');

            if ($fieldIDs->isNotEmpty()) {
                DB::table('post_answer')
                    ->where(
                        'responseID',
                        $lockedResponse->responseID
                    )
                    ->whereIn('fieldID', $fieldIDs)
                    ->delete();
            }

            $this->saveAnswers(
                $request,
                $form,
                $lockedResponse->responseID
            );

            if ($request->submit_action === 'Submitted') {
                $this->saveAudit(
                    $lockedResponse->gn_id,
                    $stage,
                    $role,
                    $form,
                    $externalObserver
                );
            }
        });

        return redirect()
            ->route(
                $role === 'observer'
                    ? 'observer.manage'
                    : 'external.manage',
                $response->gn_id
            )
            ->with(
                'success',
                $request->submit_action === 'Submitted'
                    ? 'Form submitted successfully'
                    : 'Draft updated successfully'
            );
    }

    // VIEW SUBMITTED FEEDBACK
    public function show(Request $request, $responseID)
    {
        $teacherID = Auth::guard('teacher')->id();

        abort_unless(
            $teacherID,
            403,
            'Unauthorized access.'
        );

        [$role, $routeStage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $response = DB::table('post_response')
            ->where('responseID', $responseID)
            ->where('status', 'Submitted')
            ->first();

        abort_unless(
            $response,
            404,
            'Submitted Feedback response not found.'
        );

        $stage = $response->observation_stage;

        if ($stage === 'EXTERNAL') {

            $assignedExternal = $role === 'external'
                && $externalObserver
                && DB::table('observer_assignment')
                ->where('gn_id', $response->gn_id)
                ->where('assignmentType', 'external')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                )
                ->exists();

            $assignedPostTeacher = $role === 'observer'
                && DB::table('observer_assignment')
                ->where('gn_id', $response->gn_id)
                ->where('assignmentType', 'post')
                ->where('postTeacherID', $teacherID)
                ->exists();

            abort_unless(
                $assignedExternal || $assignedPostTeacher,
                403,
                'You are not assigned to view this GN Feedback history.'
            );
        } elseif ($stage === 'POST') {

            abort_unless(
                $role === 'observer',
                403,
                'Invalid POST Feedback route.'
            );

            $this->checkOwnership(
                $response,
                $role,
                $observer,
                $externalObserver
            );

            $this->checkHistoricalAssignment(
                $response,
                $role,
                $externalObserver
            );
        } else {
            abort(404, 'Invalid Feedback observation stage.');
        }

        $guru = $this->getGuruNew($response->gn_id);
        $form = $this->getForm($response->formID);

        $existingAnswers = $this->getExistingAnswers(
            $response->responseID,
            $form
        );

        if ($stage === 'EXTERNAL') {

            $evaluatorName = DB::table('external_observer')
                ->join(
                    'teacher',
                    'external_observer.teacherID',
                    '=',
                    'teacher.teacherID'
                )
                ->where(
                    'external_observer.externalObserverID',
                    $response->externalObserverID
                )
                ->value('teacher.teacherName');
        } else {

            $evaluatorName = DB::table('observer')
                ->join(
                    'teacher',
                    'observer.teacherID',
                    '=',
                    'teacher.teacherID'
                )
                ->where(
                    'observer.observerID',
                    $response->observerID
                )
                ->value('teacher.teacherName');
        }

        return view('post-observation.view', compact(
            'form',
            'guru',
            'response',
            'role',
            'stage',
            'existingAnswers',
            'evaluatorName'
        ));
    }

    // RESoLVE ROLE AND STAGE
    private function resolveRole(Request $request): array
    {
        $teacherID = Auth::guard('teacher')->id();

        abort_unless(
            $teacherID,
            403,
            'Unauthorized access.'
        );

        $observer = DB::table('observer')
            ->where('teacherID', $teacherID)
            ->first();

        $externalObserver = DB::table('external_observer')
            ->where('teacherID', $teacherID)
            ->first();

        if ($request->routeIs('observer.post.*')) {
            return [
                'observer',
                'POST',
                $observer,
                $externalObserver,
            ];
        }

        abort_unless(
            $request->routeIs('external.post.*'),
            403,
            'Invalid Feedback route.'
        );

        abort_unless(
            $externalObserver,
            403,
            'You are not registered as an External Observer.'
        );

        return [
            'external',
            'EXTERNAL',
            $observer,
            $externalObserver,
        ];
    }

  
    // GET CURRENT ASSIGNMENT
    private function getAssignment(
        $gn_id,
        $role,
        $externalObserver
    ) {
        $query = DB::table('observer_assignment')
            ->where('gn_id', $gn_id)
            ->where('status', 'Active');

        if ($role === 'observer') {

            $query->where('assignmentType', 'post')
                ->where(
                    'postTeacherID',
                    Auth::guard('teacher')->id()
                );
        } else {

            abort_unless(
                $externalObserver,
                403,
                'You are not registered as an External Observer.'
            );

            $query->where('assignmentType', 'external')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                );
        }

        $assignment = $query
            ->orderByDesc('attemptNo')
            ->orderByDesc('assignmentID')
            ->first();

        abort_unless(
            $assignment,
            403,
            'You do not have an active assignment for this evaluation.'
        );

        if ($role === 'external') {

            $latestAssignment = DB::table('observer_assignment')
                ->where('gn_id', $gn_id)
                ->where('assignmentType', 'external')
                ->orderByDesc('attemptNo')
                ->orderByDesc('assignmentID')
                ->first();

            abort_unless(
                $latestAssignment &&
                    $latestAssignment->assignmentID ==
                    $assignment->assignmentID,
                403,
                'This External Observation assignment is no longer current.'
            );
        }

        return $assignment;
    }


    // CHECK STAGE ELIGIBILITY
    private function checkStageEligibility(
        $gn_id,
        $stage,
        $attemptNo,
        $role,
        $observer,
        $externalObserver
    ) {
        abort_unless(
            in_array($stage, ['EXTERNAL', 'POST'], true),
            403,
            'Invalid observation stage.'
        );

        // 1. REQUIRE MATCHING SUBMITTED PDPC

        $currentPdpcQuery = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', $stage)
            ->where('attempt_no', $attemptNo)
            ->where('status', 'Submitted');

        if ($role === 'external') {

            abort_unless(
                $externalObserver,
                403,
                'External Observer record not found.'
            );

            $currentPdpcQuery->where(
                'externalObserverID',
                $externalObserver->externalObserverID
            );
        } else {

            abort_unless(
                $observer,
                403,
                'Observer record not found.'
            );

            $currentPdpcQuery->where(
                'observerID',
                $observer->observerID
            );
        }

        $currentPdpc = $currentPdpcQuery
            ->orderByDesc('responseID')
            ->first();

        abort_unless(
            $currentPdpc,
            403,
            'Please submit the matching PdPc Observation before accessing Feedback.'
        );

        abort_unless(
            in_array(
                $currentPdpc->result,
                ['PASS', 'REPEAT'],
                true
            ),
            409,
            'The submitted PdPc must have a PASS or REPEAT result.'
        );

        // 2. BLOCK COMPLETED EVALUATIONS

        $hasCompletedEvaluation = DB::table(
            'pdpc_response as pdpc'
        )
            ->where('pdpc.gn_id', $gn_id)
            ->whereIn(
                'pdpc.observation_stage',
                ['EXTERNAL', 'POST']
            )
            ->where('pdpc.status', 'Submitted')
            ->where('pdpc.result', 'PASS')
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
                        'feedback.observation_stage',
                        'pdpc.observation_stage'
                    )
                    ->where('feedback.status', 'Submitted')
                    ->where(function ($query) {
                        $query
                            ->where(function ($external) {
                                $external
                                    ->where(
                                        'pdpc.observation_stage',
                                        'EXTERNAL'
                                    )
                                    ->whereColumn(
                                        'feedback.externalObserverID',
                                        'pdpc.externalObserverID'
                                    );
                            })
                            ->orWhere(function ($post) {
                                $post
                                    ->where(
                                        'pdpc.observation_stage',
                                        'POST'
                                    )
                                    ->whereColumn(
                                        'feedback.observerID',
                                        'pdpc.observerID'
                                    );
                            });
                    });
            })
            ->exists();

        abort_if(
            $hasCompletedEvaluation,
            409,
            'This teacher has already completed the evaluation.'
        );

        // 3. PREVENT DUPLICATE SUBMITTED FEEDBACK

        $submittedFeedbackQuery = DB::table('post_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', $stage)
            ->where('attempt_no', $attemptNo)
            ->where('status', 'Submitted');

        if ($role === 'external') {
            $submittedFeedbackQuery->where(
                'externalObserverID',
                $externalObserver->externalObserverID
            );
        } else {
            $submittedFeedbackQuery->where(
                'observerID',
                $observer->observerID
            );
        }

        abort_if(
            $submittedFeedbackQuery->exists(),
            409,
            'Feedback has already been Submitted for this attempt.'
        );

        // 4. EXTERNAL FEEDBACK

        if ($stage === 'EXTERNAL') {

            $earlierPass = DB::table('pdpc_response')
                ->where('gn_id', $gn_id)
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->where('result', 'PASS')
                ->where('attempt_no', '<', $attemptNo)
                ->exists();

            abort_if(
                $earlierPass,
                409,
                'This teacher has already passed an earlier EXTERNAL attempt.'
            );

            $postPass = DB::table('pdpc_response')
                ->where('gn_id', $gn_id)
                ->where('observation_stage', 'POST')
                ->where('status', 'Submitted')
                ->where('result', 'PASS')
                ->exists();

            abort_if(
                $postPass,
                409,
                'This teacher has already passed POST Observation.'
            );

            if ($attemptNo > 1) {

                $previous = DB::table('pdpc_response')
                    ->where('gn_id', $gn_id)
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->where('attempt_no', $attemptNo - 1)
                    ->orderByDesc('responseID')
                    ->first();

                abort_unless(
                    $previous &&
                        $previous->result === 'REPEAT',
                    409,
                    'The previous EXTERNAL attempt must be REPEAT.'
                );

                $previousFeedback = DB::table('post_response')
                    ->where('gn_id', $gn_id)
                    ->where('observation_stage', 'EXTERNAL')
                    ->where(
                        'attempt_no',
                        $previous->attempt_no
                    )
                    ->where(
                        'externalObserverID',
                        $previous->externalObserverID
                    )
                    ->where('status', 'Submitted')
                    ->exists();

                abort_unless(
                    $previousFeedback,
                    409,
                    'Previous EXTERNAL Feedback must be Submitted.'
                );
            }

            $newerSubmittedExternal = DB::table('pdpc_response')
                ->where('gn_id', $gn_id)
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->where('attempt_no', '>', $attemptNo)
                ->exists();

            abort_if(
                $newerSubmittedExternal,
                409,
                'A newer EXTERNAL attempt has already been submitted.'
            );

            return;
        }

        // 5. POST FEEDBACK

        $completedExternalRepeat = DB::table(
            'pdpc_response as pdpc'
        )
            ->where('pdpc.gn_id', $gn_id)
            ->where('pdpc.observation_stage', 'EXTERNAL')
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
            ->exists();

        abort_unless(
            $completedExternalRepeat,
            403,
            'POST requires at least one completed EXTERNAL REPEAT.'
        );

        $latestExternal = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        abort_unless(
            $latestExternal &&
                $latestExternal->result === 'REPEAT',
            409,
            'The latest EXTERNAL result must be REPEAT.'
        );

        $externalPass = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->where('result', 'PASS')
            ->exists();

        abort_if(
            $externalPass,
            409,
            'POST Feedback is not available after an EXTERNAL PASS.'
        );

        // POST Feedback allowed for PASS and REPEAT.
        // PASS + Submitted Feedback = Completed.
        // REPEAT + Submitted Feedback = Eligible for new POST.

        $newerSubmittedPost = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->where('attempt_no', '>', $attemptNo)
            ->exists();

        abort_if(
            $newerSubmittedPost,
            409,
            'A newer POST attempt has already been submitted.'
        );

        $previousPost = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->where('attempt_no', '<', $attemptNo)
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        if ($previousPost) {

            abort_unless(
                (int) $previousPost->attempt_no ===
                    $attemptNo - 1,
                409,
                'POST attempt number must follow the previous attempt.'
            );

            abort_unless(
                $previousPost->result === 'REPEAT',
                409,
                'The previous POST attempt must have a REPEAT result.'
            );

            $previousPostFeedback = DB::table('post_response')
                ->where('gn_id', $gn_id)
                ->where('observation_stage', 'POST')
                ->where(
                    'attempt_no',
                    $previousPost->attempt_no
                )
                ->where(
                    'observerID',
                    $previousPost->observerID
                )
                ->where('status', 'Submitted')
                ->exists();

            abort_unless(
                $previousPostFeedback,
                409,
                'Previous POST Feedback must be Submitted before another POST attempt.'
            );
        }
    }


    // GET RESPONSE FOR ATTEMPT
    private function getAttemptResponse(
        $gn_id,
        $stage,
        $attemptNo
    ) {
        return DB::table('post_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', $stage)
            ->where('attempt_no', $attemptNo)
            ->orderByDesc('responseID')
            ->first();
    }


    // CHECK OWNERSHIP
    private function checkOwnership(
        $response,
        $role,
        $observer,
        $externalObserver
    ) {
        if ($role === 'observer') {

            abort_if(
                !$observer ||
                    $response->observerID != $observer->observerID,
                403,
                'You are not allowed to access this Feedback response.'
            );

            return;
        }

        abort_if(
            !$externalObserver ||
                $response->externalObserverID !=
                $externalObserver->externalObserverID,
            403,
            'You are not allowed to access this Feedback response.'
        );
    }

    // =====================================================
    // CHECK HISTORICAL ASSIGNMENT
    // =====================================================

    private function checkHistoricalAssignment(
        $response,
        $role,
        $externalObserver
    ) {
        $query = DB::table('observer_assignment')
            ->where('gn_id', $response->gn_id)
            ->where('attemptNo', $response->attempt_no);

        if ($role === 'observer') {

            $query->where('assignmentType', 'post')
                ->where(
                    'postTeacherID',
                    Auth::guard('teacher')->id()
                );
        } else {

            $query->where('assignmentType', 'external')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                );
        }

        abort_if(
            !$query->exists(),
            403,
            'You are not assigned to this Feedback attempt.'
        );
    }


    // REQUIRE OBSERVER RECORD FOR POST
    private function requirePostObserverRecord(
        $role,
        $observer
    ): void {
        if ($role === 'observer') {
            abort_unless(
                $observer,
                409,
                'The POST-assigned teacher does not have an Observer record. The Feedback table requires observerID.'
            );
        }
    }

    // SAVE AUDIT
    private function saveAudit(
        $gn_id,
        $stage,
        $role,
        $form,
        $externalObserver
    ): void {
        DB::table('audit_observation')->insert([
            'teacherID' => $role === 'observer'
                ? Auth::guard('teacher')->id()
                : $externalObserver->teacherID,

            'gn_id' => $gn_id,

            'role' => $role === 'observer'
                ? 'Observer'
                : 'External Observer',

            'stage' => $stage,
            'form_name' => $form->form_name,
            'action' => 'Submitted',
            'audit_date' => now()->toDateString(),
            'audit_time' => now()->format('H:i:s'),
        ]);
    }


    // GET ACTIVE FEEDBACK FORM
    private function getActiveForm()
    {
        $form = DB::table('post_form')
            ->where('status', 'Active')
            ->orderByDesc('formID')
            ->first();

        abort_unless(
            $form,
            404,
            'No active post observation form found.'
        );

        return $this->attachFormStructure($form);
    }


    // GET EXACT FORM VERSION

    private function getForm($formID)
    {
        $form = DB::table('post_form')
            ->where('formID', $formID)
            ->first();

        abort_unless(
            $form,
            404,
            'Post observation form not found.'
        );

        return $this->attachFormStructure($form);
    }


    private function attachFormStructure($form)
    {
        $sections = DB::table('post_section')
            ->where('formID', $form->formID)
            ->orderBy('display_order')
            ->get();

        foreach ($sections as $section) {

            $fields = DB::table('post_field')
                ->where('sectionID', $section->sectionID)
                ->orderBy('display_order')
                ->get();

            foreach ($fields as $field) {
                $field->options = DB::table('post_field_option')
                    ->where('fieldID', $field->fieldID)
                    ->orderBy('display_order')
                    ->get();
            }

            $section->fields = $fields;
        }

        $form->sections = $sections;

        return $form;
    }


    private function getGuruNew($gn_id)
    {
        $guru = DB::table('guru_new')
            ->where('gn_id', $gn_id)
            ->first();

        abort_unless(
            $guru,
            404,
            'Teacher not found.'
        );

        $guru->school = $guru->schoolID
            ? DB::table('school')
            ->where('schoolID', $guru->schoolID)
            ->first()
            : null;

        return $guru;
    }


    // GET EXISTING ANSWERS
    private function getExistingAnswers(
        $responseID,
        $form
    ): array {
        $answers = DB::table('post_answer')
            ->where('responseID', $responseID)
            ->get();

        $fields = $form->sections
            ->flatMap(fn($section) => $section->fields);

        $existingAnswers = [];

        foreach ($answers as $answer) {

            $field = $fields->firstWhere(
                'fieldID',
                $answer->fieldID
            );

            if (!$field) {
                continue;
            }

            if ($field->field_type === 'checkbox') {

                $existingAnswers[$answer->fieldID] =
                    json_decode(
                        $answer->answer_value,
                        true
                    ) ?? [];
            } else {

                $existingAnswers[$answer->fieldID] =
                    $answer->answer_value;
            }
        }

        return $existingAnswers;
    }


    // VALIDATE FEEDBACK FORM
    private function validateForm(Request $request, $form)
    {
        $isSubmit = $request->submit_action === 'Submitted';

        $rules = [
            'class_name' => $isSubmit
                ? ['required', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],

            'subject_name' => $isSubmit
                ? ['required', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],

            'observation_date' => $isSubmit
                ? ['required', 'date']
                : ['nullable', 'date'],

            'observation_time' => $isSubmit
                ? ['required', 'date_format:H:i']
                : ['nullable', 'date_format:H:i'],

            'submit_action' => [
                'required',
                'in:Draft,Submitted',
            ],
        ];

        foreach ($form->sections as $section) {
            foreach ($section->fields as $field) {

                if ($field->field_type === 'display') {
                    continue;
                }

                $answerKey = 'answers.' . $field->fieldID;

                if ($field->field_type === 'checkbox') {

                    $rules[$answerKey] =
                        $isSubmit && $field->is_required
                        ? ['required', 'array', 'min:1']
                        : ['nullable', 'array'];

                    $rules[$answerKey . '.*'] = [
                        'string',
                        'max:255',
                    ];
                } else {

                    $rules[$answerKey] =
                        $isSubmit && $field->is_required
                        ? ['required', 'string']
                        : ['nullable', 'string'];
                }
            }
        }

        $request->validate($rules);
    }


    // SAVE FEEDBACK ANSWERS
    private function saveAnswers(
        Request $request,
        $form,
        $responseID
    ) {
        foreach ($form->sections as $section) {
            foreach ($section->fields as $field) {

                if ($field->field_type === 'display') {
                    continue;
                }

                $answerValue = $request->input(
                    'answers.' . $field->fieldID
                );

                if ($field->field_type === 'checkbox') {

                    if (
                        !is_array($answerValue) ||
                        empty($answerValue)
                    ) {
                        continue;
                    }

                    $answerValue = json_encode($answerValue);
                } else {

                    if (
                        $answerValue === null ||
                        trim((string) $answerValue) === ''
                    ) {
                        continue;
                    }
                }

                DB::table('post_answer')->insert([
                    'responseID' => $responseID,
                    'fieldID' => $field->fieldID,
                    'answer_value' => $answerValue,
                ]);
            }
        }
    }
}
