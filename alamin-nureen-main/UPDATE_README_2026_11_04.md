# TrainHub Al Amin — 4 November 2026 Update

## What's changed
- Credit Hour Settings: compact blue/white cards and editable multipliers for internal training, trainer credit, external training with management order, and external voluntary attendance.
- Annual Staff EDU Training / Tarbiah / Total credit cards on Dashboard.
- Download individual yearly credit history in Excel from the dashboard (current year card or a selected year in View History). Endpoint: `GET /credit-history/export?year=2026`. It only exports the logged-in active Staff EDU account.
- Settings: Profile Information on top-left, Change Password on top-right, Staff EDU Management below both.
- New Teacher assignments: searchable, downward-opening scrollable observer dropdowns (Pre, External, Post) that keep the existing select field names for submission.
- Enrollment page: simplified labels and consistent blue/white card design.
- Input placeholders are lighter across the system.

## LMS requirements — updated for servers without trigger privileges

Your shared database **already has** `trainhub_new_teacher_lms_requirements`.
**Do not recreate, DROP, or ALTER that table.** A MySQL trigger is **optional** for
TrainHub settings to save. `DashboardRulesController` no longer blocks saving
when the trigger is missing.

Before changing the LMS default (for example, 5 to 6), check that your existing
new teachers have stored requirements:

```sql
SELECT g.gn_id, g.gn_name, r.required_lms_courses, r.assigned_at
FROM guru_new g
LEFT JOIN trainhub_new_teacher_lms_requirements r ON r.gn_id = g.gn_id
ORDER BY g.gn_id;
```

If the teachers appointed under the old default have no record yet, **verify
what their old requirement should be**. Do not change the default until those
records are assigned. The updated Laravel code backfills missing records with
the existing default before changing it; it never overwrites an existing value.

The Laravel fallback also syncs missing requirements when Staff EDU visits the
Dashboard, New Teacher list, or Credit Hour Settings. A `GuruNew` Eloquent
`created` event saves the requirement immediately when a teacher is created
through this application's `GuruNew` model.

**Important cross-system limitation:** teachers created directly by your
friend's separate Teacher Employment system (or raw SQL) are not captured
immediately by this Laravel event. For guaranteed creation-time snapshots,
ask that system to save the requirement in the **same database transaction**
as the `guru_new` insert, using the following query and new teacher ID:

```sql
INSERT IGNORE INTO trainhub_new_teacher_lms_requirements
    (gn_id, required_lms_courses, assigned_at)
SELECT gn_id,
       COALESCE((SELECT CAST(setting_value AS UNSIGNED)
                 FROM trainhub_settings
                 WHERE setting_key = 'new_teacher_lms_required' LIMIT 1), 5),
       CURRENT_TIMESTAMP
FROM guru_new
WHERE gn_id = ?;
```

The other system should run this from its own authenticated backend (never
from a public page) and bind the actual newly created `gn_id` to `?`.
Using the same transaction means both inserts succeed or roll back together.
This is the recommended workaround when triggers cannot be installed.

### Optional SQL patch: trigger **only** (database administrator)

`database_patches/2026_11_04_lms_trigger_ONLY_for_DBA.sql` contains **only**
the `CREATE TRIGGER` statement. It does not create or modify the LMS table.
The account that produced SQL Error 1419 cannot execute this patch under the
current binary-logging configuration. A privileged database/server
administrator must assess and install it if shared-system automatic inserts
are required. Do **not** alter global MySQL safety settings yourself.

Check whether a trigger already exists before a DBA attempts to install it:

```sql
SHOW TRIGGERS WHERE `Trigger` = 'tr_th_guru_new_lms_snapshot';
```

Other group systems should read the saved per-teacher field:
`trainhub_new_teacher_lms_requirements.required_lms_courses`, not just the
current global default in `trainhub_settings`.

## Note about the October patch
The archive includes `database_patches/2026_10_29_enrollment_management_orders.sql` for new deployments. If you already created the two tables in DBeaver, do not execute that patch again.

## Export and calculation behavior
- XLSX uses the existing credit service's verified attendance records, including approved external orders and Tarbiah.
- XLSX includes staff ID, year, annual total/targets, each activity/date, credited hours and applied rule.
- Rows show activity credits before annual caps. Summary totals apply configured training and Tarbiah caps.
- Historical totals are recalculated using current credit settings; old multiplier policies are not versioned or frozen in this release.
- Exports are private to the logged-in Staff EDU account. The app creates a temporary XLSX and deletes it after download.
- Excel generation works without PHP `ext-zip` via the existing portable ZIP writer.

## Test after deployment
1. Back up DB, confirm the existing LMS table has the correct saved targets (no trigger required).
2. Open Credit Hour Settings: edit both Other Organiser multipliers and set the new default to 6; save.
3. Confirm existing teachers still show 5. Create a teacher through your app and confirm 6. For the other group's HR flow, install its creation-time integration or ask the DBA to create the optional trigger.
4. Open Dashboard: check Training, Tarbiah, Total cards.
5. Open View History: select a year, Filter, Download Excel; check numbers and row details.
6. Settings: check Profile/Password top row, Staff EDU Management below.
7. New Teacher: type in Pre, External and Post observer search and select from the downward scroll list; confirm POST values save.
8. Enrollment: check cards, filters, PIC/Staff enrollment and management-order approval.
9. Check placeholders on all major forms in desktop/mobile viewport.

## Validation status
- All relevant PHP source files passed `php -l` checks.
- Five updated Blade templates compiled and passed PHP syntax checks after compilation.
- JavaScript from teacher view passed `node --check`.
- Generated a sample XLSX that loaded through `openpyxl` with the expected rows.
- Laravel route listing found the `credit.history.export` route.
- Full PHPUnit/browser tests were **not** run: this container lacks PHP DOM, mbstring and XMLWriter extensions, and there is no connected MySQL server for live feature tests.

## Push without overwriting environment / Git metadata
Extract the supplied GitHub-ready ZIP over the current `C:\xampp\htdocs\alamin-nureen` repository, but **do not delete** the local `.git`, `.env`, `vendor` or uploads. ZIP intentionally excludes those directories/files. Review `git status`, `git diff --check`, and `git ls-files --cached -- .env` before committing and pushing.
