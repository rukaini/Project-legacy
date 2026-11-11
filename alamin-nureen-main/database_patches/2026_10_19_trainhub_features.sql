-- TrainHub 19 Oct 2026: apply to a BACKED-UP TEST DATABASE first.
-- DO NOT import the attached tarbiah/attendance SQL dumps (they contain DROP TABLE).
-- Run each numbered statement in DBeaver; do not run twice without checking existing objects.
-- 1. Training course-level credit hours. NULL means use scheduled session duration.
-- This block safely skips an existing column on MySQL 8.0.
SET @trainhub_add_course_hours := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `course` ADD COLUMN `credit_hours` DECIMAL(6,2) NULL DEFAULT NULL',
    'SELECT ''course.credit_hours already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course' AND COLUMN_NAME = 'credit_hours'
);
PREPARE trainhub_course_stmt FROM @trainhub_add_course_hours;
EXECUTE trainhub_course_stmt;
DEALLOCATE PREPARE trainhub_course_stmt;

-- Tarbiah from the friend's 15 Oct SQL dump already includes credit_hours.
-- Older canonical databases did not. Add only when missing.
SET @trainhub_add_tarbiah_hours := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tarbiah` ADD COLUMN `credit_hours` DECIMAL(5,2) NULL DEFAULT NULL',
    'SELECT ''tarbiah.credit_hours already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tarbiah' AND COLUMN_NAME = 'credit_hours'
);
PREPARE trainhub_tarbiah_stmt FROM @trainhub_add_tarbiah_hours;
EXECUTE trainhub_tarbiah_stmt;
DEALLOCATE PREPARE trainhub_tarbiah_stmt;

-- 2. Settings. Numeric values are validated by Laravel before saving.
CREATE TABLE IF NOT EXISTS `trainhub_settings` (
  `setting_key` VARCHAR(80) NOT NULL PRIMARY KEY,
  `setting_value` VARCHAR(30) NOT NULL,
  `updated_by` VARCHAR(20) NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_trainhub_settings_staff` FOREIGN KEY (`updated_by`) REFERENCES `staff_edu` (`staffID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `trainhub_settings` (`setting_key`,`setting_value`) VALUES
  ('new_teacher_lms_required','5'),
  ('training_target_hours','30'),
  ('tarbiah_target_hours','10'),
  ('ladap_target_hours','30'),
  ('trainer_multiplier','1.5'),
  ('alamin_multiplier','1.0'),
  ('other_multiplier','0.5');

-- 3. Random short links; the public URL contains a random opaque token, not a DB ID.
CREATE TABLE IF NOT EXISTS `trainhub_short_links` (
  `token` VARCHAR(48) NOT NULL PRIMARY KEY,
  `link_type` ENUM('attendance','feedback_participant','feedback_pic') NOT NULL,
  `target_id` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_trainhub_short_target` (`link_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Staff EDU Tarbiah attendance is DISTINCT from tarbiah_attendance (teacher only).
-- Your TrainHub canonical schema already has this table; CREATE IF NOT EXISTS
-- will only add it if your connected test database is missing it.
CREATE TABLE IF NOT EXISTS `staff_tarbiah_attendance` (
  `staff_tarbiah_attendance_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staffID` VARCHAR(20) NOT NULL,
  `tarbiah_id` BIGINT UNSIGNED NOT NULL,
  `attendance_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`staff_tarbiah_attendance_id`),
  UNIQUE KEY `uq_staff_tarbiah_attendance` (`staffID`,`tarbiah_id`),
  CONSTRAINT `fk_staff_tarbiah_staff` FOREIGN KEY (`staffID`) REFERENCES `staff_edu` (`staffID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_staff_tarbiah_tarbiah` FOREIGN KEY (`tarbiah_id`) REFERENCES `tarbiah` (`tarbiah_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_staff_tarbiah_status` CHECK (`attendance_status` IN ('pending','approved','rejected'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The friend's penyelaras_ladap.sql already has credit_hours.
-- LADAP target is configurable but Staff EDU does not earn LADAP credit.

-- 5. IMPORTANT: older databases CHECK staff_edu.credit_hour <=40.
-- If EDU raises goals above 40, a controlled DBA schema update is required:
-- ALTER TABLE staff_edu DROP CHECK chk_staff_edu_1;
-- ALTER TABLE staff_edu ADD CONSTRAINT chk_staff_edu_nonnegative CHECK (credit_hour BETWEEN 0 AND 999.99);
-- Do not execute these two lines on the shared DB without approval.
