# PSM Report Extraction Guide — EduTrain
> **For Claude Code:** Scan the codebase and fill in every `<!-- EXTRACT: ... -->` block below.
> Run this file from the project root. Output a completed markdown file or paste sections into the Word report.

---

## HOW TO USE THIS FILE

1. Open this file in Claude Code at your project root.
2. Claude Code will scan routes, models, migrations, services, controllers, config files, and React components.
3. Every `<!-- EXTRACT: instruction -->` block will be replaced with real data from the codebase.
4. The completed output maps directly to the PSM report chapters.

---

## STUDENT INFO (fill manually)

```
Student Name     : ___________________________
Student ID       : ___________________________
Supervisor       : ___________________________
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

<!-- EXTRACT: Read README.md if it exists. Summarise the project purpose in 2–3 sentences. -->

### 1.2 Tech Stack

<!-- EXTRACT:
Read composer.json and package.json.
Output a table with columns: Layer | Technology | Version
Include: PHP version, Laravel version, React version, Inertia version, Tailwind version,
all major composer require packages (non-dev), all major npm dependencies.
Format as markdown table.
-->

**Expected output format:**
| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | x.x |
| Frontend | React | x.x |
| ... | ... | ... |

### 1.3 User Roles

<!-- EXTRACT:
Scan: database/migrations/ for any migration containing "roles" or "permissions".
Scan: app/Models/ for any model with HasRoles trait or role assignments.
Scan: routes/web.php and routes/auth.php for role middleware (role: , permission:).
Scan: database/seeders/ for any RoleSeeder or PermissionSeeder — list all roles seeded.
Output a table: Role | Description | Key Permissions/Capabilities
-->

**Expected output format:**
| Role | Description | Key Capabilities |
|------|-------------|-----------------|
| Guru Besar | ... | ... |
| Teacher | ... | ... |
| Guru Baru | ... | ... |
| Outsider | ... | ... |

### 1.4 Objectives

EduTrain was developed with the following objectives:

1. To design and develop a web-based LADAP training management system for Al Amin Edu Oasis that digitises training program creation, enrollment, and lifecycle management.
2. To implement a multi-role enrollment and approval workflow supporting Teacher, Guru Baru, and Outsider participant types with capacity validation.
3. To develop a QR code-based attendance system that automatically records participant presence per session and calculates accumulated LADAP hours.
4. To provide a structured training content delivery platform supporting PDF materials, YouTube videos, MCQ quizzes, and file task submissions.
5. To generate automated PDF compliance reports including training history, attendance letters, and training summary reports.

### 1.5 Scope

#### 1.5.1 User Scope

<!-- EXTRACT:
Scan routes/web.php for all route groups with middleware(['role:...']) or similar.
List each role and the route prefixes/controllers they can access.
Output as: Role | Can Access | Cannot Access
-->

#### 1.5.2 Module Scope

<!-- EXTRACT:
Scan app/Http/Controllers/ — list every controller file name and its public methods.
Group controllers into functional modules.
Output as: Module Name | Controller(s) | Key Actions
-->

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

<!-- EXTRACT:
Read composer.json require (non-dev) section.
Read package.json dependencies section.
For each package, output: Package | Purpose | Why Selected
Key packages to explain: laravel/framework, inertiajs/inertia-laravel, spatie/laravel-permission,
barryvdh/laravel-dompdf, simplesoftwareio/simple-qrcode, react, @inertiajs/react,
tailwindcss, shadcn/ui or @radix-ui/* packages.
-->

**Expected output format:**
| Package | Purpose | Reason for Selection |
|---------|---------|---------------------|
| ... | ... | ... |

### 2.4 Schedule and Milestones

*(To be filled manually by student with Gantt chart and milestone table.)*

---

## CHAPTER 3 — ANALYSIS

### 3.1 Problem Analysis

Current manual LADAP management at Al Amin Edu Oasis involves physical application forms, paper attendance sheets, and manual LADAP hour compilation — leading to errors, data loss, and administrative delay.

### 3.2 Proposed System

EduTrain replaces all manual processes with a fully digital workflow covering: training creation → application → approval → QR attendance → content delivery → assessment → PDF reporting.

### 3.3 Functional Requirements

<!-- EXTRACT:
Scan app/Http/Requests/ — list every Form Request class and its rules() method contents.
Scan app/Services/ — list every public method in each service class with a one-line description.
Compile into a Functional Requirements table: FR_ID | Requirement | Description | Source (Service/Request class)

Also scan routes/web.php for all named routes and group them by feature.
-->

**Expected output format:**
| FR ID | Requirement | Description | Implemented In |
|-------|------------|-------------|---------------|
| FR01 | User Authentication | ... | app/Http/Requests/Auth/ |
| ... | ... | ... | ... |

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

<!-- EXTRACT:
Scan app/Http/Controllers/ to identify all external-facing inputs and outputs per user role.
For each controller, identify: who calls it (role), what data goes in, what data comes out.
Output a DFD description table:
Process | Actor/Entity | Input Data | Output Data | Data Store Affected
-->

---

## CHAPTER 4 — DESIGN

### 4.1 System Architecture

<!-- EXTRACT:
Scan the overall folder structure:
- app/Http/Controllers/ — list all controllers
- app/Services/ — list all service classes
- app/Repositories/ — list all repository classes (if any)
- app/Models/ — list all Eloquent models
- app/Listeners/ / app/Events/ — list all event-listener pairs
- app/Jobs/ — list all queued jobs
- resources/js/Pages/ — list all React page components (grouped by folder)
- resources/js/Components/ — list shared components

Output as a layered architecture diagram description:
Layer | Classes/Files | Responsibility
-->

**Expected output format:**
| Layer | Files | Responsibility |
|-------|-------|---------------|
| Presentation (React) | Pages/Training/*, Pages/Dashboard/* | UI rendering, form handling |
| Controller | TrainingController, EnrollmentController... | HTTP request/response |
| Service | TrainingService, ApplicationService... | Business logic |
| Repository | TeacherRepository, PrincipalRepository... | Data access abstraction |
| Model | Training, User, EnrollTeacher... | Eloquent ORM |
| Job/Queue | MarkAbsentJob, SendNotificationJob... | Async processing |

### 4.2 Data Model Design

#### 4.2.1 Database Tables

<!-- EXTRACT:
Scan ALL files in database/migrations/ in chronological order.
For each migration, extract:
  - Table name
  - All columns with: column name, data type, nullable/required, default, foreign key references
Output as a list of tables, each with their columns.

Format per table:
### Table: `table_name`
| Column | Type | Nullable | Default | FK Reference |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | No | auto | — |
| ... | ... | ... | ... | ... |
-->

#### 4.2.2 Entity Relationships

<!-- EXTRACT:
Scan app/Models/ for all Eloquent relationship methods:
hasOne, hasMany, belongsTo, belongsToMany, hasManyThrough.
For each model, list:
  Model | Relationship Type | Related Model | Foreign Key
Output as a relationship table, then summarise in business rule sentences.
-->

**Expected output format:**
| Model | Relationship | Related Model | Via |
|-------|------------|--------------|-----|
| Training | hasMany | EnrollTeacher | training_id |
| ... | ... | ... | ... |

**Business Rules derived from relationships:**
<!-- EXTRACT: Convert each relationship above into a plain-English business rule sentence. -->

### 4.3 Logical Design (Data Dictionary)

<!-- EXTRACT:
For the 5 most important tables (trainings, enroll_teacher, attendance_sessions, attendance_records, users):
Output a full data dictionary table per table:
Attribute Name | Contents/Description | Data Type & Size | Format | Required? | Default | Unique? | PK/FK | FK Referenced Table
Get column types from migrations. Get descriptions from model $fillable, $casts, or docblocks.
-->

### 4.4 GUI Design

<!-- EXTRACT:
Scan resources/js/Pages/ — list all page components grouped by role/module.
For each page, extract the component name and any prop types or TypeScript interfaces defined at the top.
Output as: Page Component | Route | User Role | Key UI Elements (forms, tables, buttons)

Also scan resources/js/Components/ for shared UI components and list them.
-->

---

## CHAPTER 5 — IMPLEMENTATION

### 5.1 Environment Setup

#### Software Environment

<!-- EXTRACT:
Read composer.json → require (non-dev) for PHP/Laravel packages.
Read package.json → dependencies for JS packages.
Read .env.example for environment variable keys (not values).
Output a Software Environment table:
Software Component | Tool/Software Name | Version | Purpose
-->

#### Hardware Requirements

Development environment: minimum 8 GB RAM, quad-core CPU, 20 GB disk, internet for SMTP.
Deployment environment: minimum 2 vCPU, 4 GB RAM, 20 GB storage, web server (Nginx/Apache).
Participant devices: smartphone with camera for QR code attendance scanning.

#### Database Connection

<!-- EXTRACT:
Read config/database.php — extract the mysql connection configuration keys.
Read .env.example — list all DB_* variables.
Read app/Providers/AppServiceProvider.php or any DatabaseServiceProvider if present.
Output the connection config snippet and explain each key.
-->

### 5.2 DML Implementation

<!-- EXTRACT:
Scan app/Services/ for all methods that call ->create(), ->update(), ->delete(), ->save(), ->insert().
Extract 3 representative examples:
  1. One INSERT example (e.g., creating a training, creating an enrollment)
  2. One UPDATE example (e.g., updating training status, approving an application)
  3. One DELETE example (e.g., deleting a rejected application)

For each, show:
  Query description
  The actual Eloquent/DB code from the service
  Equivalent raw SQL

Format as:
#### INSERT — [Description]
**Eloquent:**
```php
[code]
```
**Equivalent SQL:**
```sql
[sql]
```
-->

### 5.3 Query Implementation

<!-- EXTRACT:
Scan app/Services/ and app/Models/ for complex query methods:
  - Methods using ->with(), ->join(), ->whereHas(), ->groupBy(), ->sum(), ->count()
  - Especially: LADAP hours calculation (sum of attendance hours), enrollment count per training,
    capacity check queries, training content sorted by type.

For each query found, show:
  Query description
  The Eloquent code
  Equivalent SQL

Extract at least 3–5 queries. Format same as DML section above.
-->

### 5.4 System Interface

<!-- EXTRACT:
Scan resources/js/Pages/ — list every page file with its path.
Group by module: Auth, Dashboard, Training, Enrollment, Attendance, Content, Quiz, Task, Notification, Report.
For each page, write one sentence describing what the user sees and can do on that page.

Format as:
#### [Module Name]
- `Pages/[path].tsx` — [one-sentence description]
-->

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

<!-- EXTRACT:
Scan app/Services/ for all public methods — derive a test case for each major action.
Scan routes/web.php for all POST/PUT/DELETE routes — derive test cases.

For each test case output:
Test ID | Role | Module | Test Case | Test Input | Expected Result

Minimum test cases to cover:
- TC01: Login with valid credentials (each role)
- TC02: Login with invalid credentials
- TC03: Create training (Guru Besar)
- TC04: Teacher applies to training
- TC05: Guru Besar approves application
- TC06: Guru Besar rejects application
- TC07: Guru Besar generates QR session
- TC08: Teacher scans QR (valid token)
- TC09: Teacher scans QR (expired token)
- TC10: Auto-absent job fires on session expiry
- TC11: Teacher completes quiz
- TC12: Teacher submits task file
- TC13: Guru Besar grades task
- TC14: Enrollment blocked at capacity
- TC15: Export PDF report

Also scan tests/ folder if it exists — extract actual test method names and assertions.
-->

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

<!-- EXTRACT:
Run: find . -type f -name "*.php" | grep -E "(Controller|Service|Repository|Model|Job|Event|Listener)" | sort
Run: find resources/js -type f -name "*.tsx" | sort
Output the full list grouped by type.
-->

### Route List

<!-- EXTRACT:
Run: php artisan route:list --columns=method,uri,name,action
Output the full route list as a markdown table.
Group routes by middleware/role group.
-->

### Database Schema Summary

<!-- EXTRACT:
Run: php artisan db:show --json OR scan all migration files.
List all tables with column count and key relationships.
Output: Table Name | Columns | Relationships | Purpose
-->

### Queue Jobs

<!-- EXTRACT:
Scan app/Jobs/ — list every job class with:
  - Job name
  - What triggers it (scan app/Services/ or app/Listeners/ for dispatch() calls)
  - What it does (read handle() method)
  - Queue name/connection used
-->

### Events & Listeners

<!-- EXTRACT:
Scan app/Events/ and app/Listeners/.
Read app/Providers/EventServiceProvider.php for the $listen array.
Output: Event Class | Triggered By | Listener Class | What Listener Does
-->

---

## NOTES FOR CLAUDE CODE

When scanning this project:

1. **Start with `composer.json` and `package.json`** to get all versions.
2. **Scan `database/migrations/` in date order** — this gives the complete data model.
3. **Scan `app/Models/`** for relationships and `$fillable`, `$casts`.
4. **Scan `app/Services/`** — this is where all business logic lives (not controllers).
5. **Scan `app/Repositories/`** if it exists — explains data access pattern.
6. **Scan `routes/web.php`** for the complete route list and role middleware groupings.
7. **Scan `app/Jobs/`** for async processes (auto-absent marking, etc.).
8. **Scan `app/Listeners/`** for notification triggers.
9. **Scan `resources/js/Pages/`** for all frontend screens.
10. **Look for `config/`** files for queue driver, mail driver, session config.

For each `<!-- EXTRACT: ... -->` block: replace the entire HTML comment and any placeholder table/text below it with the real extracted data. If a file or pattern is not found, write `[Not found in codebase — verify path]`.

Output one complete `.md` file with all blocks filled in.
