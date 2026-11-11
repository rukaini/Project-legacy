<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ReportController extends Controller
{
    public function attendanceLetter(Training $training): BinaryFileResponse
    {
        $approvedApplications = Application::with('teacher')
            ->where('course_id', $training->getKey())
            ->where('status', 'approved')
            ->get();

        $sessionIds        = CourseSession::where('courseID', $training->getKey())->pluck('sessionID');
        $attendedTeacherIds = Attendance::whereIn('session_id', $sessionIds)->pluck('teacher_id')->unique();
        $presentEmails      = Teacher::whereIn('teacherID', $attendedTeacherIds)->pluck('email');
        $usersByEmail       = User::whereIn('email', $presentEmails)->get()->keyBy('email');

        $participants = $approvedApplications
            ->map(fn ($app) => $app->teacher ? $usersByEmail->get($app->teacher->email) : null)
            ->filter();

        $zipPath = storage_path('app/temp/letters-' . $training->getKey() . '-' . time() . '.zip');
        @mkdir(dirname($zipPath), 0755, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($participants as $user) {
            $pdf = Pdf::loadView('pdf.attendance-letter', [
                'user'     => $user,
                'training' => $training,
                'date'     => now(),
            ]);

            $filename = sprintf(
                'letter-%s.pdf',
                preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($user->name))
            );

            $zip->addFromString($filename, $pdf->output());
        }

        $zip->close();

        $zipName = sprintf(
            'attendance-letters-%s.zip',
            preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($training->courseName))
        );

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
