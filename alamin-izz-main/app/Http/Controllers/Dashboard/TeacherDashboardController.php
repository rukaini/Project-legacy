<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\Notification;
use App\Models\PenyelarasLadap;
use App\Models\Tarbiah;
use App\Models\Teacher;
use App\Models\Training;
use App\Services\CertificateService;
use App\Services\PenyelarasLadapService;
use App\Services\TarbiahService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    public function __construct(
        private TeacherRepositoryInterface $teachers,
        private TarbiahService $tarbiahService,
        private PenyelarasLadapService $penyelarasLadapService,
        private CertificateService $certificateService,
    ) {}

    public function index(Request $request): Response
    {
        $user       = $request->user();
        $teacher    = $this->teachers->findByEmail($user->email);
        $schoolName = $teacher?->schoolName;

        $approvedApplications = $teacher
            ? Application::where('teacherID', $teacher->id)
                ->where('status', 'approved')
                ->with('training')
                ->get()
            : collect();

        $completedApps = $approvedApplications->filter(fn($app) => $app->training && $app->training->status === 'completed');

        $currentYear = now()->year;

        $allApprovedCourseIds = $approvedApplications->pluck('training.courseID')->filter();
        $sessionIds           = CourseSession::whereIn('courseID', $allApprovedCourseIds)
            ->whereYear('sessionDate', $currentYear)
            ->pluck('sessionID');
        $totalLadapHours      = $teacher
            ? round((float) Attendance::where('teacher_id', $teacher->id)->whereIn('session_id', $sessionIds)->sum('hours_ladap'), 2)
            : 0.0;

        $teacherModel        = Teacher::where('email', $user->email)->first();
        $totalTarbiahHours   = $teacherModel ? $this->tarbiahService->totalLadapHours($teacherModel) : 0.0;
        $totalPenyelarasLadapHours = $teacherModel
            ? $this->penyelarasLadapService->totalHours($teacherModel) + $this->certificateService->totalHours($teacherModel)
            : 0.0;

        $upcomingTrainings = $approvedApplications
            ->filter(fn($app) => $app->training && $app->training->status === 'upcoming')
            ->values()
            ->map(fn($app) => [
                'type'      => 'training',
                'id'        => $app->training->getKey(),
                'title'     => $app->training->courseName,
                'mode'      => $app->training->mode,
                'organiser' => $app->training->organiserName,
                'location'  => null,
                'date'      => null,
            ]);

        $schoolId = $teacherModel?->schoolID;

        $upcomingPenyelarasLadap = $schoolId
            ? PenyelarasLadap::sessions()->where('schoolID', $schoolId)->get()
                ->filter(fn (PenyelarasLadap $s) => $this->penyelarasLadapService->status($s) === 'upcoming')
                ->map(fn (PenyelarasLadap $s) => [
                    'type'      => 'penyelaras_ladap',
                    'id'        => $s->id,
                    'title'     => $s->title,
                    'mode'      => null,
                    'organiser' => null,
                    'location'  => $s->location,
                    'date'      => $s->session_date->format('Y-m-d'),
                ])
                ->values()
            : collect();

        $upcomingTarbiah = $schoolId
            ? Tarbiah::where('schoolID', $schoolId)->get()
                ->filter(fn (Tarbiah $t) => $this->tarbiahService->status($t) === 'upcoming')
                ->map(fn (Tarbiah $t) => [
                    'type'      => 'tarbiah',
                    'id'        => $t->tarbiah_id,
                    'title'     => $t->title,
                    'mode'      => null,
                    'organiser' => null,
                    'location'  => $t->location,
                    'date'      => $t->session_date->format('Y-m-d'),
                ])
                ->values()
            : collect();

        $upcoming = $upcomingTrainings
            ->concat($upcomingPenyelarasLadap)
            ->concat($upcomingTarbiah)
            ->sortBy(fn ($row) => $row['date'] ?? '9999-99-99')
            ->values();

        return Inertia::render('dashboard/teacher', [
            'display_name'          => $teacher?->name ?? $user->name,
            'upcoming'              => $upcoming,
            'pending_applications'  => $teacher
                ? Application::where('teacherID', $teacher->id)->where('status', 'pending')->count()
                : 0,
            'total_ladap_hours'     => round($totalLadapHours + $totalPenyelarasLadapHours, 2),
            'total_tarbiah_hours'   => $totalTarbiahHours,
            'completed_count'       => $completedApps->count(),
            'unread_notifications'  => Notification::forRecipient($user)
                ->where('is_read', false)
                ->count(),
            'school_name'           => $schoolName,
        ]);
    }
}
