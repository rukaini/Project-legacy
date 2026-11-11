<?php

namespace App\Http\Controllers;

use App\Models\PdpcForm;
use App\Models\PdpcResponse;
use App\Models\PdpcScore;
use App\Models\PreResponse;
use App\Models\Observer;
use App\Models\ExternalObserver;
use App\Models\GuruNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PdpcObservationController extends Controller
{

    // CREATE OBSERVATION
    public function create(Request $request, $gn_id)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $guru = GuruNew::with('school')
            ->where('gn_id', $gn_id)
            ->firstOrFail();

        $assignment = $this->getAssignment(
            $gn_id,
            $role,
            $observer,
            $externalObserver
        );

        $attemptNo = (int) $assignment->attemptNo;

        $existing = $this->getAttemptResponse(
            $gn_id,
            $stage,
            $attemptNo
        );

        if ($existing) {
            $this->checkOwnership(
                $existing,
                $role,
                $observer,
                $externalObserver
            );

            if ($existing->status === 'Draft') {
                return redirect()->route(
                    $role === 'observer'
                        ? 'observer.pdpc.edit'
                        : 'external.pdpc.edit',
                    $existing->responseID
                );
            }

            return redirect()->route(
                $role === 'observer'
                    ? 'observer.pdpc.view'
                    : 'external.pdpc.view',
                $existing->responseID
            );
        }

        $this->checkStageEligibility(
            $gn_id,
            $stage,
            $attemptNo
        );

        $form = $this->getActiveForm();

        return view('pdpc-observation.form', compact(
            'form',
            'guru',
            'gn_id',
            'role',
            'stage'
        ));
    }


    // STORE OBSERVATION

    public function store(Request $request, $gn_id)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        GuruNew::where('gn_id', $gn_id)->firstOrFail();

        $assignment = $this->getAssignment(
            $gn_id,
            $role,
            $observer,
            $externalObserver
        );

        $form = $this->getActiveForm();

        $this->validateObservation($request, $form);

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
            GuruNew::where('gn_id', $gn_id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentAssignment = $this->getAssignment(
                $gn_id,
                $role,
                $observer,
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
                $attemptNo
            );

            $existing = $this->getAttemptResponse(
                $gn_id,
                $stage,
                $attemptNo
            );

            abort_if(
                $existing,
                409,
                'A PdPc response already exists for this attempt.'
            );

            $calculation = $this->calculateResult(
                $request,
                $form
            );

            $response = PdpcResponse::create([
                'formID' => $form->formID,
                'gn_id' => $gn_id,

                'observerID' => $role === 'observer'
                    ? $observer->observerID
                    : null,

                'externalObserverID' => $role === 'external'
                    ? $externalObserver->externalObserverID
                    : null,

                'observation_stage' => $stage,
                'attempt_no' => $attemptNo,

                'class_name' => $request->class_name,
                'subject_name' => $request->subject_name,
                'observation_date' => $request->observation_date,
                'observation_time' => $request->observation_time,

                'total_score' => $calculation['total_score'],
                'percentage' => $calculation['percentage'],
                'achievement_level' => $calculation['achievement_level'],

                // Both EXTERNAL and POST have PASS/REPEAT.
                'result' => $calculation['result'],

                'status' => $request->submit_action,
            ]);

            $this->saveScores(
                $request,
                $form,
                $response
            );

            if ($request->submit_action === 'Submitted') {
                $this->saveAudit(
                    $response,
                    $form,
                    $role,
                    $observer,
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
                    : 'Form draft saved successfully'
            );
    }


    // EDIT DRAFT

    public function edit(Request $request, $responseID)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $response = PdpcResponse::where(
            'responseID',
            $responseID
        )
            ->where('observation_stage', $stage)
            ->firstOrFail();

        abort_if(
            $response->status === 'Submitted',
            403,
            'Submitted observations cannot be edited.'
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
            $observer,
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
            (int) $assignment->attemptNo
        );

        $guru = GuruNew::with('school')
            ->where('gn_id', $response->gn_id)
            ->firstOrFail();

        $gn_id = $response->gn_id;

        $form = $this->getForm($response->formID);

        $existingScores = PdpcScore::where(
            'responseID',
            $response->responseID
        )
            ->pluck('score', 'pointID')
            ->toArray();

        return view('pdpc-observation.edit', compact(
            'form',
            'guru',
            'gn_id',
            'role',
            'stage',
            'response',
            'existingScores'
        ));
    }


    // UPDATE DRAFT

    public function update(Request $request, $responseID)
    {
        [$role, $stage, $observer, $externalObserver] =
            $this->resolveRole($request);

        $response = PdpcResponse::where(
            'responseID',
            $responseID
        )
            ->where('observation_stage', $stage)
            ->firstOrFail();

        abort_if(
            $response->status === 'Submitted',
            403,
            'Submitted observations cannot be edited.'
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
            $observer,
            $externalObserver
        );

        abort_if(
            (int) $response->attempt_no !==
                (int) $assignment->attemptNo,
            403,
            'This draft does not belong to your current assignment.'
        );

        $form = $this->getForm($response->formID);

        $this->validateObservation($request, $form);

        DB::transaction(function () use (
            $request,
            $response,
            $form,
            $stage,
            $observer,
            $externalObserver,
            $role,
            $assignment
        ) {
            GuruNew::where('gn_id', $response->gn_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedResponse = PdpcResponse::where(
                'responseID',
                $response->responseID
            )
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedResponse->status === 'Submitted',
                403,
                'Submitted observations cannot be edited.'
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
                $observer,
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
                (int) $currentAssignment->attemptNo
            );

            $calculation = $this->calculateResult(
                $request,
                $form
            );

            $lockedResponse->update([
                'class_name' => $request->class_name,
                'subject_name' => $request->subject_name,
                'observation_date' => $request->observation_date,
                'observation_time' => $request->observation_time,

                'total_score' => $calculation['total_score'],
                'percentage' => $calculation['percentage'],
                'achievement_level' => $calculation['achievement_level'],

                // POST also stores PASS or REPEAT.
                'result' => $calculation['result'],

                'status' => $request->submit_action,
            ]);

            PdpcScore::where(
                'responseID',
                $lockedResponse->responseID
            )->delete();

            $this->saveScores(
                $request,
                $form,
                $lockedResponse
            );

            if ($request->submit_action === 'Submitted') {
                $this->saveAudit(
                    $lockedResponse,
                    $form,
                    $role,
                    $observer,
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
                    : 'Form draft updated successfully'
            );
    }


    // VIEW SUBMITTED OBSERVATION

    public function show(Request $request, $responseID)
    {
        $teacherID = Auth::guard('teacher')->id();

        abort_if(
            !$teacherID,
            403,
            'Unauthorized access.'
        );

        $observer = Observer::where(
            'teacherID',
            $teacherID
        )->first();

        $externalObserver = ExternalObserver::where(
            'teacherID',
            $teacherID
        )->first();

        $isExternalRoute = $request->routeIs('external.pdpc.*');
        $isObserverRoute = $request->routeIs('observer.pdpc.*');

        abort_unless(
            $isExternalRoute || $isObserverRoute,
            403,
            'Invalid PdPc observation route.'
        );

        $role = $isExternalRoute ? 'external' : 'observer';

        $response = PdpcResponse::with([
            'observer.teacher',
            'externalObserver.teacher',
        ])
            ->where('responseID', $responseID)
            ->where('status', 'Submitted')
            ->firstOrFail();

        $stage = $response->observation_stage;

        if ($stage === 'EXTERNAL') {

            // External Observers can view assigned GN history.
            $assignedExternal = $isExternalRoute &&
                $externalObserver &&
                DB::table('observer_assignment')
                ->where('gn_id', $response->gn_id)
                ->where('assignmentType', 'external')
                ->where(
                    'externalObserverID',
                    $externalObserver->externalObserverID
                )
                ->exists();

            // POST Observers can view historical EXTERNAL PdPc.
            $assignedPostTeacher = $isObserverRoute &&
                DB::table('observer_assignment')
                ->where('gn_id', $response->gn_id)
                ->where('assignmentType', 'post')
                ->where('postTeacherID', $teacherID)
                ->exists();

            abort_unless(
                $assignedExternal || $assignedPostTeacher,
                403,
                'You are not assigned to view this GN evaluation history.'
            );
        } elseif ($stage === 'POST') {

            abort_unless(
                $isObserverRoute,
                403,
                'Invalid POST observation route.'
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
                $observer,
                $externalObserver
            );
        } else {
            abort(404, 'Invalid observation stage.');
        }

        $guru = GuruNew::with('school')
            ->where('gn_id', $response->gn_id)
            ->firstOrFail();

        $form = $this->getForm($response->formID);

        $scores = PdpcScore::where(
            'responseID',
            $response->responseID
        )->pluck('score', 'pointID');

        $tumsResults = $this->calculateStoredTumsResults(
            $form,
            $scores
        );

        return view('pdpc-observation.view', compact(
            'form',
            'guru',
            'response',
            'role',
            'stage',
            'scores',
            'tumsResults'
        ));
    }

    // RESOLVE EVALUATOR ROLE
    private function resolveRole(Request $request)
    {
        $teacherID = Auth::guard('teacher')->id();

        abort_if(
            !$teacherID,
            403,
            'Unauthorized access.'
        );

        $observer = Observer::where(
            'teacherID',
            $teacherID
        )->first();

        $externalObserver = ExternalObserver::where(
            'teacherID',
            $teacherID
        )->first();

        if ($request->routeIs('observer.pdpc.*')) {
            abort_if(
                !$observer,
                403,
                'You are not registered as an Observer.'
            );

            return [
                'observer',
                'POST',
                $observer,
                $externalObserver,
            ];
        }

        abort_unless(
            $request->routeIs('external.pdpc.*'),
            403,
            'Invalid PdPc observation route.'
        );

        abort_if(
            !$externalObserver,
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


    // GET CURRENT ACTIVE ASSIGNMENT
    private function getAssignment(
        $gn_id,
        $role,
        $observer,
        $externalObserver
    ) {
        $query = DB::table('observer_assignment')
            ->where('gn_id', $gn_id);

        if ($role === 'observer') {

            $teacherID = Auth::guard('teacher')->id();

            $query->where('assignmentType', 'post')
                ->where('postTeacherID', $teacherID);
        } else {

            abort_if(
                !$externalObserver,
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
            ->where('status', 'Active')
            ->orderByDesc('attemptNo')
            ->orderByDesc('assignmentID')
            ->first();

        abort_if(
            !$assignment,
            403,
            'You do not have an active assignment for this evaluation.'
        );

        // Only the latest EXTERNAL assignment can create/edit.
        // This allows a different External Observer on a new attempt.
        if ($role === 'external') {

            $latestGnAssignment = DB::table('observer_assignment')
                ->where('gn_id', $gn_id)
                ->where('assignmentType', 'external')
                ->orderByDesc('attemptNo')
                ->orderByDesc('assignmentID')
                ->first();

            abort_unless(
                $latestGnAssignment &&
                    $latestGnAssignment->assignmentID ==
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
        $attemptNo
    ) {
        // An EXTERNAL or POST PASS with matching Feedback
        // completes the evaluation.
        $externalCompleted = DB::table('pdpc_response as pdpc')
            ->where('pdpc.gn_id', $gn_id)
            ->where('pdpc.observation_stage', 'EXTERNAL')
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

        $postCompleted = DB::table('pdpc_response as pdpc')
            ->where('pdpc.gn_id', $gn_id)
            ->where('pdpc.observation_stage', 'POST')
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
                        'feedback.observerID',
                        'pdpc.observerID'
                    )
                    ->where(
                        'feedback.observation_stage',
                        'POST'
                    )
                    ->where(
                        'feedback.status',
                        'Submitted'
                    );
            })
            ->exists();

        abort_if(
            $externalCompleted || $postCompleted,
            409,
            'This teacher has already completed the evaluation.'
        );

        // After PASS, the evaluator must finish Feedback.
        // No other PdPc evaluation may begin.
        $hasPassed = PdpcResponse::where('gn_id', $gn_id)
            ->where('status', 'Submitted')
            ->where('result', 'PASS')
            ->whereIn(
                'observation_stage',
                ['EXTERNAL', 'POST']
            )
            ->exists();

        abort_if(
            $hasPassed,
            409,
            'A passing evaluation already exists. Complete its Feedback first.'
        );

        // EXTERNAL OBSERVATION
        if ($stage === 'EXTERNAL') {

            // PRE must be submitted before any EXTERNAL attempt.
            abort_unless(
                PreResponse::where('gn_id', $gn_id)
                    ->where('observation_stage', 'PRE')
                    ->where('status', 'Submitted')
                    ->exists(),
                409,
                'PRE Observation must be Submitted before starting EXTERNAL Observation.'
            );

            $latestExternal = PdpcResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'EXTERNAL')
                ->where('status', 'Submitted')
                ->orderByDesc('attempt_no')
                ->orderByDesc('responseID')
                ->first();

            // First EXTERNAL attempt is allowed
            // through an active assignment.
            if ($attemptNo > 1) {

                $previousAttempt = PdpcResponse::where(
                    'gn_id',
                    $gn_id
                )
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->where('attempt_no', $attemptNo - 1)
                    ->orderByDesc('attempt_no')
                    ->orderByDesc('responseID')
                    ->first();

                abort_unless(
                    $previousAttempt &&
                        $previousAttempt->result === 'REPEAT',
                    409,
                    'A previous EXTERNAL REPEAT is required.'
                );

                // Feedback must belong to the evaluator
                // who submitted the previous PdPc attempt.
                $previousFeedbackSubmitted = DB::table(
                    'post_response'
                )
                    ->where('gn_id', $gn_id)
                    ->where(
                        'observation_stage',
                        'EXTERNAL'
                    )
                    ->where(
                        'externalObserverID',
                        $previousAttempt->externalObserverID
                    )
                    ->where(
                        'attempt_no',
                        $previousAttempt->attempt_no
                    )
                    ->where('status', 'Submitted')
                    ->exists();

                abort_unless(
                    $previousFeedbackSubmitted,
                    409,
                    'Previous EXTERNAL Feedback must be Submitted.'
                );
            }

            abort_if(
                $latestExternal &&
                    (int) $latestExternal->attempt_no >= $attemptNo,
                409,
                'This EXTERNAL attempt is already completed or outdated.'
            );

            return;
        }


        // POST OBSERVATION (OPTIONAL)
        abort_unless(
            $stage === 'POST',
            403,
            'Invalid observation stage.'
        );

        // POST is available after at least ONE
        // EXTERNAL REPEAT with matching Submitted Feedback.
        $completedExternalRepeat = DB::table(
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
            ->exists();

        $latestExternal = PdpcResponse::where(
            'gn_id',
            $gn_id
        )
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        abort_unless(
            $completedExternalRepeat &&
                $latestExternal &&
                $latestExternal->result === 'REPEAT',
            403,
            'POST requires at least one completed EXTERNAL REPEAT.'
        );

        // A previously submitted POST with REPEAT
        // can be followed by another POST attempt.
        $latestPost = PdpcResponse::where(
            'gn_id',
            $gn_id
        )
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        if ($latestPost) {

            abort_unless(
                (int) $latestPost->attempt_no === $attemptNo - 1,
                409,
                'POST attempt number must follow the previous attempt.'
            );

            abort_if(
                (int) $latestPost->attempt_no >= $attemptNo,
                409,
                'This POST attempt is already completed or outdated.'
            );

            abort_unless(
                $latestPost->result === 'REPEAT',
                409,
                'The previous POST evaluation must be REPEAT.'
            );

            $previousPostFeedback = DB::table(
                'post_response'
            )
                ->where('gn_id', $gn_id)
                ->where('observation_stage', 'POST')
                ->where(
                    'observerID',
                    $latestPost->observerID
                )
                ->where(
                    'attempt_no',
                    $latestPost->attempt_no
                )
                ->where('status', 'Submitted')
                ->exists();

            abort_unless(
                $previousPostFeedback,
                409,
                'Previous POST Feedback must be Submitted before repeating POST.'
            );
        }

        $submittedPost = PdpcResponse::where(
            'gn_id',
            $gn_id
        )
            ->where('observation_stage', 'POST')
            ->where('attempt_no', $attemptNo)
            ->where('status', 'Submitted')
            ->exists();

        abort_if(
            $submittedPost,
            409,
            'This POST PdPc has already been Submitted.'
        );
    }


    // GET RESPONSE FOR SPECIFIC ATTEMPT

    private function getAttemptResponse(
        $gn_id,
        $stage,
        $attemptNo
    ) {
        return PdpcResponse::where('gn_id', $gn_id)
            ->where('observation_stage', $stage)
            ->where('attempt_no', $attemptNo)
            ->latest('responseID')
            ->first();
    }

    // CHECK OWNERSHIP
    private function checkOwnership(
        PdpcResponse $response,
        $role,
        $observer,
        $externalObserver
    ) {
        if ($role === 'observer') {

            $teacherID = Auth::guard('teacher')->id();

            abort_if(
                !$observer ||
                    $observer->teacherID != $teacherID ||
                    $response->observerID != $observer->observerID,
                403,
                'You are not allowed to access this observation.'
            );

            return;
        }

        abort_if(
            !$externalObserver ||
                $response->externalObserverID !=
                $externalObserver->externalObserverID,
            403,
            'You are not allowed to access this observation.'
        );
    }

    // CHECK HISTORICAL ASSIGNMENT

    private function checkHistoricalAssignment(
        PdpcResponse $response,
        $role,
        $observer,
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
            'You are not assigned to this observation attempt.'
        );
    }


    // SAVE AUDIT

    private function saveAudit(
        PdpcResponse $response,
        $form,
        $role,
        $observer,
        $externalObserver
    ) {
        DB::table('audit_observation')->insert([
            'teacherID' => $role === 'observer'
                ? Auth::guard('teacher')->id()
                : $externalObserver->teacherID,

            'gn_id' => $response->gn_id,

            'role' => $role === 'observer'
                ? 'Observer'
                : 'External Observer',

            'stage' => $response->observation_stage,
            'form_name' => $form->form_name,
            'action' => 'Submitted',
            'audit_date' => now()->toDateString(),
            'audit_time' => now()->format('H:i:s'),
        ]);
    }

    // GET ACTIVE FORM

    private function getActiveForm()
    {
        $form = PdpcForm::where('status', 'Active')
            ->with([
                'aspects.tums.tt.points',
                'aspects.tums.rubrics',
            ])
            ->latest('formID')
            ->first();

        abort_if(
            !$form,
            404,
            'No active PDPC form found.'
        );

        return $form;
    }

    // GET RESPONSE FORM

    private function getForm($formID)
    {
        return PdpcForm::with([
            'aspects.tums.tt.points',
            'aspects.tums.rubrics',
        ])
            ->where('formID', $formID)
            ->firstOrFail();
    }

    // VALIDATE OBSERVATION

    private function validateObservation(
        Request $request,
        $form
    ) {
        $isSubmit = $request->submit_action === 'Submitted';

        $rules = [
            'class_name' => $isSubmit
                ? 'required|string|max:100'
                : 'nullable|string|max:100',

            'subject_name' => $isSubmit
                ? 'required|string|max:100'
                : 'nullable|string|max:100',

            'observation_date' => $isSubmit
                ? 'required|date'
                : 'nullable|date',

            'observation_time' => $isSubmit
                ? 'required|date_format:H:i'
                : 'nullable|date_format:H:i',

            'submit_action' => 'required|in:Draft,Submitted',
        ];

        foreach ($form->aspects as $aspect) {
            foreach ($aspect->tums as $tums) {
                foreach ($tums->tt as $tt) {
                    foreach ($tt->points as $point) {

                        $key = 'scores.' . $point->pointID;

                        $rules[$key] = $isSubmit
                            ? 'required|integer|min:0|max:4'
                            : 'nullable|integer|min:0|max:4';
                    }
                }
            }
        }

        $request->validate($rules);
    }


    // CALCULATE FINAL RESULT
    private function calculateResult(Request $request, $form)
    {
        if ($request->submit_action === 'Draft') {
            return [
                'total_score' => null,
                'percentage' => null,
                'achievement_level' => null,
                'result' => null,
                'tums_results' => [],
            ];
        }

        $totalQualityScore = 0;
        $overallTotal = 0;
        $tumsResults = [];

        foreach ($form->aspects as $aspect) {
            foreach ($aspect->tums as $tums) {

                $points = $tums->tt->flatMap(
                    fn($tt) => $tt->points
                );

                $totalPoints = $points->count();
                $actionCount = 0;
                $qualityTotal = 0;

                foreach ($points as $point) {

                    $score = (int) $request->input(
                        'scores.' . $point->pointID
                    );

                    $qualityTotal += $score;

                    if ($score > 0) {
                        $actionCount++;
                    }
                }

                $actionScore = $totalPoints > 0
                    ? round(($actionCount / $totalPoints) * 4)
                    : 0;

                $qualityMean = $totalPoints > 0
                    ? round($qualityTotal / $totalPoints, 2)
                    : 0;

                $actionPercentage = round(
                    ($actionScore / 4) * 100,
                    2
                );

                $qualityPercentage = round(
                    ($qualityMean / 4) * 100,
                    2
                );

                $tumsPercentage =
                    ($actionPercentage * 0.25)
                    +
                    ($qualityPercentage * 0.75);

                $weight = (float) $tums->wajaran;

                // Keep raw weighted value.
                $weightedScore =
                    ($tumsPercentage * $weight) / 100;

                $overallTotal += $weightedScore;
                $totalQualityScore += $qualityTotal;

                $tumsResults[$tums->tumsID] = [
                    'total_points' => $totalPoints,
                    'action_count' => $actionCount,
                    'quality_total' => $qualityTotal,
                    'action_score' => $actionScore,
                    'quality_mean' => $qualityMean,
                    'action_percentage' => $actionPercentage,
                    'quality_percentage' => $qualityPercentage,
                    'tums_percentage' => $tumsPercentage,
                    'wajaran' => $weight,
                    'weighted_score' => round($weightedScore, 2),
                ];
            }
        }

        $overallTotal = round($overallTotal, 2);

        $achievementLevel = match (true) {
            $overallTotal >= 90 => 'Excellent',
            $overallTotal >= 80 => 'Good',
            $overallTotal >= 50 => 'Satisfactory',
            $overallTotal >= 20 => 'Weak',
            default => 'Very Weak',
        };

        $result = $overallTotal >= 85
            ? 'PASS'
            : 'REPEAT';

        return [
            'total_score' => $totalQualityScore,
            'percentage' => $overallTotal,
            'achievement_level' => $achievementLevel,
            'result' => $result,
            'tums_results' => $tumsResults,
        ];
    }

    // CALCULATE TUMS FROM STORED SCORES
    private function calculateStoredTumsResults(
        $form,
        $scores
    ): array {
        $results = [];

        foreach ($form->aspects as $aspect) {
            foreach ($aspect->tums as $tums) {

                $points = $tums->tt->flatMap(
                    fn($tt) => $tt->points
                );

                $totalPoints = $points->count();
                $actionCount = 0;
                $qualityTotal = 0;

                foreach ($points as $point) {

                    $score = (int) (
                        $scores[$point->pointID] ?? 0
                    );

                    $qualityTotal += $score;

                    if ($score > 0) {
                        $actionCount++;
                    }
                }

                $actionScore = $totalPoints > 0
                    ? round(($actionCount / $totalPoints) * 4)
                    : 0;

                $qualityMean = $totalPoints > 0
                    ? round($qualityTotal / $totalPoints, 2)
                    : 0;

                $actionPercentage = round(
                    ($actionScore / 4) * 100,
                    2
                );

                $qualityPercentage = round(
                    ($qualityMean / 4) * 100,
                    2
                );

                $tumsPercentage =
                    ($actionPercentage * 0.25)
                    +
                    ($qualityPercentage * 0.75);

                $weight = (float) $tums->wajaran;

                $weightedScore =
                    ($tumsPercentage * $weight) / 100;

                $results[$tums->tumsID] = [
                    'total_points' => $totalPoints,
                    'action_count' => $actionCount,
                    'quality_total' => $qualityTotal,
                    'action_score' => $actionScore,
                    'quality_mean' => $qualityMean,
                    'action_percentage' => $actionPercentage,
                    'quality_percentage' => $qualityPercentage,
                    'tums_percentage' => $tumsPercentage,
                    'wajaran' => $weight,
                    'weighted_score' => round($weightedScore, 2),
                ];
            }
        }

        return $results;
    }

    private function saveScores(
        Request $request,
        $form,
        PdpcResponse $response
    ) {
        foreach ($form->aspects as $aspect) {
            foreach ($aspect->tums as $tums) {
                foreach ($tums->tt as $tt) {
                    foreach ($tt->points as $point) {

                        $score = $request->input(
                            'scores.' . $point->pointID
                        );

                        if ($score === null || $score === '') {
                            continue;
                        }

                        PdpcScore::create([
                            'responseID' => $response->responseID,
                            'pointID' => $point->pointID,
                            'score' => (int) $score,
                        ]);
                    }
                }
            }
        }
    }
}
