> **Important:** This README describes the initial 19 October build. For the latest changes, read `UPDATE_README_2026_10_19_CORRECTIONS.md` first. The newer correction note supersedes references here to Tarbiah approvals, LMS progress, and old observation-stage ENUM values.

# TrainHub Al Amin — 19 October 2026 change set

This updates the **13 October optional Post Observation build**. Keep all existing SSO, Teacher, QR and certificate files when integrating.

## Implemented in this ZIP

1. **Observation wording:** The three names used for stages in the Teacher interface are **Pre Observation**, **External Observation**, and **Post Observation**. Database IDs/ENUMs remain unchanged for compatibility. Post remains optional after External REPEAT, with repeat attempts already in the earlier project.
2. **Attendance Excel:** Staff EDU has **Download Attendance Excel** for each training session. Uses the layout from `Contoh Kehadiran Peserta.xlsx`: title, time, venue, one CLOCK IN/LOG IN table (NO, Timestamp, Nama Sekolah/Organisasi, Nama Penuh) and signature area. All scanned records are included; approval status isn't inferred from a clock-in. No CLOCK OUT is fabricated. Requires PHP `zip` extension (`ZipArchive`).
3. **Tarbiah menu:** All Staff EDU can see activities and enroll voluntarily. Rows are written to `staff_tarbiah_attendance` with **pending** status. A different active Staff EDU may approve/reject, and **only approved** Tarbiah adds its recorded `tarbiah.credit_hours` without a multiplier. Staff cannot approve their own attendance. Teacher attendance stays in the separate `tarbiah_attendance` table.
4. **Short public links:** Shared attendance QR uses `/a/<random-token>`; participant feedback uses `/f/<token>` and PIC feedback `/p/<token>`. Internal resolution preserves validations. URLs conceal record IDs but are NOT an authentication layer: keep email checks, and do not share private response URLs. Existing QR previews are regenerated with the new URL when opened, without requiring stored QR image regeneration.
5. **New Teacher LMS requirement:** Editable target on Dashboard (default 5). Completed = `courseType='LMS'`, `targetAudience='new_teacher'` **only**, `course.status='completed'`, every session approved, and feedback complete (`v_certificate_eligibility='Eligible'`). Dashboard includes a completion list. The definition should be confirmed with EDU if they count LMS completion differently.
6. **Configurable credit settings:** Dashboard allows annual targets for Training 30, Tarbiah 10, LADAP 30, plus editable Training multipliers: staff trainer ×1.5, Al Amin organiser ×1.0, other organiser ×0.5. **Staff EDU total = capped Training + capped approved Tarbiah; LADAP is not included.** Course has optional `credit_hours` for whole-course credit; if set, it is prorated by approved-session duration; if blank, session duration is used. Tarbiah hours come from `tarbiah.credit_hours` (fallback to scheduled duration). `penyelaras_ladap.credit_hours` belongs to the friend's LADAP/teacher system; no teacher-side credit calculation is installed by this ZIP.

## Database preparation — DO NOT RUN ON THE SHARED SERVER WITHOUT OWNER APPROVAL

1. Clone/backup `alamin_main_database` to an isolated **test** database. Switch your local `.env` DB_DATABASE to that test copy. Be mindful that even local Laravel code connected to `10.10.0.219` modifies the shared live database.
2. Open DBeaver connected to the test database and run `database_patches/2026_10_19_trainhub_features.sql` (it uses `DATABASE()` for the active schema). This patch is additive/idempotent: adds `course.credit_hours` and missing `tarbiah.credit_hours`, adds `trainhub_settings`, `trainhub_short_links`, and ensures `staff_tarbiah_attendance` exists. **Do NOT run the uploaded `tarbiah.sql`, `attendance.sql` or `tarbiah_attendance(1).sql` as imports.** Those are full dumps with `DROP TABLE`.
3. Check `staff_edu.credit_hour` CHECK: canonical legacy rule limits it to 40. If EDU will set Staff total targets above 40, ask the DB owner to review `database_patches/OPTIONAL_expand_staff_edu_credit_hour_limit.sql` before enabling that setting. Dynamic Dashboard display works without widening it; the persisted staff cache cannot exceed the old constraint.
4. `penyelaras_ladap.credit_hours` is already present in the file shared by your friend. TrainHub does not add or modify that table.

## Local deployment and checks

- Copy files from the ZIP into the existing Git-tracked folder while keeping `.env`, `.git`, `vendor` and local uploads. **Do not delete your old project folder.**
- Connect to the approved test DB, then run `php artisan optimize:clear` and `php artisan route:list`.
- Make sure `extension=zip` is enabled in your XAMPP `php.ini`.
- Run `php artisan test --filter=AttendanceXlsxExportTest`, then the existing `PostObservationWorkflowTest` and regressions.
- Do an end-to-end test of Tarbiah joining → another Staff EDU approves → dashboard credit; Training attendance approval → dashboard credit; editable targets; LMS completion; short attendance and feedback links; XLSX download; and SSO login/logout.
- **The earlier SSO redirect to `/systems` is unchanged.** Local authenticated testing still requires an approved local SSO callback or safe development-only authentication against a test database. No bypass is added.

## Caution: known integration boundaries

- `staff_edu.credit_hour` is a persisted cache with an old stored procedure and CHECK. The Dashboard calculates a current **read-only** total using the new rules, and the application refreshes the cache after its own attendance approval actions. If **another system** changes approval records directly, coordinate recalculation with the database owner; existing shared procedures/views still use fixed legacy values.
- Changes to LADAP credit target are stored in TrainHub settings; your friend's LADAP system must adopt the same target if they want shared calculation. TrainHub does **not** start counting LADAP towards Staff EDU hours.
- The sample Excel includes a LOG OUT section, but the new export intentionally omits it because TrainHub currently records clock-in only.
- The supplied code has passed PHP syntax and static integration checks; full tests against a real isolated MySQL/SSO stack must be run locally. No live MySQL data has been changed by producing this ZIP.
