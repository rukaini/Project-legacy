<?php

namespace App\Http\Controllers\NewTeacher;

use App\Http\Controllers\Controller;
use App\Models\PreResponse;
use App\Models\PdpcResponse;
use App\Models\PostResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\Training\TrainingProgressService;

class NewTeacherDashboardController extends Controller
{
    // Show GN dashboard
    public function index(TrainingProgressService $trainingProgressService)
    {
        $guru = Auth::guard('new_teacher')->user();

        abort_if(
            !$guru,
            403,
            'Unauthorized access'
        );

        $gnId = $guru->gn_id;

        // Get submitted PRE
        $pre = PreResponse::with('form')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->orderByDesc('responseID')
            ->first();

        // Get all submitted EXTERNAL PDPC attempts
        $externalAttempts = PdpcResponse::with('form')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        // Get latest EXTERNAL PDPC attempt
        $externalPdpc = $externalAttempts->first();

        // Get latest submitted EXTERNAL Feedback
        $externalFeedback = PostResponse::with('form')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->first();

        // Get submitted POST PDPC by Observer
        $postPdpc = PdpcResponse::with('form')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('responseID')
            ->first();

        // Get submitted POST Feedback by Observer
        $postFeedback = PostResponse::with('form')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('responseID')
            ->first();

        // PRE STATUS
        $preStatus = $pre
            ? 'completed'
            : 'pending';

        // EXTERNAL STATUS
        // the latest submitted EXTERNAL PDPC attempt.
        $externalFeedbackMatched =
            $externalPdpc !== null &&
            $externalFeedback !== null &&
            (int) $externalFeedback->attempt_no ===
            (int) $externalPdpc->attempt_no;

        // Check whether latest EXTERNAL PDPC passed.
        $externalPassed =
            $externalPdpc !== null &&
            $externalPdpc->result === 'PASS';

        if (!$externalPdpc && !$externalFeedback) {

            $externalStatus = 'pending';
        } elseif (
            $externalPdpc &&
            $externalPdpc->result === 'REPEAT'
        ) {

            $externalStatus = 'repeat';
        } elseif (
            $externalPassed &&
            $externalFeedbackMatched
        ) {

            $externalStatus = 'completed';
        } else {

            $externalStatus = 'in_progress';
        }


        // POST STATUS

        $postBothCompleted =
            $postPdpc !== null &&
            $postFeedback !== null;

        if ($postBothCompleted) {

            $postStatus = 'completed';
        } elseif ($postPdpc || $postFeedback) {

            $postStatus = 'in_progress';
        } else {

            $postStatus = 'pending';
        }


        // PRE + PDPC + FEEDBACK
        // Component 1: PRE submitted
        $preCompleted = $pre !== null;

        $externalFeedbackCompleted =
            $externalPassed &&
            $externalFeedbackMatched;

        // EXTERNAL PASS or Observer POST 
        $pdpcCompleted =
            $externalPassed ||
            $postBothCompleted;

        $feedbackCompleted =
            $externalFeedbackCompleted ||
            $postBothCompleted;

        // Count completed components
        $submittedForms =
            ($preCompleted ? 1 : 0)
            +
            ($pdpcCompleted ? 1 : 0)
            +
            ($feedbackCompleted ? 1 : 0);

        // Only 3 evaluation components
        $totalForms = 3;

        // Calculate completion percentage
        $submittedPercentage = round(
            ($submittedForms / $totalForms) * 100
        );

        // OVERALL EVALUATION STATUS

        $evaluationPathCompleted =
            $externalFeedbackCompleted ||
            $postBothCompleted;

        if (
            $preCompleted &&
            $evaluationPathCompleted
        ) {

            $evaluationStatus = 'Completed';
        } elseif (
            $externalPdpc &&
            $externalPdpc->result === 'REPEAT'
        ) {

            $evaluationStatus = 'Repeat Required';
        } elseif (
            !$pre &&
            !$externalPdpc &&
            !$externalFeedback &&
            !$postPdpc &&
            !$postFeedback
        ) {

            $evaluationStatus = 'Pending';
        } else {

            $evaluationStatus = 'In Progress';
        }

        // =====================================================
        // TRAINING PROGRESS - LMS
        // =====================================================

        $trainingCourses = $trainingProgressService->getProgress($gnId);

        $totalTrainingCourses = count($trainingCourses);

        $completedTrainingCourses = collect($trainingCourses)
            ->where('completed', true)
            ->count();

        $trainingPercentage = $totalTrainingCourses > 0
            ? round(($completedTrainingCourses / $totalTrainingCourses) * 100)
            : 0;

        $trainingHasStarted = collect($trainingCourses)->contains(
            fn($course) => ($course['approved_sessions'] ?? 0) > 0
                || ($course['feedback_completed'] ?? false)
        );

        if ($totalTrainingCourses === 0) {

            $trainingStatus = 'Not Started';
        } elseif ($completedTrainingCourses === $totalTrainingCourses) {

            $trainingStatus = 'Completed';
        } elseif ($trainingHasStarted) {

            $trainingStatus = 'In Progress';
        } else {

            $trainingStatus = 'Not Started';
        }

        return view(
            'newteacher.dashboard',
            compact(
                'guru',
                'pre',
                'externalAttempts',
                'externalPdpc',
                'externalFeedback',
                'postPdpc',
                'postFeedback',
                'preStatus',
                'externalStatus',
                'postStatus',
                'submittedForms',
                'totalForms',
                'submittedPercentage',
                'evaluationStatus',
                'evaluationStatus',
                'trainingCourses',
                'totalTrainingCourses',
                'completedTrainingCourses',
                'trainingPercentage',
                'trainingStatus'
            )
        );
    }
}
