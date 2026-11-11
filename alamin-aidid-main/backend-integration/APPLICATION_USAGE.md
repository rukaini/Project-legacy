# Application database usage

Trainer runtime uses the authoritative shared contracts directly. Query
subselects provide stable presentation fields for existing Blade templates;
they do not create compatibility tables or duplicate external ownership.

## External owner tables read by Trainer

- fyp2: `course`, `course_session`, `session_trainer`, `trainer`, `staff_edu`,
  `qr_session`, `course_participant`, and the feedback tables.
- edudb: `guru_new`.
- edu: `teacher`.
- PSM Assessment: `applicant`, `outsider`, the three `enroll_*` tables, the
  three attendance tables, `assessment_submission`, `training_access_log`,
  and `training_content_interaction_log`.
- Shared agreement: `trainer_document`, using `trainerID VARCHAR(20)` and
  `sentByStaffID VARCHAR(20)`.

`CanonicalEnrollment` and `CanonicalAttendance` union the owner's split tables
at query time. `ExternalParticipantIdentity` exposes only the locked `teacher`
and `guru_new` owner columns. Applicant identity remains independent from
`guru_new`; no applicant-to-`gn_id` mapping is inferred.

## Trainer-owned writes

Trainer writes `training_module`, `learning_material`, `participant_material_access`, `assessment`,
`assessment_question`, `training_content_block`, `course_proposal`,
`teaching_plans`, `teaching_plan_comments`, `trainer_activity_log`, and
`trainer_certificate`. Their references to shared masters use the canonical
course, session, trainer, and staff identities. Trainer does not create, alter,
or seed external owner tables in production.

For participant material access,
`participant_material_access.course_id = course.courseID`; the database
enforces this physical foreign key with update and delete cascades, while the
Staff EDU save workflow also checks that the course exists before writing.

## Database access

Trainer runtime uses Laravel query-builder and application logic over the base
tables. It owns no database views, functions, procedures, or triggers.

## Attendance semantics

The PSM attendance DDL is known and implemented. Its owner source does not
define how decimal `timestamp_status` and `hours_ladap` values map to
Present/Late/Absent or an attendance percentage. Runtime therefore reports
whether an owner attendance row exists and exposes no invented rate.

## Seeder policy

`DatabaseSeeder` is intentionally empty. No sample identities, passwords,
owner rows, or browser-QA fixtures are imported by the deployment seed path.
Any synthetic fixtures and disposable runtimes used for verification remain
ignored local data and are not production deployment inputs.
