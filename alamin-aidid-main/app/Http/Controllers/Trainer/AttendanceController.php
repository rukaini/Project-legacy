<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\AttendanceQrService;
use App\Services\Lms\TrainerLmsReportService;
use App\Support\CanonicalTrainingQuery;
use App\Support\SharedTrainingSchema;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function attendance(Request $request, string $courseId, TrainerLmsReportService $reports)
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
        $report = $reports->attendance($courseId, $request);

        if ($request->ajax()) {
            return view('trainer.training.attendance.partials.report-fragment', array_merge(['course' => $course], $report));
        }

        return view('trainer.training.attendance', array_merge(['course' => $course], $report));
    }

    public function qrPresentation(string $courseId, string $sessionId, AttendanceQrService $attendanceQrs)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        abort_unless($this->trainerCanAccessCourse($courseId), 403);

        $course = SharedTrainingSchema::course($courseId);
        abort_unless($course, 404);
        $courseId = (string) $course->course_id;

        $trainingSessionQuery = SharedTrainingSchema::constrainSession(
            CanonicalTrainingQuery::sessions(),
            $sessionId
        );
        SharedTrainingSchema::constrainSessionsToCourse($trainingSessionQuery, $course);
        $trainingSession = $trainingSessionQuery->first();
        abort_unless($trainingSession, 404);

        $attendanceQr = $attendanceQrs->forSession($trainingSession, $courseId);
        abort_unless($attendanceQr['can_view'] && $attendanceQr['image_url'], 404);

        $sessionTitle = trim((string) ($trainingSession->session_title ?? '')) ?: 'Training Session';
        $startsAt = $attendanceQr['starts_at'];
        $endsAt = $attendanceQr['ends_at'];
        $isLms = strtolower((string) ($course->training_mode ?? 'lms')) === 'lms';

        return response()
            ->view('trainer.attendance.qr-presentation', [
                'attendanceQr' => $attendanceQr,
                'attendanceRoute' => route(
                    $isLms ? 'trainer.courses.attendance' : 'trainer.simple.attendance',
                    ['course_id' => $courseId, 'session_id' => $sessionId]
                ),
                'course' => $course,
                'sessionTitle' => $sessionTitle,
                'sessionDateLabel' => $startsAt->format('d M Y'),
                'sessionTimeLabel' => $startsAt->format('h:i A').' – '.$endsAt->format('h:i A'),
            ])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function trainerCanAccessCourse(string $courseId): bool
    {
        $trainerId = SharedTrainingSchema::currentTrainerId();

        if ($trainerId === '') {
            return false;
        }

        return SharedTrainingSchema::trainerAssignedToCourse($trainerId, $courseId);
    }
}
