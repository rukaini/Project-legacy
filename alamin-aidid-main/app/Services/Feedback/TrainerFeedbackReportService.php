<?php

namespace App\Services\Feedback;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class TrainerFeedbackReportService
{
    private const PERIOD_OPTIONS = ['all', '30', '90', '365', 'custom'];

    private const RATING_OPTIONS = ['all', 'high', 'mid', 'low'];

    private const COMMENT_BATCH_SIZE = 5;

    public function normalizeFilters(Request $request): array
    {
        $requestedPeriod = $request->string('period')->toString();
        $period = in_array($requestedPeriod, self::PERIOD_OPTIONS, true) ? $requestedPeriod : 'all';
        $rating = $request->string('rating')->toString();
        $search = trim($request->string('search')->toString());
        $dateFrom = $this->validDate($request->string('date_from')->toString());
        $dateTo = $this->validDate($request->string('date_to')->toString());
        $customDateValid = $period === 'custom'
            && $dateFrom !== ''
            && $dateTo !== ''
            && $dateFrom <= $dateTo;
        if ($period !== 'custom' || ! $customDateValid) {
            $dateFrom = '';
            $dateTo = '';
        }
        if ($period === 'custom' && ! $customDateValid) {
            $period = 'all';
        }

        return [
            'filter_course_id' => mb_substr(trim($request->string('filter_course_id')->toString()), 0, 20),
            'course_id' => mb_substr(trim($request->string('course_id')->toString()), 0, 20),
            'question_id' => mb_substr(trim($request->string('question_id')->toString()), 0, 20),
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'custom_date_valid' => $customDateValid,
            'rating' => in_array($rating, self::RATING_OPTIONS, true) ? $rating : 'all',
            'search' => mb_substr($search, 0, 120),
            'comment_offset' => max(0, $request->integer('comment_offset')),
        ];
    }

    public function report(string $trainerId, array $filters): array
    {
        $startedCourseIds = $this->startedCourseIds();
        $empty = $this->emptyReport($filters);

        if (trim($trainerId) === '' || $startedCourseIds->isEmpty()) {
            return $empty;
        }

        try {
            $overviewRows = $this->feedbackQuery($trainerId, $startedCourseIds)
                ->select(['course_id', 'course_name', 'course_category', 'form_title', 'anonymous_code'])
                ->get();

            $courseOptions = $overviewRows
                ->groupBy('course_id')
                ->map(fn (Collection $rows) => $this->courseIdentity($rows->first()))
                ->sortBy('course_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            if ($filters['filter_course_id'] !== '' && ! $courseOptions->contains('course_id', $filters['filter_course_id'])) {
                $filters['filter_course_id'] = '';
            }

            $summaryQuery = $this->applyPeriod($this->feedbackQuery($trainerId, $startedCourseIds), $filters);
            if ($filters['filter_course_id'] !== '') {
                $summaryQuery->where('course_id', $filters['filter_course_id']);
            }

            $summaryRows = $summaryQuery
                ->select(['course_id', 'course_name', 'course_category', 'form_title', 'anonymous_code', 'rating'])
                ->get();

            $matchingCourseIds = $this->matchingSearchCourseIds($trainerId, $startedCourseIds, $filters);
            $trainingResults = $summaryRows
                ->groupBy('course_id')
                ->map(fn (Collection $rows) => $this->trainingSummary($rows))
                ->filter(fn (array $training) => $this->matchesRating($training['avg_rating'], $filters['rating']))
                ->when(
                    $filters['search'] !== '',
                    fn (Collection $trainings) => $trainings->whereIn('course_id', $matchingCourseIds)
                )
                ->sortBy('course_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $metricRows = $this->metricRows(
                $trainerId,
                $startedCourseIds,
                $filters,
                $trainingResults->pluck('course_id')
            );
            $metricRatings = $metricRows
                ->pluck('rating')
                ->filter(fn ($rating) => $rating !== null)
                ->map(fn ($rating) => (float) $rating);
            $averageRating = $this->usesUnfilteredRatingScope($filters)
                ? $this->liveRating($trainerId)
                : ($metricRatings->isNotEmpty() ? $metricRatings->avg() : null);

            $selectedIndex = $filters['course_id'] !== ''
                ? $trainingResults->search(fn (array $training) => $training['course_id'] === $filters['course_id'])
                : false;
            $selectedTraining = $selectedIndex !== false
                ? $trainingResults->get((int) $selectedIndex)
                : $trainingResults->first();
            $selectedCourseId = (string) ($selectedTraining['course_id'] ?? '');
            $filters['course_id'] = $selectedCourseId;

            $questions = $selectedCourseId !== ''
                ? $this->questions($trainerId, $startedCourseIds, $selectedCourseId, $filters)
                : collect();
            $selectedQuestion = $questions->firstWhere('id', $filters['question_id']) ?: $questions->first();
            $selectedQuestionId = (string) ($selectedQuestion['id'] ?? '');
            $filters['question_id'] = $selectedQuestionId;
            $comments = $selectedQuestionId !== ''
                ? $this->commentBatch($trainerId, $startedCourseIds, $selectedCourseId, $selectedQuestionId, $filters, 0)
                : $this->emptyCommentBatch();

            return [
                'liveRating' => $averageRating,
                'totalRespondents' => $metricRows->pluck('anonymous_code')->unique()->count(),
                'totalCourses' => $metricRows->pluck('course_id')->unique()->count(),
                'writtenComments' => $metricRows
                    ->filter(fn ($row) => trim((string) $row->comment) !== '')
                    ->count(),
                'courseOptions' => $courseOptions,
                'trainingResults' => $trainingResults,
                'trainingResultCount' => $trainingResults->count(),
                'selectedTraining' => $selectedTraining,
                'feedbackQuestions' => $questions,
                'selectedQuestion' => $selectedQuestion,
                'selectedComments' => $comments,
                'filters' => $filters,
            ];
        } catch (Throwable) {
            return $empty;
        }
    }

    public function commentPage(string $trainerId, string $courseId, string $questionId, array $filters): array
    {
        $startedCourseIds = $this->startedCourseIds();
        if ($trainerId === '' || $courseId === '' || $questionId === '' || ! $startedCourseIds->contains($courseId)) {
            return $this->emptyCommentBatch();
        }

        try {
            return $this->commentBatch(
                $trainerId,
                $startedCourseIds,
                $courseId,
                $questionId,
                $filters,
                $filters['comment_offset']
            );
        } catch (Throwable) {
            return $this->emptyCommentBatch();
        }
    }

    private function commentBatch(
        string $trainerId,
        Collection $startedCourseIds,
        string $courseId,
        string $questionId,
        array $filters,
        int $offset
    ): array {
        $query = $this->applyPeriod(
            $this->feedbackQuery($trainerId, $startedCourseIds)
                ->where('course_id', $courseId)
                ->where('question_id', $questionId)
                ->whereNotNull('comment')
                ->whereRaw("TRIM(comment) <> ''"),
            $filters
        );
        if ($filters['search'] !== '') {
            $query->whereRaw('LOWER(comment) LIKE ?', ['%'.mb_strtolower($filters['search']).'%']);
        }

        $total = (clone $query)->count();
        $safeOffset = min(max(0, $offset), $total);
        $comments = $query
            ->select(['comment', 'response_date'])
            ->orderByDesc('response_date')
            ->offset($safeOffset)
            ->limit(self::COMMENT_BATCH_SIZE)
            ->get()
            ->map(fn ($row) => [
                'comment' => $row->comment,
                'date_label' => Carbon::parse($row->response_date)->format('d M Y, g:i A'),
            ]);
        $nextOffset = $safeOffset + $comments->count();

        return [
            'comments' => $comments,
            'commentTotal' => $total,
            'nextCommentOffset' => $nextOffset,
            'hasMoreComments' => $nextOffset < $total,
        ];
    }

    private function startedCourseIds(): Collection
    {
        try {
            return DB::table('course_session')
                ->whereDate('course_session.sessionDate', '<=', Carbon::today())
                ->distinct()
                ->pluck('course_session.courseID')
                ->map(fn ($courseId) => (string) $courseId)
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    private function feedbackQuery(string $trainerId, Collection $startedCourseIds): Builder
    {
        return $this->feedbackSource()
            ->where('trainer_id', $trainerId)
            ->whereIn('course_id', $startedCourseIds)
            ->whereRaw('LOWER(question_category) LIKE ?', ['%trainer%']);
    }

    private function feedbackSource(): Builder
    {
        $anonymousCode = DB::connection()->getDriverName() === 'mysql'
            ? "SHA2(CONCAT('feedback:', COALESCE(fr.participantID, fr.staffID, fr.responseID)), 256)"
            : "'feedback:' || COALESCE(fr.participantID, fr.staffID, fr.responseID)";

        $source = DB::table('feedback_response as fr')
            ->join('feedback_question as fq', 'fq.questionID', '=', 'fr.questionID')
            ->join('feedback_category as fc', 'fc.categoryID', '=', 'fq.categoryID')
            ->join('feedback_form as ff', 'ff.formID', '=', 'fc.formID')
            ->leftJoin('course_session as cs', 'cs.sessionID', '=', 'ff.sessionID')
            ->join('course as c', function ($join): void {
                $join->whereColumn('c.courseID', DB::raw('COALESCE(ff.courseID, cs.courseID)'));
            })
            ->leftJoin('session_trainer as st', 'st.sessionID', '=', 'ff.sessionID')
            ->select([
                'c.courseID as course_id',
                'c.courseName as course_name',
                'c.courseCategory as course_category',
                'ff.title as form_title',
                DB::raw($anonymousCode.' as anonymous_code'),
                'fr.rating',
                'fr.comment',
                'fr.responseDate as response_date',
                'fq.questionID as question_id',
                'fq.questionOrder as question_order',
                'fq.questionText as question_text',
                'fq.questionType as question_type',
                'fc.categoryName as question_category',
                'ff.feedbackType as feedback_type',
                'st.trainerID as trainer_id',
            ]);

        return DB::query()->fromSub($source, 'feedback');
    }

    private function applyPeriod(Builder $query, array $filters): Builder
    {
        if ($filters['period'] === 'custom') {
            if ($filters['custom_date_valid']) {
                $query
                    ->whereDate('response_date', '>=', $filters['date_from'])
                    ->whereDate('response_date', '<=', $filters['date_to']);
            }

            return $query;
        }

        if ($filters['period'] !== 'all') {
            $query->where('response_date', '>=', Carbon::now()->subDays((int) $filters['period'])->startOfDay());
        }

        return $query;
    }

    private function matchingSearchCourseIds(string $trainerId, Collection $startedCourseIds, array $filters): Collection
    {
        if ($filters['search'] === '') {
            return collect();
        }

        return $this->applyPeriod($this->feedbackQuery($trainerId, $startedCourseIds), $filters)
            ->when($filters['filter_course_id'] !== '', fn (Builder $query) => $query->where('course_id', $filters['filter_course_id']))
            ->whereNotNull('comment')
            ->whereRaw("TRIM(comment) <> ''")
            ->whereRaw('LOWER(comment) LIKE ?', ['%'.mb_strtolower($filters['search']).'%'])
            ->distinct()
            ->pluck('course_id')
            ->map(fn ($courseId) => (string) $courseId);
    }

    private function questions(string $trainerId, Collection $startedCourseIds, string $courseId, array $filters): Collection
    {
        $rows = $this->applyPeriod(
            $this->feedbackQuery($trainerId, $startedCourseIds)->where('course_id', $courseId),
            $filters
        )->select(['question_id', 'question_order', 'question_text', 'question_type', 'rating', 'comment'])->get();

        if ($filters['search'] !== '') {
            $needle = mb_strtolower($filters['search']);
            $matchingQuestionIds = $rows
                ->filter(fn ($row) => trim((string) $row->comment) !== '' && str_contains(mb_strtolower((string) $row->comment), $needle))
                ->pluck('question_id')
                ->unique();
            $rows = $rows->whereIn('question_id', $matchingQuestionIds);
        }

        return $rows
            ->groupBy('question_id')
            ->map(function (Collection $questionRows, $questionId) use ($filters) {
                $first = $questionRows->first();
                if (strtolower((string) $first->question_type) !== 'rating') {
                    return null;
                }

                $ratings = $questionRows->pluck('rating')->filter(fn ($rating) => $rating !== null)->map(fn ($rating) => (float) $rating);
                $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                foreach ($ratings as $rating) {
                    $bucket = (int) round($rating);
                    if ($bucket >= 1 && $bucket <= 5) {
                        $distribution[$bucket]++;
                    }
                }

                $comments = $questionRows->filter(function ($row) use ($filters) {
                    $comment = trim((string) $row->comment);

                    return $comment !== '' && ($filters['search'] === '' || str_contains(mb_strtolower($comment), mb_strtolower($filters['search'])));
                });

                return [
                    'id' => (string) $questionId,
                    'question' => $first->question_text,
                    'avg' => $ratings->isNotEmpty() ? $ratings->avg() : null,
                    'count' => $ratings->count(),
                    'distribution' => $distribution,
                    'comment_count' => $comments->count(),
                    'order' => (int) $first->question_order,
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values();
    }

    private function trainingSummary(Collection $rows): array
    {
        $first = $rows->first();
        $ratings = $rows->pluck('rating')->filter(fn ($rating) => $rating !== null)->map(fn ($rating) => (float) $rating);

        return array_merge($this->courseIdentity($first), [
            'respondent_count' => $rows->pluck('anonymous_code')->unique()->count(),
            'avg_rating' => $ratings->isNotEmpty() ? $ratings->avg() : null,
        ]);
    }

    private function courseIdentity(object $row): array
    {
        return [
            'course_id' => (string) $row->course_id,
            'course_name' => $row->course_name,
            'course_category' => $row->course_category,
            'form_title' => $row->form_title,
        ];
    }

    private function matchesRating(?float $average, string $rating): bool
    {
        return match ($rating) {
            'high' => $average !== null && $average >= 4,
            'mid' => $average !== null && $average >= 3 && $average < 4,
            'low' => $average !== null && $average > 0 && $average < 3,
            default => true,
        };
    }

    private function metricRows(
        string $trainerId,
        Collection $startedCourseIds,
        array $filters,
        Collection $includedCourseIds
    ): Collection {
        if ($includedCourseIds->isEmpty()) {
            return collect();
        }

        $query = $this->applyPeriod($this->feedbackQuery($trainerId, $startedCourseIds), $filters)
            ->whereIn('course_id', $includedCourseIds);
        if ($filters['filter_course_id'] !== '') {
            $query->where('course_id', $filters['filter_course_id']);
        }
        if ($filters['search'] !== '') {
            $query
                ->whereNotNull('comment')
                ->whereRaw("TRIM(comment) <> ''")
                ->whereRaw('LOWER(comment) LIKE ?', ['%'.mb_strtolower($filters['search']).'%']);
        }

        return $query
            ->select(['course_id', 'anonymous_code', 'rating', 'comment'])
            ->get();
    }

    private function validDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function usesUnfilteredRatingScope(array $filters): bool
    {
        $periodIsUnfiltered = $filters['period'] === 'all'
            || ($filters['period'] === 'custom' && ! $filters['custom_date_valid']);

        return $filters['filter_course_id'] === ''
            && $periodIsUnfiltered
            && $filters['rating'] === 'all'
            && $filters['search'] === '';
    }

    public function liveRating(string $trainerId): ?float
    {
        try {
            $row = $this->feedbackSource()
                ->where('trainer_id', $trainerId)
                ->whereRaw('LOWER(question_type) = ?', ['rating'])
                ->whereRaw('LOWER(feedback_type) = ?', ['participant'])
                ->whereRaw('LOWER(question_category) LIKE ?', ['%trainer%'])
                ->selectRaw('ROUND(AVG(rating), 2) as rating')
                ->first();

            return isset($row->rating) ? (float) $row->rating : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function emptyCommentBatch(): array
    {
        return [
            'comments' => collect(),
            'commentTotal' => 0,
            'nextCommentOffset' => 0,
            'hasMoreComments' => false,
        ];
    }

    private function emptyReport(array $filters): array
    {
        return [
            'liveRating' => null,
            'totalRespondents' => 0,
            'totalCourses' => 0,
            'writtenComments' => 0,
            'courseOptions' => collect(),
            'trainingResults' => collect(),
            'trainingResultCount' => 0,
            'selectedTraining' => null,
            'feedbackQuestions' => collect(),
            'selectedQuestion' => null,
            'selectedComments' => $this->emptyCommentBatch(),
            'filters' => $filters,
        ];
    }
}
