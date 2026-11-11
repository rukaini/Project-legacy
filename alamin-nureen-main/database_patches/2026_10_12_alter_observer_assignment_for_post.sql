-- TrainHub Al Amin — one-table Post Observation patch (MySQL 8 / DBeaver)
-- IMPORTANT: back up alamin_main_database and obtain the shared DB owner's
-- approval before running. Run ONLY ONCE. Do not run migrate:fresh / db:wipe.
-- This MODIFIES observer_assignment; it creates NO new table and does not
-- delete any current Observer/External Observer assignment rows.
--
-- Existing assignmentType is intentionally NULL for legacy rows: observerID
-- and externalObserverID already distinguish them. All new POST rows explicitly
-- have assignmentType='post' and a positive attemptNo. The CHECK constraint deliberately does not reference
-- observerID/externalObserverID because those fields have ON DELETE/UPDATE
-- referential actions, which MySQL 8 forbids in CHECK expressions.
-- This avoids accidental
-- uniqueness conflicts among historical observer / external assignment rows.
-- A UNIQUE key on (gn_id, assignmentType, attemptNo) prevents duplicate POST
-- attempts, while MySQL permits multiple historical NULL assignmentType rows.

USE `alamin_main_database`;

ALTER TABLE `observer_assignment`
  ADD COLUMN `assignmentType` ENUM('observer','external','post') NULL DEFAULT NULL,
  ADD COLUMN `attemptNo` INT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN `postTeacherID` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `observationDate` DATE DEFAULT NULL,
  ADD COLUMN `createdByStaff` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD UNIQUE KEY `uq_observer_post_attempt` (`gn_id`, `assignmentType`, `attemptNo`),
  ADD KEY `idx_observer_assignment_post_teacher` (`postTeacherID`),
  ADD KEY `idx_observer_assignment_post_staff` (`createdByStaff`),
  ADD CONSTRAINT `fk_observer_assignment_post_teacher`
    FOREIGN KEY (`postTeacherID`) REFERENCES `teacher` (`teacherID`),
  ADD CONSTRAINT `fk_observer_assignment_post_creator`
    FOREIGN KEY (`createdByStaff`) REFERENCES `staff_edu` (`staffID`),
  ADD CONSTRAINT `chk_observer_assignment_post_fields` CHECK (
    `assignmentType` IS NULL OR `assignmentType` <> 'post' OR
    (`attemptNo` >= 1 AND `postTeacherID` IS NOT NULL AND
     `observationDate` IS NOT NULL AND `createdByStaff` IS NOT NULL)
  );

-- Read-only verification (should list five added columns):
SHOW COLUMNS FROM `observer_assignment` WHERE `Field` IN
  ('assignmentType','attemptNo','postTeacherID','observationDate','createdByStaff');

-- See historical rows without changing them:
SELECT assignmentID, gn_id, observerID, externalObserverID,
       assignmentType, attemptNo, postTeacherID, observationDate
FROM observer_assignment ORDER BY assignedDate DESC LIMIT 20;
