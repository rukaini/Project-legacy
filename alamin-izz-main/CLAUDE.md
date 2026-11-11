# EduTrain System — CLAUDE.md

Educator Training Management System for Al Amin Edu Oasis. Manages LADAP teacher training: registration, approvals, attendance (QR-based), task/quiz submission, notifications, and reporting.

Full specs: `docs/PRD_EduTrain_System.md`. Implementation order: `docs/Implementation_Plan_EduTrain.md`.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | React 19 (TypeScript), Inertia.js v2 |
| UI | shadcn/ui + Tailwind CSS v4 + Radix UI |
| Database | MySQL (local dev) |
| RBAC | Spatie laravel-permission v6 |
| Queue | Database driver (runs via `composer run dev`) |
| PDF | barryvdh/laravel-dompdf |
| QR | simplesoftwareio/simple-qrcode |

---

## Dev Environment

```bash
composer run dev        # starts server + queue + vite concurrently
php artisan schedule:work   # run in a separate terminal for scheduled jobs
```

After any `migrate:fresh --seed`:
```bash
php artisan permission:cache-reset
php artisan storage:link
```

Demo accounts (all password: `password`):
- `principal@edutrain.test` — Guru Besar
- `teacher1@edutrain.test`, `teacher2@edutrain.test`, `teacher3@edutrain.test` — Teachers
- `outsider@edutrain.test` — Outsider

---

## Current Scaffolding State

**Exists and working:**
- Auth controllers: login, register, password reset, email verification
- Settings controllers: profile update, password update
- `User` model (no `HasRoles` yet — add in Phase 2)
- Spatie permission tables migration (already present)
- `HandleInertiaRequests` middleware (shares `auth.user` to Inertia)
- `bootstrap/app.php` — Spatie middleware aliases NOT yet registered (do this before any route uses `role:`)
- shadcn/ui components in `resources/js/components/ui/`
- Single generic `dashboard.tsx` page (replace with role-specific pages)

**Does not exist yet — build in implementation plan order:**
All domain models, migrations, controllers, services, routes, and pages.

---

## RBAC — Spatie

### Three roles only. No granular permissions.

```php
// app/Enums/Role.php (create this first)
enum Role: string {
    case GuruBesar = 'guru_besar';
    case Teacher   = 'teacher';
    case Outsider  = 'outsider';
}
```

Always use `Role::X->value` — never hardcode the string `'guru_besar'` etc.

### Middleware registration (bootstrap/app.php)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        HandleInertiaRequests::class,
        AddLinkHeadersForPreloadedAssets::class,
    ]);
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

### Route middleware syntax

```php
->middleware('role:guru_besar')           // single role
->middleware('role:teacher|outsider')      // OR — pipe, no spaces
```

Do NOT use commas: `'role:teacher, outsider'` does not work.

### User model

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable {
    use HasRoles;

    public function isGuruBesar(): bool  { return $this->hasRole(Role::GuruBesar->value); }
    public function isTeacher(): bool    { return $this->hasRole(Role::Teacher->value); }
    public function isOutsider(): bool   { return $this->hasRole(Role::Outsider->value); }
    public function isParticipant(): bool { return $this->hasAnyRole([Role::Teacher->value, Role::Outsider->value]); }
}
```

### Assigning roles

```php
$user->assignRole(Role::Outsider->value);  // on self-registration
$user->assignRole(Role::Teacher->value);   // in UserSeeder
```

### Seeder order

```
RoleSeeder → UserSeeder → TrainingSeeder
```

`RoleSeeder` must run before `UserSeeder` — roles must exist before assignment.

### Three layers of access control

| Layer | Tool | Purpose |
|---|---|---|
| Route access | `role:` middleware | Who can reach the route |
| Business rules | `abort_if` / `abort_unless` in service | Whether the action is valid right now |
| UI visibility | `useRole()` hook in React | Show/hide buttons (not enforcement) |

Never rely on frontend role checks for security — they are UI-only.

---

## Architecture Patterns

### Controllers — thin
Controllers receive a request, call a service or model, return `Inertia::render(...)`. No business logic. No Eloquent queries beyond simple lookups.

### Services — one per complex module
Place in `app/Services/`. Create a service when a module has eligibility checks, multi-step writes, or event dispatching. Simple CRUD modules (Notifications, Feedback) do not need a service.

| Service | Module |
|---|---|
| `ApplicationService` | eligibility, approve, reject, assign |
| `AttendanceService` | time-window attendance marking, auto-absent |
| `QuizService` | MCQ auto-grading, score calculation |
| `TrainingService` | mark completed, auto-close |

### Form Requests — one per action
Every controller method that accepts user input has its own `FormRequest`. Validation never lives inline in controllers.

### Notifications — always queued
All notification listeners implement `ShouldQueue`. Never write to the `notifications` table synchronously in an HTTP request.

---

## Integration Seam (Teacher & Principal only)

Teacher and Principal data may come from an external system in Phase 2. These two entities use a repository abstraction. Everything else uses plain Eloquent directly.

```
app/Integration/
    Contracts/
        TeacherRepositoryInterface.php
        PrincipalRepositoryInterface.php
    DTOs/
        TeacherDTO.php
        PrincipalDTO.php
    Repositories/
        LocalTeacherRepository.php
        LocalPrincipalRepository.php
```

Bound in `AppServiceProvider::register()`. Controllers and services type-hint the interface, never the concrete class.

**Rule:** Never call `Teacher::find()` or `Principal::where()` directly outside the repository. All other models — plain Eloquent is fine.

---

## Directory Structure for New Files

```
app/
  Enums/Role.php
  Http/
    Controllers/
      Dashboard/          (GuruBesar, Teacher, Outsider)
      Auth/               (existing — modify only)
      Settings/           (existing — extend)
      TrainingController.php
      ApplicationController.php
      AttendanceController.php
      TaskController.php
      QuizController.php
      NotificationController.php
      FeedbackController.php
      ReportController.php
      TrainingHistoryController.php
    Requests/
      Training/
      Application/
      Task/
      Quiz/
      Feedback/
    Middleware/            (existing — HandleInertiaRequests)
  Integration/             (see above)
  Models/                  (one per table)
  Services/                (one per complex module)
  Policies/                (add only when ownership checks needed)
  Events/                  (one per notification trigger)
  Listeners/               (one per event, all ShouldQueue)
  Jobs/
    AutoMarkAbsentJob.php
  Console/Commands/
    CloseExpiredTrainings.php
    SendDeadlineReminders.php
    SyncStaffCommand.php   (no-op stub in Phase 1)

database/
  migrations/              (numbered, dependency-ordered)
  seeders/
    RoleSeeder.php
    UserSeeder.php
    TrainingSeeder.php
    DemoSeeder.php
    DatabaseSeeder.php

resources/
  js/
    pages/
      dashboard/           (guru-besar.tsx, teacher.tsx, outsider.tsx)
      trainings/
      applications/
      attendance/
      tasks/
      quizzes/
      history/
      notifications/
      feedback/
      reports/
    lib/auth.ts             (useRole() hook)
    types/index.ts          (extend with UserRole, update User interface)
  views/pdf/               (Blade templates for DomPDF — not Inertia)
    training-history.blade.php
    attendance-letter.blade.php
    training-summary.blade.php
```

---

## Inertia + React Conventions

### Page component structure
```tsx
// resources/js/pages/trainings/show.tsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface Props {
    training: Training;
    can_apply: boolean;      // computed on server, passed as prop
    has_applied: boolean;
}

export default function TrainingShow({ training, can_apply, has_applied }: Props) {
    const { isGuruBesar } = useRole();
    // ...
}
```

### Pass computed booleans from controller, not raw data for frontend to evaluate
```php
// Good — server decides eligibility
return Inertia::render('trainings/show', [
    'training'    => $training,
    'can_apply'   => $service->canApply($user, $training),
    'has_applied' => Application::existsFor($user, $training),
]);

// Bad — frontend computes eligibility from raw data
return Inertia::render('trainings/show', [
    'training'    => $training,
    'applications' => $training->applications,  // don't expose raw relation
]);
```

### Use existing shadcn/ui components
Components are in `resources/js/components/ui/`. Do not install additional component libraries. If a component is missing, build it from Radix UI primitives following the existing pattern.

### Shared props (from HandleInertiaRequests)
```typescript
// Available on every page via usePage<PageProps>().props
auth.user.id
auth.user.name
auth.user.email
auth.user.role       // 'guru_besar' | 'teacher' | 'outsider'
notifications.unread_count
```

---

## Database Conventions

- **Local:** SQLite at `database/database.sqlite`
- **Migrations:** Create in dependency order. Foreign keys must reference tables in earlier migrations.
- **Enums:** Use `$table->enum('status', ['open','closed','completed'])` — not plain `string`. Constrains values at DB level.
- **Timestamps:** All tables use `$table->timestamps()` except `audit_logs` which uses `$table->timestamp('created_at')` only.
- **Unique constraints:** `$table->unique(['user_id', 'training_id'])` on `applications`, `feedback`, `answer_sheets`, `attendance_records`.
- **Indexes:** Add index on `status` for `trainings`. Add index on `(user_id, is_read)` for `notifications`. Add unique index on `qr_token` for `attendance_sessions`.

---

## File Uploads

- Store via `Storage::disk('public')` to `storage/app/public/submissions/`
- Run `php artisan storage:link` once to create `public/storage` symlink
- Store relative paths in `task_submissions.file_path`
- Validate MIME type against `tasks.accepted_file_types` before storing

---

## PDF Export

- Use `barryvdh/laravel-dompdf` — render Blade templates (not Inertia pages)
- PDF Blade templates live in `resources/views/pdf/`
- Controller streams PDF: `return PDF::loadView(...)->download('filename.pdf')`
- Attendance letters: generate per participant in a loop, zip with PHP `ZipArchive`

---

## Scheduled Jobs

Registered in `routes/console.php`:
```php
Schedule::command('edutrain:close-expired-trainings')->dailyAt('00:01');
Schedule::command('edutrain:send-deadline-reminders')->dailyAt('07:00');
```

Auto-absent job is NOT cron-based — it is dispatched with a delay when a QR session is created:
```php
AutoMarkAbsentJob::dispatch($session)->delay($session->expires_at);
```

---

## Common Commands

```bash
# Reset and reseed (development)
php artisan migrate:fresh --seed && php artisan permission:cache-reset

# Clear all caches
php artisan optimize:clear

# Run a specific seeder
php artisan db:seed --class=DemoSeeder

# Check migration status
php artisan migrate:status

# Tinker for quick checks
php artisan tinker

# Run scheduled commands manually
php artisan edutrain:close-expired-trainings
php artisan edutrain:send-deadline-reminders

# Test queue processing (runs one job)
php artisan queue:work --once
```

---

## What Not To Do

- **Do not** add `role` ENUM column to `users` table — role is managed entirely by Spatie's `model_has_roles` pivot, not a column on users
- **Do not** call `Teacher::find()` or `Principal::where()` directly in controllers or services — go through the repository interface
- **Do not** use `'role:teacher, outsider'` (comma-space) in middleware — use pipe: `'role:teacher|outsider'`
- **Do not** put business logic in controllers — eligibility checks belong in services
- **Do not** mix Inertia pages and Blade views for user-facing pages — Blade is only for PDF templates
- **Do not** `assignRole()` anything other than `outsider` in `RegisteredUserController` — teachers and GB are seeded, never self-registered
- **Do not** forget `php artisan permission:cache-reset` after `migrate:fresh --seed` — stale cache causes `role:` middleware to fail silently
- **Do not** hardcode role strings like `'guru_besar'` — use `Role::GuruBesar->value` from the enum
- **Do not** trust frontend role checks for authorization — they are UI-only; every route and service action has its own backend check
