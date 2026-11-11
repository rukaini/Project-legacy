<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CanonicalEnrollment
{
    /**
     * Unified read model over the authoritative V4 enrollment/participant tables.
     * It is a query subselect only; no compatibility table or view is created.
     */
    public static function query(string $alias = 'enrollment'): Builder
    {
        $queries = [];
        if (Schema::hasTable('enroll_teacher')) {
            $teacherId = self::columnOrNull('enroll_teacher', 'teacherID');
            $status = self::columnOrNull('enroll_teacher', 'status');
            $appliedAt = self::columnOrNull('enroll_teacher', 'applied_at');
            $query = DB::table('enroll_teacher');
            self::joinCourseParticipant($query, 'cp_teacher', 'teacherID', 'teacherID', 'teacher');
            $queries[] = $query->selectRaw(
                "NULL AS enrollment_id, enroll_teacher.course_id, {$teacherId} AS teacher_id, NULL AS applicant_id, NULL AS outsider_id, NULL AS staff_id, ".self::participantColumn('cp_teacher', 'participantID').' AS participant_id, '.self::participantColumn('cp_teacher', 'participantName').' AS participant_name, '.self::participantColumn('cp_teacher', 'email').' AS participant_email, '.self::participantColumn('cp_teacher', 'phoneNumber').' AS participant_phone, '.self::participantColumn('cp_teacher', 'organisationName')." AS participant_organisation, 'Teacher' AS participant_type, {$status} AS approval_status, {$appliedAt} AS enrolled_at"
            );
        }
        if (Schema::hasTable('enroll_guru_baru')) {
            $enrollmentId = self::columnOrNull('enroll_guru_baru', 'enrollment_id');
            $newTeacherId = self::columnOrNull('enroll_guru_baru', 'gn_id');
            $status = self::columnOrNull('enroll_guru_baru', 'status');
            $appliedAt = self::columnOrNull('enroll_guru_baru', 'applied_at');
            $query = DB::table('enroll_guru_baru');
            self::joinCourseParticipant($query, 'cp_new_teacher', 'gn_id', 'gn_id', 'new_teacher');
            $queries[] = $query->selectRaw(
                "{$enrollmentId} AS enrollment_id, enroll_guru_baru.course_id, NULL AS teacher_id, {$newTeacherId} AS applicant_id, NULL AS outsider_id, NULL AS staff_id, ".self::participantColumn('cp_new_teacher', 'participantID').' AS participant_id, '.self::participantColumn('cp_new_teacher', 'participantName').' AS participant_name, '.self::participantColumn('cp_new_teacher', 'email').' AS participant_email, '.self::participantColumn('cp_new_teacher', 'phoneNumber').' AS participant_phone, '.self::participantColumn('cp_new_teacher', 'organisationName')." AS participant_organisation, 'Guru New' AS participant_type, {$status} AS approval_status, {$appliedAt} AS enrolled_at"
            );
        }
        if (Schema::hasTable('enroll_outsider')) {
            $outsiderId = self::columnOrNull('enroll_outsider', 'outsider_id');
            $status = self::columnOrNull('enroll_outsider', 'status');
            $appliedAt = self::columnOrNull('enroll_outsider', 'applied_at');
            $query = DB::table('enroll_outsider');
            self::joinCourseParticipant($query, 'cp_public', 'outsider_id', 'outsider_id', 'public');
            $queries[] = $query->selectRaw(
                "NULL AS enrollment_id, enroll_outsider.course_id, NULL AS teacher_id, NULL AS applicant_id, {$outsiderId} AS outsider_id, NULL AS staff_id, ".self::participantColumn('cp_public', 'participantID').' AS participant_id, '.self::participantColumn('cp_public', 'participantName').' AS participant_name, '.self::participantColumn('cp_public', 'email').' AS participant_email, '.self::participantColumn('cp_public', 'phoneNumber').' AS participant_phone, '.self::participantColumn('cp_public', 'organisationName')." AS participant_organisation, 'Outsider' AS participant_type, {$status} AS approval_status, {$appliedAt} AS enrolled_at"
            );
        }
        if (Schema::hasTable('course_participant')) {
            $queries[] = DB::table('course_participant as cp_staff')
                ->whereRaw('LOWER(cp_staff.participantType) = ?', ['staff'])
                ->selectRaw(
                    'NULL AS enrollment_id, cp_staff.courseID AS course_id, NULL AS teacher_id, NULL AS applicant_id, NULL AS outsider_id, cp_staff.staffID AS staff_id, cp_staff.participantID AS participant_id, cp_staff.participantName AS participant_name, cp_staff.email AS participant_email, '.self::participantColumn('cp_staff', 'phoneNumber').' AS participant_phone, '.self::participantColumn('cp_staff', 'organisationName')." AS participant_organisation, 'Staff' AS participant_type, NULL AS approval_status, NULL AS enrolled_at"
                );
        }

        $union = array_shift($queries) ?? DB::query()
            ->selectRaw('NULL AS enrollment_id, NULL AS course_id, NULL AS teacher_id, NULL AS applicant_id, NULL AS outsider_id, NULL AS staff_id, NULL AS participant_id, NULL AS participant_name, NULL AS participant_email, NULL AS participant_phone, NULL AS participant_organisation, NULL AS participant_type, NULL AS approval_status, NULL AS enrolled_at')
            ->whereRaw('1 = 0');
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, $alias);
    }

    private static function columnOrNull(string $table, string $column): string
    {
        return Schema::hasColumn($table, $column) ? $table.'.'.$column : 'NULL';
    }

    private static function joinCourseParticipant(
        Builder $query,
        string $alias,
        string $enrollmentColumn,
        string $participantColumn,
        string $participantType
    ): void {
        if (! Schema::hasTable('course_participant')) {
            return;
        }

        $table = $query->from;
        $query->leftJoin("course_participant as {$alias}", function ($join) use ($alias, $table, $enrollmentColumn, $participantColumn, $participantType): void {
            $join->on("{$alias}.{$participantColumn}", '=', "{$table}.{$enrollmentColumn}")
                ->on("{$alias}.courseID", '=', "{$table}.course_id")
                ->whereRaw("LOWER({$alias}.participantType) = ?", [$participantType]);
        });
    }

    private static function participantColumn(string $alias, string $column): string
    {
        return Schema::hasTable('course_participant') && Schema::hasColumn('course_participant', $column)
            ? "{$alias}.{$column}"
            : 'NULL';
    }
}
