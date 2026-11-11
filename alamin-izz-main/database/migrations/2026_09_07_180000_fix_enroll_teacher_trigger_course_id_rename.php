<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * enroll_teacher.course_id was renamed to courseID when the `course` table was reworked.
 * This trigger lives in the database itself (not Laravel schema) so the rename migration
 * couldn't reach it — recreate it referencing the new column name. Fires on every application
 * approval/rejection, so this was a live bug.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS after_enroll_teacher_status_change');
        DB::unprepared('
            CREATE TRIGGER after_enroll_teacher_status_change
            AFTER UPDATE ON enroll_teacher
            FOR EACH ROW
            BEGIN
                IF OLD.status <> NEW.status
                    AND (NEW.status = \'approved\' OR NEW.status = \'rejected\')
                THEN
                    INSERT INTO audit_logs
                        (user_id, action, target_type, target_id, metadata, created_at)
                    VALUES (
                        NULL,
                        CONCAT(\'application_\', NEW.status),
                        \'enroll_teacher\',
                        NEW.teacherID,
                        JSON_OBJECT(
                            \'courseID\',        NEW.courseID,
                            \'previous_status\', OLD.status,
                            \'new_status\',      NEW.status,
                            \'source\',          \'db_trigger\'
                        ),
                        NOW()
                    );
                END IF;
            END
        ');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS after_enroll_teacher_status_change');
        DB::unprepared('
            CREATE TRIGGER after_enroll_teacher_status_change
            AFTER UPDATE ON enroll_teacher
            FOR EACH ROW
            BEGIN
                IF OLD.status <> NEW.status
                    AND (NEW.status = \'approved\' OR NEW.status = \'rejected\')
                THEN
                    INSERT INTO audit_logs
                        (user_id, action, target_type, target_id, metadata, created_at)
                    VALUES (
                        NULL,
                        CONCAT(\'application_\', NEW.status),
                        \'enroll_teacher\',
                        NEW.teacherID,
                        JSON_OBJECT(
                            \'course_id\',       NEW.course_id,
                            \'previous_status\', OLD.status,
                            \'new_status\',      NEW.status,
                            \'source\',          \'db_trigger\'
                        ),
                        NOW()
                    );
                END IF;
            END
        ');
    }
};
