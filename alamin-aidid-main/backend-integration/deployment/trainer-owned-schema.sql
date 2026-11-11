-- Aidid Trainer Management System owned business tables only.
-- Structure-only deployment artifact; contains no runtime data.
-- Definitions follow main_database_CANONICAL_PRE_INSERT_V4.sql as supplied on 2026-09-08,
-- plus later Aidid-owned feature migrations documented in this repository.
-- External/shared tables and all views, functions, procedures, and triggers are excluded.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `training_module` (
  `module_id` int NOT NULL AUTO_INCREMENT,
  `course_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_title` varchar(255) NOT NULL,
  `module_type` varchar(100) NOT NULL,
  `module_order` int DEFAULT '1',
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`module_id`),
  UNIQUE KEY `uq_training_module_course_type` (`course_id`,`module_type`),
  CONSTRAINT `fk_training_module_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON UPDATE NO ACTION ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- participant_material_access.course_id = course.courseID.
CREATE TABLE IF NOT EXISTS `participant_material_access` (
  `course_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `access_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  CONSTRAINT `participant_material_access_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `learning_material` (
  `material_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trainer_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata_cleared` tinyint(1) NOT NULL DEFAULT '0',
  `extracted_metadata` text COLLATE utf8mb4_unicode_ci,
  `ai_generated_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_synopsis` text COLLATE utf8mb4_unicode_ci,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`material_id`),
  KEY `learning_material_course_id_foreign` (`course_id`),
  KEY `learning_material_trainer_id_foreign` (`trainer_id`),
  CONSTRAINT `learning_material_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `learning_material_trainer_id_foreign` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainerID`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assessment` (
  `assessment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `module_id` int DEFAULT NULL,
  `instruction_text` text COLLATE utf8mb4_unicode_ci,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `accepted_format` varchar(100) DEFAULT NULL,
  `time_limit_minutes` int DEFAULT NULL,
  `max_attempts` int DEFAULT NULL COMMENT 'Quiz max attempts. NULL = unlimited.',
  `total_marks` decimal(6,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `weight_percent` decimal(5,2) DEFAULT NULL COMMENT 'Quiz weight in overall course grade (e.g. 10.00 = 10% of course)',
  PRIMARY KEY (`assessment_id`),
  KEY `assessment_course_id_foreign` (`course_id`),
  KEY `fk_assessment_module` (`module_id`),
  CONSTRAINT `assessment_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_module` FOREIGN KEY (`module_id`) REFERENCES `training_module` (`module_id`) ON UPDATE NO ACTION ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assessment_question` (
  `question_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint unsigned NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `question_order` int DEFAULT '1',
  `question_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options_json` json DEFAULT NULL,
  `correct_answer` text COLLATE utf8mb4_unicode_ci,
  `marks` decimal(5,2) DEFAULT '1.00',
  `grading_rubric` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `assessment_question_assessment_id_foreign` (`assessment_id`),
  CONSTRAINT `assessment_question_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_content_block` (
  `block_id` bigint NOT NULL AUTO_INCREMENT,
  `module_id` int NOT NULL,
  `block_type` varchar(50) NOT NULL,
  `block_title` varchar(255) DEFAULT NULL,
  `block_content` text DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `material_id` bigint unsigned DEFAULT NULL,
  `assessment_id` bigint unsigned DEFAULT NULL,
  `block_order` int DEFAULT '1',
  `is_visible` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_id`),
  KEY `fk_content_block_module` (`module_id`),
  KEY `fk_content_block_material` (`material_id`),
  KEY `fk_content_block_assessment` (`assessment_id`),
  CONSTRAINT `fk_content_block_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT `fk_content_block_material` FOREIGN KEY (`material_id`) REFERENCES `learning_material` (`material_id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT `fk_content_block_module` FOREIGN KEY (`module_id`) REFERENCES `training_module` (`module_id`) ON UPDATE NO ACTION ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_proposal` (
  `proposal_id` int unsigned NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proposed_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_audience` text COLLATE utf8mb4_unicode_ci,
  `objective` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `training_mode` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_mode` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_capacity` int DEFAULT NULL,
  `proposed_start_date` date DEFAULT NULL,
  `proposed_end_date` date DEFAULT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `admin_comment` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`proposal_id`),
  KEY `idx_proposal_trainer` (`trainer_id`),
  KEY `idx_proposal_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `teaching_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trainer_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned NOT NULL,
  `submitted_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teaching_plans_course_id_trainer_id_unique` (`course_id`,`trainer_id`),
  KEY `teaching_plans_course_id_index` (`course_id`),
  KEY `teaching_plans_trainer_id_index` (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `teaching_plan_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teaching_plan_id` bigint unsigned NOT NULL,
  `reviewer_staff_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `seen_by_trainer_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teaching_plan_comments_teaching_plan_id_index` (`teaching_plan_id`),
  KEY `teaching_plan_comments_reviewer_staff_id_index` (`reviewer_staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trainer_activity_log` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_trainer` (`trainer_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trainer_certificate` (
  `cert_id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cert_id`),
  KEY `idx_tc_trainer` (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
