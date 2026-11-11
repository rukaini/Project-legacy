<?php

namespace App\Http\Controllers\Observer;

use App\Http\Controllers\Controller;
use App\Models\PreForm;
use App\Models\PreResponse;
use App\Models\PreScore;
use App\Models\PreSectionComment;
use App\Models\Observer;
use App\Models\GuruNew;
use App\Models\AuditObservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreObservationController extends Controller
{

    private function getTeacher()
    {
        $teacher = Auth::guard('teacher')->user();

        abort_if(
            !$teacher,
            403,
            'Unauthorized access'
        );

        return $teacher;
    }


    // CHECK PRE ASSIGNMENT
    private function getAssignedPreObserver($gn_id)
    {
        $teacher = $this->getTeacher();

        $observer = Observer::where(
            'teacherID',
            $teacher->teacherID
        )->first();

        abort_if(
            !$observer,
            403,
            'You are not registered as a PRE Observer'
        );

        $assigned = DB::table('observer_assignment')
            ->where('gn_id', $gn_id)
            ->where('assignmentType', 'pre')
            ->where('observerID', $observer->observerID)
            ->exists();

        abort_if(
            !$assigned,
            403,
            'You are not assigned to perform PRE observation for this teacher'
        );

        return $observer;
    }


    // CHECK IF PRE ALREADY SUBMITTED
    private function getSubmittedPre($gn_id)
    {
        return PreResponse::where('gn_id', $gn_id)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->latest('responseID')
            ->first();
    }


    // CREATE PRE FORM
    public function create($gn_id)
    {
        $observer = $this->getAssignedPreObserver($gn_id);

        $guru = GuruNew::with('school')
            ->where('gn_id', $gn_id)
            ->firstOrFail();

        // Only one submitted PRE is allowed per GN.
        $submittedPre = $this->getSubmittedPre($gn_id);

        if ($submittedPre) {
            return redirect()
                ->route(
                    'observer.pre.view',
                    $submittedPre->responseID
                )
                ->with(
                    'error',
                    'Pre-Observation has already been submitted.'
                );
        }

        // Resume existing draft belonging to this Observer.
        $existingResponse = PreResponse::where(
            'gn_id',
            $gn_id
        )
            ->where('observation_stage', 'PRE')
            ->where('observerID', $observer->observerID)
            ->latest('responseID')
            ->first();

        if (
            $existingResponse &&
            $existingResponse->status === 'Draft'
        ) {
            return redirect()->route(
                'observer.pre.edit',
                $existingResponse->responseID
            );
        }

        // Prevent another draft from being created for the same GN by a different Observer.
        $otherDraft = PreResponse::where('gn_id', $gn_id)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Draft')
            ->exists();

        abort_if(
            $otherDraft,
            409,
            'A PRE draft already exists for this teacher.'
        );

        $form = $this->getActiveForm();

        return view(
            'pre-observation.form',
            compact('form', 'guru', 'gn_id')
        );
    }

    // STORE PRE RESPONSE
    public function store(Request $request, $gn_id)
    {
        $observer = $this->getAssignedPreObserver($gn_id);

        GuruNew::where('gn_id', $gn_id)->firstOrFail();

        $request->validate([
            'formID' => [
                'required',
                'exists:pre_form,formID',
            ],
        ]);

        // Use the original selected form version.
        $form = $this->getForm($request->formID);

        $this->validateForm($request, $form);

        DB::transaction(function () use (
            $request,
            $gn_id,
            $observer,
            $form
        ) {

            // Lock the GN record so concurrent PRE
            // submissions for this GN are serialized.
            GuruNew::where('gn_id', $gn_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Recheck assignment inside transaction.
            $this->getAssignedPreObserver($gn_id);

            // Do not allow another PRE response
            // once one exists for this GN.
            $existingResponse = PreResponse::where(
                'gn_id',
                $gn_id
            )
                ->where('observation_stage', 'PRE')
                ->first();

            abort_if(
                $existingResponse,
                409,
                'A Pre-Observation response already exists for this teacher.'
            );

            $result = $this->calculateResult(
                $request,
                $form
            );

            $response = PreResponse::create([
                'formID' => $form->formID,
                'gn_id' => $gn_id,
                'observerID' => $observer->observerID,
                'observation_stage' => 'PRE',
                'class_name' => $request->class_name,
                'subject_name' => $request->subject_name,
                'observation_date' => $request->observation_date,
                'total_score' => $result['total_score'],
                'percentage' => $result['percentage'],
                'achievement_level' => $result['achievement_level'],
                'other_comment' => $request->other_comment,
                'status' => $request->submit_action,
            ]);

            $this->saveScores(
                $request,
                $form,
                $response
            );

            $this->saveSectionComments(
                $request,
                $form,
                $response
            );

            if ($request->submit_action === 'Submitted') {

                AuditObservation::create([
                    'teacherID' => $observer->teacherID,
                    'gn_id' => $gn_id,
                    'role' => 'Observer',
                    'stage' => 'PRE',
                    'form_name' => $form->form_name,
                    'action' => 'Submitted',
                    'audit_date' => now()->toDateString(),
                    'audit_time' => now()->format('H:i:s'),
                ]);
            }
        });

        return redirect()
            ->route('observer.manage', $gn_id)
            ->with(
                'success',
                $request->submit_action === 'Submitted'
                    ? 'Form submitted successfully'
                    : 'Form draft saved successfully'
            );
    }

    // EDIT PRE DRAFT
    public function edit($responseID)
    {
        $response = PreResponse::where(
            'responseID',
            $responseID
        )
            ->where('observation_stage', 'PRE')
            ->firstOrFail();

        $observer = $this->getAssignedPreObserver(
            $response->gn_id
        );

        abort_if(
            $response->observerID != $observer->observerID,
            403,
            'You are not allowed to edit this response'
        );

        abort_if(
            $response->status === 'Submitted',
            403,
            'Submitted form cannot be edited'
        );

        // Block editing if any PRE for the GN
        // has already been submitted.
        abort_if(
            $this->getSubmittedPre($response->gn_id),
            409,
            'PRE has already been submitted for this teacher.'
        );

        $guru = GuruNew::with('school')
            ->where('gn_id', $response->gn_id)
            ->firstOrFail();

        $gn_id = $response->gn_id;

        // Always load the original form version.
        $form = $this->getForm($response->formID);

        $existingScores = PreScore::where(
            'responseID',
            $response->responseID
        )
            ->pluck('score', 'criteriaID')
            ->toArray();

        $existingComments = PreSectionComment::where(
            'responseID',
            $response->responseID
        )
            ->pluck('comment', 'sectionID')
            ->toArray();

        return view(
            'pre-observation.edit',
            compact(
                'form',
                'guru',
                'gn_id',
                'response',
                'existingScores',
                'existingComments'
            )
        );
    }


    // UPDATE PRE DRAFT
    public function update(Request $request, $responseID)
    {
        $response = PreResponse::where(
            'responseID',
            $responseID
        )
            ->where('observation_stage', 'PRE')
            ->firstOrFail();

        $observer = $this->getAssignedPreObserver(
            $response->gn_id
        );

        abort_if(
            $response->observerID != $observer->observerID,
            403,
            'You are not allowed to edit this response'
        );

        abort_if(
            $response->status === 'Submitted',
            403,
            'Submitted Pre-Observation cannot be edited'
        );

        // Keep the original form version.
        $form = $this->getForm($response->formID);

        $this->validateForm($request, $form);

        DB::transaction(function () use (
            $request,
            $response,
            $form,
            $observer
        ) {

            GuruNew::where('gn_id', $response->gn_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedResponse = PreResponse::where(
                'responseID',
                $response->responseID
            )
                ->lockForUpdate()
                ->firstOrFail();

            // Recheck assignment before saving.
            $this->getAssignedPreObserver(
                $lockedResponse->gn_id
            );

            abort_if(
                $lockedResponse->observerID != $observer->observerID,
                403,
                'You are not allowed to update this response'
            );

            abort_if(
                $lockedResponse->status === 'Submitted',
                403,
                'Submitted Pre-Observation cannot be edited'
            );

            $anotherSubmitted = PreResponse::where(
                'gn_id',
                $lockedResponse->gn_id
            )
                ->where('observation_stage', 'PRE')
                ->where('status', 'Submitted')
                ->where(
                    'responseID',
                    '!=',
                    $lockedResponse->responseID
                )
                ->exists();

            abort_if(
                $anotherSubmitted,
                409,
                'PRE has already been submitted for this teacher.'
            );

            $result = $this->calculateResult(
                $request,
                $form
            );

            $lockedResponse->update([
                'class_name' => $request->class_name,
                'subject_name' => $request->subject_name,
                'observation_date' => $request->observation_date,
                'other_comment' => $request->other_comment,
                'total_score' => $result['total_score'],
                'percentage' => $result['percentage'],
                'achievement_level' => $result['achievement_level'],
                'status' => $request->submit_action,
            ]);

            PreScore::where(
                'responseID',
                $lockedResponse->responseID
            )->delete();

            $this->saveScores(
                $request,
                $form,
                $lockedResponse
            );

            PreSectionComment::where(
                'responseID',
                $lockedResponse->responseID
            )->delete();

            $this->saveSectionComments(
                $request,
                $form,
                $lockedResponse
            );

            if ($request->submit_action === 'Submitted') {

                AuditObservation::create([
                    'teacherID' => $observer->teacherID,
                    'gn_id' => $lockedResponse->gn_id,
                    'role' => 'Observer',
                    'stage' => 'PRE',
                    'form_name' => $form->form_name,
                    'action' => 'Submitted',
                    'audit_date' => now()->toDateString(),
                    'audit_time' => now()->format('H:i:s'),
                ]);
            }
        });

        return redirect()
            ->route('observer.manage', $response->gn_id)
            ->with(
                'success',
                $request->submit_action === 'Submitted'
                    ? 'Form submitted successfully'
                    : 'Form draft updated successfully'
            );
    }


    // VIEW SUBMITTED PRE
    public function show($responseID)
    {
        $teacher = $this->getTeacher();

        $response = PreResponse::where(
            'responseID',
            $responseID
        )
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->firstOrFail();

        // Original PRE Observer
        $observer = Observer::where(
            'teacherID',
            $teacher->teacherID
        )->first();

        $isAssignedPreObserver = false;

        if ($observer) {

            $isAssignedPreObserver = DB::table(
                'observer_assignment'
            )
                ->where('gn_id', $response->gn_id)
                ->where('assignmentType', 'pre')
                ->where(
                    'observerID',
                    $observer->observerID
                )
                ->exists();
        }

        // Explicitly assigned POST teacher
        $isAssignedPostTeacher = DB::table(
            'observer_assignment'
        )
            ->where('gn_id', $response->gn_id)
            ->where('assignmentType', 'post')
            ->where(
                'postTeacherID',
                $teacher->teacherID
            )
            ->exists();

        abort_if(
            !$isAssignedPreObserver && !$isAssignedPostTeacher,
            403,
            'You are not allowed to view this PRE response'
        );

        $guru = GuruNew::with('school')
            ->where('gn_id', $response->gn_id)
            ->firstOrFail();

        // Display the original submitted form version.
        $form = $this->getForm($response->formID);

        $scores = PreScore::where(
            'responseID',
            $response->responseID
        )
            ->pluck('score', 'criteriaID');

        $sectionComments = PreSectionComment::where(
            'responseID',
            $response->responseID
        )
            ->pluck('comment', 'sectionID');

        return view(
            'pre-observation.view',
            compact(
                'form',
                'guru',
                'response',
                'scores',
                'sectionComments'
            )
        );
    }


    // GET ACTIVE PRE FORM
    private function getActiveForm()
    {
        $form = PreForm::where('status', 'Active')
            ->with([
                'sections' => fn ($query) =>
                    $query->orderBy('display_order'),

                'sections.criteria' => fn ($query) =>
                    $query->orderBy('display_order'),
            ])
            ->orderByDesc('version')
            ->first();

        abort_if(
            !$form,
            404,
            'No active Pre-Observation form found'
        );

        return $form;
    }


    // GET EXACT PRE FORM VERSION
    private function getForm($formID)
    {
        return PreForm::where('formID', $formID)
            ->with([
                'sections' => fn ($query) =>
                    $query->orderBy('display_order'),

                'sections.criteria' => fn ($query) =>
                    $query->orderBy('display_order'),
            ])
            ->firstOrFail();
    }


    // VALIDATE PRE FORM
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

            'other_comment' => [
                'nullable',
                'string',
            ],

            'submit_action' => [
                'required',
                'in:Draft,Submitted',
            ],
        ];

        foreach ($form->sections as $section) {

            foreach ($section->criteria as $criteria) {

                $key = 'scores.' . $criteria->criteriaID;

                $rules[$key] = $isSubmit
                    ? [
                        'required',
                        'integer',
                        'min:' . $form->min_score,
                        'max:' . $form->max_score,
                    ]
                    : [
                        'nullable',
                        'integer',
                        'min:' . $form->min_score,
                        'max:' . $form->max_score,
                    ];
            }

            $rules[
                'section_comments.' . $section->sectionID
            ] = $isSubmit
                ? ['required', 'string']
                : ['nullable', 'string'];
        }

        $request->validate($rules);
    }


    // CALCULATE PRE RESULT
    private function calculateResult(Request $request, $form)
    {
        if ($request->submit_action === 'Draft') {

            return [
                'total_score' => null,
                'percentage' => null,
                'achievement_level' => null,
            ];
        }

        $totalScore = 0;
        $criteriaCount = 0;

        foreach ($form->sections as $section) {

            foreach ($section->criteria as $criteria) {

                $score = (int) $request->input(
                    'scores.' . $criteria->criteriaID
                );

                $totalScore += $score;
                $criteriaCount++;
            }
        }

        $maxScore = $criteriaCount * $form->max_score;

        $percentage = $maxScore > 0
            ? round(
                ($totalScore / $maxScore) * 100,
                2
            )
            : 0;

        if ($percentage < 40) {

            $level = 'Weak';

        } elseif ($percentage < 60) {

            $level = 'Satisfactory';

        } elseif ($percentage < 80) {

            $level = 'Good';

        } elseif ($percentage < 90) {

            $level = 'Very Good';

        } else {

            $level = 'Excellent';
        }

        return [
            'total_score' => $totalScore,
            'percentage' => $percentage,
            'achievement_level' => $level,
        ];
    }

    // SAVE PRE SCORES
    private function saveScores(
        Request $request,
        $form,
        PreResponse $response
    ) {
        foreach ($form->sections as $section) {

            foreach ($section->criteria as $criteria) {

                $score = $request->input(
                    'scores.' . $criteria->criteriaID
                );

                if ($score !== null) {

                    PreScore::create([
                        'responseID' => $response->responseID,
                        'criteriaID' => $criteria->criteriaID,
                        'score' => $score,
                    ]);
                }
            }
        }
    }


    // SAVE PRE SECTION COMMENTS
    private function saveSectionComments(
        Request $request,
        $form,
        PreResponse $response
    ) {
        foreach ($form->sections as $section) {

            $comment = $request->input(
                'section_comments.' . $section->sectionID
            );

            if (
                $comment !== null &&
                trim($comment) !== ''
            ) {

                PreSectionComment::create([
                    'responseID' => $response->responseID,
                    'sectionID' => $section->sectionID,
                    'comment' => $comment,
                ]);
            }
        }
    }
}
