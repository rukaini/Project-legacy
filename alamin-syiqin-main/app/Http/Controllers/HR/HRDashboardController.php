<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use App\Services\Training\TrainingProgressService;
use Illuminate\Support\Facades\DB;

class HRDashboardController extends Controller
{
    public function index(
        TrainingProgressService $trainingProgressService
    ) {
        // Count all new teachers
        $totalTeachers = GuruNew::count();

        // Count active teachers
        $activeTeachers = GuruNew::where(
            'current_status',
            'Active'
        )->count();

        // Count inactive teachers
        $inactiveTeachers = GuruNew::where(
            'current_status',
            'Inactive'
        )->count();

        // Count teachers marked Complete by HR
        $completeTeachers = GuruNew::where(
            'current_status',
            'Complete'
        )->count();

        // Get latest five registered teachers
        $recentTeachers = GuruNew::with('school')
            ->orderByDesc('appointed_date')
            ->take(5)
            ->get();

        // Get Active and Complete teachers for completion list
        $completedTeachers = GuruNew::with('school')
            ->whereIn('current_status', ['Active', 'Complete'])
            ->orderBy('gn_name')
            ->get()
            ->filter(function ($teacher) use ($trainingProgressService) {

                // OBSERVATION COMPLETION
                $observation = $this->getObservationCompletion(
                    $teacher->gn_id
                );

                if (!$observation['completed']) {
                    return false;
                }

                // TRAINING AND FEEDBACK COMPLETION
                $courses = $trainingProgressService->getProgress(
                    $teacher->gn_id
                );

                $totalCourses = count($courses);

                // Must have at least one LMS course
                if ($totalCourses === 0) {
                    return false;
                }

                $completedCourses = collect($courses)
                    ->where('completed', true)
                    ->count();

                $completedFeedback = collect($courses)
                    ->where('feedback_completed', true)
                    ->count();

                // All courses and feedback must be completed
                if (
                    $completedCourses !== $totalCourses ||
                    $completedFeedback !== $totalCourses
                ) {
                    return false;
                }

                // Set completion information
                $teacher->observation_count = 3;
                $teacher->observation_total = 3;

                $teacher->completed_training = $completedCourses;
                $teacher->total_training = $totalCourses;

                $teacher->completed_feedback = $completedFeedback;
                $teacher->total_feedback = $totalCourses;

                $teacher->passed_at = $observation['passed_at'];

                return true;
            })
            ->take(5)
            ->values();

        return view(
            'hr.dashboard',
            compact(
                'totalTeachers',
                'activeTeachers',
                'inactiveTeachers',
                'completeTeachers',
                'recentTeachers',
                'completedTeachers'
            )
        );
    }

    private function getObservationCompletion(string $gnId): array
    {
        $incomplete = [
            'completed' => false,
            'passed_at' => null,
        ];

        // PRE must be submitted
        $preSubmitted = DB::table('pre_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->exists();

        if (!$preSubmitted) {
            return $incomplete;
        }

        // Get submitted EXTERNAL attempts
        $externalAttempts = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        if ($externalAttempts->isEmpty()) {
            return $incomplete;
        }

        $hasExternalRepeatWithFeedback = false;

        foreach ($externalAttempts as $external) {

            $feedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where(
                    'externalObserverID',
                    $external->externalObserverID
                )
                ->where('attempt_no', $external->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            if (!$feedbackSubmitted) {
                continue;
            }

            $result = $this->getResult($external);

            // EXTERNAL PASS + matching feedback
            if ($result === 'PASS') {
                return [
                    'completed' => true,
                    'passed_at' => 'EXTERNAL',
                ];
            }

            if ($result === 'REPEAT') {
                $hasExternalRepeatWithFeedback = true;
            }
        }

        // POST allowed only after EXTERNAL REPEAT + feedback
        if (!$hasExternalRepeatWithFeedback) {
            return $incomplete;
        }

        // Get submitted POST attempts
        $postAttempts = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        foreach ($postAttempts as $post) {

            if ($this->getResult($post) !== 'PASS') {
                continue;
            }

            $feedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'POST')
                ->where('observerID', $post->observerID)
                ->where('attempt_no', $post->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            // POST PASS + matching feedback
            if ($feedbackSubmitted) {
                return [
                    'completed' => true,
                    'passed_at' => 'POST',
                ];
            }
        }

        return $incomplete;
    }

    private function getResult($response): ?string
    {
        if (!$response) {
            return null;
        }

        $result = strtoupper(trim(
            (string) ($response->result ?? '')
        ));

        if (in_array($result, ['PASS', 'REPEAT'], true)) {
            return $result;
        }

        if ($response->percentage === null) {
            return null;
        }

        return (float) $response->percentage >= 85
            ? 'PASS'
            : 'REPEAT';
    }
}
