<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use App\Services\Training\TrainingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingProgressController extends Controller
{
    public function index(
        Request $request,
        TrainingProgressService $trainingProgressService
    ) {
        $principal = Auth::guard('principal')->user();

        abort_if(!$principal, 403);

        $search = trim((string) $request->input('search', ''));

        // Only Active GN from the Principal's school
        $baseQuery = GuruNew::query()
            ->where('schoolID', $principal->schoolID)
            ->where('current_status', 'Active');

        // Summary across all Active teachers
        $allTeachers = (clone $baseQuery)
            ->select('gn_id')
            ->get();

        $totalTeachers = $allTeachers->count();
        $totalCompleted = 0;

        foreach ($allTeachers as $teacher) {
            $courses = $trainingProgressService->getProgress(
                $teacher->gn_id
            );

            $totalCourses = count($courses);

            $completedCourses = collect($courses)
                ->where('completed', true)
                ->count();

            if (
                $totalCourses > 0 &&
                $completedCourses === $totalCourses
            ) {
                $totalCompleted++;
            }
        }

        // Paginated teacher list
        $teachers = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('gn_name', 'like', "%{$search}%")
                        ->orWhere('gn_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('gn_name')
            ->paginate(10)
            ->withQueryString();

        // Calculate Training and Feedback progress
        $teachers->getCollection()->each(
            function ($teacher) use ($trainingProgressService) {

                $courses = $trainingProgressService->getProgress(
                    $teacher->gn_id
                );

                $totalCourses = count($courses);

                // Training completion:
                // All sessions must have Approved attendance.
                $completedTraining = collect($courses)
                    ->filter(function ($course) {
                        $totalSessions = (int) (
                            $course['total_sessions'] ?? 0
                        );

                        $approvedSessions = (int) (
                            $course['approved_sessions'] ?? 0
                        );

                        return $totalSessions > 0
                            && $approvedSessions >= $totalSessions;
                    })
                    ->count();

                // Feedback completion:
                // isFeedbackCompleted must be true.
                $completedFeedback = collect($courses)
                    ->filter(function ($course) {
                        return (bool) (
                            $course['feedback_completed'] ?? false
                        );
                    })
                    ->count();

                // Full course completion:
                // Both training and feedback must be completed.
                $fullyCompleted = collect($courses)
                    ->where('completed', true)
                    ->count();

                $teacher->total_training = $totalCourses;
                $teacher->completed_training = $completedTraining;

                $teacher->total_feedback = $totalCourses;
                $teacher->completed_feedback = $completedFeedback;

                $teacher->training_percentage = $totalCourses > 0
                    ? round(($completedTraining / $totalCourses) * 100)
                    : 0;

                $teacher->feedback_percentage = $totalCourses > 0
                    ? round(($completedFeedback / $totalCourses) * 100)
                    : 0;

                $hasStarted = collect($courses)->contains(
                    function ($course) {
                        return (int) (
                            $course['approved_sessions'] ?? 0
                        ) > 0
                        || (bool) (
                            $course['feedback_completed'] ?? false
                        );
                    }
                );

                if ($totalCourses === 0) {
                    $teacher->training_status = 'Not Started';
                } elseif ($fullyCompleted === $totalCourses) {
                    $teacher->training_status = 'Completed';
                } elseif ($hasStarted) {
                    $teacher->training_status = 'In Progress';
                } else {
                    $teacher->training_status = 'Not Started';
                }
            }
        );

        return view('principal.training-progress', compact(
            'teachers',
            'search',
            'totalTeachers',
            'totalCompleted'
        ));
    }
}
