<?php

namespace App\Services\SimpleTraining;

use App\Services\AttendanceQrService;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\ParticipantTypeLabel;
use App\Support\SharedTrainingSchema;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainerSimpleTrainingReportService
{
    private const ROW_OPTIONS = [10, 25, 50];

    private const MATERIAL_SORT_OPTIONS = ['newest', 'oldest', 'name-asc', 'name-desc'];

    private const ATTENDANCE_STATUS_OPTIONS = ['all', 'recorded', 'not-recorded'];

    public function __construct(private readonly AttendanceQrService $attendanceQrs) {}

    public function overview(string $courseId, string $trainerId): array
    {
        $sessions = $this->sessions($courseId, $trainerId);
        $currentSessions = $sessions->where('schedule_group', 'current')->values();
        $upcomingSessions = $sessions->where('schedule_group', 'upcoming')->values();
        $pastSessions = $sessions
            ->where('schedule_group', 'past')
            ->sortByDesc(fn (object $session) => $session->schedule_sort)
            ->values();
        $featuredSession = $currentSessions->first() ?: $upcomingSessions->first();
        $featuredLabel = $currentSessions->isNotEmpty() ? 'Current Session' : ($featuredSession ? 'Next Session' : null);
        $scheduleUpcoming = $featuredSession
            ? $sessions
                ->whereIn('schedule_group', ['current', 'upcoming'])
                ->reject(fn (object $session) => (string) $session->session_id === (string) $featuredSession->session_id)
                ->values()
            : collect();

        $participantStats = $this->participantStats($courseId);
        $participantsPreview = $this->participantQuery($courseId)
            ->select($this->participantSelect(false))
            ->orderByRaw($this->participantNameExpression())
            ->limit(6)
            ->get()
            ->map(fn (object $participant) => $this->decorateParticipant($participant));

        return [
            'sessions' => $sessions,
            'currentSessions' => $currentSessions,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'featuredSession' => $featuredSession,
            'featuredLabel' => $featuredLabel,
            'scheduleUpcoming' => $scheduleUpcoming,
            'participantsPreview' => $participantsPreview,
            'participantStats' => $participantStats,
            'materialCount' => DB::table('learning_material')->where('course_id', $courseId)->count(),
            'trainingTeam' => $this->trainingTeam($courseId, $trainerId),
        ];
    }

    public function trainingTeam(string $courseId, string $currentTrainerId): Collection
    {
        $course = SharedTrainingSchema::course($courseId);
        if (! $course) {
            return collect();
        }

        $query = DB::table('course_session as course_session');
        SharedTrainingSchema::joinSessionAssignments($query);
        SharedTrainingSchema::joinAssignmentTrainers($query);
        SharedTrainingSchema::constrainSessionsToCourse($query, $course);

        return $query
            ->selectRaw(SharedTrainingSchema::trainerInternalExpression().' AS trainer_id')
            ->selectRaw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
            ->distinct()
            ->orderByRaw(SharedTrainingSchema::trainerNameExpression())
            ->get()
            ->map(function (object $trainer) use ($currentTrainerId): object {
                $trainer->is_current = (string) $trainer->trainer_id === $currentTrainerId;

                return $trainer;
            });
    }

    public function normalizeMaterialFilters(Request $request): array
    {
        $sort = $request->string('sort')->toString();

        return [
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 255),
            'sort' => in_array($sort, self::MATERIAL_SORT_OPTIONS, true) ? $sort : 'newest',
            'type' => mb_substr(trim($request->string('type')->toString()), 0, 40),
            'rows' => $this->rows($request),
            'page' => max(1, $request->integer('page', 1)),
        ];
    }

    public function materials(string $courseId, array $filters): array
    {
        $typeExpression = $this->materialTypeExpression();
        $typeOptions = DB::table('learning_material')
            ->where('course_id', $courseId)
            ->selectRaw($typeExpression.' as material_type_label')
            ->distinct()
            ->orderBy('material_type_label')
            ->pluck('material_type_label')
            ->filter()
            ->values();

        if ($filters['type'] !== '' && ! $typeOptions->contains($filters['type'])) {
            $filters['type'] = '';
        }

        $query = DB::table('learning_material')->where('course_id', $courseId);
        if ($filters['search'] !== '') {
            $query->whereRaw('LOWER(original_file_name) LIKE ?', ['%'.mb_strtolower($filters['search']).'%']);
        }
        if ($filters['type'] !== '') {
            $query->whereRaw($typeExpression.' = ?', [$filters['type']]);
        }

        match ($filters['sort']) {
            'oldest' => $query->orderBy('uploaded_at')->orderBy('material_id'),
            'name-asc' => $query->orderByRaw('LOWER(original_file_name)')->orderBy('material_id'),
            'name-desc' => $query->orderByRaw('LOWER(original_file_name) DESC')->orderByDesc('material_id'),
            default => $query->orderByDesc('uploaded_at')->orderByDesc('material_id'),
        };

        $paginator = $this->paginate(
            $query->selectRaw('material_id, original_file_name, uploaded_at, '.$typeExpression.' as material_type'),
            $filters,
            route('trainer.simple.materials', $courseId)
        );

        return [
            'materials' => collect($paginator->items()),
            'materialPaginator' => $paginator,
            'materialFilters' => $filters,
            'materialTypeOptions' => $typeOptions,
            'materialTotal' => DB::table('learning_material')->where('course_id', $courseId)->count(),
        ];
    }

    public function normalizeAttendanceFilters(Request $request): array
    {
        $status = strtolower(trim($request->string('status')->toString()));

        return [
            'session_id' => mb_substr(trim($request->string('session_id')->toString()), 0, 20),
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 160),
            'status' => in_array($status, self::ATTENDANCE_STATUS_OPTIONS, true) ? $status : 'all',
            'rows' => $this->rows($request),
            'page' => max(1, $request->integer('page', 1)),
        ];
    }

    public function attendance(string $courseId, string $trainerId, array $filters): array
    {
        $sessions = $this->sessions($courseId, $trainerId);
        $selectedSession = $filters['session_id'] !== ''
            ? $sessions->firstWhere('session_id', $filters['session_id'])
            : null;
        $selectedSession ??= $sessions->firstWhere('schedule_group', 'current');
        $selectedSession ??= $sessions->firstWhere('schedule_group', 'upcoming');
        $selectedSession ??= $sessions->sortByDesc('schedule_sort')->first();
        $filters['session_id'] = (string) ($selectedSession->session_id ?? '');

        if (! $selectedSession) {
            return [
                'sessions' => $sessions,
                'selectedSession' => null,
                'attendanceQr' => null,
                'attendanceCounts' => $this->emptyAttendanceCounts(),
                'attendancePaginator' => $this->emptyPaginator($filters['rows'], route('trainer.simple.attendance', $courseId)),
                'attendanceFilters' => $filters,
            ];
        }

        $sessionId = (string) $selectedSession->session_id;
        $baseQuery = $this->attendanceParticipantQuery($courseId, $sessionId);
        $recordExpression = $this->attendanceRecordExpression();
        $countRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN $recordExpression IS NOT NULL THEN 1 ELSE 0 END) as recorded_count")
            ->first();
        $attendanceCounts = [
            'total' => (int) ($countRow->total_count ?? 0),
            'recorded' => (int) ($countRow->recorded_count ?? 0),
        ];
        $attendanceCounts['not_recorded'] = max(
            0,
            $attendanceCounts['total'] - $attendanceCounts['recorded']
        );

        $query = clone $baseQuery;
        if ($filters['search'] !== '') {
            $needle = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function (Builder $searchQuery) use ($needle): void {
                $searchQuery
                    ->whereRaw('LOWER('.$this->participantNameExpression().') LIKE ?', [$needle])
                    ->orWhereRaw('LOWER('.$this->participantEmailExpression().') LIKE ?', [$needle]);
            });
        }
        if ($filters['status'] === 'not-recorded') {
            $query->whereRaw($recordExpression.' IS NULL');
        } elseif ($filters['status'] === 'recorded') {
            $query->whereRaw($recordExpression.' IS NOT NULL');
        }

        $paginator = $this->paginate(
            $query
                ->select(array_merge($this->participantSelect(false), [
                    DB::raw($recordExpression.' as attendance_record_id'),
                    DB::raw($this->attendanceColumnExpression('created_at').' as self_recorded_at'),
                ]))
                ->orderByRaw($this->participantNameExpression()),
            $filters,
            route('trainer.simple.attendance', $courseId)
        );
        $paginator->setCollection($paginator->getCollection()->map(function (object $participant): object {
            $participant = $this->decorateParticipant($participant);
            $participant->attendance_status = $participant->attendance_record_id === null
                ? 'Not Recorded'
                : 'Recorded';

            return $participant;
        }));

        return [
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'attendanceQr' => $this->attendanceQrs->forSession($selectedSession, $courseId),
            'attendanceCounts' => $attendanceCounts,
            'attendancePaginator' => $paginator,
            'attendanceFilters' => $filters,
        ];
    }

    public function normalizeParticipantFilters(Request $request): array
    {
        $gender = mb_substr(trim($request->string('gender')->toString()), 0, 30);
        $letter = strtoupper(mb_substr(trim($request->string('letter')->toString()), 0, 1));

        return [
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 160),
            'gender' => in_array($gender, ['Male', 'Female', 'Unspecified'], true) ? $gender : '',
            'letter' => preg_match('/^[A-Z]$/', $letter) === 1 ? $letter : '',
            'rows' => $this->rows($request),
            'page' => max(1, $request->integer('page', 1)),
        ];
    }

    public function participants(string $courseId, array $filters): array
    {
        $participantStats = $this->participantStats($courseId);
        $letters = $this->participantQuery($courseId)
            ->selectRaw('UPPER(SUBSTR('.$this->participantNameExpression().', 1, 1)) as initial_letter')
            ->distinct()
            ->pluck('initial_letter')
            ->filter(fn ($letter) => preg_match('/^[A-Z]$/', (string) $letter) === 1)
            ->sort()
            ->values();
        if ($filters['letter'] !== '' && ! $letters->contains($filters['letter'])) {
            $filters['letter'] = '';
        }

        $query = $this->participantQuery($courseId);
        if ($filters['search'] !== '') {
            $needle = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function (Builder $searchQuery) use ($needle): void {
                $searchQuery
                    ->whereRaw('LOWER('.$this->participantNameExpression().') LIKE ?', [$needle])
                    ->orWhereRaw('LOWER('.$this->participantEmailExpression().') LIKE ?', [$needle])
                    ->orWhereRaw('LOWER('.$this->participantPhoneExpression().') LIKE ?', [$needle]);
            });
        }
        if ($filters['gender'] === 'Unspecified') {
            $query->whereRaw(
                'COALESCE(TRIM('.$this->participantGenderExpression()."), '') NOT IN ('Male', 'Female')"
            );
        } elseif ($filters['gender'] !== '') {
            $query->whereRaw($this->participantGenderExpression().' = ?', [$filters['gender']]);
        }
        if ($filters['letter'] !== '') {
            $query->whereRaw('UPPER(SUBSTR('.$this->participantNameExpression().', 1, 1)) = ?', [$filters['letter']]);
        }

        $paginator = $this->paginate(
            $query
                ->select($this->participantSelect(false))
                ->orderByRaw($this->participantNameExpression()),
            $filters,
            route('trainer.simple.participants', $courseId)
        );
        $paginator->setCollection($paginator->getCollection()->map(fn (object $participant) => $this->decorateParticipant($participant)));

        return [
            'participantStats' => $participantStats,
            'participantPaginator' => $paginator,
            'participantFilters' => $filters,
            'participantLetters' => $letters,
        ];
    }

    public function sessions(string $courseId, string $trainerId): Collection
    {
        $course = SharedTrainingSchema::course($courseId);
        if (! $course) {
            return collect();
        }

        $query = CanonicalTrainingQuery::sessions('course_session');
        SharedTrainingSchema::joinSessionAssignments($query);
        SharedTrainingSchema::constrainSessionsToCourse($query, $course);
        SharedTrainingSchema::constrainAssignmentToTrainer($query, $trainerId);

        return $query
            ->distinct()
            ->get()
            ->map(fn (object $session) => $this->decorateSession($session))
            ->sortBy('schedule_sort')
            ->values();
    }

    private function decorateSession(object $session): object
    {
        $date = $this->carbonDate($session->date ?? null);
        $start = $date && ! empty($session->start_time)
            ? $this->carbonDateTime($date->toDateString(), $session->start_time)
            : null;
        $end = $date && ! empty($session->end_time)
            ? $this->carbonDateTime($date->toDateString(), $session->end_time)
            : null;
        $now = Carbon::now();

        if (! $date) {
            $group = 'unscheduled';
        } elseif ($date->isAfter($now->copy()->startOfDay())) {
            $group = 'upcoming';
        } elseif ($date->isBefore($now->copy()->startOfDay())) {
            $group = 'past';
        } elseif ($end && $now->greaterThan($end)) {
            $group = 'past';
        } elseif ($start && $now->lessThan($start)) {
            $group = 'upcoming';
        } else {
            $group = 'current';
        }

        $session->session_id = (string) $session->session_id;
        $session->schedule_group = $group;
        $session->display_status = match ($group) {
            'past' => 'Completed',
            'current' => 'Ongoing',
            'upcoming' => 'Upcoming',
            default => 'Schedule pending',
        };
        $session->display_status_class = match ($group) {
            'past' => 'completed',
            'current' => 'ongoing',
            'upcoming' => 'upcoming',
            default => 'not-recorded',
        };
        $session->schedule_sort = ($date?->format('Y-m-d') ?? '9999-12-31').' '.($session->start_time ?: '00:00:00').' '.str_pad((string) $session->session_id, 12, '0', STR_PAD_LEFT);
        $session->date_label = $date?->format('d M Y') ?? 'Date not set';
        $session->short_date_label = $date?->format('d M') ?? '—';
        $session->time_label = $this->timeLabel($session->start_time ?? ($session->time ?? null), $session->end_time ?? null);

        return $session;
    }

    private function participantStats(string $courseId): array
    {
        $gender = $this->participantGenderExpression();
        $row = $this->participantQuery($courseId)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN $gender = 'Male' THEN 1 ELSE 0 END) as male_count")
            ->selectRaw("SUM(CASE WHEN $gender = 'Female' THEN 1 ELSE 0 END) as female_count")
            ->selectRaw("SUM(CASE WHEN COALESCE(TRIM($gender), '') NOT IN ('Male', 'Female') THEN 1 ELSE 0 END) as unspecified_count")
            ->selectRaw("SUM(CASE WHEN enrollment.participant_type = 'Teacher' THEN 1 ELSE 0 END) as teacher_count")
            ->selectRaw("SUM(CASE WHEN enrollment.participant_type = 'Guru New' THEN 1 ELSE 0 END) as new_teacher_count")
            ->selectRaw("SUM(CASE WHEN enrollment.participant_type = 'Outsider' THEN 1 ELSE 0 END) as public_count")
            ->selectRaw("SUM(CASE WHEN enrollment.participant_type = 'Staff' THEN 1 ELSE 0 END) as staff_count")
            ->first();

        return [
            'total' => (int) ($row->total_count ?? 0),
            'male' => (int) ($row->male_count ?? 0),
            'female' => (int) ($row->female_count ?? 0),
            'unspecified' => (int) ($row->unspecified_count ?? 0),
            'teacher' => (int) ($row->teacher_count ?? 0),
            'new_teacher' => (int) ($row->new_teacher_count ?? 0),
            'public' => (int) ($row->public_count ?? 0),
            'staff' => (int) ($row->staff_count ?? 0),
        ];
    }

    private function participantQuery(string $courseId): Builder
    {
        $course = SharedTrainingSchema::course($courseId);
        $query = CanonicalEnrollment::query('enrollment')
            ->leftJoin('teacher', 'enrollment.teacher_id', '=', 'teacher.teacherID')
            ->leftJoin('guru_new as guru_identity', 'enrollment.applicant_id', '=', 'guru_identity.gn_id')
            ->leftJoin('outsider as outsider_identity', 'enrollment.outsider_id', '=', 'outsider_identity.outsider_id');
        if (Schema::hasTable('staff_edu')) {
            $query->leftJoin('staff_edu as staff_identity', 'enrollment.staff_id', '=', 'staff_identity.staffID');
        }

        return SharedTrainingSchema::constrainEnrollmentToCourse($query, $course);
    }

    private function attendanceParticipantQuery(string $courseId, string $sessionId): Builder
    {
        $query = $this->participantQuery($courseId);
        if (Schema::hasTable('attendance')) {
            $query->leftJoin('attendance as teacher_attendance', function ($join) use ($sessionId): void {
                $join->on('teacher_attendance.teacher_id', '=', 'enrollment.teacher_id')
                    ->where('teacher_attendance.session_id', '=', $sessionId);
            });
        }
        if (Schema::hasTable('attendance_guru_baru')) {
            $query->leftJoin('attendance_guru_baru as guru_attendance', function ($join) use ($sessionId): void {
                $join->on('guru_attendance.gn_id', '=', 'enrollment.applicant_id')
                    ->where('guru_attendance.session_id', '=', $sessionId);
            });
        }
        if (Schema::hasTable('attendance_outsider')) {
            $query->leftJoin('attendance_outsider as outsider_attendance', function ($join) use ($sessionId): void {
                $join->on('outsider_attendance.outsider_id', '=', 'enrollment.outsider_id')
                    ->where('outsider_attendance.session_id', '=', $sessionId);
            });
        }
        if (Schema::hasTable('attendance_staff')) {
            $query->leftJoin('attendance_staff as staff_attendance', function ($join) use ($sessionId): void {
                $join->on('staff_attendance.staffID', '=', 'enrollment.staff_id')
                    ->where('staff_attendance.session_id', '=', $sessionId);
            });
        }

        return $query;
    }

    private function participantSelect(bool $includeEnrolledAt): array
    {
        $select = [
            'enrollment.enrollment_id',
            'enrollment.participant_type',
            DB::raw($this->participantNameExpression().' as participant_name'),
            DB::raw($this->participantEmailExpression().' as participant_email'),
            DB::raw($this->participantPhoneExpression().' as participant_phone'),
            DB::raw($this->participantOrganisationExpression().' as participant_organisation'),
            DB::raw($this->participantGenderExpression().' as participant_gender'),
        ];
        if ($includeEnrolledAt) {
            $select[] = 'enrollment.enrolled_at';
        }

        return $select;
    }

    private function participantNameExpression(): string
    {
        $staffName = Schema::hasTable('staff_edu') ? 'staff_identity.staffName' : "'Unknown Staff'";

        return "COALESCE(NULLIF(enrollment.participant_name, ''), CASE WHEN enrollment.participant_type = 'Teacher' THEN teacher.teacherName WHEN enrollment.participant_type = 'Guru New' THEN guru_identity.gn_name WHEN enrollment.participant_type = 'Outsider' THEN outsider_identity.name WHEN enrollment.participant_type = 'Staff' THEN {$staffName} ELSE 'Unknown Participant' END)";
    }

    private function participantEmailExpression(): string
    {
        $staffEmail = Schema::hasTable('staff_edu') ? 'staff_identity.email' : 'NULL';

        return "COALESCE(NULLIF(enrollment.participant_email, ''), CASE WHEN enrollment.participant_type = 'Teacher' THEN teacher.email WHEN enrollment.participant_type = 'Guru New' THEN guru_identity.email WHEN enrollment.participant_type = 'Staff' THEN {$staffEmail} ELSE NULL END)";
    }

    private function participantPhoneExpression(): string
    {
        $teacherPhone = $this->identityColumn('teacher', 'teacher', 'phoneNumber');
        $newTeacherPhone = $this->identityColumn('guru_new', 'guru_identity', 'phone_number');
        $outsiderPhone = $this->identityColumn('outsider', 'outsider_identity', 'phoneNumber');
        $outsiderContact = $this->identityColumn('outsider', 'outsider_identity', 'contact_no');
        $staffPhone = $this->identityColumn('staff_edu', 'staff_identity', 'phoneNumber');

        return "COALESCE(NULLIF(enrollment.participant_phone, ''), CASE WHEN enrollment.participant_type = 'Teacher' THEN {$teacherPhone} WHEN enrollment.participant_type = 'Guru New' THEN {$newTeacherPhone} WHEN enrollment.participant_type = 'Outsider' THEN COALESCE({$outsiderPhone}, {$outsiderContact}) WHEN enrollment.participant_type = 'Staff' THEN {$staffPhone} ELSE NULL END)";
    }

    private function participantOrganisationExpression(): string
    {
        $outsiderOrganisation = $this->identityColumn('outsider', 'outsider_identity', 'organization');
        $staffDepartment = $this->identityColumn('staff_edu', 'staff_identity', 'department');

        return "COALESCE(NULLIF(enrollment.participant_organisation, ''), CASE WHEN enrollment.participant_type = 'Outsider' THEN {$outsiderOrganisation} WHEN enrollment.participant_type = 'Staff' THEN {$staffDepartment} ELSE NULL END)";
    }

    private function participantGenderExpression(): string
    {
        $staffGender = Schema::hasTable('staff_edu') ? 'staff_identity.gender' : 'NULL';

        return "CASE WHEN enrollment.participant_type = 'Teacher' THEN teacher.gender WHEN enrollment.participant_type = 'Guru New' THEN guru_identity.gender WHEN enrollment.participant_type = 'Outsider' THEN outsider_identity.gender WHEN enrollment.participant_type = 'Staff' THEN {$staffGender} ELSE NULL END";
    }

    private function decorateParticipant(object $participant): object
    {
        $name = trim((string) ($participant->participant_name ?? '')) ?: 'Unknown Participant';
        $participant->participant_name = $name;
        $participant->initial = mb_strtoupper(mb_substr($name, 0, 1));
        $participant->participant_type_label = ParticipantTypeLabel::display($participant->participant_type);

        return $participant;
    }

    private function identityColumn(string $table, string $alias, string $column): string
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column)
            ? $alias.'.'.$column
            : 'NULL';
    }

    private function attendanceRecordExpression(): string
    {
        $columns = [];
        if (Schema::hasTable('attendance')) {
            $columns[] = 'teacher_attendance.attendance_id';
        }
        if (Schema::hasTable('attendance_guru_baru')) {
            $columns[] = 'guru_attendance.attendGuruBaru_id';
        }
        if (Schema::hasTable('attendance_outsider')) {
            $columns[] = 'outsider_attendance.attendOutsider_id';
        }
        if (Schema::hasTable('attendance_staff')) {
            $columns[] = 'staff_attendance.attendanceStaff_id';
        }

        return $columns === [] ? 'NULL' : 'COALESCE('.implode(', ', $columns).', NULL)';
    }

    private function attendanceColumnExpression(string $column): string
    {
        $columns = [];
        foreach ([
            'attendance' => 'teacher_attendance',
            'attendance_guru_baru' => 'guru_attendance',
            'attendance_outsider' => 'outsider_attendance',
            'attendance_staff' => 'staff_attendance',
        ] as $table => $alias) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $columns[] = $alias.'.'.$column;
            }
        }

        return $columns === [] ? 'NULL' : 'COALESCE('.implode(', ', $columns).', NULL)';
    }

    private function materialTypeExpression(): string
    {
        return "CASE
            WHEN LOWER(original_file_name) LIKE '%.pdf' THEN 'PDF'
            WHEN LOWER(original_file_name) LIKE '%.doc' OR LOWER(original_file_name) LIKE '%.docx' THEN 'Document'
            WHEN LOWER(original_file_name) LIKE '%.ppt' OR LOWER(original_file_name) LIKE '%.pptx' THEN 'Presentation'
            WHEN LOWER(original_file_name) LIKE '%.xls' OR LOWER(original_file_name) LIKE '%.xlsx' THEN 'Spreadsheet'
            WHEN LOWER(original_file_name) LIKE '%.jpg' OR LOWER(original_file_name) LIKE '%.jpeg' OR LOWER(original_file_name) LIKE '%.png' OR LOWER(original_file_name) LIKE '%.webp' OR LOWER(original_file_name) LIKE '%.gif' THEN 'Image'
            WHEN LOWER(original_file_name) LIKE '%.mp4' OR LOWER(original_file_name) LIKE '%.mov' THEN 'Video'
            WHEN LOWER(original_file_name) LIKE '%.mp3' OR LOWER(original_file_name) LIKE '%.wav' THEN 'Audio'
            ELSE 'File' END";
    }

    private function paginate(Builder $query, array &$filters, string $path): LengthAwarePaginator
    {
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $filters['rows']));
        $filters['page'] = min($filters['page'], $lastPage);
        $items = $query->forPage($filters['page'], $filters['rows'])->get();

        return new LengthAwarePaginator($items, $total, $filters['rows'], $filters['page'], [
            'path' => $path,
            'pageName' => 'page',
        ]);
    }

    private function rows(Request $request): int
    {
        $rows = $request->integer('rows', 10);

        return in_array($rows, self::ROW_OPTIONS, true) ? $rows : 10;
    }

    private function emptyPaginator(int $rows, string $path): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $rows, 1, ['path' => $path, 'pageName' => 'page']);
    }

    private function emptyAttendanceCounts(): array
    {
        return ['total' => 0, 'recorded' => 0, 'not_recorded' => 0];
    }

    private function carbonDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function carbonDateTime(string $date, mixed $time): ?Carbon
    {
        try {
            return Carbon::parse($date.' '.$time);
        } catch (Throwable) {
            return null;
        }
    }

    private function timeLabel(mixed $startValue, mixed $endValue): string
    {
        try {
            $start = $startValue ? Carbon::parse($startValue)->format('h:i A') : null;
            $end = $endValue ? Carbon::parse($endValue)->format('h:i A') : null;
        } catch (Throwable) {
            return 'Time not set';
        }

        return $start && $end ? $start.' – '.$end : ($start ?: 'Time not set');
    }
}
