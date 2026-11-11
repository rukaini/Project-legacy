# TrainHub Al Amin — Updates, 29 October 2026

## Included changes

1. Staff EDU account creation is accessible via **Settings → Add Staff EDU**, shown in the header for active Staff EDU/authorized admin accounts. Backend still validates role and active status. The account is created in `staff_edu` with a random unusable local password until **Al Amin central SSO** is provisioned for that person; adding them in TrainHub does NOT automatically create an account in the SSO owner's service.
2. Excel and Show QR buttons sit side-by-side, aligned right, with consistent design in the Training Attendance and Session views. Attendance Excel uses a portable XLSX writer even if PHP `ext-zip` is disabled, and does not contain clock-out records.
3. QR download allows active local Staff EDU/authorized admin sessions (still requires login), and downloads PNG from a fixed QR image provider. The server must be able to make outbound HTTPS requests. Do **not** make this endpoint public just to bypass a 403.
4. Public attendance forms and both feedback form types use root-aware absolute asset URLs; CSS and logo load when accessed from `/a/...`, `/f/...` or `/p/...` links. Clipboard buttons include fallbacks for HTTP sites where `navigator.clipboard` is unavailable.
5. **My Enrollment** (formerly Tarbiah) lists both training courses and Tarbiah, with Upcoming/Ongoing/History search and filters. Self-joining a training adds a Staff Participant and preserves it during legacy sync. Staff in a training's `staffAttendeeIDs` is a **PIC** and is listed under the PIC group, otherwise under **Staff Participants**. Current enrollment opens only for eligible ongoing/upcoming training and available Tarbiah.
6. Dashboard opens a dedicated **Credit Hour Settings** page. Redundant old explanatory text was removed. The page has separate annual Training/Tarbiah/LADAP targets, trainer/Al Amin rules, and the New Teacher LMS course target.
7. External organizer credit rules for Staff EDU:
   - approved management order for the **same staff and course** => **1.0×** (but only for verified approved attendance);
   - voluntary/unapproved/no order => **0.5×**;
   - staff as trainer retains existing **1.5×** exception; Al Amin organizer retains **1.0×**;
   - review includes reference, reviewer, timestamp, decision, and audit trail. **A staff member cannot approve their own order.** For a real deployment, institute an HR/management approval policy and confirm the provided reference is genuine; the software records an attestation but cannot authenticate a paper order by itself.
8. New Teacher tab uses existing school-card design and lists all schools. New Teacher list is compact; Post Observation and Pre results appear inside Assign/Edit modal instead of below the name. Observer/External/Post selections are dropdowns with real scroll and functioning name/school search.

## Database setup — REQUIRED for self-enrollment and management orders

Run `database_patches/2026_10_29_enrollment_management_orders.sql` on a **backed-up test database**, then apply to the shared DB only with owner approval. The script is additive and uses `CREATE TABLE IF NOT EXISTS`. Do not import `main_database_V6.sql` over a live database (contains `DROP TABLE`). Previous `2026_10_19_trainhub_features.sql` also remains required for short links, settings and Tarbiah credit.

## New Teacher LMS requirement — for shared subsystem developer

The required course count is **not** in `guru_new` or `teacher`. It is stored in:

```sql
SELECT CAST(setting_value AS UNSIGNED) AS required_lms_courses
FROM trainhub_settings
WHERE setting_key = 'new_teacher_lms_required';
```

Fallback default in `app/Support/TrainHubSettings.php` is `5` when a DB record is absent. Read this shared settings key from the teacher/employment system if needed; only the **New Teacher LMS** target is returned (not staff credit-hour targets).

## Developer verification / deployment

- Do not overwrite your existing `.env` or publish real credentials in Git. This ZIP excludes `.env` and `.git`; run `composer install` if `vendor` is absent.
- Set `APP_URL` to the actual local/server address, clear Laravel config and view caches (`php artisan optimize:clear`). Ensure Apache document root points to `public/`.
- Confirm session cookies work at the same domain/port; new protected routes should not be requested in a new, unsigned-in session.
- Verify attendance link styles, feedback/PIC link styles, copy fallback on **HTTP**, QR PNG download, XLSX download, Staff EDU creation + SSO provisioning, self-enrollment preservation after course view, PIC classification, management-order review, credit totals and teacher popup on your test database.
- PHP source syntax, route registration and Blade compilation were checked in this environment. A sample XLSX was generated **without ext-zip** and opened successfully via OpenPyXL. Full Laravel PHPUnit tests / live shared MySQL / live QR provider were **not runnable** here because the local PHP CLI lacks `dom`, `mbstring`, `xmlwriter`, `mysqli` and `gd`; complete end-to-end confirmation must be done on the intended XAMPP/server stack.
