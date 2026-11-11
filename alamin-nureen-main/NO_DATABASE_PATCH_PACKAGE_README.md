# TrainHub Al Amin — Full code package (no database patches)

This package is based on the 4 November 2026 full updated code with the
**no-trigger New Teacher LMS requirement implementation**.

## What is included
- Laravel application source (PHP, Blade, CSS, routes and settings).
- Updated credit-hour settings, dashboard and yearly Excel export.
- Staff EDU settings layout, observer search dropdown and enrollment design.
- NewTeacherLmsRequirementService and GuruNew model event support.
- Existing Laravel PHP migrations and application configuration, when present.

## What is deliberately omitted
- All standalone .sql scripts, including the LMS table and trigger scripts.
- The `database_patches/` and `database/patches/` standalone SQL folders.
- Local .env, .git, vendor, uploads and runtime files.

## Prerequisites
The database must already contain `trainhub_new_teacher_lms_requirements`
and the other application tables. This ZIP does not create or alter tables.
The LMS trigger is NOT required to save changes in TrainHub Credit Hour Settings.
Before changing the default, confirm each existing new teacher has the right
saved requirement (the app can backfill missing requirements with the current
setting; it cannot determine a past historical requirement).

## Cross-system limitation
A teacher created via TrainHub's GuruNew model will receive a requirement via
Laravel. A teacher inserted by a separate Teacher Employment application or
raw SQL will not trigger TrainHub's model event. That other application should
save the LMS requirement at appointment time (same transaction) to guarantee
preservation of the default from that exact moment. Otherwise TrainHub can
only backfill the missing requirement when it subsequently sees that teacher.

## Overlay to existing GitHub checkout
1. Back up the project, check `git status` is clean and pull latest `main`.
2. Extract this archive to `C:\xampp\htdocs`, preserving existing `.env` and
   `.git`. Extraction overlays files but does not delete existing files.
3. Review Git changes and test locally before committing/pushing.

Historical README files in this archive may mention database patches for
other deployments; the actual standalone SQL files have intentionally been
excluded from this package.
