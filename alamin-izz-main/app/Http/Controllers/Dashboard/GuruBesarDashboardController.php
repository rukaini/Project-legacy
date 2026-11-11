<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\Teacher;
use App\Models\TarbiahAttendance;
use App\Models\Training;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\CertificateService;
use App\Services\PenyelarasLadapService;
use App\Services\TarbiahService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuruBesarDashboardController extends Controller
{
    public function __construct(
        private PrincipalRepositoryInterface $principals,
        private ApplicationService $applicationService,
        private PenyelarasLadapService $penyelarasLadapService,
        private CertificateService $certificateService,
        private TarbiahService $tarbiahService,
    ) {}

    public function index(Request $request): Response
    {
        $user     = $request->user();
        $dto        = $this->principals->findByEmail($user->email);
        $schoolId   = $dto?->schoolID;
        $schoolName = $dto?->schoolName;

        // Teachers from the GB's school only
        $schoolTeachers       = Teacher::whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())->get();
        $teacherByEmail       = $schoolTeachers->keyBy('email');
        $schoolTeacherEmails  = $schoolTeachers->pluck('email');

        $recentApplications = Application::with(['teacher', 'training'])
            ->where('status', 'pending')
            ->whereIn('teacherID', $schoolTeachers->pluck('teacherID'))
            ->latest('applied_at')
            ->take(5)
            ->get()
            ->map(fn ($app) => [
                'id'         => $app->getRouteKey(),
                'applicant'  => $app->teacher?->teacherName ?? '—',
                'training'   => $app->training->courseName ?? '—',
                'applied_at' => $app->applied_at,
            ]);

        $upcomingTrainings = Training::where('status', 'upcoming')
            ->orderBy('courseName')
            ->orderBy('courseID')
            ->take(3)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->getKey(),
                'title'    => $t->courseName,
                'mode'     => $t->mode,
                'capacity' => $t->capacity,
            ]);

        $currentYear = now()->year;

        $teacherAttendance = $schoolTeachers
            ->map(function (Teacher $teacherRecord) use ($currentYear) {

                $approvedCourseIds = $teacherRecord
                    ? Application::where('teacherID', $teacherRecord->teacherID)
                        ->where('status', 'approved')
                        ->pluck('course_id')
                    : collect();

                $sessions   = CourseSession::whereIn('courseID', $approvedCourseIds)
                    ->whereYear('sessionDate', $currentYear)
                    ->get();
                $sessionIds = $sessions->pluck('session_id');
                $totalDays  = $sessions->groupBy(fn ($s) => $s->session_date->format('Y-m-d'))->count();

                $attendedIds = $teacherRecord
                    ? Attendance::where('teacher_id', $teacherRecord->teacherID)->whereIn('session_id', $sessionIds)->pluck('session_id')
                    : collect();
                $attended = $sessions
                    ->filter(fn ($s) => $attendedIds->contains($s->session_id))
                    ->groupBy(fn ($s) => $s->session_date->format('Y-m-d'))
                    ->count();
                $ladapHours = round(
                    $this->applicationService->totalLadapHours($teacherRecord, $currentYear)
                    + $this->penyelarasLadapService->totalHours($teacherRecord, $currentYear)
                    + $this->certificateService->totalHours($teacherRecord, $currentYear)
                    + $this->tarbiahService->totalLadapHours($teacherRecord, $currentYear),
                    2
                );
                $latestAttendance = collect([
                    Attendance::where('teacher_id', $teacherRecord->teacherID)->whereIn('session_id', $sessionIds)->max('created_at'),
                    Attendance::where('teacher_id', $teacherRecord->teacherID)
                        ->where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
                        ->max('created_at'),
                    TarbiahAttendance::where('teacher_id', $teacherRecord->teacherID)->max('created_at'),
                ])->filter()->max();

                return [
                    'id'                   => $teacherRecord->teacherID,
                    'name'                 => $teacherRecord->teacherName,
                    'email'                => $teacherRecord->email,
                    'ic_number'            => $teacherRecord?->ICNumber,
                    'total_trainings'      => $approvedCourseIds->count(),
                    'total_sessions'       => $totalDays,
                    'attended'             => $attended,
                    'ladap_hours'          => $ladapHours,
                    'latest_attendance_at' => $latestAttendance,
                    'attendance_rate'      => $totalDays > 0
                        ? round(($attended / $totalDays) * 100, 1)
                        : null,
                ];
            })
            ->sortByDesc('latest_attendance_at')
            ->take(5)
            ->values();

        return Inertia::render('dashboard/guru-besar', [
            'display_name'                   => $dto?->name ?? $user->name,
            'school_name'                    => $schoolName,
            'active_trainings'               => Training::whereIn('status', ['upcoming', 'ongoing'])->count(),
            'pending_applications'            => Application::where('status', 'pending')
                ->whereIn('teacherID', $schoolTeachers->pluck('teacherID'))
                ->count(),
            'completed_trainings_this_month'  => Training::where('status', 'completed')
                ->whereHas('courseSessions', fn ($query) => $query
                    ->whereYear('sessionDate', now()->year)
                    ->whereMonth('sessionDate', now()->month))
                ->whereDoesntHave('courseSessions', fn ($query) => $query
                    ->where('sessionDate', '>', now()->endOfMonth()->toDateString()))
                ->count(),
            'total_teachers'                 => $schoolTeachers->count(),
            'recent_applications'            => $recentApplications,
            'upcoming_trainings'             => $upcomingTrainings,
            'teacher_attendance'             => $teacherAttendance,
        ]);
    }
}
