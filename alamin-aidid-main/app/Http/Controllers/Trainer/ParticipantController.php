<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Lms\TrainerLmsReportService;
use App\Support\SharedTrainingSchema;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function participants(Request $request, string $courseId, TrainerLmsReportService $reports)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()
                ->route('trainer.courses')
                ->with('error', 'You are not assigned to this training.');
        }

        $course = SharedTrainingSchema::course($courseId);

        if (! $course) {
            return redirect()
                ->route('trainer.courses')
                ->with('error', 'Training not found.');
        }

        $courseId = (string) $course->course_id;
        $report = $reports->participants($courseId, $request);

        if ($request->ajax()) {
            return view('trainer.training.participants.partials.results', array_merge(['course' => $course], $report));
        }

        return view('trainer.training.participants', array_merge(['course' => $course], $report));
    }

    private function trainerCanAccessCourse(string $courseId): bool
    {
        return SharedTrainingSchema::trainerAssignedToCourse(
            SharedTrainingSchema::currentTrainerId(),
            $courseId
        );
    }
}
