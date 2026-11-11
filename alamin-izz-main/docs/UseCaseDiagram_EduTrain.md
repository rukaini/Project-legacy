# Use Case Diagram (ASCII)
## Educator Training Management System (EduTrain System)

**Project Code:** BITU 3973
**Version:** 1.1
**Student:** Izzhilmy Bin Shamsul Bahri (B032420045)
**Institution:** Universiti Teknikal Malaysia Melaka (UTeM) — FTMK
**Session:** 2025/2026, Semester 2

> **Version 1.1 Change Note:** Added External System actor. Added integration-related use cases to System actor. Updated include/extend relationships. Added Phase 2 annotation to integration use cases.

---

## Legend

```
  ( Use Case )     = Oval  = a system function
  [ Actor ]        = Box   = an external role interacting with the system
  O
  |                = Stick figure = Actor
  ^
 / \

  -------->        = Association (actor performs use case)
  - - - ->         = <<include>> or <<extend>> relationship
```

---

## System Boundary: EduTrain System

```
+---------------------------------------------------------------------------------------------------------------------------------------+
|                                                       EduTrain System                                                                 |
|                                                                                                                                       |
|   +----------------------------+      +-------------------------------+      +-----------------------------+                          |
|   |   Authentication Module    |      |   Training Management Module  |      |  Application & Approval     |                          |
|   |                            |      |                               |      |  Module                     |                          |
|   |  ( Register Account )      |      |  ( Create Training Program )  |      |  ( Apply for Training )     |                          |
|   |  ( Login )                 |      |  ( Edit Training Program )    |      |  ( View Application Status )|                          |
|   |  ( Logout )                |      |  ( Cancel Training Program )  |      |  ( Approve Application )    |                          |
|   |  ( Reset Password )        |      |  ( View Training Programs )   |      |  ( Reject Application )     |                          |
|   |  ( Update Profile )        |      |                               |      |  ( Assign Teacher Directly )|                          |
|   +----------------------------+      +-------------------------------+      +-----------------------------+                          |
|                                                                                                                                       |
|   +----------------------------+      +-------------------------------+      +-----------------------------+                          |
|   |   Attendance Module        |      |   Task & Quiz Module          |      |  History & Tracking Module  |                          |
|   |                            |      |                               |      |                             |                          |
|   |  ( Generate QR Code )      |      |  ( Create Task )              |      |  ( View Training History )  |                          |
|   |  ( Mark Attendance )       |      |  ( Submit Task )              |      |  ( Export Training History )|                          |
|   |  ( View Attendance Report )|      |  ( Grade Task Submission )    |      |  ( View Teacher History )   |                          |
|   |  ( Auto-Mark Absent )      |      |  ( Create Quiz )              |      |  ( View Dev. Report )       |                          |
|   |                            |      |  ( Attempt Quiz )             |      |                             |                          |
|   |                            |      |  ( View Quiz Results )        |      |                             |                          |
|   +----------------------------+      +-------------------------------+      +-----------------------------+                          |
|                                                                                                                                       |
|   +----------------------------+      +-------------------------------+                                                               |
|   |   Notification Module      |      |   Feedback & Reporting Module |                                                               |
|   |                            |      |                               |                                                               |
|   |  ( Receive Notification )  |      |  ( Submit Feedback )          |                                                               |
|   |  ( View Notifications )    |      |  ( View Feedback Summary )    |                                                               |
|   |  ( Clear Notifications )   |      |  ( Generate Attend. Letter )  |                                                               |
|   |                            |      |  ( Generate Summary Report )  |                                                               |
|   +----------------------------+      +-------------------------------+                                                               |
|                                                                                                                                       |
+---------------------------------------------------------------------------------------------------------------------------------------+
```

---

## Actor–Use Case Associations

### Guru Besar (Principal)

```
        O
        |   (Guru Besar)
       / \
        |
        +-----------> ( Login )
        |-----------> ( Logout )
        |-----------> ( Reset Password )
        |-----------> ( Update Profile )
        |
        |-----------> ( Create Training Program )
        |-----------> ( Edit Training Program )
        |-----------> ( Cancel Training Program )
        |-----------> ( View Training Programs )
        |
        |-----------> ( Approve Application )
        |-----------> ( Reject Application )
        |-----------> ( Assign Teacher Directly )
        |
        |-----------> ( Generate QR Code )          [Design Decision]
        |-----------> ( View Attendance Report )
        |
        |-----------> ( Create Task )
        |-----------> ( Grade Task Submission )
        |-----------> ( Create Quiz )
        |-----------> ( View Quiz Results )
        |
        |-----------> ( View Teacher History )
        |-----------> ( View Dev. Report )
        |
        |-----------> ( Receive Notification )
        |-----------> ( View Notifications )
        |
        +-----------> ( View Feedback Summary )
        +-----------> ( Generate Attend. Letter )
        +-----------> ( Generate Summary Report )
```

---

### Teacher

```
        O
        |   (Teacher)
       / \
        |
        +-----------> ( Login )
        |-----------> ( Logout )
        |-----------> ( Reset Password )
        |-----------> ( Update Profile )
        |
        |-----------> ( View Training Programs )
        |
        |-----------> ( Apply for Training )
        |-----------> ( View Application Status )
        |
        |-----------> ( Mark Attendance )           [Design Decision - QR]
        |
        |-----------> ( Submit Task )
        |-----------> ( Attempt Quiz )
        |
        |-----------> ( View Training History )
        |-----------> ( Export Training History )
        |
        |-----------> ( Receive Notification )
        |-----------> ( View Notifications )
        |-----------> ( Clear Notifications )
        |
        +-----------> ( Submit Feedback )
```

---

### Outsider

```
        O
        |   (Outsider)
       / \
        |
        +-----------> ( Register Account )
        |-----------> ( Login )
        |-----------> ( Logout )
        |-----------> ( Reset Password )
        |-----------> ( Update Profile )
        |
        |-----------> ( View Training Programs )
        |
        |-----------> ( Apply for Training )
        |-----------> ( View Application Status )
        |
        |-----------> ( Mark Attendance )           [Design Decision - QR]
        |
        |-----------> ( Submit Task )
        |-----------> ( Attempt Quiz )
        |
        |-----------> ( View Training History )
        |-----------> ( Export Training History )
        |
        |-----------> ( Receive Notification )
        |-----------> ( View Notifications )
        |-----------> ( Clear Notifications )
        |
        +-----------> ( Submit Feedback )
```

---

### System (Automated Actor)

```
   [System]
        |
        +-----------> ( Auto-Mark Absent )
        +-----------> ( Receive Notification )      [triggers notifications on events]
        +-----------> ( Auto-Close Training )       [when capacity reached or deadline passes]
        +-----------> ( Sync Staff Data )           [Phase 2 — M10-F01, via ExternalRepository adapter]
```

---

### External System *(Phase 2 Actor — not active in Phase 1)*

```
   [External System]
        |
        +-----------> ( Provide Staff Records )     [Phase 2 — called by ExternalTeacherRepository adapter]
```

> The External System is a passive data provider. It does not initiate actions within EduTrain. EduTrain pulls data from it via the repository adapter. All use cases involving the External System are Phase 2 only and are marked accordingly.

---

## Include / Extend Relationships

```
( Apply for Training )       --<<include>>-->  ( Login )
( Mark Attendance )          --<<include>>-->  ( Login )
( Submit Task )              --<<include>>-->  ( Login )
( Attempt Quiz )             --<<include>>-->  ( Login )
( Submit Feedback )          --<<include>>-->  ( Login )

( Apply for Training )       --<<include>>-->  ( View Training Programs )

( Approve Application )      --<<extend>> -->  ( Receive Notification )
( Reject Application )       --<<extend>> -->  ( Receive Notification )
( Assign Teacher Directly )  --<<extend>> -->  ( Receive Notification )
( Create Training Program )  --<<extend>> -->  ( Receive Notification )
( Grade Task Submission )    --<<extend>> -->  ( Receive Notification )
( Auto-Mark Absent )         --<<extend>> -->  ( Receive Notification )
( Auto-Close Training )      --<<extend>> -->  ( Receive Notification )

( Mark Attendance )          --<<include>>-->  ( Generate QR Code )

[Phase 2 only]
( Sync Staff Data )          --<<include>>-->  ( Provide Staff Records )
( Login )                    --<<extend>> -->  ( Sync Staff Data )        [on first SSO login, auto-provision if ExternalID not found]
```

---

## Full System Use Case Overview (Compact View)

```
+---------------------------------------------------------------------+
|                        EduTrain System                              |
+---------------------------------------------------------------------+
|                                                                     |
|  ACTOR: Guru Besar          ACTOR: Teacher        ACTOR: Outsider  |
|  O                          O                     O                |
|  |                          |                     |                |
|  |-- Login/Logout           |-- Login/Logout       |-- Register     |
|  |-- Manage Training        |-- View Training      |-- Login/Logout |
|  |-- Approve/Reject Apps    |-- Apply Training     |-- View Training|
|  |-- Assign Teacher         |-- View App Status    |-- Apply Train. |
|  |-- Generate QR*           |-- Mark Attendance*   |-- Mark Attend.*|
|  |-- View Attendance        |-- Submit Task        |-- Submit Task  |
|  |-- Create Task/Quiz       |-- Attempt Quiz       |-- Attempt Quiz |
|  |-- Grade Submissions      |-- View History       |-- View History |
|  |-- View Teacher History   |-- Export History     |-- Export Hist. |
|  |-- View Dev. Report       |-- Notifications      |-- Notif.       |
|  |-- Notifications          |-- Submit Feedback    |-- Feedback     |
|  |-- View Feedback          |                      |                |
|  |-- Gen. Attend. Letter    |                      |                |
|  +-- Gen. Summary Report    |                      |                |
|                             |                      |                |
|  ACTOR: System (Auto)          ACTOR: Ext. System *(Phase 2)*      |
|  |-- Auto-Mark Absent           |-- Provide Staff Records           |
|  |-- Auto-Close Training        |                                   |
|  +-- Send Notifications         |                                   |
|                                                                     |
|  * = Design Decision (QR-based, not stated in original proposal)    |
|  ** = Phase 2 only — external system schema and API TBD            |
+---------------------------------------------------------------------+
```

---

*Document prepared based on PSM Proposal by Izzhilmy Bin Shamsul Bahri, UTeM FTMK, Session 2025/2026.*  
*[Design Decision] markers indicate features derived from ERD design, not explicitly stated in the proposal.*  
*Updated to version 1.1 with External System actor and Phase 2 integration use cases.*
