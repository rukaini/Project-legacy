<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use App\Services\Training\TrainingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class TeacherProgressController extends Controller
{
    public function index(
        Request $request,
        TrainingProgressService $trainingProgressService
    ) {
        $search = trim((string) $request->input('search', ''));

        // Only Active and Complete new teachers.
        $query = GuruNew::with('school')
            ->whereIn('current_status', ['Active', 'Complete']);

        // Search by teacher name, ID, email or school.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('gn_name', 'like', "%{$search}%")
                    ->orWhere('gn_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('school', function ($schoolQuery) use ($search) {
                        $schoolQuery->where('schoolName', 'like', "%{$search}%");
                    });
            });
        }

        $teachers = $query
            ->orderBy('gn_name')
            ->get();

        // Check Observation, Training and Feedback completion.
        $teachers = $teachers
            ->filter(function ($teacher) use ($trainingProgressService) {

                // 1. OBSERVATION COMPLETION
                $observation = $this->getObservationCompletion(
                    $teacher->gn_id
                );

                if (!$observation['completed']) {
                    return false;
                }

                // 2. TRAINING AND FEEDBACK COMPLETION
                $courses = $trainingProgressService->getProgress(
                    $teacher->gn_id
                );

                $totalCourses = count($courses);

                // A teacher must have at least one LMS course.
                if ($totalCourses === 0) {
                    return false;
                }

                $completedCourses = collect($courses)
                    ->where('completed', true)
                    ->count();

                $completedFeedback = collect($courses)
                    ->where('feedback_completed', true)
                    ->count();

                // All courses and feedback must be completed.
                if (
                    $completedCourses !== $totalCourses ||
                    $completedFeedback !== $totalCourses
                ) {
                    return false;
                }

                // Teacher has completed all requirements.
                $teacher->observation_completed = true;
                $teacher->passed_at = $observation['passed_at'];

                $teacher->observation_count = 3;
                $teacher->observation_total = 3;

                $teacher->training_progress = $courses;

                $teacher->completed_training = $completedCourses;
                $teacher->total_training = $totalCourses;

                $teacher->completed_feedback = $completedFeedback;
                $teacher->total_feedback = $totalCourses;

                return true;
            })
            ->values();

        // Pagination after all completion filters.
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;

        $paginatedTeachers = new LengthAwarePaginator(
            $teachers->forPage($page, $perPage)->values(),
            $teachers->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('hr.teacher-progress', [
            'teachers' => $paginatedTeachers,
            'search' => $search,
        ]);
    }

    private function getObservationCompletion(string $gnId): array
    {
        $incomplete = [
            'completed' => false,
            'passed_at' => null,
        ];

        // PRE must be submitted.
        $preSubmitted = DB::table('pre_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->exists();

        if (!$preSubmitted) {
            return $incomplete;
        }

        // Submitted EXTERNAL attempts.
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

            // EXTERNAL PASS + matching Feedback Submitted.
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

        // POST is allowed only after EXTERNAL REPEAT + Feedback.
        if (!$hasExternalRepeatWithFeedback) {
            return $incomplete;
        }

        // Submitted POST attempts.
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

            // POST PASS + matching Feedback Submitted.
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
