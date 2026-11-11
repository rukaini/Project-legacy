# Trainer canonical schema inventory

Shared authority: owner-approved V4 schema supplied for deployment.

Tracked Aidid reference: `backend-integration/deployment/trainer-canonical-schema.sql`

All business identifiers inherited from `course`, `course_session`, `trainer`, `teacher`, `guru_new`, and `staff_edu` are `VARCHAR(20)`. Dependent owner columns were adapted to that identity type where an older dump used a numeric type. All listed objects are `CANONICAL_COMPLETE` at the DDL level.

The final `guru_new` owner contract keeps `created_at` and `updated_at` and has
no `service_date` or `remember_token` columns.

## A. Shared and external business tables

| Table | Owner | Canonical PK / identity | Canonical FKs | Indexes / uniqueness | Dependencies | Status |
|---|---|---|---|---|---|---|
| `hr_administrator` | edudb | `hrID VARCHAR(20)` | — | unique `email` | — | CANONICAL_COMPLETE |
| `school` | edudb | `schoolID VARCHAR(50)` | — | unique `school_name` | — | CANONICAL_COMPLETE |
| `guru_new` | edudb | `gn_id VARCHAR(20)` | `hrID`, `schoolID` | unique `email`; FK indexes | hr_administrator, school | CANONICAL_COMPLETE |
| `teacher` | edu | `teacherID VARCHAR(20)` | `schoolID` | unique `email`; FK index | school | CANONICAL_COMPLETE |
| `staff_edu` | fyp2 | `staffID VARCHAR(20)` | — | PK | — | CANONICAL_COMPLETE |
| `trainer` | fyp2 | `trainerID VARCHAR(20)` | — | unique `trainerEmail` | — | CANONICAL_COMPLETE |
| `course` | fyp2 | `courseID VARCHAR(20)` | staff assignment FK | PK; attendee-assignment index | staff_edu | CANONICAL_COMPLETE |
| `applicant` | PSM Assessment | `applicant_id BIGINT UNSIGNED` | — | unique `email` | — | CANONICAL_COMPLETE |
| `outsider` | PSM Assessment | `outsider_id BIGINT UNSIGNED` | `user_id` | unique `ICNumber`; user index | users | CANONICAL_COMPLETE |
| `course_session` | fyp2 | `sessionID VARCHAR(20)` | `courseID` | FK index | course | CANONICAL_COMPLETE |
| `session_trainer` | fyp2 | (`trainerID`, `sessionID`) | both PK columns | composite PK; session index | trainer, course_session | CANONICAL_COMPLETE |
| `qr_session` | fyp2 | `qrID VARCHAR(20)` | `sessionID` | unique `sessionID` | course_session | CANONICAL_COMPLETE |
| `enroll_teacher` | PSM Assessment | (`teacherID`, `course_id`) | both PK columns | composite PK; course index | teacher, course | CANONICAL_COMPLETE |
| `enroll_guru_baru` | PSM Assessment | `enrollment_id BIGINT UNSIGNED` | `applicant_id`, `course_id` | unique (`applicant_id`, `course_id`); course index | applicant, course | CANONICAL_COMPLETE |
| `enroll_outsider` | PSM Assessment | (`outsider_id`, `course_id`) | both PK columns | composite PK; course index | outsider, course | CANONICAL_COMPLETE |
| `attendance` | PSM Assessment | `attendance_id BIGINT UNSIGNED` | `teacher_id`, `session_id` | unique (`teacher_id`, `session_id`); session index | teacher, course_session | CANONICAL_COMPLETE |
| `attendance_guru_baru` | PSM Assessment | `attendGuruBaru_id BIGINT UNSIGNED` | `applicant_id`, `session_id` | unique (`applicant_id`, `session_id`); session index | applicant, course_session | CANONICAL_COMPLETE |
| `attendance_outsider` | PSM Assessment | `attendOutsider_id BIGINT UNSIGNED` | `outsider_id`, `session_id` | unique (`outsider_id`, `session_id`); session index | outsider, course_session | CANONICAL_COMPLETE |
| `assessment_submission` | PSM Assessment | `submission_id BIGINT UNSIGNED` | `assessment_id`, `enrollment_id`, `graded_by_trainer_id` | indexes on assessment/enrollment/grader | assessment, enroll_guru_baru, trainer | CANONICAL_COMPLETE |
| `training_access_log` | PSM Assessment | `access_id BIGINT UNSIGNED` | owner leaves references unconstrained | index (`course_id`, `enrollment_id`) | logical course/enrollment refs | CANONICAL_COMPLETE |
| `training_content_interaction_log` | PSM Assessment | `interaction_id BIGINT UNSIGNED` | owner leaves references unconstrained | unique (`enrollment_id`, `block_id`); block/enrollment indexes | logical course/module/block/enrollment refs | CANONICAL_COMPLETE |
| `course_participant` | fyp2 | `participantID VARCHAR(20)` | `courseID`, `gn_id`, `outsider_id`, `teacherID`, `staffID` | one index per FK | course and participant masters | CANONICAL_COMPLETE |
| `feedback_form` | fyp2 | `formID VARCHAR(20)` | staff creator, session, course, PDF staff | one index per FK | staff_edu, course_session, course | CANONICAL_COMPLETE |
| `feedback_category` | fyp2 | `categoryID VARCHAR(20)` | `formID` | (`formID`, `categoryOrder`) | feedback_form | CANONICAL_COMPLETE |
| `feedback_question` | fyp2 | `questionID VARCHAR(20)` | `categoryID` | (`categoryID`, `questionOrder`) | feedback_category | CANONICAL_COMPLETE |
| `feedback_response` | fyp2 | `responseID VARCHAR(20)` | participant, question, staff | unique (`participantID`, `questionID`); FK indexes | course_participant, feedback_question, staff_edu | CANONICAL_COMPLETE |
| `trainer_document` | shared agreement | `document_id INT UNSIGNED` | `trainerID`, `sentByStaffID` | trainer/sender indexes | trainer, staff_edu | CANONICAL_COMPLETE |

Attendance DDL is complete. The meaning of `timestamp_status` and `hours_ladap` remains `CANONICAL_SCHEMA_BLOCKED_BY_MISSING_BUSINESS_SEMANTICS`; therefore no present/late/absent conversion or attendance-rate routine is defined.

## B. Trainer-owned tables

| Table | PK | Canonical external references | Keys | Status |
|---|---|---|---|---|
| `training_module` | `module_id INT` | `course_id VARCHAR(20)` → `course.courseID` | unique course/module type | CANONICAL_COMPLETE |
| `learning_material` | `material_id BIGINT UNSIGNED` | `course_id VARCHAR(20)` → course; `trainer_id VARCHAR(20)` → trainer; `session_id VARCHAR(20)` → course session | course/trainer/session indexes | CANONICAL_COMPLETE |
| `participant_material_access` | `course_id VARCHAR(20)` | physical FK `course_id VARCHAR(20)` → `course.courseID`; update/delete cascade | primary key guarantees one row per course | CANONICAL_COMPLETE |
| `assessment` | `assessment_id BIGINT UNSIGNED` | `course_id VARCHAR(20)` → course | course/module indexes | CANONICAL_COMPLETE |
| `assessment_question` | `question_id BIGINT UNSIGNED` | indirect through assessment | assessment/order index | CANONICAL_COMPLETE |
| `training_content_block` | `block_id BIGINT` | indirect course through `module_id`; optional assessment/material IDs | module/order and object indexes | CANONICAL_COMPLETE |
| `course_proposal` | `proposal_id INT UNSIGNED` | `trainer_id VARCHAR(20)` → trainer; `reviewed_by VARCHAR(20)` → staff | trainer/reviewer indexes | CANONICAL_COMPLETE |
| `teaching_plans` | `id BIGINT UNSIGNED` | `course_id VARCHAR(20)` → course; `trainer_id VARCHAR(20)` → trainer | unique course/trainer | CANONICAL_COMPLETE |
| `teaching_plan_comments` | `id BIGINT UNSIGNED` | `reviewer_staff_id VARCHAR(20)` → staff | plan/staff indexes | CANONICAL_COMPLETE |
| `trainer_activity_log` | `log_id BIGINT UNSIGNED` | `trainer_id VARCHAR(20)` → trainer; `course_id VARCHAR(20)` → course | trainer/action/created/course indexes | CANONICAL_COMPLETE |
| `trainer_certificate` | `cert_id INT` | `trainer_id VARCHAR(20)` → trainer | trainer index | CANONICAL_COMPLETE |

## C. Laravel framework tables

`migrations`, `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs` are included with their Laravel primary, unique, and queue/session lookup indexes.

## Database objects

No Trainer-owned view, function, procedure, or trigger is required by current
runtime. Legacy history objects were not copied because their history tables
are not canonical application dependencies.
