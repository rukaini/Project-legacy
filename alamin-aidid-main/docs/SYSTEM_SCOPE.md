# System scope and repository boundaries

The Trainer Management System is the learning-delivery subsystem of Al Amin
Edu Oasis. Trainers deliver assigned courses, manage LMS and Simple-training
content, review submissions, view attendance and learner progress, inspect
feedback, submit teaching plans and course proposals, and manage their profile
documents. Administrators monitor those workflows and review Trainer-owned
records through the application.

## Source boundaries

- Application backend code belongs in `app/`.
- Blade views and frontend source belong in `resources/`; public static assets
  belong in `public/`.
- Automated PHP and JavaScript tests belong in `tests/`.
- Active Laravel migrations contain framework tables, Trainer-owned schema,
  the approved Trainer extension to `course`, and canonical database programs.
- Canonical deployment, ownership, and historical migration evidence belongs
  in `backend-integration/`.

## Data ownership

Trainer owns the eleven business tables documented in
`backend-integration/share/TRAINER_OWNED_DATA_CONTRACT.md`. It consumes the
canonical Course, Session, Trainer, participant identity, enrollment,
attendance, feedback, QR, and assessment-submission owner contracts without
claiming ownership of those external tables.

The Trainer-owned participant material access setting uses the physical
foreign key `participant_material_access.course_id = course.courseID`.

The complete deployment inventory is
`backend-integration/deployment/CANONICAL_SCHEMA_INVENTORY.md`; the associated
SQL is structure-only and contains no application rows or credentials.

## Deliberate exclusions

This repository does not contain legacy schema compatibility, real shared
data, passwords, database dumps, local uploads, dependency directories, or
compiled frontend output as versioned source. Local ignored files must be
excluded from a ZIP handoff unless they are explicitly requested for a
controlled deployment.
