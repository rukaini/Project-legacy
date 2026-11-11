# PSM Assessment selected owner contracts

> Historical legacy-database audit: the deferred states below describe only
> in-place conversion of populated `trainer_db`. They are superseded for fresh
> deployment by `deployment/CANONICAL_SCHEMA_INVENTORY.md` and
> `trainer-canonical-schema.sql`. The resolved `applicant`, split enrollment,
> `outsider`, split attendance, submission, and learner-log contracts are all
> present there; only the owner's decimal attendance interpretation is absent.

## Authority and scope

`psm_assessment.zip` is authoritative only for the selected enrollment, outsider, assessment-submission, attendance, access-log, and content-interaction contracts recorded here. Bundled duplicate master definitions do not override the already locked Course, Course Session, Teacher, New Teacher, or Trainer contracts. The ZIP was used as schema/business evidence only; none of its rows were imported.

`feedback_category` and `course_participant` remain explicitly held for a later decision.

## Selected owner status

| Logical dependency | Current Trainer representation | PSM owner representation | Current compatibility | Physical decision / blocker |
|---|---|---|---|---|
| `course_enrollment` | One polymorphic table, 276 rows | `enroll_teacher`, `enroll_guru_baru`, `enroll_outsider` | All current screens continue reading the populated generic table | `DEFERRED_BY_MISSING_ID_MAPPING`; generic table is a `LEGACY_COMPATIBILITY_TABLE` |
| `assessment_submission` | 138 rows; local FK to generic enrollment | Same table name and content fields | Trainer grading, file handling, analytics, and tracking remain on the current table | Safe partial migration is review-ready but not applied; identity/FK target reconciliation remains deferred |
| `outsider_participant` | 50-row local participant replica | `outsider` | Current Directory, participant, LMS, Simple, and analytics reads remain unchanged | `DEFERRED_BY_MISSING_EXTERNAL_DEPENDENCY`; `outsider_participant` is a `LEGACY_COMPATIBILITY_TABLE` |
| `training_access_log` | 602-row read-only learner log | Same table name | Current LMS reporting remains unchanged | Owner lookup index migration is review-ready but not applied; identity conversion is deferred |
| `training_attendance_record` | One generic 632-row RSVP/attendance table | `attendance`, `attendance_guru_baru`, `attendance_outsider` | Current reporting remains on the generic table | `DEFERRED_BY_PRODUCT_DATA_SAFETY`; generic table is a `LEGACY_COMPATIBILITY_TABLE` |
| `training_content_interaction_log` | 588-row read-only interaction log | Same table name | Learner Tracking remains read-only | Safe index/event-label migration is review-ready but not applied; identity conversion is deferred |

## Enrollment architecture

| Owner table | Owner participant identity | Owner uniqueness | Local mapping result | Status |
|---|---|---|---|---|
| `enroll_teacher` | `teacherID bigint unsigned` | (`teacherID`, `course_id`) | Current Teacher rows can be classified, but numeric IDs cannot be relabeled as canonical `teacher.teacherID varchar(20)` or `course.courseID varchar(20)` | `DEFERRED_BY_CROSS_OWNER_ID_CONFLICT` |
| `enroll_guru_baru` | `applicant_id bigint unsigned` | (`applicant_id`, `course_id`) | Current generic rows use `gn_id`; no authoritative Applicant-to-New-Teacher mapping exists | `DEFERRED_BY_MISSING_EXTERNAL_DEPENDENCY` |
| `enroll_outsider` | `outsider_id bigint unsigned` | (`outsider_id`, `course_id`) | Local outsider IDs belong to `outsider_participant`, not a proven owner `outsider` import; Course IDs also conflict | `DEFERRED_BY_MISSING_ID_MAPPING` |

The owner statuses are `pending`, `approved`, and `rejected`; only `enroll_guru_baru` additionally supports `auto_enrolled`. No Staff EDU enrollment table is invented. Current local rows are all `Approved`/`Normal`: 123 Teacher, 28 New Teacher, and 125 Outsider rows. Every local row has exactly one participant reference and no current local FK orphan.

## Applicant dependency

`applicant` does not exist locally and is not adopted as a Trainer-owned master. It is required by:

- `enroll_guru_baru.applicant_id`;
- `attendance_guru_baru.applicant_id`.

The owner ZIP contains an Applicant master, but supplies no authoritative `applicant_id` to `guru_new.gn_id` relationship. Names, email addresses, row order, and numeric similarity must not be used to infer one.

## Outsider contract

The owner replaces logical `outsider_participant` with `outsider`, whose fields are `outsider_id`, `name`, `ICNumber`, `phoneNumber`, `gender`, `organization`, `contact_no`, `user_id`, `created_at`, and `updated_at`. Its non-null `user_id` references owner `users.id`.

The local `users` table is Laravel infrastructure, contains zero rows, and is not proven to be the PSM authentication master. The 50 current outsiders therefore cannot receive valid owner `user_id` values without fabrication. Neither `outsider` nor its FK is created; current `outsider_participant` data remains intact.

## Assessment submission

The current table already supports file metadata, JSON answers, status, grade, feedback, reviewer, grading time, and timestamps. All 138 rows resolve to both a current Assessment and generic Enrollment; 106 populated numeric reviewer IDs resolve to the current numeric Trainer replica.

Safe review-only migration `2026_09_06_000002_align_assessment_submission_safe_owner_contract.php`:

1. widens nullable `grade` from `decimal(6,2)` to owner `decimal(8,2)`;
2. drops only `fk_assessment_submission_enrollment`, because the owner deliberately declares no FK for `enrollment_id`;
3. restores that legacy FK on rollback only after proving the legacy table exists and no orphan would result;
4. refuses rollback grade narrowing if any value would be truncated.

Deferred differences include signedness/identity conversion, timestamp-to-datetime semantics, status-default semantics, assessment cascade behavior, and numeric `graded_by_trainer_id` versus canonical `trainer.trainerID varchar(20)`. The owner `enrollment_id` is compatible in shape with `enroll_guru_baru.enrollment_id`, but the ZIP does not declare that FK and the current 138 generic IDs must not be reinterpreted.

## Attendance architecture

| Owner table | Identity and uniqueness | Current generic rows | Blocker |
|---|---|---:|---|
| `attendance` | (`teacher_id`, `session_id`) unique | 356 Teacher attendance rows | Teacher and Session owner IDs conflict with locked string IDs |
| `attendance_guru_baru` | (`applicant_id`, `session_id`) unique | 116 New Teacher attendance rows | Applicant is absent; Applicant-to-`gn_id` mapping is unknown; Session ID conflicts |
| `attendance_outsider` | (`outsider_id`, `session_id`) unique | 160 Outsider attendance rows | Owner Outsider identity is not established; Session ID conflicts |

The current generic table stores RSVP state, textual `Present`/`Late`/`Absent`/`Not Recorded` status, self-recorded time, and remarks. The owner tables instead store decimal `timestamp_status` and, except for Outsider, `hours_ladap`. No authoritative conversion formula was supplied. Splitting rows based only on `participant_type` would therefore lose business meaning even before resolving IDs.

No current Staff EDU rows or Staff attendance path were found. The PSM package nevertheless supplies no future Staff attendance contract; if Course staff assignments require attendance, that remains `MISSING_OWNER_CONTRACT_FOR_STAFF_ATTENDANCE`.

## Training access log

The current 602 rows all resolve to the present numeric Course and generic Enrollment records. The table is read-only in Trainer LMS reporting. The owner declares no FKs.

Safe review-only migration `2026_09_06_000003_add_training_access_log_owner_lookup_index.php` adds and reversibly removes the owner index `training_access_log_course_id_enrollment_id_index (course_id, enrollment_id)`. Numeric width/signedness, timestamp semantics, canonical Course identity, and Enrollment interpretation remain deferred.

## Training content interaction log

The current 588 rows remain readable, with populated open/access timestamps and non-negative counters. Existing integrity issues are retained, not repaired: 76 rows reference absent local `training_module` rows and 149 reference absent local `training_content_block` rows.

Safe review-only migration `2026_09_06_000004_align_training_content_interaction_log_safe_owner_indexes.php`:

1. widens nullable `last_event_type` from `varchar(30)` to owner `varchar(50)`;
2. replaces the equivalent legacy unique key (`block_id`, `enrollment_id`) with the owner-named/order key (`enrollment_id`, `block_id`) after a duplicate preflight;
3. renames the existing Block and Enrollment indexes to owner names;
4. reverses the index changes and refuses to narrow the event label if that would truncate data.

Numeric type changes remain deferred. No owner FK is invented. Existing additional Course, Module, and last-access indexes remain as safe Trainer reporting indexes.

## Cross-owner type conflict matrix

| Dependent reference | PSM owner type | Locked/local referenced contract | Canonical type | Decision |
|---|---|---|---|---|
| `enroll_teacher.teacherID` | `bigint unsigned` | `teacher.teacherID` | `varchar(20)` | Defer; no Teacher mapping |
| `enroll_teacher.course_id` | `bigint unsigned` | `course.courseID` | `varchar(20)` | Defer; no Course mapping |
| `enroll_guru_baru.applicant_id` | `bigint unsigned` | external `applicant.applicant_id` | `bigint unsigned`, locally absent | Defer missing dependency/mapping |
| `enroll_guru_baru.course_id` | `bigint unsigned` | `course.courseID` | `varchar(20)` | Defer |
| `enroll_outsider.course_id` | `bigint unsigned` | `course.courseID` | `varchar(20)` | Defer |
| `attendance.teacher_id` | `bigint unsigned` | `teacher.teacherID` | `varchar(20)` | Defer |
| `attendance.session_id` | `bigint unsigned` | `course_session.sessionID` | `varchar(20)` | Defer |
| `attendance_guru_baru.applicant_id` | `bigint unsigned` | external `applicant.applicant_id` | `bigint unsigned`, locally absent | Defer missing dependency |
| `attendance_guru_baru.session_id` | `bigint unsigned` | `course_session.sessionID` | `varchar(20)` | Defer |
| `attendance_outsider.session_id` | `bigint unsigned` | `course_session.sessionID` | `varchar(20)` | Defer |
| `assessment_submission.graded_by_trainer_id` | `bigint unsigned` | `trainer.trainerID` | `varchar(20)` | Keep current numeric field; defer mapping |
| `training_access_log.course_id` | `bigint unsigned` | `course.courseID` | `varchar(20)` | Keep current numeric values; defer mapping |
| `training_content_interaction_log.course_id` | `bigint unsigned` | `course.courseID` | `varchar(20)` | Keep current numeric values; defer mapping |
| `training_content_interaction_log.module_id` | `bigint unsigned` | Trainer-owned `training_module.module_id` | current `int` | Defer type change; 76 existing orphans |
| `training_content_interaction_log.block_id` | `bigint unsigned` | Trainer-owned `training_content_block.block_id` | current signed `bigint` | Defer type change; 149 existing orphans |

`outsider.user_id bigint unsigned` is structurally compatible with local Laravel `users.id`, but the identity meaning is unproven and the local table is empty. It is therefore a semantic dependency conflict, not a safe FK.

## Database objects retained for compatibility

The legacy attendance-rate function depends on `training_attendance_record`.
It remains physically unchanged while its populated source remains a legacy
compatibility table. No database object was rewritten for this owner batch.

## Unresolved contracts

- `feedback_category` — held for later discussion.
- `course_participant` — held for later discussion.
- external `applicant` ownership and an authoritative Applicant-to-New-Teacher relationship;
- Staff EDU attendance, if required by the cross-system product;
- canonical mappings for legacy numeric Course, Session, Teacher, Trainer, and New Teacher references;
- conversion semantics from generic RSVP/attendance status to owner decimal attendance fields;
- definitive enrollment meaning of `assessment_submission.enrollment_id`, access logs, and interaction logs.
