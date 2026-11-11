<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ParticipantMaterialAccessService;
use App\Services\TeachingPlanService;
use App\Support\CanonicalAttendance;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\ParticipantTypeLabel;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MonitoringController extends Controller
{
    public function activityLog(Request $request)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $activities = collect();
        $trainers = collect();
        $actions = collect();
        $stats = [
            'total' => 0,
            'today' => 0,
            'week' => 0,
            'topTrainerName' => null,
            'topTrainerN' => 0,
            'topActionKey' => null,
            'topActionN' => 0,
        ];
        $activityControls = [
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 160),
            'trainer' => mb_substr(trim($request->string('trainer')->toString()), 0, 40) ?: 'all',
            'action' => mb_substr(trim($request->string('action')->toString()), 0, 100) ?: 'all',
            'sort' => $request->query('sort') === 'asc' ? 'asc' : 'desc',
            'rows' => in_array($request->integer('rows'), [10, 25, 50], true) ? $request->integer('rows') : 10,
        ];
        $timezone = (string) config('app.timezone', 'Asia/Kuala_Lumpur');
        $now = Carbon::now($timezone);
        $activityPeriod = [
            'key' => 'all',
            'from' => '',
            'to' => '',
            'label' => 'All recorded dates',
            'todayValue' => $now->toDateString(),
            'weekFrom' => $now->copy()->subDays(6)->toDateString(),
            'monthFrom' => $now->copy()->subDays(29)->toDateString(),
            'todayLabel' => $now->format('d M Y'),
            'weekLabel' => $now->copy()->subDays(6)->format('d M').' - '.$now->format('d M Y'),
            'monthLabel' => $now->copy()->subDays(29)->format('d M').' - '.$now->format('d M Y'),
        ];

        if ($request->query('period') === 'custom') {
            $validated = $request->validate([
                'from' => ['required', 'date_format:Y-m-d'],
                'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            ], [
                'from.required' => 'Choose a start date for the custom activity period.',
                'to.required' => 'Choose an end date for the custom activity period.',
                'to.after_or_equal' => 'The end date must be the same as or later than the start date.',
            ]);
            $customFrom = Carbon::createFromFormat('Y-m-d', $validated['from'], $timezone)->startOfDay();
            $customTo = Carbon::createFromFormat('Y-m-d', $validated['to'], $timezone)->endOfDay();
            $activityPeriod = array_merge($activityPeriod, [
                'key' => 'custom',
                'from' => $validated['from'],
                'to' => $validated['to'],
                'label' => $customFrom->format('d M Y').' - '.$customTo->format('d M Y'),
            ]);
        }

        if (Schema::hasTable('trainer_activity_log')) {
            $hasCourse = Schema::hasColumn('trainer_activity_log', 'course_id');

            $query = DB::table('trainer_activity_log')
                ->leftJoin('trainer', 'trainer_activity_log.trainer_id', '=', 'trainer.trainerID');

            $selects = [
                'trainer_activity_log.trainer_id',
                'trainer_activity_log.action',
                'trainer_activity_log.detail',
                'trainer_activity_log.created_at',
                'trainer.trainerName as trainer_name',
            ];

            if ($hasCourse && Schema::hasTable('course')) {
                $query->leftJoin('course', 'trainer_activity_log.course_id', '=', 'course.courseID');
                $selects[] = 'trainer_activity_log.course_id';
                $selects[] = 'course.courseName as course_name';
            }

            $activities = TrainerActivityLogger::scopeMeaningful($query)
                ->select($selects)
                ->orderByDesc('trainer_activity_log.created_at')
                ->get();

            $actionQuery = TrainerActivityLogger::scopeMeaningful(DB::table('trainer_activity_log'));
            $actions = $actionQuery
                ->select('action')->distinct()->orderBy('action')->pluck('action');
            if ($activityControls['action'] !== 'all' && ! $actions->contains($activityControls['action'])) {
                $activityControls['action'] = 'all';
            }

            $trainers = DB::table('trainer')
                ->select('trainerID as trainer_id', 'trainerName as trainer_name')
                ->orderBy('trainerName')->get();

            $statsActivities = $activityPeriod['key'] === 'custom'
                ? $activities->filter(function ($activity) use ($activityPeriod, $timezone) {
                    if (! $activity->created_at) {
                        return false;
                    }

                    $occurredAt = Carbon::parse($activity->created_at, $timezone);

                    return $occurredAt->between(
                        Carbon::createFromFormat('Y-m-d', $activityPeriod['from'], $timezone)->startOfDay(),
                        Carbon::createFromFormat('Y-m-d', $activityPeriod['to'], $timezone)->endOfDay()
                    );
                })
                : $activities;
            $today = $now->copy()->startOfDay();
            $weekAgo = $now->copy()->subDays(6)->startOfDay();
            $stats['total'] = $statsActivities->count();
            $stats['today'] = $statsActivities->filter(function ($activity) use ($today) {
                return $activity->created_at && Carbon::parse($activity->created_at)->gte($today);
            })->count();
            $stats['week'] = $statsActivities->filter(function ($activity) use ($weekAgo) {
                return $activity->created_at && Carbon::parse($activity->created_at)->gte($weekAgo);
            })->count();

            $byTrainer = $statsActivities->whereNotNull('trainer_name')->groupBy('trainer_name')->map->count()->sortDesc();
            if ($byTrainer->count() > 0) {
                $stats['topTrainerName'] = $byTrainer->keys()->first();
                $stats['topTrainerN'] = $byTrainer->first();
            }

            $byAction = $statsActivities->groupBy('action')->map->count()->sortDesc();
            if ($byAction->count() > 0) {
                $stats['topActionKey'] = $byAction->keys()->first();
                $stats['topActionN'] = $byAction->first();
            }
        }

        return view('admin.activity', compact('activities', 'trainers', 'actions', 'stats', 'activityPeriod', 'activityControls'));
    }

    public function courses(
        Request $request,
        TeachingPlanService $teachingPlans,
        ParticipantMaterialAccessService $materialAccess
    ) {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $today = Carbon::now()->startOfDay();

        $courseRows = CanonicalTrainingQuery::courses()
            ->orderByRaw(SharedTrainingSchema::courseNameExpression())
            ->get()
            ->each(function (object $course): void {
                $sessions = SharedTrainingSchema::constrainSessionsToCourse(
                    CanonicalTrainingQuery::sessions('course_session'),
                    $course
                )->get();
                $course->total_sessions = $sessions->count();
                $course->held_sessions = $sessions->filter(
                    fn (object $session): bool => $session->date && Carbon::parse($session->date)->lte(now())
                )->count();
                $course->enrolled = SharedTrainingSchema::constrainEnrollmentToCourse(
                    CanonicalEnrollment::query('enrollment'),
                    $course
                )->count();
            });

        $currentTeachingPlans = $teachingPlans->currentForCourses($courseRows->pluck('course_id'));
        $materialAccessStates = $materialAccess->statesForCourses($courseRows->pluck('course_id'));

        $sessionTrainerQuery = DB::table('course as course');
        SharedTrainingSchema::joinCourseSessions($sessionTrainerQuery);
        SharedTrainingSchema::joinSessionAssignments($sessionTrainerQuery);
        SharedTrainingSchema::joinAssignmentTrainers($sessionTrainerQuery);
        $sessionTrainerRows = $sessionTrainerQuery
            ->selectRaw(SharedTrainingSchema::courseInternalExpression().' AS course_id')
            ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
            ->selectRaw(SharedTrainingSchema::trainerInternalExpression().' AS trainer_id')
            ->selectRaw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
            ->distinct()
            ->get();
        $trainersByCourse = $sessionTrainerRows->groupBy('course_id');
        $trainersBySession = $sessionTrainerRows->groupBy('session_id');

        $sessionQuery = DB::table('course as course');
        SharedTrainingSchema::joinCourseSessions($sessionQuery);
        $sessionsByCourse = $sessionQuery
            ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
            ->selectRaw(SharedTrainingSchema::courseInternalExpression().' AS course_id')
            ->selectRaw(SharedTrainingSchema::sessionNameExpression().' AS session_title')
            ->selectRaw(SharedTrainingSchema::sessionDateExpression().' AS date')
            ->selectRaw(SharedTrainingSchema::sessionStartExpression().' AS start_time')
            ->selectRaw(SharedTrainingSchema::sessionEndExpression().' AS end_time')
            ->addSelect('course_session.location')
            ->selectRaw('NULL AS session_type, NULL AS session_order')
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->get()
            ->groupBy('course_id');

        $participantsByCourse = collect();
        foreach ($courseRows as $course) {
            $participantQuery = CanonicalEnrollment::query('enrollment');
            SharedTrainingSchema::joinEnrollmentIdentities($participantQuery);
            $participantQuery
                ->select('enrollment.*')
                ->selectRaw(SharedTrainingSchema::enrollmentParticipantNameExpression().' AS name')
                ->orderBy('name');
            $participantsByCourse->put(
                (string) $course->course_id,
                SharedTrainingSchema::constrainEnrollmentToCourse($participantQuery, $course)->get()
            );
        }

        // V4 defines the approval state independently from its decimal fields.
        $attendanceAvailable = collect([
            'attendance',
            'attendance_guru_baru',
            'attendance_outsider',
            'attendance_staff',
        ])->contains(fn (string $table): bool => Schema::hasTable($table));
        $participantIdentityKey = static function (object $row): string {
            if (($row->teacher_id ?? null) !== null) {
                return 'teacher:'.$row->teacher_id;
            }
            if (($row->applicant_id ?? null) !== null) {
                return 'applicant:'.$row->applicant_id;
            }
            if (($row->staff_id ?? null) !== null) {
                return 'staff:'.$row->staff_id;
            }

            return 'outsider:'.($row->outsider_id ?? 'unknown');
        };
        $attendanceRows = $attendanceAvailable
            ? CanonicalAttendance::query('attendance_record')
                ->join('course_session', 'attendance_record.session_id', '=', 'course_session.sessionID')
                ->select('attendance_record.*', 'course_session.courseID as course_id')
                ->get()
                ->each(function (object $row) use ($participantIdentityKey): void {
                    $row->participant_key = $participantIdentityKey($row);
                })
            : collect();
        $attendanceByCourse = $attendanceRows->groupBy('course_id');
        $attendanceBySession = $attendanceRows->groupBy('session_id');
        $attendanceByParticipant = $attendanceRows->groupBy('participant_key');

        $summarizeAttendance = function ($rows, ?int $expectedRecords = null): array {
            $rows = collect($rows);
            $recorded = $rows->count();

            return [
                'recorded' => $recorded,
                'notRecorded' => max(0, ($expectedRecords ?? $recorded) - $recorded),
            ];
        };
        $formatTime = function ($value): ?string {
            if (! $value) {
                return null;
            }

            return Carbon::parse((string) $value)->format('g:i A');
        };

        $trainingData = [];
        $trainings = $courseRows->map(function ($course) use (
            $trainersByCourse,
            $trainersBySession,
            $sessionsByCourse,
            $participantsByCourse,
            $attendanceAvailable,
            $attendanceByCourse,
            $attendanceBySession,
            $attendanceByParticipant,
            $participantIdentityKey,
            $summarizeAttendance,
            $formatTime,
            $currentTeachingPlans,
            $teachingPlans,
            $materialAccessStates,
            $today,
            &$trainingData
        ) {
            $total = (int) $course->total_sessions;
            $held = (int) $course->held_sessions;
            $modeKey = strtolower(trim((string) ($course->training_mode ?? 'standard'))) ?: 'standard';
            $modeLabel = match ($modeKey) {
                'lms' => 'LMS',
                'simple' => 'Simple',
                'standard' => 'Simple',
                default => ucfirst($modeKey),
            };
            $modeFilterKey = in_array($modeKey, ['simple', 'standard'], true) ? 'simple' : $modeKey;
            $isLms = $modeKey === 'lms';
            $progress = $total > 0 ? (int) round($held / $total * 100) : 0;
            $courseTrainerRows = $trainersByCourse->get($course->course_id, collect());
            $trainerList = $courseTrainerRows
                ->unique('trainer_id')
                ->map(function ($trainer) use ($course, $currentTeachingPlans, $teachingPlans) {
                    $trainerId = (string) $trainer->trainer_id;
                    $currentPlan = $currentTeachingPlans->get(
                        $teachingPlans->scopeKey((string) $course->course_id, $trainerId)
                    );
                    $adminState = $teachingPlans->adminState($currentPlan);
                    $current = $currentPlan
                        ? [
                            'id' => (int) $currentPlan->id,
                            'originalName' => $currentPlan->original_name,
                            'submittedAt' => Carbon::parse($currentPlan->submitted_at)->format('d M Y, g:i A'),
                            'fileAvailable' => (bool) $currentPlan->file_available,
                            'viewUrl' => route('admin.courses.teaching-plans.view', [$course->course_id, $trainerId, $currentPlan->id]),
                            'downloadUrl' => route('admin.courses.teaching-plans.download', [$course->course_id, $trainerId, $currentPlan->id]),
                            'feedbackUrl' => route('admin.courses.teaching-plans.comments.store', [$course->course_id, $trainerId, $currentPlan->id]),
                            'comments' => collect($currentPlan->comments)->map(fn ($comment) => [
                                'id' => (int) $comment->id,
                                'reviewerName' => $comment->reviewer_name ?: 'Admin',
                                'comment' => $comment->comment,
                                'createdAt' => Carbon::parse($comment->created_at)->format('d M Y, g:i A'),
                            ])->values()->all(),
                        ]
                        : null;

                    return [
                        'id' => $trainerId,
                        'name' => $trainer->trainer_name,
                        'teachingPlan' => [
                            'stateKey' => $adminState['key'],
                            'stateLabel' => $adminState['label'],
                            'current' => $current,
                        ],
                    ];
                })
                ->values()
                ->all();
            $trainerNames = collect($trainerList)->pluck('name')->all();
            $teachingPlanCounts = collect($trainerList)->reduce(function (array $counts, array $trainer): array {
                $current = $trainer['teachingPlan']['current'];
                if (! $current) {
                    $counts['notSubmitted']++;
                } elseif (empty($current['comments'])) {
                    $counts['needsReview']++;
                } else {
                    $counts['feedbackSent']++;
                }

                return $counts;
            }, [
                'needsReview' => 0,
                'feedbackSent' => 0,
                'notSubmitted' => 0,
            ]);

            $courseParticipants = $participantsByCourse->get($course->course_id, collect());
            $participantTotal = $courseParticipants->count();
            $sessionList = [];
            $courseSessions = $sessionsByCourse->get($course->course_id, collect());
            if ($courseSessions->isNotEmpty()) {
                foreach ($courseSessions as $session) {
                    $date = $session->date ? Carbon::parse($session->date) : null;
                    $scheduleKey = ! $date
                        ? 'unavailable'
                        : ($date->copy()->startOfDay()->lt($today)
                            ? 'past'
                            : ($date->copy()->startOfDay()->gt($today) ? 'upcoming' : 'today'));
                    $sessionAttendance = $summarizeAttendance(
                        $attendanceBySession->get($session->session_id, collect()),
                        $participantTotal
                    );
                    $sessionTrainers = $trainersBySession->get($session->session_id, collect())
                        ->pluck('trainer_name')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    $startTime = $formatTime($session->start_time ?? null);
                    $endTime = $formatTime($session->end_time ?? null);
                    $timeLabel = $startTime && $endTime
                        ? $startTime.' - '.$endTime
                        : ($startTime ?: ($endTime ? 'Until '.$endTime : 'Time not set'));

                    $sessionList[] = [
                        'id' => (string) $session->session_id,
                        'title' => $session->session_title ?: 'Training session',
                        'dateLabel' => $date ? $date->format('d M Y') : 'Date not set',
                        'dateValue' => $date?->format('Y-m-d') ?? '',
                        'sortDate' => $date?->timestamp ?? 0,
                        'month' => $date ? strtoupper($date->format('M')) : '--',
                        'day' => $date ? $date->format('d') : '--',
                        'timeLabel' => $timeLabel,
                        'type' => $session->session_type ?: 'Mode not specified',
                        'location' => $session->location ?: 'Location not specified',
                        'scheduleKey' => $scheduleKey,
                        'scheduleLabel' => match ($scheduleKey) {
                            'past' => 'Past',
                            'today' => 'Today',
                            'upcoming' => 'Upcoming',
                            default => 'Schedule unavailable',
                        },
                        'trainers' => $sessionTrainers,
                        'attendance' => $sessionAttendance,
                    ];
                }
            }
            $sessionDates = collect($sessionList)
                ->filter(fn (array $session) => $session['dateValue'] !== '')
                ->sortBy('sortDate')
                ->values();
            $firstDate = $sessionDates->first();
            $lastDate = $sessionDates->last();
            if (! $firstDate || ! $lastDate) {
                $scheduleKey = 'unscheduled';
                $scheduleLabel = 'Schedule unavailable';
                $dateRangeLabel = 'No session dates recorded';
            } elseif ($lastDate['sortDate'] < $today->timestamp) {
                $scheduleKey = 'past';
                $scheduleLabel = 'Past';
                $dateRangeLabel = $firstDate['dateLabel'].' - '.$lastDate['dateLabel'];
            } elseif ($firstDate['sortDate'] > $today->copy()->endOfDay()->timestamp) {
                $scheduleKey = 'upcoming';
                $scheduleLabel = 'Upcoming';
                $dateRangeLabel = $firstDate['dateLabel'].' - '.$lastDate['dateLabel'];
            } else {
                $scheduleKey = 'current';
                $scheduleLabel = 'Ongoing';
                $dateRangeLabel = $firstDate['dateLabel'].' - '.$lastDate['dateLabel'];
            }
            $nextSession = $sessionDates
                ->first(fn (array $session) => $session['sortDate'] >= $today->timestamp);

            $participantList = [];
            $typeCounts = [];
            $participantNames = [];
            foreach ($courseParticipants as $participant) {
                $typeValue = trim((string) ($participant->participant_type ?? ''));
                if ($typeValue === '') {
                    $typeValue = ($participant->teacher_id ?? null) !== null && $participant->teacher_id !== ''
                        ? 'Teacher'
                        : (($participant->applicant_id ?? null) !== null && $participant->applicant_id !== ''
                            ? 'Guru Baru'
                            : (($participant->staff_id ?? null) !== null && $participant->staff_id !== ''
                                ? 'Staff'
                                : (($participant->outsider_id ?? null) !== null && $participant->outsider_id !== '' ? 'Outsider' : 'Participant')));
                }
                $typeLabel = ParticipantTypeLabel::display($typeValue);
                $participantIdentity = $participantIdentityKey($participant);
                $participantAttendance = $summarizeAttendance(
                    $attendanceByParticipant->get($participantIdentity, collect()),
                    $courseSessions->count()
                );
                $participantKey = $participant->enrollment_id
                    ?? $participant->teacher_id
                    ?? $participant->applicant_id
                    ?? $participant->outsider_id
                    ?? $participant->staff_id;
                $participantNames[$participantIdentity] = [
                    'name' => $participant->name,
                    'typeLabel' => $typeLabel,
                ];
                $participantList[] = [
                    'enrollmentId' => (string) $participantKey,
                    'name' => $participant->name,
                    'typeValue' => $typeValue,
                    'typeLabel' => $typeLabel,
                    'enrollmentType' => $participant->enrollment_type ?? null,
                    'approvalStatus' => $participant->approval_status ?? null,
                    'enrolledAt' => ! empty($participant->enrolled_at)
                        ? Carbon::parse($participant->enrolled_at)->format('d M Y')
                        : null,
                    'enrolledAtSort' => ! empty($participant->enrolled_at)
                        ? Carbon::parse($participant->enrolled_at)->timestamp
                        : 0,
                    'attendance' => $participantAttendance,
                ];
                $typeCounts[$typeLabel] = ($typeCounts[$typeLabel] ?? 0) + 1;
            }
            $participantList = collect($participantList)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

            $courseAttendanceRows = $attendanceByCourse->get($course->course_id, collect())
                ->filter(fn (object $record): bool => isset($participantNames[$record->participant_key]))
                ->values();
            $courseAttendance = $summarizeAttendance(
                $courseAttendanceRows,
                $participantTotal * $courseSessions->count()
            );
            $attendanceRecordIndex = $courseAttendanceRows->keyBy(
                fn (object $record): string => (string) $record->session_id.'|'.$record->participant_key
            );
            $attendanceRecords = collect($sessionList)
                ->flatMap(function (array $session) use ($participantNames, $attendanceRecordIndex): array {
                    return collect($participantNames)->map(function (array $participant, string $participantKey) use ($session, $attendanceRecordIndex): array {
                        $record = $attendanceRecordIndex->get($session['id'].'|'.$participantKey);

                        return [
                            'sessionId' => $session['id'],
                            'sessionTitle' => $session['title'],
                            'sessionDate' => $session['dateLabel'],
                            'participantKey' => $participantKey,
                            'participantName' => $participant['name'],
                            'participantType' => $participant['typeLabel'],
                            'statusKey' => $record ? 'recorded' : 'not-recorded',
                            'statusLabel' => $record ? 'Recorded' : 'Not Recorded',
                        ];
                    })->all();
                })
                ->sortBy(fn (array $record) => $record['sessionDate'].' '.$record['participantName'])
                ->values()
                ->all();

            $storedStatus = trim((string) ($course->status ?? '')) ?: 'Status unavailable';
            $storedStatusKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $storedStatus) ?? 'unknown');

            $course->is_lms = $isLms;
            $course->progress = $progress;
            $course->mode_key = $modeKey;
            $course->mode_filter_key = $modeFilterKey;
            $course->mode_label = $modeLabel;
            $course->schedule_key = $scheduleKey;
            $course->schedule_label = $scheduleLabel;
            $course->status_key = $storedStatusKey;
            $course->status_label = $storedStatus;
            $course->trainer_names = $trainerNames;
            $course->date_range_label = $dateRangeLabel;
            $course->sort_date = $lastDate['sortDate'] ?? ($firstDate['sortDate'] ?? 0);
            $course->teaching_plan_attention = $teachingPlanCounts['needsReview'];

            $trainingData[(string) $course->course_id] = [
                'id' => (string) $course->course_id,
                'name' => $course->course_name,
                'category' => $course->course_category ?: '',
                'description' => $course->description ?: '',
                'isLms' => $isLms,
                'modeKey' => $modeKey,
                'modeFilterKey' => $modeFilterKey,
                'modeLabel' => $modeLabel,
                'status' => $storedStatus,
                'statusKey' => $storedStatusKey,
                'scheduleKey' => $scheduleKey,
                'scheduleLabel' => $scheduleLabel,
                'dateRangeLabel' => $dateRangeLabel,
                'targetParticipant' => ParticipantTypeLabel::audience($course->target_participant ?? null),
                'capacity' => $course->capacity ?? null,
                'total' => $total,
                'held' => $held,
                'enrolled' => (int) $course->enrolled,
                'progress' => $progress,
                'trainers' => $trainerList,
                'teachingPlanCounts' => $teachingPlanCounts,
                'attendanceAvailable' => $attendanceAvailable,
                'attendance' => $courseAttendance,
                'nextSession' => $nextSession,
                'typeCounts' => $typeCounts,
                'sessions' => $sessionList,
                'participants' => $participantList,
                'attendanceRecords' => $attendanceRecords,
                'materialAccess' => $materialAccessStates->get((string) $course->course_id),
                'materialAccessUpdateUrl' => route('admin.courses.participant-material-access.update', $course->course_id),
                'sortDate' => $course->sort_date,
            ];

            return $course;
        });

        $trainingFilterOptions = [
            'modes' => $trainings
                ->map(fn ($training) => ['value' => $training->mode_filter_key, 'label' => $training->mode_label])
                ->unique('value')
                ->sortBy('label')
                ->values(),
            'statuses' => $trainings
                ->map(fn ($training) => ['value' => $training->status_key, 'label' => $training->status_label])
                ->unique('value')
                ->sortBy('label')
                ->values(),
            'trainers' => $sessionTrainerRows
                ->map(fn ($trainer) => ['value' => (string) $trainer->trainer_id, 'label' => $trainer->trainer_name])
                ->unique('value')
                ->sortBy('label')
                ->values(),
        ];

        return view('admin.courses', compact('trainings', 'trainingData', 'trainingFilterOptions'));
    }

    public function updateParticipantMaterialAccess(
        Request $request,
        string $courseId,
        ParticipantMaterialAccessService $materialAccess
    ) {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        abort_unless(SharedTrainingSchema::course($courseId), 404);

        $validated = $request->validateWithBag('materialAccess', [
            'is_enabled' => ['required', 'boolean'],
            'access_mode' => ['required', Rule::in(['no_expiry', 'until'])],
            'access_until' => [
                Rule::requiredIf($request->boolean('is_enabled') && $request->input('access_mode') === 'until'),
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
        ], [
            'is_enabled.required' => 'Choose whether participant material access is enabled.',
            'is_enabled.boolean' => 'The participant material access selection is invalid.',
            'access_mode.required' => 'Choose an access duration.',
            'access_mode.in' => 'The selected access duration is invalid.',
            'access_until.required' => 'Choose the date and time when access should expire.',
            'access_until.date_format' => 'Enter a valid access expiry date and time.',
        ]);

        $isEnabled = (bool) $validated['is_enabled'];
        $accessUntil = null;
        if ($isEnabled && $validated['access_mode'] === 'until') {
            $accessUntil = Carbon::createFromFormat(
                'Y-m-d\TH:i',
                $validated['access_until'],
                (string) config('app.timezone')
            );
            if ($accessUntil->lte(Carbon::now((string) config('app.timezone')))) {
                throw ValidationException::withMessages([
                    'access_until' => 'The access expiry must be a future date and time.',
                ])->errorBag('materialAccess');
            }
        }

        $materialAccess->save($courseId, $isEnabled, $accessUntil);

        return redirect()->route('admin.courses', ['course' => $courseId, 'tab' => 'overview'])
            ->with('success', 'Participant material access was updated successfully.');
    }
}
