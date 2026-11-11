# TrainHub Al Amin — User-requested corrections, 19 October 2026

This builds on the FULL UPDATED 19 October ZIP and preserves the attendance XLSX, Tarbiah joining, short links, Staff EDU Training settings, SSO, and optional Post Observation workflows.

## Corrected requirements

1. **Three exact stage names across TrainHub UI + observer_assignment database**
   - `Pre Observation`, `External Observation`, `Post Observation`.
   - `observer_assignment.assignmentType` now stores these exact case-sensitive display names via a **separate one-time DB migration** (see next section). Existing Pre/External assignments with NULL `assignmentType` are backfilled, and attempts are numbered per new teacher / stage. Existing Post attempt numbers are preserved.
   - Newly created or edited Pre/External assignment rows write the full stage; Post assignments also write the full stage.
   - IMPORTANT integration boundary: the friend's separate `pdpc_response.observation_stage` still stores `EXTERNAL` / `POST`, and Pre results are in `pre_response`. TrainHub still reads those exact values, because changing the external system's enum here would break its existing code. Coordinate any separate rename with that system's owner.
2. **Tarbiah attendance approval is NOT in TrainHub**
   - Removed the Tarbiah page's pending approval section, approve/reject buttons, controller action and POST route.
   - Staff EDU can still browse Tarbiah activities and join; new `staff_tarbiah_attendance` enrollment records begin `pending`.
   - External Tarbiah system/owner must mark confirmed attendance `approved` in the shared staff attendance table. No approval is simulated by TrainHub; pending records yield no credit. Confirm this integration with the other system's owner.
3. **New Teacher LMS: configurable setting only**
   - The adjustable default 5-LMS requirement remains on Dashboard settings.
   - Removed the Dashboard's New Teacher completion/progress list and its related query.
4. **Tarbiah credit hours directly from its table**
   - Approved staff Tarbiah rows read `tarbiah.credit_hours` directly, never recompute hours from start/end time, and never multiply by Training factors. A NULL credit_hours value contributes zero pending a data correction.
   - Staff EDU annual total includes Training plus verified Tarbiah, capped by their editable category targets. LADAP continues to be separate.

## DBeaver migration order — test database only

1. Back up shared MySQL and clone it into an isolated TEST schema. **Never run imported .sql dumps containing `DROP TABLE` on the shared database.**
2. If not already applied, run `database_patches/2026_10_19_trainhub_features.sql` for the previous release's settings / short links / course credit field.
3. **NEW REQUIRED**: run `database_patches/2026_10_19_normalize_observation_stage_names.sql` on that TEST database **only after** the October 12 Post Observation schema was applied. This migration changes the existing `observer_assignment.assignmentType` ENUM and historic values and preserves Post attempt numbers. It may affect your friend's consumers of the table; coordinate with them before applying on shared production.
4. If the DB owner maintains `v_staff_credit_hour` or stored procedures, coordinate updates to use `tarbiah.credit_hours` rather than time difference; TrainHub's dashboard now uses direct credit_hours independently of that legacy DB view.

## Acceptance checks

- Pre / External / Post assignment types in observer_assignment equal only the three full names; no old `observer`, `external`, `post` values after the migration.
- Existing External REPEAT still offers optional Post; Post history and attempt numbers are preserved; External PASS does not automatically create a Post assignment.
- Tarbiah page has **Join Tarbiah**, no approve/reject actions or review route. Approved external attendance uses the `tarbiah.credit_hours` figure as-is.
- Dashboard shows editable New Teacher LMS requirement but **no** individual completion/progress panel.
- Dashboard offers multipliers only for Training roles (trainer, Al Amin organiser, other organiser), not Tarbiah.
- Existing attendance XLSX exports, shorter attendance/feedback/PIC links, QR, and SSO routes remain present.

## Test status

PHP source passed syntax checking. Static checks confirmed deleted Tarbiah approval controls and New Teacher progress panel. No live MySQL/SSO integration testing was performed; new schema migration must be reviewed/tested with the database owner.
