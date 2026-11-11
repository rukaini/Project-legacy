<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Models\GuruNew;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\AttendanceGuruBaru;
use App\Models\AttendanceOutsider;
use App\Models\CourseSession;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\Outsider;
use App\Models\Teacher;
use App\Models\Training;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function manage(Training $training): Response
    {
        $sessions = $training->courseSessions()
            ->withCount(['attendances as present_count'])
            ->orderBy('sessionDate')
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->session_id,
                'date'          => $s->session_date->format('Y-m-d'),
                'start_time'    => $s->start_time
                                    ? $s->session_date->format('Y-m-d') . 'T' . $s->start_time
                                    : null,
                'end_time'      => $s->end_time
                                    ? $s->session_date->format('Y-m-d') . 'T' . $s->end_time
                                    : null,
                'present_count' => $s->present_count,
                'training_id'   => $training->getKey(),
            ]);

        return Inertia::render('attendance/manage', [
            'training' => $training,
            'sessions' => $sessions,
        ]);
    }

    public function generate(Request $request, Training $training): RedirectResponse
    {
        return redirect()->route('attendance.manage', $training->getKey())
            ->with('info', 'Attendance is recorded automatically during each session\'s scheduled time window.');
    }

    public function report(Training $training): Response
    {
        $sessions   = $training->courseSessions()->orderBy('sessionDate')->get();
        $sessionIds = $sessions->pluck('session_id');

        $approvedApplicants = $training->applications()
            ->where('status', 'approved')
            ->with('teacher')
            ->get();

        $teacherIds  = $approvedApplicants->pluck('teacherID')->filter()->unique();
        $teacherMap  = Teacher::whereIn('teacherID', $teacherIds)->get()->keyBy('teacherID');
        $attendances = Attendance::whereIn('session_id', $sessionIds)->get();

        $matrix = [];
        foreach ($approvedApplicants as $app) {
            $teacher   = $teacherMap[$app->teacherID] ?? null;
            $row       = ['user_id' => $app->teacherID, 'name' => $teacher?->teacherName ?? '—', 'email' => $teacher?->email ?? '', 'cells' => []];
            $teacherId = $teacher?->teacherID;
            foreach ($sessions as $session) {
                $present = $teacherId !== null
                    && $attendances->where('teacher_id', $teacherId)->where('session_id', $session->session_id)->isNotEmpty();
                $row['cells'][$session->session_id] = $present ? 'present' : null;
            }
            $matrix[] = $row;
        }

        return Inertia::render('attendance/report', [
            'training' => $training,
            'sessions' => $sessions->map(fn ($session) => [
                'id'   => $session->session_id,
                'date' => $session->session_date->format('Y-m-d'),
            ]),
            'matrix'   => $matrix,
        ]);
    }

    public function markForm(Training $training): Response
    {
        $currentUser = request()->user();
        $now         = now();
        $courseKey   = $training->getKey();

        if ($currentUser->isGuruBaru()) {
            $guruNew  = GuruNew::where('email', $currentUser->email)->first();
            $approved = $training->isLms()
                ? true
                : ($guruNew && EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                    ->where('course_id', $courseKey)->where('status', 'approved')->exists());
        } elseif ($currentUser->isOutsider()) {
            $outsider = Outsider::where('user_id', $currentUser->id)->first();
            $approved = $outsider && EnrollOutsider::where('outsider_id', $outsider->outsider_id)
                ->where('course_id', $courseKey)->where('status', 'approved')->exists();
        } else {
            $currentTeacher = Teacher::where('email', $currentUser->email)->first();
            $approved = $currentTeacher && Application::where('teacherID', $currentTeacher->teacherID)
                ->where('course_id', $courseKey)->where('status', 'approved')->exists();
        }

        abort_unless($approved, 403, 'You are not approved for this training.');

        // Build session list with per-user attendance status
        $sessions = CourseSession::where('courseID', $courseKey)
            ->orderBy('sessionDate')
            ->orderBy('startTime')
            ->get()
            ->map(function ($session) use ($currentUser, $now) {
                $date  = $session->session_date->format('Y-m-d');
                $start = \Carbon\Carbon::parse($date . ' ' . $session->start_time);
                $end   = \Carbon\Carbon::parse($date . ' ' . $session->end_time);

                $attended = $this->hasAttended($currentUser, $session->session_id, $session->courseID, $date);

                $status = match(true) {
                    $attended              => 'attended',
                    $now->between($start, $end) => 'active',
                    $now->lt($start)       => 'upcoming',
                    default                => 'missed',
                };

                return [
                    'session_id' => $session->session_id,
                    'date'       => $date,
                    'start_time' => $session->start_time,
                    'end_time'   => $session->end_time,
                    'status'     => $status,
                ];
            });

        return Inertia::render('attendance/mark', [
            'training' => $training,
            'sessions' => $sessions,
        ]);
    }

    public function mark(MarkAttendanceRequest $request, Training $training): RedirectResponse
    {
        $validated = $request->validated();

        $this->service->markAttendance(
            $request->user(),
            $validated['session_id'],
            $validated['remarks'] ?? null,
        );

        return redirect()->route('attendance.mark.form', $training->getKey())
            ->with('success', 'Attendance marked successfully.');
    }

    private function hasAttended(\App\Models\User $user, int|string $sessionId, int|string $courseId, string $date): bool
    {
        $sameDaySessions = CourseSession::where('courseID', $courseId)
            ->whereDate('sessionDate', $date)
            ->pluck('sessionID');

        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            return $teacher && Attendance::where('teacher_id', $teacher->teacherID)
                ->whereIn('session_id', $sameDaySessions)->exists();
        }

        if ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            return $outsider && AttendanceOutsider::where('outsider_id', $outsider->outsider_id)
                ->whereIn('session_id', $sameDaySessions)->exists();
        }

        if ($user->isGuruBaru()) {
            $guruNew = GuruNew::where('email', $user->email)->first();
            return $guruNew && AttendanceGuruBaru::where('gn_id', $guruNew->gn_id)
                ->whereIn('session_id', $sameDaySessions)->exists();
        }

        return false;
    }

}
