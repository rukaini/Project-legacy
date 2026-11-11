<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarbiah\StoreTarbiahRequest;
use App\Http\Requests\Tarbiah\UpdateTarbiahRequest;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Models\Tarbiah;
use App\Models\TarbiahAttendance;
use App\Models\Teacher;
use App\Services\TarbiahService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TarbiahController extends Controller
{
    public function __construct(
        private TarbiahService $service,
        private PrincipalRepositoryInterface $principals,
        private TeacherRepositoryInterface $teachers,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $schoolId = $user->isGuruBesar()
            ? $this->principals->findByEmail($user->email)?->schoolID
            : $this->teachers->findByEmail($user->email)?->schoolID;

        $tarbiahs = $schoolId
            ? Tarbiah::where('schoolID', $schoolId)
                ->withCount('attendances')
                ->when($user->isGuruBesar(), fn ($q) => $q->with('attendances.teacher'))
                ->orderByDesc('session_date')
                ->get()
            : collect();

        $now = Carbon::now();

        $myAttendedIds = [];
        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                $myAttendedIds = TarbiahAttendance::where('teacher_id', $teacher->teacherID)
                    ->whereIn('tarbiah_id', $tarbiahs->pluck('tarbiah_id'))
                    ->pluck('tarbiah_id')
                    ->toArray();
            }
        }

        $totalLadapHours = ($user->isTeacher() && $teacher)
            ? $this->service->totalLadapHours($teacher)
            : 0.0;

        return Inertia::render('tarbiah/index', [
            'tarbiahs' => $tarbiahs->map(fn (Tarbiah $t) => [
                'tarbiah_id'      => $t->tarbiah_id,
                'title'           => $t->title,
                'description'     => $t->description,
                'session_date'    => $t->session_date->format('Y-m-d'),
                'end_date'        => ($t->end_date ?? $t->session_date)->format('Y-m-d'),
                'start_time'      => $t->start_time,
                'end_time'        => $t->end_time,
                'location'        => $t->location,
                'credit_hours'    => (float) $t->credit_hours,
                'ladap_hours'     => $this->service->sessionHours($t),
                'attendance_count'=> $t->attendances_count,
                'attendees'       => $user->isGuruBesar()
                    ? $t->attendances->map(fn ($a) => [
                        'teacher_id' => $a->teacher_id,
                        'name'       => $a->teacher?->teacherName ?? 'Unknown teacher',
                    ])->values()
                    : [],
                'has_attended'    => in_array($t->tarbiah_id, $myAttendedIds),
                'is_active'       => $now->between(
                    Carbon::parse($t->session_date->format('Y-m-d') . ' ' . $t->start_time),
                    Carbon::parse(($t->end_date ?? $t->session_date)->format('Y-m-d') . ' ' . $t->end_time)
                ),
                'status'          => $this->service->status($t, $now),
            ]),
            'total_ladap_hours'  => $totalLadapHours,
            'ladap_target_hours' => TarbiahService::LADAP_TARGET_HOURS,
        ]);
    }

    public function store(StoreTarbiahRequest $request): RedirectResponse
    {
        $user     = $request->user();
        $schoolId = $this->principals->findByEmail($user->email)?->schoolID;

        abort_unless($schoolId, 422, 'No school found for your account.');

        $this->service->register($user, $schoolId, $request->validated());

        return redirect()->route('tarbiah.index')
            ->with('success', 'Tarbiah registered successfully.');
    }

    public function update(UpdateTarbiahRequest $request, Tarbiah $tarbiah): RedirectResponse
    {
        $user     = $request->user();
        $schoolId = $this->principals->findByEmail($user->email)?->schoolID;

        try {
            abort_unless($schoolId, 422, 'No school found for your account.');
            $this->service->update($user, $tarbiah, $schoolId, $request->validated());
            return redirect()->route('tarbiah.index')->with('success', 'Tarbiah updated.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, Tarbiah $tarbiah): RedirectResponse
    {
        $user     = $request->user();
        $schoolId = $this->principals->findByEmail($user->email)?->schoolID;

        abort_unless($schoolId, 422, 'No school found for your account.');

        $this->service->delete($user, $tarbiah, $schoolId);

        return redirect()->route('tarbiah.index')
            ->with('success', 'Tarbiah deleted.');
    }

    public function mark(Request $request, Tarbiah $tarbiah): RedirectResponse
    {
        $this->service->markAttendance($request->user(), $tarbiah);

        return redirect()->route('tarbiah.index')
            ->with('success', 'Attendance marked successfully.');
    }
}
