# Activity Diagrams (ASCII)
## Educator Training Management System (EduTrain System)

**Project Code:** BITU 3973
**Version:** 1.1
**Student:** Izzhilmy Bin Shamsul Bahri (B032420045)
**Institution:** Universiti Teknikal Malaysia Melaka (UTeM) — FTMK
**Session:** 2025/2026, Semester 2

> **Version 1.1 Change Note:** Added M01 Admin-Creates-Teacher flow with Phase 2 integration branch. Added M10 Sync Staff Data activity diagram. Updated M02 to include auto-close training logic.

---

## Legend

```
  (*)            = Initial node (start)
  (*)            = Final node (end)  [marked as (*) with double ring in text]
  [ Action ]     = Activity / action step
  < Decision >   = Decision diamond
  ------>        = Control flow
  ===            = Swimlane separator
  [Fork] [Join]  = Parallel split / merge (shown as thick horizontal bar)
```

---

## M01 — User Login Activity Diagram

```
Swimlanes: User | System

User                                  System
==============================================

         (*)
          |
          v
  [ Navigate to Login Page ]
          |
          v
  [ Enter Email & Password ]
          |
          v
          |------------------------------> [ Validate Credentials ]
                                                    |
                                          < Credentials Valid? >
                                           /                  \
                                         NO                   YES
                                          |                    |
                                 [ Show Error Msg ]   [ Identify User Role ]
                                          |                    |
                    <-- (back to entry) --+          < Role? >
                                                    /    |     \
                                             Guru  /  Teacher   \ Outsider
                                            Besar /              \
                                               |                  |
                                    [ Redirect to      [ Redirect to    [ Redirect to
                                    Principal           Teacher          Outsider
                                    Dashboard ]         Dashboard ]      Dashboard ]
                                               \           |            /
                                                +----------+-----------+
                                                           |
                                                          (*)
```

---

## M01 — Outsider Registration Activity Diagram

```
Swimlanes: Outsider | System

Outsider                              System
==============================================

         (*)
          |
          v
  [ Navigate to Register Page ]
          |
          v
  [ Fill Registration Form ]
  [ Name / Email / Password ]
          |
          v
  [ Submit Form ]
          |
          v
          |------------------------------> [ Validate All Fields ]
                                                    |
                                          < All Fields Valid? >
                                           /                  \
                                         NO                   YES
                                          |                    |
                                 [ Show Inline         < Email Already
                                   Error Msgs ]          Exists? >
                                          |              /         \
                    <-- (back to form) ---+            YES          NO
                                                        |            |
                                              [ Show Email    [ Create USER
                                                Taken Error ]   Record ]
                                                        |            |
                                    <-- (back to form) -+   [ Send Welcome
                                                             Email/Notif. ]
                                                                     |
                                                          [ Redirect to Login ]
                                                                     |
                                                                    (*)
```

---

## M02 — Create Training Program Activity Diagram

```
Swimlanes: Guru Besar | System

Guru Besar                            System
==============================================

         (*)
          |
          v
  [ Navigate to Create Training ]
          |
          v
  [ Fill Training Form ]
  [ Title / Date / Location /  ]
  [ Description / Capacity     ]
          |
          v
  [ Submit Form ]
          |
          v
          |------------------------------> [ Validate Required Fields ]
                                                    |
                                          < Fields Valid? >
                                           /                  \
                                         NO                   YES
                                          |                    |
                                 [ Show Inline        [ Save TRAINING Record ]
                                   Errors ]           [ Status = 'open' ]
                                          |                    |
                    <-- (back to form) ---+        [ Notify All Teachers:
                                                     New Training Available ]
                                                               |
                                                    [ Show Training Detail Page ]
                                                               |
                                                              (*)
```

---

## M03 — Training Application & Approval Activity Diagram

```
Swimlanes: Teacher/Outsider | System | Guru Besar

Teacher / Outsider          System              Guru Besar
===========================================================

      (*)
       |
       v
  [ Browse Training ]
       |
       v
  [ Select Training ]
  [ Click Apply ]
       |
       v
       |-----------> [ Check Eligibility ]
                     [ - Not applied before ]
                     [ - Training is open    ]
                     [ - Capacity available  ]
                     [ - Within deadline     ]
                              |
                    < All Checks Pass? >
                     /                 \
                   NO                  YES
                    |                   |
           [ Show Error Msg ]  [ Create APPLICATION ]
                    |          [ Status = 'pending'  ]
       <--(back)----+                   |
                             [ Notify Guru Besar ]
                                        |
                                        |--------------------> [ View Pending
                                                                Applications ]
                                                                      |
                                                                      v
                                                               [ Select Application ]
                                                                      |
                                                               < Decision? >
                                                                /          \
                                                           APPROVE        REJECT
                                                              |              |
                                                    [ Update Status  [ Enter Optional
                                                      = 'approved' ]   Reason ]
                                                              |              |
                                                              |    [ Update Status
                                                              |      = 'rejected' ]
                                                              \              /
                                                               +------------+
                                                                      |
                                                        [ Notify Applicant of Outcome ]
                                                                      |
                                                                     (*)
```

---

## M03 — Push Assignment Activity Diagram (Guru Besar)

```
Swimlanes: Guru Besar | System

Guru Besar                            System
==============================================

         (*)
          |
          v
  [ Navigate to Training ]
          |
          v
  [ Click "Assign Teacher" ]
          |
          v
          |------------------------------> [ Display List of
                                            Unassigned Teachers ]
          |
          v
  [ Select Teacher(s) ]
          |
          v
  [ Click "Confirm Assignment" ]
          |
          v
          |------------------------------> [ Create APPLICATION Records ]
                                           [ Status = 'approved' directly ]
                                                    |
                                          [ Notify Each Assigned Teacher ]
                                                    |
                                          [ Show Success Message ]
                                                    |
                                                   (*)
```

---

## M04 — Attendance Management Activity Diagram

```
Swimlanes: Guru Besar | System | Teacher/Outsider

[Design Decision: QR-based attendance — not stated in proposal; derived from ERD]

Guru Besar              System              Teacher / Outsider
==============================================================

    (*)
     |
     v
[ Navigate to
  Manage Attendance ]
     |
     v
[ Click Generate QR ]
     |
     |-----------> [ Create ATTENDANCE Record ]
                   [ Generate unique QR token  ]
                   [ Set time window (30 min)  ]
                            |
                   [ Display QR on Screen ]
                   [ (project in room)    ]
                            |
                            |----------------------> [ Open EduTrain App ]
                                                             |
                                                             v
                                                    [ Navigate to
                                                      Mark Attendance ]
                                                             |
                                                             v
                                                    [ Scan QR / Enter Token ]
                                                             |
                                                             v
                            |<------------------------[ Send Token to System ]
                   [ Validate Token ]
                   [ - Approved applicant?  ]
                   [ - Token valid?         ]
                   [ - Within time window?  ]
                   [ - Not already marked?  ]
                            |
                  < All Checks Pass? >
                   /                 \
                 NO                  YES
                  |                   |
         [ Show Error ]     [ Create ATTENDANCE RECORD ]
                  |         [ Status = 'present' ]
    (back) <------+                   |
                            [ Show "Attendance Marked" ]
                                      |
   [After window closes]              |
         |                            |
         v                            |
[ System Auto-checks  ]              |
[ remaining approved  ]              |
[ participants        ]              |
         |                            |
[ Mark missing as     ]              |
[ Status = 'absent'   ]              |
         |                            |
         +----------------------------+
                      |
                     (*)
```

---

## M05 — Task Submission Activity Diagram

```
Swimlanes: Guru Besar | System | Teacher/Outsider

Guru Besar              System              Teacher / Outsider
==============================================================

    (*)
     |
     v
[ Create Task ]
[ Title / Instructions ]
[ Deadline / File Types ]
     |
     |-----------> [ Save Task linked to Training ]
                            |
                   [ Notify All Approved Participants ]
                            |
                            |----------------------> [ View Task in
                                                       My Training ]
                                                             |
                                                             v
                                                    [ Click Submit ]
                                                             |
                                                             v
                            |<---------- [ System Checks Eligibility ]
                                         [ - Approved applicant?  ]
                                         [ - Before deadline?     ]
                                         [ - Not yet submitted?   ]
                                                    |
                                          < All Checks Pass? >
                                           /                  \
                                         NO                   YES
                                          |                    |
                                 [ Show Error ]    [ Display Submission Form ]
                                          |                    |
                              (back) <----+        [ Upload File / Enter Text ]
                                                             |
                                                    [ Click Submit ]
                                                             |
                                                             v
                            |<------------------------[ Save TASK_SUBMISSION ]
                   [ Update APPLICATION link ]
                            |
                   [ Show "Submitted Successfully" ]
                            |
                            |                         (async: grading)
                            v
                  [ Guru Besar Reviews ]
                  [ Submission         ]
                            |
                  [ Enter Grade/Comment ]
                            |
                  [ Click Save Grade ]
                            |
                  [ Update Submission Record ]
                            |
                  [ Notify Participant: Graded ]
                            |
                           (*)
```

---

## M06 — Quiz / Answer Sheet Activity Diagram

```
Swimlanes: Guru Besar | System | Teacher/Outsider

Guru Besar              System              Teacher / Outsider
==============================================================

    (*)
     |
     v
[ Create Quiz ]
[ Title / Questions ]
[ Time Limit / Deadline ]
     |
     |-----------> [ Save Quiz linked to Training ]
                            |
                   [ Notify All Approved Participants ]
                            |
                            |----------------------> [ View Quiz in
                                                       My Training ]
                                                             |
                                                             v
                                                    [ Click Start Quiz ]
                                                             |
                            |<------------------------[ Check Eligibility ]
                                         [ - Approved applicant?  ]
                                         [ - Within date window?  ]
                                         [ - Not yet attempted?   ]
                                                    |
                                          < All Checks Pass? >
                                           /                  \
                                         NO                   YES
                                          |                    |
                                 [ Show Error ]    [ Display Quiz Questions ]
                                          |        [ Start Timer (if set)  ]
                              (back) <----+                    |
                                                    [ Answer Questions ]
                                                             |
                                                    < Timer Expired? >
                                                     /              \
                                                   YES              NO
                                                    |                |
                                          [ Auto-Submit ]  [ Click Submit Quiz ]
                                                    \                /
                                                     +------- -------+
                                                             |
                            |<--------------------------[ Save ANSWER_SHEET ]
                   [ Auto-grade MCQ answers ]
                            |
                   [ Show Score to Participant ]
                            |
                            +-----------------------> [ View Score / Results ]
                            |
                   [ Guru Besar Views Results ]
                   [ Adjust Short-Answer Scores ]
                            |
                           (*)
```

---

## M07 — Training History & Tracking Activity Diagram

```
Swimlanes: User | System

User                                  System
==============================================

         (*)
          |
          v
  [ Navigate to Training History ]
          |
          v
          |------------------------------> [ Retrieve APPLICATION Records ]
                                           [ Status = 'approved' for user ]
                                                    |
                                          [ Display History List ]
                                          [ Training / Date /    ]
                                          [ Attendance / Tasks   ]
          |
          v
  < Action? >
  /         \         \
View       Filter    Export
Details       |         |
  |           v         v
  |   [ Apply Date /  [ Generate PDF ]
  |     Category      [ or Printable ]
  |     Filter ]           |
  |           |     [ Download File ]
  |   [ Update List ]      |
  |           |            |
  +--------- -+------------+
              |
             (*)
```

---

## M08 — Notification System Activity Diagram

```
Swimlanes: System | User

System                                User
==============================================

         (*)
          |
          v
  [ Trigger Event Occurs ]
  [ e.g. App approved,    ]
  [ New training, Deadline ]
          |
          v
  [ Identify Target User(s) ]
          |
          v
  [ Create NOTIFICATION Record ]
  [ IsRead = false              ]
          |
          v
  [ Update Notification Badge Count ]
          |
          |------------------------------> [ User Sees Badge ]
                                                    |
                                          [ Click Notification Icon ]
                                                    |
                                                    v
                                          [ View Notification List ]
                                          [ (ordered by newest)    ]
                                                    |
                                          [ Mark All as IsRead = true ]
                                                    |
                                          < Click Notification? >
                                           /                    \
                                         YES                    NO
                                          |                      |
                                 [ Redirect to               [ Stay on
                                   Relevant Page ]            Notif. List ]
                                          \                    /
                                           +------------------+
                                                    |
                                          < Clear All? >
                                           /         \
                                         YES          NO
                                          |            |
                                 [ Delete All        (end)
                                   Notifications ]
                                          |
                                         (*)
```

---

## M09 — Feedback Submission Activity Diagram

```
Swimlanes: System | Teacher/Outsider | Guru Besar

System              Teacher / Outsider          Guru Besar
===========================================================

    (*)
     |
     v                    (after training completes)
[ Mark Training
  as 'Completed' ]
     |
     v
[ Notify Participants:
  "Submit Feedback" ]
     |
     |-----------------------> [ Receive Notification ]
                                          |
                                          v
                               [ Navigate to Submit
                                 Feedback Form ]
                                          |
                               [ Check Eligibility ]
                               [ - Attended training? ]
                               [ - Not yet submitted? ]
                                          |
                               < Checks Pass? >
                                /             \
                              NO              YES
                               |               |
                      [ Show Error ]  [ Display Feedback Form ]
                               |      [ Star Rating + Comment ]
                   (back) <----+               |
                                      [ Submit Feedback ]
                                               |
     |<---------------------------------[ Save FEEDBACK Record ]
[ Show "Thank You" Msg ]
     |
     |
     |  (async: Guru Besar reviews)
     |
     |                                              [ Navigate to
     |                                                Training Feedback ]
     |<----------------------------------------------[ View Feedback Summary ]
[ Retrieve All FEEDBACK
  Records for Training ]
     |
[ Display:
  - Avg Rating
  - Total Responses
  - Rating Distribution
  - Comments List      ]
     |
     |-----------------------------------------------> [ View Summary ]
                                                               |
                                                     < Export PDF? >
                                                      /           \
                                                    YES            NO
                                                     |              |
                                           [ Generate PDF ]       (end)
                                           [ Download     ]
                                                     |
                                                    (*)
```

---

## M09 — Generate Attendance Letter Activity Diagram

```
Swimlanes: Guru Besar | System

Guru Besar                            System
==============================================

         (*)
          |
          v
  [ Navigate to Completed Training ]
          |
          v
  [ Click "Generate Attendance Letters" ]
          |
          v
          |------------------------------> [ Retrieve ATTENDANCE RECORDS ]
                                           [ Status = 'present' only     ]
                                                    |
                                          [ Generate Letter per Participant ]
                                          [ - Name, Training Title         ]
                                          [ - Date, Location, Duration     ]
                                          [ - Signature placeholder        ]
                                                    |
                                          [ Package as ZIP / Individual PDFs ]
                                                    |
                                          [ Prompt Download ]
          |
          v
  [ Download Files ]
          |
         (*)
```

---

## M01 — Admin Creates Teacher Account Activity Diagram

```
Swimlanes: System Admin | System | [External System — Phase 2 only]

System Admin                  System                External System
================================================================

         (*)
          |
          v
  [ Navigate to User
    Management → Teachers ]
          |
          v
  [ Click "Add Teacher" ]
          |
          v
          |-----> [ Display Create Teacher Form ]
                  [ Name / Email / Dept / Position ]
          |
          v
  [ Fill Form & Submit ]
          |
          v
          |-----> [ Validate Fields ]
                           |
                 < Fields Valid? >
                  /               \
                NO                YES
                 |                 |
        [ Show Errors ]  [ Create USER record ]
                 |        [ Role = 'teacher'  ]
     (back) <---+         [ Create TEACHER    ]
                          [ profile record    ]
                                   |
                          [ Generate temp.    ]
                          [ password / send   ]
                          [ invite email      ]
                                   |
                          [ Show Success Msg  ]
                                   |
                                  (*)

Phase 2 branch (if sync is active):
  Instead of manual form entry, Admin clicks "Sync from External System".
  System calls ExternalTeacherRepository::fetchAll().
  System creates/updates USER + TEACHER records from TeacherDTO.
  Existing accounts are matched by ExternalID and updated (not duplicated).
  New accounts are created. Removed staff are flagged (not deleted).
  Audit log entry written for each change.
```

---

## M02 — Auto-Close Training Activity Diagram

```
Swimlanes: System (Scheduler)

System
======================================

         (*)
          |
          v
  [ Scheduler fires (daily) ]
          |
          v
  [ Query TRAINING records where: ]
  [ Status = 'open' AND            ]
  [ (RegistrationDeadline < today  ]
  [  OR approved_count >= Capacity)]
          |
          v
  < Any matching records? >
   /                      \
 NO                       YES
  |                        |
 (*)              [ For each matching training: ]
                  [ Update Status = 'closed'    ]
                           |
                  [ Notify all pending applicants: ]
                  [ "Registration for [Title]     ]
                  [  is now closed."               ]
                           |
                  [ Write AUDIT_LOG entry          ]
                           |
                          (*)
```

---

## M10 — Sync Staff Data Activity Diagram *(Phase 2 — not active in Phase 1)*

```
Swimlanes: System (Scheduler / Admin CLI) | External System

System                                    External System
=========================================================

         (*)
          |
          v
  [ Trigger: Scheduler or
    php artisan edutrain:sync-staff ]
          |
          v
          |------------------------------> [ Receive fetch request ]
                                                    |
                                          < Respond OK? >
                                           /           \
                                         NO            YES
                                          |              |
                               [ Log 'sync_failed' ] [ Return staff records ]
                               [ to AUDIT_LOG      ]   (TeacherDTO array)
                               [ Exit non-zero     ]          |
                   (*)  <------+                    [ Validate each DTO ]
                                                             |
                                                   < DTO valid? >
                                                    /          \
                                                  NO           YES
                                                   |             |
                                          [ Log 'record_  < ExternalID exists
                                            skipped' ]     in USER table? >
                                                   |        /           \
                                                  (skip)  YES            NO
                                                           |              |
                                                  [ Update Name,  [ Create USER ]
                                                    Dept,          [ + TEACHER  ]
                                                    Position ]     [ record     ]
                                                           |              |
                                                           +--------------+
                                                                  |
                                                    [ Write AUDIT_LOG ]
                                                    [ per changed record ]
                                                                  |
                                                    [ Log 'sync_completed' ]
                                                    [ summary to AUDIT_LOG ]
                                                                  |
                                                                 (*)
```

---

*Document prepared based on PSM Proposal by Izzhilmy Bin Shamsul Bahri, UTeM FTMK, Session 2025/2026.*  
*[Design Decision] markers indicate features derived from ERD design, not explicitly stated in the proposal.*  
*Updated to version 1.1 with Admin-Creates-Teacher flow, Auto-Close Training, and M10 Sync Staff Data diagrams.*
