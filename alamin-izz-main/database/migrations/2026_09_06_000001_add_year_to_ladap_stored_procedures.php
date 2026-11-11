<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS GetTeacherLadapSummary');
        DB::unprepared('
            CREATE PROCEDURE GetTeacherLadapSummary(IN p_teacher_id BIGINT UNSIGNED, IN p_year INT)
            BEGIN
                SELECT
                    t.teacherID,
                    t.teacherName,
                    COUNT(DISTINCT et.course_id)  AS total_trainings,
                    COUNT(a.attendance_id)         AS sessions_attended,
                    COALESCE(SUM(a.hours_ladap), 0.00) AS total_ladap_hours
                FROM teacher t
                LEFT JOIN enroll_teacher et
                    ON t.teacherID = et.teacherID AND et.status = \'approved\'
                LEFT JOIN course_session cs
                    ON et.course_id = cs.course_id AND YEAR(cs.session_date) = p_year
                LEFT JOIN attendance a
                    ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
                WHERE t.teacherID = p_teacher_id
                GROUP BY t.teacherID, t.teacherName;
            END
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS GetSchoolLadapOverview');
        DB::unprepared('
            CREATE PROCEDURE GetSchoolLadapOverview(IN p_school_id BIGINT UNSIGNED, IN p_year INT)
            BEGIN
                SELECT
                    t.teacherID,
                    t.teacherName,
                    t.ICNumber,
                    t.email,
                    COUNT(DISTINCT et.course_id)       AS total_trainings,
                    COUNT(DISTINCT cs.session_id)      AS total_sessions,
                    COUNT(a.attendance_id)             AS sessions_attended,
                    COALESCE(SUM(a.hours_ladap), 0.00) AS total_ladap_hours,
                    CASE
                        WHEN COUNT(DISTINCT cs.session_id) > 0
                        THEN ROUND(
                            COUNT(a.attendance_id) / COUNT(DISTINCT cs.session_id) * 100, 1
                        )
                        ELSE NULL
                    END AS attendance_rate
                FROM teacher t
                LEFT JOIN enroll_teacher et
                    ON t.teacherID = et.teacherID AND et.status = \'approved\'
                LEFT JOIN course_session cs
                    ON et.course_id = cs.course_id AND YEAR(cs.session_date) = p_year
                LEFT JOIN attendance a
                    ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
                WHERE t.schoolID = p_school_id
                GROUP BY t.teacherID, t.teacherName, t.ICNumber, t.email;
            END
        ');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS GetTeacherLadapSummary');
        DB::unprepared('DROP PROCEDURE IF EXISTS GetSchoolLadapOverview');

        DB::unprepared('
            CREATE PROCEDURE GetTeacherLadapSummary(IN p_teacher_id BIGINT UNSIGNED)
            BEGIN
                SELECT
                    t.teacherID,
                    t.teacherName,
                    COUNT(DISTINCT et.course_id)  AS total_trainings,
                    COUNT(a.attendance_id)         AS sessions_attended,
                    COALESCE(SUM(a.hours_ladap), 0.00) AS total_ladap_hours
                FROM teacher t
                LEFT JOIN enroll_teacher et
                    ON t.teacherID = et.teacherID AND et.status = \'approved\'
                LEFT JOIN course_session cs
                    ON et.course_id = cs.course_id
                LEFT JOIN attendance a
                    ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
                WHERE t.teacherID = p_teacher_id
                GROUP BY t.teacherID, t.teacherName;
            END
        ');

        DB::unprepared('
            CREATE PROCEDURE GetSchoolLadapOverview(IN p_school_id BIGINT UNSIGNED)
            BEGIN
                SELECT
                    t.teacherID,
                    t.teacherName,
                    t.ICNumber,
                    t.email,
                    COUNT(DISTINCT et.course_id)       AS total_trainings,
                    COUNT(DISTINCT cs.session_id)      AS total_sessions,
                    COUNT(a.attendance_id)             AS sessions_attended,
                    COALESCE(SUM(a.hours_ladap), 0.00) AS total_ladap_hours,
                    CASE
                        WHEN COUNT(DISTINCT cs.session_id) > 0
                        THEN ROUND(
                            COUNT(a.attendance_id) / COUNT(DISTINCT cs.session_id) * 100, 1
                        )
                        ELSE NULL
                    END AS attendance_rate
                FROM teacher t
                LEFT JOIN enroll_teacher et
                    ON t.teacherID = et.teacherID AND et.status = \'approved\'
                LEFT JOIN course_session cs
                    ON et.course_id = cs.course_id
                LEFT JOIN attendance a
                    ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
                WHERE t.schoolID = p_school_id
                GROUP BY t.teacherID, t.teacherName, t.ICNumber, t.email;
            END
        ');
    }
};
