<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Attendance;
use App\Models\AttendanceGuruBaru;
use App\Models\AttendanceOutsider;
use App\Models\CanonicalSsoUser;
use App\Models\CourseSession;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\GuruNew;
use App\Models\Outsider;
use App\Models\PenyelarasLadap;
use App\Models\Tarbiah;
use App\Models\TarbiahAttendance;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\CertificateService;
use App\Services\TarbiahService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class TrainingHistoryController extends Controller
{
    public function __construct(
        private PrincipalRepositoryInterface $principals,
        private TeacherRepositoryInterface $teachers,
        private TarbiahService $tarbiahService,
        private CertificateService $certificateService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $year = (int) $request->input('year', now()->year);

        if ($user->isOutsider()) {
            $rows = $this->historyForOutsider($user, $request->input('status'), $year);
        } elseif ($user->isGuruBaru()) {
            $rows = $this->historyForGuruBaru($user, $request->input('status'), $year);
        } else {
            $rows = $this->historyFor($user, $request->input('status'), $year);
        }

        $tarbiahRows         = $user->isTeacher() ? $this->tarbiahHistoryFor($user, $year) : collect();
        $penyelarasLadapRows = $user->isTeacher() ? $this->penyelarasLadapHistoryFor($user, $year) : collect();
        $certificateRows     = $user->isTeacher() ? $this->certificateHistoryFor($user, $year) : collect();

        $totalLadapTraining   = (float) $rows->sum('ladap_hours');
        $totalLadapPenyelaras = (float) $penyelarasLadapRows->sum('ladap_hours');
        $totalLadapCert       = (float) $certificateRows->sum('ladap_hours');
        $totalLadapTarbiah    = (float) $tarbiahRows->sum('ladap_hours');

        return Inertia::render('history/index', [
            'rows'                       => $rows->concat($penyelarasLadapRows)->concat($certificateRows)->values(),
            'tarbiah_rows'               => $tarbiahRows,
            'filters'                    => $request->only(['status']),
            'viewing_self'               => true,
            'viewing_user'               => null,
            'selected_year'              => $year,
            'available_years'            => $this->availableYearsFor($user),
            'total_ladap'                => round($totalLadapTraining + $totalLadapPenyelaras + $totalLadapCert + $totalLadapTarbiah, 2),
            'total_ladap_training'       => round($totalLadapTraining + $totalLadapPenyelaras + $totalLadapCert, 2),
            'total_ladap_training_only'  => round($totalLadapTraining, 2),
            'total_ladap_penyelaras'     => round($totalLadapPenyelaras, 2),
            'total_ladap_certificate'    => round($totalLadapCert, 2),
            'total_ladap_tarbiah'        => round($totalLadapTarbiah, 2),
            'ladap_training_target'      => ApplicationService::LADAP_TARGET_HOURS,
            'ladap_tarbiah_target'       => TarbiahService::LADAP_TARGET_HOURS,
            'is_outsider'                => $user->isOutsider(),
            'is_guru_baru'               => $user->isGuruBaru(),
        ]);
    }

    /**
     * Distinct years the user has training (and, for teachers, Tarbiah)
     * attendance in, plus the current year (always included so the
     * selector never looks empty).
     *
     * @return array<int>
     */
    private function availableYearsFor(User $user): array
    {
        $years = collect();

        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                $approvedCourseIds = Application::where('teacherID', $teacher->teacherID)
                    ->where('status', 'approved')
                    ->pluck('course_id');

                $attendedSessionIds = Attendance::where('teacher_id', $teacher->teacherID)
                    ->pluck('session_id');

                $years = $years->concat(
                    CourseSession::whereIn('courseID', $approvedCourseIds)
                        ->whereIn('sessionID', $attendedSessionIds)
                        ->get()
                        ->map(fn (CourseSession $s) => $s->session_date->year)
                );

                $years = $years->concat(
                    $this->certificateService->approvedFor($teacher)->get()->map(fn (PenyelarasLadap $c) => $c->session_date->year)
                );

                $tarbiahIds = TarbiahAttendance::where('teacher_id', $teacher->teacherID)->pluck('tarbiah_id');
                $years = $years->concat(
                    Tarbiah::whereIn('tarbiah_id', $tarbiahIds)->get()->map(fn (Tarbiah $t) => $t->session_date->year)
                );
            }
        } elseif ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            if ($outsider) {
                $sessionIds = AttendanceOutsider::where('outsider_id', $outsider->outsider_id)
                    ->pluck('session_id');
                $years = $years->concat(
                    CourseSession::whereIn('sessionID', $sessionIds)->get()->map(fn (CourseSession $s) => $s->session_date->year)
                );
            }
        } elseif ($user->isGuruBaru()) {
            $guruNew = GuruNew::where('email', $user->email)->first();
            if ($guruNew) {
                $sessionIds = AttendanceGuruBaru::where('gn_id', $guruNew->gn_id)
                    ->pluck('session_id');
                $years = $years->concat(
                    CourseSession::whereIn('sessionID', $sessionIds)->get()->map(fn (CourseSession $s) => $s->session_date->year)
                );
            }
        }

        return $years->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function tarbiahHistoryFor(User $user, ?int $year = null): Collection
    {
        $teacher = Teacher::where('email', $user->email)->first();
        if (! $teacher) {
            return collect();
        }

        $tarbiahIds = TarbiahAttendance::where('teacher_id', $teacher->teacherID)
            ->pluck('tarbiah_id');

        return Tarbiah::whereIn('tarbiah_id', $tarbiahIds)
            ->when($year !== null, fn ($q) => $q->whereYear('session_date', $year))
            ->orderByDesc('session_date')
            ->get()
            ->map(fn (Tarbiah $t) => [
                'tarbiah_id'   => $t->tarbiah_id,
                'title'        => $t->title,
                'session_date' => $t->session_date->format('Y-m-d'),
                'start_time'   => $t->start_time,
                'end_time'     => $t->end_time,
                'location'     => $t->location,
                'ladap_hours'  => $this->tarbiahService->sessionHours($t),
            ])
            ->values();
    }

    /**
     * Approved Penyelaras Ladap attendance, shaped like a training row so it
     * can be merged into the same history table (with a 'type' column to
     * tell them apart).
     */
    private function penyelarasLadapHistoryFor(User $user, ?int $year = null): Collection
    {
        $teacher = Teacher::where('email', $user->email)->first();
        if (! $teacher) {
            return collect();
        }

        return Attendance::where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
            ->where('teacher_id', $teacher->teacherID)
            ->where('attendance_status', 'approved')
            ->with('penyelarasLadap')
            ->get()
            ->filter(fn (Attendance $a) => $a->penyelarasLadap !== null
                && ($year === null || $a->penyelarasLadap->session_date->year === $year))
            ->map(function (Attendance $a) {
                $session = $a->penyelarasLadap;

                return [
                    'application_id'      => 'pl-' . $a->attendance_id,
                    'training_id'         => null,
                    'type'                => 'penyelaras_ladap',
                    'title'               => $session->title,
                    'mode'                => $session->location,
                    'organiserName'       => null,
                    'course_category'     => null,
                    'course_type'         => null,
                    'status'              => 'completed',
                    'present_count'       => 1,
                    'total_sessions'      => 1,
                    'attendance_rate'     => 100.0,
                    'total_session_hours' => round((float) $a->timestamp_status, 2),
                    'ladap_hours'         => round((float) $a->hours_ladap, 2),
                ];
            })
            ->sortByDesc(fn ($row) => $row['application_id'])
            ->values();
    }

    /**
     * Approved certificates, shaped like a training row so they merge into the
     * same history table. LADAP hours = credit hours awarded by Guru Besar.
     */
    private function certificateHistoryFor(User $user, ?int $year = null): Collection
    {
        $teacher = Teacher::where('email', $user->email)->first();
        if (! $teacher) {
            return collect();
        }

        return $this->certificateService->approvedFor($teacher, $year)
            ->orderByDesc('session_date')
            ->get()
            ->map(fn (PenyelarasLadap $c) => [
                'application_id'      => 'cert-' . $c->id,
                'training_id'         => null,
                'type'                => 'certificate',
                'title'               => $c->title,
                'mode'                => $c->location,
                'organiserName'       => null,
                'course_category'     => null,
                'course_type'         => null,
                'status'              => 'completed',
                'present_count'       => 1,
                'total_sessions'      => 1,
                'attendance_rate'     => 100.0,
                'total_session_hours' => $c->durationHours(),
                'ladap_hours'         => (float) $c->certificateLadapHours(),
                'date'                => $c->session_date->format('Y-m-d'),
            ])
            ->values();
    }

    public function show(Request $request, Training $training): Response
    {
        $user = $request->user();

        if ($user->isOutsider()) {
            return $this->showOutsider($request, $training);
        }

        if ($user->isGuruBaru()) {
            return $this->showGuruBaru($request, $training);
        }

        $teacher     = Teacher::where('email', $user->email)->first();
        $application = $teacher
            ? Application::where('teacherID', $teacher->teacherID)
                ->where('course_id', $training->getKey())
                ->where('status', 'approved')
                ->first()
            : null;

        abort_unless($application, 404, 'No approved application found for this training.');

        $row       = $this->buildHistoryRow($application, $user);
        $teacherId = $teacher?->teacherID;

        $sessionAttendance = CourseSession::with('trainers')
            ->where('courseID', $training->getKey())
            ->orderBy('sessionDate')
            ->orderBy('startTime')
            ->get()
            ->map(function ($session) use ($teacherId) {
                $attendance = $teacherId
                    ? Attendance::where('teacher_id', $teacherId)
                        ->where('session_id', $session->session_id)
                        ->first()
                    : null;
                return [
                    'session_id'       => $session->session_id,
                    'session_date'     => $session->session_date->format('Y-m-d'),
                    'start_time'       => $session->start_time,
                    'end_time'         => $session->end_time,
                    'attended'         => $attendance !== null,
                    'timestamp_status' => $attendance?->timestamp_status,
                    'hours_ladap'      => $attendance?->hours_ladap ?? 0,
                    'trainers'         => $session->trainers->map(fn ($t) => [
                        'trainer_id'   => $t->trainer_id,
                        'trainer_name' => $t->trainer_name,
                    ]),
                ];
            });

        return Inertia::render('history/show', [
            'training'           => $training,
            'row'                => $row,
            'session_attendance' => $sessionAttendance,
        ]);
    }

    private function showOutsider(Request $request, Training $training): Response
    {
        $user     = $request->user();
        $outsider = Outsider::where('user_id', $user->id)->first();

        $enrollment = $outsider
            ? EnrollOutsider::where('outsider_id', $outsider->outsider_id)
                ->where('course_id', $training->getKey())
                ->where('status', 'approved')
                ->first()
            : null;

        abort_unless($enrollment, 404, 'No approved enrollment found for this training.');

        $courseKey  = $training->getKey();
        $sessions   = CourseSession::where('courseID', $courseKey)->get();
        $sessionIds = $sessions->pluck('session_id');

        $attended = $outsider
            ? AttendanceOutsider::where('outsider_id', $outsider->outsider_id)
                ->whereIn('session_id', $sessionIds)
                ->get()
                ->keyBy('session_id')
            : collect();

        $totalSessions = $sessions->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
            ? $s->session_date->format('Y-m-d')
            : substr($s->session_date, 0, 10)
        )->count();

        $attendedIds       = $attended->keys(); // keyed by session_id
        $presentCount      = $sessions
            ->filter(fn ($s) => $attendedIds->contains($s->session_id))
            ->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
                ? $s->session_date->format('Y-m-d')
                : substr($s->session_date, 0, 10))
            ->count();
        $totalSessionHours = round((float) $attended->sum('timestamp_status'), 2);

        $row = [
            'application_id'      => $enrollment->courseID,
            'training_id'         => $courseKey,
            'title'               => $training->courseName,
            'mode'          => $training->mode ?? null,
            'organiserName'      => $training->organiserName ?? null,
            'course_category'     => $training->course_category ?? null,
            'status'              => $training->status,
            'present_count'       => $presentCount,
            'total_sessions'      => $totalSessions,
            'attendance_rate'     => $totalSessions > 0
                ? round(($presentCount / $totalSessions) * 100, 1)
                : null,
            'total_session_hours' => $totalSessionHours,
            'submitted_count'     => 0,
            'total_tasks'         => 0,
            'attempted_quizzes'   => 0,
            'total_quizzes'       => 0,
            'average_quiz_score'  => null,
            'ladap_hours'         => 0.0,
        ];

        $sessionAttendance = CourseSession::with('trainers')
            ->where('courseID', $courseKey)
            ->orderBy('sessionDate')
            ->orderBy('startTime')
            ->get()
            ->map(function ($session) use ($attended) {
                $record = $attended->get($session->session_id);
                return [
                    'session_id'       => $session->session_id,
                    'session_date'     => $session->session_date instanceof \Carbon\Carbon
                        ? $session->session_date->format('Y-m-d')
                        : substr($session->session_date, 0, 10),
                    'start_time'       => $session->start_time,
                    'end_time'         => $session->end_time,
                    'attended'         => $record !== null,
                    'timestamp_status' => $record?->timestamp_status,
                    'hours_ladap'      => 0,
                    'trainers'         => $session->trainers->map(fn ($t) => [
                        'trainer_id'   => $t->trainer_id,
                        'trainer_name' => $t->trainer_name,
                    ]),
                ];
            });

        return Inertia::render('history/show', [
            'training'           => $training,
            'row'                => $row,
            'session_attendance' => $sessionAttendance,
            'task_submissions'   => collect(),
            'answer_sheets'      => collect(),
        ]);
    }

    public function export(Request $request): HttpResponse
    {
        $user                = $request->user();
        $year                = (int) $request->input('year', now()->year);
        $rows                = $this->historyFor($user, null, $year);
        $tarbiahRows         = $this->tarbiahHistoryFor($user, $year);
        $penyelarasLadapRows = $this->penyelarasLadapHistoryFor($user, $year);
        $certificateRows     = $this->certificateHistoryFor($user, $year);

        $totalLadapTraining   = (float) $rows->sum('ladap_hours');
        $totalLadapPenyelaras = (float) $penyelarasLadapRows->sum('ladap_hours');
        $totalLadapCert       = (float) $certificateRows->sum('ladap_hours');
        $totalLadapTarbiah    = (float) $tarbiahRows->sum('ladap_hours');

        $pdf = Pdf::loadView('pdf.training-history', [
            'user'                   => $user,
            'year'                   => $year,
            'rows'                   => $rows,
            'tarbiah_rows'           => $tarbiahRows,
            'penyelaras_ladap_rows'  => $penyelarasLadapRows,
            'certificate_rows'       => $certificateRows,
            'total_ladap_training'   => $totalLadapTraining,
            'total_ladap_penyelaras' => $totalLadapPenyelaras,
            'total_ladap_certificate' => $totalLadapCert,
            'total_ladap_tarbiah'    => $totalLadapTarbiah,
            'total_ladap'            => $totalLadapTraining + $totalLadapPenyelaras + $totalLadapCert + $totalLadapTarbiah,
            'exported_at'            => now(),
        ]);

        $filename = sprintf(
            'training-history-%s-%s-%s.pdf',
            preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($user->name)),
            $year,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    public function exportOutsider(Request $request): HttpResponse
    {
        $user = $request->user();
        abort_unless($user->isOutsider(), 403);

        $year = (int) $request->input('year', now()->year);
        $rows = $this->historyForOutsider($user, null, $year);

        $pdf = Pdf::loadView('pdf.training-history-outsider', [
            'user'        => $user,
            'year'        => $year,
            'rows'        => $rows,
            'total_hours' => $rows->sum('total_session_hours'),
            'exported_at' => now(),
        ]);

        $filename = sprintf(
            'training-history-outsider-%s-%s-%s.pdf',
            preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($user->name)),
            $year,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    public function exportGuruBaru(Request $request): HttpResponse
    {
        $user = $request->user();
        abort_unless($user->isGuruBaru(), 403);

        $year = (int) $request->input('year', now()->year);
        $rows = $this->historyForGuruBaru($user, null, $year);

        $pdf = Pdf::loadView('pdf.training-history-guru-baru', [
            'user'        => $user,
            'year'        => $year,
            'rows'        => $rows,
            'total_hours' => $rows->sum('total_session_hours'),
            'total_ladap' => $rows->sum('ladap_hours'),
            'exported_at' => now(),
        ]);

        $filename = sprintf(
            'training-history-guru-baru-%s-%s-%s.pdf',
            preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($user->name)),
            $year,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    public function teacherHistory(Request $request, string $teacherId): Response
    {
        $teacher = Teacher::where('teacherID', $teacherId)->firstOrFail();
        $user    = $this->canonicalTeacherUser($teacher);

        $year                = (int) $request->input('year', now()->year);
        $rows                = $this->historyFor($user, null, $year);
        $tarbiahRows         = $this->tarbiahHistoryFor($user, $year);
        $penyelarasLadapRows = $this->penyelarasLadapHistoryFor($user, $year);
        $certificateRows     = $this->certificateHistoryFor($user, $year);

        $totalLadapTraining   = (float) $rows->sum('ladap_hours');
        $totalLadapPenyelaras = (float) $penyelarasLadapRows->sum('ladap_hours');
        $totalLadapCert       = (float) $certificateRows->sum('ladap_hours');
        $totalLadapTarbiah    = (float) $tarbiahRows->sum('ladap_hours');

        return Inertia::render('history/index', [
            'rows'                      => $rows->concat($penyelarasLadapRows)->concat($certificateRows)->values(),
            'tarbiah_rows'              => $tarbiahRows,
            'filters'                   => [],
            'viewing_self'              => false,
            'viewing_user'              => ['id' => $teacher->teacherID, 'name' => $teacher->teacherName, 'email' => $teacher->email],
            'selected_year'             => $year,
            'available_years'           => $this->availableYearsFor($user),
            'total_ladap'               => round($totalLadapTraining + $totalLadapPenyelaras + $totalLadapCert + $totalLadapTarbiah, 2),
            'total_ladap_training'      => round($totalLadapTraining + $totalLadapPenyelaras + $totalLadapCert, 2),
            'total_ladap_training_only' => round($totalLadapTraining, 2),
            'total_ladap_penyelaras'    => round($totalLadapPenyelaras, 2),
            'total_ladap_certificate'   => round($totalLadapCert, 2),
            'total_ladap_tarbiah'       => round($totalLadapTarbiah, 2),
            'ladap_training_target'     => ApplicationService::LADAP_TARGET_HOURS,
            'ladap_tarbiah_target'      => TarbiahService::LADAP_TARGET_HOURS,
            'is_outsider'               => false,
            'is_guru_baru'              => false,
        ]);
    }

    public function developmentOverview(Request $request): Response
    {
        $schoolId = $this->principals->findByEmail($request->user()->email)?->schoolID;

        $schoolTeachers = Teacher::when($schoolId, fn ($q) => $q->whereHas('assigns', fn ($a) => $a->where('schoolID', $schoolId)->active()))
            ->get();

        $overview = $schoolTeachers->map(function (Teacher $teacher) {
            $user                = $this->canonicalTeacherUser($teacher);
            $trainingRows        = $this->historyFor($user);
            $penyelarasLadapRows = $this->penyelarasLadapHistoryFor($user);
            $tarbiahRows         = $this->tarbiahHistoryFor($user);
            $certificateRows     = $this->certificateHistoryFor($user);
            $rows                = $trainingRows->concat($penyelarasLadapRows);
            $dto                 = $this->teachers->findByEmail($teacher->email);
            $icNumber            = $dto?->ICNumber;

            $totalSessions = $trainingRows->sum('total_sessions');
            $presentCount  = $trainingRows->sum('present_count');

            return [
                'user'                => ['id' => $teacher->teacherID, 'name' => $teacher->teacherName, 'email' => $teacher->email],
                'ic_number'           => $icNumber,
                'completed_trainings' => $rows->where('status', 'completed')->count(),
                'total_trainings'     => $rows->count(),
                'ladap_hours'         => round((float) ($rows->sum('ladap_hours') + $certificateRows->sum('ladap_hours') + $tarbiahRows->sum('ladap_hours')), 2),
                'attendance_rate'     => $totalSessions > 0
                    ? round(($presentCount / $totalSessions) * 100, 1)
                    : null,
            ];
        });

        return Inertia::render('reports/development-overview', [
            'overview'             => $overview,
            'departments'          => collect(),
            'filters'              => $request->only(['department']),
            'ladap_target_per_teacher' => ApplicationService::LADAP_TARGET_HOURS + TarbiahService::LADAP_TARGET_HOURS,
        ]);
    }

    public function developmentOverviewPdf(Request $request): HttpResponse
    {
        $gb       = $request->user();
        $schoolId = $this->principals->findByEmail($gb->email)?->schoolID;

        $schoolTeachers = Teacher::when($schoolId, fn ($q) => $q->whereHas('assigns', fn ($a) => $a->where('schoolID', $schoolId)->active()))
            ->get();

        $overview = $schoolTeachers->map(function (Teacher $teacher) {
            $user                = $this->canonicalTeacherUser($teacher);
            $trainingRows        = $this->historyFor($user);
            $penyelarasLadapRows = $this->penyelarasLadapHistoryFor($user);
            $tarbiahRows         = $this->tarbiahHistoryFor($user);
            $certificateRows     = $this->certificateHistoryFor($user);
            $rows                = $trainingRows->concat($penyelarasLadapRows);
            $dto                 = $this->teachers->findByEmail($teacher->email);
            $totalSessions       = $trainingRows->sum('total_sessions');
            $presentCount        = $trainingRows->sum('present_count');

            return [
                'name'                => $teacher->teacherName,
                'email'               => $teacher->email,
                'ic_number'           => $dto?->ICNumber,
                'completed_trainings' => $rows->where('status', 'completed')->count(),
                'total_trainings'     => $rows->count(),
                'ladap_hours'         => round((float) ($rows->sum('ladap_hours') + $certificateRows->sum('ladap_hours') + $tarbiahRows->sum('ladap_hours')), 1),
                'attendance_rate'     => $totalSessions > 0
                    ? round(($presentCount / $totalSessions) * 100, 1)
                    : null,
            ];
        });

        $pdf = Pdf::loadView('pdf.development-overview', [
            'overview'    => $overview,
            'gb_name'     => $gb->name,
            'exported_at' => now(),
        ]);

        return $pdf->download('development-overview-' . now()->format('Ymd') . '.pdf');
    }

    private function historyForOutsider(User $user, ?string $statusFilter = null, ?int $year = null): Collection
    {
        $outsider = Outsider::where('user_id', $user->id)->first();

        $enrollments = $outsider
            ? EnrollOutsider::with('course')
                ->where('outsider_id', $outsider->outsider_id)
                ->where('status', 'approved')
                ->get()
            : collect();

        $rows = $enrollments
            ->filter(fn (EnrollOutsider $e) => $e->course !== null)
            ->map(function (EnrollOutsider $e) use ($outsider, $year) {
                $course    = $e->course;
                $courseKey = $course->courseID;

                $sessions = CourseSession::where('courseID', $courseKey)
                    ->when($year !== null, fn ($q) => $q->whereYear('sessionDate', $year))
                    ->get();

                if ($year !== null && $sessions->isEmpty()) {
                    return null;
                }

                $sessionIds = $sessions->pluck('session_id');

                $totalSessions = $sessions->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
                    ? $s->session_date->format('Y-m-d')
                    : substr($s->session_date, 0, 10)
                )->count();

                $attended = $outsider
                    ? AttendanceOutsider::where('outsider_id', $outsider->outsider_id)
                        ->whereIn('session_id', $sessionIds)
                        ->get()
                    : collect();

                $attendedIds       = $attended->pluck('session_id');
                $presentCount      = $sessions
                    ->filter(fn ($s) => $attendedIds->contains($s->session_id))
                    ->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
                        ? $s->session_date->format('Y-m-d')
                        : substr($s->session_date, 0, 10))
                    ->count();
                $totalSessionHours = round((float) $attended->sum('timestamp_status'), 2);

                return [
                    'application_id'      => $e->courseID,
                    'training_id'         => $courseKey,
                    'title'               => $course->courseName,
                    'mode'          => $course->mode ?? null,
                    'organiserName'      => $course->organiserName ?? null,
                    'course_category'     => $course->course_category ?? null,
                    'status'              => $course->status,
                    'present_count'       => $presentCount,
                    'total_sessions'      => $totalSessions,
                    'attendance_rate'     => $totalSessions > 0
                        ? round(($presentCount / $totalSessions) * 100, 1)
                        : null,
                    'total_session_hours' => $totalSessionHours,
                    'submitted_count'     => 0,
                    'total_tasks'         => 0,
                    'attempted_quizzes'   => 0,
                    'total_quizzes'       => 0,
                    'average_quiz_score'  => null,
                    'ladap_hours'         => 0.0,
                ];
            })
            ->filter(fn (?array $row) => $row !== null);

        if ($statusFilter === 'completed') {
            $rows = $rows->where('status', 'completed');
        } elseif ($statusFilter === 'in_progress') {
            $rows = $rows->whereIn('status', ['upcoming', 'ongoing']);
        }

        return $rows->values();
    }

    private function historyForGuruBaru(User $user, ?string $statusFilter = null, ?int $year = null): Collection
    {
        $guruNew = GuruNew::where('email', $user->email)->first();

        $buildGuruBaruRow = function ($t, string|int|null $enrollmentId = null) use ($guruNew, $year): ?array {
            $courseKey = $t->getKey();
            $sessions  = CourseSession::where('courseID', $courseKey)
                ->when($year !== null, fn ($q) => $q->whereYear('sessionDate', $year))
                ->get();

            if ($year !== null && $sessions->isEmpty()) {
                return null;
            }

            $sessionIds = $sessions->pluck('session_id');

            $totalSessions = $sessions->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
                ? $s->session_date->format('Y-m-d')
                : substr($s->session_date, 0, 10)
            )->count();

            $attended = $guruNew
                ? AttendanceGuruBaru::where('gn_id', $guruNew->gn_id)
                    ->whereIn('session_id', $sessionIds)
                    ->get()
                : collect();

            $attendedIds       = $attended->pluck('session_id');
            $presentCount      = $sessions
                ->filter(fn ($s) => $attendedIds->contains($s->session_id))
                ->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
                    ? $s->session_date->format('Y-m-d')
                    : substr($s->session_date, 0, 10))
                ->count();
            $totalSessionHours = round((float) $attended->sum('timestamp_status'), 2);
            $ladapHours        = round((float) $attended->sum('hours_ladap'), 2);

            $taskIds = Assessment::where('course_id', $courseKey)
                ->where('type', '!=', 'quiz')
                ->pluck('assessment_id');
            $quizIds = Assessment::where('course_id', $courseKey)
                ->where('type', 'quiz')
                ->pluck('assessment_id');

            $submittedCount   = 0;
            $averageQuizScore = null;

            if ($enrollmentId) {
                $submittedCount = AssessmentSubmission::where('enrollment_id', $enrollmentId)
                    ->whereIn('assessment_id', $taskIds)
                    ->count();

                $quizGrades = AssessmentSubmission::where('enrollment_id', $enrollmentId)
                    ->whereIn('assessment_id', $quizIds)
                    ->whereNotNull('grade')
                    ->pluck('grade');

                if ($quizGrades->count() > 0) {
                    $averageQuizScore = round((float) $quizGrades->avg(), 1);
                }
            }

            return [
                'application_id'      => $courseKey,
                'training_id'         => $courseKey,
                'title'               => $t->courseName,
                'mode'          => $t->mode ?? null,
                'organiserName'      => $t->organiserName ?? null,
                'course_category'     => $t->course_category ?? null,
                'status'              => $t->status,
                'present_count'       => $presentCount,
                'total_sessions'      => $totalSessions,
                'attendance_rate'     => ($totalSessions > 0 && $presentCount > 0)
                    ? round(($presentCount / $totalSessions) * 100, 1)
                    : null,
                'total_session_hours' => $totalSessionHours,
                'submitted_count'     => $submittedCount,
                'total_tasks'         => $taskIds->count(),
                'average_quiz_score'  => $averageQuizScore,
                'ladap_hours'         => $ladapHours,
            ];
        };

        // Series courses use their canonical numeric enrollment row when one
        // has already been materialized by the course-access workflow.
        $seriesRows = Training::lms()->get()
            ->map(function ($t) use ($buildGuruBaruRow, $guruNew) {
                $enrollmentId = $guruNew
                    ? EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                        ->where('course_id', $t->getKey())
                        ->value('enrollment_id')
                    : null;

                return $buildGuruBaruRow($t, $enrollmentId);
            });

        // Single/multi_day: only approved enrollments
        $enrollmentRows = collect();
        if ($guruNew) {
            $enrollments = EnrollGuruBaru::with('course')
                ->where('gn_id', $guruNew->gn_id)
                ->where('status', 'approved')
                ->get();

            $enrollmentRows = $enrollments
                ->filter(fn (EnrollGuruBaru $e) => $e->course !== null)
                ->map(fn (EnrollGuruBaru $e) => $buildGuruBaruRow($e->course, $e->enrollment_id));
        }

        $rows = $seriesRows->concat($enrollmentRows)->filter(fn (?array $row) => $row !== null);

        if ($statusFilter === 'completed') {
            $rows = $rows->where('status', 'completed');
        } elseif ($statusFilter === 'in_progress') {
            $rows = $rows->whereIn('status', ['upcoming', 'ongoing']);
        }

        return $rows->values();
    }

    private function showGuruBaru(Request $request, Training $training): Response
    {
        $user    = $request->user();
        $guruNew = GuruNew::where('email', $user->email)->first();

        // Validate access: series = always accessible; single/multi_day = need approved enrollment
        if (! $training->isLms()) {
            $enrollment = $guruNew
                ? EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                    ->where('course_id', $training->getKey())
                    ->where('status', 'approved')
                    ->first()
                : null;
            abort_unless($enrollment, 404, 'No approved enrollment found for this training.');
        }

        $courseKey  = $training->getKey();
        $sessions   = CourseSession::where('courseID', $courseKey)->get();
        $sessionIds = $sessions->pluck('session_id');

        $totalSessions = $sessions->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
            ? $s->session_date->format('Y-m-d')
            : substr($s->session_date, 0, 10)
        )->count();

        $attended = $guruNew
            ? AttendanceGuruBaru::where('gn_id', $guruNew->gn_id)
                ->whereIn('session_id', $sessionIds)
                ->get()
                ->keyBy('session_id')
            : collect();

        $attendedIds       = $attended->keys();
        $presentCount      = $sessions
            ->filter(fn ($s) => $attendedIds->contains($s->session_id))
            ->groupBy(fn ($s) => $s->session_date instanceof \Carbon\Carbon
                ? $s->session_date->format('Y-m-d')
                : substr($s->session_date, 0, 10))
            ->count();
        $totalSessionHours = round((float) $attended->sum('timestamp_status'), 2);
        $ladapHours        = round((float) $attended->sum('hours_ladap'), 2);

        $enrollment = $guruNew
            ? EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                ->where('course_id', $courseKey)
                ->first()
            : null;
        $enrollmentId = $enrollment?->enrollment_id;

        $taskIds = Assessment::where('course_id', $courseKey)->where('type', '!=', 'quiz')->pluck('assessment_id');
        $quizIds = Assessment::where('course_id', $courseKey)->where('type', 'quiz')->pluck('assessment_id');

        $submittedCount   = 0;
        $averageQuizScore = null;
        if ($enrollmentId) {
            $submittedCount = AssessmentSubmission::where('enrollment_id', $enrollmentId)
                ->whereIn('assessment_id', $taskIds)->count();
            $quizGrades = AssessmentSubmission::where('enrollment_id', $enrollmentId)
                ->whereIn('assessment_id', $quizIds)->whereNotNull('grade')->pluck('grade');
            if ($quizGrades->count() > 0) {
                $averageQuizScore = round((float) $quizGrades->avg(), 1);
            }
        }

        $row = [
            'application_id'      => $courseKey,
            'training_id'         => $courseKey,
            'title'               => $training->courseName,
            'mode'          => $training->mode ?? null,
            'organiserName'      => $training->organiserName ?? null,
            'course_category'     => $training->course_category ?? null,
            'status'              => $training->status,
            'present_count'       => $presentCount,
            'total_sessions'      => $totalSessions,
            'attendance_rate'     => $totalSessions > 0
                ? round(($presentCount / $totalSessions) * 100, 1)
                : null,
            'total_session_hours' => $totalSessionHours,
            'submitted_count'     => $submittedCount,
            'total_tasks'         => $taskIds->count(),
            'average_quiz_score'  => $averageQuizScore,
            'ladap_hours'         => $ladapHours,
        ];

        $sessionAttendance = CourseSession::with('trainers')
            ->where('courseID', $courseKey)
            ->orderBy('sessionDate')
            ->orderBy('startTime')
            ->get()
            ->map(function ($session) use ($attended) {
                $record = $attended->get($session->session_id);
                return [
                    'session_id'       => $session->session_id,
                    'session_date'     => $session->session_date instanceof \Carbon\Carbon
                        ? $session->session_date->format('Y-m-d')
                        : substr($session->session_date, 0, 10),
                    'start_time'       => $session->start_time,
                    'end_time'         => $session->end_time,
                    'attended'         => $record !== null,
                    'timestamp_status' => $record?->timestamp_status,
                    'hours_ladap'      => $record?->hours_ladap ?? 0,
                    'trainers'         => $session->trainers->map(fn ($t) => [
                        'trainer_id'   => $t->trainer_id,
                        'trainer_name' => $t->trainer_name,
                    ]),
                ];
            });

        $allAssessmentIds = Assessment::where('course_id', $courseKey)->pluck('assessment_id');
        $submissionMap    = $enrollmentId
            ? AssessmentSubmission::where('enrollment_id', $enrollmentId)
                ->whereIn('assessment_id', $allAssessmentIds)
                ->get()
                ->keyBy('assessment_id')
            : collect();

        $assessments = Assessment::where('course_id', $courseKey)
            ->orderBy('type')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Assessment $a) => [
                'assessment_id' => $a->assessment_id,
                'type'          => $a->type,
                'title'         => $a->title,
                'deadline'      => $a->available_until?->toISOString(),
                'total_marks'   => $a->total_marks,
                'status'        => $a->status,
                'my_submission' => $submissionMap->has($a->assessment_id)
                    ? $submissionMap->get($a->assessment_id)->only([
                        'submission_id', 'grade', 'status', 'submitted_at',
                    ])
                    : null,
            ]);

        return Inertia::render('history/show', [
            'training'           => $training,
            'row'                => $row,
            'session_attendance' => $sessionAttendance,
            'assessments'        => $assessments,
        ]);
    }

    /**
     * Teachers are SSO-only and may have no `users` table row, so building
     * reports for them (development overview, a single teacher's history)
     * needs a session-less identity carrying just their email — the rest of
     * this controller only ever reads `$user->email` off it.
     */
    private function canonicalTeacherUser(Teacher $teacher): CanonicalSsoUser
    {
        return CanonicalSsoUser::fromDomain('teacher', $teacher->teacherID, $teacher->teacherName, $teacher->email, Role::Teacher->value);
    }

    private function historyFor(User $user, ?string $statusFilter = null, ?int $year = null): Collection
    {
        $teacher = Teacher::where('email', $user->email)->first();

        $applications = $teacher
            ? Application::with('training')
                ->where('teacherID', $teacher->teacherID)
                ->where('status', 'approved')
                ->get()
            : collect();

        $rows = $applications
            ->filter(fn (Application $app) => $app->training !== null)
            ->map(fn (Application $app) => $this->buildHistoryRow($app, $user, $year))
            ->filter(fn (?array $row) => $row !== null);

        if ($statusFilter === 'completed') {
            $rows = $rows->where('status', 'completed');
        } elseif ($statusFilter === 'in_progress') {
            $rows = $rows->whereIn('status', ['upcoming', 'ongoing']);
        }

        return $rows->values();
    }

    /**
     * @return array<string, mixed>|null Null when $year is given and the
     *     training has no sessions in that year (it is excluded from that
     *     year's history).
     */
    private function buildHistoryRow(Application $app, User $user, ?int $year = null): ?array
    {
        $training  = $app->training;
        $courseKey = $training->getKey();

        $teacher   = Teacher::where('email', $user->email)->first();
        $teacherId = $teacher?->teacherID;

        $sessions = CourseSession::where('courseID', $courseKey)
            ->when($year !== null, fn ($q) => $q->whereYear('sessionDate', $year))
            ->get();

        if ($year !== null && $sessions->isEmpty()) {
            return null;
        }

        $sessionIds = $sessions->pluck('session_id');

        // Total hours = sum of hours the teacher actually attended (timestamp_status per session)
        $totalSessionHours = $teacherId
            ? (float) Attendance::where('teacher_id', $teacherId)->whereIn('session_id', $sessionIds)->sum('timestamp_status')
            : 0.0;

        // One session per day required — count distinct days, not sessions
        $totalSessions = $sessions->groupBy(fn ($s) => $s->session_date->format('Y-m-d'))->count();

        $attendedIds  = $teacherId
            ? Attendance::where('teacher_id', $teacherId)->whereIn('session_id', $sessionIds)->pluck('session_id')
            : collect();
        $presentCount = $sessions
            ->filter(fn ($s) => $attendedIds->contains($s->session_id))
            ->groupBy(fn ($s) => $s->session_date->format('Y-m-d'))
            ->count();

        $ladapHours = $teacherId
            ? (float) Attendance::where('teacher_id', $teacherId)->whereIn('session_id', $sessionIds)->sum('hours_ladap')
            : 0.0;

        return [
            'application_id'      => $app->id,
            'training_id'         => $courseKey,
            'type'                => 'training',
            'title'               => $training->courseName,
            'mode'          => $training->mode,
            'organiserName'      => $training->organiserName,
            'course_category'     => $training->course_category,
            'course_type'         => $training->isLms() ? 'lms' : ($training->isSimple() ? 'simple' : null),
            'status'              => $training->status,
            'present_count'       => $presentCount,
            'total_sessions'      => $totalSessions,
            'attendance_rate'     => $totalSessions > 0
                ? round(($presentCount / $totalSessions) * 100, 1)
                : null,
            'total_session_hours' => round($totalSessionHours, 2),
            'ladap_hours'         => $ladapHours,
        ];
    }
}
