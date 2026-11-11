-- Al Amin Edu Oasis - Trainer Management canonical deployment schema
-- MySQL 8.x, STRUCTURE ONLY. No user rows, credential hashes, PII values, or sample-row INSERTS.
--
-- Contract precedence:
--   * guru_new: edudb owner
--   * teacher: edu owner
--   * course/session/feedback/QR/session assignment/staff/trainer: fyp2 owner
--   * enrollment/outsider/submission/attendance/training logs: PSM Assessment owner
--   * trainer_document: agreed shared contract
--   * Trainer-owned objects: Trainer Management owner, with canonical string FKs
--   * participant_material_access: later Aidid-owned integration extension
--
-- Integration adaptations are limited to dependent identifier types and storage
-- engine/collation compatibility needed for valid MySQL foreign keys.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Laravel framework tables
-- ============================================================

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (`key` varchar(255) NOT NULL, `value` mediumtext NOT NULL, `expiration` int NOT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `cache_locks` (`key` varchar(255) NOT NULL, `owner` varchar(255) NOT NULL, `expiration` int NOT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `jobs` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `queue` varchar(255) NOT NULL, `payload` longtext NOT NULL, `attempts` tinyint unsigned NOT NULL, `reserved_at` int unsigned NULL, `available_at` int unsigned NOT NULL, `created_at` int unsigned NOT NULL, PRIMARY KEY (`id`), KEY `jobs_queue_index` (`queue`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `job_batches` (`id` varchar(255) NOT NULL, `name` varchar(255) NOT NULL, `total_jobs` int NOT NULL, `pending_jobs` int NOT NULL, `failed_jobs` int NOT NULL, `failed_job_ids` longtext NOT NULL, `options` mediumtext NULL, `cancelled_at` int NULL, `created_at` int NOT NULL, `finished_at` int NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `failed_jobs` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `uuid` varchar(255) NOT NULL, `connection` text NOT NULL, `queue` text NOT NULL, `payload` longtext NOT NULL, `exception` longtext NOT NULL, `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BEGIN LARAVEL-MIGRATION-BUSINESS-SCHEMA
-- ============================================================
-- External/shared masters and their required owner dependencies
-- ============================================================

CREATE TABLE `hr_administrator` (
  `hrID` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `hrname` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'hr',
  PRIMARY KEY (`hrID`), UNIQUE KEY `hr_administrator_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `school` (
  `schoolID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `school_name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `school_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `register_date` date NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `total_teacher` int DEFAULT 0, `vacancy` int DEFAULT 0, `capacity` int DEFAULT 0,
  PRIMARY KEY (`schoolID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `guru_new` (
  `gn_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ic_number` varchar(20) NOT NULL, `gn_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL, `email` varchar(100) NOT NULL,
  `marital_status` enum('Single','Married','Divorced') DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL, `address` varchar(255) DEFAULT NULL,
  `race` varchar(50) DEFAULT NULL, `appointed_date` date NOT NULL,
  `role` varchar(20) DEFAULT 'new_teacher',
  `current_status` enum('Inactive','Active','Complete') NOT NULL DEFAULT 'Inactive',
  `hrID` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `schoolID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`gn_id`), UNIQUE KEY `guru_new_ic_unique` (`ic_number`),
  UNIQUE KEY `guru_new_email_unique` (`email`), KEY `guru_new_hr_index` (`hrID`),
  KEY `idx_guru_new_school_status` (`schoolID`,`current_status`),
  CONSTRAINT `fk_guru_new_school` FOREIGN KEY (`schoolID`) REFERENCES `school` (`schoolID`),
  CONSTRAINT `fk_guru_new_hr` FOREIGN KEY (`hrID`) REFERENCES `hr_administrator` (`hrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `teacher` (
  `teacherID` varchar(20) NOT NULL, `teacherName` varchar(250) NOT NULL,
  `ICNumber` varchar(20) NOT NULL, `phoneNumber` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL, `maritalStatus` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL, `address` varchar(255) NOT NULL,
  `race` varchar(50) NOT NULL, `appointedDate` date NOT NULL,
  `serviceDate` varchar(20) NOT NULL, `pensionDate` date NOT NULL,
  `latestAge` int NOT NULL, `password` varchar(255) NOT NULL,
  `password_change_required` tinyint(1) NOT NULL DEFAULT 1,
  `role` varchar(20) NOT NULL DEFAULT 'teacher',
  `schoolID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `resignation_request_date` date DEFAULT NULL,
  `resignation_request_reason` text DEFAULT NULL,
  `resignation_request_status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`teacherID`), UNIQUE KEY `uq_teacher_ic` (`ICNumber`),
  KEY `idx_teacher_school` (`schoolID`),
  CONSTRAINT `fk_teacher_school` FOREIGN KEY (`schoolID`) REFERENCES `school` (`schoolID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `staff_edu` (
  `staffID` varchar(20) NOT NULL, `staffName` varchar(250) NOT NULL,
  `ICNumber` varchar(20) DEFAULT NULL, `phoneNumber` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL, `maritalStatus` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL, `address` varchar(255) DEFAULT NULL,
  `race` varchar(50) DEFAULT NULL, `appointedDate` date DEFAULT NULL,
  `serviceDate` varchar(20) DEFAULT NULL, `pensionDate` date DEFAULT NULL,
  `latestAge` int DEFAULT NULL, `password` varchar(255) NOT NULL,
  `password_changed_required` tinyint(1) DEFAULT 1,
  `role` varchar(20) DEFAULT 'STAFF_EDU', `credit_hour` decimal(5,2) DEFAULT 0.00,
  `department` varchar(50) DEFAULT NULL, `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`staffID`), UNIQUE KEY `uq_staff_edu_ic` (`ICNumber`),
  UNIQUE KEY `uq_staff_edu_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trainer` (
  `trainerID` varchar(20) NOT NULL, `trainerName` varchar(255) NOT NULL,
  `trainerIC` varchar(20) DEFAULT NULL, `trainerEmail` varchar(255) DEFAULT NULL,
  `trainerPhoneNo` varchar(20) DEFAULT NULL, `expertise` text DEFAULT NULL,
  `trainerPic` varchar(255) DEFAULT NULL,
  `trainerPassword` varchar(255) NOT NULL,
  `trainer_changed_password` tinyint(1) DEFAULT 1,
  `averageRating` decimal(3,2) DEFAULT 0.00, `status` varchar(20) DEFAULT 'active',
  `paymentStatus` varchar(20) DEFAULT 'unpaid',
  PRIMARY KEY (`trainerID`), UNIQUE KEY `uq_trainer_email` (`trainerEmail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course` (
  `courseID` varchar(20) NOT NULL, `courseName` varchar(255) NOT NULL,
  `description` text DEFAULT NULL, `capacity` int NOT NULL,
  `price` decimal(10,2) DEFAULT NULL, `courseCategory` varchar(100) DEFAULT NULL,
  `targetAudience` set('teacher','new_teacher','staff','public','other') NOT NULL DEFAULT 'teacher',
  `otherTargetAudience` varchar(255) DEFAULT NULL, `staffAttendeeIDs` text DEFAULT NULL,
  `staffAttendeeAssignedBy` varchar(20) DEFAULT NULL, `staffAttendeeAssignedDate` date DEFAULT NULL,
  `courseRating` decimal(3,2) DEFAULT 0.00, `status` varchar(20) DEFAULT 'upcoming',
  `poster` varchar(255) DEFAULT NULL, `mode` varchar(20) DEFAULT NULL,
  `onlineLink` varchar(255) DEFAULT NULL, `whatsappGroup` varchar(255) DEFAULT NULL,
  `closeDate` date DEFAULT NULL, `organiserName` varchar(255) DEFAULT NULL,
  `courseType` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`courseID`), KEY `idx_course_targetAudience` (`targetAudience`),
  KEY `idx_course_attendee_assigned_by` (`staffAttendeeAssignedBy`),
  CONSTRAINT `fk_course_attendee_assigned_by` FOREIGN KEY (`staffAttendeeAssignedBy`) REFERENCES `staff_edu` (`staffID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `applicant` (
  `applicant_id` bigint unsigned NOT NULL AUTO_INCREMENT, `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL, `password` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL, `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`applicant_id`), UNIQUE KEY `applicant_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `outsider` (
  `outsider_id` bigint unsigned NOT NULL AUTO_INCREMENT, `name` varchar(255) DEFAULT NULL,
  `ICNumber` varchar(255) DEFAULT NULL, `phoneNumber` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL, `organization` varchar(255) DEFAULT NULL,
  `contact_no` varchar(255) DEFAULT NULL, `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`outsider_id`), UNIQUE KEY `outsider_icnumber_unique` (`ICNumber`),
  KEY `outsider_user_id_foreign` (`user_id`),
  CONSTRAINT `outsider_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course_session` (
  `sessionID` varchar(20) NOT NULL, `sessionDate` date NOT NULL,
  `sessionName` varchar(255) NOT NULL, `startTime` time NOT NULL,
  `endTime` time NOT NULL, `location` varchar(255) DEFAULT NULL,
  `courseID` varchar(20) NOT NULL,
  PRIMARY KEY (`sessionID`), KEY `idx_course_session_course` (`courseID`,`sessionDate`),
  CONSTRAINT `fk_course_session_course` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `session_trainer` (
  `trainerID` varchar(20) NOT NULL, `sessionID` varchar(20) NOT NULL,
  PRIMARY KEY (`trainerID`,`sessionID`), KEY `idx_session_trainer_session` (`sessionID`),
  CONSTRAINT `fk_session_trainer_trainer` FOREIGN KEY (`trainerID`) REFERENCES `trainer` (`trainerID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_session_trainer_session` FOREIGN KEY (`sessionID`) REFERENCES `course_session` (`sessionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `qr_session` (
  `qrID` varchar(20) NOT NULL, `qrCode` text NOT NULL,
  `attendanceLink` varchar(255) DEFAULT NULL, `expiryTime` datetime DEFAULT NULL,
  `sessionID` varchar(20) NOT NULL,
  PRIMARY KEY (`qrID`), UNIQUE KEY `uq_qr_session_session` (`sessionID`),
  CONSTRAINT `fk_qr_session_session` FOREIGN KEY (`sessionID`) REFERENCES `course_session` (`sessionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PSM Assessment enrollment, outsider, attendance, and log contracts
-- Dependent Course/Session/Teacher columns use canonical VARCHAR(20) masters.
-- ============================================================

CREATE TABLE `enroll_teacher` (
  `teacherID` varchar(20) NOT NULL, `course_id` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text NULL, `applied_at` timestamp NOT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`teacherID`,`course_id`), KEY `enroll_teacher_course_id_foreign` (`course_id`),
  CONSTRAINT `enroll_teacher_teacherid_foreign` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE CASCADE,
  CONSTRAINT `enroll_teacher_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enroll_guru_baru` (
  `enrollment_id` bigint unsigned NOT NULL AUTO_INCREMENT, `applicant_id` bigint unsigned NOT NULL,
  `course_id` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected','auto_enrolled') NOT NULL DEFAULT 'pending',
  `rejection_reason` text NULL, `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`enrollment_id`), UNIQUE KEY `enroll_guru_baru_applicant_id_course_id_unique` (`applicant_id`,`course_id`),
  KEY `enroll_guru_baru_course_id_foreign` (`course_id`),
  CONSTRAINT `enroll_guru_baru_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicant` (`applicant_id`) ON DELETE CASCADE,
  CONSTRAINT `enroll_guru_baru_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enroll_outsider` (
  `outsider_id` bigint unsigned NOT NULL, `course_id` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text NULL, `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`outsider_id`,`course_id`), KEY `enroll_outsider_course_id_foreign` (`course_id`),
  CONSTRAINT `enroll_outsider_outsider_id_foreign` FOREIGN KEY (`outsider_id`) REFERENCES `outsider` (`outsider_id`) ON DELETE CASCADE,
  CONSTRAINT `enroll_outsider_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance` (
  `attendance_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_status` decimal(5,2) NOT NULL DEFAULT 0.00,
  `hours_ladap` decimal(5,2) NOT NULL DEFAULT 0.00,
  `teacher_id` varchar(20) NOT NULL, `session_id` varchar(20) NOT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`attendance_id`), UNIQUE KEY `attendance_teacher_id_session_id_unique` (`teacher_id`,`session_id`),
  KEY `attendance_session_id_foreign` (`session_id`),
  CONSTRAINT `attendance_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacherID`) ON DELETE CASCADE,
  CONSTRAINT `attendance_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `course_session` (`sessionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance_guru_baru` (
  `attendGuruBaru_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_status` decimal(5,2) NOT NULL DEFAULT 0.00,
  `hours_ladap` decimal(5,2) NOT NULL DEFAULT 0.00,
  `applicant_id` bigint unsigned NOT NULL, `session_id` varchar(20) NOT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`attendGuruBaru_id`), UNIQUE KEY `attendance_guru_baru_applicant_id_session_id_unique` (`applicant_id`,`session_id`),
  KEY `attendance_guru_baru_session_id_foreign` (`session_id`),
  CONSTRAINT `attendance_guru_baru_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicant` (`applicant_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_guru_baru_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `course_session` (`sessionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance_outsider` (
  `attendOutsider_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_status` decimal(5,2) NOT NULL DEFAULT 0.00,
  `outsider_id` bigint unsigned NOT NULL, `session_id` varchar(20) NOT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`attendOutsider_id`), UNIQUE KEY `attendance_outsider_outsider_id_session_id_unique` (`outsider_id`,`session_id`),
  KEY `attendance_outsider_session_id_foreign` (`session_id`),
  CONSTRAINT `attendance_outsider_outsider_id_foreign` FOREIGN KEY (`outsider_id`) REFERENCES `outsider` (`outsider_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_outsider_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `course_session` (`sessionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Trainer-owned tables (canonical external reference types)
-- ============================================================

-- participant_material_access.course_id = course.courseID.
CREATE TABLE `participant_material_access` (
  `course_id` varchar(20) NOT NULL, `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `access_until` datetime DEFAULT NULL, `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`course_id`),
  CONSTRAINT `participant_material_access_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `training_module` (
  `module_id` int NOT NULL AUTO_INCREMENT, `course_id` varchar(20) NOT NULL,
  `module_title` varchar(255) NOT NULL, `module_type` varchar(100) NOT NULL,
  `module_order` int DEFAULT 1, `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`module_id`), UNIQUE KEY `uq_training_module_course_type` (`course_id`,`module_type`),
  CONSTRAINT `fk_training_module_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `learning_material` (
  `material_id` bigint unsigned NOT NULL AUTO_INCREMENT, `course_id` varchar(20) NOT NULL,
  `trainer_id` varchar(20) DEFAULT NULL, `session_id` varchar(20) DEFAULT NULL,
  `original_file_name` varchar(255) NOT NULL, `file_path` varchar(255) NOT NULL,
  `material_type` varchar(50) NOT NULL, `metadata_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `extracted_metadata` text NULL, `ai_generated_title` varchar(255) DEFAULT NULL,
  `ai_synopsis` text NULL, `uploaded_at` timestamp NULL,
  PRIMARY KEY (`material_id`), KEY `learning_material_course_id_foreign` (`course_id`),
  KEY `learning_material_trainer_id_foreign` (`trainer_id`), KEY `learning_material_session_id_foreign` (`session_id`),
  CONSTRAINT `learning_material_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  CONSTRAINT `learning_material_trainer_id_foreign` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainerID`) ON DELETE SET NULL,
  CONSTRAINT `learning_material_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `course_session` (`sessionID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assessment` (
  `assessment_id` bigint unsigned NOT NULL AUTO_INCREMENT, `course_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL, `type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Draft', `module_id` int DEFAULT NULL,
  `instruction_text` text NULL, `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL, `accepted_format` varchar(100) DEFAULT NULL,
  `time_limit_minutes` int DEFAULT NULL, `max_attempts` int DEFAULT NULL,
  `total_marks` decimal(6,2) DEFAULT NULL, `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP, `weight_percent` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`assessment_id`), KEY `assessment_course_id_foreign` (`course_id`), KEY `fk_assessment_module` (`module_id`),
  CONSTRAINT `assessment_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_module` FOREIGN KEY (`module_id`) REFERENCES `training_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assessment_question` (
  `question_id` bigint unsigned NOT NULL AUTO_INCREMENT, `assessment_id` bigint unsigned NOT NULL,
  `question_text` text NOT NULL, `question_image_path` varchar(255) DEFAULT NULL,
  `question_order` int DEFAULT 1, `question_type` varchar(50) DEFAULT NULL,
  `options_json` json DEFAULT NULL, `correct_answer` text NULL,
  `marks` decimal(5,2) DEFAULT 1.00, `grading_rubric` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`), KEY `assessment_question_assessment_id_foreign` (`assessment_id`),
  CONSTRAINT `assessment_question_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `training_content_block` (
  `block_id` bigint NOT NULL AUTO_INCREMENT, `module_id` int NOT NULL,
  `block_type` varchar(50) NOT NULL, `block_title` varchar(255) DEFAULT NULL,
  `block_content` text NULL, `external_url` varchar(500) DEFAULT NULL,
  `material_id` bigint unsigned DEFAULT NULL, `assessment_id` bigint unsigned DEFAULT NULL,
  `block_order` int DEFAULT 1, `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_id`), KEY `fk_content_block_module` (`module_id`),
  KEY `fk_content_block_material` (`material_id`), KEY `fk_content_block_assessment` (`assessment_id`),
  CONSTRAINT `fk_content_block_module` FOREIGN KEY (`module_id`) REFERENCES `training_module` (`module_id`),
  CONSTRAINT `fk_content_block_material` FOREIGN KEY (`material_id`) REFERENCES `learning_material` (`material_id`),
  CONSTRAINT `fk_content_block_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course_proposal` (
  `proposal_id` int unsigned NOT NULL AUTO_INCREMENT, `trainer_id` varchar(20) DEFAULT NULL,
  `proposed_title` varchar(255) NOT NULL,
  `target_audience` text NULL, `objective` text NULL, `description` text NULL,
  `training_mode` varchar(30) DEFAULT NULL, `delivery_mode` varchar(30) DEFAULT NULL,
  `estimated_capacity` int DEFAULT NULL, `proposed_start_date` date DEFAULT NULL,
  `proposed_end_date` date DEFAULT NULL, `document_path` varchar(255) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL, `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `admin_comment` text NULL, `reviewed_by` varchar(20) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL, `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`proposal_id`), KEY `idx_proposal_trainer` (`trainer_id`), KEY `idx_proposal_status` (`status`),
  KEY `idx_proposal_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_course_proposal_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainerID`) ON DELETE SET NULL,
  CONSTRAINT `fk_course_proposal_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `staff_edu` (`staffID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teaching_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT, `course_id` varchar(20) NOT NULL,
  `trainer_id` varchar(20) NOT NULL, `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL, `mime_type` varchar(150) NOT NULL,
  `file_size` bigint unsigned NOT NULL, `submitted_at` timestamp NOT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `teaching_plans_course_id_trainer_id_unique` (`course_id`,`trainer_id`),
  KEY `teaching_plans_course_id_index` (`course_id`), KEY `teaching_plans_trainer_id_index` (`trainer_id`),
  CONSTRAINT `fk_teaching_plans_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  CONSTRAINT `fk_teaching_plans_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainerID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teaching_plan_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT, `teaching_plan_id` bigint unsigned NOT NULL,
  `reviewer_staff_id` varchar(20) NOT NULL, `comment` text NOT NULL,
  `seen_by_trainer_at` timestamp NULL, `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`id`), KEY `teaching_plan_comments_teaching_plan_id_index` (`teaching_plan_id`),
  KEY `teaching_plan_comments_reviewer_staff_id_index` (`reviewer_staff_id`),
  CONSTRAINT `fk_teaching_plan_comments_plan` FOREIGN KEY (`teaching_plan_id`) REFERENCES `teaching_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_teaching_plan_comments_staff` FOREIGN KEY (`reviewer_staff_id`) REFERENCES `staff_edu` (`staffID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trainer_activity_log` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT, `trainer_id` varchar(20) NOT NULL,
  `action` varchar(60) NOT NULL, `detail` varchar(255) DEFAULT NULL,
  `course_id` varchar(20) DEFAULT NULL, `target_type` varchar(40) DEFAULT NULL,
  `target_id` bigint DEFAULT NULL, `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`), KEY `idx_trainer` (`trainer_id`), KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`), KEY `idx_activity_course` (`course_id`),
  CONSTRAINT `fk_activity_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainerID`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trainer_certificate` (
  `cert_id` int NOT NULL AUTO_INCREMENT, `trainer_id` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL, `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cert_id`), KEY `idx_tc_trainer` (`trainer_id`),
  CONSTRAINT `fk_trainer_certificate_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainerID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PSM submission and learner logs (after Trainer-owned targets)
-- ============================================================

CREATE TABLE `assessment_submission` (
  `submission_id` bigint unsigned NOT NULL AUTO_INCREMENT, `assessment_id` bigint unsigned NOT NULL,
  `enrollment_id` bigint unsigned NOT NULL, `submitted_file_path` varchar(255) DEFAULT NULL,
  `original_file_name` varchar(255) DEFAULT NULL, `answers_json` json DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL, `status` varchar(50) DEFAULT NULL,
  `grade` decimal(8,2) DEFAULT NULL, `feedback` text NULL,
  `graded_by_trainer_id` varchar(20) DEFAULT NULL, `graded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`submission_id`), KEY `assessment_submission_assessment_id_foreign` (`assessment_id`),
  KEY `idx_assessment_submission_enrollment` (`enrollment_id`), KEY `idx_assessment_submission_grader` (`graded_by_trainer_id`),
  CONSTRAINT `assessment_submission_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_submission_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enroll_guru_baru` (`enrollment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_submission_grader` FOREIGN KEY (`graded_by_trainer_id`) REFERENCES `trainer` (`trainerID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `training_access_log` (
  `access_id` bigint unsigned NOT NULL AUTO_INCREMENT, `course_id` varchar(20) NOT NULL,
  `enrollment_id` bigint unsigned NOT NULL, `accessed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`access_id`), KEY `training_access_log_course_id_enrollment_id_index` (`course_id`,`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `training_content_interaction_log` (
  `interaction_id` bigint unsigned NOT NULL AUTO_INCREMENT, `course_id` varchar(20) NOT NULL,
  `module_id` int NOT NULL, `block_id` bigint NOT NULL,
  `enrollment_id` bigint unsigned NOT NULL, `first_opened_at` datetime DEFAULT NULL,
  `last_accessed_at` datetime DEFAULT NULL, `open_count` int unsigned NOT NULL DEFAULT 0,
  `total_seconds` int unsigned NOT NULL DEFAULT 0, `last_event_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`interaction_id`),
  UNIQUE KEY `training_content_interaction_log_enrollment_id_block_id_unique` (`enrollment_id`,`block_id`),
  KEY `training_content_interaction_log_block_id_index` (`block_id`),
  KEY `training_content_interaction_log_enrollment_id_index` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- fyp2 feedback contracts (the removed question constraint is intentionally absent)
-- ============================================================

CREATE TABLE `course_participant` (
  `participantID` varchar(20) NOT NULL, `participantType` varchar(20) NOT NULL,
  `participantName` varchar(255) NOT NULL, `organisationName` varchar(255) DEFAULT NULL,
  `ICNumber` varchar(20) DEFAULT NULL, `phoneNumber` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL, `paymentProof` varchar(255) DEFAULT NULL,
  `RSVPStatus` varchar(30) DEFAULT 'Will come', `isFeedbackCompleted` tinyint(1) DEFAULT 0,
  `replacementStatus` varchar(20) DEFAULT 'original', `replacedByName` varchar(255) DEFAULT NULL,
  `replacementReason` text DEFAULT NULL, `courseID` varchar(20) NOT NULL,
  `gn_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `outsider_id` bigint unsigned DEFAULT NULL, `teacherID` varchar(20) DEFAULT NULL,
  `staffID` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`participantID`), KEY `idx_course_participant_course` (`courseID`),
  KEY `idx_course_participant_gn` (`gn_id`), KEY `idx_course_participant_outsider` (`outsider_id`),
  KEY `idx_course_participant_teacher` (`teacherID`), KEY `idx_course_participant_staff` (`staffID`),
  CONSTRAINT `fk_course_participant_course` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_course_participant_gn` FOREIGN KEY (`gn_id`) REFERENCES `guru_new` (`gn_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_course_participant_outsider` FOREIGN KEY (`outsider_id`) REFERENCES `outsider` (`outsider_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_course_participant_teacher` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_course_participant_staff` FOREIGN KEY (`staffID`) REFERENCES `staff_edu` (`staffID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback_form` (
  `formID` varchar(20) NOT NULL, `title` varchar(255) NOT NULL,
  `createdByStaff` varchar(20) NOT NULL, `createdDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sessionID` varchar(20) DEFAULT NULL, `courseID` varchar(20) DEFAULT NULL,
  `feedbackType` varchar(20) DEFAULT 'participant', `generatedPdf` varchar(255) DEFAULT NULL,
  `pdfGeneratedByStaff` varchar(20) DEFAULT NULL, `pdfGeneratedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`formID`), KEY `idx_feedback_form_staff` (`createdByStaff`),
  KEY `idx_feedback_form_session` (`sessionID`), KEY `idx_feedback_form_course` (`courseID`),
  KEY `idx_feedback_form_pdf_staff` (`pdfGeneratedByStaff`),
  CONSTRAINT `fk_feedback_form_staff` FOREIGN KEY (`createdByStaff`) REFERENCES `staff_edu` (`staffID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_form_session` FOREIGN KEY (`sessionID`) REFERENCES `course_session` (`sessionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_form_course` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_form_pdf_staff` FOREIGN KEY (`pdfGeneratedByStaff`) REFERENCES `staff_edu` (`staffID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback_category` (
  `categoryID` varchar(20) NOT NULL, `categoryName` varchar(100) NOT NULL,
  `categoryOrder` int DEFAULT 1, `formID` varchar(20) NOT NULL,
  PRIMARY KEY (`categoryID`), KEY `idx_feedback_category_form` (`formID`,`categoryOrder`),
  CONSTRAINT `fk_feedback_category_form` FOREIGN KEY (`formID`) REFERENCES `feedback_form` (`formID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback_question` (
  `questionID` varchar(20) NOT NULL, `questionText` text NOT NULL,
  `questionType` enum('rating','paragraph','image') DEFAULT 'rating',
  `questionImage` varchar(255) DEFAULT NULL, `isRequired` tinyint(1) DEFAULT 1,
  `questionOrder` int DEFAULT 1, `categoryID` varchar(20) NOT NULL,
  PRIMARY KEY (`questionID`), KEY `idx_feedback_question_category` (`categoryID`,`questionOrder`),
  CONSTRAINT `fk_feedback_question_category` FOREIGN KEY (`categoryID`) REFERENCES `feedback_category` (`categoryID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback_response` (
  `responseID` varchar(20) NOT NULL, `rating` int DEFAULT NULL, `comment` text DEFAULT NULL,
  `responseDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `participantID` varchar(20) DEFAULT NULL,
  `questionID` varchar(20) NOT NULL, `staffID` varchar(20) DEFAULT NULL,
  `responseType` varchar(20) DEFAULT 'participant',
  PRIMARY KEY (`responseID`), UNIQUE KEY `uq_feedback_response_participant_question` (`participantID`,`questionID`),
  KEY `idx_feedback_response_question` (`questionID`), KEY `idx_feedback_response_staff` (`staffID`),
  CONSTRAINT `fk_feedback_response_participant` FOREIGN KEY (`participantID`) REFERENCES `course_participant` (`participantID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_response_question` FOREIGN KEY (`questionID`) REFERENCES `feedback_question` (`questionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_response_staff` FOREIGN KEY (`staffID`) REFERENCES `staff_edu` (`staffID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trainer_document` (
  `document_id` int unsigned NOT NULL AUTO_INCREMENT, `trainerID` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL, `document_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL, `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL, `sentByStaffID` varchar(20) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0, `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`document_id`), KEY `idx_trainer_document_trainer` (`trainerID`),
  KEY `idx_trainer_document_sender` (`sentByStaffID`),
  CONSTRAINT `fk_trainer_document_trainer` FOREIGN KEY (`trainerID`) REFERENCES `trainer` (`trainerID`),
  CONSTRAINT `fk_trainer_document_sender` FOREIGN KEY (`sentByStaffID`) REFERENCES `staff_edu` (`staffID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- END LARAVEL-MIGRATION-BUSINESS-SCHEMA

SET FOREIGN_KEY_CHECKS = 1;

-- CANONICAL_SCHEMA_BLOCKED_BY_MISSING_BUSINESS_SEMANTICS:
-- fn_course_attendance_rate is intentionally not created. The PSM owner DDL
-- defines timestamp_status and hours_ladap as DECIMAL values but supplies no
-- authoritative interpretation from which an attendance-rate formula can be derived.

-- Legacy proposal/session history tables and their triggers are not application
-- dependencies and are deliberately outside this canonical deployment contract.
