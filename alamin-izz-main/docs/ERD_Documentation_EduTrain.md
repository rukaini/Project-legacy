# Entity-Relationship Diagram (ERD) Documentation
## Educator Training Management System (EduTrain System)

**Project Code:** BITU 3973  
**Version:** 1.1  
**Student:** Izzhilmy Bin Shamsul Bahri (B032420045)  
**Institution:** Universiti Teknikal Malaysia Melaka (UTeM) — FTMK  
**Session:** 2025/2026, Semester 2  

> **Version 1.1 Change Note:** Added OUTSIDER, NOTIFICATION, FEEDBACK, AUDIT_LOG entities. Fixed APPLICATION primary key design. Added `capacity`, `status`, and LADAP metadata columns to TRAINING. Added `training_id` FK to ATTENDANCE. Added `external_id` to USER, TEACHER, PRINCIPAL for integration. Added Section 6 (Integration Boundary). Updated Section 5 (Gaps) to reflect resolved items and remaining open decisions.

---

## 1. ERD Overview

The ERD for the EduTrain System consists of **14 entities** that collectively represent the data structure required to manage LADAP training activities for Al Amin Edu Oasis. The design separates authentication concerns (USER) from role-specific profiles (TEACHER, PRINCIPAL, OUTSIDER), and centralizes training participation through the APPLICATION entity as the core junction table.

### Entity Ownership Classification

Entities are classified by data ownership to support the planned cross-system integration (see PRD Section 6.3). Entities marked as *potentially external* are currently implemented as local tables but are designed to be backed by an external repository adapter when the integration is defined.

| Entity | Ownership | Notes |
|---|---|---|
| USER | EduTrain | Auth layer; `external_id` column reserved for future SSO reconciliation |
| TEACHER | EduTrain (Phase 1) — *potentially external* | Backed by `LocalTeacherRepository`; adapter-ready |
| PRINCIPAL | EduTrain (Phase 1) — *potentially external* | Backed by `LocalPrincipalRepository`; adapter-ready |
| OUTSIDER | EduTrain (permanent) | Self-registered; never sourced externally |
| TRAINING | EduTrain | Fully owned |
| APPLICATION | EduTrain | Fully owned |
| ATTENDANCE\_SESSION | EduTrain | Fully owned |
| ATTENDANCE\_RECORD | EduTrain | Fully owned |
| TASK | EduTrain | Fully owned |
| TASK\_SUBMISSION | EduTrain | Fully owned |
| QUIZ / QUIZ\_QUESTION / QUIZ\_OPTION | EduTrain | Fully owned |
| ANSWER\_SHEET / ANSWER\_RESPONSE | EduTrain | Fully owned |
| NOTIFICATION | EduTrain | Fully owned |
| FEEDBACK | EduTrain | Fully owned |
| AUDIT\_LOG | EduTrain | Fully owned |

---

## 2. Entities & Attributes

### 2.1 USER

The base authentication entity shared by all system users.

| Attribute | Key | Type | Description |
|---|---|---|---|
| UserID | PK | INT | Unique identifier for every system user |
| Name | — | VARCHAR | Full name of the user |
| Email | — | VARCHAR | Login email address (unique) |
| Password | — | VARCHAR | Hashed password |
| Role | — | ENUM | Defines access level: `guru_besar`, `teacher`, `outsider` |
| ExternalID | — | VARCHAR (nullable) | Identifier assigned by the external system for synced staff accounts. `NULL` for self-registered Outsiders. Used for ID reconciliation in Phase 2 integration only. |

---

### 2.2 TEACHER

Stores school-staff-specific profile data for users with the Teacher role.

> **Integration Note:** This entity is owned by EduTrain in Phase 1 and backed by `LocalTeacherRepository`. If the external system is confirmed as the master of staff records in Phase 2, this table becomes a local cache populated by the `ExternalTeacherRepository` adapter. The entity structure and attribute names remain unchanged — the DTO contract is the stable interface between this entity and the rest of the application.

| Attribute | Key | Type | Description |
|---|---|---|---|
| TeacherID | PK | INT | Unique identifier for the teacher |
| Name | — | VARCHAR | Teacher's full name |
| Department | — | VARCHAR | Department the teacher belongs to |
| Position | — | VARCHAR | Job position/designation |
| UserID | FK | INT | References USER(UserID) |
| ExternalID | — | VARCHAR (nullable) | External system's staff identifier for this teacher. Used for sync reconciliation. `NULL` if not synced. |

---

### 2.3 PRINCIPAL

Stores profile data specific to the Guru Besar (Principal) role.

> **Integration Note:** Same adapter-readiness note as TEACHER (Section 2.2). Backed by `LocalPrincipalRepository` in Phase 1.

| Attribute | Key | Type | Description |
|---|---|---|---|
| PrincipalID | PK | INT | Unique identifier for the principal |
| Name | — | VARCHAR | Principal's full name |
| Phone_No | — | VARCHAR | Contact phone number |
| Department | — | VARCHAR | Department assigned |
| UserID | FK | INT | References USER(UserID) |
| ExternalID | — | VARCHAR (nullable) | External system's staff identifier. Used for sync reconciliation. |

---

### 2.4 OUTSIDER

Stores profile data specific to external (non-staff) participants who self-register on the platform.

> **Previously identified as Gap #1 in ERD version 1.0.** Added to mirror the TEACHER/PRINCIPAL profile table design and to clearly differentiate outsider records from internal staff records in all reports (DR-13). Outsider data is permanently owned by EduTrain and will never be sourced from or transmitted to the external system (DR-INT-04).

| Attribute | Key | Type | Description |
|---|---|---|---|
| OutsiderID | PK | INT | Unique identifier for the outsider |
| Name | — | VARCHAR | Outsider's full name |
| Organization | — | VARCHAR (nullable) | Organization or institution the outsider represents |
| Contact_No | — | VARCHAR (nullable) | Contact phone number |
| UserID | FK | INT | References USER(UserID) |

---

### 2.5 TRAINING

Represents each LADAP training program offered by the institution.

> **Updated from version 1.0:** Added `Capacity`, `Status`, `Time`, `RegistrationDeadline`, `Category`, `TrainerName`, `DurationHours`, `CompetencyArea`, and `IsPublic` columns. The original entity was missing status and capacity (Gap #3), the LADAP metadata required by DR-02 and DR-08, and the outsider-visibility flag required by DR-11.

| Attribute | Key | Type | Description |
|---|---|---|---|
| TrainingID | PK | INT | Unique identifier for the training program |
| Title | — | VARCHAR | Name/title of the training |
| Date | — | DATE | Scheduled date of the training |
| Time | — | TIME | Scheduled start time |
| Location | — | VARCHAR | Venue of the training |
| Description | — | TEXT | Details and objectives of the training |
| Capacity | — | INT | Maximum number of participants allowed |
| Status | — | ENUM | Program lifecycle state: `open`, `closed`, `completed` |
| RegistrationDeadline | — | DATE | Last date on which applications are accepted |
| Category | — | VARCHAR | Training category (e.g., pedagogy, ICT, leadership, subject knowledge) |
| CompetencyArea | — | VARCHAR | LADAP competency area targeted by this training (DR-08) |
| TrainerName | — | VARCHAR | Name of the trainer or facilitator (DR-02) |
| DurationHours | — | DECIMAL | Duration of the training in hours, used for LADAP hours accumulation (DR-02, DR-03) |
| IsPublic | — | BOOLEAN | Whether Outsiders (external participants) may apply for this training (DR-11) |
| UserID | FK | INT | References USER(UserID) — the Guru Besar who created this training |

---

### 2.6 APPLICATION

The central junction entity linking Users (Teachers and Outsiders) to Training programs, capturing the full participation record including approval status and rejection reason.

> **Updated from version 1.0:** Primary key corrected to `(UserID, TrainingID)` only. `SubmissionID` and `SheetID` were incorrectly included in the composite PK in the original design (Gap #2) — they are FK references only. Task submissions and answer sheets are now linked to the participant's `(UserID, TrainingID)` pair directly on their respective tables, not embedded in the APPLICATION PK. `UserID` replaces `TeacherID` to allow Outsiders to apply as well. Added `RejectionReason` column.

| Attribute | Key | Type | Description |
|---|---|---|---|
| ApplicationID | PK | INT | Surrogate primary key |
| UserID | UK, FK1 | INT | References USER(UserID) — the applicant (teacher or outsider) |
| TrainingID | UK, FK2 | INT | References TRAINING(TrainingID) |
| Status | — | ENUM | Application state: `pending`, `approved`, `rejected` |
| RejectionReason | — | TEXT (nullable) | Optional reason entered by Guru Besar when rejecting |
| AppliedAt | — | TIMESTAMP | When the application was submitted |

> **Unique constraint:** `(UserID, TrainingID)` — prevents duplicate applications (FR-18).

---

### 2.7 ATTENDANCE\_SESSION

Represents an attendance check-in session associated with a specific training program. Each session is identified by a unique, time-limited QR token.

> **Design Decision Note:** QR-based check-in is not described in the original PSM proposal. The mechanism was introduced during ERD modelling and should be confirmed with the supervisor. See original rationale in Section 4.3.

> **Updated from version 1.0:** Entity renamed from `ATTENDANCE` to `ATTENDANCE_SESSION` to distinguish it clearly from `ATTENDANCE_RECORD`. Added `TrainingID` FK (Gap #6 from version 1.0 — the session was previously unlinked from its parent training). Added `ExpiresAt` to replace the implicit 30-minute window with an explicit, stored timestamp.

| Attribute | Key | Type | Description |
|---|---|---|---|
| AttendanceSessionID | PK | INT | Unique identifier for the attendance session |
| TrainingID | FK | INT | References TRAINING(TrainingID) — links this session to its training program |
| QRToken | — | VARCHAR | Unique, cryptographically random token generated per session *(design decision)* |
| ExpiresAt | — | TIMESTAMP | The exact timestamp after which the QR token is invalid (e.g., 30 minutes from generation) |
| Date | — | DATE | Date of the attendance session |

---

### 2.8 ATTENDANCE\_RECORD

A junction entity that records whether a specific participant (teacher or outsider) was present at a specific attendance session.

> **Updated from version 1.0:** `TeacherID` replaced with `UserID` to allow Outsider attendance to be recorded in the same table. `AttendanceID` updated to reference `ATTENDANCE_SESSION(AttendanceSessionID)`. `CheckedInAt` timestamp added to replace the generic `Date` column with a precise check-in time.

| Attribute | Key | Type | Description |
|---|---|---|---|
| AttendanceRecordID | PK | INT | Surrogate primary key |
| UserID | UK, FK1 | INT | References USER(UserID) — the participant |
| AttendanceSessionID | UK, FK2 | INT | References ATTENDANCE_SESSION(AttendanceSessionID) |
| Status | — | ENUM | Attendance outcome: `present`, `absent`, `late` |
| CheckedInAt | — | TIMESTAMP (nullable) | Exact timestamp of QR scan. `NULL` for system-generated `absent` records. |

> **Unique constraint:** `(UserID, AttendanceSessionID)` — prevents duplicate attendance marking (FR-23).

---

### 2.9 TASK

Defines an assignment or work task created by the Guru Besar and linked to a training program.

> **New entity in version 1.1.** The original ERD referenced TASK_SUBMISSION but had no parent TASK entity to define the assignment itself.

| Attribute | Key | Type | Description |
|---|---|---|---|
| TaskID | PK | INT | Unique identifier for the task |
| TrainingID | FK | INT | References TRAINING(TrainingID) |
| Title | — | VARCHAR | Task title |
| Description | — | TEXT | Instructions for the task |
| Deadline | — | DATETIME | Submission cutoff — submissions after this point are rejected (FR-T04) |
| AcceptedFileTypes | — | VARCHAR | Comma-separated accepted MIME types (e.g., pdf, docx, jpg) |

---

### 2.10 TASK\_SUBMISSION

Stores the assignment submitted by a participant for a specific task.

> **Updated from version 1.0:** Added `UserID` and `TaskID` FKs (the original entity had no FKs, making it unreachable without the APPLICATION PK design). Added `Status` enum and `Grade` column. `SubmittedAt` timestamp replaces generic `Date`.

| Attribute | Key | Type | Description |
|---|---|---|---|
| SubmissionID | PK | INT | Unique identifier for the submission |
| UserID | FK | INT | References USER(UserID) — the submitting participant |
| TaskID | FK | INT | References TASK(TaskID) |
| TextAnswer | — | TEXT (nullable) | Written answer or response |
| FilePath | — | VARCHAR (nullable) | Server path to the uploaded file |
| SubmittedAt | — | TIMESTAMP | When the submission was made |
| Status | — | ENUM | `not_submitted`, `submitted`, `graded` |
| Grade | — | VARCHAR (nullable) | Grade or mark assigned by the Guru Besar |

---

### 2.11 QUIZ

Defines an online quiz created by the Guru Besar and linked to a training program.

> **New entity in version 1.1.** Required to support FR-T03, M06, and the ANSWER_SHEET entity.

| Attribute | Key | Type | Description |
|---|---|---|---|
| QuizID | PK | INT | Unique identifier for the quiz |
| TrainingID | FK | INT | References TRAINING(TrainingID) |
| Title | — | VARCHAR | Quiz title |
| Instructions | — | TEXT (nullable) | Instructions shown to participants |
| TimeLimit | — | INT (nullable) | Time limit in minutes. `NULL` means no limit. |
| AvailableFrom | — | DATETIME | Earliest time participants may start the quiz |
| Deadline | — | DATETIME | Latest time participants may submit |

---

### 2.12 QUIZ\_QUESTION

Stores individual questions belonging to a quiz.

| Attribute | Key | Type | Description |
|---|---|---|---|
| QuestionID | PK | INT | Unique identifier for the question |
| QuizID | FK | INT | References QUIZ(QuizID) |
| QuestionText | — | TEXT | The question prompt |
| Type | — | ENUM | `mcq` (multiple choice) or `short_answer` |
| DisplayOrder | — | INT | Order in which the question appears |

---

### 2.13 QUIZ\_OPTION

Stores answer options for multiple-choice questions.

| Attribute | Key | Type | Description |
|---|---|---|---|
| OptionID | PK | INT | Unique identifier for the option |
| QuestionID | FK | INT | References QUIZ_QUESTION(QuestionID) |
| OptionText | — | VARCHAR | The answer option text |
| IsCorrect | — | BOOLEAN | Whether this option is the correct answer (used for auto-grading) |

---

### 2.14 ANSWER\_SHEET

Stores the quiz attempt record for a participant. One row per participant per quiz.

> **Updated from version 1.0:** Added `UserID` and `QuizID` FKs. Replaced generic `Answer TEXT` with a reference to ANSWER_RESPONSE rows (see 2.15) so individual question responses can be stored, graded, and reviewed independently. Added `TotalScore` and `SubmittedAt`.

| Attribute | Key | Type | Description |
|---|---|---|---|
| SheetID | PK | INT | Unique identifier for the answer sheet |
| UserID | FK | INT | References USER(UserID) |
| QuizID | FK | INT | References QUIZ(QuizID) |
| TotalScore | — | DECIMAL (nullable) | Computed total score. Populated after auto-grading MCQ; adjusted manually for short-answer. |
| SubmittedAt | — | TIMESTAMP | When the quiz was submitted |

> **Unique constraint:** `(UserID, QuizID)` — one attempt per participant per quiz.

---

### 2.15 ANSWER\_RESPONSE

Stores the individual answer a participant gave for each question in their quiz attempt.

> **New entity in version 1.1.** Replaces the opaque `Answer TEXT` blob in the original ANSWER_SHEET, enabling per-question review and manual short-answer scoring (M06-F03).

| Attribute | Key | Type | Description |
|---|---|---|---|
| ResponseID | PK | INT | Unique identifier for the response |
| SheetID | FK | INT | References ANSWER_SHEET(SheetID) |
| QuestionID | FK | INT | References QUIZ_QUESTION(QuestionID) |
| SelectedOptionID | FK (nullable) | INT | References QUIZ_OPTION(OptionID) — populated for MCQ answers |
| TextAnswer | — | TEXT (nullable) | Free-text response — populated for short_answer questions |

---

### 2.16 NOTIFICATION

Stores in-app notification records delivered to users on system events.

> **New entity in version 1.1** (Gap #4 from version 1.0). Required by FR-22 through FR-39 and M08.

| Attribute | Key | Type | Description |
|---|---|---|---|
| NotificationID | PK | INT | Unique identifier for the notification |
| UserID | FK | INT | References USER(UserID) — the recipient |
| Message | — | TEXT | Human-readable notification content |
| Type | — | VARCHAR | Event type key (e.g., `application_approved`, `new_training`, `task_deadline`) — used to build redirect URLs |
| URL | — | VARCHAR (nullable) | Deep-link to the relevant page in the system |
| IsRead | — | BOOLEAN | `false` until the user views the notification centre |
| CreatedAt | — | TIMESTAMP | When the notification was generated |

---

### 2.17 FEEDBACK

Stores post-training feedback submitted by participants after a training is marked `completed`.

> **New entity in version 1.1** (Gap #5 from version 1.0). Required by FR-40 through FR-43 and M09.

| Attribute | Key | Type | Description |
|---|---|---|---|
| FeedbackID | PK | INT | Unique identifier for the feedback record |
| UserID | FK | INT | References USER(UserID) — the participant submitting feedback |
| TrainingID | FK | INT | References TRAINING(TrainingID) |
| Rating | — | TINYINT | Star rating from 1 to 5 |
| Comment | — | TEXT (nullable) | Optional written feedback |
| SubmittedAt | — | TIMESTAMP | When feedback was submitted |

> **Unique constraint:** `(UserID, TrainingID)` — one feedback submission per participant per training (FR-42).

---

### 2.18 AUDIT\_LOG

Records a tamper-evident log of key system actions for accountability and PDPA compliance.

> **New entity in version 1.1.** Required by DR-16. Captures approvals, rejections, attendance marking, and other consequential actions.

| Attribute | Key | Type | Description |
|---|---|---|---|
| LogID | PK | INT | Unique identifier for the log entry |
| UserID | FK (nullable) | INT | References USER(UserID) — the actor. `NULL` for system-automated actions (e.g., auto-mark absent). |
| Action | — | VARCHAR | Action key (e.g., `application_approved`, `attendance_marked`, `training_cancelled`) |
| TargetType | — | VARCHAR | The entity type acted upon (e.g., `Application`, `AttendanceRecord`) |
| TargetID | — | INT | The PK of the entity acted upon |
| Metadata | — | JSON (nullable) | Additional context (e.g., rejection reason, old vs new status) |
| CreatedAt | — | TIMESTAMP | When the action occurred |

---

## 3. Entity Relationships

### 3.1 Relationship Summary Table

> Updated in version 1.1 to reflect all new and corrected entities.

| Relationship | Entities Involved | Cardinality | Description |
|---|---|---|---|
| has profile | USER — TEACHER | 1 : 1 | One USER account maps to one TEACHER profile |
| has profile | USER — PRINCIPAL | 1 : 1 | One USER account maps to one PRINCIPAL profile |
| has profile | USER — OUTSIDER | 1 : 1 | One USER account maps to one OUTSIDER profile |
| creates | USER (principal) — TRAINING | 1 : N | A Guru Besar can create many training programs |
| applies to | USER — APPLICATION | 1 : N | A user (teacher or outsider) can have many applications |
| receives | TRAINING — APPLICATION | 1 : N | A training can have many applications |
| has | TRAINING — ATTENDANCE\_SESSION | 1 : N | A training can have multiple attendance sessions (e.g., multi-day) |
| has | ATTENDANCE\_SESSION — ATTENDANCE\_RECORD | 1 : N | One session has one record per participant |
| records | USER — ATTENDANCE\_RECORD | 1 : N | A user can have attendance records across many sessions |
| has | TRAINING — TASK | 1 : N | A training can have many tasks |
| submits | USER — TASK\_SUBMISSION | 1 : N | A user can submit to many tasks |
| belongs to | TASK\_SUBMISSION — TASK | N : 1 | Each submission belongs to one task |
| has | TRAINING — QUIZ | 1 : N | A training can have many quizzes |
| has | QUIZ — QUIZ\_QUESTION | 1 : N | A quiz has many questions |
| has | QUIZ\_QUESTION — QUIZ\_OPTION | 1 : N | An MCQ question has many answer options |
| has | USER — ANSWER\_SHEET | 1 : N | A user can have answer sheets across many quizzes |
| belongs to | ANSWER\_SHEET — QUIZ | N : 1 | Each answer sheet belongs to one quiz |
| has | ANSWER\_SHEET — ANSWER\_RESPONSE | 1 : N | One answer sheet has one response per question |
| receives | USER — NOTIFICATION | 1 : N | A user receives many notifications |
| submits | USER — FEEDBACK | 1 : N | A user can submit feedback for many trainings |
| about | FEEDBACK — TRAINING | N : 1 | Each feedback record is about one training |
| recorded by | AUDIT\_LOG — USER | N : 1 | Many log entries can reference the same actor |

---

### 3.2 ASCII Entity-Relationship Diagram

> Diagram updated in version 1.1 to reflect all new entities and corrected relationships.

```
Legend:
  ||----||   One-to-One (1:1)
  ||----{    One-to-Many (1:N)
  PK         Primary Key
  FK         Foreign Key
  - - - -    Relationship line

+------------------+          +---------------------+
|      USER        |          |      TEACHER         |
+------------------+          +---------------------+
| PK  UserID       |1        1| PK  TeacherID        |
|     Name         |----------+|     Name             |
|     Email        |  (have)   |     Department       |
|     Password     |           |     Position         |
|     Role         |           | FK  UserID           |
+------------------+           +---------------------+
        |                               |
        | (have)                        | (apply)
        | 1:1                           | 1:N
        |                               |
+------------------+                   |
|    PRINCIPAL     |                   |
+------------------+                   v
| PK  PrincipalID  |      +------------------------------+
|     Name         |      |         APPLICATION           |
|     Phone_No     |      +------------------------------+
|     Department   |      | PK,FK1  TeacherID            |
| FK  UserID       |      | PK,FK2  TrainingID           |
+------------------+      |    FK   SubmissionID         |
        |                 |    FK   SheetID              |
        | (have)          |         Status               |
        | 1:N             |   (pending/approved/rejected)|
        |                 +------------------------------+
        v                      |          |
+------------------+           |          |
|     TRAINING     |           | (have)   | (have)
+------------------+           | 1:1      | 1:1
| PK  TrainingID   |           |          |
|     Title        |           v          v
|     Date         | +------------------+ +------------------+
|     Location     | | TASK_SUBMISSION  | |  ANSWER_SHEET    |
|     Description  | +------------------+ +------------------+
| FK  UserID       | | PK  SubmissionID | | PK  SheetID      |
+------------------+ |     Answer       | |     Answer       |
        |            |     Date         | |     Date         |
        | (have)     |     File_path    | |     Description  |
        | 1:N        |     Description  | +------------------+
        |            +------------------+
        |
        | (1:N via APPLICATION)
        |
        +---> [linked through APPLICATION.TrainingID]


+---------------------+            +---------------------+
|     TEACHER         |            |  ATTENDANCE RECORD  |
+---------------------+            +---------------------+
| PK  TeacherID       |1          N| PK,FK1 TeacherID    |
|     ...             |------------+| PK,FK2 AttendanceID |
+---------------------+ (submit    |        Status        |
                         attend.)  |        Date          |
                                   +---------------------+
                                            |
                                            | (have)
                                            | N:1
                                            |
                                   +---------------------+
                                   |     ATTENDANCE      |
                                   +---------------------+
                                   | PK  AttendanceID    |
                                   |     QR  (*)         |
                                   |     Date            |
                                   +---------------------+

  (*) Design Decision: QR attribute is not stated in the original
      proposal. It was introduced during ERD modelling to support
      QR-based check-in. Confirm with supervisor before implementation.
```

#### Full Entity Map (Compact Overview — v1.1)

```
Authentication & Profiles
─────────────────────────────────────────────────────────────────
+----------+  1:1  +-----------+      [*] = potentially external
|  USER    |------>| TEACHER*  |          (adapter-backed in Phase 2)
+----------+  1:1  +-----------+
     |         1:1  +-----------+
     |-------->     | PRINCIPAL*|
     |              +-----------+
     |         1:1  +-----------+
     +-------->     | OUTSIDER  |
                    +-----------+

Training & Applications
─────────────────────────────────────────────────────────────────
USER (principal) --1:N--> TRAINING --1:N--> APPLICATION <--N:1-- USER
                               |                                 (teacher/outsider)
                               |--1:N--> ATTENDANCE_SESSION --1:N--> ATTENDANCE_RECORD <--N:1-- USER
                               |
                               |--1:N--> TASK --1:N--> TASK_SUBMISSION <--N:1-- USER
                               |
                               +--1:N--> QUIZ --1:N--> QUIZ_QUESTION --1:N--> QUIZ_OPTION
                                              |
                                              +--1:N--> ANSWER_SHEET <--N:1-- USER
                                                              |
                                                              +--1:N--> ANSWER_RESPONSE

Notifications, Feedback & Audit
─────────────────────────────────────────────────────────────────
USER --1:N--> NOTIFICATION
USER --1:N--> FEEDBACK --N:1--> TRAINING
USER --1:N--> AUDIT_LOG  (UserID nullable for system-automated entries)
```

---

## 4. Design Justification

### 4.1 Separation of USER and Role Profiles (TEACHER, PRINCIPAL, OUTSIDER)

The USER entity is kept as a standalone authentication table holding only login credentials and role information. Role-specific data (department, position, phone number, organization) is stored in separate TEACHER, PRINCIPAL, and OUTSIDER tables, each linked back to USER via a foreign key.

**Justification:** This follows the principle of separation of concerns and is the critical seam for cross-system integration. Because profile data is isolated in its own table, it can be read from a local repository in Phase 1 and from an external repository adapter in Phase 2 without changing the USER authentication table or any other entity. The `ExternalID` nullable column on TEACHER and PRINCIPAL provides the reconciliation key for syncing without polluting the USER table's authentication purpose. Adding OUTSIDER as a dedicated profile table (rather than a nullable column on USER) mirrors the TEACHER/PRINCIPAL design and ensures outsider records are unambiguously differentiated in all queries and reports (DR-13).

---

### 4.2 APPLICATION as the Approval Junction Entity

The APPLICATION entity is the approval record linking a USER (teacher or outsider) to a TRAINING program. Its primary key is a surrogate `ApplicationID` with a unique constraint on `(UserID, TrainingID)`.

**Justification (revised from v1.0):** The original design embedded `SubmissionID` and `SheetID` as part of the composite primary key, which was incorrect — it would require a submission or answer sheet to exist before an application could be created, and would prevent a participant from having more than one task submission per training. The corrected design keeps APPLICATION as a lightweight approval record. TASK_SUBMISSION rows reference `(UserID, TaskID)` directly, and ANSWER_SHEET rows reference `(UserID, QuizID)` directly. Participation eligibility (approved application check) is enforced at the service layer, not via FK embedding in APPLICATION. This is cleaner, avoids circular dependency, and correctly models the domain: approval is granted first, submissions follow independently.

---

### 4.3 Separate ATTENDANCE and ATTENDANCE RECORD Entities

The ATTENDANCE entity represents a training session's check-in event identified by a QR token, while ATTENDANCE RECORD captures each teacher's individual check-in response to that session.

> **Design Decision Note:** QR-based attendance is not described in the original PSM proposal. The proposal only states that participants will "attend their attendance." The QR attribute was introduced during ERD design as an implementation-level decision inferred from the `QR` field present in the submitted ERD diagram. It is documented here transparently as a design decision, not a proposal requirement.

**Justification:** This two-table design correctly models a many-to-many relationship between teachers and attendance sessions. One attendance session (e.g., Day 1 Morning of Training X) can have records for 30 teachers. Merging them into one table would result in repeated QR and session data for every teacher, violating the principle of normalization (1NF/2NF). The QR token approach was selected over manual marking because it is faster to execute during a live training session, eliminates proxy attendance risk, and creates a timestamped, verifiable record without requiring administrator intervention for each check-in.

---

### 4.4 Separate TASK / TASK\_SUBMISSION and QUIZ / ANSWER\_SHEET Entity Groups

Tasks and quizzes each have a definition entity (TASK, QUIZ) and a response entity (TASK\_SUBMISSION, ANSWER\_SHEET). Quizzes are further broken down into QUIZ\_QUESTION, QUIZ\_OPTION, and ANSWER\_RESPONSE.

**Justification:** Tasks and quizzes are fundamentally different in structure and lifecycle. Tasks require file/text submission reviewed by a human grader. Quizzes require structured per-question responses that can be auto-graded for MCQ. Merging them into one table would require many nullable columns, making schema integrity and query logic fragile. The ANSWER\_RESPONSE row-per-question design (replacing the opaque `Answer TEXT` blob from v1.0) enables the Guru Besar to review and adjust individual question scores, supports MCQ auto-grading, and makes quiz analytics (per-question pass rate, common wrong answers) possible without parsing a serialized blob.

---

### 4.5 TRAINING Linked to USER (Not PRINCIPAL Directly)

The TRAINING entity references UserID (from the USER table) rather than PrincipalID from the PRINCIPAL table.

**Justification:** Linking to USER rather than PRINCIPAL keeps the foreign key at the authentication layer, which is consistent with how ownership is managed in Laravel (where the authenticated user's ID is used). It also means that if administrative roles expand in the future (e.g., a vice-principal who can create training), the same foreign key remains valid without schema changes.

---

### 4.6 Status Fields in APPLICATION and ATTENDANCE RECORD

Both APPLICATION (pending/approved/rejected) and ATTENDANCE RECORD (present/absent/late) use ENUM-type Status fields.

**Justification:** Using an ENUM constrains values at the database level, preventing invalid states from being stored. It is more storage-efficient than VARCHAR and makes query filtering (e.g., `WHERE status = 'approved'`) faster and more reliable. This also aligns with the workflow logic: an application must pass through exactly these states, and no arbitrary string values should be permitted.

---

### 4.7 Normalization Assessment

The ERD broadly adheres to **Third Normal Form (3NF)**:

- **1NF:** All attributes are atomic (no repeating groups or multi-valued attributes).
- **2NF:** All non-key attributes in junction tables (APPLICATION, ATTENDANCE RECORD) are fully dependent on the composite primary key.
- **3NF:** No transitive dependencies are present — role-specific attributes live in their own tables (TEACHER, PRINCIPAL) rather than being derived from USER.

---

## 5. Design Gap Tracker

### 5.1 Resolved Gaps (v1.0 → v1.1)

| # | Gap (v1.0) | Resolution in v1.1 |
|---|---|---|
| 1 | OUTSIDER role had no dedicated profile table | Added OUTSIDER entity (Section 2.4) with `organization` and `contact_no` fields |
| 2 | APPLICATION composite PK incorrectly included SubmissionID and SheetID | Corrected to surrogate PK `ApplicationID` with unique constraint on `(UserID, TrainingID)`. SubmissionID/SheetID removed entirely — submissions reference UserID + TaskID/QuizID directly |
| 3 | TRAINING had no `capacity` or `status` fields | Added `Capacity`, `Status`, `Time`, `RegistrationDeadline`, `Category`, `CompetencyArea`, `TrainerName`, `DurationHours`, `IsPublic` (Section 2.5) |
| 4 | No NOTIFICATION entity | Added NOTIFICATION entity (Section 2.16) |
| 5 | No FEEDBACK entity | Added FEEDBACK entity (Section 2.17) |
| 6 | ATTENDANCE had no FK to TRAINING | Added `TrainingID` FK to ATTENDANCE\_SESSION (Section 2.7) |
| 7 | No TASK definition entity (only TASK\_SUBMISSION existed) | Added TASK entity (Section 2.9) |
| 8 | No QUIZ, QUIZ\_QUESTION, QUIZ\_OPTION entities | Added all three (Sections 2.11–2.13) |
| 9 | ANSWER\_SHEET used opaque `Answer TEXT` blob | Replaced with ANSWER\_RESPONSE entity (Section 2.15) — one row per question per attempt |
| 10 | No AUDIT\_LOG entity | Added AUDIT\_LOG entity (Section 2.18) |
| 11 | TeacherID used as FK in APPLICATION and ATTENDANCE RECORD, excluding Outsiders | Replaced with `UserID` throughout so both Teachers and Outsiders can apply and attend |
| 12 | No `ExternalID` column for integration reconciliation | Added `ExternalID` nullable column to USER, TEACHER, PRINCIPAL |

### 5.2 Open Decisions (Deferred to Phase 2)

| # | Open Item | Impact |
|---|---|---|
| INT-1 | Are TEACHER and PRINCIPAL records mastered in the external system? | If yes, these tables become local cache; `ExternalTeacherRepository` replaces `LocalTeacherRepository` via IoC binding |
| INT-2 | What is the external system's staff identifier format? | Determines `ExternalID` column length and index strategy |
| INT-3 | Is authentication federated (SSO) or independent? | If SSO, `AuthenticatedSessionController` adapts; USER table `password` column becomes nullable for SSO-only accounts |
| INT-4 | Does the external system also record training/CPD activities? | If yes, cumulative LADAP hours (DR-03) require a Phase 2 aggregation query across both systems |
| INT-5 | Is data sync one-directional (read from external) or bidirectional? | If bidirectional, a write-back adapter is needed and conflict resolution rules must be defined |

---

## 6. Integration Boundary

This section defines the ERD-level boundary between EduTrain's local schema and the external system. It serves as the reference for Phase 2 adapter development.

### 6.1 EduTrain System Boundary Diagram

```
╔══════════════════════════════════════════════════════════════════╗
║                     EduTrain System Boundary                     ║
║                                                                  ║
║  +----------+   +------------+   +-----------+   +-----------+  ║
║  | USER     |   | OUTSIDER   |   | TRAINING  |   | APPLICATION| ║
║  +----------+   +------------+   +-----------+   +-----------+  ║
║                                                                  ║
║  +------------------+  +--------+  +------+  +--------+        ║
║  | ATTENDANCE_      |  | TASK   |  | QUIZ |  | NOTIF. |        ║
║  | SESSION + RECORD |  | + SUB. |  | + AS |  +--------+        ║
║  +------------------+  +--------+  +------+                     ║
║                                                                  ║
║  +-----------+   +------------+                                  ║
║  | FEEDBACK  |   | AUDIT_LOG  |                                  ║
║  +-----------+   +------------+                                  ║
║                                                                  ║
║  - - - - - - - - - - - Integration Seam - - - - - - - - - - -   ║
║                                                                  ║
║  +----------+   +------------+   (adapter-backed, Phase 1: local)║
║  | TEACHER* |   | PRINCIPAL* |   (Phase 2: ExternalRepository)  ║
║  +----------+   +------------+                                   ║
║                          │                                       ║
╚══════════════════════════│═══════════════════════════════════════╝
                           │  Repository Interface (DTO contract)
                           ▼
              ╔════════════════════════╗
              ║   External System      ║
              ║   (schema TBD)         ║
              ║                        ║
              ║   ? Staff records      ║
              ║   ? Auth provider      ║
              ║   ? CPD records        ║
              ╚════════════════════════╝
```

### 6.2 Integration Seam Rules

These rules govern what crosses the integration boundary and how:

| Rule | Description |
|---|---|
| **No direct table access** | EduTrain code must never reference an external system's table name or column directly. All access goes through a repository interface. |
| **DTO is the contract** | Data crossing the boundary is represented as a DTO (`TeacherDTO`, `PrincipalDTO`). The DTO schema is EduTrain's contract — the external system must map to it, not vice versa. |
| **Read-only by default** | EduTrain reads staff profile data from the external system. It does not write back unless a bidirectional sync is explicitly designed and approved (Phase 2 open decision INT-5). |
| **ExternalID is the join key** | The `ExternalID` column on USER, TEACHER, and PRINCIPAL is the only permitted reference to an external system identifier. It is opaque to all application logic except the repository adapter. |
| **Fail-safe isolation** | A failure in the integration layer must not propagate to EduTrain's core operations. Repository calls are wrapped with timeouts and fallback to local cache. |

---

*Document prepared based on ERD diagram submitted by Izzhilmy Bin Shamsul Bahri, UTeM FTMK, Session 2025/2026.*  
*Updated to version 1.1 with integration architecture, corrected entity design, and new entities.*
