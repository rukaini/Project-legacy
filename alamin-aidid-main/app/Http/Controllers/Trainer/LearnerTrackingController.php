<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Lms\TrainerLmsLearnerTrackingReportService;
use App\Support\SharedTrainingSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerTrackingController extends Controller
{
    public function learnerTracking(
        Request $request,
        string $courseId,
        TrainerLmsLearnerTrackingReportService $reports
    ) {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()->route('trainer.courses')->with('error', 'You are not assigned to this training.');
        }

        $course = SharedTrainingSchema::course($courseId);
        if (! $course) {
            return redirect()->route('trainer.courses')->with('error', 'Training not found.');
        }

        $courseId = (string) $course->course_id;
        $data = $reports->build($courseId, $request->query());
        $data['course'] = $course;

        if ($request->boolean('fragment')) {
            return new JsonResponse([
                'html' => view('trainer.training.learner-tracking.partials.content', $data)->render(),
                'title' => 'Learner Tracking | Al Amin Edu Oasis',
            ]);
        }

        return view('trainer.training.learner-tracking', $data);
    }

    private function formatTrackingSeconds(int $seconds): string
    {
        return TrainerLmsLearnerTrackingReportService::formatDuration($seconds);
    }

    private function trainerCanAccessCourse(string $courseId): bool
    {
        return SharedTrainingSchema::trainerAssignedToCourse(
            SharedTrainingSchema::currentTrainerId(),
            $courseId
        );
    }
}
