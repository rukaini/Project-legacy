# Requirements Specification
## Educator Training Management System (EduTrain System)

**Project Code:** BITU 3973  
**Version:** 1.1  
**Student:** Izzhilmy Bin Shamsul Bahri (B032420045)  
**Institution:** Universiti Teknikal Malaysia Melaka (UTeM) — FTMK  
**Session:** 2025/2026, Semester 2  

> **Version 1.1 Change Note:** Added Section 1.10 (Integration Requirements), Section 2.7 (Integration NFRs), Section 3.6 (Integration Domain Requirements). Updated FR-02 to reflect external system context. Added duplicate-ID numbering fix in Section 1.4/1.5.

---

## 1. Functional Requirements

Functional requirements define the specific behaviours and functions the system must perform.

---

### 1.1 User Authentication & Account Management

| ID | Requirement |
|---|---|
| FR-01 | The system shall allow Outsiders to self-register an account using their name, email, and password. |
| FR-02 | The system shall allow Teachers to be pre-registered by the System Admin. In Phase 1, the Admin creates Teacher accounts manually. In Phase 2, if the external system is integrated, Teacher accounts may be created or synced via the external repository adapter without changing this requirement's intent. |
| FR-03 | The system shall authenticate users based on their registered email and password. |
| FR-04 | The system shall enforce role-based login, distinguishing between Guru Besar, Teacher, and Outsider. |
| FR-05 | The system shall allow users to reset their password via a verified email link. |
| FR-06 | The system shall allow users to update their profile information (name, contact, password). |

---

### 1.2 Training Program Management

| ID | Requirement |
|---|---|
| FR-07 | The system shall allow the Guru Besar to create new training programs with details including title, description, date, time, location, and maximum capacity. |
| FR-08 | The system shall allow the Guru Besar to edit or cancel existing training programs. |
| FR-09 | The system shall display a list of all available training programs to all user roles. |
| FR-10 | The system shall allow filtering and searching of training programs by date, title, or status. |
| FR-11 | The system shall assign a status to each training program: `Open`, `Closed`, or `Completed`. |
| FR-12 | The system shall automatically update training status to `Closed` when capacity is reached or the registration deadline passes. |

---

### 1.3 Training Application & Approval Workflow

| ID | Requirement |
|---|---|
| FR-13 | The system shall allow Teachers and Outsiders to submit an application to join an available training program. |
| FR-14 | The system shall allow the Guru Besar to select and assign specific Teachers to a training program directly (push assignment). |
| FR-15 | The system shall allow the Guru Besar to view all pending training applications. |
| FR-16 | The system shall allow the Guru Besar to approve or reject each training application with an optional reason. |
| FR-17 | The system shall update the application status upon Guru Besar's action: `Pending`, `Approved`, or `Rejected`. |
| FR-18 | The system shall prevent duplicate applications from the same user for the same training program. |

---

### 1.4 Attendance Management

> **Design Decision Note:** The original PSM proposal states only that participants will "attend their attendance." The QR-based check-in mechanism described in the requirements below is a design decision made during system modelling, inferred from the `QR` attribute in the submitted ERD diagram. It is not explicitly required by the proposal text and should be confirmed with the project supervisor.

| ID | Requirement |
|---|---|
| FR-19 | The system shall allow the Guru Besar to generate a unique, time-limited QR token for each training attendance session. *(Design decision — QR mechanism not stated in proposal)* |
| FR-20 | The system shall allow approved participants (Teachers and Outsiders) to mark their attendance by scanning or entering the QR token within the active attendance window. |
| FR-21 | The system shall record the timestamp of each attendance check-in. |
| FR-22 | The system shall reject attendance attempts made outside the defined active time window for a session. |
| FR-23 | The system shall prevent a participant from marking attendance more than once for the same session. |
| FR-24 | The system shall automatically mark participants as `Absent` if no attendance record is found once the attendance window closes. |
| FR-25 | The system shall allow the Guru Besar to view the attendance report for any training program. |
| FR-26 | The system shall display each participant's attendance status: `Present`, `Absent`, or `Late`. |

---

### 1.5 Task & Quiz Management

> **Numbering note:** FR-24 through FR-29 below were originally duplicated from Section 1.4. They are renumbered here as FR-T01 through FR-T06 to eliminate ambiguity. Final sequential IDs will be assigned in the next document revision.

| ID | Requirement |
|---|---|
| FR-T01 | The system shall allow the Guru Besar or Trainer to create tasks and quizzes linked to a specific training program. |
| FR-T02 | The system shall support task submission via file upload (PDF, DOCX, images) or text input. |
| FR-T03 | The system shall support online quiz answering with multiple-choice or short-answer question types. |
| FR-T04 | The system shall enforce task and quiz submission deadlines and prevent late submissions. |
| FR-T05 | The system shall track submission status per participant: `Not Submitted`, `Submitted`, or `Graded`. |
| FR-T06 | The system shall allow the Guru Besar or Trainer to view and grade submitted tasks. |

---

### 1.6 Training History & Tracking

| ID | Requirement |
|---|---|
| FR-30 | The system shall maintain a personal training history log for each user. |
| FR-31 | The training history shall display the training name, date, attendance status, and task/quiz completion for each record. |
| FR-32 | The system shall allow the Guru Besar to view the training history of individual teachers or all teachers collectively. |
| FR-33 | The system shall allow users to export their training history as a document (PDF or printable format). |

---

### 1.7 Notification System

| ID | Requirement |
|---|---|
| FR-34 | The system shall send in-app notifications to applicants when their application status changes (approved or rejected). |
| FR-35 | The system shall send notifications to the Guru Besar when a new training application is submitted. |
| FR-36 | The system shall send reminders to approved participants before an upcoming training session. |
| FR-37 | The system shall notify participants of upcoming task or quiz deadlines. |
| FR-38 | The system shall allow users to view all past and unread notifications in a notification centre. |
| FR-39 | The system shall mark notifications as read once viewed by the user. |

---

### 1.8 Feedback System

| ID | Requirement |
|---|---|
| FR-40 | The system shall allow participants to submit feedback for a training program after it is marked `Completed`. |
| FR-41 | The feedback form shall include a star rating (1–5) and an optional written comment field. |
| FR-42 | The system shall allow each participant to submit feedback only once per training program. |
| FR-43 | The system shall allow the Guru Besar to view aggregated feedback results per training program. |

---

### 1.9 Dashboard & Reporting

| ID | Requirement |
|---|---|
| FR-44 | The system shall provide a Guru Besar dashboard displaying: total active training programs, total pending applications, overall teacher participation rate, and attendance summaries. |
| FR-45 | The system shall provide a Teacher dashboard displaying: applied training programs, upcoming approved training, and pending task/quiz submissions. |
| FR-46 | The system shall provide an Outsider dashboard displaying: registered training programs and training history. |
| FR-47 | The system shall generate a training attendance letter or certificate for approved participants upon request. |
| FR-48 | The system shall allow the Guru Besar to generate summary reports of training participation by date range or training program. |

---

### 1.10 Cross-System Integration Requirements

> These requirements govern how EduTrain is architected to support integration with an external system whose schema and API are not yet defined. They apply in Phase 1 (design-time constraints) and Phase 2 (live integration). See PRD Section 6.3 for the full integration architecture rationale.

| ID | Requirement |
|---|---|
| FR-INT-01 | The system shall implement a Repository abstraction layer separating all data-access logic from controllers and business services, so that any data source (local DB or external system) can be swapped without modifying the calling code. |
| FR-INT-02 | The system shall define a repository interface (`TeacherRepositoryInterface`, `PrincipalRepositoryInterface`) specifying the data contract EduTrain requires from staff profile sources, independent of storage location. |
| FR-INT-03 | The system shall implement `LocalTeacherRepository` and `LocalPrincipalRepository` as the default implementations of these interfaces, reading from EduTrain's own database, active in Phase 1. |
| FR-INT-04 | All staff profile data consumed by controllers and views shall be normalized through DTOs (`TeacherDTO`, `PrincipalDTO`) so that the internal data shape is decoupled from any external system's column naming or structure. |
| FR-INT-05 | The system shall store an `external_id` nullable column on the `users` table to hold the identifier assigned by the external system for synced staff accounts. This column shall be `null` for self-registered Outsiders. |
| FR-INT-06 | When the external system's integration is implemented (Phase 2), it shall be introduced by creating a new repository implementation (e.g., `ExternalTeacherRepository`) and rebinding the interface in Laravel's service container, with no changes required to any controller, service, or view. |
| FR-INT-07 | The system shall gracefully handle external system unavailability: if an external repository call fails or times out, the system shall fall back to the last locally-cached profile data and continue operating normally for all participant-facing flows. |
| FR-INT-08 | The system shall log all external data-fetch failures to the audit log, including timestamp, repository method called, and failure reason, without exposing error details to end users. |
| FR-INT-09 | No controller, model, or view shall contain a hardcoded reference to an external database connection name, table name, API endpoint URL, or authentication token. All such references are confined to repository adapter implementations. |
| FR-INT-10 | The system shall support an optional data-sync command (`php artisan edutrain:sync-staff`) that, when the Phase 2 adapter is installed, pulls updated staff records from the external system and updates local cache. This command shall be a no-op in Phase 1. |

---

## 2. Non-Functional Requirements

Non-functional requirements define the quality attributes and constraints the system must conform to.

---

### 2.1 Performance

| ID | Requirement |
|---|---|
| NFR-01 | The system shall load any page within 3 seconds under normal network conditions. |
| NFR-02 | The system shall support a minimum of 50 concurrent users without performance degradation. |
| NFR-03 | Database queries shall be optimized to return results within 1 second for standard operations (browsing, searching). |

---

### 2.2 Usability

| ID | Requirement |
|---|---|
| NFR-04 | The system shall provide a clean, intuitive user interface accessible to users with basic computer literacy. |
| NFR-05 | The system shall be fully responsive and functional on desktop, tablet, and mobile web browsers. |
| NFR-06 | Error messages shall be clear, descriptive, and guide the user toward corrective action. |
| NFR-07 | All user-facing text shall be available in English, with consideration for Bahasa Malaysia where applicable. |

---

### 2.3 Security

| ID | Requirement |
|---|---|
| NFR-08 | All user passwords shall be hashed using a secure algorithm (e.g., bcrypt) before storage. |
| NFR-09 | The system shall enforce Role-Based Access Control (RBAC) to prevent unauthorized access to restricted features or data. |
| NFR-10 | All data transmitted between the client and server shall be encrypted over HTTPS/TLS. |
| NFR-11 | The system shall implement CSRF (Cross-Site Request Forgery) protection on all forms. |
| NFR-12 | The system shall implement session expiry after a defined period of inactivity (e.g., 30 minutes). |
| NFR-13 | The system shall validate and sanitize all user inputs to prevent SQL injection and XSS attacks. |

---

### 2.4 Reliability & Availability

| ID | Requirement |
|---|---|
| NFR-14 | The system shall target a minimum uptime of 99% during school operating hours (7:00 AM – 6:00 PM, Monday to Friday). |
| NFR-15 | The system shall perform automated database backups at least once every 24 hours. |
| NFR-16 | The system shall gracefully handle errors and display user-friendly error pages (e.g., 404, 500) without exposing system internals. |

---

### 2.5 Maintainability

| ID | Requirement |
|---|---|
| NFR-17 | The system shall follow the MVC (Model-View-Controller) architectural pattern using the Laravel framework. |
| NFR-18 | The codebase shall adhere to consistent coding standards (PSR-12 for PHP). |
| NFR-19 | All modules and functions shall be documented with inline comments for future maintainability. |
| NFR-20 | The system shall be structured to allow new modules or features to be added without significant restructuring of existing code. |

---

### 2.6 Scalability

| ID | Requirement |
|---|---|
| NFR-21 | The system architecture shall support future horizontal scaling if user load increases beyond initial capacity. |
| NFR-22 | The database schema shall be designed to accommodate additional training types or user roles in the future without major rework. |

---

### 2.7 Integration Non-Functional Requirements

| ID | Requirement |
|---|---|
| NFR-INT-01 | Repository interface implementations shall be swappable via Laravel's service container (`AppServiceProvider` binding) without any code change outside the service provider. |
| NFR-INT-02 | All external data access shall be wrapped in a try-catch with a defined timeout (maximum 5 seconds) to prevent external system latency from degrading EduTrain's response time. |
| NFR-INT-03 | DTOs used as the integration data contract shall be immutable (read-only properties) to prevent accidental mutation of data received from an external source. |
| NFR-INT-04 | The `external_id` column shall be indexed on the `users` table to support efficient lookup when reconciling external staff IDs. |
| NFR-INT-05 | Integration-layer code (repository adapters, DTOs, interfaces) shall be organized in a dedicated namespace (`App\Integration`) to clearly separate it from core EduTrain application code. |
| NFR-INT-06 | The data-sync command (`edutrain:sync-staff`) shall be idempotent — running it multiple times shall produce the same result as running it once, with no duplicate records created. |

---

## 3. Domain Requirements

Domain requirements are derived from the specific rules, regulations, and constraints of the educational and Malaysian public service training context.

---

### 3.1 LADAP (Latihan Dalam Perkhidmatan) Compliance

| ID | Requirement |
|---|---|
| DR-01 | The system shall support the management of LADAP activities as defined under Malaysian Ministry of Education guidelines for teacher professional development. |
| DR-02 | Each training program recorded in the system shall include LADAP-relevant metadata: training category, trainer name, duration (hours), and competency area targeted. |
| DR-03 | The system shall maintain a cumulative record of LADAP hours completed by each teacher, enabling compliance monitoring against minimum annual training requirements. |
| DR-04 | Training attendance letters generated by the system shall conform to the standard format acceptable for LADAP record submission. |

---

### 3.2 Institutional Hierarchy & Workflow

| ID | Requirement |
|---|---|
| DR-05 | The system shall reflect the institutional hierarchy of Al Amin Edu Oasis, where the Guru Besar holds final authority over all training approvals. |
| DR-06 | No teacher shall be confirmed as a participant in a training program without explicit approval from the Guru Besar. |
| DR-07 | The Guru Besar shall have full visibility into all teacher training activities, including applications, attendance, and task completion. |

---

### 3.3 Teacher Professional Development

| ID | Requirement |
|---|---|
| DR-08 | The system shall categorize training programs by professional development competency areas (e.g., pedagogy, subject knowledge, leadership, ICT). |
| DR-09 | The system shall support tracking of each teacher's professional development progress over time to facilitate annual performance reviews. |
| DR-10 | Teachers shall be able to access and download their own training records for use in personal portfolios or official submissions. |

---

### 3.4 Outsider Participation

| ID | Requirement |
|---|---|
| DR-11 | Outsiders (non-school-staff participants) shall be permitted to join training programs that are designated as open to external participants. |
| DR-12 | Outsiders shall not have access to internal school data such as teacher records, internal reports, or institutional dashboards. |
| DR-13 | Outsider accounts shall be clearly differentiated from internal Teacher accounts in all system records and reports. |

---

### 3.5 Data Privacy & Record Integrity

| ID | Requirement |
|---|---|
| DR-14 | All personally identifiable information (PII) of teachers and participants shall be handled in accordance with the Personal Data Protection Act 2010 (PDPA) of Malaysia. |
| DR-15 | Training records, once marked as completed, shall be immutable and not editable by any standard user role to ensure record integrity. |
| DR-16 | Audit logs shall be maintained for key actions including approvals, rejections, and attendance marking to support accountability. |

---

### 3.6 Cross-System Integration Domain Requirements

| ID | Requirement |
|---|---|
| DR-INT-01 | EduTrain shall treat the external system as the authoritative source for staff (teacher and principal) profile data when the integration is active. In cases of conflict between locally-cached data and data retrieved from the external system, the external system's version shall take precedence for display and reporting purposes. |
| DR-INT-02 | EduTrain shall remain the sole authoritative source for all training, application, attendance, task, quiz, feedback, and notification data, regardless of integration status. The external system shall have no write access to these records. |
| DR-INT-03 | LADAP cumulative hours reported in EduTrain (DR-03) shall be clearly labelled as "EduTrain records only" until Phase 2 integration is confirmed. If the external system also holds training records, a combined hours figure shall only be displayed once the data contract for that aggregation is defined. |
| DR-INT-04 | Outsider participant data (self-registered accounts) shall remain permanently under EduTrain's ownership and shall not be transmitted to or overwritten by the external system. |
| DR-INT-05 | The `external_id` stored on a synced user account shall be treated as opaque — EduTrain shall store it and use it for reconciliation only. It shall not be displayed to end users or used to infer information about the external system's structure. |
| DR-INT-06 | Any data received from the external system shall be validated against EduTrain's DTO schema before use. Records that fail validation shall be rejected, logged to the audit log, and not persisted locally. |
| DR-INT-07 | Personally identifiable information (PII) received from the external system shall be subject to the same PDPA 2010 compliance requirements as locally-originated PII (DR-14). |

---

*Document prepared based on PSM Proposal by Izzhilmy Bin Shamsul Bahri, UTeM FTMK, Session 2025/2026.*
