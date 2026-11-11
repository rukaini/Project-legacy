<?php

namespace App\Services\Lms;

use App\Services\AttendanceQrService;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\SharedTrainingSchema;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainerLmsReportService
{
    private const ROW_OPTIONS = [10, 25, 50];

    private const ATTENDANCE_STATUS_OPTIONS = ['all', 'recorded', 'not-recorded'];

    public function __construct(private readonly AttendanceQrService $attendanceQrs) {}

    public function participants(string $courseId, Request $request): array
    {
        $filters = $this->participantFilters($request);
        $accessTableExists = Schema::hasTable('training_access_log');
        $baseQuery = $this->participantQuery($courseId, $accessTableExists);
        $gender = $this->participantGenderExpression();
        $lastAccess = $accessTableExists ? 'last_access.last_accessed_at' : 'NULL';

        $stats = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN $gender = 'Male' THEN 1 ELSE 0 END) as male_count")
            ->selectRaw("SUM(CASE WHEN $gender = 'Female' THEN 1 ELSE 0 END) as female_count")
            ->selectRaw("SUM(CASE WHEN $lastAccess IS NOT NULL THEN 1 ELSE 0 END) as accessed_count")
            ->first();

        $participantStats = [
            'total' => (int) ($stats->total_count ?? 0),
            'male' => (int) ($stats->male_count ?? 0),
            'female' => (int) ($stats->female_count ?? 0),
            'accessed' => (int) ($stats->accessed_count ?? 0),
        ];
        $participantStats['never_accessed'] = max(0, $participantStats['total'] - $participantStats['accessed']);

        $letters = (clone $baseQuery)
            ->selectRaw('UPPER(SUBSTR('.$this->participantNameExpression().', 1, 1)) as initial_letter')
            ->distinct()
            ->pluck('initial_letter')
            ->filter(fn ($letter): bool => preg_match('/^[A-Z]$/', (string) $letter) === 1)
            ->sort()
            ->values();

        if ($filters['letter'] !== '' && ! $letters->contains($filters['letter'])) {
            $filters['letter'] = '';
        }

        $query = clone $baseQuery;
        if ($filters['search'] !== '') {
            $needle = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function (Builder $searchQuery) use ($needle): void {
                $searchQuery
                    ->whereRaw('LOWER('.$this->participantNameExpression().') LIKE ?', [$needle])
                    ->orWhereRaw('LOWER('.$this->participantEmailExpression().') LIKE ?', [$needle]);
            });
        }
        if ($filters['gender'] !== '') {
            $query->whereRaw($gender.' = ?', [$filters['gender']]);
        }
        if ($filters['access'] === 'accessed') {
            $accessTableExists ? $query->whereNotNull('last_access.last_accessed_at') : $query->whereRaw('1 = 0');
        } elseif ($filters['access'] === 'never') {
            $query->whereRaw($lastAccess.' IS NULL');
        }
        if ($filters['letter'] !== '') {
            $query->whereRaw('UPPER(SUBSTR('.$this->participantNameExpression().', 1, 1)) = ?', [$filters['letter']]);
        }

        $select = $this->participantSelect();
        $select[] = $accessTableExists
            ? DB::raw('last_access.last_accessed_at as last_accessed_at')
            : DB::raw('NULL as last_accessed_at');

        $paginator = $this->paginate(
            $query->select($select)->orderByRaw($this->participantNameExpression()),
            $filters,
            route('trainer.courses.participants', $courseId)
        );
        $paginator->setCollection($paginator->getCollection()->map(fn (object $participant): object => $this->decorateParticipant($participant)));

        return [
            'participantStats' => $participantStats,
            'participantPaginator' => $paginator,
            'participantFilters' => $filters,
            'participantLetters' => $letters,
        ];
    }

    public function attendance(string $courseId, Request $request): array
    {
        $filters = $this->attendanceFilters($request);
        $sessions = $this->sessions($courseId);
        $selectedSession = $filters['session_id'] !== ''
            ? $sessions->firstWhere('session_id', $filters['session_id'])
            : $sessions->first();
        $filters['session_id'] = (string) ($selectedSession->session_id ?? $filters['session_id']);

        if (! $selectedSession) {
            return [
                'sessions' => $sessions,
                'selectedSession' => null,
                'attendanceQr' => null,
                'attendanceCounts' => $this->emptyAttendanceCounts(),
                'attendancePaginator' => $this->emptyPaginator($filters['rows'], route('trainer.courses.attendance', $courseId)),
                'attendanceFilters' => $filters,
            ];
        }

        $baseQuery = $this->attendanceParticipantQuery($courseId, (string) $selectedSession->session_id);
        $recordExpression = $this->attendanceRecordExpression();
        $countRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN $recordExpression IS NOT NULL THEN 1 ELSE 0 END) as recorded_count")
            ->first();
        $attendanceCounts = [
            'total' => (int) ($countRow->total_count ?? 0),
            'recorded' => (int) ($countRow->recorded_count ?? 0),
        ];
        $attendanceCounts['not_recorded'] = max(0, $attendanceCounts['total'] - $attendanceCounts['recorded']);

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

        $select = $this->participantSelect();
        $select[] = DB::raw($recordExpression.' as attendance_record_id');
        $select[] = DB::raw($this->attendanceColumnExpression('created_at').' as self_recorded_at');

        $paginator = $this->paginate(
            $query->select($select)->orderByRaw($this->participantNameExpression()),
            $filters,
            route('trainer.courses.attendance', $courseId)
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

    private function participantFilters(Request $request): array
    {
        $gender = ucfirst(strtolower(trim($request->string('gender')->toString())));
        $access = strtolower(trim($request->string('access')->toString()));
        $letter = strtoupper(mb_substr(trim($request->string('letter')->toString()), 0, 1));

        return [
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 160),
            'gender' => in_array($gender, ['Male', 'Female'], true) ? $gender : '',
            'access' => in_array($access, ['accessed', 'never'], true) ? $access : '',
            'letter' => preg_match('/^[A-Z]$/', $letter) === 1 ? $letter : '',
            'rows' => $this->rows($request),
            'page' => max(1, $request->integer('page', 1)),
        ];
    }

    private function attendanceFilters(Request $request): array
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

    private function participantQuery(string $courseId, bool $includeAccess): Builder
    {
        $course = SharedTrainingSchema::course($courseId);
        $query = CanonicalEnrollment::query('enrollment');
        SharedTrainingSchema::joinEnrollmentIdentities($query);
        if ($course) {
            SharedTrainingSchema::constrainEnrollmentToCourse($query, $course);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($includeAccess) {
            $courseReferences = collect([$course?->course_id])
                ->filter(fn ($value): bool => trim((string) $value) !== '')
                ->values();
            $lastAccess = DB::table('training_access_log')
                ->whereIn('courseID', $courseReferences->all() ?: [''])
                ->selectRaw('enrollment_id, MAX(accessed_at) as last_accessed_at')
                ->groupBy('enrollment_id');
            $query->leftJoinSub($lastAccess, 'last_access', function ($join): void {
                $join->on('enrollment.enrollment_id', '=', 'last_access.enrollment_id');
            });
        }

        return $query;
    }

    private function attendanceParticipantQuery(string $courseId, string $sessionId): Builder
    {
        $query = $this->participantQuery($courseId, false);
        $session = SharedTrainingSchema::session($sessionId);
        $sessionReferences = collect([$session?->session_id])
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->values()
            ->all();

        if (Schema::hasTable('attendance')) {
            $query->leftJoin('attendance as teacher_attendance', function ($join) use ($sessionReferences): void {
                $join->on('teacher_attendance.teacher_id', '=', 'enrollment.teacher_id')
                    ->whereIn('teacher_attendance.session_id', $sessionReferences ?: ['']);
            });
        }
        if (Schema::hasTable('attendance_guru_baru')) {
            $query->leftJoin('attendance_guru_baru as guru_attendance', function ($join) use ($sessionReferences): void {
                $join->on('guru_attendance.gn_id', '=', 'enrollment.applicant_id')
                    ->whereIn('guru_attendance.session_id', $sessionReferences ?: ['']);
            });
        }
        if (Schema::hasTable('attendance_outsider')) {
            $query->leftJoin('attendance_outsider as outsider_attendance', function ($join) use ($sessionReferences): void {
                $join->on('outsider_attendance.outsider_id', '=', 'enrollment.outsider_id')
                    ->whereIn('outsider_attendance.session_id', $sessionReferences ?: ['']);
            });
        }
        if (Schema::hasTable('attendance_staff')) {
            $query->leftJoin('attendance_staff as staff_attendance', function ($join) use ($sessionReferences): void {
                $join->on('staff_attendance.staffID', '=', 'enrollment.staff_id')
                    ->whereIn('staff_attendance.session_id', $sessionReferences ?: ['']);
            });
        }

        return $query;
    }

    private function sessions(string $courseId): Collection
    {
        $course = SharedTrainingSchema::course($courseId);
        if (! $course) {
            return collect();
        }

        return SharedTrainingSchema::constrainSessionsToCourse(CanonicalTrainingQuery::sessions(), $course)
            ->orderByRaw(SharedTrainingSchema::sessionDateExpression())
            ->orderByRaw(SharedTrainingSchema::sessionStartExpression())
            ->get()
            ->map(fn (object $session): object => $this->decorateSession($session));
    }

    private function decorateSession(object $session): object
    {
        $date = $this->carbonDate($session->date ?? null);
        $start = $date && ! empty($session->start_time) ? $this->carbonDateTime($date, $session->start_time) : null;
        $end = $date && ! empty($session->end_time) ? $this->carbonDateTime($date, $session->end_time) : null;
        $now = Carbon::now();

        if (! $date) {
            $status = 'upcoming';
        } elseif ($date->isBefore($now->copy()->startOfDay()) || ($end && $now->greaterThan($end))) {
            $status = 'completed';
        } elseif ($date->isAfter($now->copy()->startOfDay()) || ($start && $now->lessThan($start))) {
            $status = 'upcoming';
        } else {
            $status = 'ongoing';
        }

        $session->session_id = (string) $session->session_id;
        $session->display_status = ucfirst($status);
        $session->display_status_class = $status;
        $session->status_group = $status === 'completed' ? 'completed' : 'upcoming';
        $session->date_label = $date?->format('d M Y') ?? 'Date not set';
        $session->short_date_label = $date?->format('M d') ?? '—';
        $session->time_label = $this->timeLabel($session->start_time ?? null, $session->end_time ?? null, $session->time ?? null);

        return $session;
    }

    private function participantSelect(): array
    {
        return [
            'enrollment.enrollment_id',
            'enrollment.participant_type',
            'enrollment.teacher_id',
            'enrollment.applicant_id',
            'enrollment.outsider_id',
            'enrollment.staff_id',
            DB::raw($this->participantNameExpression().' as participant_name'),
            DB::raw($this->participantEmailExpression().' as participant_email'),
            DB::raw($this->participantGenderExpression().' as participant_gender'),
        ];
    }

    private function participantNameExpression(): string
    {
        return SharedTrainingSchema::enrollmentParticipantNameExpression();
    }

    private function participantEmailExpression(): string
    {
        return SharedTrainingSchema::enrollmentParticipantEmailExpression();
    }

    private function participantGenderExpression(): string
    {
        return SharedTrainingSchema::enrollmentParticipantGenderExpression();
    }

    private function decorateParticipant(object $participant): object
    {
        $name = trim((string) ($participant->participant_name ?? '')) ?: 'Unknown Participant';
        $participant->participant_name = $name;
        $participant->initial = mb_strtoupper(mb_substr($name, 0, 1));
        $participant->participant_code = match ($participant->participant_type) {
            'Teacher' => (string) ($participant->teacher_id ?? '-'),
            'Guru New' => (string) ($participant->applicant_id ?? '-'),
            'Outsider' => (string) ($participant->outsider_id ?? '-'),
            'Staff' => (string) ($participant->staff_id ?? '-'),
            default => '-',
        };

        return $participant;
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
        try {
            return $value ? Carbon::parse($value)->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function carbonDateTime(Carbon $date, mixed $time): ?Carbon
    {
        try {
            return Carbon::parse($date->toDateString().' '.$time);
        } catch (Throwable) {
            return null;
        }
    }

    private function timeLabel(mixed $start, mixed $end, mixed $fallback): string
    {
        try {
            $startLabel = $start ? Carbon::parse($start)->format('h:i A') : null;
            $endLabel = $end ? Carbon::parse($end)->format('h:i A') : null;
        } catch (Throwable) {
            return $fallback ?: 'Time not set';
        }

        return $startLabel && $endLabel ? $startLabel.' - '.$endLabel : ($fallback ?: ($startLabel ?: 'Time not set'));
    }
}
