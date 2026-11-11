# Module Specification & Flow of Events
## Educator Training Management System (EduTrain System)

**Project Code:** BITU 3973
**Version:** 1.1
**Student:** Izzhilmy Bin Shamsul Bahri (B032420045)
**Institution:** Universiti Teknikal Malaysia Melaka (UTeM) — FTMK
**Session:** 2025/2026, Semester 2

> **Version 1.1 Change Note:** Updated M01 to document the external system integration seam for teacher/principal account management. Added Phase 1 / Phase 2 distinctions to M01-F02. Added integration fallback note to M04. Updated M07-F02 to note LADAP hours caveat. Added M10 (Integration Sync) as a new system-only module.

---

## System Modules Overview

The EduTrain System is organized into **9 functional modules**, each serving a distinct area of the training management lifecycle.

| No. | Module | Primary Actor(s) |
|---|---|---|
| M01 | Authentication & Account Management | All Users |
| M02 | Training Program Management | Guru Besar |
| M03 | Training Application & Approval | Teacher, Outsider, Guru Besar |
| M04 | Attendance Management | Teacher, Outsider, Guru Besar |
| M05 | Task Submission | Teacher, Outsider |
| M06 | Quiz / Answer Sheet | Teacher, Outsider |
| M07 | Training History & Tracking | Teacher, Outsider, Guru Besar |
| M08 | Notification System | All Users |
| M09 | Feedback & Reporting | Teacher, Outsider, Guru Besar |

---

## M01 — Authentication & Account Management

### Description
Handles all user registration, login, logout, and profile management operations. This module is the entry point for all system interactions and enforces role-based access control across all other modules.

This module contains the primary **integration seam** for cross-system account management. In Phase 1, all accounts are managed locally. In Phase 2, Teacher and Principal accounts may originate from the external system via the repository adapter — the flows below note where Phase 2 behaviour differs.

### Actors
- Teacher (pre-registered by Admin in Phase 1; potentially synced from external system in Phase 2)
- Outsider (self-registers — permanent, no external system involvement)
- Guru Besar (pre-registered by Admin in Phase 1; potentially synced from external system in Phase 2)

### Flow of Events

#### M01-F01: Outsider Self-Registration

```
1. Outsider navigates to the registration page.
2. Outsider fills in the registration form:
   - Full Name
   - Email Address
   - Password
   - Confirm Password
3. System validates all fields:
   a. All fields must be non-empty.
   b. Email must follow a valid format.
   c. Email must not already exist in the USER table.
   d. Password and Confirm Password must match.
   e. Password must meet minimum strength requirements (min. 8 characters).
4. [If validation fails] → System displays inline error messages. Flow returns to Step 2.
5. [If validation passes] → System creates a new USER record with Role = 'outsider'.
6. System sends a welcome notification to the registered email.
7. System redirects Outsider to the login page with a success message.
```

#### M01-F02: User Login

```
Phase 1 (local auth):
1. User navigates to the login page.
2. User enters Email and Password.
3. System validates credentials against the local USER table.
4. [If credentials are invalid] → System displays: "Invalid email or password."
   Flow returns to Step 2. (Maximum 5 attempts before temporary lockout — 15 minutes.)
5. [If credentials are valid] → System identifies the user's Role.
6. System creates an authenticated session for the user.
7. System redirects the user to their role-specific dashboard:
   - Guru Besar → Principal Dashboard
   - Teacher → Teacher Dashboard
   - Outsider → Outsider Dashboard

Phase 2 note (SSO/federated auth — if adopted):
- Steps 3–4 are handled by the external identity provider.
- EduTrain receives an authenticated identity token, verifies the ExternalID
  against its local USER table, and proceeds from Step 5.
- If no local USER record matches the ExternalID, the system auto-provisions
  one using the TeacherDTO/PrincipalDTO from the external repository.
- This adaptation is confined to AuthenticatedSessionController;
  no other flow changes.
```

#### M01-F03: User Logout

```
1. User clicks the "Logout" button from any page.
2. System terminates the current session.
3. System clears all session data.
4. System redirects user to the login page with a logout confirmation message.
```

#### M01-F04: Password Reset

```
1. User clicks "Forgot Password" on the login page.
2. User enters their registered email address.
3. System checks if the email exists in the USER table.
4. [If email not found] → System displays: "If this email is registered, a reset link will be sent."
5. [If email found] → System generates a secure, time-limited password reset token.
6. System sends the reset link to the user's email.
7. User clicks the link and is directed to the Reset Password page.
8. User enters and confirms a new password.
9. System validates the new password (min. 8 characters, matches confirm field).
10. System updates the hashed password in the USER table and invalidates the token.
11. System redirects user to the login page with a success message.
```

#### M01-F05: Profile Update

```
1. User navigates to "My Profile" from their dashboard.
2. System displays current profile information (Name, Email, Department, Position).
3. User edits desired fields and clicks "Save Changes".
4. System validates updated inputs.
5. [If validation fails] → System displays error messages. Flow returns to Step 3.
6. [If validation passes] → System updates the corresponding profile table
   (TEACHER or PRINCIPAL based on role).
7. System displays a success confirmation message.
```

---

## M02 — Training Program Management

### Description
Enables the Guru Besar to create, manage, and publish LADAP training programs that are available for teachers and outsiders to apply for.

### Actors
- Guru Besar (primary)
- Teacher, Outsider (view only)

### Flow of Events

#### M02-F01: Create Training Program

```
1. Guru Besar navigates to "Training Management" → "Create Training".
2. System displays the training creation form.
3. Guru Besar fills in the training details:
   - Title
   - Description
   - Date and Time
   - Location
   - Maximum Capacity
   - Training Category (e.g., pedagogy, ICT, leadership)
   - Registration Deadline
4. System validates all required fields.
5. [If validation fails] → System displays inline errors. Flow returns to Step 3.
6. [If validation passes] → System saves a new TRAINING record with Status = 'open'
   and UserID = Guru Besar's UserID.
7. System sends a notification to all Teachers: "New training program available: [Title]."
8. System displays the training detail page with a success message.
```

#### M02-F02: Edit Training Program

```
1. Guru Besar navigates to "Training Management" and selects an existing training.
2. Guru Besar clicks "Edit".
3. System displays the pre-filled training form.
4. Guru Besar modifies the desired fields and clicks "Update".
5. System validates the updated data.
6. [If validation fails] → System displays errors. Flow returns to Step 4.
7. [If validation passes] → System updates the TRAINING record.
8. System notifies enrolled participants if the date or location has changed.
9. System displays a success confirmation message.
```

#### M02-F03: Cancel / Close Training Program

```
1. Guru Besar selects a training and clicks "Cancel Training".
2. System displays a confirmation prompt: "Are you sure you want to cancel this training?"
3. [If Guru Besar cancels the action] → Flow ends. No changes made.
4. [If Guru Besar confirms] → System updates the TRAINING record Status = 'closed'.
5. System sends a cancellation notification to all applicants with Status = 'approved'.
6. System displays a success message.
```

#### M02-F04: View Training Programs (All Roles)

```
1. User navigates to the "Training" section from their dashboard.
2. System retrieves and displays all TRAINING records with Status = 'open'.
3. User may apply filters: by date range, category, or keyword search.
4. System updates the displayed list based on the applied filters.
5. User selects a training to view its full details (title, date, location,
   description, available slots, registration deadline).
```

---

## M03 — Training Application & Approval

### Description
Manages the end-to-end workflow of training applications, from submission by Teachers and Outsiders through to approval or rejection by the Guru Besar.

### Actors
- Teacher / Outsider (apply)
- Guru Besar (approve / reject / assign)

### Flow of Events

#### M03-F01: Apply for Training (Teacher / Outsider)

```
1. User browses available training programs (M02-F04).
2. User selects a training and clicks "Apply".
3. System checks:
   a. User has not already applied for this training.
   b. Training Status is 'open'.
   c. Training has not exceeded maximum capacity.
   d. Current date is before the registration deadline.
4. [If any check fails] → System displays the appropriate error message. Flow ends.
5. [If all checks pass] → System creates a new APPLICATION record:
   - TeacherID / UserID = current user
   - TrainingID = selected training
   - Status = 'pending'
6. System sends a notification to the Guru Besar:
   "New training application received from [User Name] for [Training Title]."
7. System displays a confirmation to the user: "Your application has been submitted."
```

#### M03-F02: Guru Besar Reviews Applications

```
1. Guru Besar navigates to "Applications" from the dashboard.
2. System displays a list of all applications with Status = 'pending',
   showing applicant name, training title, and application date.
3. Guru Besar selects an application to view full details.
4. System displays applicant profile and training information.
5. Guru Besar selects either "Approve" or "Reject".
6. [If Reject] → System prompts for an optional rejection reason.
7. System updates the APPLICATION record Status = 'approved' or 'rejected'.
8. System sends a notification to the applicant with the outcome.
9. System returns Guru Besar to the applications list.
```

#### M03-F03: Guru Besar Assigns Teacher to Training (Push Assignment)

```
1. Guru Besar navigates to a specific training program.
2. Guru Besar clicks "Assign Teacher".
3. System displays a list of all Teachers not yet enrolled in this training.
4. Guru Besar selects one or more Teachers from the list.
5. Guru Besar clicks "Confirm Assignment".
6. System creates APPLICATION records for each selected Teacher:
   - Status = 'approved' (directly, without pending step)
7. System sends a notification to each assigned Teacher:
   "You have been assigned to [Training Title] on [Date]."
8. System displays a success message.
```

#### M03-F04: View Application Status (Teacher / Outsider)

```
1. User navigates to "My Applications" from their dashboard.
2. System displays a list of all the user's applications with:
   - Training Title
   - Application Date
   - Status (Pending / Approved / Rejected)
   - Rejection Reason (if applicable)
3. User selects an application to view full details.
```

---

## M04 — Attendance Management

### Description
Manages attendance recording for approved training participants using a QR-based check-in mechanism, and enables the Guru Besar to monitor attendance records.

> **Design Decision Note:** The original PSM proposal does not specify how attendance is marked — it states only that participants will "attend their attendance." The QR-based mechanism in this module is a design decision derived from the `QR` attribute present in the submitted ERD diagram. It was adopted over manual marking because it is faster, reduces proxy attendance risk, and produces a timestamped verifiable record without administrator intervention per check-in. This decision is not a proposal requirement and should be confirmed with the project supervisor.

### Actors
- Teacher / Outsider (mark attendance)
- Guru Besar (generate QR, view reports)

### Flow of Events

#### M04-F01: Generate Attendance QR Code (Guru Besar)

```
1. Guru Besar navigates to a training program and selects "Manage Attendance".
2. Guru Besar clicks "Generate QR Code" for a specific session date.
3. System creates a new ATTENDANCE record:
   - AttendanceID (auto-generated)
   - QR token (unique, cryptographically random, time-limited)
   - Date = session date
4. System displays the QR code on screen (printable or projectable for
   participants to scan in the training room).
5. QR token remains valid only within the defined attendance window
   (e.g., 30 minutes from generation time).
6. Once the window expires, the token is invalidated automatically.
```

#### M04-F02: Mark Attendance via QR (Teacher / Outsider)

```
[Design Decision: QR check-in — not stated in proposal; derived from ERD]

1. Approved participant opens the EduTrain system on their device.
2. Participant navigates to "My Training" → selects the active training session.
3. Participant clicks "Mark Attendance".
4. System displays a QR scan interface or a manual token entry field.
5. Participant scans the QR code displayed in the training room,
   or manually enters the QR token.
6. System validates:
   a. Participant has an approved APPLICATION for this training.
   b. The QR token matches the active ATTENDANCE record for this training.
   c. The current time is within the active attendance window.
   d. Participant has not already marked attendance for this session.
7. [If any validation fails] → System displays an appropriate error message:
   - "Invalid or expired QR code." / "Attendance already recorded."
8. [If all validations pass] → System creates an ATTENDANCE RECORD:
   - TeacherID = current user
   - AttendanceID = matched attendance session
   - Status = 'present'
   - Date = current timestamp
9. System displays: "Attendance marked successfully."
```

#### M04-F03: View Attendance Report (Guru Besar)

```
1. Guru Besar navigates to a training program and selects "Attendance Report".
2. System retrieves all ATTENDANCE RECORD entries linked to the training.
3. System displays a table showing:
   - Participant Name
   - Session Date
   - Status (Present / Absent / Late)
4. Guru Besar may filter by session date or participant name.
5. Guru Besar may export the attendance report as a PDF or printable format.
```

#### M04-F04: Mark Absent (System Automated)

```
1. After the attendance window closes (QR token expires):
2. System identifies all approved participants who do not have an
   ATTENDANCE RECORD for that session.
3. System automatically creates ATTENDANCE RECORD entries for those participants
   with Status = 'absent'.
4. System logs the automated action for audit purposes.
```

---

## M05 — Task Submission

### Description
Allows participants to submit assignments or work tasks linked to a specific training program, and allows the Guru Besar to review and grade submissions.

### Actors
- Teacher / Outsider (submit)
- Guru Besar (review and grade)

### Flow of Events

#### M05-F01: Create Task (Guru Besar)

```
1. Guru Besar navigates to a training program and selects "Tasks" → "Create Task".
2. System displays the task creation form.
3. Guru Besar fills in:
   - Task Title
   - Description / Instructions
   - Submission Deadline
   - Accepted file types (PDF, DOCX, images, or text)
4. System validates all fields.
5. [If validation fails] → System shows errors. Flow returns to Step 3.
6. [If valid] → System saves the task and links it to the TrainingID.
7. System sends a notification to all approved participants:
   "A new task has been posted for [Training Title]. Deadline: [Date]."
```

#### M05-F02: Submit Task (Teacher / Outsider)

```
1. Participant navigates to "My Training" → selects training → "Tasks".
2. System displays all tasks for the training with their deadlines and statuses.
3. Participant selects a task and clicks "Submit".
4. System checks:
   a. Participant has an approved APPLICATION for this training.
   b. Current date is on or before the submission deadline.
   c. Participant has not already submitted for this task.
5. [If any check fails] → System displays the appropriate error message.
6. [If all checks pass] → System displays the submission form.
7. Participant uploads a file and/or enters a written answer, then clicks "Submit".
8. System saves a new TASK_SUBMISSION record:
   - SubmissionID (auto-generated)
   - Answer = text input (if any)
   - Submitted_file_path = path to uploaded file
   - Date = current date
9. System links the SubmissionID to the participant's APPLICATION record.
10. System displays: "Task submitted successfully."
```

#### M05-F03: Review and Grade Submission (Guru Besar)

```
1. Guru Besar navigates to a training's "Tasks" section.
2. System displays all submissions per task, showing participant name,
   submission date, and current status (Submitted / Not Submitted / Graded).
3. Guru Besar selects a submission to review.
4. System displays the submitted file (downloadable) and/or text answer.
5. Guru Besar enters a grade or feedback comment.
6. Guru Besar clicks "Save Grade".
7. System updates the TASK_SUBMISSION record with the grade.
8. System sends a notification to the participant: "Your task submission has been graded."
```

---

## M06 — Quiz / Answer Sheet

### Description
Allows the Guru Besar to create quizzes linked to training programs, and participants to attempt and submit their answers online.

### Actors
- Teacher / Outsider (attempt quiz)
- Guru Besar (create quiz, view results)

### Flow of Events

#### M06-F01: Create Quiz (Guru Besar)

```
1. Guru Besar navigates to a training program and selects "Quiz" → "Create Quiz".
2. Guru Besar fills in:
   - Quiz Title
   - Instructions
   - Time Limit (optional)
   - Available Date and Deadline
3. Guru Besar adds questions one by one:
   - Question text
   - Question type (multiple choice or short answer)
   - Answer options (for multiple choice)
   - Correct answer (for auto-grading)
4. Guru Besar clicks "Publish Quiz".
5. System saves the quiz and links it to the TrainingID.
6. System notifies all approved participants:
   "A new quiz is available for [Training Title]. Deadline: [Date]."
```

#### M06-F02: Attempt Quiz (Teacher / Outsider)

```
1. Participant navigates to "My Training" → selects training → "Quiz".
2. System displays available quizzes with their deadlines and attempt status.
3. Participant selects a quiz and clicks "Start Quiz".
4. System checks:
   a. Participant has an approved APPLICATION for this training.
   b. Current date is within the available window.
   c. Participant has not already completed this quiz.
5. [If any check fails] → System displays the appropriate error message.
6. [If all checks pass] → System displays the quiz questions.
   - Timer starts if a time limit is set.
7. Participant answers all questions and clicks "Submit Quiz".
8. [If time runs out] → System auto-submits with answers entered so far.
9. System saves a new ANSWER_SHEET record:
   - SheetID (auto-generated)
   - Answer = serialized answers
   - Date = current date
10. System links SheetID to the participant's APPLICATION record.
11. System auto-grades multiple-choice questions and displays the score.
12. System displays: "Quiz submitted successfully. Your score: [X / Y]."
```

#### M06-F03: View Quiz Results (Guru Besar)

```
1. Guru Besar navigates to a training's "Quiz" section.
2. System displays a summary of all participants' quiz results:
   - Participant Name
   - Score
   - Attempt Date
   - Status (Completed / Not Attempted)
3. Guru Besar selects a participant to view their individual answer sheet.
4. System displays each question alongside the participant's answer
   and the correct answer.
5. Guru Besar may manually adjust scores for short-answer questions.
6. Guru Besar clicks "Save" to confirm any manual score adjustments.
```

---

## M07 — Training History & Tracking

### Description
Provides users with a complete record of their past and current training participation, and gives the Guru Besar visibility into all teachers' development progress.

### Actors
- Teacher / Outsider (view personal history)
- Guru Besar (view all teacher histories)

### Flow of Events

#### M07-F01: View Personal Training History (Teacher / Outsider)

```
1. User navigates to "My Training History" from their dashboard.
2. System retrieves all APPLICATION records linked to the current user
   with Status = 'approved'.
3. System displays a list of training records showing:
   - Training Title
   - Date
   - Location
   - Attendance Status (Present / Absent / Late)
   - Task Submission Status (Submitted / Not Submitted / Graded)
   - Quiz Status (Completed / Not Attempted)
4. User may filter records by date range or training category.
5. User clicks "View Details" on any entry to see the full training summary
   including score and feedback submitted.
```

#### M07-F02: Export Training History (Teacher / Outsider)

```
1. User navigates to "My Training History".
2. User clicks "Export" and selects the format (PDF or printable view).
3. System generates a formatted training history document including:
   - User name and role
   - List of completed trainings with dates
   - Attendance and completion statuses
   - Total LADAP hours accumulated (see Phase 2 note below)
4. System prompts the user to download the generated file.

Phase 2 note (LADAP hours completeness):
- In Phase 1, total LADAP hours are computed from EduTrain records only.
- The exported document shall include a disclaimer:
  "LADAP hours shown reflect training recorded in EduTrain only."
- In Phase 2, if the external system also holds training records,
  this total will be updated to aggregate across both systems once
  the data contract for that query is defined (PRD Open Decision D-05).
```

#### M07-F03: View Teacher Training History (Guru Besar)

```
1. Guru Besar navigates to "Teacher Development" from the dashboard.
2. System displays a list of all Teachers.
3. Guru Besar selects a Teacher to view their individual training history.
4. System displays the same training history view as M07-F01 for that teacher.
5. Guru Besar may filter by date range or training category.
6. Guru Besar may export the selected teacher's history as a PDF.
```

#### M07-F04: View Aggregated Development Report (Guru Besar)

```
1. Guru Besar navigates to "Reports" → "Teacher Development Overview".
2. System displays aggregated statistics:
   - Total number of training programs conducted
   - Total teacher participation count
   - Average attendance rate per training
   - Number of teachers meeting minimum LADAP hour requirements
3. Guru Besar may filter by session, date range, or department.
4. Guru Besar may export the report as a PDF.
```

---

## M08 — Notification System

### Description
Delivers in-app notifications to users for key system events such as application status changes, new training programs, upcoming deadlines, and assignment results.

### Actors
- All Users (receive notifications)
- System (auto-generates notifications)

### Flow of Events

#### M08-F01: System Sends a Notification

```
1. A trigger event occurs in the system. Trigger events include:
   - New training program created
   - Training application submitted
   - Application approved or rejected
   - Teacher assigned to training
   - New task or quiz posted
   - Task submission graded
   - Upcoming training reminder (1 day before)
   - Task/quiz deadline reminder (1 day before deadline)
   - Training cancelled
2. System identifies the target user(s) based on the event type.
3. System creates a new NOTIFICATION record for each target user:
   - UserID = target user
   - Message = event-specific message
   - IsRead = false
   - CreatedAt = current timestamp
4. The notification badge count on the target user's interface is updated.
```

#### M08-F02: User Views Notifications

```
1. User clicks the notification icon/bell in the navigation bar.
2. System retrieves all NOTIFICATION records for the current user,
   ordered by CreatedAt (most recent first).
3. System displays notifications with:
   - Message content
   - Timestamp
   - Read / Unread indicator
4. System marks displayed notifications as IsRead = true.
5. User may click on a notification to be redirected to the relevant page
   (e.g., clicking an approval notification → redirects to Application detail).
```

#### M08-F03: User Clears Notifications

```
1. User clicks "Mark All as Read" or "Clear All" in the notification centre.
2. System updates all the user's NOTIFICATION records to IsRead = true,
   or deletes them if "Clear All" is selected.
3. Notification badge count resets to zero.
```

---

## M09 — Feedback & Reporting

### Description
Allows participants to submit post-training feedback and enables the Guru Besar to access feedback summaries and generate training reports.

### Actors
- Teacher / Outsider (submit feedback)
- Guru Besar (view feedback and reports)

### Flow of Events

#### M09-F01: Submit Feedback (Teacher / Outsider)

```
1. After a training program is marked as 'completed', the participant
   receives a notification: "Please submit your feedback for [Training Title]."
2. Participant navigates to "My Training" → selects the completed training
   → clicks "Submit Feedback".
3. System checks:
   a. Participant attended the training (ATTENDANCE RECORD Status = 'present').
   b. Participant has not already submitted feedback for this training.
4. [If any check fails] → System displays the appropriate message.
5. [If all checks pass] → System displays the feedback form:
   - Star rating (1–5)
   - Written comment (optional)
   - Specific questions (e.g., "Was the content relevant?",
     "Was the trainer effective?")
6. Participant completes the form and clicks "Submit".
7. System saves the FEEDBACK record:
   - UserID = current user
   - TrainingID = selected training
   - Rating = selected star value
   - Comment = written input
   - SubmittedAt = current timestamp
8. System displays: "Thank you for your feedback."
```

#### M09-F02: View Feedback Summary (Guru Besar)

```
1. Guru Besar navigates to a completed training program and selects "Feedback".
2. System retrieves all FEEDBACK records for that training.
3. System displays:
   - Average star rating
   - Total number of responses
   - Distribution of ratings (e.g., bar chart of 1–5 stars)
   - List of written comments (anonymized or named, based on settings)
4. Guru Besar may filter feedback by rating value.
5. Guru Besar may export the feedback summary as a PDF report.
```

#### M09-F03: Generate Training Attendance Letter (Guru Besar)

```
1. Guru Besar navigates to a completed training program.
2. Guru Besar clicks "Generate Attendance Letters".
3. System retrieves all participants with ATTENDANCE RECORD Status = 'present'.
4. System generates individual attendance letters/certificates for each
   present participant, including:
   - Participant name
   - Training title
   - Date and location
   - Duration (hours)
   - Official signature placeholder
5. System packages all letters into a downloadable ZIP file or individual PDFs.
6. Guru Besar downloads the generated files.
```

#### M09-F04: Generate Training Summary Report (Guru Besar)

```
1. Guru Besar navigates to "Reports" and selects "Training Summary".
2. Guru Besar selects filter criteria:
   - Date range
   - Specific training program (optional)
   - Department (optional)
3. System compiles a report including:
   - Total trainings conducted
   - Total participants enrolled vs attended
   - Task and quiz completion rates
   - Average feedback ratings per training
4. System displays the report on screen.
5. Guru Besar may export the report as a PDF or CSV file.
```

---

## Summary: Module–Flow Matrix

| Module | Flows | Primary Trigger |
|---|---|---|
| M01 Auth | F01–F05 | User action (login, register, profile) |
| M02 Training Management | F01–F04 | Guru Besar creates/manages training |
| M03 Application & Approval | F01–F04 | Teacher/Outsider applies; GB approves |
| M04 Attendance | F01–F04 | QR scan during training session |
| M05 Task Submission | F01–F03 | Participant submits work |
| M06 Quiz / Answer Sheet | F01–F03 | Participant attempts quiz |
| M07 History & Tracking | F01–F04 | User views training records |
| M08 Notifications | F01–F03 | System event triggers |
| M09 Feedback & Reporting | F01–F04 | Post-training actions |
| M10 Integration Sync *(Phase 2)* | F01–F02 | System-automated staff data sync from external system |

**Total Flows Defined: 36 (34 Phase 1 + 2 Phase 2)**

---

## M10 — Integration Sync *(Phase 2 — Not Implemented in Phase 1)*

### Description
Manages the synchronization of staff profile data (Teachers, Principals) from the external system into EduTrain's local cache via the repository adapter. This module is system-automated and has no user-facing interface. It is defined here to document the expected behavior when the external system's integration is implemented.

> **Phase 1 status:** The `edutrain:sync-staff` Artisan command exists but is a no-op. The `LocalTeacherRepository` and `LocalPrincipalRepository` are the active implementations.

### Actors
- System (automated)
- External System (data source)

### Flow of Events

#### M10-F01: Scheduled Staff Sync

```
[Triggered by: Laravel Scheduler — frequency TBD based on external system's update cadence]

1. System executes `php artisan edutrain:sync-staff`.
2. System calls ExternalTeacherRepository::fetchAll() and
   ExternalPrincipalRepository::fetchAll() via the repository interfaces.
3. External repository adapter calls the external system (API/DB — method TBD).
4. [If external system is unreachable] →
   a. System logs failure to AUDIT_LOG (Action = 'sync_failed', Metadata = error detail).
   b. Command exits with a non-zero status. Local cache is unchanged.
   c. Existing local profile data remains active (fallback behaviour — FR-INT-07).
5. [If data is received] →
   a. System validates each record against TeacherDTO / PrincipalDTO schema.
   b. [If a record fails validation] → Log to AUDIT_LOG and skip. Do not persist.
   c. [If a record passes validation] →
      - Lookup existing USER by ExternalID.
      - If found: update Name, Department, Position (non-auth fields only).
      - If not found: create new USER record (Role = teacher/guru_besar)
        and linked TEACHER/PRINCIPAL profile record. Set ExternalID.
6. System logs a summary to AUDIT_LOG:
   Action = 'sync_completed', Metadata = { synced: N, skipped: M, failed: K }.
```

#### M10-F02: Manual Admin-Triggered Sync

```
[Triggered by: System Admin running the command manually from the server CLI]

1. Admin runs: php artisan edutrain:sync-staff --verbose
2. Flow is identical to M10-F01.
3. Command outputs a sync summary to the console.
```

---

*Document prepared based on PSM Proposal by Izzhilmy Bin Shamsul Bahri, UTeM FTMK, Session 2025/2026.*  
*Updated to version 1.1 with integration architecture notes and M10 module.*
