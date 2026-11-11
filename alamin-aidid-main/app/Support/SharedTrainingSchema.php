<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Small query helper for the canonical shared training tables defined by V4.
 *
 * External records use their VARCHAR business identifiers directly. Presentation
 * aliases keep the existing Trainer Management System view models stable.
 */
final class SharedTrainingSchema
{
    public static function courseInternalExpression(string $alias = 'course'): string
    {
        return $alias.'.courseID';
    }

    public static function trainerInternalColumn(): string
    {
        return 'trainerID';
    }

    public static function trainerInternalExpression(string $alias = 'trainer'): string
    {
        return $alias.'.trainerID';
    }

    public static function sessionInternalExpression(string $alias = 'course_session'): string
    {
        return $alias.'.sessionID';
    }

    public static function courseNameExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'courseName');
    }

    public static function courseCategoryExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'courseCategory');
    }

    public static function courseTypeExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'courseType');
    }

    public static function courseModeExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'mode');
    }

    public static function courseOrganiserExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'organiserName');
    }

    public static function courseWhatsappExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'whatsappGroup');
    }

    public static function coursePosterExpression(string $alias = 'course'): string
    {
        return self::columnOrFallback('course', $alias, 'poster');
    }

    public static function trainerNameExpression(string $alias = 'trainer'): string
    {
        return self::columnOrFallback('trainer', $alias, 'trainerName');
    }

    public static function trainerEmailExpression(string $alias = 'trainer'): string
    {
        return self::columnOrFallback('trainer', $alias, 'trainerEmail');
    }

    public static function trainerPhoneExpression(string $alias = 'trainer'): string
    {
        return self::columnOrFallback('trainer', $alias, 'trainerPhoneNo');
    }

    public static function trainerPictureExpression(string $alias = 'trainer'): string
    {
        return self::columnOrFallback('trainer', $alias, 'trainerPic');
    }

    public static function trainerRatingExpression(string $alias = 'trainer'): string
    {
        return self::columnOrFallback('trainer', $alias, 'averageRating', '0');
    }

    public static function sessionNameExpression(string $alias = 'course_session'): string
    {
        return self::columnOrFallback('course_session', $alias, 'sessionName');
    }

    public static function sessionDateExpression(string $alias = 'course_session'): string
    {
        return self::columnOrFallback('course_session', $alias, 'sessionDate');
    }

    public static function sessionStartExpression(string $alias = 'course_session'): string
    {
        return self::columnOrFallback('course_session', $alias, 'startTime');
    }

    public static function sessionEndExpression(string $alias = 'course_session'): string
    {
        return self::columnOrFallback('course_session', $alias, 'endTime');
    }

    public static function constrainCourse(Builder $query, string $reference, string $alias = 'course'): Builder
    {
        return $query->where($alias.'.courseID', (string) $reference);
    }

    public static function constrainTrainer(Builder $query, string $reference, string $alias = 'trainer'): Builder
    {
        return $query->where($alias.'.trainerID', (string) $reference);
    }

    public static function constrainSession(Builder $query, string $reference, string $alias = 'course_session'): Builder
    {
        return $query->where($alias.'.sessionID', (string) $reference);
    }

    public static function course(string $reference): ?object
    {
        if (! Schema::hasTable('course')) {
            return null;
        }

        return self::constrainCourse(CanonicalTrainingQuery::courses(), $reference)->first();
    }

    public static function trainer(string $reference): ?object
    {
        if (! Schema::hasTable('trainer')) {
            return null;
        }

        return self::constrainTrainer(CanonicalTrainingQuery::trainers(), $reference)->first();
    }

    public static function normaliseTrainer(object $trainer): object
    {
        $trainer->trainer_id = $trainer->trainerID;
        $trainer->trainer_name = $trainer->trainerName ?? null;
        $trainer->trainer_email = $trainer->trainerEmail ?? null;
        $trainer->trainer_phoneNo = $trainer->trainerPhoneNo ?? null;
        $trainer->trainer_pic = $trainer->trainerPic ?? null;

        return $trainer;
    }

    public static function session(string $reference): ?object
    {
        if (! Schema::hasTable('course_session')) {
            return null;
        }

        return self::constrainSession(CanonicalTrainingQuery::sessions(), $reference)->first();
    }

    public static function currentTrainerId(): string
    {
        return trim((string) session('user_id', ''));
    }

    public static function joinCourseSessions(
        Builder $query,
        string $courseAlias = 'course',
        string $sessionAlias = 'course_session',
        string $type = 'inner'
    ): Builder {
        return $query->join(
            "course_session as {$sessionAlias}",
            $courseAlias.'.courseID',
            '=',
            $sessionAlias.'.courseID',
            $type
        );
    }

    public static function joinSessionCourse(
        Builder $query,
        string $sessionAlias = 'course_session',
        string $courseAlias = 'course',
        string $type = 'inner'
    ): Builder {
        return $query->join(
            "course as {$courseAlias}",
            $sessionAlias.'.courseID',
            '=',
            $courseAlias.'.courseID',
            $type
        );
    }

    public static function joinSessionAssignments(
        Builder $query,
        string $sessionAlias = 'course_session',
        string $assignmentAlias = 'session_trainer',
        string $type = 'inner'
    ): Builder {
        return $query->join(
            "session_trainer as {$assignmentAlias}",
            $sessionAlias.'.sessionID',
            '=',
            $assignmentAlias.'.sessionID',
            $type
        );
    }

    public static function joinAssignmentTrainers(
        Builder $query,
        string $assignmentAlias = 'session_trainer',
        string $trainerAlias = 'trainer',
        string $type = 'inner'
    ): Builder {
        return $query->join(
            "trainer as {$trainerAlias}",
            $assignmentAlias.'.trainerID',
            '=',
            $trainerAlias.'.trainerID',
            $type
        );
    }

    public static function constrainAssignmentToTrainer(
        Builder $query,
        string $trainerReference,
        string $assignmentAlias = 'session_trainer'
    ): Builder {
        return $query->where($assignmentAlias.'.trainerID', (string) $trainerReference);
    }

    public static function constrainSessionsToCourse(
        Builder $query,
        object $course,
        string $sessionAlias = 'course_session'
    ): Builder {
        return $query->where($sessionAlias.'.courseID', (string) $course->course_id);
    }

    public static function assignedCourseIds(string $trainerReference): Collection
    {
        if (
            ! Schema::hasTable('course')
            || ! Schema::hasTable('course_session')
            || ! Schema::hasTable('session_trainer')
        ) {
            return collect();
        }

        $query = DB::table('course as course');
        self::joinCourseSessions($query);
        self::joinSessionAssignments($query);

        return self::constrainAssignmentToTrainer($query, $trainerReference)
            ->select('course.courseID')
            ->distinct()
            ->pluck('courseID')
            ->map(static fn ($id): string => (string) $id)
            ->values();
    }

    public static function trainerAssignedToCourse(string $trainerReference, string $courseReference): bool
    {
        $query = DB::table('course_session as course_session');
        self::joinSessionAssignments($query);

        return self::constrainAssignmentToTrainer($query, $trainerReference)
            ->where('course_session.courseID', (string) $courseReference)
            ->exists();
    }

    public static function constrainEnrollmentToCourse(
        Builder $query,
        object $course,
        string $alias = 'enrollment'
    ): Builder {
        return $query->where($alias.'.course_id', (string) $course->course_id);
    }

    public static function joinEnrollmentIdentities(Builder $query, string $enrollmentAlias = 'enrollment'): Builder
    {
        if (Schema::hasTable('teacher')) {
            $query->leftJoin('teacher', $enrollmentAlias.'.teacher_id', '=', 'teacher.teacherID');
        }
        if (Schema::hasTable('guru_new')) {
            $query->leftJoin('guru_new', $enrollmentAlias.'.applicant_id', '=', 'guru_new.gn_id');
        }
        if (Schema::hasTable('outsider')) {
            $query->leftJoin('outsider', $enrollmentAlias.'.outsider_id', '=', 'outsider.outsider_id');
        }
        if (Schema::hasTable('staff_edu')) {
            $query->leftJoin('staff_edu', $enrollmentAlias.'.staff_id', '=', 'staff_edu.staffID');
        }

        return $query;
    }

    public static function enrollmentParticipantNameExpression(string $enrollmentAlias = 'enrollment'): string
    {
        $identityName = self::participantCase($enrollmentAlias, [
            'Teacher' => self::qualifiedColumnOrFallback('teacher', 'teacherName', "'Unknown Participant'"),
            'Guru New' => self::qualifiedColumnOrFallback('guru_new', 'gn_name', "'Unknown New Teacher'"),
            'Outsider' => self::qualifiedColumnOrFallback('outsider', 'name', "'Unknown Participant'"),
            'Staff' => self::qualifiedColumnOrFallback('staff_edu', 'staffName', "'Unknown Staff'"),
        ], "'Unknown Participant'");

        return "COALESCE(NULLIF({$enrollmentAlias}.participant_name, ''), {$identityName})";
    }

    public static function enrollmentParticipantEmailExpression(string $enrollmentAlias = 'enrollment'): string
    {
        $identityEmail = self::participantCase($enrollmentAlias, [
            'Teacher' => self::qualifiedColumnOrFallback('teacher', 'email'),
            'Guru New' => self::qualifiedColumnOrFallback('guru_new', 'email'),
            'Outsider' => 'NULL',
            'Staff' => self::qualifiedColumnOrFallback('staff_edu', 'email'),
        ], 'NULL');

        return "COALESCE(NULLIF({$enrollmentAlias}.participant_email, ''), {$identityEmail})";
    }

    public static function enrollmentParticipantGenderExpression(string $enrollmentAlias = 'enrollment'): string
    {
        return self::participantCase($enrollmentAlias, [
            'Teacher' => self::qualifiedColumnOrFallback('teacher', 'gender'),
            'Guru New' => self::qualifiedColumnOrFallback('guru_new', 'gender'),
            'Outsider' => self::qualifiedColumnOrFallback('outsider', 'gender'),
            'Staff' => self::qualifiedColumnOrFallback('staff_edu', 'gender'),
        ], 'NULL');
    }

    public static function joinNewTeacherEnrollmentIdentities(
        Builder $query,
        string $enrollmentAlias = 'enrollment'
    ): Builder {
        return Schema::hasTable('guru_new')
            ? $query->leftJoin('guru_new', $enrollmentAlias.'.gn_id', '=', 'guru_new.gn_id')
            : $query;
    }

    public static function newTeacherNameExpression(string $fallback = "'Participant'"): string
    {
        return Schema::hasTable('guru_new') && Schema::hasColumn('guru_new', 'gn_name')
            ? "COALESCE(guru_new.gn_name, {$fallback})"
            : $fallback;
    }

    private static function columnOrFallback(
        string $table,
        string $alias,
        string $column,
        string $fallback = 'NULL'
    ): string {
        return Schema::hasColumn($table, $column) ? $alias.'.'.$column : $fallback;
    }

    private static function qualifiedColumnOrFallback(
        string $table,
        string $column,
        string $fallback = 'NULL'
    ): string {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column)
            ? $table.'.'.$column
            : $fallback;
    }

    private static function participantCase(
        string $enrollmentAlias,
        array $expressions,
        string $fallback
    ): string {
        $cases = [];
        foreach ($expressions as $type => $expression) {
            $cases[] = "WHEN {$enrollmentAlias}.participant_type = '{$type}' THEN {$expression}";
        }

        return 'CASE '.implode(' ', $cases).' ELSE '.$fallback.' END';
    }
}
