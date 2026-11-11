# TrainHub Al Amin — October 12, 2026 Update

This is the **updated latest uploaded Laravel source** (`alamin-nureen(2).zip`). The original database and SSO login flow were not replaced. The distributed ZIP intentionally **does not include `.env` credentials, Git history, or the generated `vendor/` directory**. Keep your existing `.env` and `vendor/` when copying these files into your existing installation. For a new installation, use `.env.example` as a template, configure the real credentials privately, and run `composer install` before starting Laravel.

## Applied changes

1. **Post Observation** — in Teacher → New Teacher → Choose School → New Teacher. Displays the submitted PRE result from `pre_response`, EXTERNAL result and submitted POST attempts from `pdpc_response`. **Post Observation is optional.** After the latest submitted EXTERNAL result is `REPEAT`, Staff EDU may leave Post unassigned while the Teacher Employment system performs another EXTERNAL attempt, or choose to assign Post Attempt 1. The Post section stays visible even when there is no assignment, and all historical Post attempts remain visible after a later EXTERNAL `PASS`. Only the latest submitted EXTERNAL `REPEAT` permits creating a new Post assignment; if a Post attempt already exists, its latest submitted result must additionally be `REPEAT`. `PASS` and NULL/pending decisions block a new assignment. Existing EXTERNAL attempts remain managed by the other system, not TrainHub. The same eligible External Observer may be reused or a different eligible teacher selected. Each attempt and audit event is stored separately. POST results are never changed by TrainHub.
2. **Staff EDU creation** — the existing Settings → Staff EDU Management functionality remains available to **all active staff whose canonical `staff_edu.role` is `STAFF_EDU`**. It already uses uniqueness checks, a hashed random local placeholder password, and staff audit triggers. The UI now clearly explains that SSO credentials must be provisioned via the shared central login system; creating a staff record here does **not** automatically create a login password at the Al Amin Login gateway.
3. **QR attendance** — both Show QR actions on the Training course/session screens now open a popup showing the QR image and attendance link. Copy Link and Download QR (PNG) are available. Download requires an authenticated active Staff EDU session and reaches the **fixed** third-party QR server provider; if the server is offline or cannot reach that service, download returns a clear 503 error. The attendance URL remains unchanged.
4. **PIC certificate eligibility** — qualifying PIC/Staff EDU participants are eligible after **either** submitted participant feedback **or** submitted PIC/coordinator feedback for the same course, **provided they have approved attendance for the selected session**. The participant must already belong to the course's `course_participant` list; answering a form does not give an unregistered person a certificate.
5. **Observer lists** — larger, scrollable searchable teacher lists in the Observer, External Observer and Post Observer dialogs; teacher name, school and service years remain visible.
6. **New Teacher navigation** — New Teacher tab opens a school chooser first. Selecting a school opens only that school's new teachers and their existing assignments; Back to School List returns to the school chooser.

## Mandatory one-time database step — ONE assignment table (DBeaver / MySQL 8)

**The old `trainhub_post_assignment` patch has been removed. Do NOT apply that earlier patch.**
Back up `alamin_main_database` and obtain the shared database owner's approval.
In DBeaver, choose the intended connection/database, open SQL Editor, and run **only**:

`database_patches/2026_10_12_alter_observer_assignment_for_post.sql`

This uses `ALTER TABLE observer_assignment` to **add five columns** (`assignmentType`,
`attemptNo`, `postTeacherID`, `observationDate`, `createdByStaff`), the teacher/staff
foreign keys and a unique attempt constraint. It creates NO new table, does not
remove historical Observer / External Observer rows, and is meant to be run once.
The existing `observerID` and `externalObserverID` are unchanged. Older rows keep
`assignmentType=NULL` (legacy type is inferred from those existing columns);
new Post rows use `assignmentType='post'` and `attemptNo=1,2,...`.

**If you already manually added any of these columns, do not run the ALTER
script again.** Inspect `DESCRIBE observer_assignment` and reconcile the missing
columns/indexes/constraints on a backup copy first. Do not run `migrate`,
`migrate:fresh`, `db:wipe`, or a complete schema import against the shared database.

**If you previously created the old `trainhub_post_assignment` table and it already
contains real assignments**, see `database_patches/OPTIONAL_transfer_existing_post_rows.sql`.
It transfers those rows into `observer_assignment` after the ALTER patch; review
it carefully and take a backup. Do not drop the old table automatically.

### Cross-system contract

- Syiqin/Teacher Employment owns `pre_response` and `pdpc_response` assessment
  results (`observation_stage='EXTERNAL'/'POST'`, `attempt_no`, `result`,
  `status='Submitted'`). TrainHub reads them; it does not create or change them.
- TrainHub writes POST assignments to existing `observer_assignment` with
  `assignmentType='post'`, `attemptNo`, `postTeacherID`, `observationDate` and
  `createdByStaff`. New assignments have unique string IDs with prefix `PST`.
- The other system must use the **same** `gn_id` and attempt number, and read
  the Post Observer's `postTeacherID` in `observer_assignment` for each new POST
  form; this ZIP does not modify your friend's separate application.
- The latest submitted External `REPEAT` offers (but never automatically creates)
  Post Attempt 1. Staff may instead let the new teacher redo External Observation
  in the other system. If Staff EDU chooses Post and its latest submitted
  result is `REPEAT`, the next Post attempt can be assigned as long as External
  has not since passed. `PASS` or NULL/pending result prevents new assignments.
  Previous Post assignments and results stay visible regardless of later External outcomes.
- MySQL unique `(gn_id, assignmentType, attemptNo)` plus a new-teacher row lock
  inside the transaction prevents duplicates. Existing NULL assignment types
  remain unaffected by this unique index.

## Verification performed here

- PHP lint for changed controllers, support code, legacy logic, routes and Blade-rendered PHP: passed.
- Blade compilation of Teacher, Training and Settings templates: passed.
- JavaScript syntax checks on the changed Teacher and Training scripts: passed.
- Standalone Post Observation stage-transition assertions (External PASS/REPEAT; Post PASS/REPEAT/pending): five passed.
- Laravel route registered for `/training-qr/{sessionID}/download`: verified.

**Not run:** full PHPUnit suite and actual MySQL/SSO/QR-in-production checks. The execution environment lacks PHP `mysqli`, `dom`, `mbstring`, and `xmlwriter` extensions and does not have your live MySQL or SSO gateway access. Run acceptance tests on a backed-up staging copy before deploying.

### Suggested manual tests

1. Select a new teacher whose EXTERNAL assessment is `PASS`: no Post assignment. Change/test one with submitted EXTERNAL `REPEAT`: Post Attempt 1 becomes available.
2. Assign Attempt 1 to the same external teacher. Confirm its row in `observer_assignment` with `assignmentType='post'`. Have the friend’s system submit POST result `REPEAT` for attempt 1; confirm Attempt 2 unlocks. Submit `PASS` for Attempt 2; confirm Attempt 3 is blocked.
3. Use a different eligible teacher for a subsequent Post assignment; confirm older attempts remain visible.
4. As two different active Staff EDU users, each create a new unique Staff EDU member in Settings; verify no duplicate IDs/emails, and follow your central SSO provisioning process.
5. Click Show QR on attendance and session management screens, copy its link and download the PNG. Confirm link/QR both resolve to the correct session.
6. For a course PIC whose `course_participant` row exists, approve their attendance, submit a PIC feedback form and check Certificate → Eligible. Repeat with a participant form for another course/PIC. Without approved attendance the PIC must remain ineligible.
7. Confirm searching/selecting Observer and External Observer still works, and that New Teacher school selection filters correctly.

## Files added/changed

- `app/Support/PostObservationWorkflow.php` (new)
- `database_patches/2026_10_12_alter_observer_assignment_for_post.sql` (**manual one-time ALTER**), plus an optional old-row transfer file
- `tests/Unit/PostObservationWorkflowTest.php` (new)
- `app/Http/Controllers/TrainingQrController.php` (new)
- `routes/web.php`
- `app/Legacy/teacher.logic.php`
- `resources/views/legacy/teacher.blade.php`
- `app/Legacy/certificate.logic.php`
- `resources/views/legacy/course.blade.php`
- `resources/views/legacy/settings.blade.php`
- `public/assets/css/teacher.css`, `app/Legacy/assets/css/teacher.css`
- `public/assets/css/course.css`, `app/Legacy/assets/css/course.css`

## October 13 clarification — optional Post versus repeated External

- **No further DBeaver SQL is required if all five columns and constraints were already applied successfully.** The SQL patch remains as a reference for fresh installations only; **never re-run it on an already modified database**.
- A submitted EXTERNAL `REPEAT` unlocks the **option** to assign Post, but does not require it. EXTERNAL Attempt 2, 3, ... remains entirely in the Teacher Employment system.
- Post Observation is always displayed in New Teacher details with an **Optional** label, even when the latest EXTERNAL result is `PASS` or there has never been a Post assignment.
- The system checks the **latest submitted EXTERNAL attempt**, not the earliest REPEAT. Example: GN0007 External 1 REPEAT then External 2 PASS: no new Post assignment is offered.
- Historical Post assignments are never automatically deleted because the External stage later passed.
- A submitted POST with a `NULL` result (example: GN0005) is displayed as **Awaiting result** and cannot unlock another attempt.
- No automatic Post creation and no writes into the friend's `pdpc_response` data.

### Local test sequence after copying

1. In DBeaver verify your *already modified* `observer_assignment` columns; **do not run ALTER again**.
2. Open Teacher → New Teacher → choose school → new teacher: the Optional Post section is visible for every new teacher.
3. For GN0007 (EXTERNAL 1 REPEAT; EXTERNAL 2 PASS) the Post action should be unavailable, with the external attempt history visible.
4. For a new teacher with the **latest submitted EXTERNAL = REPEAT** and no Post history, "Assign Post Attempt 1 (Optional)" should appear; repeating EXTERNAL in Syiqin's system remains allowed.
5. For a Post attempt submitted with `result=NULL`, "Awaiting result" should appear and the next Post assignment must remain disabled.
6. Assign Post to an eligible teacher, verify the assignment is stored in `observer_assignment`, and have the friend's system submit POST `REPEAT` to enable the next attempt. POST `PASS` blocks another attempt. Verify existing history stays visible.
7. Separately regress-test Staff EDU creation, QR image/download, certificate PIC eligibility, teacher picker size, school-first New Teacher filtering and SSO.
