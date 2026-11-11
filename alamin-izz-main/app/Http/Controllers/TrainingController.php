<?php

namespace App\Http\Controllers;

use App\Events\TrainingCancelled;
use App\Events\TrainingCreated;
use App\Events\TrainingUpdated;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Http\Requests\Training\UpdateTrainingRequest;
use App\Models\GuruNew;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\AttendanceGuruBaru;
use App\Models\AttendanceOutsider;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use App\Models\LearningMaterial;
use App\Models\CourseSession;
use App\Models\TrainingModule;
use App\Models\Outsider;
use App\Models\Teacher;
use App\Models\Trainer;
use App\Models\Training;
use App\Services\ApplicationService;
use App\Services\CertificateService;
use App\Services\PenyelarasLadapService;
use App\Services\TrainingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function __construct(
        private TrainingService $service,
        private ApplicationService $applicationService,
        private PenyelarasLadapService $penyelarasLadap,
        private CertificateService $certificateService,
    ) {}

    public function index(Request $request): Response
    {
        $this->service->syncStatuses();

        $user  = $request->user();
        $query = Training::withCount('applications');

        if ($user->isTeacher()) {
            $query->forAudience('teacher');
        } elseif ($user->isGuruBaru()) {
            $query->forAudience('new_teacher');
        } elseif ($user->isOutsider()) {
            $query->forAudience('public');
        }

        if ($request->filled('search')) {
            $query->where('courseName', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $trainings = $query->orderBy('courseName')->orderBy('courseID')->get();

        $userApplicationMap  = [];
        $totalLadapHours     = 0.0;
        $estimatedLadapHours = 0.0;

        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                $userApplicationMap = Application::where('teacherID', $teacher->teacherID)
                    ->pluck('status', 'course_id')
                    ->toArray();

                $totalLadapHours = $this->applicationService->totalLadapHours($teacher)
                    + $this->penyelarasLadap->totalHours($teacher)
                    + $this->certificateService->totalHours($teacher);

                $isTrainer = Trainer::where('trainerIC', $teacher->ICNumber)->exists();

                $pendingCourseIds = Application::where('teacherID', $teacher->teacherID)
                    ->where('status', 'pending')
                    ->pluck('course_id');

                foreach ($pendingCourseIds as $courseId) {
                    $course     = Training::find($courseId);
                    $isAlAmin   = $course && $course->isInHouse();
                    $multiplier = ($isTrainer && $isAlAmin) ? 1.5 : ($isAlAmin ? 1.0 : 0.5);

                    $sessions = CourseSession::where('courseID', $courseId)
                        ->whereNotNull('startTime')
                        ->whereNotNull('endTime')
                        ->get()
                        ->groupBy(fn ($s) => Carbon::parse($s->session_date)->format('Y-m-d'))
                        ->map(fn ($daySessions) => $daySessions->first());

                    foreach ($sessions as $session) {
                        $baseHours            = Carbon::parse($session->start_time)
                            ->diffInMinutes(Carbon::parse($session->end_time)) / 60;
                        $estimatedLadapHours += $baseHours * $multiplier;
                    }
                }

                $estimatedLadapHours = round($estimatedLadapHours, 2);
            }
        } elseif ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            if ($outsider) {
                $userApplicationMap = EnrollOutsider::where('outsider_id', $outsider->outsider_id)
                    ->pluck('status', 'course_id')
                    ->toArray();
            }
        } elseif ($user->isGuruBaru()) {
            // Series courses: auto-enrolled
            $seriesCourseIds = Training::lms()->pluck('courseID');
            foreach ($seriesCourseIds as $id) {
                $userApplicationMap[$id] = 'enrolled';
            }
            // Single/multi_day: check EnrollGuruBaru
            $guruNew = GuruNew::where('email', $user->email)->first();
            if ($guruNew) {
                $enrollments = EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                    ->pluck('status', 'course_id');
                foreach ($enrollments as $courseId => $status) {
                    $userApplicationMap[$courseId] = $status;
                }
            }
        }

        return Inertia::render('trainings/index', [
            'trainings'            => $trainings,
            'user_applications'    => $userApplicationMap,
            'filters'              => $request->only(['search', 'status']),
            'total_ladap_hours'     => $totalLadapHours,
            'estimated_ladap_hours' => $estimatedLadapHours,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('trainings/create');
    }

    public function store(StoreTrainingRequest $request): RedirectResponse
    {
        $training = Training::create($request->safe()->except('course_pic'));

        event(new TrainingCreated($training));

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Training created successfully.');
    }

    public function show(Training $training): Response
    {
        $user = request()->user();

        $training->loadCount('applications');
        $training->load(['courseSessions' => function ($q) {
            $q->orderBy('sessionDate')->with('trainers');
        }]);

        $hasApplied  = false;
        $canApply    = false;
        $application = null;
        $ladapCapReached = false;

        $totalApproved = $training->applications()->where('status', 'approved')->count()
            + $training->outsiderEnrollments()->where('status', 'approved')->count()
            + $training->guruBaruEnrollments()->where('status', 'approved')->count();
        $isFull = $totalApproved >= $training->capacity;

        if ($user->isTeacher()) {
            $teacher     = Teacher::where('email', $user->email)->first();
            $application = $teacher
                ? Application::where('teacherID', $teacher->teacherID)
                    ->where('course_id', $training->getKey())
                    ->first()
                : null;

            $firstSessionDate = $training->courseSessions->min('session_date');
            $beforeDeadline   = $firstSessionDate !== null
                && now()->startOfDay()->lt(Carbon::parse($firstSessionDate)->startOfDay());

            $hasApplied        = (bool) $application;
            $totalApprovedTeacher = $training->applications()->where('status', 'approved')->count()
                + $training->outsiderEnrollments()->where('status', 'approved')->count()
                + $training->guruBaruEnrollments()->where('status', 'approved')->count();

            $ladapCapReached = false;
            if ($teacher) {
                $ladapCapReached = ($this->applicationService->totalLadapHours($teacher) + $this->penyelarasLadap->totalHours($teacher) + $this->certificateService->totalHours($teacher))
                    >= ApplicationService::LADAP_TARGET_HOURS;
            }

            $canApply   = ! $hasApplied
                && $training->status === 'upcoming'
                && $beforeDeadline
                && $totalApprovedTeacher < $training->capacity
                && ! $ladapCapReached;
        } elseif ($user->isOutsider()) {
            $outsider    = Outsider::where('user_id', $user->id)->first();
            $application = $outsider
                ? EnrollOutsider::where('outsider_id', $outsider->outsider_id)
                    ->where('course_id', $training->getKey())
                    ->first()
                : null;

            $firstSessionDate = $training->courseSessions->min('session_date');
            $beforeDeadline   = $firstSessionDate !== null
                && now()->startOfDay()->lt(Carbon::parse($firstSessionDate)->startOfDay());

            $approvedCount = $training->applications()->where('status', 'approved')->count()
                + $training->outsiderEnrollments()->where('status', 'approved')->count()
                + $training->guruBaruEnrollments()->where('status', 'approved')->count();

            $hasApplied = (bool) $application;
            $canApply   = ! $hasApplied
                && $training->isSimple()
                && $training->status === 'upcoming'
                && $beforeDeadline
                && $approvedCount < $training->capacity;
        } elseif ($user->isGuruBaru()) {
            $guruNew = GuruNew::where('email', $user->email)->first();

            if ($training->isLms()) {
                // Auto-enrolled — no application record needed
                $application = ['status' => 'approved', 'rejection_reason' => null];
                $hasApplied  = true;
                $canApply    = false;
            } else {
                $enrollment = $guruNew
                    ? EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                        ->where('course_id', $training->getKey())
                        ->first()
                    : null;

                $firstSessionDate = $training->courseSessions->min('session_date');
                $beforeDeadline   = $firstSessionDate !== null
                    && now()->startOfDay()->lt(Carbon::parse($firstSessionDate)->startOfDay());

                $totalApprovedGb = $training->applications()->where('status', 'approved')->count()
                    + $training->outsiderEnrollments()->where('status', 'approved')->count()
                    + $training->guruBaruEnrollments()->where('status', 'approved')->count();
                $hasApplied = (bool) $enrollment;
                $canApply   = ! $hasApplied
                    && $training->status === 'upcoming'
                    && $beforeDeadline
                    && $totalApprovedGb < $training->capacity;
                $application = $enrollment;
            }
        }

        $isEnrolled = is_array($application)
            ? ($application['status'] ?? null) === 'approved'
            : $application?->status === 'approved';

        // A read-only course view may inspect an existing enrollment, but it
        // must not materialize one merely because the page was opened.
        $autoEnrollment = null;
        if ($user->isGuruBaru()
            && $training->isLms()
        ) {
            $accessGuruNew = GuruNew::where('email', $user->email)->first();
            if ($accessGuruNew) {
                $autoEnrollment = EnrollGuruBaru::where('gn_id', $accessGuruNew->gn_id)
                    ->where('course_id', $training->getKey())
                    ->first();
            }
        }

        $attendedSessionIds = [];
        if ($isEnrolled && $user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                $sessionIds = $training->courseSessions->pluck('session_id');
                $attendedSessionIds = Attendance::where('teacher_id', $teacher->teacherID)
                    ->whereIn('session_id', $sessionIds)
                    ->pluck('session_id')
                    ->toArray();
            }
        } elseif ($isEnrolled && $user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            if ($outsider) {
                $sessionIds = $training->courseSessions->pluck('session_id');
                $attendedSessionIds = AttendanceOutsider::where('outsider_id', $outsider->outsider_id)
                    ->whereIn('session_id', $sessionIds)
                    ->pluck('session_id')
                    ->toArray();
            }
        } elseif ($isEnrolled && $user->isGuruBaru()) {
            $guruNewForAttend = GuruNew::where('email', $user->email)->first();
            if ($guruNewForAttend) {
                $sessionIds = $training->courseSessions->pluck('session_id');
                $attendedSessionIds = AttendanceGuruBaru::where('gn_id', $guruNewForAttend->gn_id)
                    ->whereIn('session_id', $sessionIds)
                    ->pluck('session_id')
                    ->toArray();
            }
        }

        $courseModules    = null;
        $learningMaterials = null;

        if ($training->isLms()) {
            $courseModules = TrainingModule::where('course_id', $training->getKey())
                ->orderBy('module_order')
                ->with([
                    'contentBlocks' => fn ($q) => $q
                        ->where('is_visible', true)
                        ->orderBy('block_order')
                        ->with('material.trainer', 'material.session'),
                ])
                ->get()
                ->map(fn (TrainingModule $m) => [
                    'module_id'    => $m->module_id,
                    'module_title' => $m->module_title,
                    'module_order' => $m->module_order,
                    'blocks'       => $m->contentBlocks->map(fn ($b) => [
                        'block_id'      => $b->block_id,
                        'block_type'    => $b->block_type,
                        'block_title'   => $b->block_title,
                        'block_content' => $b->block_content,
                        'external_url'  => $b->external_url,
                        'assessment_id' => $b->assessment_id,
                        'material_id'   => $b->material_id,
                        'material'      => $b->material ? [
                            'material_id'         => $b->material->material_id,
                            'original_file_name'  => $b->material->original_file_name,
                            'file_path'           => $b->material->file_path,
                            'material_type'       => $b->material->material_type,
                            'trainer'             => $b->material->trainer?->trainer_name,
                            'session_date'        => $b->material->session?->session_date,
                            'session_start_time'  => $b->material->session?->start_time,
                            'session_end_time'    => $b->material->session?->end_time,
                        ] : null,
                        'block_order'   => $b->block_order,
                    ])->values(),
                ])
                ->toArray();
        } elseif ($training->isSimple()) {
            $learningMaterials = LearningMaterial::where('course_id', $training->getKey())
                ->with(['trainer', 'session'])
                ->get()
                ->map(fn (LearningMaterial $mat) => [
                    'material_id'        => $mat->material_id,
                    'original_file_name' => $mat->original_file_name,
                    'file_path'          => $mat->file_path,
                    'material_type'      => $mat->material_type,
                    'trainer'            => $mat->trainer?->trainer_name,
                    'session_date'       => $mat->session?->session_date,
                    'session_start_time' => $mat->session?->start_time,
                    'session_end_time'   => $mat->session?->end_time,
                    'uploaded_at'        => $mat->uploaded_at?->toISOString(),
                ]);
        }

        $assessments = null;
        if (($user->isGuruBaru() || $user->isTeacher()) && $training->isLms() && $isEnrolled) {
            if ($user->isTeacher()) {
                $enrollmentId = Teacher::where('email', $user->email)->value('teacherID');
            } else {
                $guruNew      = GuruNew::where('email', $user->email)->first();
                $enrollment   = $autoEnrollment ?? ($guruNew
                    ? EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                        ->where('course_id', $training->getKey())
                        ->first()
                    : null);
                $enrollmentId = $enrollment?->enrollment_id;
            }

            $assessmentIds = Assessment::where('course_id', $training->getKey())
                ->pluck('assessment_id');

            $submissionMap = AssessmentSubmission::where('enrollment_id', $enrollmentId)
                ->whereIn('assessment_id', $assessmentIds)
                ->get()
                ->keyBy('assessment_id');

            $assessments = Assessment::where('course_id', $training->getKey())
                ->orderBy('type')
                ->orderBy('created_at')
                ->get()
                ->map(fn (Assessment $a) => [
                    'assessment_id'  => $a->assessment_id,
                    'module_id'      => $a->module_id,
                    'type'           => $a->type,
                    'title'          => $a->title,
                    'instruction'    => $a->instruction_text,
                    'deadline'       => $a->available_until?->toISOString(),
                    'total_marks'    => $a->total_marks,
                    'accepted_format'=> $a->accepted_format,
                    'status'         => $a->status,
                    'my_submission'  => $submissionMap->has($a->assessment_id)
                        ? $submissionMap->get($a->assessment_id)->only([
                            'submission_id', 'grade', 'status', 'submitted_at',
                        ])
                        : null,
                ]);
        }

        // Feedback form for this course
        $feedbackFormData = null;
        $feedbackForm = FeedbackForm::where('courseID', $training->getKey())
            ->with(['questions' => fn ($q) => $q->with('category')->orderBy('questionOrder')])
            ->first();
        $hasSubmittedFeedback = false;
        if ($feedbackForm) {
            $feedbackFormData = [
                'formID' => $feedbackForm->formID,
                'title'  => $feedbackForm->title,
                'questions' => $feedbackForm->questions->map(fn ($q) => [
                    'questionID'    => $q->questionID,
                    'category'      => $q->category?->categoryName,
                    'questionText'  => $q->questionText,
                    'questionType'  => $q->questionType,
                    'questionImage' => $q->questionImage,
                    'options_json'  => $q->options_json,
                    'isRequired'    => $q->isRequired,
                    'questionOrder' => $q->questionOrder,
                ])->values(),
            ];
            $participant = \App\Models\CourseParticipant::findFor($user, $training->getKey());
            $hasSubmittedFeedback = (bool) $participant?->isFeedbackCompleted;
        }

        // Resolve enrollment_id for guru_baru (needed by frontend interaction logging)
        $enrollmentId = null;
        if ($user->isGuruBaru()) {
            $gbGuruNew    = GuruNew::where('email', $user->email)->first();
            $gbEnrollment = $autoEnrollment ?? ($gbGuruNew
                ? EnrollGuruBaru::where('gn_id', $gbGuruNew->gn_id)
                    ->where('course_id', $training->getKey())
                    ->first()
                : null);
            $enrollmentId = $gbEnrollment?->enrollment_id;
        }

        return Inertia::render('trainings/show', [
            'training'             => $training,
            'has_applied'          => $hasApplied,
            'can_apply'            => $canApply,
            'is_full'              => $isFull,
            'ladap_cap_reached'    => $ladapCapReached,
            'application'          => $application,
            'attended_session_ids' => $attendedSessionIds,
            'course_modules'       => $courseModules,
            'assessments'          => $assessments,
            'learning_materials'   => $learningMaterials,
            'enrollment_id'        => $enrollmentId,
            'feedback_form'        => $feedbackFormData,
            'has_submitted_feedback' => $hasSubmittedFeedback,
        ]);
    }

    public function edit(Training $training): Response
    {
        return Inertia::render('trainings/edit', [
            'training' => $training,
        ]);
    }

    public function update(UpdateTrainingRequest $request, Training $training): RedirectResponse
    {
        $training->update($request->safe()->except('course_pic'));

        if ($training->wasChanged(['mode', 'organiserName'])) {
            event(new TrainingUpdated($training));
        }

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Training updated successfully.');
    }

    public function cancel(Training $training): RedirectResponse
    {
        $training->update(['status' => 'ongoing']);

        event(new TrainingCancelled($training));

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Training cancelled.');
    }

    public function complete(Request $request, Training $training): RedirectResponse
    {
        $this->service->markCompleted($training, $request->user());

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Training marked as completed.');
    }
}
