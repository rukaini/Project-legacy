# PSM Report Extraction Guide — EduTrain

---

## STUDENT INFO (fill manually)

```
Student Name     : Izzhilmy Bin Shamsul Bahri
Student ID       : B032420045
Supervisor       : Ts.Hidayah Binti Rahmalan
Programme        : Bachelor of Computer Science (Database Management) with Honours
Faculty          : Fakulti Teknologi Maklumat dan Komunikasi (FTMK)
University       : Universiti Teknikal Malaysia Melaka (UTeM)
Submission Date  : ___________________________
```

---

## CHAPTER 1 — INTRODUCTION

### 1.1 Background

**Project Name:** EduTrain — Educator Training Management System
**Organization:** Al Amin Edu Oasis
**Domain:** LADAP (Latihan Dalam Perkhidmatan) teacher in-service training management

EduTrain is a web-based LADAP training management system developed for Al Amin Edu Oasis to digitise and centralise the entire in-service teacher training lifecycle — from program creation and multi-role enrollment, through attendance recording, to automated PDF compliance reporting. The system replaces manual paper-based processes involving physical application forms, attendance sheets, and manual LADAP hour compilation, which were prone to error, data loss, and administrative delay. Five user roles — Guru Besar (school principal), Teacher, Guru Baru (new teacher applicant), Outsider, and HR — interact with the system across training registration, approval workflows, content delivery, assessment submission, and training history tracking.

EduTrain operates as a system that integrates with an external platform. Core training content — including training programmes, sessions, modules, content blocks, assessments, learning materials, trainer profiles, school records, and applicant (Guru Baru) profiles — is sourced from the external system. EduTrain manages enrollment, attendance, LADAP hours calculation, notifications, and reporting on top of this external data. Teacher and Principal records are currently stored locally but are designed via a repository abstraction layer to be replaced by live external-system synchronisation in Phase 2.

### 1.2 Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Runtime Language | PHP | ^8.2 |
| Backend Framework | Laravel | ^12.0 |
| Frontend Framework | React | ^19.0.0 |
| SPA Bridge | Inertia.js (inertiajs/inertia-laravel) | ^2.0 |
| TypeScript Support | TypeScript | ^5.7.2 |
| UI Component Library | shadcn/ui + Radix UI | various |
| Styling | Tailwind CSS | ^4.0.0 |
| RBAC | Spatie laravel-permission | ^6.25 |
| PDF Generation | barryvdh/laravel-dompdf | ^3.1 |
| QR Code | simplesoftwareio/simple-qrcode | ^0.0.4@beta |
| Route URL helper (JS) | tightenco/ziggy | ^2.4 |
| Database (local dev) | SQLite (configurable to MySQL/MariaDB) | — |
| Build Tool | Vite (laravel-vite-plugin) | ^6.0 |
| Queue Driver | Database | — |
| QR rendering (React) | qrcode.react | ^4.2.0 |

### 1.3 User Roles

| Role (enum value) | Description | Key Capabilities |
|---|---|---|
| `guru_besar` | School principal / system administrator | Create and manage training programs; approve/reject/assign teacher applications; manage attendance sessions; view school-wide development overview report; download attendance letters (ZIP PDF) |
| `teacher` | Registered school teacher | Browse and apply for training programs; mark attendance via button; access learning materials (single/multi_day); track personal LADAP hours; export training history PDF |
| `guru_baru` | New teacher applicant (Applicant entity) | Auto-enrolled in all series courses; apply for single/multi-day courses (HR approval); access learning materials (single/multi_day); submit tasks and take MCQ quizzes (series only); track attendance |
| `outsider` | External participant | Self-registers on the platform; apply for single or multi-day courses (HR approval); mark attendance via button; access learning materials; track enrollment history |
| `hr` | Human Resources staff | Approve/reject outsider and guru baru applications; view all schools, teachers, outsiders, and guru baru; receive notifications when new applications are submitted |

### 1.4 Objectives

EduTrain was developed with the following objectives:

1. To design and develop a web-based LADAP training management system for Al Amin Edu Oasis that digitises training program creation, enrollment, and lifecycle management.
2. To implement a multi-role enrollment and approval workflow supporting Teacher (approved by Guru Besar), Guru Baru, and Outsider (approved by HR) participant types with capacity validation.
3. To develop a button-based attendance system that records participant presence per session and calculates accumulated LADAP hours with organiser-based multipliers.
4. To provide a structured training content delivery platform supporting PDF learning materials, YouTube videos, MCQ quizzes, and file task submissions per training category and role.
5. To generate automated PDF compliance reports including training history, attendance letters, and training summary reports.
6. To integrate with an external system that provides training programme data, session schedules, assessments, school records, and Guru Baru applicant profiles.

### 1.5 Scope

#### 1.5.1 User Scope

| Role | Can Access | Cannot Access |
|---|---|---|
| `guru_besar` | `/dashboard/principal`, all training CRUD, teacher application approve/reject/assign, attendance manage and report, teacher history, development overview PDF, attendance letters ZIP | Attendance marking as participant, assessment/quiz submission, outsider/guru baru application approval |
| `teacher` | `/dashboard/teacher`, training index/show, personal application list, attendance mark button, learning materials (single/multi_day), personal training history and PDF export, notifications | Application approval, training creation/edit, attendance reports, assessment submission |
| `guru_baru` | `/dashboard/guru-baru`, series courses (auto-enrolled), single/multi-day applications (HR approved), attendance mark button, learning materials (single/multi_day), tasks & quizzes (series only), content block interaction logging, personal history | Training creation/edit, application approval, school-level reports |
| `outsider` | `/dashboard/outsider`, training index/show (single/multi-day only), outsider application store (HR approved), attendance mark button, learning materials, personal enrollment history | Series courses, assessment/quiz submission, application approval, reports |
| `hr` | `/hr/dashboard`, outsider application approve/reject, guru baru application approve/reject, schools browser, teacher list per school, outsider list, guru baru list, notifications | Training creation/edit, attendance management, teacher application approval, PDF reports |

#### 1.5.2 Module Scope

| Module Name | Controller(s) | Key Actions |
|---|---|---|
| Dashboard | `GuruBesarDashboardController`, `TeacherDashboardController`, `GuruBaruDashboardController`, `OutsiderDashboardController`, `HrDashboardController` | Role-specific KPI cards, pending application counts, upcoming trainings, teacher attendance overview |
| Training Management | `TrainingController` | index (with search/filter), create, store, show (with content/assessment/feedback), edit, update, cancel, complete |
| Application Management (Teacher) | `ApplicationController` | Apply (teacher), approve/reject by Guru Besar, assign teachers directly, view my-applications |
| Application Management (Outsider & Guru Baru) | `HrApplicationController`, `OutsiderApplicationController`, `GuruBaruApplicationController` | Apply (outsider/guru baru), approve/reject by HR, view my-applications |
| HR User Management | `HrUserController` | Schools list with teacher counts, teacher list per school (with search), outsider list, guru baru list |
| Attendance | `AttendanceController` | Generate/manage sessions, mark attendance via button (multi-role), attendance matrix report |
| Training History | `TrainingHistoryController` | Personal history index/show, export PDF, Guru Besar view teacher history, development overview, development overview PDF |
| Assessment | `AssessmentController` | Show quiz/task, submit quiz (auto-graded MCQ), submit task (file upload) — Guru Baru, series courses only |
| Learning Materials | `LearningMaterialController` | Stream or download uploaded files — Teacher, Outsider, Guru Baru for single/multi_day trainings |
| Content Interaction | `TrainingContentInteractionController` | Log open/close/download/read/submit/pause_video events per content block |
| Notifications | `NotificationController` | Index, mark-read, clear all |
| Feedback | `FeedbackController` | Submit per-question feedback response per course |
| Reports | `ReportController` | Attendance letter ZIP download (one PDF per participant) |

### 1.6 Expected Outcome

Upon completion, EduTrain delivers a fully functional web-based LADAP training management system accessible via standard web browsers. Key expected outcomes:

- Guru Besar can manage all training programs from a single dashboard with real-time enrollment visibility.
- Teachers independently apply, track LADAP hours, and complete learning modules digitally.
- QR attendance eliminates manual attendance sheets.
- Automated PDF reports replace manual compilation of training records.

---

## CHAPTER 2 — LITERATURE REVIEW & PROJECT METHODOLOGY

### 2.1 Literature Review

*(To be written manually by student — compare 3–5 existing systems such as HRMIS, Google Classroom, TalentLMS, Moodle. Highlight gaps EduTrain addresses.)*

### 2.2 Project Methodology

EduTrain was developed following the **Database Life Cycle (DBLC)** model combined with an **Agile** iterative approach, suitable for a database-driven web application with multi-role requirements.

### 2.3 Technology Review

| Package | Purpose | Reason for Selection |
|---|---|---|
| `laravel/framework` ^12.0 | Backend MVC framework providing routing, ORM (Eloquent), queuing, event system | Industry-standard PHP framework with full-stack tooling, active LTS support, and built-in database migration system |
| `inertiajs/inertia-laravel` ^2.0 | SPA bridge — renders React pages server-side via Laravel controllers without a separate API | Eliminates need for REST API while keeping React frontend; shared props pattern avoids duplicate auth logic |
| `react` ^19.0.0 + `@inertiajs/react` ^2.0.0 | Interactive component-based UI | Modern declarative UI with TypeScript support; Inertia integration means no separate state management for server data |
| `tailwindcss` ^4.0.0 + shadcn/ui + Radix UI | Utility-first CSS styling and accessible headless components | Rapid UI development with consistent design system; Radix provides WCAG-compliant primitives |
| `spatie/laravel-permission` ^6.25 | Role-Based Access Control (RBAC) | Proven Spatie package integrates with Laravel middleware; manages roles via pivot table, no column needed on `users` |
| `barryvdh/laravel-dompdf` ^3.1 | PDF generation from Blade templates | Generates training history PDFs, attendance letters, and development overview reports without external service |
| `simplesoftwareio/simple-qrcode` ^0.0.4@beta | Server-side QR code token generation for attendance sessions | Lightweight QR library that generates unique tokens stored per `course_session.qr_code` |
| `tightenco/ziggy` ^2.4 | Exports Laravel named routes to JavaScript | Allows `route()` calls in React pages exactly matching backend route names, eliminating hardcoded URLs |
| `qrcode.react` ^4.2.0 | React component to render QR code visually | Displays QR code for attendance session directly in the Guru Besar manage-attendance page |
| `pestphp/pest` ^3.8 | Testing framework | Expressive PHP test syntax for unit and feature testing |
| `fakerphp/faker` ^1.23 | Fake data generation in seeders | Creates realistic seeder data for demo accounts and trainings |

### 2.4 Schedule and Milestones

*(To be filled manually by student with Gantt chart and milestone table.)*

---

## CHAPTER 3 — ANALYSIS

### 3.1 Problem Analysis

Current manual LADAP management at Al Amin Edu Oasis involves physical application forms, paper attendance sheets, and manual LADAP hour compilation — leading to errors, data loss, and administrative delay.

### 3.2 Proposed System

EduTrain replaces all manual processes with a fully digital workflow covering: training creation → application → approval → QR attendance → content delivery → assessment → PDF reporting.

### 3.3 Functional Requirements

| FR ID | Requirement | Description | Implemented In |
|---|---|---|---|
| FR01 | Training Creation | Guru Besar can create a training program specifying name, category (single/series/multi_day), capacity, price, mode, organiser, and WhatsApp group URL | `StoreTrainingRequest`, `TrainingController::store`, `Training::create` |
| FR02 | Training Update | Guru Besar can edit training details; location/organiser changes trigger a notification to enrolled teachers | `UpdateTrainingRequest`, `TrainingController::update`, `TrainingUpdated` event |
| FR03 | Training Cancellation | Guru Besar can cancel a training (status → ongoing); enrolled teachers and pending applicants are notified | `TrainingController::cancel`, `TrainingCancelled` event, `SendTrainingCancelledNotification` |
| FR04 | Training Completion | Guru Besar can mark a training as complete; action is audited | `TrainingController::complete`, `TrainingService::markCompleted`, `AuditLog::create` |
| FR05 | Teacher Self-Application | Teacher can apply for an upcoming training before the first session date; checks capacity, not already applied, and training status | `ApplicationService::apply`, `StoreApplicationRequest`, `Application::create` |
| FR06 | Outsider Application | Outsider can apply for single or multi-day upcoming courses before the first session date; checks capacity; triggers HR notification | `OutsiderApplicationController::store`, `EnrollOutsider::create`, `OutsiderApplicationSubmitted` event |
| FR07 | Guru Baru Application | Guru Baru auto-enrolled in all series courses; can apply for single/multi-day courses with capacity check; triggers HR notification | `GuruBaruApplicationController::store`, `EnrollGuruBaru::create`, `GuruBaruApplicationSubmitted` event |
| FR08 | Teacher Application Approval/Rejection | Guru Besar can approve or reject a teacher application; rejection stores optional reason; applicant notified | `ApplicationService::approve`, `ApplicationService::reject`, `ApplicationApproved`/`ApplicationRejected` events |
| FR09 | Outsider/Guru Baru Application Approval/Rejection | HR can approve or reject outsider and guru baru applications with optional reason; applicant notified | `HrApplicationController::approveOutsider`, `rejectOutsider`, `approveGuruBaru`, `rejectGuruBaru` |
| FR10 | Direct Teacher Assignment | Guru Besar can assign multiple teachers directly to a training (bypassing application); capacity auto-incremented per assignment | `ApplicationService::assignDirect`, `AssignTeachersRequest`, `TeacherAssigned` event |
| FR11 | QR Attendance Marking | Approved participants scan a QR token (40-char string); system validates token, checks session time window, prevents same-day double scan, calculates LADAP hours with organiser multiplier | `AttendanceService::markAttendance`, `MarkAttendanceRequest` |
| FR12 | Attendance Management | Guru Besar views all sessions for a training with present-count per session | `AttendanceController::manage` |
| FR13 | Attendance Report | Guru Besar views a participant-vs-session presence matrix for a training | `AttendanceController::report` |
| FR14 | Personal Training History | Any authenticated user views their approved training history with attendance rates and LADAP hours | `TrainingHistoryController::index`, `TrainingHistoryController::show` |
| FR15 | Training History PDF Export | Teacher can export personal LADAP history as a PDF download | `TrainingHistoryController::export`, `Pdf::loadView('pdf.training-history')` |
| FR16 | Development Overview Report | Guru Besar views and downloads PDF of all school teachers' LADAP summary | `TrainingHistoryController::developmentOverview`, `developmentOverviewPdf` |
| FR17 | Attendance Letter PDF ZIP | Guru Besar downloads a ZIP of individual attendance letters for all present participants in a training | `ReportController::attendanceLetter`, `ZipArchive` |
| FR18 | Assessment (Quiz) Submission | Guru Baru enrolled in **series** trainings submits MCQ/true-false quiz; auto-graded; short-answer questions remain in 'submitted' status for manual review | `AssessmentController::submitQuiz`, `AssessmentSubmission::create` |
| FR19 | Assessment (Task) Submission | Guru Baru enrolled in **series** trainings uploads a file or submits text for a task assessment; stored to `public` disk under `submissions/` | `AssessmentController::submitTask`, `Storage::disk('public')` |
| FR20 | Content Block Interaction Logging | Guru Baru's interactions (open/close/download/read/submit/pause_video) with content blocks are logged with timestamps and time-on-block | `TrainingContentInteractionController::log`, `TrainingContentInteractionLog` |
| FR21 | Feedback Submission | Any authenticated user can submit per-question feedback responses once per course; stores rating, comment, and individual answers | `FeedbackController::submit`, `FeedbackResponse::create` |
| FR22 | Notification Delivery | System sends queued notifications: training created → all teachers/outsiders/guru baru; teacher applies → Guru Besar; outsider/guru baru applies → HR; approved/rejected → applicant; teacher assigned → teacher; training updated/cancelled → all enrolled participants | All `Send*Notification` listeners implementing `ShouldQueue` |
| FR26 | HR Application Dashboard | HR views all outsider and guru baru applications with tab/status filtering; approves or rejects with optional reason | `HrApplicationController::index`, `HrDashboardController::index` |
| FR27 | HR User Management | HR browses schools (with teacher count and Guru Besar name), drills into a school to view its teachers, and views outsider/guru baru lists with search | `HrUserController::teachers`, `HrUserController::outsiders`, `HrUserController::guruBaru` |
| FR28 | Outsider Self-Registration | External participants register at `/register`; assigned `outsider` role automatically; minimal profile created (name from registration); remaining profile fields completed via Settings | `RegisteredUserController::store`, `Outsider::create` |
| FR23 | Notification Management | Authenticated users can view, mark as read, and clear their notifications | `NotificationController::index`, `markRead`, `clear` |
| FR24 | Learning Material Streaming | Teachers, Outsiders, and Guru Baru enrolled in single or multi_day trainings can view or download learning materials via auth-guarded route; `?dl=1` forces download | `LearningMaterialController::show` |
| FR25 | Training Status Sync | Training status automatically transitions between upcoming/ongoing/complete based on session dates on each index page load | `TrainingService::syncStatuses` |

### 3.4 Non-Functional Requirements

| NFR ID | Requirement | Description |
|--------|------------|-------------|
| NFR01 | Availability | System accessible 24/7 via web browser |
| NFR02 | Security | RBAC enforced at route middleware, service layer, and React UI |
| NFR03 | Usability | Role-specific dashboards with minimal training required |
| NFR04 | Performance | Pages load within 3 seconds under normal conditions |
| NFR05 | Data Integrity | Capacity validation prevents over-enrollment across all enrollment types |
| NFR06 | Maintainability | MVC separation with dedicated service layer; no business logic in controllers |

### 3.5 Data Flow Diagram

| Process | Actor/Entity | Input Data | Output Data | Data Store Affected |
|---|---|---|---|---|
| Create Training | Guru Besar | course_name, course_category, capacity, price, course_mod, organiser_name | Training record; notification to all teachers, outsiders, and guru baru | `course`, `notification_ladap` |
| Submit Application (Teacher) | Teacher | user session, training ID | Application record; notification to Guru Besar | `enroll_teacher`, `notification_ladap`, `audit_logs` |
| Submit Application (Outsider) | Outsider | user session, training ID | EnrollOutsider record; notification to all HR users | `enroll_outsider`, `notification_ladap` |
| Submit Application (Guru Baru) | Guru Baru | user session, training ID | EnrollGuruBaru record; notification to all HR users | `enroll_guru_baru`, `notification_ladap` |
| Approve Application (Teacher) | Guru Besar | application route key (teacherID_course_id) | Updated status = approved; notification to teacher | `enroll_teacher`, `notification_ladap`, `audit_logs` |
| Approve Application (Outsider/Guru Baru) | HR | outsider_id+course_id or enrollment_id | Updated status = approved; notification to applicant | `enroll_outsider` / `enroll_guru_baru`, `notification_ladap` |
| Mark Attendance | Teacher / Outsider / Guru Baru | Training ID, session ID (button press + confirm modal) | Attendance record with hours_ladap; audit log | `attendance` / `attendance_outsider` / `attendance_guru_baru`, `audit_logs` |
| Submit Quiz | Guru Baru | assessment_id, enrollment_id, answers JSON | AssessmentSubmission with auto-calculated grade | `assessment_submission` |
| Submit Task | Guru Baru | assessment_id, file upload or text_answer | AssessmentSubmission with file_path | `assessment_submission`, disk `public/submissions/` |
| Export History PDF | Teacher / Guru Besar | user session | PDF download streamed to browser | None (read-only from `attendance`, `enroll_teacher`) |
| Download Attendance Letters | Guru Besar | training ID | ZIP of per-participant PDF attendance letters | None (read-only, temp ZIP in `storage/app/temp/`) |
| Submit Feedback | Authenticated user | form_id, answers[], rating, comment | FeedbackResponse rows (one per answered question) | `feedback_response` |

---

## CHAPTER 4 — DESIGN

### 4.1 System Architecture

| Layer | Files | Responsibility |
|---|---|---|
| Routing | `routes/web.php`, `routes/auth.php`, `routes/settings.php` | Define URL patterns, map to controllers, apply role middleware |
| Middleware | `bootstrap/app.php` (alias registration), `HandleInertiaRequests` | Auth gate, role enforcement (`role:guru_besar`, `role:teacher|outsider|guru_baru`), Inertia shared props |
| Controllers | `app/Http/Controllers/` (27 files) | Receive request, call service or model, return `Inertia::render()` |
| Form Requests | `app/Http/Requests/` (8 files) | Input validation and authorization per action |
| Services | `app/Services/ApplicationService.php`, `AttendanceService.php`, `TrainingService.php` | Business logic: eligibility checks, capacity validation, LADAP multiplier, audit writing |
| Models / ORM | `app/Models/` (31 files) | Eloquent table mapping, relationships, scopes, accessors |
| Events | `app/Events/` (8 files) | Domain event payloads dispatched after state changes |
| Listeners | `app/Listeners/` (8 files, all `ShouldQueue`) | Queued notification creation in `notification_ladap` |
| Jobs | `app/Jobs/AutoMarkAbsentJob.php` | Placeholder (absence is implicit — no attendance record = absent) |
| Repository / Integration | `app/Integration/` (6 files) | Abstraction over `teacher` and `principal` tables for future external-system swap |
| Providers | `app/Providers/AppServiceProvider.php` | Binds `TeacherRepositoryInterface → LocalTeacherRepository`, `PrincipalRepositoryInterface → LocalPrincipalRepository` |
| Frontend Pages | `resources/js/pages/` (32 .tsx files) | Inertia page components rendered by React |
| Frontend Components | `resources/js/components/` (43 .tsx files including shadcn/ui and layout parts) | Reusable UI components |
| PDF Templates | `resources/views/pdf/` | Blade templates rendered by DomPDF for downloads |
| Database | `database/migrations/` (44 migration files) | SQLite (dev) / MySQL (production) schema |

### 4.2 Data Model Design

#### 4.2.1 Database Tables

**Table: `users`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| id | bigint UNSIGNED (PK, auto-increment) | No | — | — |
| name | varchar(255) | No | — | — |
| email | varchar(255) UNIQUE | No | — | — |
| email_verified_at | timestamp | Yes | NULL | — |
| password | varchar(255) | No | — | — |
| remember_token | varchar(100) | Yes | NULL | — |
| external_id | varchar(255) | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `school`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| schoolID | bigint UNSIGNED (PK) | No | — | — |
| schoolName | varchar(255) | No | — | — |
| schoolAddress | varchar(255) | No | — | — |
| registerDate | date | No | — | — |
| phoneNumber | varchar(255) | No | — | — |
| totalTeacher | int | No | 0 | — |
| vacancy | int | No | 0 | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `teacher`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| teacherID | bigint UNSIGNED (PK) | No | — | — |
| teacherName | varchar(255) | No | — | — |
| ICNumber | varchar(255) UNIQUE | No | — | — |
| phoneNumber | varchar(255) | No | — | — |
| email | varchar(255) UNIQUE | No | — | — |
| martialStatus | varchar(255) | No | — | — |
| gender | varchar(255) | No | — | — |
| address | text | No | — | — |
| race | varchar(255) | No | — | — |
| appointedDate | date | No | — | — |
| serviceDate | date | No | — | — |
| pensionDate | date | Yes | NULL | — |
| latestAge | int | No | — | — |
| schoolID | bigint UNSIGNED | No | — | `school.schoolID` (cascade delete) |
| password | varchar(255) | No | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `principal`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| principalID | bigint UNSIGNED (PK) | No | — | — |
| principalName | varchar(255) | No | — | — |
| ICNumber | varchar(255) UNIQUE | No | — | — |
| phoneNumber | varchar(255) | No | — | — |
| email | varchar(255) UNIQUE | No | — | — |
| martialStatus | varchar(255) | No | — | — |
| gender | varchar(255) | No | — | — |
| address | text | No | — | — |
| race | varchar(255) | No | — | — |
| appointedDate | date | No | — | — |
| serviceDate | date | No | — | — |
| pensionDate | date | Yes | NULL | — |
| latestAge | int | No | — | — |
| password | varchar(255) | No | — | — |
| schoolID | bigint UNSIGNED | No | — | `school.schoolID` (cascade delete) |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `outsider`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| outsider_id | bigint UNSIGNED (PK) | No | — | — |
| name | varchar(255) | Yes | NULL | — |
| ICNumber | varchar(255) UNIQUE | Yes | NULL | — |
| phoneNumber | varchar(255) | Yes | NULL | — |
| gender | varchar(255) | Yes | NULL | — |
| organization | varchar(255) | Yes | NULL | — |
| contact_no | varchar(255) | Yes | NULL | — |
| user_id | bigint UNSIGNED | No | — | `users.id` (cascade delete) |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

> **Note:** `name`, `ICNumber`, `phoneNumber`, and `gender` are nullable to support partial profile creation at self-registration. The outsider completes these fields later via the Settings page. `organization` and `contact_no` were added to support professional context fields for external participants.

**Table: `applicant`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| applicant_id | bigint UNSIGNED (PK) | No | — | — |
| full_name | varchar(255) | No | — | — |
| email | varchar(255) UNIQUE | No | — | — |
| password | varchar(255) | No | — | — |
| phone_number | varchar(255) | No | — | — |
| remember_token | varchar(100) | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `course`** (mapped to `Training` model)

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| course_id | bigint UNSIGNED (PK) | No | — | — |
| course_name | varchar(255) | No | — | — |
| course_category | enum('single','series','multi_day') | Yes | NULL | — |
| description | text | Yes | NULL | — |
| capacity | int | No | — | — |
| price | decimal(10,2) | No | 0.00 | — |
| status | varchar(255) | No | 'open' (migrated to 'upcoming') | — |
| poster | varchar(255) | Yes | NULL | — |
| course_mod | varchar(255) | Yes | NULL | — |
| course_pic | varchar(255) | Yes | NULL | — |
| whatsapp_group | varchar(255) | Yes | NULL | — |
| organiser_name | varchar(255) | No | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `enroll_teacher`** (mapped to `Application` model)

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| teacherID | bigint UNSIGNED (PK, composite) | No | — | `teacher.teacherID` (cascade delete) |
| course_id | bigint UNSIGNED (PK, composite) | No | — | `course.course_id` (cascade delete) |
| status | enum('pending','approved','rejected') | No | 'pending' | — |
| rejection_reason | text | Yes | NULL | — |
| applied_at | timestamp | No | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `enroll_outsider`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| outsider_id | bigint UNSIGNED (PK, composite) | No | — | `outsider.outsider_id` (cascade delete) |
| course_id | bigint UNSIGNED (PK, composite) | No | — | `course.course_id` (cascade delete) |
| status | enum('pending','approved','rejected') | No | 'pending' | — |
| rejection_reason | text | Yes | NULL | — |
| applied_at | timestamp | No | current | — |

**Table: `enroll_guru_baru`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| enrollment_id | bigint UNSIGNED (PK, auto-increment) | No | — | — |
| applicant_id | bigint UNSIGNED | No | — | `applicant.applicant_id` (cascade delete) |
| course_id | bigint UNSIGNED | No | — | `course.course_id` (cascade delete) |
| status | enum('pending','approved','rejected','auto_enrolled') | No | 'pending' | — |
| rejection_reason | text | Yes | NULL | — |
| applied_at | timestamp | No | current | — |
| UNIQUE | (applicant_id, course_id) | — | — | — |

**Table: `assign`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| schoolID | bigint UNSIGNED (PK, composite) | No | — | `school.schoolID` (cascade delete) |
| teacherID | bigint UNSIGNED (PK, composite) | No | — | `teacher.teacherID` (cascade delete) |
| assignDate | date | No | — | — |
| status | varchar(255) | No | — | — |

**Table: `trainer`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| trainer_id | bigint UNSIGNED (PK) | No | — | — |
| trainer_name | varchar(255) | No | — | — |
| trainer_ic | varchar(255) | No | — | — |
| trainer_email | varchar(255) | No | — | — |
| trainer_phoneNo | varchar(255) | No | — | — |
| expertise | text | No | — | — |
| trainer_pic | varchar(255) | Yes | NULL | — |
| invitation_letter | varchar(255) | Yes | NULL | — |
| average_rating | decimal(3,2) | No | 0.00 | — |
| status | enum('active','inactive') | No | 'active' | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `course_session`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| session_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | `course.course_id` (cascade delete) |
| session_date | date | Yes | NULL | — |
| start_time | time | Yes | NULL | — |
| end_time | time | Yes | NULL | — |
| location | varchar(255) | Yes | NULL | — |
| qr_code | varchar(255) UNIQUE | Yes | NULL | — |
| qr_expiry_time | datetime | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `session_trainer`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| id | bigint UNSIGNED (PK) | No | — | — |
| session_id | bigint UNSIGNED | No | — | `course_session.session_id` (cascade delete) |
| trainer_id | bigint UNSIGNED | No | — | `trainer.trainer_id` (cascade delete) |
| UNIQUE | (session_id, trainer_id) | — | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `attendance`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| attendance_id | bigint UNSIGNED (PK) | No | — | — |
| timestamp_status | decimal(5,2) | No | 0.00 | — |
| hours_ladap | decimal(5,2) | No | 0.00 | — |
| teacher_id | bigint UNSIGNED | No | — | `teacher.teacherID` (cascade delete) |
| session_id | bigint UNSIGNED | No | — | `course_session.session_id` (cascade delete) |
| UNIQUE | (teacher_id, session_id) | — | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `attendance_outsider`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| attendOutsider_id | bigint UNSIGNED (PK) | No | — | — |
| timestamp_status | decimal(5,2) | No | 0.00 | — |
| outsider_id | bigint UNSIGNED | No | — | `outsider.outsider_id` (cascade delete) |
| session_id | bigint UNSIGNED | No | — | `course_session.session_id` (cascade delete) |
| UNIQUE | (outsider_id, session_id) | — | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `attendance_guru_baru`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| attendGuruBaru_id | bigint UNSIGNED (PK) | No | — | — |
| timestamp_status | decimal(5,2) | No | 0.00 | — |
| hours_ladap | decimal(5,2) | No | 0.00 | — |
| applicant_id | bigint UNSIGNED | No | — | `applicant.applicant_id` (cascade delete) |
| session_id | bigint UNSIGNED | No | — | `course_session.session_id` (cascade delete) |
| UNIQUE | (applicant_id, session_id) | — | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `assessment`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| assessment_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | `course.course_id` (cascade delete) |
| type | varchar(50) | No | — | — |
| title | varchar(255) | No | — | — |
| status | varchar(50) | No | 'Active' | — |
| module_id | bigint UNSIGNED | Yes | NULL | — |
| instruction_text | text | Yes | NULL | — |
| available_from | datetime | Yes | NULL | — |
| available_until | datetime | Yes | NULL | — |
| accepted_format | varchar(100) | Yes | NULL | — |
| time_limit_minutes | int | Yes | NULL | — |
| max_attempts | int | Yes | NULL | — |
| total_marks | decimal(8,2) | Yes | NULL | — |
| weight_percent | decimal(5,2) | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |
| INDEX | status | — | — | — |

**Table: `assessment_question`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| question_id | bigint UNSIGNED (PK) | No | — | — |
| assessment_id | bigint UNSIGNED | No | — | `assessment.assessment_id` (cascade delete) |
| question_text | text | No | — | — |
| question_image_path | varchar(255) | Yes | NULL | — |
| question_order | int | No | 1 | — |
| question_type | varchar(50) | No | — | — |
| options_json | json | Yes | NULL | — |
| correct_answer | text | Yes | NULL | — |
| marks | decimal(8,2) | No | 1.00 | — |
| grading_rubric | text | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `assessment_submission`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| submission_id | bigint UNSIGNED (PK) | No | — | — |
| assessment_id | bigint UNSIGNED | No | — | `assessment.assessment_id` (cascade delete) |
| enrollment_id | bigint UNSIGNED | No | — | — |
| submitted_file_path | varchar(255) | Yes | NULL | — |
| original_file_name | varchar(255) | Yes | NULL | — |
| answers_json | json | Yes | NULL | — |
| submitted_at | datetime | Yes | NULL | — |
| status | varchar(50) | Yes | NULL | — |
| grade | decimal(8,2) | Yes | NULL | — |
| feedback | text | Yes | NULL | — |
| graded_by_trainer_id | bigint UNSIGNED | Yes | NULL | — |
| graded_at | datetime | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `training_module`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| module_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | — |
| module_title | varchar(255) | No | — | — |
| module_order | int | No | 1 | — |
| cover_image | varchar(255) | Yes | NULL | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `training_content_block`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| block_id | bigint UNSIGNED (PK) | No | — | — |
| module_id | int | No | — | — |
| block_type | varchar(50) | No | — | — |
| block_title | varchar(255) | Yes | NULL | — |
| block_content | text | Yes | NULL | — |
| external_url | varchar(500) | Yes | NULL | — |
| material_id | bigint UNSIGNED | Yes | NULL | — |
| assessment_id | bigint UNSIGNED | Yes | NULL | — |
| block_order | int | No | 1 | — |
| is_visible | tinyint(1) | No | 1 (true) | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `learning_material`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| material_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | INDEX |
| trainer_id | bigint UNSIGNED | Yes | NULL | — |
| session_id | bigint UNSIGNED | Yes | NULL | — |
| original_file_name | varchar(255) | Yes | NULL | — |
| file_path | varchar(500) | Yes | NULL | — |
| material_type | varchar(50) | Yes | NULL | — |
| metadata_cleared | tinyint(1) | No | 0 (false) | — |
| uploaded_at | datetime | No | current | — |

**Table: `training_access_log`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| access_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | — |
| enrollment_id | bigint UNSIGNED | No | — | — |
| accessed_at | timestamp | No | current | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |
| INDEX | (course_id, enrollment_id) | — | — | — |

**Table: `training_content_interaction_log`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| interaction_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | — |
| module_id | bigint UNSIGNED | No | — | — |
| block_id | bigint UNSIGNED | No | — | — |
| enrollment_id | bigint UNSIGNED | No | — | — |
| first_opened_at | datetime | Yes | NULL | — |
| last_accessed_at | datetime | Yes | NULL | — |
| open_count | int UNSIGNED | No | 0 | — |
| total_seconds | int UNSIGNED | No | 0 | — |
| last_event_type | varchar(50) | Yes | NULL | — |
| UNIQUE | (enrollment_id, block_id) | — | — | — |
| INDEX | block_id, enrollment_id | — | — | — |
| created_at | timestamp | Yes | NULL | — |
| updated_at | timestamp | Yes | NULL | — |

**Table: `feedback_form`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| form_id | bigint UNSIGNED (PK) | No | — | — |
| course_id | bigint UNSIGNED | No | — | `course.course_id` |
| trainer_id | bigint UNSIGNED | No | — | `trainer.trainer_id` |
| title | varchar(255) | No | — | — |
| created_by_staff | bigint UNSIGNED | No | — | — |
| created_date | timestamp | Yes | NULL | — |

**Table: `feedback_question`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| question_id | bigint UNSIGNED (PK) | No | — | — |
| form_id | bigint UNSIGNED | No | — | `feedback_form.form_id` (cascade delete) |
| category | varchar(100) | Yes | NULL | — |
| question_text | text | No | — | — |
| question_type | enum('likert','rating','short_text','paragraph','yes_no','multiple_choice') | No | — | — |
| options_json | text | Yes | NULL | — |
| is_required | tinyint(1) | No | 1 (true) | — |
| question_order | int | No | 0 | — |

**Table: `feedback_response`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| response_id | bigint UNSIGNED (PK) | No | — | — |
| form_id | bigint UNSIGNED | No | — | `feedback_form.form_id` (cascade delete) |
| question_id | bigint UNSIGNED | No | — | `feedback_question.question_id` (cascade delete) |
| user_id | bigint UNSIGNED | No | — | `users.id` (cascade delete) |
| answer_value | text | No | — | — |
| rating | tinyint UNSIGNED | Yes | NULL | — |
| comment | text | Yes | NULL | — |
| response_date | timestamp | Yes | NULL | — |
| teacherID | bigint UNSIGNED | Yes | NULL | `teacher.teacherID` (null on delete) |
| applicant_id | bigint UNSIGNED | Yes | NULL | `applicant.applicant_id` (null on delete) |
| created_at | timestamp | No | current | — |
| UNIQUE | (form_id, question_id, user_id) | — | — | — |

**Table: `notification_ladap`** (originally `notifications`, renamed by migration)

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| Notification_id | bigint UNSIGNED (PK) | No | — | — |
| user_id | bigint UNSIGNED | No | — | `users.id` (cascade delete) |
| message | text | No | — | — |
| type | varchar(255) | No | — | — |
| url | varchar(255) | Yes | NULL | — |
| is_read | tinyint(1) | No | 0 (false) | — |
| teacherID | bigint UNSIGNED | Yes | NULL | `teacher.teacherID` (null on delete) |
| applicant_id | bigint UNSIGNED | Yes | NULL | `applicant.applicant_id` (null on delete) |
| principalID | bigint UNSIGNED | Yes | NULL | `principal.principalID` (null on delete) |
| created_at | timestamp | No | — | — |
| INDEX | (user_id, is_read) | — | — | — |

**Table: `audit_logs`**

| Column | Type | Nullable | Default | FK Reference |
|---|---|---|---|---|
| id | bigint UNSIGNED (PK) | No | — | — |
| user_id | bigint UNSIGNED | Yes | NULL | `users.id` (null on delete) |
| action | varchar(255) | No | — | — |
| target_type | varchar(255) | No | — | — |
| target_id | bigint UNSIGNED | No | — | — |
| metadata | json | Yes | NULL | — |
| created_at | timestamp | No | — | — |

**Spatie RBAC Tables (framework-managed)**

EduTrain uses `spatie/laravel-permission` v6 for Role-Based Access Control. These tables are created automatically by the Spatie migration and managed entirely by the package — no manual queries are made against them in application code:

| Table | Purpose |
|---|---|
| `roles` | Stores role definitions: `guru_besar`, `teacher`, `outsider`, `guru_baru`, `hr` |
| `permissions` | Stores permission definitions (unused — EduTrain uses role-only RBAC) |
| `model_has_roles` | Pivot: assigns roles to users (`model_type = App\Models\User`, `model_id = users.id`) |
| `model_has_permissions` | Pivot: assigns direct permissions to models (unused) |
| `role_has_permissions` | Pivot: assigns permissions to roles (unused) |

Roles are assigned via `$user->assignRole(Role::X->value)` and checked via `role:` middleware or `$user->hasRole()`. No `role` column exists on the `users` table — all role state lives in `model_has_roles`.

#### 4.2.2 Entity Relationships

| Model | Relationship | Related Model | Via |
|---|---|---|---|
| `User` | hasOne | `Teacher` | `teacher.email = users.email` |
| `User` | hasOne | `Principal` | `principal.email = users.email` |
| `User` | hasOne | `Outsider` | `outsider.user_id = users.id` |
| `User` | hasMany | `Notification` | `notification_ladap.user_id` |
| `User` | hasMany | `AuditLog` | `audit_logs.user_id` |
| `Teacher` | belongsTo | `School` | `teacher.schoolID` |
| `Principal` | belongsTo | `School` | `principal.schoolID` |
| `Applicant` | hasMany | `EnrollGuruBaru` | `enroll_guru_baru.applicant_id` |
| `Training` (→ `course`) | hasMany | `CourseSession` | `course_session.course_id` |
| `Training` | hasMany | `Application` (→ `enroll_teacher`) | `enroll_teacher.course_id` |
| `Training` | hasMany | `EnrollOutsider` | `enroll_outsider.course_id` |
| `Training` | hasMany | `EnrollGuruBaru` | `enroll_guru_baru.course_id` |
| `Training` | hasMany | `TrainingAccessLog` | `training_access_log.course_id` |
| `Training` | hasMany | `LearningMaterial` | `learning_material.course_id` |
| `CourseSession` | belongsTo | `Training` | `course_session.course_id` |
| `CourseSession` | belongsToMany | `Trainer` | pivot `session_trainer` |
| `CourseSession` | hasMany | `Attendance` | `attendance.session_id` |
| `CourseSession` | hasMany | `LearningMaterial` | `learning_material.session_id` |
| `Application` | belongsTo | `Teacher` | `enroll_teacher.teacherID` |
| `Application` | belongsTo | `Training` | `enroll_teacher.course_id` |
| `EnrollGuruBaru` | belongsTo | `Applicant` | `enroll_guru_baru.applicant_id` |
| `EnrollGuruBaru` | belongsTo | `Training` | `enroll_guru_baru.course_id` |
| `EnrollGuruBaru` | hasMany | `TrainingAccessLog` | `training_access_log.enrollment_id` |
| `Attendance` | belongsTo | `Teacher` | `attendance.teacher_id` |
| `Attendance` | belongsTo | `CourseSession` | `attendance.session_id` |
| `TrainingModule` | belongsTo | `Training` | `training_module.course_id` |
| `TrainingModule` | hasMany | `TrainingContentBlock` | `training_content_block.module_id` |
| `TrainingContentBlock` | belongsTo | `TrainingModule` | `training_content_block.module_id` |
| `TrainingContentBlock` | belongsTo | `LearningMaterial` | `training_content_block.material_id` |
| `TrainingContentBlock` | hasMany | `TrainingContentInteractionLog` | `training_content_interaction_log.block_id` |
| `Assessment` | hasMany | `AssessmentQuestion` | `assessment_question.assessment_id` (ordered by `question_order`) |
| `Assessment` | hasMany | `AssessmentSubmission` | `assessment_submission.assessment_id` |
| `FeedbackForm` | belongsTo | `Course` | `feedback_form.course_id` |
| `FeedbackForm` | belongsTo | `Trainer` | `feedback_form.trainer_id` |
| `FeedbackForm` | hasMany | `FeedbackQuestion` | `feedback_question.form_id` (cascade delete) |
| `FeedbackQuestion` | hasMany | `FeedbackResponse` (implied) | `feedback_response.question_id` (cascade delete) |
| `Notification` | belongsTo | `User` | `notification_ladap.user_id` |

**Business Rules derived from relationships:**

- A teacher belongs to exactly one school; a school can have many teachers. A Guru Besar is linked to their school via the `principal` table, and can only manage teachers from their own school (enforced in `ApplicationController::schoolTeacherIds`).
- Each training program (course) can have multiple scheduled sessions (`course_session`). The training's status (upcoming/ongoing/complete) is derived from the earliest and latest session dates, calculated by `TrainingService::syncStatuses` on every training index load.
- A teacher or outsider may be enrolled in a training only once (composite PK on `enroll_teacher` and `enroll_outsider` prevents duplicates). A Guru Baru is similarly constrained by UNIQUE(applicant_id, course_id) on `enroll_guru_baru`.
- A teacher's attendance record is created at QR scan time. Absence is implicit: if no `attendance` row exists for a (teacher_id, session_id) pair, the teacher is considered absent. The `AutoMarkAbsentJob` is a no-op placeholder implementing this design decision.
- LADAP hours for teachers are calculated with a multiplier: 1.5× for a registered trainer presenting at an Al-Amin course, 1.0× for other Al-Amin participants, and 0.5× for external-organiser training. The multiplier is resolved at scan time in `AttendanceService::resolveMultiplier`.
- A training's effective capacity applies across all enrollment types combined: `enroll_teacher` (approved) + `enroll_outsider` (approved) + `enroll_guru_baru` (approved) must not exceed `course.capacity`.
- A `FeedbackResponse` is UNIQUE on (form_id, question_id, user_id), ensuring each user answers each question at most once per form.
- Series courses auto-enroll all Guru Baru participants; no application record or Guru Besar approval is required. Single/multi-day courses require explicit application and Guru Besar approval.

### 4.3 Logical Design (Data Dictionary)

**Table: `course` (Training)**

| Attribute Name | Description | Data Type & Size | Required? | Default | Unique? | PK/FK | FK Referenced Table |
|---|---|---|---|---|---|---|---|
| course_id | Surrogate primary key | BIGINT UNSIGNED | Yes | Auto-increment | Yes | PK | — |
| course_name | Full name of the training program | VARCHAR(255) | Yes | — | No | — | — |
| course_category | Training format: single session, recurring series, or multi-day program | ENUM('single','series','multi_day') | No | NULL | No | — | — |
| description | Rich-text description of the training content and objectives | TEXT | No | NULL | No | — | — |
| capacity | Maximum total approved enrollments across all participant types | INT | Yes | — | No | — | — |
| price | Registration fee in MYR | DECIMAL(10,2) | Yes | 0.00 | No | — | — |
| status | Lifecycle state: upcoming (registration open), ongoing (in progress), complete (ended) | VARCHAR(255) | Yes | 'open' | No | — | — |
| course_mod | Delivery mode (e.g., Face-to-Face, Online, Hybrid) | VARCHAR(255) | No | NULL | No | — | — |
| organiser_name | Name of the organising body; used for LADAP hour multiplier calculation | VARCHAR(255) | Yes | — | No | — | — |
| whatsapp_group | URL of the training's WhatsApp communication group | VARCHAR(255) | No | NULL | No | — | — |

**Table: `enroll_teacher`**

| Attribute Name | Description | Data Type & Size | Required? | Default | Unique? | PK/FK | FK Referenced Table |
|---|---|---|---|---|---|---|---|
| teacherID | Identifies the applying teacher | BIGINT UNSIGNED | Yes | — | Composite PK | PK, FK | `teacher.teacherID` |
| course_id | Identifies the target training | BIGINT UNSIGNED | Yes | — | Composite PK | PK, FK | `course.course_id` |
| status | Enrollment decision | ENUM('pending','approved','rejected') | Yes | 'pending' | No | — | — |
| rejection_reason | Reason provided by Guru Besar when rejecting | TEXT | No | NULL | No | — | — |
| applied_at | Exact timestamp when the teacher submitted the application | TIMESTAMP | Yes | — | No | — | — |

**Table: `course_session`**

| Attribute Name | Description | Data Type & Size | Required? | Default | Unique? | PK/FK | FK Referenced Table |
|---|---|---|---|---|---|---|---|
| session_id | Surrogate primary key | BIGINT UNSIGNED | Yes | Auto-increment | Yes | PK | — |
| course_id | Parent training program | BIGINT UNSIGNED | Yes | — | No | FK | `course.course_id` |
| session_date | Calendar date of the training session | DATE | No | NULL | No | — | — |
| start_time | Session start time (used for QR window validation) | TIME | No | NULL | No | — | — |
| end_time | Session end time (used to calculate session duration) | TIME | No | NULL | No | — | — |
| location | Physical or virtual venue | VARCHAR(255) | No | NULL | No | — | — |
| qr_code | Unique 40-character token; scanned by participants to mark attendance | VARCHAR(255) | No | NULL | Yes | — | — |
| qr_expiry_time | When this QR token becomes invalid | DATETIME | No | NULL | No | — | — |

**Table: `attendance`**

| Attribute Name | Description | Data Type & Size | Required? | Default | Unique? | PK/FK | FK Referenced Table |
|---|---|---|---|---|---|---|---|
| attendance_id | Surrogate primary key | BIGINT UNSIGNED | Yes | Auto-increment | Yes | PK | — |
| teacher_id | Teacher who was present | BIGINT UNSIGNED | Yes | — | Composite unique | FK | `teacher.teacherID` |
| session_id | Session attended | BIGINT UNSIGNED | Yes | — | Composite unique | FK | `course_session.session_id` |
| timestamp_status | Raw session duration in hours (end_time − start_time) | DECIMAL(5,2) | Yes | 0.00 | No | — | — |
| hours_ladap | Adjusted LADAP hours after applying organiser multiplier (0.5×, 1.0×, or 1.5×) | DECIMAL(5,2) | Yes | 0.00 | No | — | — |

**Table: `users`**

| Attribute Name | Description | Data Type & Size | Required? | Default | Unique? | PK/FK | FK Referenced Table |
|---|---|---|---|---|---|---|---|
| id | Surrogate primary key | BIGINT UNSIGNED | Yes | Auto-increment | Yes | PK | — |
| name | Display name of the user | VARCHAR(255) | Yes | — | No | — | — |
| email | Login credential and cross-table linking key to `teacher.email`, `principal.email`, `applicant.email` | VARCHAR(255) | Yes | — | Yes | — | — |
| password | Bcrypt-hashed password | VARCHAR(255) | Yes | — | No | — | — |
| external_id | Optional identifier for future external system integration | VARCHAR(255) | No | NULL | No | — | — |
| email_verified_at | Timestamp of email verification completion | TIMESTAMP | No | NULL | No | — | — |

### 4.4 GUI Design

| Page Component | Route | User Role | Key UI Elements |
|---|---|---|---|
| `dashboard/guru-besar.tsx` | `/dashboard/principal` | guru_besar | KPI cards (active trainings, pending applications, completed this month, total teachers); pending applications table; upcoming trainings list; teacher attendance table with LADAP hours |
| `dashboard/teacher.tsx` | `/dashboard/teacher` | teacher | LADAP hours total, completed trainings count, pending applications badge; upcoming enrolled trainings list; school name display |
| `dashboard/guru-baru.tsx` | `/dashboard/guru-baru` | guru_baru | Series courses count, pending/approved application counts; recent applications list; available open courses cards; unread notifications count |
| `dashboard/outsider.tsx` | `/dashboard/outsider` | outsider | Registered upcoming/ongoing trainings list; pending applications count; training history count; unread notifications count |
| `trainings/index.tsx` | `/trainings` | all authenticated | Searchable/filterable training cards with status badges; per-training application status indicator; apply/view buttons |
| `trainings/show.tsx` | `/trainings/{training}` | all authenticated | Training details (dates, sessions, trainer, mode); apply button; session attendance status per session; learning materials list; content block modal (material/YouTube/quiz/task); feedback form |
| `trainings/create.tsx` | `/trainings/create` | guru_besar | Form: course name, category, description, capacity, price, mode, organiser, WhatsApp group |
| `trainings/edit.tsx` | `/trainings/{training}/edit` | guru_besar | Pre-filled update form matching create fields |
| `trainings/assign.tsx` | `/trainings/{training}/assign` | guru_besar | Checkbox list of available school teachers not yet enrolled; assign button |
| `applications/index.tsx` | `/applications` | guru_besar | Tabbed list of all teacher applications (pending/approved/rejected) with approve/reject inline actions |
| `applications/my-applications.tsx` | `/my-applications` | teacher / outsider | Personal application list with status, applied date, rejection reason |
| `applications/guru-baru-courses.tsx` | `/guru-baru/my-courses` | guru_baru | All series courses with links to show page |
| `applications/guru-baru-applications.tsx` | `/guru-baru/my-applications` | guru_baru | Guru Baru's single/multi-day applications with status |
| `attendance/manage.tsx` | `/trainings/{training}/attendance` | guru_besar | Session list with QR code display, present count, date/time per session |
| `attendance/mark.tsx` | `/trainings/{training}/attendance/mark` | teacher / outsider / guru_baru | QR token input field (manual or camera); success/error flash message |
| `attendance/report.tsx` | `/trainings/{training}/attendance/report` | guru_besar | Matrix of participants vs sessions; present/absent indicators; attendance rate summary |
| `history/index.tsx` | `/my-history` | all authenticated | List of approved training enrollments with attendance rate, total hours, LADAP hours; PDF export button |
| `history/show.tsx` | `/my-history/{training}` | all authenticated | Detailed training history: session-by-session attendance, hours, trainer names; assessment summary for Guru Baru |
| `assessments/show.tsx` | `/trainings/{training}/assessments/{assessment}` | guru_baru | Assessment title and instruction; MCQ question list with radio buttons or text area; submit button; previous submission result |
| `notifications/index.tsx` | `/notifications` | all authenticated | Chronological notification list; mark-read and clear-all actions; notification type badges |
| `reports/development-overview.tsx` | `/reports/development-overview` | guru_besar | Table of all school teachers with completed trainings count, total LADAP hours, attendance rate; PDF download button |
| `hr/dashboard.tsx` | `/hr/dashboard` | hr | KPI cards (total schools, teachers, outsiders, guru baru, pending outsider applications, pending guru baru applications); quick links to applications and user lists |
| `hr/applications/index.tsx` | `/hr/applications` | hr | Tabbed by type (outsider / guru baru); status filter (pending / approved / rejected); approve/reject buttons with optional reason modal |
| `hr/teachers/index.tsx` | `/hr/teachers` | hr | Two-step flow: school card grid (with teacher count and Guru Besar name) → click school → teacher table with search |
| `hr/outsiders/index.tsx` | `/hr/outsiders` | hr | Searchable list of all registered outsiders |
| `hr/guru-baru/index.tsx` | `/hr/guru-baru` | hr | Searchable list of all guru baru applicants |
| `auth/login.tsx` | `/login` | unauthenticated | Split-panel layout: violet branding panel (left) + form panel (right); email + password; remember-me checkbox |
| `auth/register.tsx` | `/register` | unauthenticated | Split-panel layout; name, email, password, confirm-password; self-registers as `outsider` role only |
| `settings/profile.tsx` | `/settings/profile` | all authenticated | Update name and email; delete account option |
| `settings/password.tsx` | `/settings/password` | all authenticated | Current password, new password, confirm new password |
| `settings/appearance.tsx` | `/settings/appearance` | all authenticated | Theme selection (light/dark/system) |

### 4.5 DBMS Selection

EduTrain uses two database configurations depending on the environment:

| Environment | DBMS | Reason |
|---|---|---|
| Development | SQLite | Zero-configuration, file-based (`database/database.sqlite`), no server required, fast for local iteration |
| Production | MySQL / MariaDB | Industry-standard relational DBMS with full support for foreign keys, indexes, transactions, and concurrent connections |

Laravel's database abstraction layer (Eloquent ORM and Query Builder) makes the application largely DBMS-agnostic. Switching between SQLite and MySQL requires only a change to the `DB_CONNECTION` value in `.env`. MySQL was selected for production because it supports:
- Full `ENUM` column type used in `course.course_category`, `enroll_guru_baru.status`, and `feedback_question.question_type`
- Row-level locking for concurrent enrollment capacity checks
- `utf8mb4` charset for full Unicode support including Malay characters
- Production-grade backup tools (`mysqldump`, binary log, point-in-time recovery)

### 4.6 Stored Procedures and Triggers

#### Stored Procedures

EduTrain implements two stored procedures for LADAP summary calculations. These are created via Laravel migrations and called from dashboard controllers when the production MySQL driver is active (SQLite dev environment falls back to Eloquent queries).

**Procedure 1: `GetTeacherLadapSummary(p_teacher_id)`**

Returns the LADAP summary for a single teacher — total approved trainings, sessions attended, and total LADAP hours. Called from `TeacherDashboardController::index()`.

```sql
CREATE PROCEDURE GetTeacherLadapSummary(IN p_teacher_id BIGINT UNSIGNED)
BEGIN
    SELECT
        t.teacherID,
        t.teacherName,
        COUNT(DISTINCT et.course_id)       AS total_trainings,
        COUNT(a.attendance_id)             AS sessions_attended,
        COALESCE(SUM(a.hours_ladap), 0.00) AS total_ladap_hours
    FROM teacher t
    LEFT JOIN enroll_teacher et
        ON t.teacherID = et.teacherID AND et.status = 'approved'
    LEFT JOIN course_session cs
        ON et.course_id = cs.course_id
    LEFT JOIN attendance a
        ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
    WHERE t.teacherID = p_teacher_id
    GROUP BY t.teacherID, t.teacherName;
END
```

Called in PHP:
```php
$summary = DB::select('CALL GetTeacherLadapSummary(?)', [$teacher->id]);
$totalLadapHours = round((float) ($summary[0]->total_ladap_hours ?? 0), 2);
```

**Procedure 2: `GetSchoolLadapOverview(p_school_id)`**

Returns per-teacher LADAP statistics for all teachers in a school — used in `GuruBesarDashboardController::index()` to populate the teacher attendance table.

```sql
CREATE PROCEDURE GetSchoolLadapOverview(IN p_school_id BIGINT UNSIGNED)
BEGIN
    SELECT
        t.teacherID,
        t.teacherName,
        t.ICNumber,
        t.email,
        COUNT(DISTINCT et.course_id)       AS total_trainings,
        COUNT(DISTINCT cs.session_id)      AS total_sessions,
        COUNT(a.attendance_id)             AS sessions_attended,
        COALESCE(SUM(a.hours_ladap), 0.00) AS total_ladap_hours,
        CASE
            WHEN COUNT(DISTINCT cs.session_id) > 0
            THEN ROUND(
                COUNT(a.attendance_id) / COUNT(DISTINCT cs.session_id) * 100, 1
            )
            ELSE NULL
        END AS attendance_rate
    FROM teacher t
    LEFT JOIN enroll_teacher et
        ON t.teacherID = et.teacherID AND et.status = 'approved'
    LEFT JOIN course_session cs
        ON et.course_id = cs.course_id
    LEFT JOIN attendance a
        ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
    WHERE t.schoolID = p_school_id
    GROUP BY t.teacherID, t.teacherName, t.ICNumber, t.email;
END
```

Called in PHP:
```php
$spRows = DB::select('CALL GetSchoolLadapOverview(?)', [$schoolId]);
```

#### Triggers

EduTrain implements one database trigger that writes an audit record automatically whenever a teacher's enrollment status changes to `approved` or `rejected` at the database level — acting as a safety net independent of the application layer.

**Trigger: `after_enroll_teacher_status_change`**

Fires AFTER UPDATE on `enroll_teacher`. If the `status` column changes to `approved` or `rejected`, it inserts a row into `audit_logs` with the source marked as `db_trigger`, distinguishing it from application-layer audit writes.

```sql
CREATE TRIGGER after_enroll_teacher_status_change
AFTER UPDATE ON enroll_teacher
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status
        AND (NEW.status = 'approved' OR NEW.status = 'rejected')
    THEN
        INSERT INTO audit_logs
            (user_id, action, target_type, target_id, metadata, created_at)
        VALUES (
            NULL,
            CONCAT('application_', NEW.status),
            'enroll_teacher',
            NEW.teacherID,
            JSON_OBJECT(
                'course_id',       NEW.course_id,
                'previous_status', OLD.status,
                'new_status',      NEW.status,
                'source',          'db_trigger'
            ),
            NOW()
        );
    END IF;
END
```

| Field | Value | Notes |
|---|---|---|
| `user_id` | NULL | Triggers run in DB context, no session user available |
| `action` | `application_approved` / `application_rejected` | Built via CONCAT |
| `target_type` | `enroll_teacher` | Identifies the affected model |
| `target_id` | `NEW.teacherID` | The teacher whose application changed |
| `metadata.source` | `db_trigger` | Distinguishes from application-layer audit writes |

Both stored procedures and the trigger are created via dedicated Laravel migration files (`2026_06_23_000001_create_ladap_stored_procedures.php`, `2026_06_23_000002_create_enroll_teacher_audit_trigger.php`) and are guarded with `DB::getDriverName() !== 'mysql'` so they are skipped on the SQLite development environment.

#### Related Database Objects

The following database objects are defined in EduTrain's migrations:

**Indexes:**

| Table | Index | Type | Purpose |
|---|---|---|---|
| `course` | `status` | Regular | Fast filtering of upcoming/ongoing/complete trainings |
| `notification_ladap` | `(user_id, is_read)` | Composite | Fast unread count lookup per user |
| `course_session` | `qr_code` | Unique | Prevent duplicate QR tokens; fast token lookup on attendance scan |
| `training_content_interaction_log` | `(enrollment_id, block_id)` | Unique | Prevent duplicate interaction rows; fast upsert |
| `training_content_interaction_log` | `block_id`, `enrollment_id` | Regular | Fast content block engagement queries |
| `training_access_log` | `(course_id, enrollment_id)` | Regular | Fast access log lookup per enrollment |
| `assessment` | `status` | Regular | Fast active assessment filtering |

**Foreign Keys (with cascade delete):**

All relationship tables define foreign keys with `CASCADE DELETE` to maintain referential integrity automatically when parent records are removed (e.g. deleting a `course` removes all related `course_session`, `enroll_teacher`, `attendance`, `assessment`, `learning_material` rows).

**Composite Primary Keys:**

| Table | Composite PK Columns | Purpose |
|---|---|---|
| `enroll_teacher` | `(teacherID, course_id)` | Prevents a teacher from applying to the same training twice |
| `enroll_outsider` | `(outsider_id, course_id)` | Prevents duplicate outsider enrollment |
| `assign` | `(schoolID, teacherID)` | Prevents duplicate school-teacher assignment |

**Composite Unique Constraints:**

| Table | Unique Constraint | Purpose |
|---|---|---|
| `enroll_guru_baru` | `(applicant_id, course_id)` | One enrollment per guru baru per course |
| `attendance` | `(teacher_id, session_id)` | One attendance record per teacher per session |
| `attendance_outsider` | `(outsider_id, session_id)` | One attendance record per outsider per session |
| `attendance_guru_baru` | `(applicant_id, session_id)` | One attendance record per guru baru per session |
| `session_trainer` | `(session_id, trainer_id)` | One trainer-session assignment per pair |
| `feedback_response` | `(form_id, question_id, user_id)` | One response per question per user per form |
| `training_content_interaction_log` | `(enrollment_id, block_id)` | One interaction log row per enrollment per content block |

### 4.7 Security Mechanisms

EduTrain implements a three-layer defence-in-depth security model:

#### Layer 1 — Route-Level Access Control (RBAC)
All routes are protected by Spatie `role:` middleware. Unauthenticated users are redirected to `/login`. Authenticated users can only reach routes permitted for their role:

```php
->middleware('role:guru_besar')          // single role
->middleware('role:teacher|outsider')    // OR — pipe separator
```

No role-column exists on `users` — all role checks query Spatie's `model_has_roles` pivot table, making role assignment and revocation immediate.

#### Layer 2 — Business Rule Enforcement (Service Layer)
Beyond route access, services validate whether the specific action is valid at runtime:

| Check | Where | Example |
|---|---|---|
| Capacity validation | `ApplicationService::apply()` | Blocks enrollment if approved count ≥ capacity |
| Deadline check | `ApplicationService::apply()` | Blocks application after first session date |
| Duplicate enrollment | Database unique constraint + service check | Prevents applying twice |
| Attendance time window | `AttendanceService::markAttendance()` | Blocks scan outside session start/end time |
| Duplicate attendance | `AttendanceService` + unique DB constraint | Prevents double attendance on same session |
| School ownership | `ApplicationController` | Guru Besar can only approve teachers from their own school |

#### Layer 3 — Frontend UI Visibility
React role checks via `useRole()` hook show or hide buttons and actions based on role. These are UI-only and provide no security enforcement — all enforcement happens at layers 1 and 2.

#### Additional Security Controls

| Control | Implementation |
|---|---|
| SQL Injection Prevention | All queries via Eloquent ORM / Query Builder (parameterized) — no raw string interpolation |
| CSRF Protection | Laravel's built-in CSRF token on all state-changing requests (POST/PATCH/DELETE) |
| Password Hashing | `Hash::make()` using bcrypt with cost factor 10 (Laravel default) |
| Session Isolation | `Inertia::location()` used on login/logout to force full browser reload, preventing previous user's cached page data from appearing |
| Input Validation | Dedicated `FormRequest` class per controller action; validation never lives inline |
| File Upload Validation | MIME type and extension validated before storing to `storage/app/public/submissions/` |
| Auth-Guarded File Access | Learning materials served via authenticated route (`/learning-materials/{id}`) — not publicly accessible via direct URL |

### 4.8 Database Contingency

#### Current Implementation
EduTrain's contingency provisions at the application layer include:

| Mechanism | Description |
|---|---|
| Database Queue | Notifications and async jobs use Laravel's database queue driver. Failed jobs are logged in the `failed_jobs` table and can be retried via `php artisan queue:retry` |
| DB Transactions | `ApplicationService::approve()` wraps capacity check and status update in a `DB::transaction()` to ensure atomicity — if either step fails, both are rolled back |
| Unique Constraints | Composite unique constraints on enrollment and attendance tables prevent duplicate records even if the application layer fails |
| Audit Logs | `audit_logs` table records all approval, rejection, and training completion actions with user ID, timestamp, and metadata — providing a recoverable activity trail |

#### Recommended Contingency Measures (Future)

| Measure | Description |
|---|---|
| Automated Database Backup | Schedule daily `mysqldump` exports stored off-server (e.g. cloud storage) |
| Point-in-Time Recovery | Enable MySQL binary logging for granular recovery to any transaction |
| Database Replication | Configure MySQL primary-replica replication for read scalability and failover |
| Soft Deletes | Add `deleted_at` to critical tables (course, enroll_teacher) to allow recovery of accidentally deleted records |
| Environment Separation | Maintain separate development, staging, and production databases to prevent accidental data loss |

---

## CHAPTER 5 — IMPLEMENTATION

### 5.1 Environment Setup

#### Software Environment

| Software Component | Tool/Software Name | Version | Purpose |
|---|---|---|---|
| Backend Runtime | PHP | ^8.2 | Executes Laravel application server-side |
| Backend Framework | Laravel | ^12.0 | MVC framework, ORM, routing, queue, events |
| Frontend Build Tool | Vite (via laravel-vite-plugin) | ^6.0 | Hot-reload development server and production asset bundling |
| Frontend Language | TypeScript | ^5.7.2 | Statically typed React components |
| Frontend Framework | React | ^19.0.0 | Component-based UI rendering |
| Package Manager (PHP) | Composer | — | Manages PHP dependencies |
| Package Manager (JS) | npm | — | Manages JavaScript dependencies |
| Database (development) | SQLite | — | File-based local database (`database/database.sqlite`) |
| Database (production) | MySQL / MariaDB | — | Production-grade relational database |
| Queue Worker | Laravel Queue (database driver) | — | Processes notification listeners asynchronously |
| PDF Renderer | DomPDF (barryvdh/laravel-dompdf) | ^3.1 | Converts Blade templates to PDF files |
| Version Control | Git | — | Source code management |
| Local Dev Orchestration | `composer run dev` (concurrently) | — | Runs Laravel server, queue listener, and Vite in parallel |

#### Hardware Requirements

Development environment: minimum 8 GB RAM, quad-core CPU, 20 GB disk, internet for SMTP.
Deployment environment: minimum 2 vCPU, 4 GB RAM, 20 GB storage, web server (Nginx/Apache).
Participant devices: smartphone with camera for QR code attendance scanning.

#### Database Connection

From `config/database.php` and `.env.example`:

| Config Key | Value / .env Variable | Notes |
|---|---|---|
| Default driver | `DB_CONNECTION` (default: `sqlite`) | Switch to `mysql` for production |
| SQLite path | `DB_DATABASE` = `database/database.sqlite` | Local dev only; created by `post-create-project-cmd` |
| MySQL host | `DB_HOST` = `127.0.0.1` | — |
| MySQL port | `DB_PORT` = `3306` | — |
| MySQL database name | `DB_DATABASE` = `laravel` | Rename for production |
| MySQL username | `DB_USERNAME` = `root` | — |
| MySQL password | `DB_PASSWORD` = _(empty)_ | — |
| MySQL charset | `DB_CHARSET` = `utf8mb4` | Full Unicode including emoji |
| MySQL collation | `DB_COLLATION` = `utf8mb4_unicode_ci` | — |
| Foreign key enforcement | `DB_FOREIGN_KEYS` = `true` (SQLite only) | — |
| Session driver | `SESSION_DRIVER` = `database` | — |
| Queue connection | `QUEUE_CONNECTION` = `database` | Notification listeners use database queue |
| Cache store | `CACHE_STORE` = `database` | — |

### 5.2 DML Implementation

**INSERT Example — Create a new application (teacher self-applies):**

Eloquent (from `ApplicationService::apply`):
```php
$application = Application::create([
    'teacherID'  => $teacher->teacherID,
    'course_id'  => $training->getKey(),
    'status'     => 'pending',
    'applied_at' => now(),
]);
```

Equivalent SQL:
```sql
INSERT INTO enroll_teacher (teacherID, course_id, status, applied_at, created_at, updated_at)
VALUES (42, 7, 'pending', '2026-06-17 10:30:00', NOW(), NOW());
```

**UPDATE Example — Approve a teacher application with capacity lock:**

Eloquent (from `ApplicationService::approve`):
```php
DB::table('enroll_teacher')
    ->where('teacherID', $application->teacherID)
    ->where('course_id', $application->course_id)
    ->update(['status' => 'approved']);
```

Equivalent SQL:
```sql
UPDATE enroll_teacher
SET status = 'approved', updated_at = NOW()
WHERE teacherID = 42 AND course_id = 7;
```

**DELETE Example — Clear all notifications for a user:**

Eloquent (from `NotificationController::clear`):
```php
Notification::where('user_id', $request->user()->id)->delete();
```

Equivalent SQL:
```sql
DELETE FROM notification_ladap
WHERE user_id = 15;
```

### 5.3 Query Implementation

**Query 1 — Attendance matrix for a training (Guru Besar report):**

Eloquent (from `AttendanceController::report`):
```php
$sessionIds = $training->courseSessions()->orderBy('session_date')->pluck('session_id');
$attendances = Attendance::whereIn('session_id', $sessionIds)->get();
```

Equivalent SQL:
```sql
SELECT * FROM attendance
WHERE session_id IN (
    SELECT session_id FROM course_session
    WHERE course_id = 7
    ORDER BY session_date
);
```

**Query 2 — Total LADAP hours for a teacher across completed trainings:**

Eloquent (from `TeacherDashboardController::index`):
```php
$sessionIds = CourseSession::whereIn('course_id', $completedCourseIds)->pluck('session_id');
$totalLadapHours = Attendance::where('teacher_id', $teacher->id)
    ->whereIn('session_id', $sessionIds)
    ->sum('hours_ladap');
```

Equivalent SQL:
```sql
SELECT SUM(a.hours_ladap)
FROM attendance a
WHERE a.teacher_id = 42
  AND a.session_id IN (
      SELECT cs.session_id FROM course_session cs
      WHERE cs.course_id IN (3, 5, 9)
  );
```

**Query 3 — Enrolled teacher user IDs for bulk notification (using join chain):**

Eloquent (from `Application::enrolledUserIds`):
```php
return static::where('enroll_teacher.course_id', $courseId)
    ->whereIn('status', $statuses)
    ->join('teacher', 'enroll_teacher.teacherID', '=', 'teacher.teacherID')
    ->join('users', 'teacher.email', '=', 'users.email')
    ->pluck('users.id');
```

Equivalent SQL:
```sql
SELECT u.id
FROM enroll_teacher et
JOIN teacher t ON et.teacherID = t.teacherID
JOIN users u ON t.email = u.email
WHERE et.course_id = 7
  AND et.status IN ('approved');
```

**Query 4 — Development overview: per-teacher LADAP summary for Guru Besar:**

Eloquent (from `GuruBesarDashboardController::index`, attendance sub-aggregate):
```php
$sessions   = CourseSession::whereIn('course_id', $approvedCourseIds)->get();
$sessionIds = $sessions->pluck('session_id');
$ladapHours = Attendance::where('teacher_id', $teacherRecord->teacherID)
    ->whereIn('session_id', $sessionIds)
    ->sum('hours_ladap');
$attendanceRate = $totalDays > 0
    ? round(($attended / $totalDays) * 100, 1)
    : null;
```

Equivalent SQL:
```sql
SELECT
    t.teacherID,
    t.teacherName,
    SUM(a.hours_ladap) AS total_ladap_hours,
    COUNT(a.attendance_id) AS sessions_attended,
    ROUND(COUNT(a.attendance_id) / COUNT(DISTINCT DATE(cs.session_date)) * 100, 1) AS attendance_rate
FROM teacher t
JOIN enroll_teacher et ON t.teacherID = et.teacherID
JOIN course_session cs ON et.course_id = cs.course_id
LEFT JOIN attendance a ON a.teacher_id = t.teacherID AND a.session_id = cs.session_id
WHERE t.schoolID = 1
  AND et.status = 'approved'
GROUP BY t.teacherID;
```

**Query 5 — Training index with total application count per training:**

Eloquent (from `TrainingController::index`):
```php
$trainings = Training::withCount('applications')->latest()->get();
```

Equivalent SQL:
```sql
SELECT c.*, COUNT(et.teacherID) AS applications_count
FROM course c
LEFT JOIN enroll_teacher et ON c.course_id = et.course_id
GROUP BY c.course_id
ORDER BY c.created_at DESC;
```

### 5.4 System Interface

**Dashboard Module:**
- `dashboard/guru-besar`: Guru Besar sees four KPI cards (active trainings, pending applications, completed this month, total school teachers), a recent-pending-applications table with inline approve/reject links, an upcoming trainings list, and a teacher attendance breakdown table showing LADAP hours and attendance rate per teacher.
- `dashboard/teacher`: Teacher sees their accumulated LADAP hours, count of completed and pending applications, and a list of enrolled upcoming trainings.
- `dashboard/guru-baru`: Guru Baru sees counts of auto-enrolled series courses, pending applications, approved enrollments, a list of recent applications, and cards of open courses to apply for.
- `dashboard/outsider`: Outsider sees their currently active registered trainings, pending application count, and completed training count.

**Training Management Module:**
- `trainings/index`: All authenticated users see a filterable (by name and status) grid of training cards. Application status per training is shown inline (applied/pending/approved/not applied).
- `trainings/show`: Full training detail page with sessions list, QR attendance buttons (for participants), learning materials section (PDF files and YouTube videos in a modal per block), assessment list (quiz and task cards), and feedback form. Guru Besar sees management action buttons (edit, cancel, complete, manage attendance).
- `trainings/create` and `trainings/edit`: Guru Besar forms with fields for all course attributes.
- `trainings/assign`: Guru Besar sees a list of school teachers not yet enrolled; selects via checkbox and submits bulk assignment.

**Application Management Module:**
- `applications/index`: Guru Besar views all teacher enrollment requests from their school with pending/approved/rejected tabs and approve/reject buttons.
- `applications/my-applications`: Teachers and Outsiders see their own application history with status badges and rejection reasons.
- `applications/guru-baru-courses`: Guru Baru's auto-enrolled series course list.
- `applications/guru-baru-applications`: Guru Baru's explicit single/multi-day application list with status.

**QR Attendance Module:**
- `attendance/manage`: Guru Besar sees each session with its QR token rendered as a visual QR code, present count, and session time window.
- `attendance/mark`: Participants enter or scan a 40-character QR token; system validates and returns success/error feedback.
- `attendance/report`: Guru Besar sees a matrix table of all approved participants vs all sessions with present/absent indicators.

**History and Reports Module:**
- `history/index`: All authenticated users see their approved training list with attendance rate and LADAP hours; Teachers have a PDF export button.
- `history/show`: Detailed view of a single training's session-by-session attendance record, including hours attended and trainer names.
- `reports/development-overview`: Guru Besar sees all school teachers' training summary with total LADAP hours and attendance rates, with a PDF download button.

**Assessment Module:**
- `assessments/show`: Guru Baru sees assessment title, instruction, questions (MCQ or short answer), and a submission form; previous submission result shown if already submitted.

**Notifications Module:**
- `notifications/index`: Chronological notification feed with type-tagged messages, read/unread state, and mark-all-read / clear-all controls.

---

## CHAPTER 6 — TESTING AND VALIDATION

### 6.1 Test Strategy

| Testing Type | Purpose | Focus Area |
|-------------|---------|-----------|
| Unit Testing | Test individual service methods in isolation | Service layer logic, validation, capacity checks |
| Integration Testing | Test enrollment, attendance, notification modules together | Data flow, queue job dispatch |
| System Testing | Test complete EduTrain end-to-end | Full LADAP training lifecycle, PDF generation |
| UAT | Confirm system meets Guru Besar and Teacher needs | Usability, real LADAP workflow |

### 6.2 Functional Test Cases

| Test ID | Role | Module | Test Case | Test Input | Expected Result |
|---------|------|--------|-----------|------------|----------------|
| TC01 | guru_besar | Training | Create a new training program | course_name="Pedagogy Workshop", course_category=single, capacity=30, price=0, organiser_name="EDU Al-amin" | Training record created; all teachers and outsiders receive 'training.created' notification |
| TC02 | guru_besar | Training | Edit training location and organiser | course_mod="Online", organiser_name="External Org" | Training updated; all approved enrolled teachers receive 'training.updated' notification |
| TC03 | teacher | Application | Teacher applies for an upcoming training | POST `/trainings/7/apply` | `enroll_teacher` record created with status=pending; Guru Besar receives 'application.submitted' notification |
| TC04 | teacher | Application | Teacher applies for a full training | POST `/trainings/7/apply` (capacity already met by approved enrollments) | HTTP 422 error: "This training is full." No record created |
| TC05 | teacher | Application | Teacher applies after first session date | POST `/trainings/7/apply` (first session date in the past) | HTTP 422 error: "The application deadline has passed." |
| TC06 | guru_besar | Application | Approve a pending application | POST `/applications/{key}/approve` | `enroll_teacher.status` → approved; teacher receives 'application.approved' notification; audit log created |
| TC07 | guru_besar | Application | Reject a pending application with reason | POST `/applications/{key}/reject`, reason="Quota exceeded" | `enroll_teacher.status` → rejected; rejection_reason stored; teacher receives 'application.rejected' notification |
| TC08 | guru_besar | Application | Assign teachers directly to training | POST `/trainings/7/assign`, user_ids=[15,16] | `enroll_teacher` records created with status=approved; training capacity incremented by 2; teachers receive 'teacher.assigned' notification |
| TC09 | teacher | Attendance | Mark attendance with valid QR token | POST `/trainings/7/attendance/mark`, token="[valid 40-char token]", within session time window | `attendance` record created with timestamp_status and hours_ladap calculated |
| TC10 | teacher | Attendance | Attempt double scan (same session day) | POST `/trainings/7/attendance/mark` twice for same session date | Second attempt returns HTTP 422: "You have already attended a session today for this training." |
| TC11 | guru_baru | Assessment | Submit MCQ quiz | POST `/trainings/7/assessments/3/submit`, answers[q1]=A, answers[q2]=B | `assessment_submission` created with auto-calculated grade; status='graded' if no short-answer questions |
| TC12 | guru_baru | Assessment | Submit task with file upload | POST `/trainings/7/assessments/4/submit`, file=report.pdf | File stored to `public/submissions/`; `assessment_submission` created with status='submitted' |
| TC13 | teacher | History | Export personal training history as PDF | GET `/my-history/export` | PDF file downloaded with name `training-history-{name}-{date}.pdf`; all approved training rows present |
| TC14 | guru_besar | Report | Download attendance letters ZIP | GET `/trainings/7/letters` | ZIP file with one PDF attendance letter per participant who has at least one attendance record |
| TC15 | any authenticated | Notification | Mark specific notifications as read | POST `/notifications/mark-read`, ids=[1,2,3] | `notification_ladap.is_read` = true for specified IDs; unread count decremented |
| TC16 | outsider | Application | Outsider applies for a single course | POST `/trainings/7/outsider-apply` | `enroll_outsider` record created with status=pending |
| TC17 | guru_baru | Application | Guru Baru applies for multi-day course | POST `/trainings/8/guru-baru-apply` | `enroll_guru_baru` record created with status=pending (series courses are blocked) |
| TC18 | guru_besar | Report | View development overview | GET `/reports/development-overview` | All school teachers listed with LADAP hours and attendance rate calculated from attendance table |
| TC19 | any authenticated | Feedback | Submit feedback for a course | POST `/trainings/7/feedback/1/submit`, answers per question, rating=4 | One `feedback_response` row per answered question; duplicate submission blocked with error |
| TC20 | guru_baru | Content | Log content block interaction | POST `/trainings/7/blocks/5/interact`, event_type=open | `training_content_interaction_log` upserted: first_opened_at set on first open, open_count incremented |

### 6.3 Non-Functional Test Cases

| Test ID | Module | Type | Scenario | Expected Result | Actual Result | Status |
|---------|--------|------|----------|----------------|--------------|--------|
| NFT01 | Dashboard | Performance | Load Teacher dashboard with enrolled trainings | Page loads < 3 seconds | ___ | ___ |
| NFT02 | Training Content | Usability | First-time teacher navigates to content page | Completes navigation without confusion | ___ | ___ |
| NFT03 | Login | Security | Submit SQL injection string in password field | Input sanitized, error displayed | ___ | ___ |
| NFT04 | QR Attendance | Reliability | Scan same QR token twice | Duplicate scan rejected | ___ | ___ |
| NFT05 | Enrollment | Data Integrity | Enroll when training at full capacity | System blocks with error message | ___ | ___ |

---

## APPENDIX — CODEBASE SUMMARY

### File Structure

**Controllers (`app/Http/Controllers/`)**
- `Controller.php` — Base controller
- `TrainingController.php` — index, create, store, show, edit, update, cancel, complete
- `ApplicationController.php` — index, store, approve, reject, approveGuruBaru, rejectGuruBaru, approveOutsider, rejectOutsider, assign, myApplications, assignForm
- `AttendanceController.php` — manage, generate, report, markForm, mark
- `AssessmentController.php` — show, store (quiz and task submission)
- `LearningMaterialController.php` — show (stream/download)
- `TrainingContentInteractionController.php` — log
- `TrainingHistoryController.php` — index, show, export, teacherHistory, developmentOverview, developmentOverviewPdf
- `NotificationController.php` — index, markRead, clear
- `FeedbackController.php` — submit
- `ReportController.php` — attendanceLetter
- `OutsiderApplicationController.php` — store
- `GuruBaruApplicationController.php` — store, myCourses, myApplications
- `Dashboard/GuruBesarDashboardController.php` — index
- `Dashboard/TeacherDashboardController.php` — index
- `Dashboard/GuruBaruDashboardController.php` — index
- `Dashboard/OutsiderDashboardController.php` — index
- `Hr/HrDashboardController.php` — index (KPI stats: schools, teachers, outsiders, guru baru, pending counts)
- `Hr/HrApplicationController.php` — index, approveOutsider, rejectOutsider, approveGuruBaru, rejectGuruBaru
- `Hr/HrUserController.php` — teachers (schools grid → teacher list with school_id param), outsiders, guruBaru
- `Auth/AuthenticatedSessionController.php`, `RegisteredUserController.php`, `NewPasswordController.php`, `PasswordResetLinkController.php`, `EmailVerificationNotificationController.php`, `EmailVerificationPromptController.php`, `VerifyEmailController.php`, `ConfirmablePasswordController.php`
- `Settings/ProfileController.php`, `Settings/PasswordController.php`

**Services (`app/Services/`)**
- `ApplicationService.php` — apply, approve, reject, assignDirect, writeAudit
- `AttendanceService.php` — markAttendance, markGuruBaruAttendance, markOutsiderAttendance, resolveMultiplier, writeAudit
- `TrainingService.php` — syncStatuses, markCompleted

**Repositories / Integration (`app/Integration/`)**
- `Contracts/TeacherRepositoryInterface.php` — findById, findByEmail, all, allForDropdown, updateProfile
- `Contracts/PrincipalRepositoryInterface.php` — findById, findByEmail, all
- `DTOs/TeacherDTO.php`, `DTOs/PrincipalDTO.php`
- `Repositories/LocalTeacherRepository.php` — implements TeacherRepositoryInterface via `teacher` table
- `Repositories/LocalPrincipalRepository.php` — implements PrincipalRepositoryInterface via `principal` table

**Models (`app/Models/`)**
- `User.php`, `Teacher.php`, `Principal.php`, `School.php`, `Outsider.php`, `Applicant.php`
- `Training.php` (→ `course` table), `Course.php` (alias)
- `Application.php` (→ `enroll_teacher`), `EnrollOutsider.php`, `EnrollGuruBaru.php`
- `CourseSession.php`, `Trainer.php`, `Assign.php`
- `Attendance.php`, `AttendanceOutsider.php`, `AttendanceGuruBaru.php`
- `AttendanceSession.php`, `AttendanceRecord.php` (legacy, pre-refactor stubs)
- `Assessment.php`, `AssessmentQuestion.php`, `AssessmentSubmission.php`
- `TrainingModule.php`, `TrainingContentBlock.php`, `LearningMaterial.php`
- `TrainingAccessLog.php`, `TrainingContentInteractionLog.php`
- `FeedbackForm.php`, `FeedbackQuestion.php`, `FeedbackResponse.php`
- `Notification.php` (→ `notification_ladap`), `AuditLog.php`

**Jobs (`app/Jobs/`)**
- `AutoMarkAbsentJob.php` — No-op placeholder (absence = no attendance record)

**Events (`app/Events/`)**
- `ApplicationSubmitted.php`, `ApplicationApproved.php`, `ApplicationRejected.php`
- `TeacherAssigned.php`
- `TrainingCreated.php`, `TrainingUpdated.php`, `TrainingCancelled.php`
- `OutsiderApplicationSubmitted.php` — payload: `EnrollOutsider $enrollment`
- `GuruBaruApplicationSubmitted.php` — payload: `EnrollGuruBaru $enrollment`
- `AttendanceSessionCreated.php` (deprecated, no-op)

**Listeners (`app/Listeners/`)**
- `SendApplicationSubmittedNotification.php` — notifies all Guru Besar when a teacher applies
- `SendApplicationApprovedNotification.php` — notifies applying teacher
- `SendApplicationRejectedNotification.php` — notifies applying teacher
- `SendTeacherAssignedNotification.php` — notifies directly assigned teacher
- `SendTrainingCreatedNotification.php` — notifies all teachers, outsiders, and guru baru
- `SendTrainingUpdatedNotification.php` — notifies all approved enrolled teachers, outsiders, and guru baru
- `SendTrainingCancelledNotification.php` — notifies all pending and approved enrolled teachers, outsiders, and guru baru
- `SendOutsiderApplicationSubmittedNotification.php` — notifies all HR users when outsider applies
- `SendGuruBaruApplicationSubmittedNotification.php` — notifies all HR users when guru baru applies
- `SendAttendanceReminderNotification.php` — no-op stub

**React Pages (`resources/js/pages/`)**
```
auth/           confirm-password.tsx, forgot-password.tsx, login.tsx, register.tsx, reset-password.tsx, verify-email.tsx
dashboard/      guru-besar.tsx, teacher.tsx, guru-baru.tsx, outsider.tsx
hr/             dashboard.tsx
hr/applications/ index.tsx
hr/teachers/    index.tsx  (two-step: school grid → teacher table)
hr/outsiders/   index.tsx
hr/guru-baru/   index.tsx
trainings/      index.tsx, show.tsx, create.tsx, edit.tsx, assign.tsx
applications/   index.tsx, my-applications.tsx, guru-baru-courses.tsx, guru-baru-applications.tsx
attendance/     manage.tsx, mark.tsx, report.tsx
assessments/    show.tsx
history/        index.tsx, show.tsx
notifications/  index.tsx
reports/        development-overview.tsx
settings/       appearance.tsx, password.tsx, profile.tsx
dashboard.tsx   (generic redirect page)
welcome.tsx     (landing / redirect to login)
```

### Route List

| Method | URI | Route Name | Controller Action | Middleware / Role |
|---|---|---|---|---|
| GET | `/` | `home` | Redirect to login | public |
| GET | `/dashboard` | `dashboard` | Role-based redirect | auth |
| GET | `/dashboard/principal` | `dashboard.guru-besar` | `GuruBesarDashboardController@index` | auth, role:guru_besar |
| GET | `/trainings/create` | `trainings.create` | `TrainingController@create` | auth, role:guru_besar |
| POST | `/trainings` | `trainings.store` | `TrainingController@store` | auth, role:guru_besar |
| GET | `/trainings/{training}/edit` | `trainings.edit` | `TrainingController@edit` | auth, role:guru_besar |
| PATCH | `/trainings/{training}` | `trainings.update` | `TrainingController@update` | auth, role:guru_besar |
| POST | `/trainings/{training}/cancel` | `trainings.cancel` | `TrainingController@cancel` | auth, role:guru_besar |
| POST | `/trainings/{training}/complete` | `trainings.complete` | `TrainingController@complete` | auth, role:guru_besar |
| GET | `/applications` | `applications.index` | `ApplicationController@index` | auth, role:guru_besar |
| POST | `/applications/{application}/approve` | `applications.approve` | `ApplicationController@approve` | auth, role:guru_besar |
| POST | `/applications/{application}/reject` | `applications.reject` | `ApplicationController@reject` | auth, role:guru_besar |
| POST | `/guru-baru-applications/{enrollment}/approve` | `guru_baru_applications.approve` | `ApplicationController@approveGuruBaru` | auth, role:guru_besar |
| POST | `/guru-baru-applications/{enrollment}/reject` | `guru_baru_applications.reject` | `ApplicationController@rejectGuruBaru` | auth, role:guru_besar |
| POST | `/outsider-applications/{outsiderId}/{courseId}/approve` | `outsider_applications.approve` | `ApplicationController@approveOutsider` | auth, role:guru_besar |
| POST | `/outsider-applications/{outsiderId}/{courseId}/reject` | `outsider_applications.reject` | `ApplicationController@rejectOutsider` | auth, role:guru_besar |
| GET | `/trainings/{training}/assign` | `trainings.assign.form` | `ApplicationController@assignForm` | auth, role:guru_besar |
| POST | `/trainings/{training}/assign` | `trainings.assign` | `ApplicationController@assign` | auth, role:guru_besar |
| GET | `/trainings/{training}/attendance` | `attendance.manage` | `AttendanceController@manage` | auth, role:guru_besar |
| POST | `/trainings/{training}/attendance/generate` | `attendance.generate` | `AttendanceController@generate` | auth, role:guru_besar |
| GET | `/trainings/{training}/attendance/report` | `attendance.report` | `AttendanceController@report` | auth, role:guru_besar |
| GET | `/teachers/{user}/history` | `history.teacher` | `TrainingHistoryController@teacherHistory` | auth, role:guru_besar |
| GET | `/reports/development-overview` | `reports.development` | `TrainingHistoryController@developmentOverview` | auth, role:guru_besar |
| GET | `/reports/development-overview/pdf` | `reports.development.pdf` | `TrainingHistoryController@developmentOverviewPdf` | auth, role:guru_besar |
| GET | `/trainings/{training}/letters` | `reports.letters` | `ReportController@attendanceLetter` | auth, role:guru_besar |
| GET | `/dashboard/teacher` | `dashboard.teacher` | `TeacherDashboardController@index` | auth, role:teacher\|outsider |
| GET | `/dashboard/outsider` | `dashboard.outsider` | `OutsiderDashboardController@index` | auth, role:teacher\|outsider |
| POST | `/trainings/{training}/apply` | `applications.store` | `ApplicationController@store` | auth, role:teacher\|outsider |
| GET | `/my-applications` | `applications.mine` | `ApplicationController@myApplications` | auth, role:teacher\|outsider |
| GET | `/trainings/{training}/attendance/mark` | `attendance.mark.form` | `AttendanceController@markForm` | auth, role:teacher\|outsider |
| POST | `/trainings/{training}/attendance/mark` | `attendance.mark` | `AttendanceController@mark` | auth, role:teacher\|outsider |
| POST | `/trainings/{training}/outsider-apply` | `outsider.applications.store` | `OutsiderApplicationController@store` | auth, role:outsider |
| GET | `/hr/dashboard` | `hr.dashboard` | `HrDashboardController@index` | auth, role:hr |
| GET | `/hr/applications` | `hr.applications.index` | `HrApplicationController@index` | auth, role:hr |
| POST | `/hr/applications/outsider/{outsiderId}/{courseId}/approve` | `hr.applications.outsider.approve` | `HrApplicationController@approveOutsider` | auth, role:hr |
| POST | `/hr/applications/outsider/{outsiderId}/{courseId}/reject` | `hr.applications.outsider.reject` | `HrApplicationController@rejectOutsider` | auth, role:hr |
| POST | `/hr/applications/guru-baru/{enrollment}/approve` | `hr.applications.guru_baru.approve` | `HrApplicationController@approveGuruBaru` | auth, role:hr |
| POST | `/hr/applications/guru-baru/{enrollment}/reject` | `hr.applications.guru_baru.reject` | `HrApplicationController@rejectGuruBaru` | auth, role:hr |
| GET | `/hr/teachers` | `hr.teachers.index` | `HrUserController@teachers` | auth, role:hr |
| GET | `/hr/outsiders` | `hr.outsiders.index` | `HrUserController@outsiders` | auth, role:hr |
| GET | `/hr/guru-baru` | `hr.guru_baru.index` | `HrUserController@guruBaru` | auth, role:hr |
| GET | `/dashboard/guru-baru` | `dashboard.guru-baru` | `GuruBaruDashboardController@index` | auth, role:guru_baru |
| POST | `/trainings/{training}/guru-baru-apply` | `guru_baru.applications.store` | `GuruBaruApplicationController@store` | auth, role:guru_baru |
| GET | `/guru-baru/my-courses` | `guru_baru.courses.mine` | `GuruBaruApplicationController@myCourses` | auth, role:guru_baru |
| GET | `/guru-baru/my-applications` | `guru_baru.applications.mine` | `GuruBaruApplicationController@myApplications` | auth, role:guru_baru |
| GET | `/trainings/{training}/assessments/{assessment}` | `assessments.show` | `AssessmentController@show` | auth, role:guru_baru |
| POST | `/trainings/{training}/assessments/{assessment}/submit` | `assessments.store` | `AssessmentController@store` | auth, role:guru_baru |
| POST | `/trainings/{training}/blocks/{block}/interact` | `content.interact` | `TrainingContentInteractionController@log` | auth, role:guru_baru |
| GET | `/trainings` | `trainings.index` | `TrainingController@index` | auth |
| GET | `/trainings/{training}` | `trainings.show` | `TrainingController@show` | auth |
| GET | `/notifications` | `notifications.index` | `NotificationController@index` | auth |
| POST | `/notifications/mark-read` | `notifications.mark-read` | `NotificationController@markRead` | auth |
| DELETE | `/notifications` | `notifications.clear` | `NotificationController@clear` | auth |
| GET | `/my-history` | `history.mine` | `TrainingHistoryController@index` | auth |
| GET | `/my-history/export` | `history.export` | `TrainingHistoryController@export` | auth |
| GET | `/my-history/{training}` | `history.show` | `TrainingHistoryController@show` | auth |
| GET | `/learning-materials/{material}` | `learning-materials.show` | `LearningMaterialController@show` | auth |
| POST | `/trainings/{training}/feedback/{form}/submit` | `feedback.submit` | `FeedbackController@submit` | auth |

### Database Schema Summary

| Table Name | Column Count | Key Relationships | Purpose |
|---|---|---|---|
| `users` | 9 | links to `teacher`, `principal`, `outsider` via email; owns `notification_ladap`, `audit_logs` | Laravel authentication accounts for all roles |
| `school` | 9 | parent of `teacher`, `principal`, `assign` | School entity; links teachers and principals to institutions |
| `teacher` | 16 | belongsTo `school`; linked to `user` via email; owns `enroll_teacher`, `attendance`, `assign` | Registered teachers' professional profile |
| `principal` | 16 | belongsTo `school`; linked to `user` via email | Guru Besar professional profile |
| `outsider` | 7 | belongsTo `user`; owns `enroll_outsider`, `attendance_outsider` | External participant profile |
| `applicant` | 8 | owns `enroll_guru_baru`, `attendance_guru_baru`; linked to `user` via email | New-teacher (Guru Baru) participant profile |
| `course` | 13 (with migrations) | parent of `course_session`, `enroll_teacher`, `enroll_outsider`, `enroll_guru_baru`, `assessment`, `training_module`, `learning_material`, `feedback_form` | Core training program entity (also accessed via `Training` model alias) |
| `enroll_teacher` | 7 | composite PK (teacherID, course_id); FK to `teacher`, `course` | Teacher enrollment applications and approval status |
| `enroll_outsider` | 6 | composite PK (outsider_id, course_id); FK to `outsider`, `course` | Outsider enrollment applications |
| `enroll_guru_baru` | 7 | UNIQUE(applicant_id, course_id); FK to `applicant`, `course` | Guru Baru enrollment applications; includes auto_enrolled status for series |
| `assign` | 4 | composite PK (schoolID, teacherID) | Maps teachers to schools |
| `trainer` | 11 | linked via `session_trainer` to `course_session`; parent of `learning_material`, `feedback_form` | External trainers/facilitators |
| `course_session` | 10 | belongsTo `course`; ManyToMany `trainer` via `session_trainer`; parent of `attendance`, `attendance_outsider`, `attendance_guru_baru`, `learning_material` | Individual scheduled session with QR token and time window |
| `session_trainer` | 5 | UNIQUE(session_id, trainer_id); pivot between `course_session` and `trainer` | Many-to-many pivot for session facilitators |
| `attendance` | 7 | UNIQUE(teacher_id, session_id); FK to `teacher`, `course_session` | Teacher attendance records with LADAP hours |
| `attendance_outsider` | 6 | UNIQUE(outsider_id, session_id); FK to `outsider`, `course_session` | Outsider attendance records |
| `attendance_guru_baru` | 7 | UNIQUE(applicant_id, session_id); FK to `applicant`, `course_session` | Guru Baru attendance records with LADAP hours |
| `assessment` | 14 | belongsTo `course`; parent of `assessment_question`, `assessment_submission` | Quizzes and file tasks for series course modules |
| `assessment_question` | 10 | belongsTo `assessment` | Individual quiz/task questions with correct answer and marks |
| `assessment_submission` | 13 | belongsTo `assessment`; references `enrollment_id` | Participant submissions with grades and file paths |
| `training_module` | 6 | belongsTo `course`; parent of `training_content_block` | Logical module grouping for series course content |
| `training_content_block` | 11 | belongsTo `training_module`; optional FK to `learning_material`, `assessment` | Content blocks: material, YouTube, quiz, or task type |
| `learning_material` | 9 | belongsTo `course`, optional `trainer`, optional `course_session` | Uploaded PDF files and other materials streamed via auth route |
| `training_access_log` | 5 | references `course_id` and `enrollment_id` | Logs each time a Guru Baru accesses a series training's show page |
| `training_content_interaction_log` | 12 | UNIQUE(enrollment_id, block_id) | Per-block engagement tracking: open count, time on block, last event type |
| `feedback_form` | 6 | belongsTo `course`, `trainer`; parent of `feedback_question` | Trainer feedback questionnaire template per course |
| `feedback_question` | 8 | belongsTo `feedback_form`; parent of `feedback_response` | Individual feedback questions with type enum |
| `feedback_response` | 11 | UNIQUE(form_id, question_id, user_id); FK to `feedback_form`, `feedback_question`, `users`, `teacher`, `applicant` | Individual question responses from participants |
| `notification_ladap` | 10 | belongsTo `user`; FK to `teacher`, `applicant`, `principal` | In-app notifications for all role events |
| `audit_logs` | 7 | optional FK to `user` | Immutable action audit trail for application approvals, rejections, and training completions |

### Queue Jobs

| Job Name | Triggered By | What It Does | Queue |
|---|---|---|---|
| `AutoMarkAbsentJob` | Registered for dispatch at session creation (architectural placeholder) | No-op — absence is recorded implicitly; if no `attendance` row exists for a participant-session pair, the participant is considered absent. No database writes occur. | default (database driver) |

All event listeners (`Send*Notification` classes) also run as queued jobs via `ShouldQueue`. They write to `notification_ladap` asynchronously so that application-layer HTTP responses are not blocked by notification fan-out.

### Events & Listeners

| Event Class | Triggered By | Listener Class | What Listener Does |
|---|---|---|---|
| `ApplicationSubmitted` | `ApplicationService::apply` after `Application::create` | `SendApplicationSubmittedNotification` | Notifies all users with role `guru_besar` that a new application arrived; writes to `notification_ladap` |
| `ApplicationApproved` | `ApplicationService::approve` after status update to 'approved' | `SendApplicationApprovedNotification` | Notifies the applying teacher (resolved via email→user join) that their application was approved |
| `ApplicationRejected` | `ApplicationService::reject` after status update to 'rejected' | `SendApplicationRejectedNotification` | Notifies the applying teacher that their application was rejected |
| `TeacherAssigned` | `ApplicationService::assignDirect` per assigned teacher | `SendTeacherAssignedNotification` | Notifies each directly assigned teacher that they have been enrolled |
| `TrainingCreated` | `TrainingController::store` after `Training::create` | `SendTrainingCreatedNotification` | Notifies all users with role teacher, outsider, and guru_baru that a new training is available |
| `TrainingUpdated` | `TrainingController::update` when course_mod or organiser_name changed | `SendTrainingUpdatedNotification` | Notifies all approved enrolled teachers, outsiders, and guru baru that training details have changed |
| `TrainingCancelled` | `TrainingController::cancel` | `SendTrainingCancelledNotification` | Notifies all pending and approved enrolled teachers, outsiders, and guru baru that training has been cancelled |
| `OutsiderApplicationSubmitted` | `OutsiderApplicationController::store` after `EnrollOutsider::create` | `SendOutsiderApplicationSubmittedNotification` | Notifies all HR users that a new outsider application has been submitted; links to HR applications page with outsider/pending filter |
| `GuruBaruApplicationSubmitted` | `GuruBaruApplicationController::store` after `EnrollGuruBaru::create` | `SendGuruBaruApplicationSubmittedNotification` | Notifies all HR users that a new guru baru application has been submitted; links to HR applications page with guru_baru/pending filter |
| `AttendanceSessionCreated` | (deprecated) | `SendAttendanceReminderNotification` | No-op — attendance reminders are now handled per session activation; this event and listener are retained as stubs only |

Event-to-listener mapping is auto-discovered by Laravel 12's event discovery mechanism (no explicit `$listen` array in an `EventServiceProvider` — the framework scans listener `handle()` type-hints). All listeners implement `ShouldQueue` and are processed by the database queue worker started via `composer run dev`.agentId: a16c459d331cb5f5f (use SendMessage with to: 'a16c459d331cb5f5f' to continue this agent)
<usage>subagent_tokens: 134042
tool_uses: 113
duration_ms: 641574</usage>