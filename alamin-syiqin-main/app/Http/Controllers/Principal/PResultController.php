<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use App\Models\PreForm;
use App\Models\PreResponse;
use App\Models\PreScore;
use App\Models\PreSectionComment;
use App\Models\PdpcForm;
use App\Models\PdpcResponse;
use App\Models\PdpcScore;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PResultController extends Controller
{

    public function index(Request $request)
    {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403, 'Unauthorized access');

        $search = trim((string) $request->input('search', ''));

        $status = $request->input('status', 'all');

        $allowedStatuses = [
            'all',
            'Pending',
            'In Progress',
            'Repeat Required',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $perPage = 10;

        // Get teachers belonging to the principal's school.
        $teacherCollection = DB::table('guru_new')
            ->where('schoolID', $principal->schoolID)
            ->whereIn('current_status', ['Active', 'Complete'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('gn_name', 'like', "%{$search}%")
                        ->orWhere('gn_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('gn_name')
            ->get();

        // Calculate evaluation progress for every teacher.
        $teacherCollection = $teacherCollection->map(
            function ($teacher) {

                $evaluation = $this->getEvaluationData(
                    $teacher->gn_id
                );

                $teacher->pre_completed =
                    $evaluation['preCompleted'];

                $teacher->pre_total =
                    $evaluation['preTotal'];

                $teacher->pre_progress =
                    $evaluation['preProgress'];

                $teacher->external_completed =
                    $evaluation['externalCompleted'];

                $teacher->external_total =
                    $evaluation['externalTotal'];

                $teacher->external_progress =
                    $evaluation['externalProgress'];

                $teacher->post_completed =
                    $evaluation['postCompleted'];

                $teacher->post_total =
                    $evaluation['postTotal'];

                $teacher->post_progress =
                    $evaluation['postProgress'];

                $teacher->completed_forms =
                    $evaluation['completedForms'];

                $teacher->total_forms =
                    $evaluation['totalForms'];

                $teacher->overall_progress =
                    $evaluation['overallProgress'];

                $teacher->evaluation_status =
                    $evaluation['evaluationStatus'];

                return $teacher;
            }
        );

        // Total teachers before applying evaluation status filter.
        $totalTeachers = $teacherCollection->count();

        // Separate teachers based on calculated evaluation status.
        $completedCollection = $teacherCollection
            ->filter(
                fn($teacher) =>
                $teacher->evaluation_status === 'Completed'
            )
            ->values();

        $ongoingCollection = $teacherCollection
            ->filter(
                fn($teacher) =>
                $teacher->evaluation_status !== 'Completed'
            )
            ->values();

        // Status filter applies only to ongoing evaluations.
        if ($status !== 'all') {
            $ongoingCollection = $ongoingCollection
                ->filter(
                    fn($teacher) =>
                    $teacher->evaluation_status === $status
                )
                ->values();
        }

        // Counts for the page.
        $totalCompleted = $completedCollection->count();

        $totalOngoing = $ongoingCollection->count();

        // Separate pagination for the two tables.
        $ongoingPage = LengthAwarePaginator::resolveCurrentPage(
            'ongoing_page'
        );

        $completedPage = LengthAwarePaginator::resolveCurrentPage(
            'completed_page'
        );

        $ongoingTeachers = new LengthAwarePaginator(
            $ongoingCollection
                ->slice(($ongoingPage - 1) * $perPage, $perPage)
                ->values(),
            $ongoingCollection->count(),
            $perPage,
            $ongoingPage,
            [
                'path' => $request->url(),
                'pageName' => 'ongoing_page',
                'query' => $request->query(),
            ]
        );

        $completedTeachers = new LengthAwarePaginator(
            $completedCollection
                ->slice(($completedPage - 1) * $perPage, $perPage)
                ->values(),
            $completedCollection->count(),
            $perPage,
            $completedPage,
            [
                'path' => $request->url(),
                'pageName' => 'completed_page',
                'query' => $request->query(),
            ]
        );

        return view('principal.listresult', compact(
            'ongoingTeachers',
            'completedTeachers',
            'totalTeachers',
            'totalOngoing',
            'totalCompleted',
            'search',
            'status'
        ));
    }



    // SHOW SELECTED GN EVALUATION RESULT
    public function show($gn_id)
    {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403, 'Unauthorized access');

        $guru = DB::table('guru_new')
            ->where('gn_id', $gn_id)
            ->where('schoolID', $principal->schoolID)
            ->first();

        abort_if(!$guru, 404, 'Teacher not found');

        // PRE
        $pre = DB::table('pre_response')
            ->leftJoin(
                'pre_form',
                'pre_response.formID',
                '=',
                'pre_form.formID'
            )
            ->where('pre_response.gn_id', $gn_id)
            ->where('pre_response.observation_stage', 'PRE')
            ->where('pre_response.status', 'Submitted')
            ->select(
                'pre_response.*',
                'pre_form.form_name'
            )
            ->orderByDesc('pre_response.responseID')
            ->first();

        // POST PDPC - latest submitted attempt
        $postPdpc = DB::table('pdpc_response')
            ->leftJoin(
                'pdpc_form',
                'pdpc_response.formID',
                '=',
                'pdpc_form.formID'
            )
            ->where('pdpc_response.gn_id', $gn_id)
            ->where('pdpc_response.observation_stage', 'POST')
            ->where('pdpc_response.status', 'Submitted')
            ->select(
                'pdpc_response.*',
                'pdpc_form.form_name'
            )
            ->orderByDesc('pdpc_response.attempt_no')
            ->orderByDesc('pdpc_response.responseID')
            ->first();

        // POST Feedback matching latest POST PDPC
        $postFeedback = null;

        if ($postPdpc) {
            $postFeedback = DB::table('post_response')
                ->leftJoin(
                    'post_form',
                    'post_response.formID',
                    '=',
                    'post_form.formID'
                )
                ->where('post_response.gn_id', $gn_id)
                ->where(
                    'post_response.observation_stage',
                    'POST'
                )
                ->where(
                    'post_response.status',
                    'Submitted'
                )
                ->where(
                    'post_response.attempt_no',
                    $postPdpc->attempt_no
                )
                ->where(
                    'post_response.observerID',
                    $postPdpc->observerID
                )
                ->select(
                    'post_response.*',
                    'post_form.form_name'
                )
                ->orderByDesc('post_response.responseID')
                ->first();
        }

        // All submitted EXTERNAL attempts
        $externalAttempts = DB::table('pdpc_response')
            ->leftJoin(
                'pdpc_form',
                'pdpc_response.formID',
                '=',
                'pdpc_form.formID'
            )
            ->where('pdpc_response.gn_id', $gn_id)
            ->where(
                'pdpc_response.observation_stage',
                'EXTERNAL'
            )
            ->where(
                'pdpc_response.status',
                'Submitted'
            )
            ->select(
                'pdpc_response.*',
                'pdpc_form.form_name'
            )
            ->orderByDesc('pdpc_response.attempt_no')
            ->orderByDesc('pdpc_response.responseID')
            ->get();

        $externalPdpc = $externalAttempts->first();

        // EXTERNAL Feedback matching latest EXTERNAL PDPC
        $externalFeedback = null;

        if ($externalPdpc) {
            $externalFeedback = DB::table('post_response')
                ->leftJoin(
                    'post_form',
                    'post_response.formID',
                    '=',
                    'post_form.formID'
                )
                ->where('post_response.gn_id', $gn_id)
                ->where(
                    'post_response.observation_stage',
                    'EXTERNAL'
                )
                ->where(
                    'post_response.status',
                    'Submitted'
                )
                ->where(
                    'post_response.attempt_no',
                    $externalPdpc->attempt_no
                )
                ->where(
                    'post_response.externalObserverID',
                    $externalPdpc->externalObserverID
                )
                ->select(
                    'post_response.*',
                    'post_form.form_name'
                )
                ->orderByDesc('post_response.responseID')
                ->first();
        }

        $externalHistory = $externalAttempts
            ->skip(1)
            ->map(function ($attempt) use ($gn_id) {

                $attempt->feedback = DB::table('post_response')
                    ->where('gn_id', $gn_id)
                    ->where('observation_stage', 'EXTERNAL')
                    ->where('status', 'Submitted')
                    ->where('attempt_no', $attempt->attempt_no)
                    ->where('externalObserverID', $attempt->externalObserverID)
                    ->orderByDesc('responseID')
                    ->first();

                return $attempt;
            })
            ->values();

        // Previous POST attempts
        $postHistory = DB::table('pdpc_response')
            ->leftJoin(
                'pdpc_form',
                'pdpc_response.formID',
                '=',
                'pdpc_form.formID'
            )
            ->where('pdpc_response.gn_id', $gn_id)
            ->where('pdpc_response.observation_stage', 'POST')
            ->where('pdpc_response.status', 'Submitted')
            ->when($postPdpc, function ($query) use ($postPdpc) {
                $query->where(
                    'pdpc_response.responseID',
                    '!=',
                    $postPdpc->responseID
                );
            })
            ->select(
                'pdpc_response.*',
                'pdpc_form.form_name'
            )
            ->orderByDesc('pdpc_response.attempt_no')
            ->orderByDesc('pdpc_response.responseID')
            ->get();

        // Shared calculation for list and detail pages.
        $evaluation = $this->getEvaluationData($gn_id);

        $preCompleted = $evaluation['preCompleted'];
        $preTotal = $evaluation['preTotal'];

        $externalCompleted = $evaluation['externalCompleted'];
        $externalTotal = $evaluation['externalTotal'];

        $postCompleted = $evaluation['postCompleted'];
        $postTotal = $evaluation['postTotal'];

        $completedForms = $evaluation['completedForms'];
        $totalForms = $evaluation['totalForms'];

        $overallProgress = $evaluation['overallProgress'];
        $evaluationStatus = $evaluation['evaluationStatus'];

        return view(
            'principal.viewresult',
            compact(
                'guru',
                'pre',
                'postPdpc',
                'postFeedback',
                'externalPdpc',
                'externalFeedback',
                'externalHistory',
                'postHistory',
                'preCompleted',
                'preTotal',
                'externalCompleted',
                'externalTotal',
                'postCompleted',
                'postTotal',
                'completedForms',
                'totalForms',
                'overallProgress',
                'evaluationStatus'
            )
        );
    }


    private function getEvaluationData($gn_id): array
    {

        // PRE
        $pre = DB::table('pre_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->orderByDesc('responseID')
            ->first();

        $preSubmitted = $pre !== null;


        // LATEST EXTERNAL PDPC
        $externalPdpc = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        $externalFeedback = null;

        if ($externalPdpc) {
            $externalFeedback = DB::table('post_response')
                ->where('gn_id', $gn_id)
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


        // LATEST POST PDPC
        $postPdpc = DB::table('pdpc_response')
            ->where('gn_id', $gn_id)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        $postFeedback = null;

        if ($postPdpc) {
            $postFeedback = DB::table('post_response')
                ->where('gn_id', $gn_id)
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

        $postFeedbackSubmitted =
            $postFeedback !== null;

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

        $preProgress = round(
            ($preCompleted / $preTotal) * 100
        );

        $externalCompleted =
            ($externalPdpc !== null ? 1 : 0) +
            ($externalFeedbackSubmitted ? 1 : 0);

        $externalTotal = 2;

        $externalProgress = round(
            ($externalCompleted / $externalTotal) * 100
        );

        $postCompleted =
            ($postPdpc !== null ? 1 : 0) +
            ($postFeedbackSubmitted ? 1 : 0);

        $postTotal = 2;

        $postProgress = round(
            ($postCompleted / $postTotal) * 100
        );


        // OVERALL PROGRESS
        // PRE + PDPC + FEEDBACK

        $evaluationPathCompleted =
            $externalPathCompleted ||
            $postPathCompleted;

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

 
        // OVERALL EVALUATION STATUS
        if (
            $preSubmitted &&
            $evaluationPathCompleted
        ) {
            $evaluationStatus = 'Completed';
        } elseif (
            $postRepeat &&
            $postFeedbackSubmitted
        ) {
            $evaluationStatus = 'Repeat Required';
        } elseif (
            $postRepeat &&
            !$postFeedbackSubmitted
        ) {
            $evaluationStatus = 'In Progress';
        } elseif (
            $externalRepeat &&
            $externalFeedbackSubmitted &&
            !$postPdpc
        ) {
            $evaluationStatus = 'Repeat Required';
        } elseif (
            $externalRepeat &&
            !$externalFeedbackSubmitted &&
            !$postPdpc
        ) {
            $evaluationStatus = 'In Progress';
        } elseif (
            !$preSubmitted &&
            !$externalPdpc &&
            !$postPdpc
        ) {
            $evaluationStatus = 'Pending';
        } else {
            $evaluationStatus = 'In Progress';
        }

        return [
            'preCompleted' => $preCompleted,
            'preTotal' => $preTotal,
            'preProgress' => $preProgress,

            'externalCompleted' => $externalCompleted,
            'externalTotal' => $externalTotal,
            'externalProgress' => $externalProgress,

            'postCompleted' => $postCompleted,
            'postTotal' => $postTotal,
            'postProgress' => $postProgress,

            'completedForms' => $completedForms,
            'totalForms' => $totalForms,
            'overallProgress' => $overallProgress,

            'evaluationStatus' => $evaluationStatus,
        ];
    }

    // VIEW PRE RESULT
    public function pre($responseID)
    {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403, 'Unauthorized access');

        $response = PreResponse::with(['observer.teacher'])
            ->where('responseID', $responseID)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->firstOrFail();

        $guru = GuruNew::with('school')
            ->where('gn_id', $response->gn_id)
            ->where('schoolID', $principal->schoolID)
            ->firstOrFail();

        $form = PreForm::where('formID', $response->formID)
            ->with([
                'sections' => fn($query) =>
                $query->orderBy('display_order'),

                'sections.criteria' => fn($query) =>
                $query->orderBy('display_order'),
            ])
            ->firstOrFail();

        $scores = PreScore::where(
            'responseID',
            $response->responseID
        )->pluck('score', 'criteriaID');

        $sectionComments = PreSectionComment::where(
            'responseID',
            $response->responseID
        )->pluck('comment', 'sectionID');

        return view('principal.result-pre', compact(
            'guru',
            'form',
            'response',
            'scores',
            'sectionComments'
        ));
    }

    // VIEW PDPC RESULT
    public function pdpc($responseID)
    {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403, 'Unauthorized access');

        $response = PdpcResponse::with([
            'observer.teacher',
            'externalObserver.teacher',
        ])
            ->where('responseID', $responseID)
            ->whereIn(
                'observation_stage',
                ['POST', 'EXTERNAL']
            )
            ->where('status', 'Submitted')
            ->firstOrFail();

        $guru = GuruNew::with('school')
            ->where('gn_id', $response->gn_id)
            ->where('schoolID', $principal->schoolID)
            ->firstOrFail();

        $form = PdpcForm::with([
            'aspects.tums.tt.points',
            'aspects.tums.rubrics',
        ])
            ->where('formID', $response->formID)
            ->firstOrFail();

        $scores = PdpcScore::where(
            'responseID',
            $response->responseID
        )->pluck('score', 'pointID');

        $tumsResults = $this->calculateStoredTumsResults(
            $form,
            $scores
        );

        return view('principal.result-pdpc', compact(
            'guru',
            'form',
            'response',
            'scores',
            'tumsResults'
        ));
    }


    // VIEW FEEDBACK RESULT
    public function post($responseID)
    {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403, 'Unauthorized access');

        $response = DB::table('post_response')
            ->where('responseID', $responseID)
            ->whereIn(
                'observation_stage',
                ['POST', 'EXTERNAL']
            )
            ->where('status', 'Submitted')
            ->first();

        abort_if(
            !$response,
            404,
            'Feedback result not found'
        );

        $guru = $this->getGuruNewForPrincipal(
            $response->gn_id,
            $principal->schoolID
        );

        $form = $this->getPostForm(
            $response->formID
        );

        $this->attachFeedbackEvaluator($response);

        $existingAnswers = $this->getPostAnswers(
            $response->responseID,
            $form
        );

        return view('principal.result-post', compact(
            'guru',
            'form',
            'response',
            'existingAnswers'
        ));
    }


    // BUILD PDPC TUMS DISPLAY FROM SAVED SCORES
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

                // Keep existing display calculation.
                $actionScore = $totalPoints > 0
                    ? round(
                        ($actionCount / $totalPoints) * 4
                    )
                    : 0;

                $qualityMean = $totalPoints > 0
                    ? round(
                        $qualityTotal / $totalPoints,
                        2
                    )
                    : 0;

                $actionPercentage = round(
                    ($actionScore / 4) * 100,
                    2
                );

                $qualityPercentage = round(
                    ($qualityMean / 4) * 100,
                    2
                );

                $tumsPercentage = round(
                    ($actionPercentage * 0.25) +
                        ($qualityPercentage * 0.75),
                    2
                );

                $weight = (float) $tums->wajaran;

                $weightedScore = round(
                    ($tumsPercentage * $weight) / 100,
                    2
                );

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
                    'weighted_score' => $weightedScore,
                ];
            }
        }

        return $results;
    }


    // GET EXACT FEEDBACK FORM VERSION
    private function getPostForm($formID)
    {
        $form = DB::table('post_form')
            ->where('formID', $formID)
            ->first();

        abort_if(
            !$form,
            404,
            'Feedback form not found.'
        );

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
                $field->options = DB::table(
                    'post_field_option'
                )
                    ->where('fieldID', $field->fieldID)
                    ->orderBy('display_order')
                    ->get();
            }

            $section->fields = $fields;
        }

        $form->sections = $sections;

        return $form;
    }


    // GET SAVED FEEDBACK ANSWERS
    private function getPostAnswers(
        $responseID,
        $form
    ): array {
        $answers = DB::table('post_answer')
            ->where('responseID', $responseID)
            ->get();

        $fields = $form->sections->flatMap(
            fn($section) => $section->fields
        );

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

    // GET GN UNDER PRINCIPAL SCHOOL
    private function getGuruNewForPrincipal(
        $gnId,
        $schoolID
    ) {
        $guru = DB::table('guru_new')
            ->where('gn_id', $gnId)
            ->where('schoolID', $schoolID)
            ->first();

        abort_if(
            !$guru,
            404,
            'Teacher not found.'
        );

        $guru->school = $guru->schoolID
            ? DB::table('school')
            ->where(
                'schoolID',
                $guru->schoolID
            )
            ->first()
            : null;

        return $guru;
    }


    // ATTACH FEEDBACK EVALUATOR INFORMATION
    private function attachFeedbackEvaluator(
        $response
    ): void {
        $response->evaluator_name = '-';

        $response->evaluator_role =
            $response->observation_stage === 'EXTERNAL'
            ? 'External Observer'
            : 'Observer';

        // POST Observer
        if (
            $response->observation_stage === 'POST' &&
            $response->observerID
        ) {
            $observer = DB::table('observer')
                ->where(
                    'observerID',
                    $response->observerID
                )
                ->first();

            if ($observer) {
                $teacher = DB::table('teacher')
                    ->where(
                        'teacherID',
                        $observer->teacherID
                    )
                    ->first();

                $response->evaluator_name =
                    $teacher->teacherName ?? '-';
            }

            return;
        }

        // EXTERNAL Observer
        if (
            $response->observation_stage === 'EXTERNAL' &&
            $response->externalObserverID
        ) {
            $externalObserver = DB::table(
                'external_observer'
            )
                ->where(
                    'externalObserverID',
                    $response->externalObserverID
                )
                ->first();

            if ($externalObserver) {
                $teacher = DB::table('teacher')
                    ->where(
                        'teacherID',
                        $externalObserver->teacherID
                    )
                    ->first();

                $response->evaluator_name =
                    $teacher->teacherName ?? '-';
            }
        }
    }
}
