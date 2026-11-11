<?php

namespace App\Support;

use mysqli;
use RuntimeException;

/**
 * TrainHub schedules Pre Observation, External Observation and Post Observation in the existing
 * observer_assignment table. Teacher Employment owns the assessment outcomes
 * in pdpc_response. Never overwrite that system's results or create fake forms.
 *
 * observer_assignment.assignmentType uses three readable values:
 * 'Pre Observation', 'External Observation', and 'Post Observation'.
 * The separate assessment system still stores EXTERNAL / POST in pdpc_response.
 */
final class PostObservationWorkflow
{
    public static function tableExists(mysqli $conn, string $name): bool
    {
        static $cache = [];
        $key = spl_object_id($conn).':'.$name;
        if (array_key_exists($key, $cache)) return $cache[$key];
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        return $cache[$key] = ((int)($stmt->get_result()->fetch_assoc()['total'] ?? 0) > 0);
    }

    public static function latestResult(mysqli $conn, string $newTeacherId, string $stage, ?int $attempt = null): ?array
    {
        if (!self::tableExists($conn, 'pdpc_response')) return null;
        if ($attempt === null) {
            $stmt = $conn->prepare("SELECT responseID, attempt_no, result, observation_date FROM pdpc_response
                WHERE gn_id = ? AND observation_stage = ? AND status = 'Submitted'
                ORDER BY attempt_no DESC, responseID DESC LIMIT 1");
            $stmt->bind_param('ss', $newTeacherId, $stage);
        } else {
            $stmt = $conn->prepare("SELECT responseID, attempt_no, result, observation_date FROM pdpc_response
                WHERE gn_id = ? AND observation_stage = ? AND attempt_no = ? AND status = 'Submitted'
                ORDER BY responseID DESC LIMIT 1");
            $stmt->bind_param('ssi', $newTeacherId, $stage, $attempt);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function preResult(mysqli $conn, string $newTeacherId): ?array
    {
        if (!self::tableExists($conn, 'pre_response')) return null;
        $stmt = $conn->prepare("SELECT observation_date, achievement_level, percentage, status
            FROM pre_response WHERE gn_id = ? AND status = 'Submitted'
            ORDER BY observation_date DESC, responseID DESC LIMIT 1");
        $stmt->bind_param('s', $newTeacherId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /** Confirm the shared table was extended with the required POST columns. */
    public static function schemaReady(mysqli $conn): bool
    {
        if (!self::tableExists($conn, 'observer_assignment') || !self::tableExists($conn, 'pdpc_response')) {
            return false;
        }
        $required = ['assignmentType', 'attemptNo', 'postTeacherID', 'observationDate', 'createdByStaff'];
        $stmt = $conn->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'observer_assignment'");
        $stmt->execute();
        $available = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'COLUMN_NAME');
        return count(array_diff($required, $available)) === 0;
    }

    public static function assignments(mysqli $conn, string $newTeacherId): array
    {
        if (!self::schemaReady($conn)) return [];
        $stmt = $conn->prepare("SELECT oa.assignmentID, oa.attemptNo AS attempt_no,
                oa.postTeacherID AS teacherID, oa.observationDate, oa.status,
                oa.assignedDate, oa.createdByStaff, t.teacherName, s.schoolName
            FROM observer_assignment oa
            JOIN teacher t ON t.teacherID = oa.postTeacherID
            LEFT JOIN v_teacher_current_school tcs ON tcs.teacherID = t.teacherID
            LEFT JOIN school s ON s.schoolID = tcs.schoolID
            WHERE oa.gn_id = ? AND oa.assignmentType = 'Post Observation'
            ORDER BY oa.attemptNo ASC");
        $stmt->bind_param('s', $newTeacherId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * All submitted outcomes, including a submitted assessment awaiting the
     * final PASS/REPEAT decision. A NULL result must not unlock a new attempt.
     * Draft assessments are never treated as completed decisions.
     */
    public static function submittedResults(mysqli $conn, string $newTeacherId, string $stage): array
    {
        if (!self::tableExists($conn, 'pdpc_response')) return [];
        $stmt = $conn->prepare("SELECT responseID, attempt_no, result, observation_date
            FROM pdpc_response WHERE gn_id = ? AND observation_stage = ?
              AND status = 'Submitted' ORDER BY attempt_no ASC, responseID ASC");
        $stmt->bind_param('ss', $newTeacherId, $stage);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * An External Observation REPEAT *offers* Post Observation Attempt 1; it never creates it.
     * Repeating External Observation instead is always handled by the Teacher
     * Employment system. A later External PASS closes new optional Post
     * assignments, but previous Post Observation assignments are never deleted.
     * Once Post has been chosen, only a submitted Post REPEAT allows a new
     * Post attempt; a missing or PASS decision stops the sequence.
     */
    public static function nextAttempt(?string $externalResult, array $history): ?int
    {
        if ($externalResult !== 'REPEAT') return null;
        if ($history === []) return 1;
        $last = end($history);
        if (($last['result'] ?? null) !== 'REPEAT') return null;
        return (int)$last['attempt'] + 1;
    }

    /** This is a choice, not a required transition or an automatic assignment. */
    public static function canOfferOptionalPost(?string $externalResult, array $history): bool
    {
        return self::nextAttempt($externalResult, $history) !== null;
    }

    public static function state(mysqli $conn, string $newTeacherId): array
    {
        $externalHistory = self::submittedResults($conn, $newTeacherId, 'EXTERNAL');
        $external = $externalHistory === [] ? null : end($externalHistory);
        $externalResult = $external['result'] ?? null;
        $assignments = self::assignments($conn, $newTeacherId);
        $history = [];
        foreach ($assignments as $assignment) {
            $attempt = (int)$assignment['attempt_no'];
            $history[$attempt] = [
                'attempt' => $attempt,
                'teacher' => $assignment['teacherName'],
                'school' => $assignment['schoolName'],
                'teacherID' => $assignment['teacherID'],
                'date' => $assignment['observationDate'],
                'result' => null,
                'status' => $assignment['status'],
            ];
        }

        // Preserve submitted POST records created before TrainHub supported
        // assignments. GN0005, for example, has a submitted result of NULL:
        // it must remain visible but must not unlock Attempt 2.
        foreach (self::submittedResults($conn, $newTeacherId, 'POST') as $row) {
            $n = (int)$row['attempt_no'];
            if (!isset($history[$n])) {
                $history[$n] = [
                    'attempt' => $n,
                    'teacher' => 'Recorded in Teacher Employment System',
                    'school' => '',
                    'teacherID' => '',
                    'date' => $row['observation_date'],
                    'result' => $row['result'],
                    'status' => 'Submitted',
                ];
            } else {
                $history[$n]['result'] = $row['result'];
                $history[$n]['status'] = 'Submitted';
                $history[$n]['date'] = $row['observation_date'] ?: $history[$n]['date'];
            }
        }
        ksort($history);
        $history = array_values($history);
        $nextAttempt = self::nextAttempt($externalResult, $history);
        return [
            'preResult' => self::preResult($conn, $newTeacherId),
            'externalResult' => $externalResult,
            'externalAttempt' => $external['attempt_no'] ?? null,
            'externalHistory' => array_map(static fn(array $row): array => [
                'attempt' => (int)$row['attempt_no'],
                'result' => $row['result'],
                'date' => $row['observation_date'],
            ], $externalHistory),
            'history' => $history,
            'nextAttempt' => $nextAttempt,
            'postOptional' => true,
            'ready' => self::schemaReady($conn),
        ];
    }

    public static function assign(mysqli $conn, string $newTeacherId, string $teacherId, string $observationDate, string $staffId): int
    {
        if (!self::schemaReady($conn)) {
            throw new RuntimeException('Post Observation setup is incomplete. Apply the included observer_assignment ALTER TABLE patch first.');
        }
        if (!$observationDate || $observationDate < date('Y-m-d') || !\DateTimeImmutable::createFromFormat('!Y-m-d', $observationDate)
            || \DateTimeImmutable::createFromFormat('!Y-m-d', $observationDate)->format('Y-m-d') !== $observationDate) {
            throw new RuntimeException('Choose a valid observation date.');
        }
        $conn->begin_transaction();
        try {
            // Serialise concurrent post submissions for the same new teacher.
            $lock = $conn->prepare('SELECT current_status FROM guru_new WHERE gn_id = ? FOR UPDATE');
            $lock->bind_param('s', $newTeacherId);
            $lock->execute();
            $newTeacher = $lock->get_result()->fetch_assoc();
            if (!$newTeacher || strtolower(trim((string)$newTeacher['current_status'])) !== 'active') {
                throw new RuntimeException('Post Observation assignment requires an active new teacher.');
            }
            $teacher = $conn->prepare('SELECT t.teacherID FROM teacher t
                WHERE t.teacherID = ? AND t.appointedDate IS NOT NULL
                AND t.appointedDate < DATE_SUB(CURDATE(), INTERVAL 10 YEAR) LIMIT 1');
            $teacher->bind_param('s', $teacherId);
            $teacher->execute();
            if (!$teacher->get_result()->fetch_assoc()) {
                throw new RuntimeException('Select an eligible teacher with more than 10 years of service.');
            }
            $state = self::state($conn, $newTeacherId);
            if (!$state['nextAttempt']) {
                throw new RuntimeException('Post Observation is optional. A new Post Observation attempt is available only when the latest submitted External Observation result is REPEAT (or the last Post Observation result is REPEAT). A PASS or pending result prevents further assignments.');
            }
            $next = (int)$state['nextAttempt'];
            // Use a collision-resistant string key, avoiding the legacy ASG
            // numeric sequence used by simultaneous regular assignments.
            $assignmentId = 'PST' . strtoupper(bin2hex(random_bytes(8)));
            $stmt = $conn->prepare("INSERT INTO observer_assignment
                (assignmentID, gn_id, observerID, externalObserverID,
                 assignedDate, endDate, status, assignmentType, attemptNo,
                 postTeacherID, observationDate, createdByStaff)
                VALUES (?, ?, NULL, NULL, CURDATE(), NULL, 'active', 'Post Observation', ?, ?, ?, ?)");
            $stmt->bind_param('ssisss', $assignmentId, $newTeacherId, $next, $teacherId, $observationDate, $staffId);
            $stmt->execute();
            $name = trim((string)($_SESSION['staffName'] ?? $_SESSION['staff_name'] ?? $staffId));
            $details = "Assigned Post Observation attempt {$next} for {$newTeacherId} to teacher {$teacherId} on {$observationDate}";
            $audit = $conn->prepare("INSERT INTO audit_log (staffID, userName, actionType, tableName, newValue)
                VALUES (?, ?, 'INSERT', 'observer_assignment', ?)");
            $audit->bind_param('sss', $staffId, $name, $details);
            $audit->execute();
            $conn->commit();
            return $next;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
