<?php

namespace App\Services\Lms;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TrainerLmsLearnerTrackingReportService
{
    private const ACTIVITY_UNION_COLLATION = 'utf8mb4_unicode_ci';

    public function __construct(
        private readonly TrainerLmsMaterialAuthoringService $materials
    ) {}

    /**
     * This service is deliberately read-only. The optional interaction tables
     * are an integration boundary and may not exist in the standalone system.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(string $courseId, array $filters): array
    {
        $tab = $this->tab($filters['tab'] ?? 'overview');
        $pivot = ($filters['pivot'] ?? 'participant') === 'module' ? 'module' : 'participant';
        $requestedRows = (int) ($filters['rows'] ?? 10);
        $rows = in_array($requestedRows, [10, 25, 50], true)
            ? $requestedRows
            : 10;
        $search = trim((string) ($filters['search'] ?? ''));
        $participantSearch = trim((string) ($filters['participant_search'] ?? ''));
        $period = $this->period($filters);

        $tracking = $this->trackingContract();
        $quizAttemptsAvailable = $this->quizAttemptContract();
        $participants = collect();
        $selectedParticipantId = null;
        $selectedParticipant = null;
        $modules = collect();
        $trackableBlocks = collect();
        $selectedModuleId = null;
        $selectedModule = null;
        $selectedMaterialId = null;
        $quizzes = collect();
        $selectedQuizId = null;
        $selectedQuiz = null;
        $overview = ['metrics' => ['materials_accessed' => null, 'quiz_attempts' => null, 'last_activity' => null, 'tracked_seconds' => null], 'events' => $this->emptyPaginator($rows, 'activity_page')];
        $engagement = ['rows' => $this->emptyPaginator($rows, 'engagement_page'), 'mode' => $pivot];
        $quizTracking = ['available' => $quizAttemptsAvailable, 'rows' => $this->emptyPaginator($rows, 'quiz_page'), 'attempts' => collect(), 'detail' => null, 'summary' => ['attempted' => 0, 'not_attempted' => 0, 'total_attempts' => 0, 'latest_attempt' => null]];

        if ($tab === 'overview' || ($tab === 'engagement' && $pivot === 'participant')) {
            $participantQuery = $this->participantQuery($courseId);
            $selectedParticipantId = $this->validSelectedId(clone $participantQuery, 'enrollment.enrollment_id', $filters['participant'] ?? null);
            $participants = $this->participantNavigator($courseId, $search, $selectedParticipantId);
            $selectedParticipant = $selectedParticipantId
                ? $this->participantQuery($courseId)->where('enrollment.enrollment_id', $selectedParticipantId)->first()
                : null;
        }
        if ($tab === 'engagement') {
            $requestedModuleId = filter_var($filters['module'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $selectedModuleId = $this->validSelectedId(
                DB::table('training_module')->where('course_id', $courseId),
                'module_id',
                $requestedModuleId
            );
            $selectedModule = $selectedModuleId
                ? DB::table('training_module')->where('course_id', $courseId)->where('module_id', $selectedModuleId)->first()
                : null;
            $modules = $this->modules($courseId, $pivot === 'module' ? $search : '', $requestedModuleId);
            $trackableBlocks = $this->trackableBlocks(
                $courseId,
                $modules->pluck('module_id')->push($selectedModuleId)->filter()->unique()->values()
            );
            $requestedMaterialId = filter_var($filters['material'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $selectedMaterialId = $requestedMaterialId
                && $trackableBlocks->where('module_id', $selectedModuleId)->contains('block_id', (int) $requestedMaterialId)
                    ? (int) $requestedMaterialId
                    : null;
        }
        if ($tab === 'quiz') {
            $requestedQuizId = filter_var($filters['quiz'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $selectedQuizId = $this->validSelectedId($this->quizQuery($courseId), 'assessment_id', $requestedQuizId);
            $quizzes = $this->quizzes($courseId, $search, $search === '' ? $selectedQuizId : null);
            $selectedQuiz = $quizzes->firstWhere('assessment_id', $selectedQuizId)
                ?? $this->quizById($courseId, $selectedQuizId);
        }

        if ($tab === 'overview') {
            $overview = $this->overview($courseId, $selectedParticipant, $tracking, $rows, $filters, $period);
        } elseif ($tab === 'engagement') {
            $engagement = $pivot === 'module'
                ? $this->engagementByModule($courseId, $selectedModuleId, $selectedMaterialId, $tracking, $rows, $filters, $period)
                : $this->engagementByParticipant($courseId, $selectedParticipantId, $tracking, $rows, $filters, $period);
        } else {
            $quizTracking = $this->quizTracking($courseId, $selectedQuiz, $rows, $filters, $period);
        }

        return compact(
            'tab',
            'pivot',
            'rows',
            'search',
            'participantSearch',
            'participants',
            'selectedParticipantId',
            'selectedParticipant',
            'modules',
            'trackableBlocks',
            'selectedModuleId',
            'selectedModule',
            'selectedMaterialId',
            'quizzes',
            'selectedQuizId',
            'selectedQuiz',
            'tracking',
            'quizAttemptsAvailable',
            'period',
            'overview',
            'engagement',
            'quizTracking'
        );
    }

    /** @return array{available: bool, duration: bool, reason: string|null} */
    private function trackingContract(): array
    {
        if (! Schema::hasTable('training_content_interaction_log')) {
            return ['available' => false, 'duration' => false, 'reason' => 'Material activity is not recorded by this standalone system yet.'];
        }

        $required = ['enrollment_id', 'block_id'];
        foreach ($required as $column) {
            if (! Schema::hasColumn('training_content_interaction_log', $column)) {
                return ['available' => false, 'duration' => false, 'reason' => 'The connected material activity source is not compatible with this report.'];
            }
        }

        return [
            'available' => true,
            'duration' => Schema::hasColumn('training_content_interaction_log', 'total_seconds'),
            'reason' => null,
        ];
    }

    private function quizAttemptContract(): bool
    {
        if (! Schema::hasTable('assessment_submission')) {
            return false;
        }

        foreach (['submission_id', 'assessment_id', 'enrollment_id', 'grade', 'submitted_at'] as $column) {
            if (! Schema::hasColumn('assessment_submission', $column)) {
                return false;
            }
        }

        return true;
    }

    private function overview(
        string $courseId,
        ?object $participant,
        array $tracking,
        int $rows,
        array $filters,
        array $period
    ): array {
        $metrics = [
            'materials_accessed' => null,
            'quiz_attempts' => null,
            'last_activity' => null,
            'tracked_seconds' => null,
        ];

        if (! $participant) {
            return ['metrics' => $metrics, 'events' => $this->emptyPaginator($rows, 'activity_page')];
        }

        $eventQueries = [];
        if ($tracking['available']) {
            $participantLogs = $this->interactionAggregate($courseId, $period)
                ->where('log.enrollment_id', $participant->enrollment_id);
            $accessed = (clone $participantLogs)->where(function (Builder $query): void {
                $query->where('log.open_count', '>', 0)->orWhereNotNull('log.last_accessed_at');
            });
            $metrics['materials_accessed'] = (clone $accessed)->distinct()->count('log.block_id');
            $metrics['tracked_seconds'] = $tracking['duration'] ? (int) (clone $accessed)->sum('log.total_seconds') : null;
            $eventQueries[] = (clone $participantLogs)
                ->whereNotNull('log.last_accessed_at')
                ->select(
                    $this->activityUnionTextExpression("'material'", 'kind'),
                    $this->activityUnionCoalescedTextExpression([
                        'training_content_block.block_title',
                        'learning_material.original_file_name',
                        "'Untitled material'",
                    ], 'title'),
                    $this->activityUnionTextExpression('training_module.module_title', 'context'),
                    'log.last_accessed_at as activity_at',
                    'log.open_count',
                    DB::raw('NULL as grade')
                );
        }

        if ($this->quizAttemptContract()) {
            $attempts = $this->quizAttemptsQuery($courseId, $period)
                ->where('assessment_submission.enrollment_id', $participant->enrollment_id);
            $metrics['quiz_attempts'] = (clone $attempts)->count();
            $eventQueries[] = (clone $attempts)
                ->select(
                    $this->activityUnionTextExpression("'quiz'", 'kind'),
                    $this->activityUnionTextExpression('assessment.title', 'title'),
                    $this->activityUnionTextExpression("'Quiz'", 'context'),
                    'assessment_submission.submitted_at as activity_at',
                    DB::raw('NULL as open_count'),
                    'assessment_submission.grade'
                );
        }
        $page = max(1, (int) ($filters['activity_page'] ?? 1));
        if ($eventQueries === []) {
            return ['metrics' => $metrics, 'events' => $this->emptyPaginator($rows, 'activity_page')];
        }

        $eventsUnion = array_shift($eventQueries);
        foreach ($eventQueries as $eventQuery) {
            $eventsUnion->unionAll($eventQuery);
        }
        $eventsQuery = DB::query()->fromSub($eventsUnion, 'activity')->whereNotNull('activity_at')->orderByDesc('activity_at');
        $metrics['last_activity'] = (clone $eventsQuery)->reorder()->max('activity_at');
        $events = $this->paginateBuilder($eventsQuery, $rows, $page, 'activity_page');
        $events->getCollection()->transform(function (object $event): object {
            $event->title = ($event->kind === 'quiz' ? 'Attempted ' : 'Accessed ').$event->title;
            $event->meta = $event->kind === 'quiz'
                ? ($event->grade === null ? 'Not graded' : 'Mark: '.$event->grade)
                : ((int) $event->open_count).' open'.((int) $event->open_count === 1 ? '' : 's');

            return $event;
        });

        return [
            'metrics' => $metrics,
            'events' => $events,
        ];
    }

    private function activityUnionTextExpression(string $expression, string $alias): Expression
    {
        return DB::raw($this->activityUnionTextSql($expression, $alias));
    }

    /**
     * MySQL resolves every textual UNION position to one collation before it
     * evaluates the outer query. Legacy Materials columns use 0900_ai_ci while
     * Assessment and connection strings use unicode_ci, so each branch must
     * explicitly present the same character set and collation.
     */
    private function activityUnionTextSql(string $expression, string $alias, ?string $driver = null): string
    {
        $driver ??= DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return "{$expression} as {$alias}";
        }

        return $this->mysqlNormalizedTextSql($expression)." as {$alias}";
    }

    /** @param  list<string>  $expressions */
    private function activityUnionCoalescedTextExpression(array $expressions, string $alias): Expression
    {
        return DB::raw($this->activityUnionCoalescedTextSql($expressions, $alias));
    }

    /** @param  list<string>  $expressions */
    private function activityUnionCoalescedTextSql(array $expressions, string $alias, ?string $driver = null): string
    {
        $driver ??= DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            $parts = array_map(
                fn (string $expression): string => "NULLIF({$expression}, '')",
                array_slice($expressions, 0, -1)
            );
            $parts[] = $expressions[array_key_last($expressions)];

            return 'COALESCE('.implode(', ', $parts).") as {$alias}";
        }

        $empty = $this->mysqlNormalizedTextSql("''");
        $parts = array_map(
            fn (string $expression): string => 'NULLIF('.$this->mysqlNormalizedTextSql($expression).", {$empty})",
            array_slice($expressions, 0, -1)
        );
        $parts[] = $this->mysqlNormalizedTextSql($expressions[array_key_last($expressions)]);

        return 'COALESCE('.implode(', ', $parts).') COLLATE '.self::ACTIVITY_UNION_COLLATION." as {$alias}";
    }

    private function mysqlNormalizedTextSql(string $expression): string
    {
        return "CONVERT(({$expression}) USING utf8mb4) COLLATE ".self::ACTIVITY_UNION_COLLATION;
    }

    private function engagementByParticipant(
        string $courseId,
        ?int $participantId,
        array $tracking,
        int $rows,
        array $filters,
        array $period
    ): array {
        if (! $participantId) {
            return ['rows' => $this->emptyPaginator($rows, 'engagement_page'), 'mode' => 'participant'];
        }

        $query = $this->trackableBlockQuery($courseId);
        if ($tracking['available']) {
            $aggregate = $this->interactionAggregateSubquery($period);
            $query->leftJoinSub($aggregate, 'interaction', function ($join) use ($participantId): void {
                $join->on('interaction.block_id', '=', 'training_content_block.block_id')
                    ->where('interaction.enrollment_id', '=', $participantId);
            });
            $query->addSelect(
                DB::raw('COALESCE(interaction.open_count, 0) as open_count'),
                'interaction.first_opened_at',
                'interaction.last_accessed_at',
                'interaction.total_seconds'
            );
        } else {
            $query->addSelect(
                DB::raw('0 as open_count'),
                DB::raw('NULL as first_opened_at'),
                DB::raw('NULL as last_accessed_at'),
                DB::raw('NULL as total_seconds')
            );
        }

        if (! empty($filters['module'])) {
            $query->where('training_module.module_id', (int) $filters['module']);
        }

        $page = max(1, (int) ($filters['engagement_page'] ?? 1));
        $result = $this->paginateBuilder($query, $rows, $page, 'engagement_page');
        $result->getCollection()->transform(fn (object $row): object => $this->normalizeEngagementRow($row, $tracking));

        return ['rows' => $result, 'mode' => 'participant'];
    }

    private function engagementByModule(
        string $courseId,
        ?int $moduleId,
        ?int $materialId,
        array $tracking,
        int $rows,
        array $filters,
        array $period
    ): array {
        if (! $moduleId) {
            return ['rows' => $this->emptyPaginator($rows, 'engagement_page'), 'mode' => 'module'];
        }

        $blocks = $this->trackableBlockQuery($courseId)->where('training_module.module_id', $moduleId);
        if ($materialId) {
            $blocks->where('training_content_block.block_id', $materialId);
        }

        $query = $this->participantQuery($courseId)
            ->crossJoinSub($blocks, 'content')
            ->addSelect(
                'content.block_id',
                'content.module_id',
                'content.module_title',
                'content.content_title',
                'content.block_type',
                'content.original_file_name'
            );

        if ($tracking['available']) {
            $query->leftJoinSub($this->interactionAggregateSubquery($period), 'interaction', function ($join): void {
                $join->on('interaction.block_id', '=', 'content.block_id')
                    ->on('interaction.enrollment_id', '=', 'enrollment.enrollment_id');
            })->addSelect(
                DB::raw('COALESCE(interaction.open_count, 0) as open_count'),
                'interaction.first_opened_at',
                'interaction.last_accessed_at',
                'interaction.total_seconds'
            );
        } else {
            $query->addSelect(DB::raw('0 as open_count'), DB::raw('NULL as first_opened_at'), DB::raw('NULL as last_accessed_at'), DB::raw('NULL as total_seconds'));
        }

        $query->orderBy('enrollment.enrollment_id')->orderBy('content.block_order')->orderBy('content.block_id');
        $page = max(1, (int) ($filters['engagement_page'] ?? 1));
        $result = $this->paginateBuilder($query, $rows, $page, 'engagement_page');
        $result->getCollection()->transform(fn (object $row): object => $this->normalizeEngagementRow($row, $tracking));

        return ['rows' => $result, 'mode' => 'module'];
    }

    private function quizTracking(string $courseId, ?object $quiz, int $rows, array $filters, array $period): array
    {
        if (! $this->quizAttemptContract()) {
            return ['available' => false, 'rows' => $this->emptyPaginator($rows, 'quiz_page'), 'attempts' => collect(), 'detail' => null, 'summary' => ['attempted' => null, 'not_attempted' => null, 'total_attempts' => null, 'latest_attempt' => null]];
        }
        if (! $quiz) {
            return ['available' => true, 'rows' => $this->emptyPaginator($rows, 'quiz_page'), 'attempts' => collect(), 'detail' => null, 'summary' => ['attempted' => 0, 'not_attempted' => 0, 'total_attempts' => 0, 'latest_attempt' => null]];
        }

        $attemptAggregate = DB::table('assessment_submission')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('assessment_submission.assessment_id', $quiz->assessment_id)
            ->where('enrollment.course_id', $courseId)
            ->select('assessment_submission.enrollment_id')
            ->selectRaw('COUNT(*) as attempt_count')
            ->selectRaw('MAX(assessment_submission.submitted_at) as latest_attempt')
            ->groupBy('assessment_submission.enrollment_id');
        $this->applyPeriod($attemptAggregate, 'assessment_submission.submitted_at', $period);

        $query = $this->participantQuery($courseId)
            ->leftJoinSub($attemptAggregate, 'attempts', 'attempts.enrollment_id', '=', 'enrollment.enrollment_id')
            ->addSelect(
                DB::raw('COALESCE(attempts.attempt_count, 0) as attempt_count'),
                'attempts.latest_attempt'
            );
        $status = $filters['status'] ?? '';
        if ($status === 'attempted') {
            $query->whereNotNull('attempts.enrollment_id');
        } elseif ($status === 'not_attempted') {
            $query->whereNull('attempts.enrollment_id');
        }
        $this->applyParticipantSearch($query, trim((string) ($filters['participant_search'] ?? '')));

        $page = max(1, (int) ($filters['quiz_page'] ?? 1));
        $result = $this->paginateBuilder($query, $rows, $page, 'quiz_page');
        $enrollmentIds = $result->getCollection()->pluck('enrollment_id');
        $attemptRows = $enrollmentIds->isEmpty()
            ? collect()
            : $this->quizAttemptsQuery($courseId, $period)
                ->where('assessment_submission.assessment_id', $quiz->assessment_id)
                ->whereIn('assessment_submission.enrollment_id', $enrollmentIds)
                ->orderBy('assessment_submission.enrollment_id')
                ->orderBy('assessment_submission.submitted_at')
                ->orderBy('assessment_submission.submission_id')
                ->get()
                ->groupBy('enrollment_id')
                ->map(fn (Collection $items): Collection => $items->values()->map(function (object $attempt, int $index) use ($quiz): object {
                    $attempt->attempt_number = $index + 1;
                    $attempt->percentage = $attempt->grade !== null && (float) $quiz->total_marks > 0
                        ? round(((float) $attempt->grade / (float) $quiz->total_marks) * 100, 1)
                        : null;

                    return $attempt;
                }));

        $summaryRows = DB::table('assessment_submission')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('assessment_submission.assessment_id', $quiz->assessment_id)
            ->where('enrollment.course_id', $courseId)
            ->selectRaw('COUNT(*) as total_attempts, COUNT(DISTINCT assessment_submission.enrollment_id) as attempted, MAX(assessment_submission.submitted_at) as latest_attempt');
        $this->applyPeriod($summaryRows, 'assessment_submission.submitted_at', $period);
        $summaryRows = $summaryRows->first();
        $participantTotal = $this->participantQuery($courseId)->count();
        $attempted = (int) ($summaryRows->attempted ?? 0);

        return [
            'available' => true,
            'rows' => $result,
            'attempts' => $attemptRows,
            'detail' => $this->quizAttemptDetail($courseId, $quiz, $filters['attempt'] ?? null),
            'summary' => [
                'attempted' => $attempted,
                'not_attempted' => max(0, $participantTotal - $attempted),
                'total_attempts' => (int) ($summaryRows->total_attempts ?? 0),
                'latest_attempt' => $summaryRows->latest_attempt ?? null,
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function quizAttemptDetail(string $courseId, object $quiz, mixed $requestedSubmissionId): ?array
    {
        $submissionId = filter_var($requestedSubmissionId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        if (! $submissionId) {
            return null;
        }

        $columns = [
            'assessment_submission.submission_id',
            'assessment_submission.enrollment_id',
            'assessment_submission.grade',
            'assessment_submission.submitted_at',
        ];
        $answersAvailable = Schema::hasColumn('assessment_submission', 'answers_json');
        $columns[] = $answersAvailable
            ? 'assessment_submission.answers_json'
            : DB::raw('NULL as answers_json');

        $attempt = DB::table('assessment_submission')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('assessment_submission.submission_id', $submissionId)
            ->where('assessment_submission.assessment_id', $quiz->assessment_id)
            ->where('enrollment.course_id', $courseId)
            ->select($columns)
            ->first();
        if (! $attempt) {
            return null;
        }

        $participant = $this->participantQuery($courseId)
            ->where('enrollment.enrollment_id', $attempt->enrollment_id)
            ->first();
        $attempts = DB::table('assessment_submission')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('assessment_submission.assessment_id', $quiz->assessment_id)
            ->where('assessment_submission.enrollment_id', $attempt->enrollment_id)
            ->where('enrollment.course_id', $courseId)
            ->orderBy('assessment_submission.submitted_at')
            ->orderBy('assessment_submission.submission_id')
            ->get([
                'assessment_submission.submission_id',
                'assessment_submission.grade',
                'assessment_submission.submitted_at',
                $answersAvailable
                    ? 'assessment_submission.answers_json'
                    : DB::raw('NULL as answers_json'),
            ])
            ->values()
            ->map(function (object $item, int $index) use ($submissionId): object {
                $item->attempt_number = $index + 1;
                $item->selected = (int) $item->submission_id === $submissionId;

                return $item;
            });
        $selectedAttempt = $attempts->firstWhere('selected', true);

        $questionsAvailable = Schema::hasTable('assessment_question')
            && collect(['question_id', 'assessment_id', 'question_text', 'question_order', 'question_type', 'correct_answer', 'marks'])
                ->every(fn (string $column): bool => Schema::hasColumn('assessment_question', $column));
        $answers = $answersAvailable
            ? $this->decodedAttemptAnswers($attempt->answers_json)
            : null;
        $questions = $questionsAvailable
            ? DB::table('assessment_question')
                ->where('assessment_id', $quiz->assessment_id)
                ->orderBy('question_order')
                ->orderBy('question_id')
                ->get()
                ->values()
                ->map(function (object $question, int $index) use ($answers, $courseId, $quiz): object {
                    $order = (int) ($question->question_order ?: ($index + 1));
                    $question->review_order = $order;
                    $question->question_type = $this->normalizedQuestionType((string) $question->question_type);
                    $question->display_options = $this->decodedAnswerArray($question->options_json ?? null);
                    $learnerAnswer = $this->learnerAnswerForQuestion($answers, $question, $order);
                    $question->learner_answer = $this->displayLearnerAnswer(
                        $question->question_type,
                        $learnerAnswer,
                        $question->display_options
                    );
                    $question->display_correct_answer = $this->displayCanonicalAnswer(
                        $question->question_type,
                        $question->correct_answer ?? null,
                        $question->display_options
                    );
                    $question->answer_status = $this->questionAnswerStatus($question, $learnerAnswer);
                    $question->participant_answer_usable = $this->participantAnswerIsUsable(
                        $question->question_type,
                        $learnerAnswer,
                        $question->display_options
                    );
                    $question->answer_options = $this->answerOptions(
                        $question->question_type,
                        $question->display_options,
                        $learnerAnswer,
                        $question->correct_answer ?? null,
                        $question->answer_status
                    );
                    $question->image_url = ! empty($question->question_image_path)
                        ? route('trainer.courses.assessments.questions.image', [
                            'course_id' => $courseId,
                            'assessment_id' => $quiz->assessment_id,
                            'question_id' => $question->question_id,
                        ])
                        : null;

                    return $question;
                })
            : collect();
        $detailedAnswersAvailable = $answers !== null
            && $questions->contains(fn (object $question): bool => $question->participant_answer_usable);
        $attempts->each(function (object $item) use ($answersAvailable, $questions, $questionsAvailable): void {
            $itemAnswers = $answersAvailable
                ? $this->decodedAttemptAnswers($item->answers_json)
                : null;
            $item->detail_available = $questionsAvailable
                && $itemAnswers !== null
                && $questions->contains(function (object $question) use ($itemAnswers): bool {
                    $order = (int) $question->review_order;
                    $answer = $this->learnerAnswerForQuestion($itemAnswers, $question, $order);

                    return $this->participantAnswerIsUsable(
                        $question->question_type,
                        $answer,
                        $question->display_options
                    );
                });
            unset($item->answers_json);
        });
        $answerReview = $this->answerReviewTotal($questions, $detailedAnswersAvailable, $attempt->grade);

        return [
            'attempt' => $attempt,
            'attempt_number' => $selectedAttempt?->attempt_number,
            'attempts' => $attempts,
            'participant' => $participant,
            'answers_available' => $answersAvailable && $questionsAvailable,
            'answers_recorded' => $answers !== null,
            'detailed_answers_available' => $detailedAnswersAvailable,
            'answer_review' => $answerReview,
            'questions' => $questions,
        ];
    }

    /** @return array<string|int, mixed>|null */
    private function decodedAttemptAnswers(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && ! array_is_list($decoded) && $decoded !== []
            ? $decoded
            : null;
    }

    /** @param  list<string>  $options */
    private function participantAnswerIsUsable(string $type, mixed $answer, array $options): bool
    {
        if ($this->answerIsEmpty($answer)) {
            return false;
        }

        if (in_array($type, ['mcq', 'checkbox', 'true_false'], true)) {
            $resolved = $this->normalizedChoiceAnswer($answer, $this->canonicalChoiceOptions($type, $options));

            return $resolved !== null && $resolved !== [];
        }

        return $this->displayAnswer($answer) !== null;
    }

    /**
     * @return array{earned: float, maximum: float, matches_recorded: bool|null}|null
     */
    private function answerReviewTotal(Collection $questions, bool $detailedAnswersAvailable, mixed $recordedGrade): ?array
    {
        if (! $detailedAnswersAvailable || $questions->isEmpty()) {
            return null;
        }

        $objectiveStatuses = ['correct', 'incorrect', 'unanswered'];
        if ($questions->contains(fn (object $question): bool => ! in_array($question->question_type, ['mcq', 'checkbox', 'true_false'], true)
            || ! in_array($question->answer_status, $objectiveStatuses, true)
            || ! is_numeric($question->marks)
            || (float) $question->marks < 0)) {
            return null;
        }

        $earned = (float) $questions
            ->where('answer_status', 'correct')
            ->sum(fn (object $question): float => (float) $question->marks);
        $maximum = (float) $questions->sum(fn (object $question): float => (float) $question->marks);
        $matchesRecorded = is_numeric($recordedGrade)
            ? abs($earned - (float) $recordedGrade) < 0.00001
            : null;

        return [
            'earned' => $earned,
            'maximum' => $maximum,
            'matches_recorded' => $matchesRecorded,
        ];
    }

    /** @param  array<string|int, mixed>|null  $answers */
    private function learnerAnswerForQuestion(?array $answers, object $question, int $order): mixed
    {
        if ($answers === null) {
            return null;
        }

        foreach (['q'.$order, (string) $question->question_id] as $key) {
            if (array_key_exists($key, $answers)) {
                return $answers[$key];
            }
        }

        return null;
    }

    /** @param  list<string>  $options */
    private function displayCanonicalAnswer(string $type, mixed $answer, array $options): ?string
    {
        if (in_array($type, ['mcq', 'checkbox', 'true_false'], true)) {
            $resolved = $this->normalizedChoiceAnswer($answer, $this->canonicalChoiceOptions($type, $options));

            return $resolved === null ? $this->displayAnswer($answer) : $this->displayResolvedChoices($resolved, $type, $options);
        }

        return $this->displayAnswer($answer);
    }

    /** @param  list<string>  $options */
    private function displayLearnerAnswer(string $type, mixed $answer, array $options): ?string
    {
        if (in_array($type, ['mcq', 'checkbox', 'true_false'], true)) {
            $resolved = $this->normalizedChoiceAnswer($answer, $this->canonicalChoiceOptions($type, $options));
            if ($resolved !== null) {
                return $this->displayResolvedChoices($resolved, $type, $options);
            }
        }

        return $this->displayAnswer($answer);
    }

    private function questionAnswerStatus(object $question, mixed $learnerAnswer): string
    {
        if ($this->answerIsEmpty($learnerAnswer)) {
            return 'unanswered';
        }

        if (! in_array($question->question_type, ['mcq', 'checkbox', 'true_false'], true)) {
            return 'manual';
        }

        $options = $this->canonicalChoiceOptions($question->question_type, $question->display_options);
        $expected = $this->normalizedChoiceAnswer($question->correct_answer ?? null, $options);
        $actual = $this->normalizedChoiceAnswer($learnerAnswer, $options);
        if ($expected === null || $expected === []) {
            return 'cannot_evaluate';
        }
        if (in_array($question->question_type, ['mcq', 'true_false'], true) && count($expected) !== 1) {
            return 'cannot_evaluate';
        }
        if ($actual === null) {
            return 'recorded_unavailable';
        }
        if (in_array($question->question_type, ['mcq', 'true_false'], true) && count($actual) !== 1) {
            return 'recorded_unavailable';
        }

        sort($expected);
        sort($actual);

        return $actual === $expected ? 'correct' : 'incorrect';
    }

    private function answerIsEmpty(mixed $answer): bool
    {
        if ($answer === null) {
            return true;
        }
        if (is_array($answer)) {
            return collect($answer)->every(fn (mixed $value): bool => $this->answerIsEmpty($value));
        }

        if (is_bool($answer)) {
            return false;
        }

        return ! is_scalar($answer) || trim((string) $answer) === '';
    }

    private function normalizedQuestionType(string $type): string
    {
        $type = strtolower(trim($type));

        return $type === 'multiple_choice' ? 'mcq' : $type;
    }

    /**
     * @param  list<array{key: string, display: string, aliases: list<string>}>  $options
     * @return list<string>|null
     */
    private function normalizedChoiceAnswer(mixed $answer, array $options): ?array
    {
        $answers = $this->answerValues($answer);
        if ($answers === null) {
            return null;
        }

        $resolved = [];
        foreach ($answers as $value) {
            if (! is_scalar($value)) {
                return null;
            }
            $value = $this->scalarAnswerText($value);
            if ($value === '') {
                continue;
            }
            $normalized = $this->normalizedAnswerText($value);
            $matches = collect($options)->filter(
                fn (array $option): bool => in_array($normalized, $option['aliases'], true)
            );
            if ($matches->count() !== 1) {
                return null;
            }
            $resolved[] = $matches->first()['key'];
        }

        return array_values(array_unique($resolved));
    }

    /** @return list<mixed>|null */
    private function answerValues(mixed $answer): ?array
    {
        if (is_string($answer)) {
            $decoded = json_decode($answer, true);
            if (is_array($decoded)) {
                $answer = $decoded;
            } elseif (str_contains($answer, '|')) {
                $answer = preg_split('/\s*\|\s*/', $answer);
            }
        }

        if (! is_array($answer)) {
            return [$answer];
        }
        if (array_is_list($answer)) {
            return $answer;
        }

        foreach (['answer', 'value', 'selected', 'selected_options', 'answers'] as $wrapper) {
            if (array_key_exists($wrapper, $answer)) {
                $wrapped = $answer[$wrapper];

                return is_array($wrapped) && array_is_list($wrapped) ? $wrapped : [$wrapped];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $displayOptions
     * @return list<array{key: string, display: string, aliases: list<string>}>
     */
    private function canonicalChoiceOptions(string $type, array $displayOptions): array
    {
        if ($type === 'true_false') {
            $displayOptions = ['True', 'False'];
        }

        return collect($displayOptions)->values()->map(function (string $display, int $index): array {
            $display = trim($display);
            $defaultKey = chr(ord('A') + $index);
            $key = $defaultKey;
            $text = $display;
            if (preg_match('/^([A-Z])[\s]*[.):\-]\s*(.+)$/iu', $display, $matches) === 1) {
                $key = strtoupper($matches[1]);
                $text = trim($matches[2]);
            }
            $aliases = [
                $this->normalizedAnswerText($key),
                $this->normalizedAnswerText($display),
                $this->normalizedAnswerText($text),
                $this->normalizedAnswerText($key.'. '.$text),
            ];

            return [
                'key' => $key,
                'display' => $display,
                'aliases' => array_values(array_unique(array_filter($aliases))),
            ];
        })->all();
    }

    private function normalizedAnswerText(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value)));
    }

    private function scalarAnswerText(mixed $value): string
    {
        return is_bool($value) ? ($value ? 'true' : 'false') : trim((string) $value);
    }

    /**
     * @param  list<string>  $resolved
     * @param  list<string>  $displayOptions
     */
    private function displayResolvedChoices(array $resolved, string $type, array $displayOptions): ?string
    {
        $options = collect($this->canonicalChoiceOptions($type, $displayOptions))->keyBy('key');
        $values = collect($resolved)->map(fn (string $key): ?string => $options->get($key)['display'] ?? null)->filter()->values();

        return $values->isEmpty() ? null : $values->implode(', ');
    }

    /**
     * @param  list<string>  $displayOptions
     * @return list<object{key: string, display: string, selected: bool, correct: bool, state: string}>
     */
    private function answerOptions(
        string $type,
        array $displayOptions,
        mixed $learnerAnswer,
        mixed $correctAnswer,
        string $answerStatus
    ): array {
        if (! in_array($type, ['mcq', 'checkbox', 'true_false'], true)) {
            return [];
        }

        $options = $this->canonicalChoiceOptions($type, $displayOptions);
        $selected = $this->normalizedChoiceAnswer($learnerAnswer, $options) ?? [];
        $correct = $answerStatus === 'cannot_evaluate'
            ? []
            : ($this->normalizedChoiceAnswer($correctAnswer, $options) ?? []);

        return collect($options)->map(function (array $option) use ($selected, $correct, $answerStatus): object {
            $isSelected = in_array($option['key'], $selected, true);
            $isCorrect = in_array($option['key'], $correct, true);
            $state = match (true) {
                $isSelected && $isCorrect => 'correct-selected',
                $isSelected && $answerStatus === 'cannot_evaluate' => 'participant-selected-neutral',
                $isSelected => 'wrong-selected',
                $isCorrect && $answerStatus === 'recorded_unavailable' => 'correct-reference',
                $isCorrect => 'correct-answer',
                default => 'neutral',
            };

            return (object) [
                'key' => $option['key'],
                'display' => $option['display'],
                'selected' => $isSelected,
                'correct' => $isCorrect,
                'state' => $state,
            ];
        })->all();
    }

    private function displayAnswer(mixed $answer): ?string
    {
        if (is_string($answer)) {
            $decoded = json_decode($answer, true);
            if (is_array($decoded)) {
                $answer = $decoded;
            } else {
                $answer = trim($answer);
            }
        }

        if (is_array($answer)) {
            if (! array_is_list($answer) || collect($answer)->contains(fn (mixed $item): bool => ! is_scalar($item))) {
                return null;
            }
            $answer = implode(', ', array_values(array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                $answer
            ), static fn (string $item): bool => $item !== '')));
        }

        if (! is_scalar($answer)) {
            return null;
        }
        $answer = $this->scalarAnswerText($answer);

        return $answer !== '' ? $answer : null;
    }

    /** @return list<string> */
    private function decodedAnswerArray(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded)
            ? array_values(array_map(static fn (mixed $item): string => (string) $item, $decoded))
            : [];
    }

    private function participantNavigator(string $courseId, string $search, ?int $selectedId): Collection
    {
        $query = $this->participantQuery($courseId);
        $this->applyParticipantSearch($query, $search);
        $participants = $query->limit(100)->get();
        if ($search === '' && $selectedId && ! $participants->contains('enrollment_id', $selectedId)) {
            $selected = $this->participantQuery($courseId)->where('enrollment.enrollment_id', $selectedId)->first();
            if ($selected) {
                $participants->prepend($selected);
            }
        }

        return $participants->unique('enrollment_id')->values();
    }

    private function applyParticipantSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $needle = '%'.strtolower($search).'%';
        $query->where(function (Builder $builder) use ($needle): void {
            $builder->whereRaw('LOWER('.$this->participantNameExpression().') LIKE ?', [$needle])
                ->orWhereRaw('LOWER('.$this->participantEmailExpression().') LIKE ?', [$needle]);
        });
    }

    private function participantQuery(string $courseId): Builder
    {
        return DB::table('enroll_guru_baru as enrollment')
            ->join('guru_new', 'enrollment.gn_id', '=', 'guru_new.gn_id')
            ->where('enrollment.course_id', $courseId)
            ->select(
                'enrollment.enrollment_id',
                DB::raw("'Guru New' as participant_type"),
                DB::raw($this->participantNameExpression().' as participant_name'),
                DB::raw($this->participantEmailExpression().' as participant_email')
            )
            ->orderBy('participant_name')
            ->orderBy('enrollment.enrollment_id');
    }

    private function participantNameExpression(): string
    {
        return "COALESCE(guru_new.gn_name, 'Unknown New Teacher')";
    }

    private function participantEmailExpression(): string
    {
        return 'guru_new.email';
    }

    private function modules(string $courseId, string $search = '', ?int $selectedId = null): Collection
    {
        $query = DB::table('training_module')->where('course_id', $courseId);
        if ($search !== '') {
            $needle = '%'.strtolower($search).'%';
            $hasVisibility = Schema::hasColumn('training_content_block', 'is_visible');
            $query->where(function (Builder $builder) use ($hasVisibility, $needle): void {
                $builder->whereRaw('LOWER(training_module.module_title) LIKE ?', [$needle])
                    ->orWhereExists(function (Builder $content) use ($hasVisibility, $needle): void {
                        $content->selectRaw('1')
                            ->from('training_content_block')
                            ->leftJoin('learning_material', 'training_content_block.material_id', '=', 'learning_material.material_id')
                            ->whereColumn('training_content_block.module_id', 'training_module.module_id')
                            ->when($hasVisibility, fn (Builder $visible): Builder => $visible->where(
                                fn (Builder $state): Builder => $state->where('training_content_block.is_visible', true)
                                    ->orWhereNull('training_content_block.is_visible')
                            ))
                            ->where(function (Builder $names) use ($needle): void {
                                $names->whereRaw('LOWER(training_content_block.block_title) LIKE ?', [$needle])
                                    ->orWhereRaw('LOWER(learning_material.original_file_name) LIKE ?', [$needle]);
                            });
                        $this->applyTrackableBlockScope($content);
                    });
            });
        }

        $modules = $query
            ->orderByRaw('CASE WHEN module_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('module_order')->orderBy('module_id')->limit(100)->get();
        if ($search === '' && $selectedId && ! $modules->contains('module_id', $selectedId)) {
            $selected = DB::table('training_module')
                ->where('course_id', $courseId)
                ->where('module_id', $selectedId)
                ->first();
            if ($selected) {
                $modules->prepend($selected);
            }
        }

        return $modules->unique('module_id')->values();
    }

    private function trackableBlocks(string $courseId, Collection $moduleIds): Collection
    {
        if ($moduleIds->isEmpty()) {
            return collect();
        }

        return $this->trackableBlockQuery($courseId)->whereIn('training_module.module_id', $moduleIds)->get();
    }

    private function trackableBlockQuery(string $courseId): Builder
    {
        $query = $this->materials->builderBlocksQuery()
            ->join('training_module', 'training_content_block.module_id', '=', 'training_module.module_id')
            ->leftJoin('learning_material', 'training_content_block.material_id', '=', 'learning_material.material_id')
            ->where('training_module.course_id', $courseId)
            ->select(
                'training_content_block.block_id',
                'training_content_block.module_id',
                'training_content_block.block_type',
                'training_content_block.block_order',
                'training_module.module_title',
                'training_module.module_order',
                'learning_material.original_file_name',
                DB::raw("COALESCE(NULLIF(training_content_block.block_title, ''), NULLIF(learning_material.original_file_name, ''), 'Untitled material') as content_title")
            )
            ->orderByRaw('CASE WHEN training_module.module_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('training_module.module_order')->orderBy('training_module.module_id')
            ->orderByRaw('CASE WHEN training_content_block.block_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('training_content_block.block_order')->orderBy('training_content_block.block_id');

        return $this->applyTrackableBlockScope($query);
    }

    private function applyTrackableBlockScope(Builder $query): Builder
    {
        return $query->where(function (Builder $eligible): void {
            $eligible->whereIn('training_content_block.block_type', ['material', 'youtube'])
                ->orWhere(function (Builder $media): void {
                    $media->where('training_content_block.block_type', 'media')
                        ->where(function (Builder $filename): void {
                            foreach (TrainerLmsMaterialAuthoringService::interactiveMediaExtensions() as $index => $extension) {
                                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                                $filename->{$method}('LOWER(learning_material.original_file_name) LIKE ?', ['%.'.$extension]);
                            }
                        });
                });
        });
    }

    private function quizzes(string $courseId, string $search, ?int $selectedId = null): Collection
    {
        $query = $this->quizQuery($courseId);
        if ($search !== '') {
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%']);
        }

        $quizzes = $query->orderByDesc('assessment_id')->limit(100)->get();
        if ($search === '' && $selectedId && ! $quizzes->contains('assessment_id', $selectedId)) {
            $selected = DB::table('assessment')
                ->where('course_id', $courseId)
                ->whereRaw("LOWER(TRIM(type)) = 'quiz'")
                ->where('assessment_id', $selectedId)
                ->select($this->quizColumns())
                ->first();
            if ($selected) {
                $quizzes->prepend($selected);
            }
        }

        return $this->enrichQuizzes($courseId, $quizzes->unique('assessment_id')->values());
    }

    private function quizById(string $courseId, ?int $assessmentId): ?object
    {
        if (! $assessmentId) {
            return null;
        }

        $quiz = $this->quizQuery($courseId)->where('assessment_id', $assessmentId)->first();

        return $quiz ? $this->enrichQuizzes($courseId, collect([$quiz]))->first() : null;
    }

    private function quizQuery(string $courseId): Builder
    {
        return DB::table('assessment')
            ->where('course_id', $courseId)
            ->whereRaw("LOWER(TRIM(type)) = 'quiz'")
            ->select($this->quizColumns());
    }

    /** @return list<string> */
    private function quizColumns(): array
    {
        $columns = ['assessment_id', 'title', 'total_marks'];
        foreach (['available_from', 'available_until'] as $column) {
            if (Schema::hasColumn('assessment', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function enrichQuizzes(string $courseId, Collection $quizzes): Collection
    {
        $quizIds = $quizzes->pluck('assessment_id')->filter()->values();
        $attempted = $this->quizAttemptContract() && $quizIds->isNotEmpty()
            ? DB::table('assessment_submission')
                ->join('assessment', 'assessment_submission.assessment_id', '=', 'assessment.assessment_id')
                ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id')
                ->where('assessment.course_id', $courseId)
                ->where('enrollment.course_id', $courseId)
                ->whereIn('assessment_submission.assessment_id', $quizIds)
                ->selectRaw('assessment_submission.assessment_id, COUNT(DISTINCT assessment_submission.enrollment_id) as attempted_count')
                ->groupBy('assessment_submission.assessment_id')
                ->pluck('attempted_count', 'assessment_id')
            : collect();
        $participantCount = $this->participantQuery($courseId)->count();
        $questionsAvailable = Schema::hasTable('assessment_question');
        $questionCounts = $questionsAvailable && $quizIds->isNotEmpty()
            ? DB::table('assessment_question')
                ->whereIn('assessment_id', $quizIds)
                ->selectRaw('assessment_id, COUNT(*) as question_count')
                ->groupBy('assessment_id')
                ->pluck('question_count', 'assessment_id')
            : collect();

        return $quizzes->map(function (object $quiz) use ($attempted, $participantCount, $questionCounts, $questionsAvailable): object {
            $quiz->available_from = $quiz->available_from ?? null;
            $quiz->available_until = $quiz->available_until ?? null;
            $quiz->availability = $this->materials->deriveAssessmentAvailability($quiz->available_from, $quiz->available_until);
            $quiz->attempted_participant_count = (int) ($attempted->get($quiz->assessment_id) ?? 0);
            $quiz->participant_count = $participantCount;
            $quiz->question_count = $questionsAvailable
                ? (int) ($questionCounts->get($quiz->assessment_id) ?? 0)
                : null;

            return $quiz;
        });
    }

    private function interactionAggregate(string $courseId, array $period): Builder
    {
        $query = DB::query()->fromSub($this->interactionAggregateSubquery($period), 'log')
            ->join('training_content_block', 'log.block_id', '=', 'training_content_block.block_id')
            ->join('training_module', 'training_content_block.module_id', '=', 'training_module.module_id')
            ->leftJoin('learning_material', 'training_content_block.material_id', '=', 'learning_material.material_id')
            ->where('training_module.course_id', $courseId)
            ->select(
                'log.*',
                'training_content_block.module_id',
                'training_module.module_title',
                DB::raw("COALESCE(NULLIF(training_content_block.block_title, ''), NULLIF(learning_material.original_file_name, ''), 'Untitled material') as content_title")
            );
        $this->applyTrackableBlockScope($query);
        if (Schema::hasColumn('training_content_block', 'is_visible')) {
            $query->where(fn (Builder $builder): Builder => $builder
                ->where('training_content_block.is_visible', true)
                ->orWhereNull('training_content_block.is_visible'));
        }

        return $query;
    }

    private function interactionAggregateSubquery(array $period): Builder
    {
        $query = DB::table('training_content_interaction_log')->select('enrollment_id', 'block_id');
        $query->selectRaw(Schema::hasColumn('training_content_interaction_log', 'open_count') ? 'SUM(COALESCE(open_count, 0)) as open_count' : 'COUNT(*) as open_count');
        $query->selectRaw(Schema::hasColumn('training_content_interaction_log', 'first_opened_at') ? 'MIN(first_opened_at) as first_opened_at' : 'NULL as first_opened_at');
        $query->selectRaw(Schema::hasColumn('training_content_interaction_log', 'last_accessed_at') ? 'MAX(last_accessed_at) as last_accessed_at' : 'NULL as last_accessed_at');
        $query->selectRaw(Schema::hasColumn('training_content_interaction_log', 'total_seconds') ? 'SUM(COALESCE(total_seconds, 0)) as total_seconds' : 'NULL as total_seconds');

        if (Schema::hasColumn('training_content_interaction_log', 'last_accessed_at')) {
            $this->applyPeriod($query, 'last_accessed_at', $period);
        }

        return $query->groupBy('enrollment_id', 'block_id');
    }

    private function quizAttemptsQuery(string $courseId, array $period): Builder
    {
        if (! Schema::hasTable('assessment_submission')) {
            return DB::table('assessment_submission')->whereRaw('1 = 0');
        }

        $query = DB::table('assessment_submission')
            ->join('assessment', 'assessment_submission.assessment_id', '=', 'assessment.assessment_id')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('assessment.course_id', $courseId)
            ->where('enrollment.course_id', $courseId)
            ->whereRaw("LOWER(TRIM(assessment.type)) = 'quiz'")
            ->select(
                'assessment_submission.submission_id',
                'assessment_submission.assessment_id',
                'assessment_submission.enrollment_id',
                'assessment_submission.grade',
                'assessment_submission.submitted_at',
                'assessment.title as assessment_title'
            );
        $this->applyPeriod($query, 'assessment_submission.submitted_at', $period);

        return $query;
    }

    private function normalizeEngagementRow(object $row, array $tracking): object
    {
        $mediaKind = $row->block_type === 'media'
            ? TrainerLmsMaterialAuthoringService::mediaKind($row->original_file_name ?? null)
            : null;
        $row->content_type_label = match ($mediaKind) {
            'video' => 'Video',
            'audio' => 'Audio',
            default => ucfirst((string) $row->block_type),
        };
        $row->accessed = $tracking['available'] && ((int) ($row->open_count ?? 0) > 0 || $row->last_accessed_at !== null);
        $row->tracked_time = $tracking['duration'] && $row->total_seconds !== null
            ? self::formatDuration((int) $row->total_seconds)
            : null;

        return $row;
    }

    private function validSelectedId(Builder $query, string $column, mixed $requested): ?int
    {
        $requested = filter_var($requested, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        if ($requested && (clone $query)->where($column, $requested)->exists()) {
            return (int) $requested;
        }

        $first = $query->value($column);

        return $first ? (int) $first : null;
    }

    private function validCollectionId(Collection $items, string $key, mixed $requested): ?int
    {
        $requested = filter_var($requested, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        if ($requested && $items->contains($key, (int) $requested)) {
            return (int) $requested;
        }

        $first = $items->first();

        return $first ? (int) $first->{$key} : null;
    }

    /** @return array{key: string, from: ?CarbonImmutable, until: ?CarbonImmutable, valid: bool} */
    private function period(array $filters): array
    {
        $key = in_array($filters['period'] ?? 'all', ['all', '7', '30', 'custom'], true)
            ? (string) ($filters['period'] ?? 'all')
            : 'all';
        $now = CarbonImmutable::now(config('app.timezone'));
        if ($key === '7' || $key === '30') {
            return ['key' => $key, 'from' => $now->subDays((int) $key)->startOfDay(), 'until' => $now->endOfDay(), 'valid' => true];
        }
        if ($key === 'custom') {
            try {
                $from = ! empty($filters['from']) ? CarbonImmutable::parse($filters['from'], config('app.timezone'))->startOfDay() : null;
                $until = ! empty($filters['until']) ? CarbonImmutable::parse($filters['until'], config('app.timezone'))->endOfDay() : null;
                $valid = $from && $until && $from->lte($until);

                return ['key' => $key, 'from' => $valid ? $from : null, 'until' => $valid ? $until : null, 'valid' => (bool) $valid];
            } catch (\Throwable) {
                return ['key' => $key, 'from' => null, 'until' => null, 'valid' => false];
            }
        }

        return ['key' => 'all', 'from' => null, 'until' => null, 'valid' => true];
    }

    private function applyPeriod(Builder $query, string $column, array $period): void
    {
        if ($period['from'] && $period['until']) {
            $query->whereBetween($column, [$period['from'], $period['until']]);
        }
    }

    private function paginateBuilder(Builder $query, int $rows, int $page, string $pageName): LengthAwarePaginator
    {
        $total = (clone $query)->reorder()->count();
        $lastPage = max(1, (int) ceil($total / $rows));
        $page = max(1, min($page, $lastPage));
        $items = (clone $query)->forPage($page, $rows)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $rows,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName]
        );
    }

    private function tab(mixed $tab): string
    {
        return match ($tab) {
            'engagement' => 'engagement',
            'quiz', 'assessment' => 'quiz',
            default => 'overview',
        };
    }

    private function emptyPaginator(int $rows, string $pageName): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $rows, 1, ['path' => request()->url(), 'pageName' => $pageName]);
    }

    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? $hours.'h '.$minutes.'m' : $minutes.'m';
    }
}
