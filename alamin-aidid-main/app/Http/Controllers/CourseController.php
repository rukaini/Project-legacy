<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trainer\SaveAssessmentRequest;
use App\Services\Lms\TrainerLmsMaterialAuthoringService;
use App\Support\CanonicalAttendance;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function __construct(
        private readonly TrainerLmsMaterialAuthoringService $materialAuthoring
    ) {}

    public function index()
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $assignedCourseIds = SharedTrainingSchema::assignedCourseIds($trainerId);

        // Read course summaries from canonical base tables and filter to courses
        // that have at least one session assigned to the current trainer.
        $courses = CanonicalTrainingQuery::courses()
            ->whereIn(SharedTrainingSchema::courseInternalExpression(), $assignedCourseIds->all() ?: [''])
            ->orderByRaw(SharedTrainingSchema::courseNameExpression())
            ->get()
            ->map(function (object $course): object {
                $course->total_sessions = SharedTrainingSchema::constrainSessionsToCourse(
                    DB::table('course_session as course_session'),
                    $course
                )->count();
                $course->total_participants = SharedTrainingSchema::constrainEnrollmentToCourse(
                    CanonicalEnrollment::query('enrollment'),
                    $course
                )->count();
                $course->male_count = 0;
                $course->female_count = 0;

                return $course;
            });

        return view('trainer.training.my-training', compact('courses'));
    }

    public function show(string $courseId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();

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
        $trainerId = SharedTrainingSchema::currentTrainerId();

        // Simple (non-LMS) trainings use the lighter Simple workspace
        if (strtolower($course->training_mode ?? 'standard') !== 'lms') {
            return redirect()->route('trainer.simple.overview', $courseId);
        }

        $trainingTeam = DB::table('course_session as course_session');
        SharedTrainingSchema::joinSessionAssignments($trainingTeam);
        SharedTrainingSchema::joinAssignmentTrainers($trainingTeam);
        SharedTrainingSchema::constrainSessionsToCourse($trainingTeam, $course);
        $trainingTeam = $trainingTeam
            ->selectRaw(SharedTrainingSchema::trainerInternalExpression().' AS trainer_id')
            ->selectRaw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
            ->distinct()
            ->orderByRaw(SharedTrainingSchema::trainerNameExpression())
            ->get()
            ->map(function (object $trainer) use ($trainerId): object {
                $trainer->is_current = (string) $trainer->trainer_id === $trainerId;

                return $trainer;
            });

        /*
        |--------------------------------------------------------------------------
        | Sessions assigned to this trainer
        |--------------------------------------------------------------------------
        */

        $sessions = CanonicalTrainingQuery::sessions('course_session');
        SharedTrainingSchema::joinSessionAssignments($sessions);
        SharedTrainingSchema::constrainSessionsToCourse($sessions, $course);
        SharedTrainingSchema::constrainAssignmentToTrainer($sessions, $trainerId);
        $sessions = $sessions
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->get()
            ->each(function (object $session) use ($courseId): void {
                $session->course_id = $courseId;
            });

        $sessionCount = $sessions->count();

        $nextSession = $sessions
            ->filter(function ($session) {
                return $session->date && Carbon::parse($session->date)->gte(now()->startOfDay());
            })
            ->sortBy([
                ['date', 'asc'],
                ['start_time', 'asc'],
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Participants
        |--------------------------------------------------------------------------
        */

        $participantQuery = CanonicalEnrollment::query('enrollment');
        SharedTrainingSchema::joinEnrollmentIdentities($participantQuery);
        SharedTrainingSchema::constrainEnrollmentToCourse($participantQuery, $course);
        $participants = $participantQuery
            ->select('enrollment.*')
            ->selectRaw(SharedTrainingSchema::enrollmentParticipantNameExpression().' AS participant_name')
            ->selectRaw(SharedTrainingSchema::enrollmentParticipantEmailExpression().' AS participant_email')
            ->selectRaw(SharedTrainingSchema::enrollmentParticipantGenderExpression().' AS participant_gender')
            ->orderBy('enrollment.participant_type')
            ->orderBy('participant_name')
            ->get();

        $totalParticipants = $participants->count();
        $maleCount = $participants->where('participant_gender', 'Male')->count();
        $femaleCount = $participants->where('participant_gender', 'Female')->count();

        /*
        |--------------------------------------------------------------------------
        | Materials, modules, and content blocks
        |--------------------------------------------------------------------------
        */

        $materials = DB::table('learning_material')
            ->where('course_id', $courseId)
            ->where(function ($query) {
                $query->where('trainer_id', SharedTrainingSchema::currentTrainerId())
                    ->orWhereNull('trainer_id');
            })
            ->orderByDesc('uploaded_at')
            ->get();

        $materialCount = DB::table('learning_material')
            ->where('course_id', $courseId)
            ->count();

        $moduleCount = DB::table('training_module')
            ->where('course_id', $courseId)
            ->count();

        $contentBlockCount = DB::table('training_content_block as b')
            ->join('training_module as m', 'm.module_id', '=', 'b.module_id')
            ->where('m.course_id', $courseId)
            ->where(function ($query) {
                $query->where('b.is_visible', 1)
                    ->orWhereNull('b.is_visible');
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Assessment, quiz, and submission task counts
        |--------------------------------------------------------------------------
        */

        $assessmentCount = DB::table('assessment')
            ->where('course_id', $courseId)
            ->count();

        $quizCount = DB::table('assessment')
            ->where('course_id', $courseId)
            ->whereRaw('LOWER(type) = ?', ['quiz'])
            ->count();

        $submissionTaskCount = DB::table('assessment')
            ->where('course_id', $courseId)
            ->whereRaw('LOWER(type) = ?', ['submission'])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Submission review counts
        | Only count submission tasks here, not quiz attempts.
        |--------------------------------------------------------------------------
        */

        $submissionCount = DB::table('assessment_submission as s')
            ->join('assessment as a', 'a.assessment_id', '=', 's.assessment_id')
            ->where('a.course_id', $courseId)
            ->whereRaw('LOWER(a.type) = ?', ['submission'])
            ->whereNotNull('s.submitted_at')
            ->count();

        $pendingSubmissions = DB::table('assessment_submission as s')
            ->join('assessment as a', 'a.assessment_id', '=', 's.assessment_id')
            ->where('a.course_id', $courseId)
            ->whereRaw('LOWER(a.type) = ?', ['submission'])
            ->whereNotNull('s.submitted_at')
            ->where(function ($q) {
                $q->whereNull('s.status')
                    ->orWhereRaw('LOWER(s.status) <> ?', ['reviewed']);
            })
            ->count();

        $reviewedSubmissions = DB::table('assessment_submission as s')
            ->join('assessment as a', 'a.assessment_id', '=', 's.assessment_id')
            ->where('a.course_id', $courseId)
            ->whereRaw('LOWER(a.type) = ?', ['submission'])
            ->where('s.status', 'Reviewed')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Attendance counts
        |--------------------------------------------------------------------------
        */

        $sessionReferences = $sessions->pluck('session_id')->filter()->unique()->values();
        $attendanceTotal = CanonicalAttendance::query('attendance_record')
            ->whereIn('attendance_record.session_id', $sessionReferences->all() ?: [''])
            ->count();

        // A row is countable as a recorded owner event, but its decimal status is not interpreted.
        $attendanceRecorded = $attendanceTotal;

        $recentWorkspaceLogs = TrainerActivityLogger::workspaceUpdates(
            $courseId,
            $trainingTeam->pluck('trainer_id'),
            $trainerId,
            12
        );

        $isLmsMode = strtolower($course->training_mode ?? 'standard') === 'lms';

        return view('trainer.training.workspace-overview', compact(
            'course',
            'sessions',
            'participants',
            'materials',

            'totalParticipants',
            'maleCount',
            'femaleCount',

            'moduleCount',
            'contentBlockCount',
            'materialCount',

            'assessmentCount',
            'quizCount',
            'submissionTaskCount',

            'submissionCount',
            'pendingSubmissions',
            'reviewedSubmissions',

            'attendanceTotal',
            'attendanceRecorded',

            'sessionCount',
            'nextSession',

            'recentWorkspaceLogs',
            'trainingTeam',
            'isLmsMode'
        ));
    }

    private function defaultTrainingModules(): array
    {
        return [
            [
                'module_title' => 'General Info',
                'module_type' => 'General Info',
                'module_order' => 1,
                'subtitle' => 'Training introduction',
                'image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=400&q=80',
                'hover' => 'hover:border-indigo-400',
                'gradient' => 'from-slate-900/90 via-slate-900/40 to-transparent',
            ],
            [
                'module_title' => 'Who Am I',
                'module_type' => 'Who Am I',
                'module_order' => 2,
                'subtitle' => 'Instructor profile',
                'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=80',
                'hover' => 'hover:border-indigo-400',
                'gradient' => 'from-slate-900/90 via-slate-900/40 to-transparent',
            ],
            [
                'module_title' => 'Teaching Plan',
                'module_type' => 'Teaching Plan',
                'module_order' => 3,
                'subtitle' => 'Syllabus & schedule',
                'image' => 'https://images.unsplash.com/photo-1503694978374-8a2fa686963a?w=400&q=80',
                'hover' => 'hover:border-indigo-400',
                'gradient' => 'from-slate-900/90 via-slate-900/40 to-transparent',
            ],
            [
                'module_title' => 'Books and Resource',
                'module_type' => 'Book and Resource',
                'module_order' => 4,
                'subtitle' => 'PDFs & external links',
                'image' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=400&q=80',
                'hover' => 'hover:border-indigo-400',
                'gradient' => 'from-slate-900/90 via-slate-900/40 to-transparent',
            ],
            [
                'module_title' => 'Assessment',
                'module_type' => 'Assessment',
                'module_order' => 5,
                'subtitle' => 'Quiz & submission tasks',
                'image' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&q=80',
                'hover' => 'hover:border-rose-400',
                'gradient' => 'from-rose-900/90 via-slate-900/60 to-transparent',
            ],
            [
                'module_title' => 'Chapter 1',
                'module_type' => 'Chapter 1',
                'module_order' => 6,
                'subtitle' => 'Training content',
                'image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=80',
                'hover' => 'hover:border-indigo-400',
                'gradient' => 'from-slate-900/90 via-slate-900/40 to-transparent',
            ],
        ];
    }

    private function ensureTrainingModules(string $courseId): void
    {
        $hasAnyModule = DB::table('training_module')
            ->where('course_id', $courseId)
            ->exists();

        if ($hasAnyModule) {
            return;
        }

        foreach ($this->defaultTrainingModules() as $module) {
            DB::table('training_module')->insert([
                'course_id' => $courseId,
                'module_title' => $module['module_title'],
                'module_type' => $module['module_type'],
                'module_order' => $module['module_order'],
                'cover_image' => $module['image'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function trainerCanAccessCourse(string $courseId): bool
    {
        $trainerId = SharedTrainingSchema::currentTrainerId();

        return SharedTrainingSchema::trainerAssignedToCourse($trainerId, $courseId);
    }

    private function nextManualId(string $table, string $column): int
    {
        return ((int) DB::table($table)->max($column)) + 1;
    }

    private function formatStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = str_replace('\\', '/', $path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['storage/'])) {
            return asset($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public function materials(string $courseId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()->route('trainer.courses')
                ->with('error', 'You are not assigned to this training.');
        }

        $course = CanonicalTrainingQuery::courses()->where('course.courseID', $courseId)->first();

        if (! $course) {
            return redirect()->route('trainer.courses')
                ->with('error', 'Training not found.');
        }

        $this->ensureTrainingModules($courseId);

        $defaultModules = collect($this->defaultTrainingModules())->keyBy('module_type');

        $modules = DB::table('training_module')
            ->where('course_id', $courseId)
            ->orderBy('module_order')
            ->get()
            ->map(function ($module) use ($defaultModules) {
                $default = $defaultModules->get($module->module_type);

                return (object) [
                    'module_id' => $module->module_id,
                    'course_id' => $module->course_id,
                    'module_title' => $module->module_title,
                    'module_type' => $module->module_type,
                    'module_order' => $module->module_order,
                    'cover_image' => $this->formatStorageUrl($module->cover_image ?: ($default['image'] ?? null)),
                    'raw_cover_image' => $module->cover_image,
                    'subtitle' => $default['subtitle'] ?? 'Training content',
                    'hover' => $default['hover'] ?? 'hover:border-indigo-400',
                    'gradient' => $default['gradient'] ?? 'from-slate-900/90 via-slate-900/40 to-transparent',
                ];
            });

        $blocks = $this->materialAuthoring
            ->blocksForModules($modules->pluck('module_id'))
            ->map(function ($block) use ($courseId) {
                if ($block->block_type === 'youtube') {
                    $block->external_url = $this->normalizeYoutubeUrl($block->external_url ?? null);
                }
                $block->file_url = $this->formatStorageUrl($block->file_path ?? null);
                $safePath = in_array($block->block_type, ['material', 'media'], true)
                    ? $this->eligibleLocalAuthoringAssetPath(
                        $block->file_path ?? null,
                        $courseId,
                        ['training-materials', 'training-media']
                    )
                    : null;
                $block->file_available = $safePath !== null
                    && Storage::disk('public')->exists($safePath);

                return $block;
            });

        $blocksByModule = $blocks->groupBy('module_id');

        $assessments = $this->materialAuthoring->assessmentsForCourse($courseId);

        $quizAssessments = $assessments->filter(function ($assessment) {
            return $assessment->type === 'quiz';
        })->values();

        $submissionAssessments = $assessments->filter(function ($assessment) {
            return $assessment->type === 'submission';
        })->values();

        $totalParticipants = CanonicalEnrollment::query()
            ->where('course_id', $courseId)
            ->count();

        // Pending submissions count drives the red number badge on the Submissions tab.
        $pendingSubmissions = 0;
        if (Schema::hasTable('assessment_submission')) {
            $pendingSubmissions = DB::table('assessment_submission as s')
                ->join('assessment as a', 'a.assessment_id', '=', 's.assessment_id')
                ->where('a.course_id', $courseId)
                ->whereRaw('LOWER(a.type) = ?', ['submission'])
                ->whereNotNull('s.submitted_at')
                ->where(function ($query) {
                    $query->whereNull('s.status')
                        ->orWhere('s.status', '!=', 'Reviewed');
                })
                ->count();
        }

        return view('trainer.training.material', compact(
            'course',
            'modules',
            'blocks',
            'blocksByModule',
            'assessments',
            'quizAssessments',
            'submissionAssessments',
            'totalParticipants',
            'pendingSubmissions'
        ));
    }

    public function assessmentBuilder(string $courseId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()->route('trainer.courses')
                ->with('error', 'You are not assigned to this training.');
        }

        $course = CanonicalTrainingQuery::courses()->where('course.courseID', $courseId)->first();

        if (! $course) {
            return redirect()->route('trainer.courses')
                ->with('error', 'Training not found.');
        }

        $assessments = $this->materialAuthoring->assessmentsForCourse($courseId);
        $requestedAssessment = $assessments->firstWhere(
            'assessment_id',
            (int) request()->query('assessment', 0)
        );
        $initialAssessmentType = $requestedAssessment
            ? $requestedAssessment->type
            : (request()->query('type') === 'submission' ? 'submission' : 'quiz');

        return view('trainer.training.assessment-builder', [
            'course' => $course,
            'assessments' => $assessments,
            'initialAssessmentType' => $initialAssessmentType,
        ]);
    }

    public function assessmentAuthoringData(
        Request $request,
        string $courseId,
        int $assessmentId
    ): JsonResponse {
        if (session('user_role') !== 'trainer') {
            return response()->json([
                'success' => false,
                'message' => 'Please login as trainer first.',
            ], 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this training.',
            ], 403);
        }

        $assessment = DB::table('assessment')
            ->where('course_id', $courseId)
            ->where('assessment_id', $assessmentId)
            ->first();

        if (! $assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'assessment' => $this->assessmentAuthoringPayload($assessmentId),
        ]);
    }

    public function assessmentQuestionImage(
        string $courseId,
        int $assessmentId,
        int $questionId
    ) {
        abort_unless(session('user_role') === 'trainer', 401);
        abort_unless($this->trainerCanAccessCourse($courseId), 403);

        $question = DB::table('assessment_question as question')
            ->join('assessment', 'assessment.assessment_id', '=', 'question.assessment_id')
            ->where('assessment.course_id', $courseId)
            ->where('assessment.assessment_id', $assessmentId)
            ->where('question.question_id', $questionId)
            ->select('question.question_image_path')
            ->first();
        abort_unless($question, 404);

        $safePath = $this->eligibleLocalAuthoringAssetPath(
            $question->question_image_path ?? null,
            $courseId,
            ['assessment-question-images']
        );
        abort_unless($safePath && Storage::disk('public')->exists($safePath), 404);

        return Storage::disk('public')->response($safePath, null, [
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function storeModule(Request $request, string $courseId)
    {
        $wantsJson = $this->authoringRequestWantsJson($request);
        $fail = function (string $message, int $status = 422, array $errors = []) use ($courseId, $wantsJson) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $errors,
                ], $status);
            }

            $response = redirect()
                ->route('trainer.courses.materials', $courseId)
                ->withInput()
                ->with('error', $message);

            return $errors === [] ? $response : $response->withErrors($errors);
        };

        if (session('user_role') !== 'trainer') {
            if ($wantsJson) {
                return $fail('Please login as trainer first.', 401);
            }

            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $validator = Validator::make($request->all(), [
            'module_title' => ['required', 'string', 'max:150'],
            'cover_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'module_title.required' => 'Module title is required.',
            'module_title.max' => 'Module title must be 150 characters or fewer.',
            'cover_image_file.image' => 'Cover must be an image file.',
            'cover_image_file.mimes' => 'Cover must be a JPG, PNG, or WebP image.',
            'cover_image_file.max' => 'Cover image must be 5 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return $fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $moduleTitle = trim((string) $validator->validated()['module_title']);

        if ($moduleTitle === '') {
            return $fail('Module title is required.', 422, [
                'module_title' => ['Module title is required.'],
            ]);
        }

        // module_type currently mirrors the title for trainer-created modules.
        // This is a temporary local-schema convention, not a final shared contract.
        $duplicate = DB::table('training_module')
            ->where('course_id', $courseId)
            ->whereRaw('LOWER(module_title) = ?', [strtolower($moduleTitle)])
            ->exists();

        if ($duplicate) {
            return $fail(
                'A module named "'.$moduleTitle.'" already exists in this training. Please choose a different title.',
                422,
                ['module_title' => ['Another module already uses this title.']]
            );
        }

        $coverPath = null;
        if ($request->hasFile('cover_image_file')) {
            $coverPath = $request->file('cover_image_file')->store('module-covers', 'public');
        }

        try {
            $newModuleId = DB::transaction(function () use (
                $courseId,
                $coverPath,
                $moduleTitle
            ) {
                $nextOrder = ((int) DB::table('training_module')
                    ->where('course_id', $courseId)
                    ->max('module_order')) + 1;

                return DB::table('training_module')->insertGetId([
                    'course_id' => $courseId,
                    'module_title' => $moduleTitle,
                    'module_type' => $moduleTitle,
                    'module_order' => $nextOrder,
                    'cover_image' => $coverPath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($coverPath) {
                try {
                    if (Storage::disk('public')->exists($coverPath)) {
                        Storage::disk('public')->delete($coverPath);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::warning('Unable to clean up a new module cover after a failed save.', [
                        'course_id' => $courseId,
                        'path' => $coverPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            Log::warning('Module creation failed.', [
                'course_id' => $courseId,
                'error' => $exception->getMessage(),
            ]);

            return $fail('The module could not be created. Please try again.', 500);
        }

        TrainerActivityLogger::log(
            session('user_id'),
            'module.create',
            $moduleTitle,
            $courseId,
            'module',
            $newModuleId
        );

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => 'Module created successfully.',
                'module_id' => $newModuleId,
                'module_title' => $moduleTitle,
                'block_count' => 0,
                'refresh_url' => route('trainer.courses.materials', $courseId),
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', 'Module created successfully.');
    }

    public function updateModule(Request $request, string $courseId, int $moduleId)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With'))
                === 'xmlhttprequest';

        $fail = function (
            string $message,
            int $status = 422,
            array $errors = []
        ) use ($courseId, $wantsJson): JsonResponse|RedirectResponse {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $errors,
                ], $status);
            }

            $response = redirect()
                ->route('trainer.courses.materials', $courseId)
                ->withInput()
                ->with('error', $message);

            return $errors === [] ? $response : $response->withErrors($errors);
        };

        if (session('user_role') !== 'trainer') {
            if ($wantsJson) {
                return $fail('Please login as trainer first.', 401);
            }

            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $module = DB::table('training_module')
            ->where('module_id', $moduleId)
            ->where('course_id', $courseId)
            ->first();

        if (! $module) {
            return $fail('Module not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'module_title' => 'required|string|max:150',
            'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'delete_cover_image' => 'nullable|in:0,1',
        ], [
            'module_title.required' => 'Module title is required.',
            'module_title.max' => 'Module title must be 150 characters or fewer.',
            'cover_image_file.image' => 'Cover must be an image file.',
            'cover_image_file.mimes' => 'Cover must be a JPG, PNG, or WebP image.',
            'cover_image_file.max' => 'Cover image must be 5 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return $fail(
                $validator->errors()->first(),
                422,
                $validator->errors()->toArray()
            );
        }

        $newTitle = trim((string) $request->input('module_title'));

        if ($newTitle === '') {
            return $fail('Module title is required.', 422, [
                'module_title' => ['Module title is required.'],
            ]);
        }

        // Block duplicate titles within the same course (case-insensitive).
        $duplicate = DB::table('training_module')
            ->where('course_id', $courseId)
            ->where('module_id', '!=', $moduleId)
            ->whereRaw('LOWER(module_title) = ?', [strtolower($newTitle)])
            ->exists();

        if ($duplicate) {
            return $fail(
                'Another module in this training already uses that title.',
                422,
                ['module_title' => ['Another module already uses this title.']]
            );
        }

        $updateData = [
            'module_title' => $newTitle,
            'updated_at' => now(),
        ];

        $newCoverPath = null;
        $oldCoverPath = $module->cover_image;
        $wantsDelete = (string) $request->input('delete_cover_image', '0') === '1';

        // 1. New file uploaded — wins over any delete flag.
        if ($request->hasFile('cover_image_file')) {
            $file = $request->file('cover_image_file');
            if ($file->isValid()) {
                $newCoverPath = $file->store('module-covers', 'public');
                $updateData['cover_image'] = $newCoverPath;
            }
        }
        // 2. Delete-only request (no new file, flag set, has existing custom cover)
        elseif ($wantsDelete && $oldCoverPath) {
            $updateData['cover_image'] = null;
        }

        $hasMeaningfulModuleChange = $newTitle !== trim((string) $module->module_title)
            || $newCoverPath !== null
            || ($wantsDelete && ! empty($oldCoverPath));

        DB::beginTransaction();

        try {
            DB::table('training_module')
                ->where('module_id', $moduleId)
                ->where('course_id', $courseId)
                ->update($updateData);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($newCoverPath && Storage::disk('public')->exists($newCoverPath)) {
                Storage::disk('public')->delete($newCoverPath);
            }

            Log::warning('Module update failed.', [
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'error' => $e->getMessage(),
            ]);

            return $fail('The module could not be updated. Please try again.', 500);
        }

        // Delete the OLD file when either:
        //   (a) we just replaced it with a new upload, or
        //   (b) the user reset cover to default (no new upload, delete flag set)
        $shouldDeleteOld = (
            $newCoverPath !== null ||
            ($wantsDelete && $oldCoverPath)
        );
        $cleanupCoverPath = $this->eligibleLocalModuleCoverCleanupPath($oldCoverPath);

        if (
            $shouldDeleteOld
            && $cleanupCoverPath
        ) {
            try {
                $coverStillReferenced = DB::table('training_module')
                    ->where('cover_image', $cleanupCoverPath)
                    ->where('module_id', '!=', $moduleId)
                    ->exists();

                if (
                    ! $coverStillReferenced
                    && Storage::disk('public')->exists($cleanupCoverPath)
                ) {
                    Storage::disk('public')->delete($cleanupCoverPath);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up a previous module cover.', [
                    'course_id' => $courseId,
                    'module_id' => $moduleId,
                    'path' => $cleanupCoverPath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }

        if ($hasMeaningfulModuleChange) {
            TrainerActivityLogger::log(
                session('user_id'),
                'module.update',
                $newTitle,
                $courseId,
                'module',
                $moduleId
            );
        }

        $msg = 'Module updated successfully.';
        if ($newCoverPath) {
            $msg = 'Module updated. Cover image replaced.';
        } elseif ($wantsDelete && $oldCoverPath) {
            $msg = 'Module updated. Cover image reset to default.';
        }

        if ($wantsJson) {
            $updatedCoverPath = array_key_exists('cover_image', $updateData)
                ? $updateData['cover_image']
                : $oldCoverPath;
            $defaultModule = collect($this->defaultTrainingModules())
                ->firstWhere('module_type', $module->module_type);
            $displayCoverPath = $updatedCoverPath
                ?: ($defaultModule['image'] ?? null);

            return response()->json([
                'success' => true,
                'message' => $msg,
                'module_id' => $moduleId,
                'module_title' => $newTitle,
                'cover_url' => $this->formatStorageUrl($displayCoverPath),
                'has_custom_cover' => (bool) $updatedCoverPath,
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', $msg);
    }

    public function saveModuleBlocks(Request $request, string $courseId, int $moduleId)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        $fail = function (string $message, int $status = 422) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return back()->withInput()->with('error', $message);
        };

        $blockConflictMessage =
            'The module content could not be saved because it is out of date. '.
            'Refresh the page before retrying.';
        $blockConflictMarker = 'data-003-block-reference-conflict';

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $module = DB::table('training_module')
            ->where('module_id', $moduleId)
            ->where('course_id', $courseId)
            ->first();

        if (! $module) {
            return $fail('Module not found.', 404);
        }

        $validated = $request->validate([
            'module_title' => ['required', 'string', 'max:150'],
            'cover_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'blocks' => ['nullable', 'array'],
            'blocks.*.block_id' => ['nullable'],
            'blocks.*.block_type' => [
                'required_with:blocks',
                'in:title,text,material,media,youtube,quiz,submission',
            ],
            'blocks.*.block_title' => ['nullable', 'string', 'max:255'],
            'blocks.*.block_content' => ['nullable', 'string'],
            'blocks.*.external_url' => ['nullable', 'string', 'max:500'],
        ]);

        $moduleTitle = trim((string) $validated['module_title']);

        if ($moduleTitle === '') {
            return $fail('Module title is required.', 422);
        }

        $duplicateModuleTitle = DB::table('training_module')
            ->where('course_id', $courseId)
            ->where('module_id', '!=', $moduleId)
            ->whereRaw('LOWER(module_title) = ?', [strtolower($moduleTitle)])
            ->exists();

        if ($duplicateModuleTitle) {
            return $fail(
                'Another module in this training already uses that title.',
                422
            );
        }

        $blockRows = $validated['blocks'] ?? [];
        $blockPlan = [];

        foreach ($blockRows as $index => $blockData) {
            $blockType = $blockData['block_type'] ?? 'text';
            $blockTitle = trim((string) ($blockData['block_title'] ?? ''));
            $blockContent = trim((string) ($blockData['block_content'] ?? ''));
            $externalUrl = trim((string) ($blockData['external_url'] ?? ''));
            $blockId = $blockData['block_id'] ?? null;

            $errorMessage = null;
            if ($blockType === 'title' && $blockTitle === '') {
                $errorMessage = 'Title block cannot be empty. Fill the title or remove the block.';
            } elseif ($blockType === 'text' && $blockContent === '') {
                $errorMessage = 'Text block cannot be empty. Fill the text or remove the block.';
            } elseif (in_array($blockType, ['material', 'media'], true) && $blockTitle === '') {
                $errorMessage = ucfirst($blockType).' block needs a title. Fill it or remove the block.';
            } elseif ($blockType === 'youtube' && ($blockTitle === '' || $externalUrl === '')) {
                $errorMessage = 'YouTube block needs a title and link. Fill them or remove the block.';
            } elseif ($blockType === 'submission' && $blockTitle === '') {
                $errorMessage = 'Submission block needs a valid linked assessment.';
            } elseif ($blockType === 'quiz' && $blockTitle === '') {
                $errorMessage = 'Quiz block needs a selected quiz. Remove the block and select a valid quiz.';
            }

            if ($errorMessage) {
                return $fail($errorMessage, 422);
            }

            if (! $blockId && ! in_array($blockType, ['title', 'text'], true)) {
                return $fail(
                    'Uploaded files, media, videos, and assessments must be added with their dedicated controls.',
                    422
                );
            }

            if ($blockType === 'youtube') {
                $externalUrl = $this->normalizeYoutubeUrl($externalUrl);

                if (! $externalUrl) {
                    return $fail(
                        'YouTube block needs a valid YouTube watch, short, or embed URL.',
                        422
                    );
                }
            }

            $blockPlan[] = [
                'block_id' => $blockId,
                'block_type' => $blockType,
                'block_title' => $blockTitle,
                'block_content' => $blockContent,
                'external_url' => $externalUrl,
                'block_order' => $index + 1,
            ];
        }

        $submittedExistingBlockIdsWithDuplicates = [];

        foreach ($blockPlan as $blockData) {
            if ($blockData['block_id']) {
                $submittedExistingBlockIdsWithDuplicates[] = (string) $blockData['block_id'];
            }
        }

        $submittedExistingBlockIds = array_values(array_unique(
            $submittedExistingBlockIdsWithDuplicates
        ));
        sort($submittedExistingBlockIds, SORT_STRING);

        if (
            count($submittedExistingBlockIds)
            !== count($submittedExistingBlockIdsWithDuplicates)
        ) {
            return $fail($blockConflictMessage, 409);
        }

        if ($submittedExistingBlockIds !== []) {
            $matchedExistingBlockIds = array_values(array_unique(array_map(
                static fn ($blockId): string => (string) $blockId,
                DB::table('training_content_block')
                    ->where('module_id', $moduleId)
                    ->whereIn('block_id', $submittedExistingBlockIds)
                    ->pluck('block_id')
                    ->all()
            )));
            sort($matchedExistingBlockIds, SORT_STRING);

            if ($matchedExistingBlockIds !== $submittedExistingBlockIds) {
                return $fail($blockConflictMessage, 409);
            }
        }

        $oldCoverPath = $module->cover_image;
        $coverPath = $oldCoverPath;
        $newCoverPath = null;
        $activityBlocks = DB::table('training_content_block')
            ->where('module_id', $moduleId)
            ->get()
            ->keyBy(fn (object $block): string => (string) $block->block_id);
        $hasMeaningfulBlockChange = $moduleTitle !== trim((string) $module->module_title)
            || $request->hasFile('cover_image_file');
        $hasNonMaterialBlockChange = $hasMeaningfulBlockChange;
        $changedMaterials = [];

        foreach ($blockPlan as $plannedBlock) {
            $existingBlock = $plannedBlock['block_id']
                ? $activityBlocks->get((string) $plannedBlock['block_id'])
                : null;
            if (! $existingBlock) {
                $hasMeaningfulBlockChange = true;
                $hasNonMaterialBlockChange = true;

                continue;
            }

            $typeChanged = trim((string) ($existingBlock->block_type ?? '')) !== $plannedBlock['block_type'];
            $titleChanged = trim((string) ($existingBlock->block_title ?? '')) !== $plannedBlock['block_title'];
            $contentChanged = trim((string) ($existingBlock->block_content ?? '')) !== $plannedBlock['block_content'];
            $urlChanged = trim((string) ($existingBlock->external_url ?? '')) !== $plannedBlock['external_url'];
            $orderChanged = (int) ($existingBlock->block_order ?? 0) !== $plannedBlock['block_order'];

            if ($typeChanged || $titleChanged || $contentChanged || $urlChanged || $orderChanged) {
                $hasMeaningfulBlockChange = true;

                if (
                    ! $typeChanged
                    && ! $urlChanged
                    && ! $orderChanged
                    && ! empty($existingBlock->material_id)
                    && ($titleChanged || $contentChanged)
                ) {
                    $changedMaterials[(int) $existingBlock->material_id] = $plannedBlock['block_title'];
                } else {
                    $hasNonMaterialBlockChange = true;
                }
            }
        }

        try {
            if ($request->hasFile('cover_image_file')) {
                $newCoverPath = $request->file('cover_image_file')
                    ->store('module-covers', 'public');
                $coverPath = $newCoverPath;
            }

            DB::transaction(function () use (
                $blockConflictMarker,
                $blockPlan,
                $courseId,
                $coverPath,
                $moduleTitle,
                $moduleId,
                $submittedExistingBlockIds,
            ) {
                $lockedModule = DB::table('training_module')
                    ->where('module_id', $moduleId)
                    ->where('course_id', $courseId)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedModule) {
                    throw new \RuntimeException('Module not found.');
                }

                $lockedBlocksById = [];

                if ($submittedExistingBlockIds !== []) {
                    $lockedBlocks = DB::table('training_content_block')
                        ->where('module_id', $moduleId)
                        ->whereIn('block_id', $submittedExistingBlockIds)
                        ->lockForUpdate()
                        ->get();

                    $lockedBlockIds = array_values(array_unique(array_map(
                        static fn ($blockId): string => (string) $blockId,
                        $lockedBlocks->pluck('block_id')->all()
                    )));
                    sort($lockedBlockIds, SORT_STRING);

                    if ($lockedBlockIds !== $submittedExistingBlockIds) {
                        throw new \UnexpectedValueException($blockConflictMarker);
                    }

                    foreach ($lockedBlocks as $lockedBlock) {
                        $lockedBlocksById[(string) $lockedBlock->block_id] = $lockedBlock;
                    }
                }

                DB::table('training_module')
                    ->where('module_id', $moduleId)
                    ->where('course_id', $courseId)
                    ->update([
                        'module_title' => $moduleTitle,
                        'cover_image' => $coverPath,
                        'updated_at' => now(),
                    ]);

                foreach ($blockPlan as $blockData) {
                    $blockId = $blockData['block_id'];
                    $blockTitle = $blockData['block_title'];
                    $blockContent = $blockData['block_content'];
                    $externalUrl = $blockData['external_url'];

                    if ($blockId) {
                        $block = $lockedBlocksById[(string) $blockId] ?? null;

                        if (
                            ! $block
                            || (
                                property_exists($block, 'block_type')
                                && $block->block_type !== null
                                && $block->block_type !== $blockData['block_type']
                            )
                        ) {
                            throw new \UnexpectedValueException($blockConflictMarker);
                        }

                        DB::table('training_content_block')
                            ->where('block_id', $block->block_id)
                            ->where('module_id', $moduleId)
                            ->update([
                                'block_title' => $blockTitle !== '' ? $blockTitle : null,
                                'block_content' => $blockContent !== '' ? $blockContent : null,
                                'external_url' => $externalUrl !== '' ? $externalUrl : null,
                                'block_order' => $blockData['block_order'],
                                'updated_at' => now(),
                            ]);

                        if ($block->material_id) {
                            DB::table('learning_material')
                                ->where('material_id', $block->material_id)
                                ->where('course_id', $courseId)
                                ->update([
                                    'ai_generated_title' => $blockTitle !== '' ? $blockTitle : null,
                                    'ai_synopsis' => $blockContent !== '' ? $blockContent : null,
                                ]);
                        }

                    } else {
                        DB::table('training_content_block')->insert([
                            'module_id' => $moduleId,
                            'block_type' => $blockData['block_type'],
                            'block_title' => $blockTitle !== '' ? $blockTitle : null,
                            'block_content' => $blockContent !== '' ? $blockContent : null,
                            'external_url' => $externalUrl !== '' ? $externalUrl : null,
                            'material_id' => null,
                            'assessment_id' => null,
                            'block_order' => $blockData['block_order'],
                            'is_visible' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            $isBlockReferenceConflict =
                $exception instanceof \UnexpectedValueException
                && $exception->getMessage() === $blockConflictMarker;

            if ($newCoverPath) {
                try {
                    if (
                        Storage::disk('public')->exists($newCoverPath)
                        && ! Storage::disk('public')->delete($newCoverPath)
                    ) {
                        Log::warning('Unable to delete replacement module cover after save failure.', [
                            'module_id' => $moduleId,
                            'path' => $newCoverPath,
                        ]);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::warning('Unable to delete replacement module cover after save failure.', [
                        'module_id' => $moduleId,
                        'path' => $newCoverPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            if ($isBlockReferenceConflict) {
                return $fail($blockConflictMessage, 409);
            }

            Log::warning('Module block save failed.', [
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'error' => $exception->getMessage(),
            ]);

            return $fail('This module could not be saved. Please try again.', 500);
        }

        $cleanupOldCoverPath = $this->eligibleLocalModuleCoverCleanupPath($oldCoverPath);

        if (
            $newCoverPath
            && $cleanupOldCoverPath
            && $cleanupOldCoverPath !== $newCoverPath
        ) {
            try {
                $coverStillReferenced = DB::table('training_module')
                    ->where('cover_image', $cleanupOldCoverPath)
                    ->where('module_id', '!=', $moduleId)
                    ->exists();

                if (! $coverStillReferenced) {
                    if (
                        Storage::disk('public')->exists($cleanupOldCoverPath)
                        && ! Storage::disk('public')->delete($cleanupOldCoverPath)
                    ) {
                        Log::warning('Unable to delete previous module cover after save.', [
                            'module_id' => $moduleId,
                            'path' => $cleanupOldCoverPath,
                        ]);
                    }
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to delete previous module cover after save.', [
                    'module_id' => $moduleId,
                    'path' => $cleanupOldCoverPath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }

        if ($hasMeaningfulBlockChange) {
            if (! $hasNonMaterialBlockChange && count($changedMaterials) === 1) {
                $changedMaterialId = (int) array_key_first($changedMaterials);
                $changedMaterialTitle = trim((string) $changedMaterials[$changedMaterialId]);
                if ($changedMaterialTitle === '') {
                    $changedMaterialTitle = (string) DB::table('learning_material')
                        ->where('material_id', $changedMaterialId)
                        ->value('original_file_name');
                }

                TrainerActivityLogger::log(
                    session('user_id'),
                    'material.update',
                    $changedMaterialTitle ?: 'Learning material',
                    $courseId,
                    'material',
                    $changedMaterialId
                );
            } else {
                TrainerActivityLogger::log(
                    session('user_id'),
                    'block.update',
                    $moduleTitle,
                    $courseId,
                    'module',
                    $moduleId
                );
            }
        }

        if ($wantsJson) {
            $savedBlocks = $this->materialAuthoring->builderBlocksQuery()
                ->where('module_id', $moduleId)
                ->orderBy('block_order')
                ->orderBy('block_id')
                ->get(['block_id', 'block_order', 'block_type'])
                ->map(function ($b) use ($courseId) {
                    return [
                        'block_id' => (int) $b->block_id,
                        'block_order' => (int) $b->block_order,
                        'block_type' => $b->block_type,
                        'delete_url' => route('trainer.courses.blocks.delete', [$courseId, $b->block_id]),
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'message' => 'Module and blocks saved successfully.',
                'module_id' => $moduleId,
                'module_title' => $moduleTitle,
                'blocks' => $savedBlocks,
                'block_count' => count($savedBlocks),
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', 'Module and blocks saved successfully.');
    }

    public function storeMaterialBlock(Request $request, string $courseId, int $moduleId)
    {
        return $this->storeUploadedBlock($request, $courseId, $moduleId, 'material');
    }

    public function storeMediaBlock(Request $request, string $courseId, int $moduleId)
    {
        return $this->storeUploadedBlock($request, $courseId, $moduleId, 'media');
    }

    private function storeUploadedBlock(Request $request, string $courseId, int $moduleId, string $blockType)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        $fail = function (string $message, int $status = 422) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return back()->withInput()->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $module = DB::table('training_module')
            ->where('module_id', $moduleId)
            ->where('course_id', $courseId)
            ->first();

        if (! $module) {
            return $fail('Module not found.', 404);
        }

        $allowed = $this->allowedUploadedBlockExtensions($blockType);
        $validator = Validator::make($request->all(), [
            'material_title' => ['nullable', 'string', 'max:255'],
            'material_description' => ['nullable', 'string', 'max:1000'],
            'block_order' => ['nullable', 'integer', 'min:1'],
            'material_file' => [
                'required',
                'file',
                'mimes:'.implode(',', $allowed),
                'max:204800',
            ],
        ], [
            'material_file.mimes' => 'The selected file type is not supported.',
            'material_file.max' => 'The file is larger than the 200 MB limit.',
        ]);

        if ($validator->fails()) {
            return $fail($validator->errors()->first(), 422);
        }

        $validated = $validator->validated();

        $file = $request->file('material_file');

        if (! $file || ! $file->isValid()) {
            return $fail('The file failed to upload. If the file is large, increase upload_max_filesize and post_max_size in php.ini.');
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowed, true)) {
            return $fail('This file type is not allowed for '.$blockType.' block.');
        }

        $storedName = Str::uuid().'.'.$extension;
        $folder = $this->uploadedBlockStorageFolder($blockType, $courseId);
        $path = $file->storeAs($folder, $storedName, 'public');

        try {
            [$materialId, $newBlockId] = DB::transaction(function () use (
                $blockType,
                $courseId,
                $module,
                $moduleId,
                $originalName,
                $path,
                $validated
            ) {
                $materialId = $this->nextManualId(
                    'learning_material',
                    'material_id'
                );
                $title = $validated['material_title']
                    ?: pathinfo($originalName, PATHINFO_FILENAME);

                DB::table('learning_material')->insert([
                    'material_id' => $materialId,
                    'course_id' => $courseId,
                    'trainer_id' => (string) session('user_id'),
                    'original_file_name' => $originalName,
                    'file_path' => $path,
                    'material_type' => $blockType === 'media'
                        ? 'Media'
                        : $module->module_type,
                    'metadata_cleared' => 0,
                    'extracted_metadata' => null,
                    'ai_generated_title' => $title,
                    'ai_synopsis' => $validated['material_description'] ?? null,
                    'uploaded_at' => now(),
                ]);

                $nextOrder = $this->materialAuthoring->resolveAppendedBlockOrder(
                    $moduleId,
                    isset($validated['block_order'])
                        ? (int) $validated['block_order']
                        : null
                );

                $newBlockId = DB::table('training_content_block')->insertGetId([
                    'module_id' => $moduleId,
                    'block_type' => $blockType,
                    'block_title' => $title,
                    'block_content' => $validated['material_description'] ?? null,
                    'external_url' => null,
                    'material_id' => $materialId,
                    'assessment_id' => null,
                    'block_order' => $nextOrder,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [$materialId, $newBlockId];
            });
        } catch (\Throwable $exception) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up an uploaded authoring file after a failed save.', [
                    'course_id' => $courseId,
                    'module_id' => $moduleId,
                    'path' => $path,
                    'error' => $cleanupException->getMessage(),
                ]);
            }

            Log::warning('Authoring file block creation failed.', [
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'error' => $exception->getMessage(),
            ]);

            return $fail('The file was uploaded but could not be added to the module. Please try again.', 500);
        }

        TrainerActivityLogger::log(
            session('user_id'),
            'material.upload',
            $originalName,
            $courseId,
            'material',
            $materialId
        );

        if ($wantsJson) {
            $newBlock = $this->fetchBlockForRender($newBlockId);

            $blockHtml = view('trainer.training.partials.block', [
                'block' => $newBlock,
                'blockIndex' => 9000 + $newBlockId,
                'saveFormId' => 'saveModuleForm'.$moduleId,
                'courseId' => $courseId,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => ucfirst($blockType).' block added.',
                'block_id' => $newBlockId,
                'block_html' => $blockHtml,
                'module_id' => $moduleId,
                'block_count' => $this->materialAuthoring->visibleBlockCount($moduleId),
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', ucfirst($blockType).' block added successfully.');
    }

    public function storeAssessment(
        SaveAssessmentRequest $request,
        string $courseId
    ): JsonResponse|RedirectResponse {
        $wantsJson = $this->authoringRequestWantsJson($request);

        if (session('user_role') !== 'trainer') {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login as trainer first.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'You are not assigned to this training.',
                403
            );
        }

        $validated = $request->validated();

        if ($validated['type'] === 'quiz') {
            $quizError = $this->quizValidationMessage($validated['questions'] ?? []);
            if ($quizError) {
                return $this->authoringFailureResponse(
                    $request,
                    $courseId,
                    $quizError
                );
            }

            $validated['total_marks'] = collect(
                $validated['questions'] ?? []
            )->sum(static fn (array $question): float => (float) (
                $question['marks'] ?? 0
            ));
        }

        $assessmentId = $this->nextManualId('assessment', 'assessment_id');
        $newQuestionImagePaths = [];

        $insertData = [
            'assessment_id' => $assessmentId,
            'course_id' => $courseId,
            'module_id' => null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'instruction_text' => $validated['instruction_text'] ?? null,
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'accepted_format' => $validated['type'] === 'submission'
                ? ($validated['accepted_format'] ?? null)
                : null,
            'time_limit_minutes' => $validated['type'] === 'quiz'
                ? ($validated['time_limit_minutes'] ?? null)
                : null,
            'max_attempts' => $validated['type'] === 'quiz'
                ? ($validated['max_attempts'] ?? null)
                : null,
            'total_marks' => $validated['type'] === 'quiz'
                ? ($validated['total_marks'] ?? null)
                : null,
            'weight_percent' => $validated['type'] === 'quiz'
                ? ($validated['weight_percent'] ?? null)
                : null,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::beginTransaction();

        try {
            DB::table('assessment')->insert($insertData);

            if ($validated['type'] === 'quiz' && ! empty($validated['questions'])) {
                foreach ($validated['questions'] as $index => $question) {
                    $questionType = $question['question_type'] ?? 'mcq';
                    $correctAnswer = $question['correct_answer'] ?? null;

                    if ($questionType === 'checkbox') {
                        $correctAnswer = $question['correct_answers_json'] ?? '[]';
                    }
                    if ($questionType === 'essay') {
                        $correctAnswer = null;
                    }

                    $questionImagePath = null;
                    if ($request->hasFile("questions.$index.question_image")) {
                        $questionImagePath = $request
                            ->file("questions.$index.question_image")
                            ->store(
                                'assessment-question-images/'.$courseId,
                                'public'
                            );
                        $newQuestionImagePaths[] = $questionImagePath;
                    }

                    $questionId = $this->nextManualId(
                        'assessment_question',
                        'question_id'
                    );

                    $questionInsert = [
                        'question_id' => $questionId,
                        'assessment_id' => $assessmentId,
                        'question_text' => $question['question_text'],
                        'question_image_path' => $questionImagePath,
                        'question_order' => $index + 1,
                        'question_type' => $questionType,
                        'options_json' => $question['options_json'] ?? null,
                        'correct_answer' => $correctAnswer,
                        'marks' => $question['marks'] ?? 0,
                        'grading_rubric' => $question['grading_rubric'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    DB::table('assessment_question')->insert($questionInsert);
                }
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->cleanupQuestionImagePaths(
                $newQuestionImagePaths,
                $courseId,
                false
            );

            Log::warning('Assessment creation failed.', [
                'course_id' => $courseId,
                'error' => $exception->getMessage(),
            ]);

            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'The assessment could not be created. Your entered values are still available; please try again.',
                500
            );
        }

        TrainerActivityLogger::log(
            session('user_id'),
            $validated['type'] === 'quiz' ? 'quiz.create' : 'submission_task.create',
            $validated['title'],
            $courseId,
            'assessment',
            $assessmentId
        );

        $message = 'Assessment created successfully. You can now add it to a module.';

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'assessment' => $this->assessmentAuthoringPayload($assessmentId),
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', $message);
    }

    public function storeAssessmentBlock(Request $request, string $courseId, int $moduleId)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        $fail = function (string $message, int $status = 422) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return back()->withInput()->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $validated = $request->validate([
            'assessment_id' => ['required'],
            'block_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $module = DB::table('training_module')
            ->where('module_id', $moduleId)
            ->where('course_id', $courseId)
            ->first();

        if (! $module) {
            return $fail('Module not found.', 404);
        }

        try {
            [$assessment, $newBlockId] = DB::transaction(function () use (
                $courseId,
                $moduleId,
                $validated
            ) {
                $assessment = DB::table('assessment')
                    ->where('assessment_id', $validated['assessment_id'])
                    ->where('course_id', $courseId)
                    ->lockForUpdate()
                    ->first();

                if (! $assessment) {
                    throw new \UnexpectedValueException('assessment-not-found');
                }

                $assessmentType = strtolower((string) $assessment->type);

                if (! in_array($assessmentType, ['quiz', 'submission'], true)) {
                    throw new \UnexpectedValueException(
                        'assessment-type-unsupported'
                    );
                }

                $alreadyEmbedded = DB::table('training_content_block')
                    ->where('assessment_id', $assessment->assessment_id)
                    ->exists();
                if ($alreadyEmbedded) {
                    throw new \UnexpectedValueException('assessment-already-embedded');
                }

                $nextOrder = $this->materialAuthoring->resolveAppendedBlockOrder(
                    $moduleId,
                    isset($validated['block_order'])
                        ? (int) $validated['block_order']
                        : null
                );

                $newBlockId = DB::table('training_content_block')->insertGetId([
                    'module_id' => $moduleId,
                    'block_type' => $assessmentType,
                    'block_title' => $assessment->title,
                    'block_content' => $assessment->instruction_text,
                    'material_id' => null,
                    'assessment_id' => $assessment->assessment_id,
                    'block_order' => $nextOrder,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('assessment')
                    ->where('assessment_id', $assessment->assessment_id)
                    ->where('course_id', $courseId)
                    ->update([
                        'module_id' => $moduleId,
                        'updated_at' => now(),
                    ]);

                return [$assessment, $newBlockId];
            });
        } catch (\UnexpectedValueException $exception) {
            if ($exception->getMessage() === 'assessment-not-found') {
                return $fail('Assessment not found.', 404);
            }

            if ($exception->getMessage() === 'assessment-already-embedded') {
                return $fail(
                    'This assessment is already attached to a module. Edit the existing assessment block instead.',
                    409
                );
            }

            if ($exception->getMessage() === 'assessment-type-unsupported') {
                return $fail(
                    'This assessment type is not supported by the current authoring workspace.',
                    422
                );
            }

            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Assessment block creation failed.', [
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'assessment_id' => $validated['assessment_id'],
                'error' => $exception->getMessage(),
            ]);

            return $fail('The assessment could not be added to this module. Please try again.', 500);
        }

        TrainerActivityLogger::log(
            session('user_id'),
            'block.create',
            $assessment->title,
            $courseId,
            'block',
            $newBlockId
        );

        if ($wantsJson) {
            $newBlock = $this->fetchBlockForRender($newBlockId);

            $blockHtml = view('trainer.training.partials.block', [
                'block' => $newBlock,
                'blockIndex' => 9000 + $newBlockId,
                'saveFormId' => 'saveModuleForm'.$moduleId,
                'courseId' => $courseId,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => ucfirst(strtolower($assessment->type)).' block added.',
                'block_id' => $newBlockId,
                'block_html' => $blockHtml,
                'module_id' => $moduleId,
                'block_count' => $this->materialAuthoring->visibleBlockCount($moduleId),
                'assessment_id' => (int) $assessment->assessment_id,
                'assessment_type' => strtolower((string) $assessment->type),
                'attachment_module_id' => (int) $module->module_id,
                'attachment_module_title' => $module->module_title,
            ]);
        }

        return redirect()->route('trainer.courses.materials', $courseId)
            ->with('success', 'Assessment block added inside module.');
    }

    public function deleteBlock(Request $request, string $courseId, int $blockId)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        $fail = function (string $message, int $status = 422) use ($wantsJson, $courseId) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return redirect()->route('trainer.courses.materials', $courseId)->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $block = DB::table('training_content_block')
            ->join('training_module', 'training_content_block.module_id', '=', 'training_module.module_id')
            ->where('training_content_block.block_id', $blockId)
            ->where('training_module.course_id', $courseId)
            ->select('training_content_block.*')
            ->first();

        if (! $block) {
            return $fail('Block not found.', 404);
        }

        $moduleId = $block->module_id;
        $material = null;
        $materialPathToDelete = null;
        $deletedMaterialName = null;
        $deletedMaterialId = null;

        if ($block->material_id) {
            $material = DB::table('learning_material')
                ->where('material_id', $block->material_id)
                ->where('course_id', $courseId)
                ->first();
        }

        DB::beginTransaction();

        try {
            DB::table('training_content_block')->where('block_id', $blockId)->delete();

            $this->materialAuthoring->normalizeBuilderBlockOrder((int) $moduleId);

            if ($material) {
                $stillUsed = DB::table('training_content_block')
                    ->where('material_id', $material->material_id)
                    ->exists();

                if (! $stillUsed) {
                    $materialPathToDelete = $material->file_path;
                    $deletedMaterialName = $material->original_file_name;
                    $deletedMaterialId = $material->material_id;

                    DB::table('learning_material')
                        ->where('material_id', $material->material_id)
                        ->delete();
                }
            }

            if ($block->assessment_id) {
                $assessmentStillEmbedded = DB::table('training_content_block')
                    ->where('assessment_id', $block->assessment_id)
                    ->exists();

                if (! $assessmentStillEmbedded) {
                    DB::table('assessment')
                        ->where('assessment_id', $block->assessment_id)
                        ->where('course_id', $courseId)
                        ->where('module_id', $moduleId)
                        ->update([
                            'module_id' => null,
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            try {
                Log::warning('Block deletion failed.', [
                    'course_id' => $courseId,
                    'block_id' => $blockId,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
                // A logging failure must not alter the generic failure response.
            }

            return $fail('Block could not be deleted.', 500);
        }

        $safeMaterialPath = $this->eligibleLocalAuthoringAssetPath(
            $materialPathToDelete,
            $courseId,
            ['training-materials', 'training-media']
        );

        if ($safeMaterialPath) {
            try {
                if (Storage::disk('public')->exists($safeMaterialPath)) {
                    Storage::disk('public')->delete($safeMaterialPath);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up a deleted material file.', [
                    'course_id' => $courseId,
                    'block_id' => $blockId,
                    'path' => $safeMaterialPath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }

        if ($deletedMaterialName) {
            TrainerActivityLogger::log(
                session('user_id'),
                'material.delete',
                $deletedMaterialName,
                $courseId,
                'material',
                $deletedMaterialId
            );
        } else {
            TrainerActivityLogger::log(
                session('user_id'),
                'block.delete',
                $block->block_title ?? ('Block #'.$blockId),
                $courseId,
                'block',
                $blockId
            );
        }

        if ($wantsJson) {
            // Count remaining blocks for the parent module so the front-end can
            // update the module card grid live.
            $remainingBlocks = $this->materialAuthoring
                ->visibleBlockCount((int) $moduleId);

            return response()->json([
                'success' => true,
                'message' => 'Block deleted.',
                'block_id' => $blockId,
                'module_id' => $moduleId,
                'remaining_blocks' => $remainingBlocks,
                'assessment_id' => $block->assessment_id
                    ? (int) $block->assessment_id
                    : null,
                'block_type' => $block->block_type,
            ]);
        }

        return redirect()->route('trainer.courses.materials', $courseId)
            ->with('success', 'Block deleted successfully.');
    }

    public function replaceUploadedBlock(
        Request $request,
        string $courseId,
        int $blockId
    ): JsonResponse|RedirectResponse {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With'))
                === 'xmlhttprequest';

        $fail = function (
            string $message,
            int $status = 422
        ) use ($courseId, $wantsJson): JsonResponse|RedirectResponse {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $status);
            }

            return redirect()
                ->route('trainer.courses.materials', $courseId)
                ->withInput()
                ->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $block = DB::table('training_content_block')
            ->join(
                'training_module',
                'training_content_block.module_id',
                '=',
                'training_module.module_id'
            )
            ->join(
                'learning_material',
                'training_content_block.material_id',
                '=',
                'learning_material.material_id'
            )
            ->where('training_content_block.block_id', $blockId)
            ->where('training_module.course_id', $courseId)
            ->where('learning_material.course_id', $courseId)
            ->whereIn('training_content_block.block_type', ['material', 'media'])
            ->select(
                'training_content_block.block_id',
                'training_content_block.block_type',
                'training_content_block.module_id',
                'training_content_block.material_id',
                'training_module.module_type',
                'learning_material.file_path',
                'learning_material.original_file_name'
            )
            ->first();

        if (! $block) {
            return $fail('File block not found.', 404);
        }

        $allowed = $this->allowedUploadedBlockExtensions($block->block_type);
        $validator = Validator::make($request->all(), [
            'material_file' => [
                'required',
                'file',
                'mimes:'.implode(',', $allowed),
                'max:204800',
            ],
        ], [
            'material_file.mimes' => 'The selected file type is not supported.',
            'material_file.max' => 'The file is larger than the 200 MB limit.',
        ]);
        if ($validator->fails()) {
            return $fail($validator->errors()->first(), 422);
        }
        $validated = $validator->validated();
        $file = $validated['material_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowed, true)) {
            return $fail(
                'This file type is not allowed for a '
                .$block->block_type
                .' block.'
            );
        }

        $folder = $this->uploadedBlockStorageFolder(
            $block->block_type,
            $courseId
        );
        $newPath = $file->storeAs(
            $folder,
            Str::uuid().'.'.$extension,
            'public'
        );
        $oldPath = $block->file_path;

        try {
            DB::transaction(function () use (
                $block,
                $courseId,
                $file,
                $newPath
            ) {
                $currentBlock = DB::table('training_content_block')
                    ->join(
                        'training_module',
                        'training_content_block.module_id',
                        '=',
                        'training_module.module_id'
                    )
                    ->where('training_content_block.block_id', $block->block_id)
                    ->where('training_module.course_id', $courseId)
                    ->lockForUpdate()
                    ->select('training_content_block.*')
                    ->first();

                if (
                    ! $currentBlock
                    || (int) $currentBlock->material_id
                        !== (int) $block->material_id
                    || $currentBlock->block_type !== $block->block_type
                ) {
                    throw new \UnexpectedValueException(
                        'file-block-reference-conflict'
                    );
                }

                DB::table('learning_material')
                    ->where('material_id', $block->material_id)
                    ->where('course_id', $courseId)
                    ->update([
                        'original_file_name' => $file->getClientOriginalName(),
                        'file_path' => $newPath,
                        'material_type' => $block->block_type === 'media'
                            ? 'Media'
                            : $block->module_type,
                        'uploaded_at' => now(),
                    ]);

                DB::table('training_content_block')
                    ->where('block_id', $block->block_id)
                    ->update(['updated_at' => now()]);
            });
        } catch (\Throwable $exception) {
            try {
                if (Storage::disk('public')->exists($newPath)) {
                    Storage::disk('public')->delete($newPath);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up a replacement authoring file.', [
                    'course_id' => $courseId,
                    'block_id' => $blockId,
                    'path' => $newPath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }

            if (
                $exception instanceof \UnexpectedValueException
                && $exception->getMessage() === 'file-block-reference-conflict'
            ) {
                return $fail(
                    'This file block changed after the page loaded. Refresh before replacing the file.',
                    409
                );
            }

            Log::warning('Authoring file replacement failed.', [
                'course_id' => $courseId,
                'block_id' => $blockId,
                'error' => $exception->getMessage(),
            ]);

            return $fail('The file could not be replaced. The original file is unchanged.', 500);
        }

        $safeOldPath = $this->eligibleLocalAuthoringAssetPath(
            $oldPath,
            $courseId,
            ['training-materials', 'training-media']
        );

        if ($safeOldPath && $safeOldPath !== $newPath) {
            try {
                $pathStillReferenced = DB::table('learning_material')
                    ->where('file_path', $safeOldPath)
                    ->exists();

                if (
                    ! $pathStillReferenced
                    && Storage::disk('public')->exists($safeOldPath)
                ) {
                    Storage::disk('public')->delete($safeOldPath);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up a replaced authoring file.', [
                    'course_id' => $courseId,
                    'block_id' => $blockId,
                    'path' => $safeOldPath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }

        TrainerActivityLogger::log(
            session('user_id'),
            'material.replace',
            $file->getClientOriginalName(),
            $courseId,
            'material',
            $block->material_id
        );

        if ($wantsJson) {
            $updatedBlock = $this->fetchBlockForRender($blockId);
            $blockHtml = view('trainer.training.partials.block', [
                'block' => $updatedBlock,
                'blockIndex' => 9000 + $blockId,
                'saveFormId' => 'saveModuleForm'.$block->module_id,
                'courseId' => $courseId,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'File replaced successfully.',
                'block_id' => $blockId,
                'module_id' => $block->module_id,
                'block_html' => $blockHtml,
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', 'File replaced successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function allowedUploadedBlockExtensions(string $blockType): array
    {
        if ($blockType === 'media') {
            return [
                'jpg', 'jpeg', 'png', 'webp', 'gif',
                'mp4', 'mov', 'avi', 'webm',
                'mp3', 'wav', 'm4a', 'ogg',
            ];
        }

        return [
            'pdf', 'doc', 'docx', 'ppt', 'pptx',
            'xls', 'xlsx', 'txt', 'zip', 'rar', 'csv',
        ];
    }

    private function uploadedBlockStorageFolder(
        string $blockType,
        string $courseId
    ): string {
        return ($blockType === 'media'
            ? 'training-media/'
            : 'training-materials/')
            .$courseId;
    }

    private function normalizeYoutubeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $videoId = $query['v'] ?? null;
            } elseif (str_starts_with($path, 'embed/')) {
                $videoId = explode('/', substr($path, strlen('embed/')))[0] ?? null;
            }
        }

        if (! is_string($videoId) || preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId) !== 1) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$videoId;
    }

    public function storeYoutubeBlock(Request $request, string $courseId, int $moduleId)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        $fail = function (string $message, int $status = 422) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return back()->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $module = DB::table('training_module')
            ->where('module_id', $moduleId)
            ->where('course_id', $courseId)
            ->first();

        if (! $module) {
            return $fail('Module not found.', 404);
        }

        $validated = $request->validate([
            'youtube_title' => ['required', 'string', 'max:255'],
            'youtube_url' => ['required', 'string', 'max:500'],
            'youtube_description' => ['nullable', 'string', 'max:1000'],
            'block_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $embedUrl = $this->normalizeYoutubeUrl($validated['youtube_url']);

        if (! $embedUrl) {
            return $fail('Enter a valid YouTube watch, short, or embed URL.');
        }

        $nextOrder = $this->materialAuthoring->resolveAppendedBlockOrder(
            $moduleId,
            isset($validated['block_order'])
                ? (int) $validated['block_order']
                : null
        );

        $newBlockId = DB::table('training_content_block')->insertGetId([
            'module_id' => $moduleId,
            'block_type' => 'youtube',
            'block_title' => $validated['youtube_title'],
            'block_content' => $validated['youtube_description'] ?? null,
            'external_url' => $embedUrl,
            'material_id' => null,
            'assessment_id' => null,
            'block_order' => $nextOrder,
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TrainerActivityLogger::log(
            session('user_id'),
            'block.create',
            $validated['youtube_title'].' (YouTube)',
            $courseId,
            'block',
            $newBlockId
        );

        if ($wantsJson) {
            $newBlock = $this->fetchBlockForRender($newBlockId);

            $blockHtml = view('trainer.training.partials.block', [
                'block' => $newBlock,
                'blockIndex' => 9000 + $newBlockId,
                'saveFormId' => 'saveModuleForm'.$moduleId,
                'courseId' => $courseId,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'YouTube block added.',
                'block_id' => $newBlockId,
                'block_html' => $blockHtml,
                'module_id' => $moduleId,
                'block_count' => $this->materialAuthoring->visibleBlockCount($moduleId),
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', 'YouTube video block added successfully.');
    }

    public function reorderModules(Request $request, string $courseId): JsonResponse
    {
        if (session('user_role') !== 'trainer') {
            return response()->json([
                'success' => false,
                'message' => 'Please login as trainer first.',
            ], 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this training.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The module order is invalid. Refresh the page before retrying.',
            ], 422);
        }

        $submittedIds = array_map('intval', $validator->validated()['module_ids']);
        $currentOrder = DB::table('training_module')
            ->where('course_id', $courseId)
            ->orderBy('module_order')
            ->orderBy('module_id')
            ->pluck('module_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $courseIds = DB::table('training_module')
            ->where('course_id', $courseId)
            ->orderBy('module_id')
            ->pluck('module_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $sortedSubmittedIds = $submittedIds;
        sort($sortedSubmittedIds);

        if ($sortedSubmittedIds !== $courseIds) {
            return response()->json([
                'success' => false,
                'message' => 'The module order is out of date. Refresh the page before retrying.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($courseId, $submittedIds): void {
                $lockedIds = DB::table('training_module')
                    ->where('course_id', $courseId)
                    ->lockForUpdate()
                    ->pluck('module_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();
                $expectedIds = $submittedIds;
                sort($expectedIds);

                if ($lockedIds !== $expectedIds) {
                    throw new \UnexpectedValueException('module-order-conflict');
                }

                foreach ($submittedIds as $index => $moduleId) {
                    DB::table('training_module')
                        ->where('course_id', $courseId)
                        ->where('module_id', $moduleId)
                        ->update([
                            'module_order' => $index + 1,
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (\UnexpectedValueException $exception) {
            if ($exception->getMessage() === 'module-order-conflict') {
                return response()->json([
                    'success' => false,
                    'message' => 'The module order is out of date. Refresh the page before retrying.',
                ], 409);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Module reorder failed.', [
                'course_id' => $courseId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Module order could not be saved. Please try again.',
            ], 500);
        }

        if ($submittedIds !== $currentOrder) {
            TrainerActivityLogger::log(
                session('user_id'),
                'module.reorder',
                count($submittedIds).' modules reordered',
                $courseId,
                'course',
                $courseId
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Module order saved.',
            'module_ids' => $submittedIds,
        ]);
    }

    public function deleteModule(Request $request, string $courseId, int $moduleId)
    {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With'))
                === 'xmlhttprequest';

        $fail = function (
            string $message,
            int $status = 422
        ) use ($courseId, $wantsJson): JsonResponse|RedirectResponse {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $status);
            }

            return redirect()
                ->route('trainer.courses.materials', $courseId)
                ->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            if ($wantsJson) {
                return $fail('Please login as trainer first.', 401);
            }

            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $module = DB::table('training_module')
            ->where('module_id', $moduleId)
            ->where('course_id', $courseId)
            ->first();

        if (! $module) {
            return $fail('Module not found.', 404);
        }

        $missingModuleMarker = 'delete-module-missing-after-lock';
        $transactionBlockCount = 0;
        $deletedMaterialPaths = [];

        try {
            $deletedModule = DB::transaction(function () use (
                $courseId,
                $moduleId,
                $missingModuleMarker,
                &$transactionBlockCount,
                &$deletedMaterialPaths
            ) {
                $lockedModule = DB::table('training_module')
                    ->where('module_id', $moduleId)
                    ->where('course_id', $courseId)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedModule) {
                    throw new \UnexpectedValueException($missingModuleMarker);
                }

                $transactionBlockCount = DB::table('training_content_block')
                    ->where('module_id', $moduleId)
                    ->count();

                $materialIds = Schema::hasColumn('training_content_block', 'material_id')
                    ? DB::table('training_content_block')
                        ->where('module_id', $moduleId)
                        ->whereNotNull('material_id')
                        ->pluck('material_id')
                        ->unique()
                        ->values()
                    : collect();

                DB::table('training_content_block')
                    ->where('module_id', $moduleId)
                    ->delete();

                if (Schema::hasTable('learning_material')) {
                    foreach ($materialIds as $materialId) {
                        $stillUsed = DB::table('training_content_block')
                            ->where('material_id', $materialId)
                            ->exists();

                        if ($stillUsed) {
                            continue;
                        }

                        $material = DB::table('learning_material')
                            ->where('material_id', $materialId)
                            ->where('course_id', $courseId)
                            ->first();

                        if (! $material) {
                            continue;
                        }

                        $deletedMaterialPaths[] = $material->file_path;
                        DB::table('learning_material')
                            ->where('material_id', $materialId)
                            ->where('course_id', $courseId)
                            ->delete();
                    }
                }

                // Detach any assessment that was linked back to this module so the
                // assessment row survives the module deletion.
                DB::table('assessment')
                    ->where('course_id', $courseId)
                    ->where('module_id', $moduleId)
                    ->update([
                        'module_id' => null,
                        'updated_at' => now(),
                    ]);

                $deletedRows = DB::table('training_module')
                    ->where('module_id', $moduleId)
                    ->where('course_id', $courseId)
                    ->delete();

                if ($deletedRows !== 1) {
                    throw new \UnexpectedValueException($missingModuleMarker);
                }

                return [
                    'module_title' => $lockedModule->module_title,
                    'cover_image' => $lockedModule->cover_image,
                    'block_count' => $transactionBlockCount,
                ];
            });
        } catch (\Throwable $e) {
            $isMissingModule =
                $e instanceof \UnexpectedValueException
                && $e->getMessage() === $missingModuleMarker;

            if ($isMissingModule) {
                return $fail('Module not found.', 404);
            }

            Log::warning('Module deletion failed.', [
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'error' => $e->getMessage(),
            ]);

            return $fail('The module could not be deleted. Please try again.', 500);
        }

        $moduleTitle = $deletedModule['module_title'];
        $coverPath = $deletedModule['cover_image'];
        $cleanupCoverPath = $this->eligibleLocalModuleCoverCleanupPath($coverPath);

        if ($cleanupCoverPath !== null) {
            try {
                $coverStillReferenced = DB::table('training_module')
                    ->where('cover_image', $cleanupCoverPath)
                    ->exists();

                if (! $coverStillReferenced) {
                    $publicDisk = Storage::disk('public');

                    if (
                        $publicDisk->exists($cleanupCoverPath)
                        && ! $publicDisk->delete($cleanupCoverPath)
                    ) {
                        throw new \RuntimeException(
                            'The public disk reported that the module cover was not deleted.'
                        );
                    }
                }
            } catch (\Throwable $cleanupException) {
                try {
                    Log::warning('Unable to clean up module cover after module deletion.', [
                        'course_id' => $courseId,
                        'module_id' => $moduleId,
                        'path' => $cleanupCoverPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                } catch (\Throwable) {
                    // A warning failure must not mask the committed module deletion.
                }
            }
        }

        foreach ($deletedMaterialPaths as $materialPath) {
            $safeMaterialPath = $this->eligibleLocalAuthoringAssetPath(
                $materialPath,
                $courseId,
                ['training-materials', 'training-media']
            );

            if (! $safeMaterialPath) {
                continue;
            }

            try {
                if (Storage::disk('public')->exists($safeMaterialPath)) {
                    Storage::disk('public')->delete($safeMaterialPath);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up module material after deletion.', [
                    'course_id' => $courseId,
                    'module_id' => $moduleId,
                    'path' => $safeMaterialPath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }

        TrainerActivityLogger::log(
            session('user_id'),
            'module.delete',
            $moduleTitle,
            $courseId,
            'module',
            $moduleId
        );

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => 'Module deleted successfully.',
                'module_id' => $moduleId,
                'deleted_blocks' => $deletedModule['block_count'],
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', 'Module deleted successfully.');
    }

    private function eligibleLocalModuleCoverCleanupPath($coverPath): ?string
    {
        if (! is_string($coverPath) || $coverPath === '') {
            return null;
        }

        if (Str::startsWith($coverPath, ['http://', 'https://'])) {
            return null;
        }

        if (
            Str::startsWith($coverPath, ['/', '\\'])
            || str_contains($coverPath, '\\')
            || preg_match('/^[A-Za-z]:/', $coverPath) === 1
            || preg_match('/\p{C}/u', $coverPath) !== 0
            || ! str_starts_with($coverPath, 'module-covers/')
        ) {
            return null;
        }

        $segments = explode('/', $coverPath);
        $remainingPath = substr($coverPath, strlen('module-covers/'));

        if (
            $remainingPath === ''
            || in_array('', $segments, true)
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
        ) {
            return null;
        }

        return $coverPath;
    }

    /**
     * @param  array<int, string>  $allowedRoots
     */
    private function eligibleLocalAuthoringAssetPath(
        mixed $path,
        string $courseId,
        array $allowedRoots
    ): ?string {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (
            Str::startsWith($path, ['http://', 'https://', '/', '\\'])
            || str_contains($path, '\\')
            || preg_match('/^[A-Za-z]:/', $path) === 1
            || preg_match('/\p{C}/u', $path) !== 0
        ) {
            return null;
        }

        $matchesAllowedRoot = false;

        foreach ($allowedRoots as $root) {
            if (str_starts_with($path, $root.'/'.$courseId.'/')) {
                $matchesAllowedRoot = true;
                break;
            }
        }

        if (! $matchesAllowedRoot) {
            return null;
        }

        $segments = explode('/', $path);

        if (
            in_array('', $segments, true)
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
        ) {
            return null;
        }

        return $path;
    }

    public function downloadMaterial(string $courseId, int $materialId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()->route('trainer.courses')->with('error', 'You are not assigned to this training.');
        }

        $material = DB::table('learning_material')
            ->where('material_id', $materialId)
            ->where('course_id', $courseId)
            ->first();

        if (! $material) {
            return redirect()
                ->route('trainer.courses.materials', $courseId)
                ->with('error', 'Material not found.');
        }

        if (! $material->file_path) {
            return redirect()
                ->route('trainer.courses.materials', $courseId)
                ->with('error', 'File path is missing.');
        }

        $safePath = $this->eligibleLocalAuthoringAssetPath(
            $material->file_path,
            $courseId,
            ['training-materials', 'training-media']
        );

        if (! $safePath || ! Storage::disk('public')->exists($safePath)) {
            return redirect()
                ->route('trainer.courses.materials', $courseId)
                ->with('error', 'The file is missing from storage.');
        }

        $downloadName = $material->original_file_name ?: basename($material->file_path);

        return Storage::disk('public')->download($safePath, $downloadName);
    }

    public function updateAssessment(
        SaveAssessmentRequest $request,
        string $courseId,
        int $assessmentId
    ): JsonResponse|RedirectResponse {
        $wantsJson = $this->authoringRequestWantsJson($request);

        if (session('user_role') !== 'trainer') {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login as trainer first.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'You are not assigned to this training.',
                403
            );
        }

        $assessment = DB::table('assessment')
            ->where('assessment_id', $assessmentId)
            ->where('course_id', $courseId)
            ->first();

        if (! $assessment) {
            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'Assessment not found.',
                404
            );
        }

        $validated = $request->validated();

        $assessmentIsEmbedded = DB::table('training_content_block')
            ->where('assessment_id', $assessmentId)
            ->exists();

        if (
            $assessmentIsEmbedded
            && strtolower((string) $assessment->type) !== $validated['type']
        ) {
            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'Remove this assessment from its module before changing its type.',
                422
            );
        }

        if ($validated['type'] === 'quiz') {
            $submittedQuestions = $validated['questions'] ?? [];
            $quizError = count($submittedQuestions) > 0
                ? $this->quizValidationMessage($submittedQuestions)
                : null;
            if ($quizError) {
                return $this->authoringFailureResponse(
                    $request,
                    $courseId,
                    $quizError
                );
            }

            $validated['total_marks'] = collect(
                $validated['questions'] ?? []
            )->sum(static fn (array $question): float => (float) (
                $question['marks'] ?? 0
            ));
        }

        $quizHasLearnerAttempts = strtolower((string) $assessment->type) === 'quiz'
            && $this->assessmentHasLearnerRecords($assessmentId);

        if (
            $quizHasLearnerAttempts
            && (
                $validated['type'] !== 'quiz'
                || $this->attemptedQuizScoringWasChanged($request, $assessment)
                || (
                    $request->attributes->get('assessment_questions_supplied', false)
                    && ! $this->submittedQuizStructureMatchesExisting(
                        $request,
                        $assessmentId,
                        $validated['questions'] ?? []
                    )
                )
            )
        ) {
            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'This quiz already has learner attempts. Its questions, answers, images, order, and marks can no longer be changed; basic information and availability can still be updated.',
                409
            );
        }

        $updateData = [
            'title' => $validated['title'],
            'type' => $validated['type'],
            'instruction_text' => $validated['instruction_text'] ?? null,
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'accepted_format' => $validated['type'] === 'submission'
                ? ($validated['accepted_format'] ?? null)
                : null,
            'time_limit_minutes' => $validated['type'] === 'quiz'
                ? ($quizHasLearnerAttempts
                    ? ($assessment->time_limit_minutes ?? null)
                    : ($validated['time_limit_minutes'] ?? null))
                : null,
            'max_attempts' => $validated['type'] === 'quiz'
                ? ($quizHasLearnerAttempts
                    ? ($assessment->max_attempts ?? null)
                    : ($validated['max_attempts'] ?? null))
                : null,
            'total_marks' => $validated['type'] === 'quiz'
                ? ($quizHasLearnerAttempts
                    ? ($assessment->total_marks ?? null)
                    : ($validated['total_marks'] ?? null))
                : null,
            'weight_percent' => $validated['type'] === 'quiz'
                ? ($quizHasLearnerAttempts
                    ? ($assessment->weight_percent ?? null)
                    : ($validated['weight_percent'] ?? null))
                : null,
            'updated_at' => now(),
        ];

        $existingQuestionsById = DB::table('assessment_question')
            ->where('assessment_id', $assessmentId)
            ->get()
            ->keyBy(static fn ($question): string => (string) $question->question_id);
        $oldQuestionImagePaths = $existingQuestionsById
            ->where('assessment_id', $assessmentId)
            ->pluck('question_image_path')
            ->filter()
            ->map(static fn ($path): string => (string) $path)
            ->values()
            ->all();
        $allowedExistingQuestionImagePaths = array_fill_keys(
            $oldQuestionImagePaths,
            true
        );
        $newQuestionImagePaths = [];
        $retainedQuestionImagePaths = [];

        DB::beginTransaction();

        try {
            DB::table('assessment')
                ->where('assessment_id', $assessmentId)
                ->where('course_id', $courseId)
                ->update($updateData);

            DB::table('training_content_block')
                ->where('assessment_id', $assessmentId)
                ->update([
                    'block_title' => $validated['title'],
                    'block_content' => $validated['instruction_text'] ?? null,
                    'block_type' => $validated['type'] === 'quiz' ? 'quiz' : 'submission',
                    'updated_at' => now(),
                ]);

            if (! $quizHasLearnerAttempts) {
                DB::table('assessment_question')
                    ->where('assessment_id', $assessmentId)
                    ->delete();
            }

            if (
                ! $quizHasLearnerAttempts
                && $validated['type'] === 'quiz'
                && ! empty($validated['questions'])
            ) {
                foreach ($validated['questions'] as $index => $question) {
                    $questionType = $question['question_type'] ?? 'mcq';
                    $correctAnswer = $question['correct_answer'] ?? null;

                    if ($questionType === 'checkbox') {
                        $correctAnswer = $question['correct_answers_json'] ?? '[]';
                    }
                    if ($questionType === 'essay') {
                        $correctAnswer = null;
                    }

                    $questionImagePath = $question['existing_question_image_path'] ?? null;

                    $existingQuestion = isset($question['question_id'])
                        ? $existingQuestionsById->get((string) $question['question_id'])
                        : null;

                    if (isset($question['question_id']) && ! $existingQuestion) {
                        throw new \UnexpectedValueException(
                            'question-reference-conflict'
                        );
                    }

                    if ($existingQuestion) {
                        $questionImagePath = $existingQuestion->question_image_path
                            ?? null;
                    }

                    if (! empty($question['remove_question_image'])) {
                        $questionImagePath = null;
                    }

                    if (
                        $questionImagePath
                        && ! isset(
                            $allowedExistingQuestionImagePaths[
                                $questionImagePath
                            ]
                        )
                    ) {
                        throw new \UnexpectedValueException(
                            'question-image-reference-conflict'
                        );
                    }

                    if ($request->hasFile("questions.$index.question_image")) {
                        $questionImagePath = $request
                            ->file("questions.$index.question_image")
                            ->store('assessment-question-images/'.$courseId, 'public');
                        $newQuestionImagePaths[] = $questionImagePath;
                    }

                    if ($questionImagePath) {
                        $retainedQuestionImagePaths[] = $questionImagePath;
                    }

                    $questionId = $this->nextManualId('assessment_question', 'question_id');

                    $questionInsert = [
                        'question_id' => $questionId,
                        'assessment_id' => $assessmentId,
                        'question_text' => $question['question_text'],
                        'question_image_path' => $questionImagePath,
                        'question_order' => $index + 1,
                        'question_type' => $questionType,
                        'options_json' => $question['options_json'] ?? null,
                        'correct_answer' => $correctAnswer,
                        'marks' => $question['marks'] ?? 0,
                        'grading_rubric' => $question['grading_rubric'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    DB::table('assessment_question')->insert($questionInsert);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->cleanupQuestionImagePaths(
                $newQuestionImagePaths,
                $courseId,
                false
            );

            if (
                $e instanceof \UnexpectedValueException
                && $e->getMessage() === 'question-image-reference-conflict'
            ) {
                return $this->authoringFailureResponse(
                    $request,
                    $courseId,
                    'A question image reference is out of date. Refresh the page before retrying.',
                    409
                );
            }

            if (
                $e instanceof \UnexpectedValueException
                && $e->getMessage() === 'question-reference-conflict'
            ) {
                return $this->authoringFailureResponse(
                    $request,
                    $courseId,
                    'A question reference is out of date. Refresh the page before retrying.',
                    409
                );
            }

            Log::warning('Assessment update failed.', [
                'course_id' => $courseId,
                'assessment_id' => $assessmentId,
                'error' => $e->getMessage(),
            ]);

            return $this->authoringFailureResponse(
                $request,
                $courseId,
                'The assessment could not be updated. Your entered values are still available; please try again.',
                500
            );
        }

        $obsoleteQuestionImagePaths = $quizHasLearnerAttempts
            ? []
            : array_values(array_diff(
                $oldQuestionImagePaths,
                $retainedQuestionImagePaths
            ));
        $this->cleanupQuestionImagePaths(
            $obsoleteQuestionImagePaths,
            $courseId,
            true
        );

        TrainerActivityLogger::log(
            session('user_id'),
            $validated['type'] === 'quiz' ? 'quiz.update' : 'submission_task.update',
            $validated['title'],
            $courseId,
            'assessment',
            $assessmentId
        );

        $message = 'Assessment updated successfully.';

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'assessment' => $this->assessmentAuthoringPayload($assessmentId),
            ]);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->with('success', $message);
    }

    private function quizValidationMessage(array $questions): ?string
    {
        if (count($questions) === 0) {
            return 'Please add at least one quiz question.';
        }

        foreach ($questions as $index => $question) {
            $questionNumber = $index + 1;
            $questionType = $question['question_type'] ?? 'mcq';
            $questionText = trim((string) ($question['question_text'] ?? ''));
            $marks = $question['marks'] ?? 0;

            if ($questionText === '') {
                return 'Please fill Question '.$questionNumber.' text.';
            }

            if (! is_numeric($marks)) {
                return 'Question '.$questionNumber.' marks must be a valid number.';
            }

            if ((float) $marks < 0) {
                return 'Question '.$questionNumber.' marks cannot be negative.';
            }

            if (in_array($questionType, ['mcq', 'checkbox'], true)) {
                $options = [];

                if (! empty($question['options_json'])) {
                    $decodedOptions = json_decode($question['options_json'], true);
                    $options = is_array($decodedOptions)
                        ? array_values(array_map('trim', $decodedOptions))
                        : [];
                }

                if (count($options) < 2 || in_array('', $options, true)) {
                    return 'Please fill every choice for Question '.$questionNumber.'.';
                }

                $uniqueOptions = array_unique(array_map(
                    static fn (string $option): string => mb_strtolower($option),
                    $options
                ));

                if (count($uniqueOptions) < 2) {
                    return 'Question '.$questionNumber.' needs at least 2 different choices.';
                }

                if ($questionType === 'mcq') {
                    $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));

                    if (
                        $correctAnswer === ''
                        || ! in_array($correctAnswer, $options, true)
                    ) {
                        return 'Please choose the correct answer for Question '.$questionNumber.'.';
                    }
                }

                if ($questionType === 'checkbox') {
                    $correctAnswers = [];

                    if (! empty($question['correct_answers_json'])) {
                        $decodedCorrect = json_decode($question['correct_answers_json'], true);
                        $correctAnswers = is_array($decodedCorrect) ? array_values(array_filter(array_map('trim', $decodedCorrect))) : [];
                    }

                    if (
                        count($correctAnswers) === 0
                        || array_diff($correctAnswers, $options) !== []
                    ) {
                        return 'Please choose at least one correct answer for Question '.$questionNumber.'.';
                    }
                }
            }

            if ($questionType === 'true_false') {
                $options = json_decode((string) ($question['options_json'] ?? ''), true);

                if ($options !== ['True', 'False']) {
                    return 'Question '.$questionNumber.' must use the fixed True and False choices.';
                }

                $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));

                if (! in_array($correctAnswer, ['True', 'False'], true)) {
                    return 'Please choose True or False as the correct answer for Question '.$questionNumber.'.';
                }
            }

            if ($questionType === 'short_answer') {
                $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));

                if ($correctAnswer === '') {
                    return 'Please fill the keyword / expected answer for Question '.$questionNumber.'.';
                }
            }

            if ($questionType === 'essay') {
                $rubric = trim((string) ($question['grading_rubric'] ?? ''));

                if ($rubric === '') {
                    return 'Please fill the essay rubric for Question '.$questionNumber.'.';
                }
            }
        }

        return null;
    }

    private function assessmentHasLearnerRecords(int $assessmentId): bool
    {
        return Schema::hasTable('assessment_submission')
            && DB::table('assessment_submission')
                ->where('assessment_id', $assessmentId)
                ->exists();
    }

    private function attemptedQuizScoringWasChanged(
        Request $request,
        object $assessment
    ): bool {
        $suppliedFields = $request->attributes->get(
            'assessment_scoring_fields_supplied',
            []
        );

        foreach ($suppliedFields as $field) {
            if (! property_exists($assessment, $field)) {
                continue;
            }

            $incoming = $request->input($field);
            $existing = $assessment->{$field};
            $incoming = $incoming === '' || $incoming === null
                ? null
                : (float) $incoming;
            $existing = $existing === '' || $existing === null
                ? null
                : (float) $existing;

            if ($incoming === null || $existing === null) {
                if ($incoming !== $existing) {
                    return true;
                }

                continue;
            }

            if (abs($incoming - $existing) > 0.00001) {
                return true;
            }
        }

        return false;
    }

    /**
     * Historical quiz attempts in the current replicated contract are stored at
     * assessment level. Preserve their question/scoring context by permitting
     * metadata-only edits once an attempt exists.
     *
     * @param  array<int, array<string, mixed>>  $submittedQuestions
     */
    private function submittedQuizStructureMatchesExisting(
        Request $request,
        int $assessmentId,
        array $submittedQuestions
    ): bool {
        foreach (array_keys($submittedQuestions) as $index) {
            if ($request->hasFile("questions.$index.question_image")) {
                return false;
            }
        }

        $existingRows = DB::table('assessment_question')
            ->where('assessment_id', $assessmentId)
            ->orderBy('question_order')
            ->get();
        $existingById = $existingRows->keyBy(
            static fn ($question): string => (string) $question->question_id
        );
        $existing = $existingRows
            ->map(fn ($question): array => $this->quizQuestionStructure([
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'options_json' => $question->options_json,
                'correct_answer' => $question->correct_answer,
                'correct_answers_json' => $question->question_type === 'checkbox'
                    ? $question->correct_answer
                    : null,
                'marks' => $question->marks,
                'grading_rubric' => $question->grading_rubric,
                'existing_question_image_path' => $question->question_image_path,
            ]))
            ->values()
            ->all();

        $submitted = collect($submittedQuestions)
            ->map(function (array $question) use ($existingById): array {
                $existingQuestion = isset($question['question_id'])
                    ? $existingById->get((string) $question['question_id'])
                    : null;
                $question['existing_question_image_path'] = ! empty(
                    $question['remove_question_image']
                )
                    ? null
                    : ($existingQuestion->question_image_path ?? null);

                return $this->quizQuestionStructure($question);
            })
            ->values()
            ->all();

        return $existing === $submitted;
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    private function quizQuestionStructure(array $question): array
    {
        $type = (string) ($question['question_type'] ?? 'mcq');
        $options = json_decode((string) ($question['options_json'] ?? ''), true);
        $options = is_array($options)
            ? array_values(array_map(static fn ($value): string => trim((string) $value), $options))
            : [];
        $correct = $type === 'checkbox'
            ? json_decode((string) ($question['correct_answers_json'] ?? '[]'), true)
            : trim((string) ($question['correct_answer'] ?? ''));

        if ($type === 'checkbox') {
            $correct = is_array($correct)
                ? array_values(array_map(static fn ($value): string => trim((string) $value), $correct))
                : [];
        }

        return [
            'type' => $type,
            'text' => trim((string) ($question['question_text'] ?? '')),
            'options' => $options,
            'correct' => $correct,
            'marks' => number_format((float) ($question['marks'] ?? 0), 4, '.', ''),
            'rubric' => trim((string) ($question['grading_rubric'] ?? '')),
            'image' => (string) ($question['existing_question_image_path'] ?? ''),
        ];
    }

    private function authoringRequestWantsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With'))
                === 'xmlhttprequest';
    }

    private function authoringFailureResponse(
        Request $request,
        string $courseId,
        string $message,
        int $status = 422
    ): JsonResponse|RedirectResponse {
        if ($this->authoringRequestWantsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()
            ->route('trainer.courses.materials', $courseId)
            ->withInput()
            ->with('error', $message);
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function cleanupQuestionImagePaths(
        array $paths,
        string $courseId,
        bool $checkDatabaseReferences
    ): void {
        foreach (array_values(array_unique($paths)) as $path) {
            $safePath = $this->eligibleLocalAuthoringAssetPath(
                $path,
                $courseId,
                ['assessment-question-images']
            );

            if (! $safePath) {
                continue;
            }

            try {
                if (
                    $checkDatabaseReferences
                    && DB::table('assessment_question')
                        ->where('question_image_path', $safePath)
                        ->exists()
                ) {
                    continue;
                }

                if (Storage::disk('public')->exists($safePath)) {
                    Storage::disk('public')->delete($safePath);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Unable to clean up an assessment question image.', [
                    'course_id' => $courseId,
                    'path' => $safePath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Return the saved assessment contract used to synchronize the open browser
     * workspace after an asynchronous create or update.
     *
     * @return array<string, mixed>
     */
    private function assessmentAuthoringPayload(int $assessmentId): array
    {
        $assessment = DB::table('assessment')
            ->where('assessment_id', $assessmentId)
            ->first();

        if (! $assessment) {
            return [];
        }

        $questions = DB::table('assessment_question')
            ->where('assessment_id', $assessmentId)
            ->orderBy('question_order')
            ->get()
            ->map(function ($question) use ($assessment): array {
                $options = json_decode(
                    (string) ($question->options_json ?? ''),
                    true
                );
                $options = is_array($options) ? array_values($options) : [];
                $correctAnswers = [];

                if ($question->question_type === 'checkbox') {
                    $decoded = json_decode(
                        (string) ($question->correct_answer ?? ''),
                        true
                    );
                    $correctAnswers = is_array($decoded)
                        ? array_values($decoded)
                        : [];
                } elseif (! empty($question->correct_answer)) {
                    $correctAnswers = [$question->correct_answer];
                }

                return [
                    'question_id' => (int) $question->question_id,
                    'question_order' => (int) $question->question_order,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'options' => $options,
                    'correct_answer' => $question->correct_answer,
                    'correct_answers' => $correctAnswers,
                    'marks' => $question->marks,
                    'grading_rubric' => $question->grading_rubric,
                    'question_image_url' => $question->question_image_path
                        ? route('trainer.courses.assessments.questions.image', [
                            $assessment->course_id,
                            $assessment->assessment_id,
                            $question->question_id,
                        ])
                        : null,
                ];
            })
            ->values()
            ->all();

        return [
            'assessment_id' => (int) $assessment->assessment_id,
            'title' => $assessment->title,
            'type' => strtolower((string) $assessment->type),
            'instruction_text' => $assessment->instruction_text,
            'available_from' => $assessment->available_from,
            'available_until' => $assessment->available_until,
            'available_from_input' => $assessment->available_from
                ? Carbon::parse($assessment->available_from)
                    ->format('Y-m-d\TH:i')
                : '',
            'available_until_input' => $assessment->available_until
                ? Carbon::parse($assessment->available_until)
                    ->format('Y-m-d\TH:i')
                : '',
            'accepted_format' => $assessment->accepted_format,
            'time_limit_minutes' => $assessment->time_limit_minutes,
            'max_attempts' => $assessment->max_attempts ?? null,
            'weight_percent' => $assessment->weight_percent ?? null,
            'total_marks' => $assessment->total_marks,
            'status' => $assessment->status ?? null,
            'has_learner_records' => $this->assessmentHasLearnerRecords(
                (int) $assessment->assessment_id
            ),
            'load_url' => route('trainer.courses.assessments.authoring', [
                $assessment->course_id,
                $assessment->assessment_id,
            ]),
            'update_url' => route('trainer.courses.assessments.update', [
                $assessment->course_id,
                $assessment->assessment_id,
            ]),
            'questions' => $questions,
        ];
    }

    private function fetchBlockForRender(int $blockId): ?object
    {
        $block = DB::table('training_content_block')
            ->leftJoin('learning_material', 'training_content_block.material_id', '=', 'learning_material.material_id')
            ->leftJoin('assessment', 'training_content_block.assessment_id', '=', 'assessment.assessment_id')
            ->where('training_content_block.block_id', $blockId)
            ->select(
                'training_content_block.*',
                'learning_material.original_file_name',
                'learning_material.file_path',
                'learning_material.course_id as material_course_id',
                'learning_material.material_type',
                'learning_material.ai_generated_title',
                'learning_material.ai_synopsis',
                'assessment.title as assessment_title',
                'assessment.type as assessment_type',
                'assessment.available_from',
                'assessment.available_until',
                'assessment.accepted_format',
                'assessment.total_marks'
            )
            ->first();

        if ($block) {
            if ($block->block_type === 'youtube') {
                $block->external_url = $this->normalizeYoutubeUrl($block->external_url ?? null);
            }
            $block->file_url = $this->formatStorageUrl($block->file_path ?? null);
            $safePath = in_array($block->block_type, ['material', 'media'], true)
                ? $this->eligibleLocalAuthoringAssetPath(
                    $block->file_path ?? null,
                    (string) ($block->material_course_id ?? ''),
                    ['training-materials', 'training-media']
                )
                : null;
            $block->file_available = $safePath !== null
                && Storage::disk('public')->exists($safePath);
            if ($block->assessment_id) {
                $block->assessment_question_count = DB::table('assessment_question')
                    ->where('assessment_id', $block->assessment_id)
                    ->count();
                $block->assessment_availability = $this->materialAuthoring
                    ->deriveAssessmentAvailability(
                        $block->available_from ?? null,
                        $block->available_until ?? null
                    );
            }
        }

        return $block;
    }
}
