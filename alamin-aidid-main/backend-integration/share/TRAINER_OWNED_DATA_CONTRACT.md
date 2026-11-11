# Trainer-owned canonical data contract

The deployment source of truth is
`backend-integration/deployment/trainer-canonical-schema.sql`. The historical
`trainer_db` extraction is audit evidence only and does not define the shared
server contract.

## Ownership

The Trainer Management System owns these eleven tables:

- `training_module`
- `learning_material`
- `participant_material_access`
- `assessment`
- `assessment_question`
- `training_content_block`
- `course_proposal`
- `teaching_plans`
- `teaching_plan_comments`
- `trainer_activity_log`
- `trainer_certificate`

Other systems may read them for agreed integrations. Writes must continue to
use the Trainer workflows so validation, authorization, file lifecycle, and
activity logging remain enforced.

## Canonical external references

Every reference to an owner-provided master uses its canonical string identity.
The deployment schema enforces the following foreign keys:

| Trainer-owned column | Canonical target | Delete behavior |
|---|---|---|
| `training_module.course_id VARCHAR(20)` | `course.courseID` | default restriction |
| `learning_material.course_id VARCHAR(20)` | `course.courseID` | cascade |
| `participant_material_access.course_id VARCHAR(20)` | `course.courseID` | cascade (updates also cascade) |
| `learning_material.trainer_id VARCHAR(20)` | `trainer.trainerID` | set null |
| `learning_material.session_id VARCHAR(20)` | `course_session.sessionID` | set null |
| `assessment.course_id VARCHAR(20)` | `course.courseID` | cascade |
| `course_proposal.trainer_id VARCHAR(20)` | `trainer.trainerID` | set null |
| `course_proposal.reviewed_by VARCHAR(20)` | `staff_edu.staffID` | set null |
| `teaching_plans.course_id VARCHAR(20)` | `course.courseID` | cascade |
| `teaching_plans.trainer_id VARCHAR(20)` | `trainer.trainerID` | cascade |
| `teaching_plan_comments.reviewer_staff_id VARCHAR(20)` | `staff_edu.staffID` | default restriction |
| `trainer_activity_log.trainer_id VARCHAR(20)` | `trainer.trainerID` | cascade |
| `trainer_activity_log.course_id VARCHAR(20)` | `course.courseID` | set null |
| `trainer_certificate.trainer_id VARCHAR(20)` | `trainer.trainerID` | cascade |

In simple integration terms:
`participant_material_access.course_id = course.courseID`. This relationship
is a physical database foreign key. The Aidid save workflow also validates
course existence so users receive a clear application response before the
database constraint is needed.

The Trainer repo does not own the referenced master tables. Their definitions
in the deployment artifact are local/integration mirrors of the authoritative
owner contracts.

## Internal relationships and identities

| Entity | Primary key | Contract |
|---|---|---|
| Module | `training_module.module_id` | signed `INT`, auto-increment |
| Material | `learning_material.material_id` | unsigned `BIGINT`, auto-increment |
| Participant material access | `participant_material_access.course_id` | `VARCHAR(20)`, one row per course |
| Assessment | `assessment.assessment_id` | unsigned `BIGINT`, auto-increment |
| Question | `assessment_question.question_id` | unsigned `BIGINT`, auto-increment |
| Content block | `training_content_block.block_id` | signed `BIGINT`, auto-increment |
| Proposal | `course_proposal.proposal_id` | unsigned `INT`, auto-increment |
| Teaching Plan | `teaching_plans.id` | unsigned `BIGINT`, auto-increment |
| Plan comment | `teaching_plan_comments.id` | unsigned `BIGINT`, auto-increment |
| Activity entry | `trainer_activity_log.log_id` | unsigned `BIGINT`, auto-increment |
| Certificate | `trainer_certificate.cert_id` | signed `INT`, auto-increment |

Owned foreign keys connect assessment questions to assessments, assessments to
modules, content blocks to modules/materials/assessments, and Teaching Plan
comments to Teaching Plans. The unique key on
`teaching_plans(course_id, trainer_id)` preserves one current submission for a
course and Trainer. The unique key on
`training_module(course_id, module_type)` preserves the existing module
business rule.

## Stored value contracts

| Table column | Stored contract |
|---|---|
| `assessment.type` | `quiz` or `submission` |
| `assessment.status` | default `Draft`; current authoring flow writes `Active` |
| `assessment.accepted_format` | `PDF Only`, `Document Only`, `Image Only`, `ZIP Only`, or `NULL` |
| `assessment_question.question_type` | `mcq`, `checkbox`, `true_false`, `short_answer`, or `essay` |
| `training_content_block.block_type` | `title`, `text`, `material`, `media`, `youtube`, `quiz`, or `submission` |
| `participant_material_access.is_enabled` | boolean; default `0` |
| `participant_material_access.access_until` | nullable `DATETIME`; `NULL` means no expiry when enabled |
| `course_proposal.status` | `Pending`, `Approved`, or `Rejected` |
| `course_proposal.training_mode` | `LMS` or `Simple` |
| `course_proposal.delivery_mode` | `Online`, `Physical`, or `Hybrid` |
| `course_proposal.target_audience` | JSON-text array of `Teacher`, `New Teacher`, and/or `Public` |

`learning_material.material_type`, `training_module.module_type`, and
`trainer_activity_log.action` remain extensible application values rather than
closed database enums.

## Database access

Proposal review and feedback reporting are implemented with Laravel
query-builder and application logic over the base tables. The Trainer
deployment owns no database views, functions, procedures, or triggers.
