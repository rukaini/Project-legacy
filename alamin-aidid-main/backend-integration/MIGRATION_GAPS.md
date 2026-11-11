# Canonical migration and ownership status

The legacy `trainer_db` audit that originally motivated this file is not the
deployment target. The deployment target is the latest owner-approved shared
V4 schema; `deployment/trainer-canonical-schema.sql` is a tracked Aidid
integration reference rather than a replacement for that owner handoff.

## Production ownership

`2026_05_09_002502_create_trainer_system_tables.php` provisions only these
Trainer-owned business tables after external owners have provisioned their
dependencies:

- `training_module`
- `learning_material`
- `assessment`
- `assessment_question`
- `training_content_block`
- `course_proposal`
- `teaching_plans`
- `teaching_plan_comments`
- `trainer_activity_log`
- `trainer_certificate`

`2026_10_13_000001_create_participant_material_access_table.php` separately
adds the Aidid-owned `participant_material_access` integration setting after
the canonical external `course` table exists.
`participant_material_access.course_id = course.courseID` is enforced by a
physical foreign key with update and delete cascades. The save workflow still
validates the course before writing so validation errors remain user-friendly.

Trainer production migrations do not create the external fyp2, edudb, edu,
PSM Assessment, or agreed `trainer_document` tables. The full SQL artifact
contains local integration mirrors so an empty disposable schema can validate
the combined five-system contract.

## Active and retired migrations

`database/migrations/` contains only active production migrations: Laravel
framework infrastructure, the canonical Trainer-owned base tables, and the
participant-material-access extension. Trainer
runtime does not require database views, functions, procedures, or triggers.

Superseded conversion and owner-alignment migrations are explicit no-ops in
`history/retired-migrations/`. They are retained only to explain deployed
migration names and are outside Laravel's active discovery path. The fresh
canonical artifact already contains their final approved structure. No active
migration converts dummy numeric IDs or invents business identities.

## Seeder safety

`DatabaseSeeder` imports no business data. Historical and browser-QA seeders
were removed from the production seeder namespace after canonical runtime
verification.

## Remaining semantic gap

No required table definition is missing. The only unrelated unresolved rule is the owner meaning
of PSM attendance decimals (`timestamp_status` and `hours_ladap`). Until that
rule is supplied, the application does not derive attendance labels or rates.
