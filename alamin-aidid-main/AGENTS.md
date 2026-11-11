# AGENTS.md

## Project overview

This is a standalone Laravel trainer-management project developed as one
subsystem of a five-person Final Year Project for Al Amin Edu Oasis.

The project currently runs using its own local MySQL database named
`trainer_db`. It is not yet connected to the other team members' applications
or databases.

Some database tables are local reference replicas created so this standalone
project can simulate data that will eventually come from other subsystems.
Their current table names, columns, data types, keys, relationships, and status
values are temporary and are not the confirmed final shared database contract.

The actual repository and current database are the primary evidence for how
the system presently works. Draft reports and documentation may be incomplete
or outdated.

## Current technical environment

- Laravel 13
- PHP 8.5
- MySQL 8
- Blade templates
- Tailwind CSS 4
- Vite 8
- Windows and PowerShell
- Use `npm.cmd` and `npx.cmd` instead of `npm` and `npx` in PowerShell
- Current development branch: `system-improvement`
- Protected baseline branch: `main`
- Baseline commit: `bb5e988`

## Current improvement objective

Improve the standalone system's internal quality, including:

- Code organization and consistency
- Paths, folders, and file structure
- Bugs and incomplete functionality
- Validation and authorization
- Error handling
- Security
- Internal database and migration synchronization
- Models, controllers, services, requests, policies, routes, and views
- UI consistency, usability, accessibility, and responsiveness
- Performance
- Tests
- Documentation

Improvements must preserve unresolved integration boundaries.

## Integration-sensitive areas

Until the client and team confirm the final shared design, do not rename,
delete, merge, standardize, or structurally redesign shared or replicated
database contracts, including:

- Table names
- Column names
- Column data types
- Primary keys
- Foreign keys
- Relationships
- Status values
- User and authentication structures
- Role names shared with other systems
- Course and session structures
- Trainer master records
- Teacher and participant records
- Enrolment and attendance structures
- Feedback structures
- Assessment-submission structures
- Applicant and new-teacher structures

These areas may be inspected, documented, tested, and isolated, but structural
changes require explicit approval.

## Required working behaviour

Before making a substantial change:

1. Read the relevant project files.
2. Explain the current behaviour using file-based evidence.
3. Identify uncertainties and integration-sensitive dependencies.
4. Propose a focused plan.
5. Wait for approval when the task is broad, risky, destructive, or affects
   shared contracts.

Work feature by feature rather than performing uncontrolled repository-wide
cleanup.

After making changes, report:

- Files created, modified, moved, or deleted
- Commands run
- Tests and checks performed
- Test results
- Database changes
- Remaining limitations or risks
- Git diff summary

## Safety rules

- Never expose `.env` values, passwords, tokens, keys, or private data.
- Never commit `.env`, database dumps, generated dependencies, logs, or local
  uploaded files.
- Never run destructive database commands without separate explicit approval.
- Never run `migrate:fresh`, `db:wipe`, destructive resets, drops, truncates,
  broad deletes, or database restores against `trainer_db`.
- Never drop or overwrite `trainer_db`.
- Do not run migrations against the main database without explicit approval
  and a verified backup.
- Do not install, remove, or update Composer or npm packages without approval.
- Do not delete files merely because they appear unused.
- Confirm usage through routes, controllers, services, providers, events,
  listeners, jobs, policies, Blade includes, JavaScript imports,
  configuration, tests, and dynamic references.
- Preserve existing behaviour unless a change is intentionally approved.
- Keep changes focused and reviewable.
- Do not push code or configure a remote repository without approval.
- Do not work directly on `main`.

## Database rules

The current MySQL database, migrations, models, code, seeders, factories, and
tests must eventually become synchronized and reproducible.

Codex may perform read-only schema audits and propose corrective migrations.
Temporary replicated tables may be made reproducible in their current form,
but they must remain clearly identified as unconfirmed external replicas.

Database changes should first be tested against a separate empty or temporary
database whenever practical.

## Testing expectations

Run the smallest relevant checks after each focused change.

Depending on the task, checks may include:

- Laravel tests
- Targeted feature or unit tests
- PHP syntax checks
- Laravel Pint
- Route inspection
- Frontend builds
- Manual workflow verification

Do not claim that a change works unless an appropriate check was actually run.

## Git expectations

- Keep `main` as the protected original baseline.
- Perform improvement work on `system-improvement` or a later focused branch.
- Review `git status` and `git diff` before commits.
- Create focused checkpoints after verified work.
- Do not mix unrelated changes in one task or commit.
