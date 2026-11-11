# New Teacher LMS — no-trigger update

## What you need to do now

Your table `trainhub_new_teacher_lms_requirements` already exists. **Do not
CREATE it again or delete its records.** The updated TrainHub application no
longer requires the MySQL trigger to save Credit Hour Settings.

**Check existing targets in DBeaver before changing the default:**

```sql
SELECT g.gn_id, g.gn_name, r.required_lms_courses, r.assigned_at
FROM guru_new g
LEFT JOIN trainhub_new_teacher_lms_requirements r ON r.gn_id = g.gn_id
ORDER BY g.gn_id;
```

Existing targets (for example, 5) stay saved when Staff EDU changes the default
to 6. New teachers get the current default through the TrainHub `GuruNew` model.
The Dashboard, New Teacher page, and Credit Hour Settings page also backfill
teachers that do not yet have a saved target. This fallback does not overwrite
existing requirements.

## Other group's Teacher Employment system

An appointment made directly by a different application does **not** fire
TrainHub's Laravel model event. That application's developer should add the
following INSERT **inside the same transaction** immediately after inserting
`guru_new`, with a bound parameter for the new ID:

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

Without that integration or the optional database trigger, TrainHub will only
capture an external new-teacher record on a later page visit or setting change.
It cannot guarantee the exact appointment-time value if another system changes
the default outside TrainHub or during a race with record creation.

## Trigger-only patch (OPTIONAL: database administrator)

File: `database_patches/2026_11_04_lms_trigger_ONLY_for_DBA.sql`

This file has **only CREATE TRIGGER**, not CREATE TABLE or a new database
migration. SQL Error 1419 will persist if you execute it with the same account.
Ask the database administrator to run it if they approve installing it on the
shared server; do not try to bypass binary-logging safeguards yourself.

If the administrator creates the trigger, the Laravel code will still work;
its `INSERT IGNORE` prevents duplicate rows.

## GitHub deployment

1. Back up your working project and confirm `git status` is clean.
2. Copy the updated source files over your existing `alamin-nureen` folder,
   preserving `.env`, `.git`, `vendor`, and uploads.
3. Inspect `git status`, `git diff --check`, `git diff --cached --check`, and
   `git ls-files --cached -- .env` before committing.
4. Commit and push to your existing `main` branch. A GitHub push alone may not
   deploy to `cendekia.utem.edu.my:8082`.

## Verification

- DBeaver: inspect `trainhub_new_teacher_lms_requirements` before and after an
  LMS default change, confirming saved values do not change.
- Web: open Credit Hour Settings with no trigger and save a new default.
- Create a New Teacher through the TrainHub `GuruNew` model; verify the snapshot.
- Have your group mate create a New Teacher via their app after implementing the
  same-transaction insert; verify the snapshot immediately.

No live shared database or browser was connected during package verification.
