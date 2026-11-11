-- TrainHub Al Amin — 19 October 2026 stage-name migration.
-- PURPOSE: observer_assignment.assignmentType must store EXACT stage names:
--   'Pre Observation', 'External Observation', 'Post Observation'
-- PRECONDITION: the October 12 observer_assignment Post columns/constraints exist.
-- RUN ONCE on a backed-up TEST COPY of alamin_main_database with DB owner approval.
-- This is a schema/data migration, not an import. Do not run on the shared live DB.
-- DDL auto-commits in MySQL, so a backup is REQUIRED before execution.
-- The friend's tables pre_response/pdpc_response retain their OWN schema and
-- status codes (e.g., pdpc_response.observation_stage 'EXTERNAL'/'POST').
-- TrainHub maps these externally; changing another system's ENUMs would break it.

-- 0. BEFORE: inspect existing assignments and check constraint names.
SELECT assignmentID, gn_id, assignmentType, attemptNo, observerID,
       externalObserverID, postTeacherID
FROM observer_assignment ORDER BY gn_id, assignedDate, assignmentID;

-- 1. Temporarily accept legacy labels AND final names to avoid ENUM truncation.
-- Removing existing CHECK / UNIQUE first is necessary when normalizing rows:
-- historical NULL-stage rows often share attemptNo=1 within a new teacher.
ALTER TABLE `observer_assignment` DROP CHECK `chk_observer_assignment_post_fields`;
ALTER TABLE `observer_assignment` DROP INDEX `uq_observer_post_attempt`;
ALTER TABLE `observer_assignment`
  MODIFY COLUMN `assignmentType` ENUM(
    'observer', 'external', 'post',
    'Pre Observation', 'External Observation', 'Post Observation'
  ) NULL DEFAULT NULL;

-- 2. Create a one-time mapping for all valid historical assignments.
-- Pre/External attempts are renumbered separately per GN and stage to retain
-- history and allow the restored unique (gn_id, assignmentType, attemptNo).
-- POST attemptNo is PRESERVED (results in pdpc_response refer to attempt_no).
DROP TEMPORARY TABLE IF EXISTS `trainhub_stage_normalization`;
CREATE TEMPORARY TABLE `trainhub_stage_normalization` AS
SELECT
    a.assignmentID,
    a.normalized_stage,
    CASE WHEN a.normalized_stage = 'Post Observation' THEN a.attemptNo
         ELSE ROW_NUMBER() OVER (
           PARTITION BY a.gn_id, a.normalized_stage
           ORDER BY a.assignedDate, a.assignmentID
         )
    END AS normalized_attempt
FROM (
    SELECT
      assignmentID, gn_id, assignedDate, attemptNo,
      CASE
        WHEN assignmentType IN ('Post Observation', 'post')
             OR (assignmentType IS NULL AND postTeacherID IS NOT NULL)
          THEN 'Post Observation'
        WHEN assignmentType IN ('Pre Observation', 'observer')
             OR (assignmentType IS NULL AND observerID IS NOT NULL AND externalObserverID IS NULL)
          THEN 'Pre Observation'
        WHEN assignmentType IN ('External Observation', 'external')
             OR (assignmentType IS NULL AND externalObserverID IS NOT NULL AND observerID IS NULL)
          THEN 'External Observation'
        ELSE NULL
      END AS normalized_stage
    FROM `observer_assignment`
) AS a
WHERE a.normalized_stage IS NOT NULL;

UPDATE `observer_assignment` AS oa
JOIN `trainhub_stage_normalization` AS m ON m.assignmentID = oa.assignmentID
SET oa.assignmentType = m.normalized_stage,
    oa.attemptNo = m.normalized_attempt;
DROP TEMPORARY TABLE IF EXISTS `trainhub_stage_normalization`;

-- 3. Confirm no old stage labels remain, then restrict to the three full names.
ALTER TABLE `observer_assignment`
  MODIFY COLUMN `assignmentType` ENUM(
    'Pre Observation', 'External Observation', 'Post Observation'
  ) NULL DEFAULT NULL;
ALTER TABLE `observer_assignment`
  ADD UNIQUE KEY `uq_observer_post_attempt` (`gn_id`, `assignmentType`, `attemptNo`);
ALTER TABLE `observer_assignment`
  ADD CONSTRAINT `chk_observer_assignment_post_fields` CHECK (
    `assignmentType` IS NULL OR `assignmentType` <> 'Post Observation' OR
    (`attemptNo` >= 1 AND `postTeacherID` IS NOT NULL AND
     `observationDate` IS NOT NULL AND `createdByStaff` IS NOT NULL)
  );

-- 4. AFTER: review all records (NULL should appear only for truly unmatched
-- or incomplete legacy rows; resolve those with the database owner).
SELECT assignmentID, gn_id, assignmentType, attemptNo, observerID,
       externalObserverID, postTeacherID
FROM observer_assignment ORDER BY gn_id, assignmentType, attemptNo;
SELECT assignmentType, COUNT(*) AS records
FROM observer_assignment GROUP BY assignmentType;
