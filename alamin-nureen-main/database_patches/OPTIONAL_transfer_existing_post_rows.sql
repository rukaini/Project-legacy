-- OPTIONAL ONLY: run if the OLD separate trainhub_post_assignment table
-- already exists and contains real rows you need to preserve. BACK UP first.
-- FIRST apply 2026_10_12_alter_observer_assignment_for_post.sql.
-- NEVER run this if the source table does not exist.
-- Review all old rows and check the teacher/staff FKs before transferring.
USE `alamin_main_database`;

SELECT postAssignmentID, gn_id, attempt_no, teacherID, observationDate,
       status, createdByStaff FROM trainhub_post_assignment ORDER BY gn_id, attempt_no;

-- Preserve old attempt numbers and post teachers. Keep the old source table
-- until you have fully verified the newly inserted assignments.
INSERT INTO observer_assignment
    (assignmentID, gn_id, observerID, externalObserverID, assignedDate, endDate,
     status, assignmentType, attemptNo, postTeacherID, observationDate, createdByStaff)
SELECT CONCAT('PST', LPAD(UPPER(HEX(pa.postAssignmentID)), 16, '0')),
       pa.gn_id, NULL, NULL, pa.assignedDate, NULL,
       IF(pa.status = 'cancelled', 'inactive', 'active'), 'post',
       pa.attempt_no, pa.teacherID, pa.observationDate, pa.createdByStaff
FROM trainhub_post_assignment pa
WHERE NOT EXISTS (
    SELECT 1 FROM observer_assignment oa
    WHERE oa.gn_id = pa.gn_id
      AND oa.assignmentType = 'post'
      AND oa.attemptNo = pa.attempt_no
);

-- Read-only reconciliation. Expected: one observer_assignment post row
-- for every distinct gn_id/attempt_no in the old table.
SELECT oa.gn_id, oa.attemptNo, oa.assignmentID, oa.postTeacherID
FROM observer_assignment oa
WHERE oa.assignmentType = 'post'
ORDER BY oa.gn_id, oa.attemptNo;

-- Do NOT drop trainhub_post_assignment in this script.
