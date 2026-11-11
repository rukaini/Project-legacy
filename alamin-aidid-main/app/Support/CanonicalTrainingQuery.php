<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CanonicalTrainingQuery
{
    /** Canonical Course query with stable presentation aliases for existing Blade view models. */
    public static function courses(string $alias = 'course'): Builder
    {
        return DB::table("course as {$alias}")
            ->select("{$alias}.*")
            ->selectRaw(SharedTrainingSchema::courseInternalExpression($alias).' AS course_id')
            ->selectRaw(SharedTrainingSchema::courseNameExpression($alias).' AS course_name')
            ->selectRaw(SharedTrainingSchema::courseCategoryExpression($alias).' AS course_category')
            ->selectRaw('LOWER('.SharedTrainingSchema::courseTypeExpression($alias).') AS training_mode')
            ->selectRaw(SharedTrainingSchema::courseModeExpression($alias).' AS course_mode')
            ->selectRaw(SharedTrainingSchema::courseOrganiserExpression($alias).' AS organiser_name')
            ->selectRaw(SharedTrainingSchema::courseWhatsappExpression($alias).' AS whatsapp_group')
            ->selectRaw(SharedTrainingSchema::coursePosterExpression($alias).' AS course_pic')
            ->selectRaw(self::optionalColumn($alias, 'course', 'targetAudience').' AS target_participant');
    }

    /** Canonical Course Session query with presentation aliases. */
    public static function sessions(string $alias = 'course_session'): Builder
    {
        return DB::table("course_session as {$alias}")
            ->select("{$alias}.*")
            ->selectRaw(SharedTrainingSchema::sessionInternalExpression($alias).' AS session_id')
            ->selectRaw(SharedTrainingSchema::sessionNameExpression($alias).' AS session_title')
            ->selectRaw(SharedTrainingSchema::sessionDateExpression($alias).' AS date')
            ->selectRaw(SharedTrainingSchema::sessionStartExpression($alias).' AS time')
            ->selectRaw(SharedTrainingSchema::sessionStartExpression($alias).' AS start_time')
            ->selectRaw(SharedTrainingSchema::sessionEndExpression($alias).' AS end_time')
            ->selectRaw($alias.'.courseID AS course_id')
            ->selectRaw('NULL as session_type, NULL as session_order');
    }

    /** Canonical Trainer query with presentation aliases. */
    public static function trainers(string $alias = 'trainer'): Builder
    {
        $safeColumns = Schema::hasTable('trainer')
            ? array_values(array_diff(Schema::getColumnListing('trainer'), ['trainerPassword']))
            : [];

        return DB::table("trainer as {$alias}")
            ->select(array_map(static fn (string $column): string => "{$alias}.{$column}", $safeColumns))
            ->selectRaw(SharedTrainingSchema::trainerInternalExpression($alias).' AS trainer_id')
            ->selectRaw(SharedTrainingSchema::trainerNameExpression($alias).' AS trainer_name')
            ->selectRaw(SharedTrainingSchema::trainerEmailExpression($alias).' AS trainer_email')
            ->selectRaw(SharedTrainingSchema::trainerPhoneExpression($alias).' AS trainer_phoneNo')
            ->selectRaw(SharedTrainingSchema::trainerPictureExpression($alias).' AS trainer_pic')
            ->selectRaw(SharedTrainingSchema::trainerRatingExpression($alias).' AS feedback_rating');
    }

    /** Canonical Staff EDU query with presentation aliases. */
    public static function staff(string $alias = 'staff_edu'): Builder
    {
        return DB::table("staff_edu as {$alias}")->select([
            "{$alias}.staffID",
            "{$alias}.staffName",
            "{$alias}.ICNumber",
            "{$alias}.phoneNumber",
            "{$alias}.email",
            "{$alias}.maritalStatus",
            "{$alias}.gender",
            "{$alias}.address",
            "{$alias}.race",
            "{$alias}.appointedDate",
            "{$alias}.serviceDate",
            "{$alias}.pensionDate",
            "{$alias}.latestAge",
            "{$alias}.role",
            "{$alias}.credit_hour",
            "{$alias}.department",
            "{$alias}.status",
            "{$alias}.staffID as staff_id",
            "{$alias}.staffName as staff_name",
            "{$alias}.email as staff_email",
            "{$alias}.appointedDate as hire_date",
            "{$alias}.pensionDate as retirement_date",
            "{$alias}.credit_hour as total_credit_hours",
            DB::raw('NULL as staff_pic'),
        ]);
    }

    private static function optionalColumn(string $alias, string $table, string $column): string
    {
        return Schema::hasColumn($table, $column) ? $alias.'.'.$column : 'NULL';
    }
}
