# Al Amin Edu Oasis Trainer Management System

This repository contains the Trainer and learning-delivery subsystem for Al
Amin Edu Oasis. It is a Laravel application for Trainer and administrator
workflows including training delivery, LMS authoring, assessments, attendance,
learner tracking, feedback, teaching plans, proposals, analytics, and profile
documents.

The application targets the canonical shared MySQL schema. It must not be
pointed back to the former standalone `trainer_db` contract or supplied with
legacy compatibility tables.

## Technology

- PHP 8.4 or newer and Laravel 13
- MySQL 8
- Node.js with Vite 8 and Tailwind CSS 4
- Blade templates and vanilla JavaScript modules

## Project layout

- `app/` — controllers, requests, services, and canonical integration support
- `resources/views/` — Blade application UI and page-level styles
- `resources/js/` — frontend source modules
- `public/` — web entry point and public static assets only
- `routes/` — application routes
- `database/migrations/` — active production migrations only
- `database/seeders/` — the intentionally empty production seeder
- `tests/` — PHP and frontend automated tests
- `backend-integration/` — canonical schema, deployment validation, ownership,
  and integration-boundary documentation

## Local setup

1. Import the owner-approved shared schema into a disposable or approved MySQL
   database. Never import over a populated shared database without a verified
   backup and explicit deployment approval.
2. Copy `.env.example` to `.env`, generate an application key, and configure
   Aidid to use that imported database.
3. Install existing PHP and frontend dependencies with `composer install` and
   `npm.cmd install`.
4. Build frontend assets with `npm.cmd run build`.
5. Link public storage when uploaded files are required: `php artisan
   storage:link`.

The repository contains no business-data seed. External owners provision
their own master and shared data.

## Shared database contract

Start with [`backend-integration/README.md`](backend-integration/README.md).
The owner-approved V4 shared schema is the integration authority and must be
imported before Aidid is configured through `.env`. A supplied local copy is
normally kept at
`backend-integration/incoming/main_database_CANONICAL_PRE_INSERT_V4.sql`; the
incoming directory is intentionally ignored and must not be force-added.

V4 business identities use their canonical `VARCHAR` identifiers. The former
`_combined_id` approach is obsolete and unsupported. In the latest owner
contract, `guru_new` retains `created_at` and `updated_at` but has neither
`service_date` nor `remember_token`.

The tracked structure-only Aidid integration reference is
[`backend-integration/deployment/trainer-canonical-schema.sql`](backend-integration/deployment/trainer-canonical-schema.sql),
with its ownership inventory and guarded validator in the same directory; it
does not replace the latest owner-supplied shared schema.
Archived no-op migration history is retained outside Laravel's active
migration discovery path for traceability only.

Synthetic browser fixtures, disposable runtimes, and test credentials are
local-only verification data. They are not production seeds or versioned
deployment inputs.

## Verification

```powershell
php artisan test
npm.cmd run test:frontend
npm.cmd run build
vendor\bin\pint.bat --test
git diff --check
php artisan route:list
```

PHP tests use the isolated test environment configured by `phpunit.xml`.
Runtime database verification must use an approved canonical local database,
not production data.

## Al Amin Login deployment configuration

Aidid is SSO-only. `GET /login` returns users to Al Amin Login, `POST /login`
is not registered, callback failures return to Al Amin Login, and logout
destroys only the Aidid session before returning to the portal's system
selection page. The private `aidid.env` file is a small merge block; add its
`ALAMIN_LOGIN_*` lines to Aidid's existing server `.env` without replacing
unrelated settings. The current HTTP server configuration is:

```dotenv
ALAMIN_LOGIN_URL=
ALAMIN_LOGIN_CLIENT_ID=aidid
ALAMIN_LOGIN_CLIENT_SECRET=<matching-secret-provided-privately>
ALAMIN_LOGIN_REQUIRE_HTTPS=false
```

`ALAMIN_LOGIN_URL` is the required Al Amin Login base URL, without `/systems`
or a callback path. `false` explicitly permits the current HTTP deployment,
including in production; there is no hidden production-only HTTPS check.
Tracked `.env.example` retains the secure default of `true`. If HTTPS becomes
available later, update the URL and set the value to `true` without editing
application source.

No Aidid controller, route, session mapping, dashboard destination, or Blade
template should be edited when the login domain or port changes. After an
environment update, clear and rebuild Laravel's configuration cache using the
normal deployment commands.

## Integration boundaries

Trainer owns only the eleven tables listed in
[`backend-integration/share/TRAINER_OWNED_DATA_CONTRACT.md`](backend-integration/share/TRAINER_OWNED_DATA_CONTRACT.md).
Course, session, identity, enrollment, attendance, feedback, and related shared
tables retain their documented external ownership. This subsystem does not add
Trainer-owned columns to the externally owned `course` table.
Participant material access links directly through the physical foreign key
`participant_material_access.course_id = course.courseID`.

See [`docs/SYSTEM_SCOPE.md`](docs/SYSTEM_SCOPE.md) for the concise product and
repository boundary summary.
