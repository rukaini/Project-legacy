<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_course_target_type_insert_validate');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_course_target_type_update_validate');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_course_target_type_insert_validate
BEFORE INSERT ON course
FOR EACH ROW
BEGIN
    IF FIND_IN_SET('other', NEW.targetAudience) > 0
       AND (NEW.otherTargetAudience IS NULL OR TRIM(NEW.otherTargetAudience) = '') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please specify other target audience.';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_course_target_type_update_validate
BEFORE UPDATE ON course
FOR EACH ROW
BEGIN
    IF FIND_IN_SET('other', NEW.targetAudience) > 0
       AND (NEW.otherTargetAudience IS NULL OR TRIM(NEW.otherTargetAudience) = '') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please specify other target audience.';
    END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_course_target_type_insert_validate');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_course_target_type_update_validate');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_course_target_type_insert_validate
BEFORE INSERT ON course
FOR EACH ROW
BEGIN
    IF FIND_IN_SET('other', NEW.targetAudience) > 0
       AND (NEW.otherTargetAudience IS NULL OR TRIM(NEW.otherTargetAudience) = '') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please specify other target audience.';
    END IF;
    IF NEW.targetAudience = 'new_teacher' AND NEW.courseType <> 'LMS' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'New Teacher only courses must use LMS.';
    END IF;
    IF NEW.targetAudience <> 'new_teacher' AND NEW.courseType <> 'Simple' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Teacher, staff, public, mixed, and other target audiences must use Simple course type.';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_course_target_type_update_validate
BEFORE UPDATE ON course
FOR EACH ROW
BEGIN
    IF FIND_IN_SET('other', NEW.targetAudience) > 0
       AND (NEW.otherTargetAudience IS NULL OR TRIM(NEW.otherTargetAudience) = '') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please specify other target audience.';
    END IF;
    IF NEW.targetAudience = 'new_teacher' AND NEW.courseType <> 'LMS' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'New Teacher only courses must use LMS.';
    END IF;
    IF NEW.targetAudience <> 'new_teacher' AND NEW.courseType <> 'Simple' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Teacher, staff, public, mixed, and other target audiences must use Simple course type.';
    END IF;
END
SQL);
    }
};
