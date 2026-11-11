# Locked fyp2.0 owner contracts: selected batch

> Historical legacy-database audit: the deferred states below describe only
> in-place conversion of populated `trainer_db`. They are superseded for fresh
> deployment by `deployment/CANONICAL_SCHEMA_INVENTORY.md` and
> `trainer-canonical-schema.sql`, where these owner contracts and Trainer
> runtime integration are complete.

Reference owner export: `fyp2_0 (1).sql`, database `fyp2.0`, MariaDB 10.4.32. The local runtime is MySQL 8.0.45. No owner rows or password values were imported. Duplicate `guru_new` and `teacher` definitions in that export are explicitly non-authoritative; their previously locked owners remain `edudb` and `edu`.

## Independent physical status

| Selected contract | Live local PK | Owner PK | Status | Evidence / blocker |
|---|---|---|---|---|
| `course` | `course_id bigint unsigned AUTO_INCREMENT` | `courseID varchar(20)` | `DEFERRED_BY_MISSING_ID_MAPPING` | 10 legacy rows; populated references exist in selected, Trainer-owned, and out-of-scope tables. No mapping to owner `C...` IDs exists. |
| `course_session` | `session_id bigint unsigned AUTO_INCREMENT` | `sessionID varchar(20)` | `DEFERRED_BY_MISSING_ID_MAPPING` | 38 legacy rows; 632 attendance, 38 assignment, and 34 history references. No mapping to owner `CS...` IDs exists. |
| `feedback_form` | `form_id int AUTO_INCREMENT` | `formID varchar(20)` | `DEFERRED_BY_MISSING_ID_MAPPING` | 8 legacy rows. Current data model also has `trainer_id` and `status`, which have no proven owner equivalents. |
| `feedback_question` | `question_id int AUTO_INCREMENT` | `questionID varchar(20)` | `DEFERRED_BY_OUT_OF_SCOPE_DEPENDENCY` | 48 legacy rows use `form_id` plus a free-text category; owner requires `categoryID` referencing out-of-scope `feedback_category`. |
| `feedback_response` | `response_id int AUTO_INCREMENT` | `responseID varchar(20)` | `DEFERRED_BY_MISSING_ID_MAPPING` | 1,044 legacy rows. Owner participant IDs depend on out-of-scope `course_participant`; 515 ratings are fractional while the owner type is integer. |
| `qr_session` | `qrID varchar(20)` | `qrID varchar(20)` | `DEFERRED_BY_OUT_OF_SCOPE_DEPENDENCY` | Columns already match. The safe owner unique key on `sessionID` was migrated; the FK is deferred until canonical `course_session.sessionID` exists. |
| `session_trainer` | composite numeric `session_id`, `trainer_id` | composite string `trainerID`, `sessionID` | `DEFERRED_BY_MISSING_ID_MAPPING` | 38 assignments depend on both unmapped master IDs. |
| `staff_edu` | local semantic predecessor is `staff.staff_id bigint unsigned AUTO_INCREMENT` | `staffID varchar(20)` | `DEFERRED_BY_AUTHENTICATION_DEPENDENCY` | `staff_edu` is absent locally. Two legacy Staff rows drive Admin sign-in/profile and are referenced by proposals/comments. No `EDU...` mapping exists. |
| `trainer` | `trainer_id bigint unsigned AUTO_INCREMENT` | `trainerID varchar(20)` | `DEFERRED_BY_AUTHENTICATION_DEPENDENCY` | Four legacy rows drive sign-in/profile and have hundreds of populated references. No `TR...` mapping exists. |

`qr_session` is therefore partially aligned but not declared fully compatible until its canonical FK can be created. All other physical tables remain unchanged.

## Semantic field mapping

Status is the physical result in this batch.

### Course

| Local | Owner | Classification | Result |
|---|---|---|---|
| `course_id` | `courseID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID mapping |
| `course_name` | `courseName` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `course_category` | `courseCategory` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `description` | `description` | owner field | unchanged pending table migration |
| `capacity` | `capacity` | owner field | unchanged; owner default/null contract pending |
| `price` | `price` | owner field | unchanged; owner nullability differs |
| `status` | `status` | owner field | deferred; local values are `Active`, owner default is `upcoming` |
| `poster` | `poster` | owner field | unchanged pending table migration |
| `training_mode` | `courseType` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; local `lms` maps to `LMS`, and current non-LMS Simple workflow uses `standard`/`simple` |
| `target_participant` | `targetAudience` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; proven mappings include Teacher→`teacher`, Guru Baru→`new_teacher`, Outsider→`public`; one local QA value is not an owner identity mapping |

Owner-only course fields have no local source and must come from the owner: `otherTargetAudience`, `staffAttendeeIDs`, `staffAttendeeAssignedBy`, `staffAttendeeAssignedDate`, `courseRating`, `mode`, `onlineLink`, `whatsappGroup`, `closeDate`, `organiserName`.

### Course session

| Local | Owner | Classification | Result |
|---|---|---|---|
| `session_id` | `sessionID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID mapping |
| `date` | `sessionDate` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `session_title` | `sessionName` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `start_time` | `startTime` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `end_time` | `endTime` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `course_id` | `courseID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred with Course ID |
| `location` | `location` | owner field | unchanged pending table migration |
| `time` | `startTime` | `LEGACY_OBSOLETE_FIELD` | all 38 current values equal `start_time`; retain until safe migration |
| `session_order` | none | `UNKNOWN_LOCAL_FIELD` | preserved for human review |
| `session_type` | none | `UNKNOWN_LOCAL_FIELD` | preserved; current UI uses Physical/Online/Hybrid |

### Feedback form

| Local | Owner | Classification | Result |
|---|---|---|---|
| `form_id` | `formID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID mapping |
| `title` | `title` | owner field | unchanged |
| `created_by_staff` | `createdByStaff` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred with Staff ID |
| `created_date` | `createdDate` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `course_id` | `courseID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred with Course ID |
| `trainer_id` | none | `UNKNOWN_LOCAL_FIELD` | preserved; current feedback view/rating model depends on it |
| `status` | none | `UNKNOWN_LOCAL_FIELD` | preserved |

Owner-only fields without a safe local source: `sessionID`, `feedbackType`, `generatedPdf`, `pdfGeneratedByStaff`, `pdfGeneratedAt`.

### Feedback question

| Local | Owner | Classification | Result |
|---|---|---|---|
| `question_id` | `questionID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID mapping |
| `question_text` | `questionText` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `question_type` | `questionType` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; owner enum is rating/paragraph/image |
| `is_required` | `isRequired` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `question_order` | `questionOrder` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `form_id` | none directly | `UNKNOWN_LOCAL_FIELD` | preserved; owner relationship runs through out-of-scope category data |
| `category` | `categoryID` | `UNKNOWN_LOCAL_FIELD` | a free-text label is not proven equivalent to an owner business ID |
| `options_json` | none | `UNKNOWN_LOCAL_FIELD` | preserved |

Owner-only `questionImage` and `categoryID` cannot be populated safely. The `feedback_category` FK is deferred.

### Feedback response

| Local | Owner | Classification | Result |
|---|---|---|---|
| `response_id` | `responseID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID mapping |
| `rating decimal(3,1)` | `rating int` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; 515 populated ratings are fractional and cannot be rounded without changing meaning |
| `comment` | `comment` | owner field | unchanged |
| `response_date` | `responseDate` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `participant_id` | `participantID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: out-of-scope Course Participant mapping |
| `question_id` | `questionID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred with Question ID |
| `trainer_id` | none | `UNKNOWN_LOCAL_FIELD` | preserved; current rating function/trigger depend on it |
| `anonymous_code` | none | `UNKNOWN_LOCAL_FIELD` | preserved |

Owner-only `staffID` and `responseType` lack safe local source values. The `course_participant` FK is deferred.

### QR session

All five column names and types already match: `qrID`, `qrCode`, `attendanceLink`, `expiryTime`, `sessionID`. The legacy nonunique `sessionID` index was safely replaced by owner unique key `uq_qr_session_session`; 38 rows were preserved. The owner FK remains deferred. No local-only columns exist.

### Session assignment

| Local | Owner | Classification | Result |
|---|---|---|---|
| `session_id` | `sessionID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: Session ID mapping |
| `trainer_id` | `trainerID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: Trainer ID mapping |

The owner composite PK order is (`trainerID`, `sessionID`); the current local order is (`session_id`, `trainer_id`).

### Staff EDU

| Local `staff` | Owner `staff_edu` | Classification | Result |
|---|---|---|---|
| `staff_id` | `staffID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID/auth mapping |
| `staff_name` | `staffName` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `staff_email` | `email` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `role` | `role` | owner field | deferred; value/default contract differs |
| `password` | `password` | owner sensitive field | deferred; never copied or exposed |
| `hire_date` | `appointedDate` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; current profile uses it as employment start |
| `retirement_date` | `pensionDate` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `total_credit_hours` | `credit_hour` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; owner type is decimal(5,2) |
| `date_of_birth` | none | `UNKNOWN_LOCAL_FIELD` | preserved |
| `staff_pic` | none | `UNKNOWN_LOCAL_FIELD` | preserved; concrete Admin profile/header feature exists, but cross-system field ownership is not approved |

Owner-only profile fields lack a safe local source. `users` remains separate Laravel infrastructure. Admin login/password/picture behavior remains on the legacy local table until an authentication and identity migration is agreed.

### Trainer

| Local | Owner | Classification | Result |
|---|---|---|---|
| `trainer_id` | `trainerID` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred: ID/auth mapping |
| `trainer_name` | `trainerName` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `trainer_IC` | `trainerIC` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `trainer_email` | `trainerEmail` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `trainer_phoneNo` | `trainerPhoneNo` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `expertise varchar(255)` | `expertise text` | owner field | deferred |
| `trainer_pic` | `trainerPic` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred |
| `password` | `trainerPassword` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred; authentication decision required |
| `feedback_rating` | `averageRating` | `SEMANTIC_EQUIVALENT_TO_OWNER_FIELD` | deferred with Feedback objects |
| `status` | `status` | owner field | deferred; casing/default differ |
| `invitation_letter` | none | `UNKNOWN_LOCAL_FIELD` | preserved |

Owner-only `trainer_changed_password` and `paymentStatus` lack safe local source values. Existing sign-in and approved profile writes remain unchanged pending the owner/API authentication decision.

## Identifier dependency graph

### Trainer-owned references that cannot yet migrate

| Trainer-owned table.column | Current type | Populated | Canonical target |
|---|---:|---:|---|
| `assessment.course_id` | bigint unsigned | 14 | `course.courseID varchar(20)` |
| `learning_material.course_id` | bigint unsigned | 23 | `course.courseID varchar(20)` |
| `learning_material.trainer_id` | bigint unsigned | 23 | `trainer.trainerID varchar(20)` |
| `learning_material.session_id` | bigint unsigned | 0 | `course_session.sessionID varchar(20)` |
| `training_module.course_id` | bigint unsigned | 25 | `course.courseID varchar(20)` |
| `teaching_plans.course_id` | bigint unsigned | 3 | `course.courseID varchar(20)` |
| `teaching_plans.trainer_id` | bigint unsigned | 3 | `trainer.trainerID varchar(20)` |
| `teaching_plan_comments.reviewer_staff_id` | bigint unsigned | 3 | `staff_edu.staffID varchar(20)` |
| `course_proposal.trainer_id` | int unsigned | 10 | `trainer.trainerID varchar(20)` |
| `course_proposal.reviewed_by` | int unsigned | 9 | `staff_edu.staffID varchar(20)` |
| `trainer_activity_log.trainer_id` | bigint | 619 | `trainer.trainerID varchar(20)` |
| `trainer_activity_log.course_id` | bigint | 601 | `course.courseID varchar(20)` |
| `trainer_certificate.trainer_id` | int | 10 | `trainer.trainerID varchar(20)` |

No numeric value is converted to a fabricated owner code. `trainer_activity_log.target_id` remains polymorphic and requires a separate target-type contract.

### Out-of-scope blockers

| Out-of-scope table.column | Current type | Populated | Required target |
|---|---:|---:|---|
| `course_enrollment.course_id` | bigint unsigned | 276 | `courseID varchar(20)` |
| `training_access_log.course_id` | int | 602 | `courseID varchar(20)` |
| `training_attendance_record.course_id` | int | 632 | `courseID varchar(20)` |
| `training_attendance_record.session_id` | int | 632 | `sessionID varchar(20)` |
| `training_content_interaction_log.course_id` | int | 588 | `courseID varchar(20)` |
| `assessment_submission.graded_by_trainer_id` | bigint | 106 | `trainerID varchar(20)` |
| `trainer_document.trainer_id` | int unsigned | 4 | `trainerID varchar(20)` |
| `session_history.course_id` | bigint unsigned | 34 | `courseID varchar(20)` |
| `session_history.session_id` | bigint unsigned | 34 | `sessionID varchar(20)` |

`feedback_category` and `course_participant` do not exist locally and are out of scope; their owner FKs are not created.

## Database objects

Trainer runtime owns no database views, functions, procedures, or triggers.
External legacy database objects remain outside this subsystem's ownership and
were not copied from fyp2.0.

No fyp2.0 audit infrastructure, routines, triggers, or data were copied.

## Application compatibility delivered now

- `AttendanceQrService` accepts canonical `sessionID`, `sessionDate`, `startTime`, and `endTime` directly and retains the isolated legacy numeric-string fallback without generating a `CS...` ID.
- The authorized QR presentation route accepts string Course/Session IDs and resolves canonical or legacy Course, Session, and Session-Trainer columns through `ExternalTrainingSchema`.
- Existing product paths remain on the live legacy schema until their complete dependency graph can migrate atomically. This is temporary compatibility, not the intended final architecture.
- Existing `ExternalParticipantIdentity` behavior for `guru_new` and `teacher` is preserved.

## Verification at this batch boundary

- Local database: MySQL 8.0.45, `trainer_db`.
- Incremental migration `2026_09_06_000001_align_qr_session_owner_unique_key` ran in batch 9.
- Post-migration `SHOW CREATE TABLE qr_session` confirms the five owner columns, primary key `qrID`, unique key `uq_qr_session_session`, InnoDB, and `utf8mb4_unicode_ci`; 38 rows remain.
- The other eight selected physical contracts remain unchanged in the explicit deferred states above; `staff_edu` remains absent and the local `staff` predecessor remains intact.
- Focused product-path verification: 159 tests passed with 1,856 assertions.
- Complete PHP verification: 272 tests passed with 3,768 assertions.
- Laravel Pint and `git diff --check` passed.
