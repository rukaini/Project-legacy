<?php

namespace App\Services\Lms;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainerLmsSubmissionReportService
{
    private const ROW_OPTIONS = [10, 25, 50];

    private const STATUS_OPTIONS = ['all', 'need_review', 'reviewed', 'awaiting', 'late'];

    public function __construct(
        private readonly TrainerLmsSubmissionFileResolver $submissionFiles
    ) {}

    public function submissions(string $courseId, Request $request): array
    {
        $filters = $this->filters($request);
        $totalParticipants = DB::table('enroll_guru_baru')->where('course_id', $courseId)->count();
        $tasks = $this->tasks($courseId, $totalParticipants);

        if ($filters['assessment_id'] === 0 && $tasks->isNotEmpty()) {
            $filters['assessment_id'] = (int) $tasks->first()->assessment_id;
        }

        $selectedTask = $tasks->firstWhere('assessment_id', $filters['assessment_id']);
        $taskCounts = $this->emptyCounts($totalParticipants);
        $paginator = $this->emptyPaginator($filters['rows'], $courseId);

        if ($selectedTask) {
            $taskCounts = $this->taskCounts($courseId, (int) $selectedTask->assessment_id, $selectedTask);
            $paginator = $this->roster($courseId, $selectedTask, $filters);
            $selectedTask->downloadable_file_count = $this->downloadableFileCount(
                $courseId,
                (int) $selectedTask->assessment_id
            );
        }

        return [
            'submissionTasks' => $tasks,
            'selectedTask' => $selectedTask,
            'submissionPaginator' => $paginator,
            'submissionFilters' => $filters,
            'taskCounts' => $taskCounts,
            'totalParticipants' => $totalParticipants,
            'pendingSubmissionCount' => (int) $tasks->sum('need_review_count'),
        ];
    }

    public function pendingReviewCount(string $courseId): int
    {
        if (! $this->submissionTablesExist()) {
            return 0;
        }

        try {
            return DB::table('assessment_submission as submission')
                ->join('assessment', 'assessment.assessment_id', '=', 'submission.assessment_id')
                ->join('enroll_guru_baru as enrollment', 'enrollment.enrollment_id', '=', 'submission.enrollment_id')
                ->where('assessment.course_id', $courseId)
                ->where('enrollment.course_id', $courseId)
                ->whereRaw('LOWER(assessment.type) = ?', ['submission'])
                ->whereNotNull('submission.submitted_at')
                ->where(function (Builder $query): void {
                    $query->whereNull('submission.status')
                        ->orWhereRaw('LOWER(submission.status) <> ?', ['reviewed']);
                })
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public function taskCounts(string $courseId, int $assessmentId, ?object $task = null): array
    {
        $totalParticipants = DB::table('enroll_guru_baru')->where('course_id', $courseId)->count();
        $task ??= DB::table('assessment')
            ->where('course_id', $courseId)
            ->where('assessment_id', $assessmentId)
            ->whereRaw('LOWER(type) = ?', ['submission'])
            ->first();

        if (! $task) {
            return $this->emptyCounts($totalParticipants);
        }

        $baseQuery = $this->rosterBaseQuery($courseId, $assessmentId);
        $status = $this->statusExpression();
        $counts = (clone $baseQuery)
            ->selectRaw('COUNT(*) as all_count')
            ->selectRaw("SUM(CASE WHEN $status = 'need_review' THEN 1 ELSE 0 END) as need_review_count")
            ->selectRaw("SUM(CASE WHEN $status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count")
            ->selectRaw("SUM(CASE WHEN $status = 'awaiting' THEN 1 ELSE 0 END) as awaiting_count")
            ->first();

        $lateCount = 0;
        if ($task->available_until ?? null) {
            $lateCount = (clone $baseQuery)
                ->whereNotNull('submission.submitted_at')
                ->where('submission.submitted_at', '>', $task->available_until)
                ->count();
        }

        return [
            'all' => (int) ($counts->all_count ?? 0),
            'need_review' => (int) ($counts->need_review_count ?? 0),
            'reviewed' => (int) ($counts->reviewed_count ?? 0),
            'awaiting' => (int) ($counts->awaiting_count ?? 0),
            'late' => $lateCount,
        ];
    }

    private function tasks(string $courseId, int $totalParticipants): Collection
    {
        $tasks = DB::table('assessment')
            ->leftJoin('training_module', 'assessment.module_id', '=', 'training_module.module_id')
            ->where('assessment.course_id', $courseId)
            ->whereRaw('LOWER(assessment.type) = ?', ['submission'])
            ->select('assessment.*', 'training_module.module_title')
            ->orderByDesc('assessment.created_at')
            ->orderByDesc('assessment.assessment_id')
            ->get();

        $aggregates = collect();
        if ($tasks->isNotEmpty() && $this->submissionTablesExist()) {
            $aggregates = DB::table('assessment_submission as submission')
                ->join('enroll_guru_baru as enrollment', 'enrollment.enrollment_id', '=', 'submission.enrollment_id')
                ->where('enrollment.course_id', $courseId)
                ->whereIn('submission.assessment_id', $tasks->pluck('assessment_id'))
                ->groupBy('submission.assessment_id')
                ->select('submission.assessment_id')
                ->selectRaw('SUM(CASE WHEN submission.submitted_at IS NOT NULL THEN 1 ELSE 0 END) as submitted_count')
                ->selectRaw("SUM(CASE WHEN submission.submitted_at IS NOT NULL AND LOWER(COALESCE(submission.status, '')) = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count")
                ->selectRaw("SUM(CASE WHEN submission.submitted_at IS NOT NULL AND LOWER(COALESCE(submission.status, '')) <> 'reviewed' THEN 1 ELSE 0 END) as need_review_count")
                ->get()
                ->keyBy('assessment_id');
        }

        return $tasks->map(function (object $task) use ($aggregates, $totalParticipants): object {
            $counts = $aggregates->get($task->assessment_id);
            $task->submitted_count = (int) ($counts->submitted_count ?? 0);
            $task->reviewed_count = (int) ($counts->reviewed_count ?? 0);
            $task->need_review_count = (int) ($counts->need_review_count ?? 0);
            $task->awaiting_count = max(0, $totalParticipants - $task->submitted_count);
            [
                $task->display_state,
                $task->display_state_class,
                $task->display_state_icon,
                $task->display_state_message,
            ] = $this->taskDisplayState($task);

            return $task;
        });
    }

    private function roster(string $courseId, object $task, array &$filters): LengthAwarePaginator
    {
        $query = $this->rosterBaseQuery($courseId, (int) $task->assessment_id);
        $name = $this->participantNameExpression();
        $email = $this->participantEmailExpression();
        $status = $this->statusExpression();

        if ($filters['search'] !== '') {
            $needle = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function (Builder $searchQuery) use ($name, $email, $needle): void {
                $searchQuery->whereRaw("LOWER($name) LIKE ?", [$needle])
                    ->orWhereRaw("LOWER($email) LIKE ?", [$needle]);
            });
        }

        if (in_array($filters['status'], ['need_review', 'reviewed', 'awaiting'], true)) {
            $query->whereRaw("$status = ?", [$filters['status']]);
        } elseif ($filters['status'] === 'late') {
            if ($task->available_until ?? null) {
                $query->whereNotNull('submission.submitted_at')
                    ->where('submission.submitted_at', '>', $task->available_until);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $query->select(
            'enrollment.enrollment_id',
            DB::raw("'Guru New' as participant_type"),
            DB::raw("$name as participant_name"),
            DB::raw("$email as participant_email"),
            'submission.submission_id',
            'submission.submitted_file_path',
            'submission.original_file_name',
            'submission.submitted_at',
            'submission.status as stored_submission_status',
            DB::raw("$status as submission_status_key")
        )->orderByRaw($name);

        $paginator = $this->paginate($query, $filters, $courseId);
        $paginator->setCollection($paginator->getCollection()->map(
            fn (object $participant): object => $this->decorateParticipant($participant, $task, $courseId)
        ));

        return $paginator;
    }

    private function rosterBaseQuery(string $courseId, int $assessmentId): Builder
    {
        $query = DB::table('enroll_guru_baru as enrollment')
            ->join('guru_new', 'enrollment.gn_id', '=', 'guru_new.gn_id')
            ->where('enrollment.course_id', $courseId);

        if ($this->submissionTablesExist()) {
            $query->leftJoin('assessment_submission as submission', function ($join) use ($assessmentId): void {
                $join->on('submission.enrollment_id', '=', 'enrollment.enrollment_id')
                    ->where('submission.assessment_id', '=', $assessmentId);
            });
        } else {
            $query->leftJoin(DB::raw('(SELECT NULL AS enrollment_id, NULL AS submission_id, NULL AS submitted_file_path, NULL AS original_file_name, NULL AS submitted_at, NULL AS status) AS submission'), function ($join): void {
                $join->on('submission.enrollment_id', '=', 'enrollment.enrollment_id');
            });
        }

        return $query;
    }

    private function decorateParticipant(object $participant, object $task, string $courseId): object
    {
        $participant->submission_status = match ($participant->submission_status_key) {
            'reviewed' => 'Reviewed',
            'need_review' => 'Need Review',
            default => 'Awaiting',
        };
        $participant->submitted_label = $participant->submitted_at
            ? Carbon::parse($participant->submitted_at)->format('d M Y, h:i A')
            : 'Not Submitted';
        $participant->is_late = (bool) ($participant->submitted_at && ($task->available_until ?? null)
            && Carbon::parse($participant->submitted_at)->gt(Carbon::parse($task->available_until)));
        $participant->file_name = $this->submissionFiles->safeFileName(
            $participant->original_file_name ?? null,
            $participant->submitted_file_path ?? null
        );
        $participant->file_available = (bool) ($participant->submitted_at && $participant->submitted_file_path
            && $this->submissionFiles->exists((string) $participant->submitted_file_path));
        $participant->preview_url = $participant->file_available
            ? route('trainer.courses.submissions.preview', [$courseId, $participant->submission_id])
            : null;
        $participant->download_url = $participant->file_available
            ? route('trainer.courses.submissions.download', [$courseId, $participant->submission_id])
            : null;

        return $participant;
    }

    private function paginate(Builder $query, array &$filters, string $courseId): LengthAwarePaginator
    {
        $path = route('trainer.courses.submissions', $courseId);
        $paginator = $query->paginate($filters['rows'], ['*'], 'page', $filters['page'])->withPath($path);

        if ($paginator->total() > 0 && $paginator->currentPage() > $paginator->lastPage()) {
            $filters['page'] = $paginator->lastPage();
            $paginator = $query->paginate($filters['rows'], ['*'], 'page', $filters['page'])->withPath($path);
        }

        return $paginator;
    }

    private function filters(Request $request): array
    {
        $status = strtolower(trim($request->string('status')->toString()));
        $rows = $request->integer('rows', 10);

        return [
            'assessment_id' => max(0, $request->integer('assessment_id')),
            'status' => in_array($status, self::STATUS_OPTIONS, true) ? $status : 'all',
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 160),
            'rows' => in_array($rows, self::ROW_OPTIONS, true) ? $rows : 10,
            'page' => max(1, $request->integer('page', 1)),
        ];
    }

    private function taskDisplayState(object $task): array
    {
        $now = Carbon::now();
        $opens = ($task->available_from ?? null) ? Carbon::parse($task->available_from) : null;
        $closes = ($task->available_until ?? null) ? Carbon::parse($task->available_until) : null;

        if ($opens && $now->lt($opens)) {
            return [
                'Not Open Yet',
                'is-not-open',
                'bi-clock',
                'Opens '.$opens->format('d M Y, h:i A').'.',
            ];
        }

        if ($closes && $now->gt($closes)) {
            return [
                'Closed',
                'is-closed',
                'bi-lock-fill',
                'Submission period closed on '.$closes->format('d M Y, h:i A').'.',
            ];
        }

        return [
            'Open',
            'is-open',
            'bi-check-circle-fill',
            $closes
                ? 'Open for submissions until '.$closes->format('d M Y, h:i A').'.'
                : 'Open for submissions.',
        ];
    }

    private function statusExpression(): string
    {
        return "CASE
            WHEN submission.submitted_at IS NULL THEN 'awaiting'
            WHEN LOWER(COALESCE(submission.status, '')) = 'reviewed' THEN 'reviewed'
            ELSE 'need_review'
        END";
    }

    private function participantNameExpression(): string
    {
        return "COALESCE(guru_new.gn_name, 'Unknown Participant')";
    }

    private function participantEmailExpression(): string
    {
        return "COALESCE(guru_new.email, '-')";
    }

    private function downloadableFileCount(string $courseId, int $assessmentId): int
    {
        if (! $this->submissionTablesExist()) {
            return 0;
        }

        $paths = DB::table('assessment_submission as submission')
            ->join('enroll_guru_baru as enrollment', 'enrollment.enrollment_id', '=', 'submission.enrollment_id')
            ->where('enrollment.course_id', $courseId)
            ->where('submission.assessment_id', $assessmentId)
            ->whereNotNull('submission.submitted_at')
            ->whereNotNull('submission.submitted_file_path')
            ->select('submission.submitted_file_path')
            ->cursor();

        foreach ($paths as $path) {
            if ($this->submissionFiles->exists((string) $path->submitted_file_path)) {
                return 1;
            }
        }

        return 0;
    }

    private function submissionTablesExist(): bool
    {
        return Schema::hasTable('assessment_submission')
            && Schema::hasTable('assessment')
            && Schema::hasTable('enroll_guru_baru');
    }

    private function emptyCounts(int $total): array
    {
        return ['all' => $total, 'need_review' => 0, 'reviewed' => 0, 'awaiting' => $total, 'late' => 0];
    }

    private function emptyPaginator(int $rows, string $courseId): LengthAwarePaginator
    {
        return (new LengthAwarePaginator([], 0, $rows, 1))->withPath(route('trainer.courses.submissions', $courseId));
    }
}
