<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Application\AssignTeachersRequest;
use App\Http\Requests\Application\RejectApplicationRequest;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Models\Application;
use App\Models\EnrollOutsider;
use App\Models\Outsider;
use App\Models\ParticipantMaterialAccess;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $service,
        private PrincipalRepositoryInterface $principals,
    ) {}

    // Returns teacherID values for teachers in the GB's school
    private function schoolTeacherIds(User $gb): Collection
    {
        $dto      = $this->principals->findByEmail($gb->email);
        $schoolId = $dto?->schoolID;

        return Teacher::when($schoolId, fn ($q) => $q->whereHas('assigns', fn ($a) => $a->where('schoolID', $schoolId)->active()))
            ->pluck('teacherID');
    }

    // Returns User IDs for teachers in the GB's school (for availableTeachers listing)
    private function schoolTeacherUserIds(User $gb): Collection
    {
        $dto      = $this->principals->findByEmail($gb->email);
        $schoolId = $dto?->schoolID;

        $emails = Teacher::when($schoolId, fn ($q) => $q->whereHas('assigns', fn ($a) => $a->where('schoolID', $schoolId)->active()))->pluck('email');

        return User::role(Role::Teacher->value)->whereIn('email', $emails)->pluck('id');
    }

    public function index(Request $request): Response
    {
        $statusFilter     = $request->input('status');
        $schoolTeacherIds = $this->schoolTeacherIds($request->user());

        // ── Teacher applications ───────────────────────────────────────────────
        $teacherQuery = Application::with(['teacher', 'training'])
            ->whereIn('teacherID', $schoolTeacherIds);
        if ($statusFilter) $teacherQuery->where('status', $statusFilter);

        $emails       = $teacherQuery->get()->pluck('teacher.email')->filter()->unique();
        $usersByEmail = User::whereIn('email', $emails)->get()->keyBy('email');

        $teacherApps = $teacherQuery->get()->map(function (Application $app) use ($usersByEmail) {
            $user = $usersByEmail->get($app->teacher?->email ?? '');
            return [
                'id'               => 'teacher_' . $app->getRouteKey(),
                'type'             => 'teacher',
                'route_key'        => $app->getRouteKey(),
                'user_name'        => $user?->name ?? $app->teacher?->name ?? '—',
                'user_email'       => $app->teacher?->email ?? '—',
                'training_id'      => $app->training?->getKey(),
                'training_title'   => $app->training?->courseName ?? '—',
                'status'           => $app->status,
                'applied_at'       => $app->applied_at,
                'rejection_reason' => $app->rejection_reason,
            ];
        });

        $all = $teacherApps->sortByDesc('applied_at')->values();

        return Inertia::render('applications/index', [
            'applications' => $all,
            'filters'      => $request->only(['status']),
        ]);
    }

    public function store(StoreApplicationRequest $request, Training $training): RedirectResponse
    {
        $this->service->apply($request->user(), $training);

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Your application has been submitted.');
    }

    public function approve(Application $application): RedirectResponse
    {
        $gb = request()->user();
        abort_unless(
            $this->schoolTeacherIds($gb)->contains($application->teacherID),
            403, 'You can only approve applications from teachers in your school.'
        );

        try {
            $this->service->approve($application, $gb);
            return back()->with('success', 'Application approved.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(RejectApplicationRequest $request, Application $application): RedirectResponse
    {
        $gb = $request->user();
        abort_unless(
            $this->schoolTeacherIds($gb)->contains($application->teacherID),
            403, 'You can only reject applications from teachers in your school.'
        );

        try {
            $this->service->reject($application, $gb, $request->input('reason'));
            return back()->with('success', 'Application rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function assign(AssignTeachersRequest $request, Training $training): RedirectResponse
    {
        $this->service->assignDirect($training, $request->validated()['user_ids'], $request->user());

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Teachers assigned to training.');
    }

    public function myApplications(Request $request): Response
    {
        $user = $request->user();

        if ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            $applications = $outsider
                ? EnrollOutsider::with('course')
                    ->where('outsider_id', $outsider->outsider_id)
                    ->latest('applied_at')
                    ->get()
                    ->map(fn (EnrollOutsider $e) => [
                        'id'               => $e->courseID,
                        'status'           => $e->status,
                        'applied_at'       => $e->applied_at,
                        'rejection_reason' => $e->rejection_reason,
                        'training'         => $e->course ? [
                            'id'          => $e->course->courseID,
                            'courseName' => $e->course->courseName,
                            'title'       => $e->course->courseName,
                            'status'      => $e->course->status,
                        ] : null,
                    ])
                : collect();
        } else {
            $teacher = Teacher::where('email', $user->email)->first();
            $applications = $teacher
                ? Application::with('training')
                    ->where('teacherID', $teacher->teacherID)
                    ->latest('applied_at')
                    ->get()
                    ->each(fn (Application $app) => $app->setAttribute('id', $app->getRouteKey()))
                : collect();
        }

        // Whether each training's View Training button is enabled (participant_material_access).
        $access       = ParticipantMaterialAccess::forCourses($applications->map(fn ($a) => data_get($a, 'training.id') ?? data_get($a, 'training.courseID')));
        $applications = $applications->map(function ($a) use ($access) {
            $courseId = data_get($a, 'training.id') ?? data_get($a, 'training.courseID');
            $state    = $access->get($courseId, ['can_view' => true, 'access_until' => null]);

            if ($a instanceof Application) {
                $a->setAttribute('can_view', $state['can_view']);
                $a->setAttribute('access_until', $state['access_until']);
                return $a;
            }

            return $a + $state;
        })->values();

        return Inertia::render('applications/my-applications', [
            'applications' => $applications,
        ]);
    }

    public function assignForm(Request $request, Training $training): Response
    {
        $appliedTeacherIds = Application::where('enroll_teacher.course_id', $training->getKey())
            ->pluck('teacherID');
        $appliedEmails     = Teacher::whereIn('teacherID', $appliedTeacherIds)->pluck('email');
        $appliedUserIds    = User::whereIn('email', $appliedEmails)->pluck('id');

        $schoolTeacherIds  = $this->schoolTeacherUserIds($request->user());

        $availableTeachers = User::role(Role::Teacher->value)
            ->whereIn('id', $schoolTeacherIds)
            ->whereNotIn('id', $appliedUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('trainings/assign', [
            'training'           => $training,
            'available_teachers' => $availableTeachers,
        ]);
    }
}
