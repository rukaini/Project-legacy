# Implementation Plan
## Educator Training Management System (EduTrain) — Local Demo Build

**Project Code:** BITU 3973
**Stack:** Laravel 12 · Inertia.js v2 · React 19 (TypeScript) · SQLite (local) · shadcn/ui · Tailwind CSS v4
**Scope:** Phase 1 — full local demo of all 9 modules, no external system integration
**Build order:** Each phase depends on the previous. Do not skip ahead.
**Progress:** Phases 1–18 complete (18 / 18) + post-phase UI polish. System is demo-ready.

---

## How to Run the Dev Environment

```bash
composer run dev
```

This single command (already configured in `composer.json`) starts three processes concurrently:
- `php artisan serve` — Laravel backend on http://localhost:8000
- `php artisan queue:listen --tries=1` — processes background jobs (notifications, auto-absent)
- `npm run dev` — Vite HMR for React/TypeScript frontend

The scheduler must be run separately in a second terminal:
```bash
php artisan schedule:work
```

---

## Pre-Flight Checklist ✅ DONE

Before starting Phase 1, verify the scaffolding is working.

**Steps:**
1. Copy `.env.example` to `.env` — confirm `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite`
2. Run `composer install && npm install`
3. Run `php artisan key:generate`
4. Run `php artisan migrate` — confirm the 4 existing migrations run clean
5. Run `composer run dev` — confirm login page loads at http://localhost:8000

**Install missing packages (do this now, before any code):**
```bash
composer require barryvdh/laravel-dompdf
composer require simplesoftwareio/simple-qrcode
```

> `simple-qrcode` generates the QR image displayed on screen for the Guru Besar to project. Participants enter the token manually or scan it.

**Verify and publish Spatie:**
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
This creates `config/permission.php`. Run it even if it already exists — use `--force` to overwrite.

Confirm `2026_05_14_161448_create_permission_tables.php` is present in `database/migrations/`.

**Register Spatie middleware aliases** in `bootstrap/app.php` (Laravel 12 does not use `Kernel.php`):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```
This must be done in pre-flight before any route uses `role:` middleware or it will throw `Route middleware [role] not defined`.

---

## Phase 1 — Database Migrations ✅ DONE

**Goal:** All tables exist in the correct dependency order before any model or controller is written.

**Why first:** Every subsequent phase depends on the schema. Getting migrations right now avoids destructive resets later.

### Migration order (strict — each references the previous)

| Order | Migration file name | Creates table |
|---|---|---|
| 1 | `0001_01_01_000000` | `users`, `password_reset_tokens`, `sessions` *(exists)* |
| 2 | `2026_05_14_161448` | Spatie permission tables *(exists)* |
| 3 | new | `teachers` |
| 4 | new | `principals` |
| 5 | new | `outsiders` |
| 6 | new | `trainings` |
| 7 | new | `applications` |
| 8 | new | `attendance_sessions` |
| 9 | new | `attendance_records` |
| 10 | new | `tasks` |
| 11 | new | `task_submissions` |
| 12 | new | `quizzes` |
| 13 | new | `quiz_questions` |
| 14 | new | `quiz_options` |
| 15 | new | `answer_sheets` |
| 16 | new | `answer_responses` |
| 17 | new | `notifications` |
| 18 | new | `feedback` |
| 19 | new | `audit_logs` |

### Key column decisions for each table

**`users` (modify existing migration):**
- Add `external_id` VARCHAR nullable (integration seam — null for all local accounts)

**`teachers`:**
- `id`, `user_id` (FK → users), `department`, `position`, `external_id` nullable
- Unique on `user_id`

**`principals`:**
- `id`, `user_id` (FK → users), `phone_no` nullable, `department`, `external_id` nullable
- Unique on `user_id`

**`outsiders`:**
- `id`, `user_id` (FK → users), `organization` nullable, `contact_no` nullable
- Unique on `user_id`

**`trainings`:**
- `id`, `user_id` (FK → users — creator), `title`, `description` TEXT, `date` DATE, `time` TIME, `location`, `capacity` INT, `status` ENUM(`open`,`closed`,`completed`) default `open`, `registration_deadline` DATE, `category`, `competency_area`, `trainer_name`, `duration_hours` DECIMAL(5,2), `is_public` BOOLEAN default false
- Index on `status`

**`applications`:**
- `id`, `user_id` (FK → users), `training_id` (FK → trainings), `status` ENUM(`pending`,`approved`,`rejected`) default `pending`, `rejection_reason` TEXT nullable, `applied_at` TIMESTAMP
- Unique on `(user_id, training_id)`

**`attendance_sessions`:**
- `id`, `training_id` (FK → trainings), `qr_token` VARCHAR unique, `expires_at` TIMESTAMP, `date` DATE
- Index on `qr_token`

**`attendance_records`:**
- `id`, `user_id` (FK → users), `attendance_session_id` (FK → attendance_sessions), `status` ENUM(`present`,`absent`,`late`), `checked_in_at` TIMESTAMP nullable
- Unique on `(user_id, attendance_session_id)`

**`tasks`:**
- `id`, `training_id` (FK → trainings), `title`, `description` TEXT, `deadline` DATETIME, `accepted_file_types` VARCHAR nullable (comma-separated)

**`task_submissions`:**
- `id`, `user_id` (FK → users), `task_id` (FK → tasks), `text_answer` TEXT nullable, `file_path` VARCHAR nullable, `submitted_at` TIMESTAMP nullable, `status` ENUM(`not_submitted`,`submitted`,`graded`) default `not_submitted`, `grade` VARCHAR nullable
- Unique on `(user_id, task_id)`

**`quizzes`:**
- `id`, `training_id` (FK → trainings), `title`, `instructions` TEXT nullable, `time_limit` INT nullable (minutes), `available_from` DATETIME, `deadline` DATETIME

**`quiz_questions`:**
- `id`, `quiz_id` (FK → quizzes), `question_text` TEXT, `type` ENUM(`mcq`,`short_answer`), `display_order` INT

**`quiz_options`:**
- `id`, `question_id` (FK → quiz_questions), `option_text` VARCHAR, `is_correct` BOOLEAN default false

**`answer_sheets`:**
- `id`, `user_id` (FK → users), `quiz_id` (FK → quizzes), `total_score` DECIMAL(5,2) nullable, `submitted_at` TIMESTAMP nullable
- Unique on `(user_id, quiz_id)`

**`answer_responses`:**
- `id`, `answer_sheet_id` (FK → answer_sheets), `question_id` (FK → quiz_questions), `selected_option_id` (FK → quiz_options) nullable, `text_answer` TEXT nullable

**`notifications`:**
- `id`, `user_id` (FK → users), `message` TEXT, `type` VARCHAR, `url` VARCHAR nullable, `is_read` BOOLEAN default false, `created_at` TIMESTAMP
- Index on `(user_id, is_read)`

**`feedback`:**
- `id`, `user_id` (FK → users), `training_id` (FK → trainings), `rating` TINYINT (1–5), `comment` TEXT nullable, `submitted_at` TIMESTAMP
- Unique on `(user_id, training_id)`

**`audit_logs`:**
- `id`, `user_id` (FK → users) nullable, `action` VARCHAR, `target_type` VARCHAR, `target_id` BIGINT, `metadata` JSON nullable, `created_at` TIMESTAMP (no `updated_at`)

**Run after all migrations are written:**
```bash
php artisan migrate:fresh
```

**Acceptance:** `php artisan migrate:status` shows all migrations as `Ran`. No foreign key errors.

---

## Phase 2 — Eloquent Models & Relationships ✅ DONE

**Goal:** One model per table with all relationships defined. No business logic yet.

**Files to create** (`app/Models/`):
- `Teacher.php`, `Principal.php`, `Outsider.php`
- `Training.php`, `Application.php`
- `AttendanceSession.php`, `AttendanceRecord.php`
- `Task.php`, `TaskSubmission.php`
- `Quiz.php`, `QuizQuestion.php`, `QuizOption.php`
- `AnswerSheet.php`, `AnswerResponse.php`
- `Notification.php`, `Feedback.php`, `AuditLog.php`

**Modify existing:** `app/Models/User.php`

### Key model decisions

**`User.php`:**
- Add `HasRoles` trait from `Spatie\Permission\Traits\HasRoles` — this adds `assignRole()`, `removeRole()`, `hasRole()`, `hasAnyRole()`, `getRoleNames()` and the `roles` relationship to the model
- Add `HasPermissions` is included automatically via `HasRoles` — no separate import needed
- Add relationships: `hasOne(Teacher)`, `hasOne(Principal)`, `hasOne(Outsider)`, `hasMany(Application)`, `hasMany(Notification)`, `hasMany(Feedback)`, `hasMany(AuditLog)`
- Add typed helper methods that wrap Spatie calls — keep them here so the rest of the app never hardcodes role strings:
  - `isGuruBesar(): bool` → `return $this->hasRole('guru_besar');`
  - `isTeacher(): bool` → `return $this->hasRole('teacher');`
  - `isOutsider(): bool` → `return $this->hasRole('outsider');`
  - `isParticipant(): bool` → `return $this->hasAnyRole(['teacher', 'outsider']);`

> **Why helpers instead of calling `hasRole()` directly everywhere:** If the role slug ever changes (e.g., `guru_besar` → `principal`), you change one method, not every controller and policy.

**`Training.php`:**
- `belongsTo(User::class, 'user_id')` as `creator()`
- `hasMany(Application)`, `hasMany(AttendanceSession)`, `hasMany(Task)`, `hasMany(Quiz)`, `hasMany(Feedback)`
- Scopes: `scopeOpen()`, `scopePublic()`, `scopeCompleted()`
- Cast `is_public` to boolean, `status` to string

**`Application.php`:**
- `belongsTo(User)`, `belongsTo(Training)`
- Scopes: `scopePending()`, `scopeApproved()`

**`AttendanceSession.php`:**
- `belongsTo(Training)`, `hasMany(AttendanceRecord)`
- Helper: `isActive()` — returns `now()->isBefore($this->expires_at)`

**`Quiz.php`:**
- `belongsTo(Training)`, `hasMany(QuizQuestion)`, `hasMany(AnswerSheet)`

**`QuizQuestion.php`:**
- `belongsTo(Quiz)`, `hasMany(QuizOption)`, `hasMany(AnswerResponse)`
- Cast `type` to string

**`AnswerSheet.php`:**
- `belongsTo(User)`, `belongsTo(Quiz)`, `hasMany(AnswerResponse)`

**Acceptance:** Run `php artisan tinker` and confirm `User::first()->trainings` (or other relationships) does not throw.

---

## Phase 3 — Spatie RBAC Setup & Seeders ✅ DONE

**Goal:** Roles exist in the database. All demo accounts are created and assigned the correct role. The full Spatie permission system is wired and verified before any route or controller uses it.

---

### 3.1 — Spatie Configuration

**`config/permission.php` — key settings to verify:**

```php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role'       => Spatie\Permission\Models\Role::class,
],
'table_names' => [
    'roles'                 => 'roles',
    'permissions'           => 'permissions',
    'model_has_permissions' => 'model_has_permissions',
    'model_has_roles'       => 'model_has_roles',
    'role_has_permissions'  => 'role_has_permissions',
],
'column_names' => [
    'role_pivot_key'       => null,
    'permission_pivot_key' => null,
    'model_morph_key'      => 'model_id',
    'team_foreign_key'     => 'team_id',
],
'teams' => false,           // EduTrain is single-tenant — teams must be false
'cache' => [
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    'key'             => 'spatie.permission.cache',
    'store'           => 'default',
],
```

> **`teams: false` is critical.** If teams is `true` and `team_foreign_key` is not set, migrations will fail. EduTrain is single-institution — teams must be disabled.

---

### 3.2 — Role Design

EduTrain uses **three roles only**. No granular permissions are defined — role-level access is sufficient for this system's scope. All permission checks go through role membership.

| Role slug | Who has it | Assigned by |
|---|---|---|
| `guru_besar` | The school principal | Admin seeder (never self-registered) |
| `teacher` | School staff teachers | Admin seeder (never self-registered) |
| `outsider` | External participants | Auto-assigned on self-registration |

**Role slugs are the single source of truth.** Define them as constants in one place:

**`app/Enums/Role.php`** — PHP 8.1 backed enum:
```php
enum Role: string {
    case GuruBesar = 'guru_besar';
    case Teacher   = 'teacher';
    case Outsider  = 'outsider';
}
```

Use `Role::GuruBesar->value` everywhere instead of the raw string `'guru_besar'`. If you later rename a role, you change it in one place and the IDE finds all usages.

---

### 3.3 — Seeders

**`database/seeders/RoleSeeder.php`**

Creates the three Spatie `Role` records. Uses `firstOrCreate` so it is safe to run multiple times:
```
Role::firstOrCreate(['name' => Role::GuruBesar->value, 'guard_name' => 'web']);
Role::firstOrCreate(['name' => Role::Teacher->value,   'guard_name' => 'web']);
Role::firstOrCreate(['name' => Role::Outsider->value,  'guard_name' => 'web']);
```

> Must run before `UserSeeder` — users cannot be assigned a role that does not exist yet.

**`database/seeders/UserSeeder.php`**

Creates demo accounts. For each user:
1. Create `User` record
2. Call `$user->assignRole(Role::X->value)` — Spatie writes a row to `model_has_roles`
3. Create the linked profile record (`Teacher`, `Principal`, or `Outsider`)

Demo accounts:

| Email | Password | Role | Profile data |
|---|---|---|---|
| `principal@edutrain.test` | `password` | `guru_besar` | Dept: Administration, Phone: 010-0000001 |
| `teacher1@edutrain.test` | `password` | `teacher` | Dept: Mathematics, Position: Guru Kelas |
| `teacher2@edutrain.test` | `password` | `teacher` | Dept: Science, Position: Guru Mata Pelajaran |
| `teacher3@edutrain.test` | `password` | `teacher` | Dept: ICT, Position: Guru ICT |
| `outsider@edutrain.test` | `password` | `outsider` | Org: UTeM, Contact: 011-0000001 |

**`database/seeders/TrainingSeeder.php`**

Creates demo training programs (creator = GB user):
- 1 open, public, future date (outsiders can apply)
- 1 open, internal, future date (teachers only)
- 1 completed, past date (for history/feedback demo)

**`database/seeders/DatabaseSeeder.php`** — strict call order:
```
$this->call([
    RoleSeeder::class,    // 1. roles must exist first
    UserSeeder::class,    // 2. users assigned to roles
    TrainingSeeder::class // 3. trainings reference GB user
]);
```

---

### 3.4 — Wire Spatie into Self-Registration

Only Outsiders self-register. Modify `app/Http/Controllers/Auth/RegisteredUserController.php`:

After the `User::create(...)` call:
```
$user->assignRole(Role::Outsider->value);

Outsider::create([
    'user_id' => $user->id,
]);
```

> Teachers and Guru Besar are **never** created via the registration form. The registration route is only for outsiders. After Phase 5, the route group for registration does not need a role check — but a post-registration guard ensures anyone who self-registers always gets `outsider` and nothing else.

---

### 3.5 — Clear Spatie's Permission Cache

Spatie caches role/permission data. Run this after any seeder that creates or modifies roles:
```bash
php artisan permission:cache-reset
```

Add this as the last line in `RoleSeeder::run()` as well so it runs automatically during `migrate:fresh --seed`.

---

### 3.6 — Verification

```bash
php artisan migrate:fresh --seed
```

Then verify in tinker:
```php
$gb = User::where('email', 'principal@edutrain.test')->first();
$gb->hasRole('guru_besar');       // true
$gb->getRoleNames();              // ['guru_besar']
$gb->isGuruBesar();               // true (your helper)

$t = User::where('email', 'teacher1@edutrain.test')->first();
$t->hasRole('teacher');           // true
$t->hasAnyRole(['teacher','outsider']); // true (isParticipant)

$o = User::where('email', 'outsider@edutrain.test')->first();
$o->hasRole('outsider');          // true
```

Login at http://localhost:8000/login with each demo account. Confirm no errors.

---

### 3.7 — Spatie RBAC Quick Reference (used in all subsequent phases)

This table is the reference for every role check across the application:

| Context | Syntax | When to use |
|---|---|---|
| Route middleware | `->middleware('role:guru_besar')` | Protect an entire route or group |
| Multiple roles | `->middleware('role:teacher\|outsider')` | Either role — pipe-separated, no spaces |
| Controller check | `$request->user()->hasRole(Role::GuruBesar->value)` | Fine-grained check inside a method |
| Any of multiple roles | `$request->user()->hasAnyRole([...])` | When multiple roles share access |
| Policy method | `$user->hasRole(Role::GuruBesar->value)` | Inside a Laravel Policy class |
| Inertia shared prop | `'role' => $user->getRoleNames()->first()` | Expose to React for frontend guards |
| React frontend guard | `if (auth.user.role === 'guru_besar')` | Conditionally show/hide UI elements |
| Blade (PDF templates) | `@role('guru_besar') ... @endrole` | Spatie Blade directive in PDF views |

> **Frontend role checks are UI-only.** They show or hide buttons. They do not replace server-side middleware — every route and controller action has its own backend check. Never trust the frontend to enforce access.

---

## Phase 4 — Integration Abstraction (Teacher & Principal only) ✅ DONE

**Goal:** The two potentially-external entities are backed by repository interfaces. Everything else uses plain Eloquent. This is a small amount of code but is the correct time to do it — before any controller references Teacher or Principal directly.

### Files to create

**`app/Integration/DTOs/TeacherDTO.php`**
- Readonly class with properties: `int $id`, `int $userId`, `string $name`, `string $department`, `string $position`, `?string $externalId`

**`app/Integration/DTOs/PrincipalDTO.php`**
- Readonly class with properties: `int $id`, `int $userId`, `string $name`, `string $department`, `?string $phoneNo`, `?string $externalId`

**`app/Integration/Contracts/TeacherRepositoryInterface.php`**
- Methods: `findById(int $id): ?TeacherDTO`, `findByUserId(int $userId): ?TeacherDTO`, `all(): Collection`, `allForDropdown(): Collection`

**`app/Integration/Contracts/PrincipalRepositoryInterface.php`**
- Methods: `findByUserId(int $userId): ?PrincipalDTO`

**`app/Integration/Repositories/LocalTeacherRepository.php`**
- Implements `TeacherRepositoryInterface`
- Uses Eloquent `Teacher` model, maps rows to `TeacherDTO`

**`app/Integration/Repositories/LocalPrincipalRepository.php`**
- Implements `PrincipalRepositoryInterface`
- Uses Eloquent `Principal` model, maps rows to `PrincipalDTO`

**`app/Providers/AppServiceProvider.php`** — add bindings:
```
$this->app->bind(TeacherRepositoryInterface::class, LocalTeacherRepository::class);
$this->app->bind(PrincipalRepositoryInterface::class, LocalPrincipalRepository::class);
```

**Acceptance:** The repository interfaces are bound. No controller has been written yet. Run `php artisan tinker` and resolve the interface from the container — confirm it returns a `LocalTeacherRepository` instance.

---

## Phase 5 — Auth Module Wiring (M01) ✅ DONE

**Goal:** Login redirects to the correct role dashboard. All routes are protected by Spatie role middleware. The full RBAC boundary is enforced from this point forward. Role is shared to the React frontend via Inertia shared props.

---

### 5.1 — Role-Based Login Redirect

**`app/Http/Controllers/Auth/AuthenticatedSessionController.php`** — modify the `store()` method's redirect:

```php
$role = $request->user()->getRoleNames()->first();

return match($role) {
    Role::GuruBesar->value => redirect()->route('dashboard.guru-besar'),
    Role::Teacher->value   => redirect()->route('dashboard.teacher'),
    Role::Outsider->value  => redirect()->route('dashboard.outsider'),
    default                => redirect()->route('dashboard.outsider'),
};
```

Use `getRoleNames()->first()` not `hasRole()` in a chain — it's one DB call via Spatie's cached result.

---

### 5.2 — Route Groups with Spatie Middleware

**`routes/web.php`** — full structure:

```
// ── Public (no auth) ──────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

// ── Auth routes (handled by routes/auth.php) ──────────────
// login, register, forgot-password, reset-password,
// verify-email, confirm-password

// ── Authenticated (all roles) ─────────────────────────────
Route::middleware('auth')->group(function () {

    POST /logout
    GET  /notifications          → NotificationController@index
    POST /notifications/mark-read → NotificationController@markRead
    DELETE /notifications         → NotificationController@clear

    // Settings (uses existing settings.php routes)
});

// ── Guru Besar only ───────────────────────────────────────
Route::middleware(['auth', 'role:guru_besar'])
    ->name('gb.')
    ->group(function () {

    GET  /dashboard/principal          → GuruBesarDashboardController
    GET  /trainings/create             → TrainingController@create
    POST /trainings                    → TrainingController@store
    GET  /trainings/{training}/edit    → TrainingController@edit
    PATCH /trainings/{training}        → TrainingController@update
    POST /trainings/{training}/cancel  → TrainingController@cancel
    POST /trainings/{training}/complete → TrainingController@complete
    GET  /applications                 → ApplicationController@index
    POST /applications/{app}/approve   → ApplicationController@approve
    POST /applications/{app}/reject    → ApplicationController@reject
    POST /trainings/{training}/assign  → ApplicationController@assign
    ... (all GB-only routes)
});

// ── Participants (teacher OR outsider) ────────────────────
Route::middleware(['auth', 'role:teacher|outsider'])
    ->group(function () {

    GET  /dashboard/teacher            → TeacherDashboardController   (role:teacher)
    GET  /dashboard/outsider           → OutsiderDashboardController  (role:outsider)
    POST /trainings/{training}/apply   → ApplicationController@store
    GET  /my-applications              → ApplicationController@myApplications
    GET  /my-history                   → TrainingHistoryController@index
    GET  /my-history/export            → TrainingHistoryController@export
    POST /tasks/{task}/submit          → TaskController@storeSubmission
    POST /quizzes/{quiz}/attempt       → QuizController@submit
    GET  /trainings/{training}/feedback/create → FeedbackController@create
    POST /trainings/{training}/feedback        → FeedbackController@store
    ... (all participant routes)
});

// ── All authenticated users ───────────────────────────────
Route::middleware('auth')->group(function () {
    GET /trainings              → TrainingController@index  (public listing)
    GET /trainings/{training}   → TrainingController@show
});
```

**Spatie middleware syntax rules:**
- Single role: `'role:guru_besar'`
- Multiple roles (OR): `'role:teacher|outsider'` — pipe-separated, **no spaces around the pipe**
- Spatie throws a `403 Unauthorized` (not a redirect) when a role check fails — handle this in the exception handler or with a custom 403 page

---

### 5.3 — Share Role to Inertia (React Frontend)

**`app/Http/Middleware/HandleInertiaRequests.php`** — update `share()`:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'email' => $request->user()->email,
                'role'  => $request->user()->getRoleNames()->first(),
                // single role string — 'guru_besar' | 'teacher' | 'outsider'
            ] : null,
        ],
        'notifications' => [
            'unread_count' => 0, // updated in Phase 11
        ],
    ];
}
```

**Why share role as a string, not a boolean per role:** The React frontend needs to branch on role in many places (`if auth.user.role === 'guru_besar'`). A single string is cleaner than three booleans and matches the enum values directly.

**Update TypeScript types** in `resources/js/types/index.ts`:
```typescript
export type UserRole = 'guru_besar' | 'teacher' | 'outsider';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
}

export interface Auth {
    user: User | null;
}
```

**Add `auth` and `notifications` to the `PageProps` interface** so all Inertia pages have typed access to `auth.user.role`.

---

### 5.4 — Custom 403 Page

When Spatie blocks a route, Laravel renders a default 403. Add a proper one:

**`resources/views/errors/403.blade.php`:**
```html
<!DOCTYPE html>
<html>
<body>
    <h1>403 — Access Denied</h1>
    <p>You do not have permission to view this page.</p>
    <a href="{{ url()->previous() }}">Go back</a>
</body>
</html>
```

> A polished 403 page is important for demo — the default one looks unfinished.

---

### 5.5 — Dashboard Placeholders

**Three placeholder controllers:**
- `app/Http/Controllers/Dashboard/GuruBesarDashboardController.php`
- `app/Http/Controllers/Dashboard/TeacherDashboardController.php`
- `app/Http/Controllers/Dashboard/OutsiderDashboardController.php`

Each returns `Inertia::render('dashboard/[role]', [])` with empty props for now. Stats are filled in Phase 14.

**Three placeholder Inertia pages:**
- `resources/js/pages/dashboard/guru-besar.tsx` — renders `<h1>Guru Besar Dashboard</h1>`
- `resources/js/pages/dashboard/teacher.tsx`
- `resources/js/pages/dashboard/outsider.tsx`

---

### 5.6 — React Role Guard Utility

Create `resources/js/lib/auth.ts` — helper for frontend role checks:

```typescript
import { UserRole } from '@/types';

export function useRole() {
    const { auth } = usePage<PageProps>().props;
    return {
        role: auth.user?.role,
        isGuruBesar: auth.user?.role === 'guru_besar',
        isTeacher:   auth.user?.role === 'teacher',
        isOutsider:  auth.user?.role === 'outsider',
        isParticipant: auth.user?.role === 'teacher' || auth.user?.role === 'outsider',
    };
}
```

Use this hook in Inertia pages wherever UI elements should be conditionally shown:
```tsx
const { isGuruBesar } = useRole();

{isGuruBesar && <Button>Create Training</Button>}
```

> This is a **UI convenience only**. The backend route middleware is the real enforcement.

---

### 5.7 — Acceptance

- Log in as `principal@edutrain.test` → confirm redirect to `/dashboard/principal`
- Log in as `teacher1@edutrain.test` → confirm redirect to `/dashboard/teacher`
- Log in as `outsider@edutrain.test` → confirm redirect to `/dashboard/outsider`
- While logged in as teacher, navigate to `/trainings/create` (GB-only route) → confirm 403
- While logged in as outsider, navigate to `/applications` (GB-only route) → confirm 403
- Open browser console on any dashboard page → confirm `window.__inertia` props contain `auth.user.role` with the correct value

---

## Phase 6 — Training Module (M02) ✅ DONE

**Goal:** Guru Besar can create, edit, cancel training programs. All roles can browse and view them.

### Files to create

**Controller:** `app/Http/Controllers/TrainingController.php`
- `index()` — list trainings (filtered by role: GB sees all, participants see open only)
- `create()` — show form
- `store()` — validate + save + trigger notification event
- `show()` — training detail
- `edit()` — show prefilled form (GB only)
- `update()` — validate + save + notify if date/location changed
- `cancel()` — set status `closed` + notify approved applicants

**Form Requests:**
- `app/Http/Requests/Training/StoreTrainingRequest.php` — validates all required fields
- `app/Http/Requests/Training/UpdateTrainingRequest.php`

**Routes — split across role groups (defined in Phase 5.2):**
```
// All authenticated (role middleware: 'auth' only)
GET    /trainings                     → index
GET    /trainings/{training}          → show

// Guru Besar only (role middleware: 'role:guru_besar')
GET    /trainings/create              → create
POST   /trainings                     → store
GET    /trainings/{training}/edit     → edit
PATCH  /trainings/{training}          → update
POST   /trainings/{training}/cancel   → cancel
```

**Additional controller-level role check in `index()`:** The listing logic differs by role — GB sees all statuses, participants only see `open`. Use `$request->user()->isGuruBesar()` (the helper from Phase 2) to branch the query, not a second middleware.

**Inertia pages (`resources/js/pages/trainings/`):**
- `index.tsx` — card/table list with status badge, filter by status/category, search by title. Shows "Apply" button for teachers/outsiders on open trainings.
- `show.tsx` — full training detail: title, date, location, trainer, LADAP category, capacity remaining, registration deadline. Shows apply button or application status.
- `create.tsx` — form with all fields. Date picker, category select, capacity number input.
- `edit.tsx` — same form pre-filled.

**Acceptance:** Log in as Guru Besar. Create a training. Verify it appears in the list. Edit it. Cancel it. Log in as Teacher — confirm cancelled training is not visible as open.

---

## Phase 7 — Application & Approval Module (M03) ✅ DONE

**Goal:** Teachers and Outsiders can apply. Guru Besar can approve, reject, and directly assign teachers.

### Files to create

**Service:** `app/Services/ApplicationService.php`
- `apply(User $user, Training $training): Application` — runs all eligibility checks (not applied before, training open, capacity available, before deadline), creates Application record, dispatches `ApplicationSubmitted` event
- `approve(Application $application, User $guruBesar): void` — updates status, dispatches `ApplicationApproved` event, writes audit log
- `reject(Application $application, User $guruBesar, ?string $reason): void` — updates status, dispatches `ApplicationRejected` event, writes audit log
- `assignDirect(Training $training, array $userIds, User $guruBesar): void` — creates pre-approved Application records, dispatches `TeacherAssigned` event per user

**Controller:** `app/Http/Controllers/ApplicationController.php`
- `store()` — calls `ApplicationService::apply()`
- `index()` — GB: list all pending applications with filters
- `approve()` — calls `ApplicationService::approve()`
- `reject()` — calls `ApplicationService::reject()`
- `assign()` — calls `ApplicationService::assignDirect()`
- `myApplications()` — Teacher/Outsider: list their own applications with status

**Form Requests:**
- `StoreApplicationRequest.php`
- `RejectApplicationRequest.php` — validates optional rejection reason
- `AssignTeachersRequest.php` — validates array of user IDs

**Routes — split across role groups:**
```
// Participants only (role middleware: 'role:teacher|outsider')
POST   /trainings/{training}/apply        → store
GET    /my-applications                   → myApplications

// Guru Besar only (role middleware: 'role:guru_besar')
GET    /applications                      → index
POST   /applications/{application}/approve → approve
POST   /applications/{application}/reject  → reject
POST   /trainings/{training}/assign       → assign
```

**Additional controller-level check in `assign()`:** After the route middleware passes, verify the selected user IDs all belong to users with the `teacher` role. Use `User::whereIn('id', $userIds)->get()->each(fn($u) => abort_unless($u->isTeacher(), 422))`. This prevents the GB from accidentally assigning an outsider account via a crafted request.

**Inertia pages:**
- `resources/js/pages/applications/index.tsx` — GB view: table of pending applications, approve/reject buttons, rejection reason modal
- `resources/js/pages/applications/my-applications.tsx` — participant view: list with status badges, rejection reason shown if rejected
- `resources/js/pages/trainings/assign.tsx` — GB view: checklist of unassigned teachers for a training, confirm button

**Acceptance:** As Teacher, apply for open training. As Guru Besar, see the pending application, approve it. Switch back to Teacher — confirm status shows Approved. Test rejection with reason. Test direct assignment.

---

## Phase 8 — Attendance Module (M04) ✅ DONE

> **QR code implementation:** The backend `simplesoftwareio/simple-qrcode` package (v0.0.4@beta) is installed but its service provider is not registered. QR code generation was implemented on the frontend using `qrcode.react` (npm) — the token is already passed as a prop so no backend changes were needed. The QR encodes the full mark-attendance URL (`/trainings/{id}/attendance/mark?token={token}`); participants scan it with their phone camera and the browser opens the page with the token pre-filled automatically. The manual copy-token fallback is kept for environments without a camera.


**Goal:** Guru Besar generates a QR code. Approved participants enter the token to mark attendance. System auto-marks absent after window closes.

### Files to create

**Service:** `app/Services/AttendanceService.php`
- `generateSession(Training $training, Carbon $date, int $windowMinutes = 30): AttendanceSession` — creates session record, generates `Str::random(32)` QR token, sets `expires_at`, dispatches `AutoMarkAbsentJob` with delay equal to `expires_at`
- `markAttendance(User $user, string $token): AttendanceRecord` — validates token exists, not expired, user has approved application, not already marked; creates record with status `present`
- `autoMarkAbsent(AttendanceSession $session): void` — finds all approved applicants with no record for this session, creates `absent` records, writes audit log

**Job:** `app/Jobs/AutoMarkAbsentJob.php`
- Receives `AttendanceSession` ID
- Calls `AttendanceService::autoMarkAbsent()`
- Should be dispatched with delay: `dispatch(...)->delay($session->expires_at)`

**Controller:** `app/Http/Controllers/AttendanceController.php`
- `manage(Training $training)` — GB: show session list + generate QR button
- `generate(Training $training)` — GB: calls service, returns session with QR image
- `markForm(Training $training)` — Participant: show token entry form
- `mark(Training $training)` — Participant: calls service, returns success/error
- `report(Training $training)` — GB: attendance table per session

**Routes — split across role groups:**
```
// Guru Besar only
GET    /trainings/{training}/attendance            → manage
POST   /trainings/{training}/attendance/generate   → generate
GET    /trainings/{training}/attendance/report     → report

// Participants only (role:teacher|outsider)
GET    /trainings/{training}/attendance/mark       → markForm
POST   /trainings/{training}/attendance/mark       → mark
```

**Inertia pages:**
- `resources/js/pages/attendance/manage.tsx` — GB: list of sessions with QR display (uses `simple-qrcode` output rendered as `<img src="data:image/svg+xml...">`), active window countdown
- `resources/js/pages/attendance/mark.tsx` — Participant: token input field, submit button, success/error message
- `resources/js/pages/attendance/report.tsx` — GB: table of participants × sessions, status badges, export button (Phase 16)

**Acceptance:** Generate QR for a training session. Log in as approved Teacher in a second browser tab. Enter the token — confirm status becomes Present. Wait for window to expire (or manually set `expires_at` to the past and run the job). Confirm unrecorded participants are marked Absent.

---

## Phase 9 — Task Submission Module (M05) ✅ DONE

**Goal:** Guru Besar creates tasks. Approved participants submit files or text answers. Guru Besar grades submissions.

### Files to create

**Controller:** `app/Http/Controllers/TaskController.php`
- `index(Training $training)` — list tasks for a training
- `create(Training $training)` — GB: show task creation form
- `store(Training $training)` — GB: validate + save task, dispatch `TaskCreated` event
- `submit(Task $task)` — Participant: show submission form (eligibility checked here)
- `storeSubmission(Task $task)` — Participant: validate, store file (via `Storage::disk('public')`), save submission record
- `grade(Task $task)` — GB: list all submissions for a task
- `saveGrade(TaskSubmission $submission)` — GB: save grade, dispatch `SubmissionGraded` event

**Form Requests:**
- `StoreTaskRequest.php` — validates title, description, deadline, accepted_file_types
- `StoreTaskSubmissionRequest.php` — validates file upload (mime check against task's accepted_file_types) or text_answer present
- `GradeSubmissionRequest.php` — validates grade field

**Routes — split across role groups:**
```
// All authenticated
GET    /trainings/{training}/tasks         → index

// Guru Besar only
GET    /trainings/{training}/tasks/create  → create
POST   /trainings/{training}/tasks         → store
GET    /tasks/{task}/grade                 → grade
PATCH  /submissions/{submission}/grade     → saveGrade

// Participants only
GET    /tasks/{task}/submit                → submit
POST   /tasks/{task}/submit                → storeSubmission
```

**Inertia pages:**
- `resources/js/pages/tasks/index.tsx` — task list with deadline badges and submission status per participant
- `resources/js/pages/tasks/create.tsx` — form: title, description, deadline datetime picker, accepted file types checkboxes
- `resources/js/pages/tasks/submit.tsx` — drag-and-drop file upload zone, status badge (Not Submitted / Submitted / Graded), optional text area, deadline countdown
- `resources/js/pages/tasks/grade.tsx` — table of submissions, download link for files, grade input per row

**Acceptance:** Create a task on a training. Log in as approved participant. Submit a file. Log in as GB. View submission, enter grade. Verify participant sees grade in their task list.

---

## Phase 10 — Quiz Module (M06) ✅ DONE

**Goal:** Guru Besar creates quizzes with MCQ and short-answer questions. Participants attempt quizzes. MCQ is auto-graded on submit.

### Files to create

**Controller:** `app/Http/Controllers/QuizController.php`
- `index(Training $training)` — list quizzes
- `create(Training $training)` — GB: multi-step form
- `store(Training $training)` — GB: validate + save quiz + questions + options
- `attempt(Quiz $quiz)` — Participant: eligibility check, display questions
- `submit(Quiz $quiz)` — Participant: save answer sheet + responses, auto-grade MCQ
- `results(Quiz $quiz)` — GB: results table per participant
- `adjustScore(AnswerSheet $sheet)` — GB: manually adjust short-answer score

**Service:** `app/Services/QuizService.php`
- `autoGrade(AnswerSheet $sheet): float` — for each MCQ response, check if `selected_option_id` matches a `QuizOption` with `is_correct = true`. Sum correct answers. Store in `total_score`.
- `submit(User $user, Quiz $quiz, array $responses): AnswerSheet` — creates AnswerSheet + AnswerResponse rows, calls `autoGrade`, dispatches nothing (score shown immediately)

**Form Requests:**
- `StoreQuizRequest.php` — validates quiz fields + nested questions array + options per MCQ
- `SubmitQuizRequest.php` — validates `responses` array keyed by `question_id`

**Routes — split across role groups:**
```
// All authenticated
GET    /trainings/{training}/quizzes        → index

// Guru Besar only
GET    /trainings/{training}/quizzes/create → create
POST   /trainings/{training}/quizzes        → store
GET    /quizzes/{quiz}/results              → results
PATCH  /answer-sheets/{sheet}/score         → adjustScore

// Participants only
GET    /quizzes/{quiz}/attempt              → attempt
POST   /quizzes/{quiz}/attempt              → submit
```

**Inertia pages:**
- `resources/js/pages/quizzes/create.tsx` — dynamic form: quiz metadata fields + "Add Question" button; each question has type selector (MCQ shows option inputs with "correct" radio, short_answer shows just a text field)
- `resources/js/pages/quizzes/attempt.tsx` — question list with answered/unanswered badges, progress bar ("X of Y answered"), countdown timer with pulse animation when under 60 s, submit button disabled after time runs out
- `resources/js/pages/quizzes/results.tsx` — table: participant name, score, attempt date, "View answers" per row
- `resources/js/pages/quizzes/answer-detail.tsx` — per participant: question, their answer, correct answer (for MCQ), manual score input (for short_answer)

**Acceptance:** Create a quiz with 2 MCQ and 1 short-answer. Attempt as Teacher — answer questions. Confirm auto-score shows immediately for MCQ portion. Log in as GB — adjust short-answer score. Verify updated total score.

---

## Phase 11 — Notification System (M08) ✅ DONE

**Goal:** In-app notifications are created and delivered for all trigger events. Notification bell badge shows unread count. Clicking a notification navigates to the relevant page.

### Events to create (`app/Events/`)

| Event class | Triggered by |
|---|---|
| `ApplicationSubmitted` | `ApplicationService::apply()` |
| `ApplicationApproved` | `ApplicationService::approve()` |
| `ApplicationRejected` | `ApplicationService::reject()` |
| `TeacherAssigned` | `ApplicationService::assignDirect()` |
| `TrainingCreated` | `TrainingController::store()` |
| `TrainingCancelled` | `TrainingController::cancel()` |
| `TrainingUpdated` | `TrainingController::update()` (date/location changed) |
| `TaskCreated` | `TaskController::store()` |
| `SubmissionGraded` | `TaskController::saveGrade()` |
| `FeedbackRequested` | `TrainingService::markCompleted()` (Phase 13) |

### Listeners to create (`app/Listeners/`)

One listener per event. Each listener:
1. Identifies the target user(s)
2. Creates one `Notification` model record per target
3. Is queued (implements `ShouldQueue`)

Example: `SendApplicationApprovedNotification` listener — creates a Notification for the applicant with `type = 'application_approved'`, `url = route('my-applications')`, message = "Your application for [Training] has been approved."

**Register all in `app/Providers/EventServiceProvider.php`** (or `AppServiceProvider` in Laravel 12).

### Notification Controller: `app/Http/Controllers/NotificationController.php`
- `index()` — returns all user notifications ordered by newest
- `markRead()` — marks all as `is_read = true`
- `clear()` — deletes all user notifications

**Routes — all authenticated (no role restriction — all roles receive notifications):**
```
// All auth (role middleware: 'auth' only)
GET    /notifications              → index
POST   /notifications/mark-read   → markRead
DELETE /notifications              → clear
```

**Share unread count via Inertia:**
In `HandleInertiaRequests.php`, update the `notifications.unread_count` value set to `0` in Phase 5.3:
```php
'notifications' => [
    'unread_count' => auth()->check()
        ? Notification::where('user_id', auth()->id())->where('is_read', false)->count()
        : 0,
]
```

**Update nav component:**
In `resources/js/components/app-header.tsx` (or sidebar), add a bell icon that reads `notifications.unread_count` from Inertia shared props and renders a badge.

**Inertia pages:**
- `resources/js/pages/notifications/index.tsx` — list of notifications with read/unread indicator, timestamp, message, click-to-navigate. "Mark all as read" and "Clear all" buttons.

**Acceptance:** Create a training as GB. Log in as Teacher — confirm notification badge shows 1. Click it — see the notification. Click the notification — confirm redirect to training detail. Mark as read — confirm badge disappears.

---

## Phase 12 — Training History & Export (M07) ✅ DONE

**Goal:** All users can view their personal training history. Guru Besar can view any teacher's history. Export to PDF works.

### Files to create

**Controller:** `app/Http/Controllers/TrainingHistoryController.php`
- `index()` — current user's approved applications with related training + attendance + submission/quiz status
- `show(Training $training)` — full detail for one training entry
- `export()` — generates PDF via DomPDF, streams download
- `teacherHistory(User $teacher)` — GB only: same as `index()` for a specific teacher
- `developmentOverview()` — GB: aggregated stats (total trainings, participation count, LADAP hours per teacher)

**Blade PDF template:** `resources/views/pdf/training-history.blade.php`
- User name, role, export date
- Table of trainings: title, date, location, attendance status, task status, quiz score
- Total LADAP hours accumulated (with disclaimer: "EduTrain records only")
- Institution logo placeholder, footer with system name

**Routes — split across role groups:**
```
// All authenticated
GET    /my-history                    → index
GET    /my-history/{training}         → show
GET    /my-history/export             → export

// Guru Besar only
GET    /teachers/{user}/history       → teacherHistory
GET    /reports/development-overview  → developmentOverview
```

**Role check in `teacherHistory()`:** Verify the target `$user` has the `teacher` role before returning their history. Use `abort_unless($user->isTeacher(), 404)` — a 404 is better than a 403 here because exposing that outsider profiles exist via a 403 leaks information.

**Inertia pages:**
- `resources/js/pages/history/index.tsx` — table/list of completed trainings, filter by date/category, export button
- `resources/js/pages/history/show.tsx` — full detail of one training attendance, task grade, quiz score, feedback submitted
- `resources/js/pages/reports/development-overview.tsx` — GB: table of all teachers with LADAP hours, participation count, filter by department

**Acceptance:** Log in as Teacher who attended the seeded completed training. View history — confirm training appears with correct statuses. Click export — confirm PDF downloads with the correct data and disclaimer.

---

## Phase 13 — Feedback & Reporting (M09) ✅ DONE

**Goal:** Participants submit post-training feedback. Guru Besar views summaries, generates attendance letters and summary reports.

### Files to create

**Controller:** `app/Http/Controllers/FeedbackController.php`
- `create(Training $training)` — participant: eligibility check (attended + not yet submitted), show form
- `store(Training $training)` — participant: save feedback record
- `summary(Training $training)` — GB: aggregate feedback stats

**Service addition — `TrainingService.php`:**
- `markCompleted(Training $training): void` — sets status `completed`, dispatches `FeedbackRequested` event (which notifies all attendees)

**Controller:** `app/Http/Controllers/ReportController.php`
- `attendanceLetter(Training $training)` — GB: generate PDF letters for all `present` participants, package as ZIP
- `summaryReport()` — GB: generate summary PDF/CSV for a date range

**Blade PDF templates:**
- `resources/views/pdf/attendance-letter.blade.php` — participant name, training title, date, location, duration, official signature placeholder, institution letterhead
- `resources/views/pdf/training-summary.blade.php` — training title, total enrolled, total attended, task completion rate, average feedback rating

**Routes — split across role groups:**
```
// Participants only
GET    /trainings/{training}/feedback          → create
POST   /trainings/{training}/feedback          → store

// Guru Besar only
GET    /trainings/{training}/feedback/summary  → summary
POST   /trainings/{training}/complete          → markCompleted
GET    /trainings/{training}/letters           → attendanceLetter
```

> **Note:** The training summary report (`GET /reports/summary`) was removed post-implementation — route, controller method, nav link, and page file all deleted.

**Feedback eligibility check in `create()` and `store()`:** Before showing the form, check two things that are **not** covered by the role middleware:
1. The current user has an `attendance_record` with `status = present` for this training
2. No existing `feedback` row for `(user_id, training_id)`

Use `abort_if()` with a 403 for each. The role middleware only confirms the user is a participant — these are business-rule guards beyond role.

**Inertia pages:**
- `resources/js/pages/feedback/create.tsx` — star rating component (1–5 clickable stars), optional comment textarea, submit button
- `resources/js/pages/feedback/summary.tsx` — average rating displayed as stars, bar chart of rating distribution (use a simple inline SVG or a small chart — no external chart library needed), comments list
- `resources/js/pages/reports/summary.tsx` — filter form (date range, training, department), results table, export button

**Add "Mark as Completed" button** to the training detail page (GB only, shown when training date has passed and status is `open`/`closed`).

**Acceptance:** Mark seeded training as completed. Log in as Teacher who attended — confirm feedback notification received. Submit feedback with 4 stars. Log in as GB — confirm feedback summary shows average 4.0. Generate attendance letter — confirm PDF downloads.

---

## Phase 14 — Dashboards (All Roles) ✅ DONE

**Goal:** All three dashboards show real data from the database. This phase fills in the placeholder pages created in Phase 5.

### Guru Besar Dashboard

**Controller update (`GuruBesarDashboardController`)** — pass to Inertia:
- `active_trainings` — count of `status = open`
- `pending_applications` — count of pending applications
- `completed_trainings_this_month` — count
- `total_teachers` — count
- `recent_applications` — latest 5 pending applications with applicant name + training title
- `upcoming_trainings` — next 3 open trainings by date
- `teacher_attendance` — per-teacher stats: total approved trainings, sessions attended, attendance rate (sorted by rate descending)

**Page update (`dashboard/guru-besar.tsx`):**
- Stat cards: Active Trainings, Pending Applications, Teachers, Completed This Month
- Two-column row: Recent Pending Applications + Upcoming Trainings (upcoming was previously fetched but not rendered)
- Teacher Attendance Statistics table: name, email, trainings enrolled, sessions attended / total, colour-coded rate badge (green ≥ 80 %, amber ≥ 50 %, red < 50 %)

### Teacher Dashboard

**Controller update (`TeacherDashboardController`)** — pass:
- `upcoming_trainings` — approved applications for future trainings
- `pending_applications` — count of pending applications they submitted
- `pending_tasks` — tasks they haven't submitted yet (from approved trainings)
- `pending_quizzes` — quizzes not yet attempted
- `total_ladap_hours` — sum of `duration_hours` for attended (present) trainings

**Page update (`dashboard/teacher.tsx`):**
- LADAP hours counter (prominent)
- Upcoming training cards
- Pending tasks/quizzes list
- Application status summary

### Outsider Dashboard

**Controller update (`OutsiderDashboardController`)** — pass:
- `registered_trainings` — approved applications
- `training_history` — past attended trainings
- `pending_tasks` — unsubmitted tasks

**Page update (`dashboard/outsider.tsx`):**
- Registered trainings list
- Training history summary
- Pending tasks list

**Acceptance:** Log in as each role. Confirm all dashboard numbers reflect real seeded + created data. Create a new training as GB — confirm Active Trainings count increments on dashboard.

---

## Phase 15 — Scheduled Jobs ✅ DONE

**Goal:** Automated system actions run on a schedule without manual intervention.

### Jobs and commands to create

**`app/Jobs/AutoMarkAbsentJob.php`** (created in Phase 8 — verify it is queued correctly)
- Dispatched with delay when a QR session is generated
- No cron schedule — it fires at the precise `expires_at` timestamp

**`app/Console/Commands/CloseExpiredTrainings.php`**
- Finds trainings where `status = open` AND (`registration_deadline < today` OR `approved_count >= capacity`)
- Updates status to `closed`
- Notifies pending applicants

**`app/Console/Commands/SendDeadlineReminders.php`**
- Finds approved participants with upcoming trainings (1 day away)
- Finds participants with task deadlines tomorrow
- Creates `Notification` records for each

**`app/Console/Commands/SyncStaffCommand.php`** (`edutrain:sync-staff`)
- Phase 1: no-op, exits 0 with message "External integration not configured."

**Register in `routes/console.php`:**
```php
Schedule::command('edutrain:close-expired-trainings')->dailyAt('00:01');
Schedule::command('edutrain:send-deadline-reminders')->dailyAt('07:00');
```

**Run scheduler locally:**
```bash
php artisan schedule:work
```

**Acceptance:** Set a training's `registration_deadline` to yesterday via tinker. Run `php artisan edutrain:close-expired-trainings` manually — confirm status updates to `closed`. Create a training for tomorrow — run `php artisan edutrain:send-deadline-reminders` — confirm reminder notification created.

---

## Phase 16 — PDF Export & File Storage ✅ DONE

**Goal:** All PDF exports work. File uploads for task submissions are stored and downloadable.

### PDF exports (using `barryvdh/laravel-dompdf`)

Verify these are complete from Phases 12 and 13:
- Training history PDF (`resources/views/pdf/training-history.blade.php`)
- Attendance letter PDF (`resources/views/pdf/attendance-letter.blade.php`)
- Training summary report PDF (`resources/views/pdf/training-summary.blade.php`)

**Attendance letter ZIP packaging** (in `ReportController::attendanceLetter()`):
- Generate one PDF per present participant using a loop
- Use `ZipArchive` (PHP built-in) to package them
- Stream ZIP as download response

### File uploads (task submissions)

- Use `Storage::disk('public')` — files stored in `storage/app/public/submissions/`
- Run `php artisan storage:link` to create the `public/storage` symlink
- File paths stored as relative paths in `task_submissions.file_path`
- Download route: `GET /submissions/{submission}/download` — streams file with original name

**Acceptance:** Submit a task with a PDF file. Click download from the GB grade view — confirm file downloads with correct content. Generate an attendance letter for a training with 2 present participants — confirm ZIP downloads with 2 PDF files inside.

---

## Phase 17 — Settings & Profile (M01-F05)

**Goal:** All users can update their name and password. Teachers/Principals can update department and position.

The existing `app/Http/Controllers/Settings/ProfileController.php` and `PasswordController.php` already handle basic name/email/password updates. Extend them:

**Modify `ProfileController.php`:**
- After updating the `User` record, also update the linked `Teacher`, `Principal`, or `Outsider` profile record with role-specific fields (department, position, organization, contact_no)

**Modify `app/Http/Requests/Settings/ProfileUpdateRequest.php`:**
- Add conditional validation rules for role-specific fields

**Modify `resources/js/pages/settings/profile.tsx`:**
- Show additional fields based on `auth.user.role` (use the shared Inertia prop)
- Teacher/Principal: Department, Position fields
- Outsider: Organization, Contact No fields

**Acceptance:** Log in as Teacher. Go to Settings → Profile. Update department. Confirm the change persists in the database and is reflected on the Teacher dashboard.

---

## Phase 18 — Final Demo Polish

**Goal:** The system is demo-ready. All flows are connected end to end. Seed data covers every demo scenario.

### Demo seed data to add to `DatabaseSeeder.php`

Add a `DemoSeeder.php` that creates a complete end-to-end demo state:

| Data | Purpose |
|---|---|
| 1 completed training with 2 teachers attending | Demo history, feedback, attendance letter |
| 1 open training with 1 pending + 1 approved application | Demo approval workflow |
| 1 task on the open training with 1 submission | Demo task grading |
| 1 quiz on the open training with 1 attempt | Demo quiz scoring |
| Feedback submitted by Teacher1 on completed training | Demo feedback summary |
| 3 unread notifications for each user | Demo notification bell |

```bash
php artisan migrate:fresh --seed
```

### Pre-demo checklist

Run through each flow manually before presenting:

- [ ] Outsider self-registers → receives welcome notification → lands on Outsider dashboard
- [ ] Teacher logs in → sees LADAP hours + upcoming training on dashboard
- [ ] Guru Besar logs in → sees pending applications badge on dashboard
- [ ] GB creates a new training → Teachers receive "New training available" notification
- [ ] Teacher applies for training → GB sees notification + pending application
- [ ] GB approves application → Teacher receives approval notification
- [ ] GB generates QR for attendance → Teacher enters token → status shows Present
- [ ] Window expires → auto-absent job fires → absent teacher marked automatically
- [ ] GB creates task → Teacher submits file → GB grades → Teacher sees grade
- [ ] GB creates quiz → Teacher attempts → score shown immediately after submit
- [ ] GB marks training as completed → Teachers receive feedback prompt
- [ ] Teacher submits feedback → GB views summary with average rating
- [ ] GB generates attendance letter → ZIP with PDF downloads
- [ ] Teacher exports training history → PDF downloads with LADAP hours + disclaimer
- [ ] GB views development overview → sees all teachers' LADAP hours

---

## Post-Phase UI Improvements

These changes were made after all 18 phases were complete.

### Auth pages — system theme
- `auth-simple-layout.tsx`: background changed to `bg-gray-50`, content wrapped in a white `rounded-xl` card, EduTrain logo (`AppLogo`) replaces the generic SVG icon
- `login.tsx` and `register.tsx`: submit button changed to `bg-violet-600`, "Forgot password?" / "Sign up" / "Log in" links changed to `text-violet-600`

### Guru Besar dashboard enhancements
- Teacher Attendance Statistics table added (per-teacher rate with colour badge)
- Upcoming Trainings section now rendered (data was already fetched, just not displayed)
- Layout refactored to two-column grid for the middle row

### Training summary report removed
- `GET /reports/summary` route, `ReportController::summaryReport()`, "Summary" nav link, and `resources/js/pages/reports/summary.tsx` all deleted at user request

### Teacher-facing UI polish
- **Attendance** (`manage.tsx`): active sessions now display a violet QR code (200 × 200, `qrcode.react`) encoding the full mark-attendance URL; copy-token kept as fallback
- **Attendance** (`mark.tsx`): reads `?token=` query param on mount and pre-fills the input — scanning the QR code is zero-touch for the participant
- **Task submit** (`submit.tsx`): status badge (Not Submitted / Submitted / Graded) at top of card; file input replaced with drag-and-drop zone
- **Quiz attempt** (`attempt.tsx`): progress bar + "X of Y answered" counter; green "✓ Answered" chip per question card; timer badge pulses when < 60 s remain

---

## Phase Summary

| Phase | What it delivers | Estimated effort | Status |
|---|---|---|---|
| Pre-flight | Working scaffold + packages installed | 1 hour | ✅ |
| 1 — Migrations | All 19 tables created | 3 hours | ✅ |
| 2 — Models | All Eloquent models + relationships | 2 hours | ✅ |
| 3 — RBAC + Seeders | Loginnable with 3 roles + demo data | 2 hours | ✅ |
| 4 — Integration Seam | Teacher/Principal repository abstraction | 2 hours | ✅ |
| 5 — Auth Wiring | Role redirects + route groups enforced | 2 hours | ✅ |
| 6 — Training Module | Full CRUD for training programs | 4 hours | ✅ |
| 7 — Applications | Apply, approve, reject, assign flows | 4 hours | ✅ |
| 8 — Attendance | QR generate, mark, auto-absent | 5 hours | ✅ |
| 9 — Task Submission | Create task, submit, grade | 4 hours | ✅ |
| 10 — Quiz | Create quiz, attempt, auto-grade | 6 hours | ✅ |
| 11 — Notifications | All events + listeners + bell UI | 4 hours | ✅ |
| 12 — History + Export | Personal history + PDF download | 3 hours | ✅ |
| 13 — Feedback + Reports | Feedback form + summary + letters | 4 hours | ✅ |
| 14 — Dashboards | Real data on all 3 dashboards | 3 hours | ✅ |
| 15 — Scheduled Jobs | Auto-close, reminders, sync stub | 2 hours | ✅ |
| 16 — File Storage + ZIP | File upload/download + letter ZIP | 2 hours | ✅ |
| 17 — Settings/Profile | Role-specific profile update | 1 hour | ✅ |
| 18 — Demo Polish | Full seed + end-to-end smoke test | 2 hours | ✅ |
| **Total** | | **~56 hours** | **18 / 18 done** |

---

## Key Conventions to Follow Throughout

- **No business logic in controllers** — controllers call services or models, return Inertia responses
- **One FormRequest per controller action that accepts input** — all validation lives in the request class
- **Queued listeners for all notifications** — never block an HTTP request to write notification records
- **Repository interfaces only for Teacher and Principal** — all other entities use direct Eloquent
- **All Inertia pages use existing shadcn/ui components** — do not introduce new UI libraries
- **All file uploads go to `Storage::disk('public')`** — never store files in `public/` directly
- **Audit log writes for:** application approve/reject, attendance marking, task grading, training cancel/complete
- **`external_id` column stays null** for all records until Phase 2 integration

---

*Document prepared for EduTrain System — Phase 1 Local Demo Build.*
*PSM Project BITU 3973 — Izzhilmy Bin Shamsul Bahri (B032420045), UTeM FTMK, Session 2025/2026.*
