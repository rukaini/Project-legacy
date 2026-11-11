<?php

namespace App\Http\Controllers;

use App\Http\Requests\PenyelarasLadap\ApprovePenyelarasAttendanceRequest;
use App\Http\Requests\PenyelarasLadap\StorePenyelarasLadapRequest;
use App\Http\Requests\PenyelarasLadap\UpdatePenyelarasLadapRequest;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Models\Attendance;
use App\Models\PenyelarasLadap;
use App\Models\Teacher;
use App\Services\ApplicationService;
use App\Services\CertificateService;
use App\Services\PenyelarasLadapService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenyelarasLadapController extends Controller
{
    public function __construct(
        private PenyelarasLadapService $service,
        private CertificateService $certificateService,
        private PrincipalRepositoryInterface $principals,
        private TeacherRepositoryInterface $teachers,
        private ApplicationService $applicationService,
    ) {}

    public function index(Request $request): Response
    {
        $user        = $request->user();
        $isGuruBesar = $user->isGuruBesar();
        $me          = $isGuruBesar ? null : $this->teachers->findByEmail($user->email);
        $schoolId    = $isGuruBesar
            ? $this->principals->findByEmail($user->email)?->schoolID
            : $me?->schoolID;
        $now         = Carbon::now();

        $sessions = $schoolId
            ? PenyelarasLadap::sessions()->where('schoolID', $schoolId)
                ->with('assignedTeacher')
                ->orderByDesc('session_date')
                ->get()
            : collect();

        $teachers = $schoolId
            ? Teacher::whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())
                ->orderBy('teacherName')
                ->get()
                ->map(fn (Teacher $t) => [
                    'id'    => $t->teacherID,
                    'label' => $t->teacherID . ' - ' . $t->teacherName,
                ])
                ->values()
            : collect();

        // All attendance rows for the sessions on this page, with teacher names (for Guru Besar's approval list).
        $allAttendances = $sessions->isNotEmpty()
            ? Attendance::where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
                ->whereIn('penyelaras_ladap_id', $sessions->pluck('id'))
                ->with('teacher')
                ->orderBy('created_at')
                ->get()
                ->groupBy('penyelaras_ladap_id')
            : collect();

        // What has the viewing teacher already marked, for the sessions on this page?
        $myTeacher     = $me ? Teacher::where('teacherID', $me->id)->first() : null;
        $myAttendances = $myTeacher
            ? Attendance::where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
                ->where('teacher_id', $myTeacher->teacherID)
                ->whereIn('penyelaras_ladap_id', $sessions->pluck('id'))
                ->get()
                ->keyBy('penyelaras_ladap_id')
            : collect();

        $totalLadapHours = $myTeacher
            ? round($this->applicationService->totalLadapHours($myTeacher) + $this->service->totalHours($myTeacher) + $this->certificateService->totalHours($myTeacher), 2)
            : 0.0;

        return Inertia::render('penyelaras-ladap/index', [
            'sessions' => $sessions->map(function (PenyelarasLadap $s) use ($myTeacher, $myAttendances, $allAttendances, $isGuruBesar) {
                $attendance = $myAttendances->get($s->id);

                return [
                    'id'                 => $s->id,
                    'title'              => $s->title,
                    'description'        => $s->description,
                    'session_date'       => $s->session_date->format('Y-m-d'),
                    'start_time'         => $s->start_time,
                    'end_time'           => $s->end_time,
                    'location'           => $s->location,
                    'assigned_teacher_id'=> $s->assigned_teacher_id,
                    'assigned_teacher'   => $s->assignedTeacher
                        ? $s->assignedTeacher->teacherID . ' - ' . $s->assignedTeacher->teacherName
                        : null,
                    'status'             => $this->service->status($s),
                    'has_attended'       => (bool) $attendance,
                    'session_hours'      => $this->service->sessionHours($s),
                    'earned_hours'       => $attendance ? (float) $attendance->hours_ladap : null,
                    'attendance_status'  => $attendance?->attendance_status,
                    'attendance_remarks' => $attendance?->remarks,
                    'is_assigned_to_me'  => $myTeacher && $s->assigned_teacher_id === $myTeacher->teacherID,
                    'attendees'          => $isGuruBesar
                        ? ($allAttendances->get($s->id) ?? collect())->map(fn (Attendance $a) => [
                            'attendance_id' => $a->attendance_id,
                            'teacher_id'    => $a->teacher_id,
                            'name'          => $a->teacher?->teacherName ?? 'Unknown teacher',
                            'is_assigned'   => $a->teacher_id === $s->assigned_teacher_id,
                            'hours_ladap'   => (float) $a->hours_ladap,
                            'remarks'       => $a->remarks,
                            'status'        => $a->attendance_status,
                        ])->values()
                        : [],
                ];
            }),
            'teachers'           => $teachers,
            'can_manage'         => $isGuruBesar,
            'current_teacher_id' => $me?->id,
            'total_ladap_hours'  => $totalLadapHours,
            'ladap_target_hours' => ApplicationService::LADAP_TARGET_HOURS,
        ]);
    }

    public function store(StorePenyelarasLadapRequest $request): RedirectResponse
    {
        $user     = $request->user();
        $schoolId = $this->principals->findByEmail($user->email)?->schoolID;

        abort_unless($schoolId, 422, 'No school found for your account.');

        $isInSchool = Teacher::where('teacherID', $request->assigned_teacher_id)
            ->whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())
            ->exists();
        abort_unless($isInSchool, 422, 'Selected teacher is not in your school.');

        try {
            $this->service->register($user, $schoolId, $request->validated());
            return redirect()->route('penyelaras-ladap.index')->with('success', 'Penyelaras Ladap session saved.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(UpdatePenyelarasLadapRequest $request, PenyelarasLadap $penyelarasLadap): RedirectResponse
    {
        $user     = $request->user();
        $schoolId = $this->principals->findByEmail($user->email)?->schoolID;

        try {
            abort_unless($schoolId, 422, 'No school found for your account.');

            $isInSchool = Teacher::where('teacherID', $request->assigned_teacher_id)
                ->whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())
                ->exists();
            abort_unless($isInSchool, 422, 'Selected teacher is not in your school.');

            $this->service->update($user, $penyelarasLadap, $schoolId, $request->validated());
            return redirect()->route('penyelaras-ladap.index')->with('success', 'Session updated.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function mark(Request $request, PenyelarasLadap $penyelarasLadap): RedirectResponse
    {
        try {
            $this->service->markAttendance($request->user(), $penyelarasLadap);
            return redirect()->route('penyelaras-ladap.index')->with('success', 'Attendance marked — waiting for Guru Besar approval.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(ApprovePenyelarasAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $schoolId = $this->principals->findByEmail($request->user()->email)?->schoolID;

        abort_unless($schoolId, 422, 'No school found for your account.');

        try {
            $this->service->approveAttendance($request->user(), $attendance, $schoolId, $request->validated());
            return redirect()->route('penyelaras-ladap.index')->with('success', 'Attendance approved.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, PenyelarasLadap $penyelarasLadap): RedirectResponse
    {
        $schoolId = $this->principals->findByEmail($request->user()->email)?->schoolID;

        abort_unless($schoolId, 422, 'No school found for your account.');

        try {
            $this->service->delete($penyelarasLadap, $schoolId);
            return redirect()->route('penyelaras-ladap.index')->with('success', 'Session deleted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
