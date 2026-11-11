# TrainHub Al Amin Update - 5 October 2026

## Implemented changes

1. Certificate eligible list now supports both normal eligible participants and eligible Staff EDU/PIC records. PIC/coordinator feedback refreshes the matching `course_participant.isFeedbackCompleted` value.
2. Observer and External Observer selectors now have name/school search boxes.
3. Observer and External Observer candidates may be from the same school as the New Teacher.
4. Added responsive hardening for laptop, tablet/iPad, phone, modal, form, table and participant layouts.
5. Inactive/deactivated New Teachers remain visible but cannot be assigned an Observer or External Observer. This rule is enforced in both the UI and POST handler.
6. New Teacher list is ordered by school name and then teacher name.
7. Training participant attendance list is grouped by role: Teacher, New Teacher, Staff EDU/PIC, and Public/Outsider.
8. Staff EDU Settings now includes Staff EDU Management. An active `STAFF_EDU` can add only another `STAFF_EDU` account.
9. Training Type is a manual Simple/LMS choice. Database triggers were updated so target audience no longer overrides the selected type.
10. Dashboard Audit Log is limited to TrainHub-owned tables/actions from the combined database, including TrainHub Tarbiah tables.
11. Trainer list now keeps Active and Inactive trainers visible. Active trainers can be deactivated with a required reason; inactive trainers can be activated again. The reason is stored in `trainer.deactivationRemark`.
12. Participant attendance list displays the QR submission date/time using the existing `scanned_at` values.

## Required database update

Run from the Laravel project after pulling/copying the updated code:

```powershell
php artisan migrate
php artisan optimize:clear
```

The migrations are:

- `database/migrations/2026_10_05_000001_add_trainer_status_and_deactivation_remark.php`
- `database/migrations/2026_10_05_000002_allow_manual_training_type.php`

Manual SQL alternatives are also included under `database/patches/`.

## Verification performed

- PHP syntax lint passed for application PHP files and migrations.
- Updated Blade files compile to valid PHP.
- Settings, Trainer and Course routes remain registered.
- PHPUnit could not run in the supplied execution container because its PHP build is missing the `dom`, `mbstring`, and `xmlwriter` extensions. This is an environment limitation, not a detected project syntax error.

- The bundled canonical reference SQL was also aligned with the trainer deactivation remark and manual Simple/LMS rules.
