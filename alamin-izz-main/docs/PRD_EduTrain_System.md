# Product Requirements Document (PRD)
## Educator Training Management System (EduTrain System)

**Project Code:** BITU 3973  
**Version:** 1.1  
**Date:** May 2026  
**Prepared by:** Izzhilmy Bin Shamsul Bahri (B032420045)  
**Institution:** Universiti Teknikal Malaysia Melaka (UTeM) — Faculty of Information and Communication Technology  
**Supervisor:** Ts. Dr. Hidayah Binti Rahmalan  

> **Version 1.1 Change Note:** Added cross-system integration architecture (Section 6.3), updated data model ownership table (Section 8), added integration assumptions (Section 10), and revised Out of Scope (Section 9) to reflect planned integration context.

---

## 1. Overview

### 1.1 Product Summary

The **EduTrain System** is a web-based Educator Training Management System designed to digitize and centralize the management of *Latihan Dalam Perkhidmatan* (LADAP) activities for teachers at **Al Amin Edu Oasis**. The system replaces manual and fragmented processes with a unified platform that handles registration, training applications, approval workflows, attendance, task submission, and reporting.

### 1.2 Problem Statement

The current training management process at Al Amin Edu Oasis suffers from:

- Manual registration of teachers and training records
- Absence of a centralized system
- Ineffective approval workflow involving the Guru Besar (Principal)
- Difficulty in tracking training history
- Poor communication and lack of notifications regarding training status
- Delays, miscommunication, and challenges in monitoring teacher development

### 1.3 Goals

- Centralize all LADAP-related processes into a single digital platform
- Automate registration, training application, and approval workflows
- Enable monitoring and reporting of teacher participation and development
- Improve communication through a structured notification system
- Reduce manual workload and minimize administrative errors
- Design the system with a clean integration boundary so that data mastered in a connected external system (scope TBD) can be consumed without restructuring EduTrain's core logic

---

## 2. Stakeholders

| Role | Description |
|---|---|
| **Guru Besar (Principal)** | Manages and approves training; monitors teacher development |
| **Teachers** | Applies for training; tracks history; submits work and quizzes |
| **Outsiders** | External participants who register and attend training programs |
| **System Admin** | Manages the overall system (implied, for maintenance purposes) |
| **Supervisor / Evaluator** | Academic supervisor overseeing PSM project delivery |
| **External System** | A connected system (identity and scope TBD) that may master staff profile data (teacher records, principal records) used by EduTrain |

---

## 3. User Roles & Permissions

### 3.1 Guru Besar (Principal)

| Permission | Description |
|---|---|
| Select Teachers | Choose specific teachers for designated training programs |
| View Training | Browse all available training programs |
| Approve / Reject Applications | Review and act on teacher training applications |
| Monitor Progress | View teacher participation and development records |

### 3.2 Teacher

| Permission | Description |
|---|---|
| View Training | Browse available training programs |
| Apply for Training | Submit application for interested training |
| View Application Status | See approval or rejection from Guru Besar |
| View Training History | Access full record of past training attended |
| Receive Notifications | Get alerts on application and training status changes |
| Submit Work / Quiz | Upload task submissions and answer quizzes |
| Mark Attendance | Record attendance for approved training sessions |
| Submit Feedback | Provide feedback to the trainer post-training |

### 3.3 Outsider

| Permission | Description |
|---|---|
| Register Account | Create a new account on the platform |
| View Training | Browse available training programs |
| Apply for Training | Submit application for interested training |
| View Training History | Access past training attended |
| Receive Notifications | Get alerts on application and training status |
| Submit Work / Quiz | Upload task submissions and answer quizzes |
| Mark Attendance | Record attendance for approved training sessions |
| Submit Feedback | Provide feedback to the trainer post-training |

---

## 4. Functional Requirements

### 4.1 Authentication & Account Management

- **FR-01:** Users must be able to register an account (Outsiders self-register; Teachers may be pre-registered by admin).
- **FR-02:** The system must support role-based login (Guru Besar, Teacher, Outsider).
- **FR-03:** Password reset and account management must be supported.

### 4.2 Training Program Management

- **FR-04:** Administrators / Guru Besar must be able to create, edit, and publish training programs with details (title, date, location, description, capacity).
- **FR-05:** All roles must be able to browse and view available training programs.
- **FR-06:** Training programs must have statuses: `Open`, `Closed`, `Completed`.

### 4.3 Training Application Workflow

- **FR-07:** Teachers and Outsiders must be able to apply for available training programs.
- **FR-08:** Guru Besar must be able to select specific teachers for training (push-based assignment).
- **FR-09:** Guru Besar must be able to approve or reject training applications.
- **FR-10:** Applicants must receive real-time notifications upon approval or rejection.
- **FR-11:** Application statuses must include: `Pending`, `Approved`, `Rejected`.

### 4.4 Attendance Management

- **FR-12:** Approved participants (Teachers and Outsiders) must be able to mark their attendance for training sessions.
- **FR-13:** Attendance records must be stored and retrievable per training session.
- **FR-14:** Guru Besar must be able to view attendance reports per training.

### 4.5 Task & Quiz Submission

- **FR-15:** Trainers / admins must be able to create tasks and quizzes linked to specific training programs.
- **FR-16:** Participants must be able to submit tasks (file upload or text input) within given deadlines.
- **FR-17:** Participants must be able to answer quizzes online.
- **FR-18:** Submission statuses must be tracked: `Not Submitted`, `Submitted`, `Graded`.

### 4.6 Training History & Tracking

- **FR-19:** Each user must have access to a personal training history log.
- **FR-20:** History must display training name, date, attendance status, and task/quiz completion.
- **FR-21:** Guru Besar must be able to view aggregated training history for all teachers.

### 4.7 Notification System

- **FR-22:** In-app notifications must be sent for: application status changes, new training programs, upcoming training reminders, task/quiz deadlines.
- **FR-23:** (Optional) Email notifications for critical events such as approval/rejection.

### 4.8 Feedback System

- **FR-24:** Participants must be able to submit feedback for trainers after training completion.
- **FR-25:** Feedback must include a rating (e.g., 1–5 stars) and optional written comments.
- **FR-26:** Feedback results must be visible to Guru Besar for monitoring trainer quality.

### 4.9 Dashboard & Reporting

- **FR-27:** Guru Besar must have a dashboard showing: number of active training programs, teacher participation rates, pending approvals, and attendance summaries.
- **FR-28:** Teachers must have a personal dashboard showing: training applied, upcoming training, task/quiz status.
- **FR-29:** The system must support generation of training attendance letters / certificates (document export).

---

## 5. Non-Functional Requirements

### 5.1 Performance

- **NFR-01:** Page load time must not exceed 3 seconds under normal load.
- **NFR-02:** The system must support concurrent access by at least 50 users without performance degradation.

### 5.2 Usability

- **NFR-03:** The interface must be user-friendly and accessible to users with basic computer literacy.
- **NFR-04:** The system must be responsive and usable on both desktop and mobile browsers.

### 5.3 Security

- **NFR-05:** All user passwords must be stored using secure hashing (e.g., bcrypt).
- **NFR-06:** Role-based access control (RBAC) must prevent unauthorized access to restricted features.
- **NFR-07:** All data transmission must be over HTTPS.

### 5.4 Reliability & Availability

- **NFR-08:** The system must target 99% uptime during school operating hours.
- **NFR-09:** Data must be backed up regularly to prevent loss.

### 5.5 Maintainability

- **NFR-10:** The codebase must follow the MVC (Model-View-Controller) architecture pattern (Laravel framework).
- **NFR-11:** Code must be documented for ease of future maintenance and handover.

---

## 6. System Architecture

### 6.1 Technology Stack

| Layer | Technology |
|---|---|
| **Frontend** | React (TypeScript) via Inertia.js — shadcn/ui component library |
| **Backend** | PHP — Laravel Framework |
| **Database** | MySQL |
| **Web Server** | Apache / Nginx |
| **Authentication** | Laravel Breeze (Inertia stack) |
| **RBAC** | Spatie laravel-permission |
| **PDF Export** | barryvdh/laravel-dompdf |

### 6.2 High-Level Architecture

```
[Browser / Client]
       |
       v
[Web Server (Apache/Nginx)]
       |
       v
[Laravel Application]
   |              |
[Controllers]  [Inertia + React Views]
   |
[Service Layer]
   |
[Repository Layer]  <-- integration seam (see 6.3)
   |
[MySQL Database]         [External System]
                              (via adapter, future)
```

### 6.3 Cross-System Integration Architecture

EduTrain is part of a combined system. Certain data — most likely staff profile records (teachers, principals) — may be mastered in a separate external system whose schema and integration method are not yet defined. EduTrain must be designed so that when the integration is ready, it can be connected without restructuring the application.

#### 6.3.1 Integration Strategy: Repository + Service Abstraction with DTOs

EduTrain will implement the **Repository pattern** behind a **Service layer**, with all cross-boundary data normalized through **Data Transfer Objects (DTOs)**. This creates a fixed internal contract between EduTrain's business logic and any external data source.

```
[Controller]
     |
[Service Layer]         <-- business logic only, no data-source awareness
     |
[Repository Interface]  <-- contract: defines WHAT data EduTrain needs
     |
     +--- [LocalRepository]     (reads EduTrain's own MySQL DB, active now)
     |
     +--- [ExternalRepository]  (calls external system API or DB, future)
                                (swapped via Laravel IoC container binding)
```

**DTOs define the data shape EduTrain consumes internally, regardless of source:**

| DTO | Fields EduTrain Needs |
|---|---|
| `TeacherDTO` | `id`, `name`, `email`, `department`, `position` |
| `PrincipalDTO` | `id`, `name`, `email`, `phone_no`, `department` |

> **Design Decision:** Controllers and business logic must never reference a specific database connection, table name, or API endpoint directly. All external data access must go through the repository interface. This means the integration method (REST API, direct DB connection, message queue) is an implementation detail hidden behind the adapter — it can change without touching any other layer.

#### 6.3.2 Data Ownership Boundary

| Entity | Owner | Notes |
|---|---|---|
| USER (auth credentials) | EduTrain | EduTrain manages its own auth; if SSO is adopted later, this is the seam to adapt |
| TEACHER profile | **Undecided — potentially external** | Local profile table maintained now; adapter swaps it out when external system is ready |
| PRINCIPAL profile | **Undecided — potentially external** | Same as TEACHER |
| OUTSIDER profile | EduTrain | Outsiders self-register; no external system involved |
| TRAINING | EduTrain | Fully owned by EduTrain |
| APPLICATION | EduTrain | Fully owned by EduTrain |
| ATTENDANCE | EduTrain | Fully owned by EduTrain |
| TASK / SUBMISSION | EduTrain | Fully owned by EduTrain |
| QUIZ / ANSWER SHEET | EduTrain | Fully owned by EduTrain |
| NOTIFICATION | EduTrain | Fully owned by EduTrain |
| FEEDBACK | EduTrain | Fully owned by EduTrain |
| AUDIT LOG | EduTrain | Fully owned by EduTrain |

#### 6.3.3 Authentication Federation

- **Current (Phase 1):** EduTrain manages its own authentication. Teachers and Guru Besar accounts are created by the System Admin. Outsiders self-register.
- **Future (Phase 2, if SSO is adopted):** If the external system provides a shared identity provider, EduTrain's `AuthenticatedSessionController` is the single point to adapt. The repository abstraction means no other layer changes.
- **Decision required before Phase 2:** Whether to use shared credentials, token-based SSO, or OAuth delegation.

#### 6.3.4 External ID Reconciliation

If the external system uses its own staff IDs (e.g., `staff_id: "T001"`), EduTrain must maintain a mapping between its internal `user_id` (integer PK) and the external system's identifier. An `external_id` nullable column on the `users` table will hold this reference for synced accounts, remaining `null` for self-registered Outsiders.

#### 6.3.5 Fallback Behaviour

If the external system is unavailable:
- EduTrain must continue to function for all modules using locally-cached profile data.
- The system must not block login, application submission, attendance marking, or any participant-facing flow due to an external system timeout.
- If a repository call to an external source fails, the system falls back to the last known local state and logs the failure to the audit log.

---

## 7. Key User Flows

### 7.1 Teacher Applying for Training

1. Teacher logs in.
2. Browses available training programs.
3. Submits application for selected training.
4. System notifies Guru Besar of new application.
5. Guru Besar approves or rejects application.
6. Teacher receives notification of outcome.
7. On approval, teacher attends training, marks attendance, submits tasks/quiz, and provides feedback.

### 7.2 Guru Besar Selecting a Teacher for Training

1. Guru Besar logs in.
2. Browses available training programs.
3. Selects a teacher from the teacher list to assign to a training.
4. System notifies the selected teacher.
5. Teacher's training record is updated.

### 7.3 Outsider Registration and Application

1. Outsider visits the platform and registers an account.
2. Browses available training programs.
3. Applies for a training.
4. Application is reviewed and approved/rejected.
5. On approval, outsider attends training, submits tasks/quiz, and provides feedback.

---

## 8. Data Models (Entities)

Entities are grouped by ownership. See Section 6.3.2 for the full ownership rationale.

### 8.1 EduTrain-Owned Entities

| Entity | Key Attributes |
|---|---|
| **User** | id, name, email, password, role (guru_besar / teacher / outsider), external_id (nullable — maps to external system ID), created_at |
| **Training** | id, user_id (creator FK), title, description, date, time, location, capacity, status (open/closed/completed), registration_deadline, category, trainer_name, duration_hours, competency_area, is_public (open to outsiders flag) |
| **Application** | id, user_id FK, training_id FK, status (pending/approved/rejected), rejection_reason (nullable), applied_at |
| **AttendanceSession** | id, training_id FK, qr_token, expires_at, date |
| **AttendanceRecord** | id, user_id FK, attendance_session_id FK, status (present/absent/late), checked_in_at |
| **Task** | id, training_id FK, title, description, deadline, accepted_file_types |
| **TaskSubmission** | id, user_id FK, task_id FK, text_answer, file_path, submitted_at, grade, status (not_submitted/submitted/graded) |
| **Quiz** | id, training_id FK, title, instructions, time_limit (nullable), available_from, deadline |
| **QuizQuestion** | id, quiz_id FK, question_text, type (mcq/short_answer), order |
| **QuizOption** | id, question_id FK, option_text, is_correct |
| **AnswerSheet** | id, user_id FK, quiz_id FK, total_score, submitted_at |
| **AnswerResponse** | id, answer_sheet_id FK, question_id FK, selected_option_id (nullable FK), text_answer (nullable) |
| **Notification** | id, user_id FK, message, type, url (nullable), is_read, created_at |
| **Feedback** | id, user_id FK, training_id FK, rating (1–5), comment (nullable), submitted_at |
| **AuditLog** | id, user_id FK, action, target_type, target_id, metadata (JSON), created_at |

### 8.2 Role-Specific Profile Entities (Ownership Pending Integration Decision)

These entities hold role-specific profile data. They are currently owned and managed by EduTrain. If the external system is later confirmed as the master of staff records, these will be backed by the external repository adapter instead of local DB tables. The internal data shape (DTO) will not change.

| Entity | Key Attributes | Current Owner |
|---|---|---|
| **Teacher** | id, user_id FK, name, department, position, external_id (nullable) | EduTrain (local) — *may move to external* |
| **Principal** | id, user_id FK, name, phone_no, department, external_id (nullable) | EduTrain (local) — *may move to external* |
| **Outsider** | id, user_id FK, name, organization (nullable), contact_no (nullable) | EduTrain (permanent) |

---

## 9. Out of Scope

The following are explicitly **not** in scope for this project:

- Mobile native application (iOS/Android)
- Integration with third-party HR or payroll systems unrelated to the planned combined system
- AI-based training recommendations
- Live video conferencing / virtual training delivery
- Multi-institution support (system is built for Al Amin Edu Oasis only)
- Implementing the live external system connection (Phase 2 integration) — EduTrain will be designed to support it (via repository abstraction) but will not implement the actual connector until the external system's schema and API are defined

---

## 10. Assumptions & Constraints

- The system will be deployed in a school environment with stable internet access.
- Users are assumed to have basic computer literacy.
- The system is scoped to a single institution (Al Amin Edu Oasis).
- Development follows the academic PSM timeline for Session 2025/2026, Semester 2.
- Laravel (PHP) is the mandated technology framework.
- **Integration assumptions (Phase 1):** EduTrain operates as a standalone system with its own user and profile tables. The repository abstraction layer is built and tested against local data.
- **Integration assumptions (Phase 2):** The external system's schema, API contract, and integration method are unknown at the time of writing. No implementation decisions that hard-code external table names, column names, or endpoints are permitted in Phase 1 code.
- **Staff accounts:** Teachers and the Guru Besar are created by the System Admin in Phase 1. In Phase 2, account creation may be driven by a sync from the external system.
- **Authentication:** EduTrain manages its own session-based authentication in Phase 1. SSO or token federation with the external system is a Phase 2 decision and must not be assumed in current design.
- **Data authority:** In the event of a conflict between locally-stored profile data and data from the external system, the external system is considered authoritative for staff records. EduTrain is authoritative for all training, application, attendance, task, and feedback data.
- **LADAP hours:** Cumulative LADAP hours displayed in EduTrain are computed from training records within EduTrain only. If the external system also holds training records, the full cumulative figure will require a Phase 2 aggregation query across both systems.

---

## 11. Success Metrics

| Metric | Target |
|---|---|
| Training application processing time | Reduced from days to same-day |
| Manual paperwork eliminated | 100% for registration and approval processes |
| Teacher training history accessibility | Available anytime via dashboard |
| User adoption rate (teachers) | ≥ 80% within first semester of deployment |
| System uptime | ≥ 99% during operating hours |

---

## 12. Open Integration Decisions (To Be Resolved in Phase 2)

The following decisions are deferred until the external system's architecture is known. They are documented here so they are not overlooked.

| # | Decision | Impact |
|---|---|---|
| D-01 | Which entities are mastered externally: TEACHER only, TEACHER + PRINCIPAL, or also USER auth? | Determines which repository implementations to build as adapters |
| D-02 | Integration method: REST API, direct DB read, or message queue/event stream? | Determines the adapter implementation strategy |
| D-03 | Authentication federation: shared credentials, SSO/OAuth, or independent? | Affects `AuthenticatedSessionController` and session management |
| D-04 | External ID format: what identifier does the external system use for staff? | Determines `external_id` column format and mapping logic |
| D-05 | Does the external system also record training or CPD activities? | Affects cumulative LADAP hours calculation (DR-03) |
| D-06 | Sync direction: read-only from external, bidirectional, or event-driven? | Determines whether EduTrain writes back to the external system |
| D-07 | Outsider data: does the external system need visibility into outsider participants? | Determines if Outsider records need to be pushed to external system |

---

## 13. References

1. Laudon, K. C., & Laudon, J. P. (2020). *Management Information Systems*. Pearson.
2. Pressman, R. S. (2014). *Software Engineering: A Practitioner's Approach*. McGraw-Hill.
3. Sommerville, I. (2016). *Software Engineering* (10th ed.). Pearson.
4. Kendall, K. E., & Kendall, J. E. (2019). *Systems Analysis and Design*. Pearson.
5. Dennis, A., Wixom, B. H., & Roth, R. M. (2018). *Systems Analysis and Design*. Wiley.
6. Welling, L., & Thomson, L. (2017). *PHP and MySQL Web Development*. Addison-Wesley.
7. Laravel Documentation. (2025). https://laravel.com/docs
8. Elmasri, R., & Navathe, S. (2016). *Fundamentals of Database Systems*. Pearson.
9. Turban, E., Pollard, C., & Wood, G. (2018). *Information Technology for Management*. Wiley.
10. ISO. (2011). *Systems and software engineering – System life cycle processes* (ISO/IEC 12207). https://www.iso.org/standard/63712.html

---

*This PRD was derived from the PSM Proposal submitted by Izzhilmy Bin Shamsul Bahri, UTeM FTMK, Session 2025/2026.*
