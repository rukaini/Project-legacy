<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Writes one entry into trainer_activity_log.
 *
 * Used by the admin (EDU) side to monitor what trainers do.
 * Call it from any controller action AFTER the real action succeeds, e.g.:
 *
 *   TrainerActivityLogger::log($trainerId, 'material.upload',
 *       'Uploaded ' . $fileName, $courseId, 'material', $materialId);
 *
 * Logging never throws back into the calling action: if the insert fails,
 * it is quietly recorded in the Laravel log so the trainer's real action
 * (upload, delete, grade, etc.) still completes normally.
 */
class TrainerActivityLogger
{
    private const ACTION_MAX_LENGTH = 60;

    private const DETAIL_MAX_LENGTH = 255;

    private const TARGET_TYPE_MAX_LENGTH = 40;

    private const IP_ADDRESS_MAX_LENGTH = 45;

    /**
     * Human-facing metadata for meaningful trainer actions.
     *
     * The action code remains stable in the database while every activity
     * surface gets the same label, icon, and tone from this single map.
     */
    private const ACTION_META = [
        'module.create' => ['Module created', 'bi-folder-plus', 'indigo'],
        'module.update' => ['Module updated', 'bi-pencil-square', 'amber'],
        'module.reorder' => ['Module order updated', 'bi-arrow-down-up', 'amber'],
        'module.delete' => ['Module removed', 'bi-folder-minus', 'red'],
        'block.create' => ['Workspace content added', 'bi-plus-square', 'indigo'],
        'block.update' => ['Module content updated', 'bi-pencil-square', 'amber'],
        'block.delete' => ['Workspace content removed', 'bi-x-square', 'red'],
        'material.upload' => ['Material uploaded', 'bi-file-earmark-arrow-up', 'blue'],
        'material.update' => ['Material updated', 'bi-file-earmark-pencil', 'amber'],
        'material.replace' => ['Material updated', 'bi-file-earmark-pencil', 'amber'],
        'material.delete' => ['Material removed', 'bi-file-earmark-x', 'red'],
        'quiz.create' => ['Quiz created', 'bi-ui-checks-grid', 'indigo'],
        'quiz.update' => ['Quiz updated', 'bi-ui-checks-grid', 'amber'],
        'assessment.create' => ['Submission task created', 'bi-clipboard-plus', 'indigo'],
        'assessment.update' => ['Submission task updated', 'bi-clipboard-check', 'amber'],
        'submission_task.create' => ['Submission task created', 'bi-clipboard-plus', 'indigo'],
        'submission_task.update' => ['Submission task updated', 'bi-clipboard-check', 'amber'],
        'submission.review' => ['Submission review updated', 'bi-check2-square', 'green'],
        'submission.grade' => ['Submission graded', 'bi-check2-square', 'green'],
        'attendance.update' => ['Attendance updated', 'bi-person-check', 'green'],
        'session.create' => ['Session created', 'bi-calendar-plus', 'indigo'],
        'session.update' => ['Session updated', 'bi-calendar-check', 'amber'],
        'session.delete' => ['Session removed', 'bi-calendar-x', 'red'],
        'teaching_plan.submit' => ['Teaching Plan submitted', 'bi-file-earmark-check', 'indigo'],
        'teaching_plan.remove' => ['Teaching Plan removed', 'bi-file-earmark-x', 'red'],
        'proposal.submit' => ['Proposal submitted', 'bi-lightbulb', 'amber'],
        'proposal.delete' => ['Proposal withdrawn', 'bi-x-circle', 'red'],
        'profile.update' => ['Trainer profile updated', 'bi-person-gear', 'slate'],
        'profile.password' => ['Password changed', 'bi-key', 'slate'],
        'certificate.upload' => ['Certificate uploaded', 'bi-award', 'indigo'],
        'certificate.remove' => ['Certificate removed', 'bi-award', 'red'],
    ];

    /** Known page-view/authentication codes retained only as historical rows. */
    private const NAVIGATION_ACTIONS = [
        'login',
        'training.open',
        'dashboard.open',
        'profile.open',
        'materials.open',
        'submissions.open',
        'attendance.open',
        'participants.open',
        'analytics.open',
        'feedback.open',
        'proposal.open',
    ];

    /** Course activity that is safe and useful to share with co-trainers. */
    private const WORKSPACE_ACTIONS = [
        'module.create',
        'module.update',
        'module.reorder',
        'module.delete',
        'block.create',
        'block.update',
        'block.delete',
        'material.upload',
        'material.update',
        'material.replace',
        'material.delete',
        'quiz.create',
        'quiz.update',
        'assessment.create',
        'assessment.update',
        'submission_task.create',
        'submission_task.update',
        'submission.review',
        'submission.grade',
        'attendance.update',
        'session.create',
        'session.update',
        'session.delete',
    ];

    public static function log(
        string $trainerId,
        string $action,
        ?string $detail = null,
        $courseId = null,
        ?string $targetType = null,
        $targetId = null
    ): void {
        // No trainer in session = nothing to log (e.g. failed login attempt).
        if (empty($trainerId)) {
            return;
        }

        try {
            $ipAddress = request()->ip();

            DB::table('trainer_activity_log')->insert([
                'trainer_id' => $trainerId,
                'action' => mb_substr($action, 0, self::ACTION_MAX_LENGTH),
                'detail' => $detail === null ? null : mb_substr($detail, 0, self::DETAIL_MAX_LENGTH),
                'course_id' => $courseId,
                'target_type' => $targetType === null ? null : mb_substr($targetType, 0, self::TARGET_TYPE_MAX_LENGTH),
                'target_id' => $targetId,
                'ip_address' => $ipAddress === null ? null : mb_substr($ipAddress, 0, self::IP_ADDRESS_MAX_LENGTH),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Trainer activity log failed: '.$e->getMessage());
        }
    }

    public static function actionMeta(string $action): array
    {
        return self::ACTION_META[$action] ?? [
            ucfirst(str_replace(['.', '_'], ' ', $action)),
            'bi-dot',
            'slate',
        ];
    }

    public static function actionLabel(string $action): string
    {
        return self::actionMeta($action)[0];
    }

    public static function meaningfulActionCodes(): array
    {
        return array_keys(self::ACTION_META);
    }

    public static function workspaceActionCodes(): array
    {
        return self::WORKSPACE_ACTIONS;
    }

    /**
     * Remove known navigation/read-only noise without deleting historical rows.
     */
    public static function scopeMeaningful(Builder $query, string $table = 'trainer_activity_log'): Builder
    {
        $action = $table.'.action';
        $detail = $table.'.detail';

        return $query
            ->whereNotIn($action, self::NAVIGATION_ACTIONS)
            ->where(function (Builder $activity) use ($action, $detail): void {
                $activity->where($action, '<>', 'submission.review')
                    ->orWhere(function (Builder $submissionReview) use ($action, $detail): void {
                        $submissionReview
                            ->where($action, 'submission.review')
                            ->whereRaw("LOWER(COALESCE($detail, '')) NOT LIKE ?", ['viewed file:%'])
                            ->whereRaw("LOWER(COALESCE($detail, '')) NOT LIKE ?", ['downloaded file:%'])
                            ->whereRaw("LOWER(COALESCE($detail, '')) NOT LIKE ?", ['downloaded zip%']);
                    });
            });
    }

    /**
     * Shared, course-scoped workspace feed for every currently assigned trainer.
     */
    public static function workspaceUpdates(
        string $courseId,
        Collection $assignedTrainerIds,
        string $currentTrainerId,
        int $limit = 12
    ): Collection {
        if (
            ! Schema::hasTable('trainer_activity_log')
            || ! Schema::hasTable('trainer')
            || $assignedTrainerIds->isEmpty()
        ) {
            return collect();
        }

        $query = DB::table('trainer_activity_log')
            ->join('trainer', 'trainer_activity_log.trainer_id', '=', 'trainer.trainerID')
            ->where('trainer_activity_log.course_id', $courseId)
            ->whereIn('trainer_activity_log.trainer_id', $assignedTrainerIds->map(
                static fn ($trainerId): string => (string) $trainerId
            )->unique()->values())
            ->whereIn('trainer_activity_log.action', self::WORKSPACE_ACTIONS);

        return self::scopeMeaningful($query)
            ->select(
                'trainer_activity_log.log_id',
                'trainer_activity_log.trainer_id',
                'trainer_activity_log.action',
                'trainer_activity_log.detail',
                'trainer_activity_log.created_at',
                DB::raw('trainer.trainerName AS trainer_name')
            )
            ->orderByDesc('trainer_activity_log.created_at')
            ->orderByDesc('trainer_activity_log.log_id')
            ->limit(max(1, $limit))
            ->get()
            ->map(function (object $activity) use ($currentTrainerId): object {
                [$activity->title, $activity->icon, $activity->color] = self::actionMeta($activity->action);
                $activity->description = $activity->detail;
                $activity->time = $activity->created_at;
                $activity->is_current_actor = (string) $activity->trainer_id === $currentTrainerId;

                return $activity;
            });
    }
}
