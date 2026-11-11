<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CanonicalAttendance
{
    /**
     * Unified raw attendance read model. The PSM decimal fields are deliberately
     * exposed without assigning undocumented present/absent or hour semantics.
     */
    public static function query(string $alias = 'attendance_record'): Builder
    {
        $queries = [];
        if (Schema::hasTable('attendance')) {
            $status = self::columnOrNull('attendance', 'attendance_status');
            $queries[] = DB::table('attendance')->selectRaw(
                "attendance_id AS record_id, session_id, teacher_id, NULL AS applicant_id, NULL AS outsider_id, NULL AS staff_id, 'Teacher' AS participant_type, {$status} AS attendance_status, timestamp_status, hours_ladap, created_at, updated_at"
            );
        }
        if (Schema::hasTable('attendance_guru_baru')) {
            $status = self::columnOrNull('attendance_guru_baru', 'attendance_status');
            $queries[] = DB::table('attendance_guru_baru')->selectRaw(
                "attendGuruBaru_id AS record_id, session_id, NULL AS teacher_id, gn_id AS applicant_id, NULL AS outsider_id, NULL AS staff_id, 'Guru New' AS participant_type, {$status} AS attendance_status, timestamp_status, hours_ladap, created_at, updated_at"
            );
        }
        if (Schema::hasTable('attendance_outsider')) {
            $status = self::columnOrNull('attendance_outsider', 'attendance_status');
            $queries[] = DB::table('attendance_outsider')->selectRaw(
                "attendOutsider_id AS record_id, session_id, NULL AS teacher_id, NULL AS applicant_id, outsider_id, NULL AS staff_id, 'Outsider' AS participant_type, {$status} AS attendance_status, timestamp_status, NULL AS hours_ladap, created_at, updated_at"
            );
        }
        if (Schema::hasTable('attendance_staff')) {
            $status = self::columnOrNull('attendance_staff', 'attendance_status');
            $queries[] = DB::table('attendance_staff')->selectRaw(
                "attendanceStaff_id AS record_id, session_id, NULL AS teacher_id, NULL AS applicant_id, NULL AS outsider_id, staffID AS staff_id, 'Staff' AS participant_type, {$status} AS attendance_status, timestamp_status, hours_ladap, created_at, updated_at"
            );
        }

        $union = array_shift($queries) ?? DB::query()
            ->selectRaw('NULL AS record_id, NULL AS session_id, NULL AS teacher_id, NULL AS applicant_id, NULL AS outsider_id, NULL AS staff_id, NULL AS participant_type, NULL AS attendance_status, NULL AS timestamp_status, NULL AS hours_ladap, NULL AS created_at, NULL AS updated_at')
            ->whereRaw('1 = 0');
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, $alias);
    }

    public static function displayStatus(mixed $status, string $missing = 'Not Recorded'): string
    {
        return match (strtolower(trim((string) $status))) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => $missing,
        };
    }

    public static function statusKey(mixed $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'pending', 'approved', 'rejected' => strtolower(trim((string) $status)),
            default => 'not-recorded',
        };
    }

    private static function columnOrNull(string $table, string $column): string
    {
        return Schema::hasColumn($table, $column) ? $column : 'NULL';
    }
}
