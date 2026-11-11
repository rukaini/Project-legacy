<?php

namespace App\Services;

use App\Support\SharedTrainingSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TeachingPlanService
{
    public function trainerIsAssigned(string $courseId, string $trainerId): bool
    {
        return SharedTrainingSchema::trainerAssignedToCourse($trainerId, $courseId);
    }

    public function current(string $courseId, string $trainerId): ?object
    {
        if (! Schema::hasTable('teaching_plans')) {
            return null;
        }

        $plans = DB::table('teaching_plans')
            ->where('course_id', $courseId)
            ->where('trainer_id', $trainerId)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(1)
            ->get();

        return $this->attachCommentsAndFileState($plans)->first();
    }

    public function trainerViewData(string $courseId, string $trainerId): array
    {
        $current = $this->current($courseId, $trainerId);

        return [
            'currentTeachingPlan' => $current,
            'teachingPlanState' => $this->trainerState($current),
        ];
    }

    public function navigationState(string $courseId, string $trainerId): array
    {
        if (! Schema::hasTable('teaching_plans')) {
            return $this->trainerState(null);
        }

        $current = DB::table('teaching_plans')
            ->where('course_id', $courseId)
            ->where('trainer_id', $trainerId)
            ->first();

        if (! $current) {
            return $this->trainerState(null);
        }

        if (! Schema::hasTable('teaching_plan_comments')) {
            return $this->trainerStateFromSignals(true, false, false);
        }

        $comments = DB::table('teaching_plan_comments')->where('teaching_plan_id', $current->id);
        $hasComments = (clone $comments)->exists();
        $hasUnseenComments = $hasComments && (
            ! Schema::hasColumn('teaching_plan_comments', 'seen_by_trainer_at')
            || (clone $comments)->whereNull('seen_by_trainer_at')->exists()
        );

        return $this->trainerStateFromSignals(true, $hasComments, $hasUnseenComments);
    }

    public function currentForCourses(Collection $courseIds): Collection
    {
        if (! Schema::hasTable('teaching_plans') || $courseIds->isEmpty()) {
            return collect();
        }

        $plans = DB::table('teaching_plans')
            ->whereIn('course_id', $courseIds->map(fn ($id) => (string) $id)->unique()->values())
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        return $this->attachCommentsAndFileState($plans)
            ->keyBy(fn ($plan) => $this->scopeKey((string) $plan->course_id, (string) $plan->trainer_id));
    }

    public function planInTrainerContext(string $courseId, string $trainerId, int $planId): ?object
    {
        if (! Schema::hasTable('teaching_plans')) {
            return null;
        }

        return DB::table('teaching_plans')
            ->where('id', $planId)
            ->where('course_id', $courseId)
            ->where('trainer_id', $trainerId)
            ->first();
    }

    public function trainerState(?object $current): array
    {
        if (! $current) {
            return $this->trainerStateFromSignals(false, false, false);
        }

        $comments = collect($current->comments ?? []);
        $hasComments = $comments->isNotEmpty();
        $hasUnseenComments = $comments->contains(
            fn ($comment) => ! property_exists($comment, 'seen_by_trainer_at')
                || $comment->seen_by_trainer_at === null
        );

        return $this->trainerStateFromSignals(true, $hasComments, $hasUnseenComments);
    }

    public function adminState(?object $current): array
    {
        if (! $current) {
            return ['key' => 'not-submitted', 'label' => 'Not Submitted'];
        }

        return collect($current->comments ?? [])->isEmpty()
            ? ['key' => 'needs-review', 'label' => 'Needs Review']
            : ['key' => 'feedback-sent', 'label' => 'Feedback Sent'];
    }

    public function adminNeedsReviewCount(): int
    {
        if (
            ! Schema::hasTable('teaching_plans')
            || ! Schema::hasTable('course_session')
            || ! Schema::hasTable('session_trainer')
        ) {
            return 0;
        }

        return DB::table('teaching_plans as plans')->get()
            ->filter(fn (object $plan): bool => $this->trainerIsAssigned(
                (string) $plan->course_id,
                (string) $plan->trainer_id
            ))
            ->filter(fn (object $plan): bool => ! Schema::hasTable('teaching_plan_comments')
                || ! DB::table('teaching_plan_comments')->where('teaching_plan_id', $plan->id)->exists())
            ->count();
    }

    private function trainerStateFromSignals(bool $hasSubmission, bool $hasFeedback, bool $hasUnseenFeedback): array
    {
        if (! $hasSubmission) {
            return [
                'key' => 'upload-required',
                'label' => 'Upload Required',
                'message' => 'Upload your teaching plan for Admin review and feedback.',
            ];
        }

        if (! $hasFeedback) {
            return [
                'key' => 'awaiting-feedback',
                'label' => 'Awaiting Admin Feedback',
                'message' => 'Awaiting Admin feedback.',
            ];
        }

        if ($hasUnseenFeedback) {
            return [
                'key' => 'new-feedback',
                'label' => 'New Feedback',
                'message' => 'New Admin feedback is available.',
            ];
        }

        return [
            'key' => 'up-to-date',
            'label' => 'Up to Date',
            'message' => 'You have seen all Admin feedback on your current submission.',
        ];
    }

    public function scopeKey(string $courseId, string $trainerId): string
    {
        return $courseId.':'.$trainerId;
    }

    public function availableFilePath(?string $path): ?string
    {
        $normalized = trim(str_replace('\\', '/', (string) $path), '/');
        if (
            $normalized === ''
            || ! str_starts_with($normalized, 'teaching-plans/')
            || in_array('..', explode('/', $normalized), true)
            || ! Storage::disk('local')->exists($normalized)
        ) {
            return null;
        }

        return $normalized;
    }

    public function safeOriginalName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = trim((string) preg_replace('/[\x00-\x1F\x7F]+/', '', $name));

        return mb_substr($name !== '' ? $name : 'teaching-plan', 0, 255);
    }

    private function attachCommentsAndFileState(Collection $plans): Collection
    {
        if ($plans->isEmpty()) {
            return $plans;
        }

        $comments = collect();
        if (Schema::hasTable('teaching_plan_comments')) {
            $query = DB::table('teaching_plan_comments as comments')
                ->whereIn('comments.teaching_plan_id', $plans->pluck('id'))
                ->select('comments.*');

            $query->leftJoin('staff_edu', 'comments.reviewer_staff_id', '=', 'staff_edu.staffID')
                ->addSelect('staff_edu.staffName as reviewer_name');

            $comments = $query->orderBy('comments.created_at')->get()->groupBy('teaching_plan_id');
        }

        return $plans->map(function ($plan) use ($comments) {
            $plan->comments = $comments->get($plan->id, collect())->values();
            $plan->file_available = $this->availableFilePath($plan->file_path) !== null;

            return $plan;
        });
    }
}
