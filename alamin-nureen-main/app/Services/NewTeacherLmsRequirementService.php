<?php

namespace App\Services;

use App\Support\TrainHubSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saves a per-teacher LMS target in the existing snapshot table.
 *
 * A database trigger is optional. When one cannot be installed, TrainHub
 * snapshots new teachers on Eloquent creation, on Teacher/Dashboard visits,
 * and before a Staff EDU changes the default requirement.
 */
final class NewTeacherLmsRequirementService
{
    /** The table, NOT a privileged MySQL trigger, is the only prerequisite. */
    public static function ready(): bool
    {
        return Schema::hasTable('guru_new')
            && Schema::hasTable('trainhub_new_teacher_lms_requirements');
    }

    /**
     * Lock in the supplied default for teachers who do not have a target yet.
     * Already assigned requirements are never overwritten.
     *
     * Call inside the setting update transaction, BEFORE changing the default.
     */
    public static function snapshotExisting(int $requirement): void
    {
        if (!self::ready()) {
            throw new \RuntimeException('The trainhub_new_teacher_lms_requirements table is missing.');
        }

        $requirement = max(1, min(100, $requirement));
        DB::statement(<<<'SQL'
            INSERT IGNORE INTO trainhub_new_teacher_lms_requirements
                (gn_id, required_lms_courses, assigned_at)
            SELECT g.gn_id, ?, CURRENT_TIMESTAMP
            FROM guru_new AS g
            LEFT JOIN trainhub_new_teacher_lms_requirements AS r ON r.gn_id = g.gn_id
            WHERE g.gn_id IS NOT NULL AND r.gn_id IS NULL
            SQL, [$requirement]);
    }

    /** Sync teachers added by another system since the last TrainHub visit. */
    public static function snapshotMissingFromCurrentDefault(): void
    {
        if (!self::ready()) {
            return;
        }

        self::snapshotExisting((int) TrainHubSettings::all()['new_teacher_lms_required']);
    }

    /**
     * Called by the GuruNew Eloquent model's created event.
     * External systems that do not use that model should do the same insert
     * in their own new-teacher creation transaction (see update README).
     */
    public static function snapshotForTeacher(string $gnId): void
    {
        if ($gnId === '' || !self::ready()) {
            return;
        }

        $requirement = (int) TrainHubSettings::all()['new_teacher_lms_required'];
        DB::statement(<<<'SQL'
            INSERT IGNORE INTO trainhub_new_teacher_lms_requirements
                (gn_id, required_lms_courses, assigned_at)
            SELECT gn_id, ?, CURRENT_TIMESTAMP
            FROM guru_new
            WHERE gn_id = ?
            SQL, [$requirement, $gnId]);
    }

    /** Get the personal target, safely creating it if the teacher is not yet synced. */
    public static function forTeacher(string $gnId): ?int
    {
        if ($gnId === '' || !self::ready()) {
            return null;
        }

        $value = DB::table('trainhub_new_teacher_lms_requirements')
            ->where('gn_id', $gnId)
            ->value('required_lms_courses');

        if ($value === null) {
            self::snapshotForTeacher($gnId);
            $value = DB::table('trainhub_new_teacher_lms_requirements')
                ->where('gn_id', $gnId)
                ->value('required_lms_courses');
        }

        return $value === null ? null : (int) $value;
    }
}
