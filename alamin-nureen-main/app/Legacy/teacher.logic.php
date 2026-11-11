<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!$conn->select_db(TRAINHUB_DATABASE_NAME)) {
    http_response_code(500);
    exit('Unable to select the configured TrainHub database.');
}
date_default_timezone_set('Asia/Kuala_Lumpur');

use App\Support\PostObservationWorkflow;

$staffID = $_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? '';
if ($staffID === '') {
    header('Location: login.php');
    exit();
}

$staffName = $_SESSION['staffName'] ?? $_SESSION['staff_name'] ?? $staffID;
$setAuditStaff = $conn->prepare('SET @current_staff_id = ?, @current_staff_name = ?');
$setAuditStaff->bind_param('ss', $staffID, $staffName);
$setAuditStaff->execute();
$setAuditStaff->close();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        throw new RuntimeException('Your form session has expired. Please refresh the page and try again.');
    }
}


function generateNextId($conn, $table, $column, $prefix, $pad = 4) {
    $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    $prefixLength = strlen($prefix);
    $suffixStart = $prefixLength + 1;

    // Every ID column using this helper has one canonical prefix (OBS, EXT,
    // ASG). Avoid comparing a bound string with the column entirely: that
    // keeps the helper portable across utf8mb4_unicode_ci / binary parameter
    // collations in the combined database.
    $stmt = $conn->prepare("
        SELECT `$safeColumn` AS latest_id
        FROM `$safeTable`
        WHERE BINARY LEFT(`$safeColumn`, ?) = BINARY ?
        ORDER BY CAST(SUBSTRING(`$safeColumn`, ?) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $stmt->bind_param('isi', $prefixLength, $prefix, $suffixStart);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $num = 1;

    if ($row && !empty($row['latest_id']) && str_starts_with((string)$row['latest_id'], $prefix)) {
        $num = ((int)substr((string)$row['latest_id'], $prefixLength)) + 1;
    }

    return $prefix . str_pad((string)$num, $pad, '0', STR_PAD_LEFT);
}

function classifySchool(?string $schoolID, ?string $schoolName): string {
    $id = strtoupper((string)$schoolID);
    $name = strtoupper((string)$schoolName);
    $combined = $id . ' ' . $name;

    if (
        str_contains($combined, 'MENENGAH') ||
        str_contains($combined, 'SECONDARY') ||
        str_contains($combined, ' SEC') ||
        str_ends_with($id, 'SEC') ||
        str_starts_with($id, 'SMI')
    ) {
        return 'secondary';
    }

    if (
        str_contains($combined, 'RENDAH') ||
        str_contains($combined, 'PRIMARY') ||
        str_contains($combined, '(PRI)') ||
        str_contains($combined, ' PRI') ||
        str_starts_with($id, 'SRI')
    ) {
        return 'primary';
    }

    if (
        str_contains($combined, 'PRESCHOOL') ||
        str_contains($combined, 'TADIKA') ||
        str_contains($combined, 'EARLY CHILDHOOD') ||
        str_contains($combined, 'AATECC') ||
        str_starts_with($id, 'AHP')
    ) {
        return 'preschool';
    }

    return 'others';
}

function serviceYears($date): ?int {
    if (empty($date)) return null;
    try {
        return date_diff(date_create($date), date_create(date('Y-m-d')))->y;
    } catch (Throwable $e) {
        return null;
    }
}

function findObserverIDByTeacher($conn, $teacherID) {
    if (empty($teacherID)) return null;

    $stmt = mysqli_prepare($conn, "
        SELECT observerID
        FROM observer
        WHERE teacherID = ?
        ORDER BY observerID DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "s", $teacherID);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $row['observerID'] ?? null;
}

function findExternalObserverIDByTeacher($conn, $teacherID) {
    if (empty($teacherID)) return null;

    $stmt = mysqli_prepare($conn, "
        SELECT externalObserverID
        FROM external_observer
        WHERE teacherID = ?
        ORDER BY externalObserverID DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "s", $teacherID);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $row['externalObserverID'] ?? null;
}

function saveObserverRecord($conn, $currentObserverID, $teacherID, $startDate, $endDate, $status) {
    if (empty($teacherID)) return null;

    if (!empty($currentObserverID)) {
        $check = mysqli_prepare($conn, "SELECT teacherID FROM observer WHERE observerID = ? LIMIT 1");
        mysqli_stmt_bind_param($check, 's', $currentObserverID);
        mysqli_stmt_execute($check);
        $current = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

        // Update the role record only when Edit keeps the same teacher. If a
        // different teacher is selected, link the assignment to that teacher's
        // own observer record instead of changing a shared observer identity.
        if (($current['teacherID'] ?? '') === $teacherID) {
            $stmt = mysqli_prepare($conn, "
                UPDATE observer
                SET startDate = ?, endDate = ?, status = ?
                WHERE observerID = ?
            ");
            mysqli_stmt_bind_param($stmt, "ssss", $startDate, $endDate, $status, $currentObserverID);
            mysqli_stmt_execute($stmt);
            return $currentObserverID;
        }
    }

    $existingID = findObserverIDByTeacher($conn, $teacherID);
    if (!empty($existingID)) {
        $stmt = mysqli_prepare($conn, "
            UPDATE observer
            SET startDate = ?, endDate = ?, status = ?
            WHERE observerID = ?
        ");
        mysqli_stmt_bind_param($stmt, "ssss", $startDate, $endDate, $status, $existingID);
        mysqli_stmt_execute($stmt);

        return $existingID;
    }

    $observerID = generateNextId($conn, 'observer', 'observerID', 'OBS', 4);

    $stmt = mysqli_prepare($conn, "
        INSERT INTO observer (observerID, teacherID, startDate, endDate, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "sssss", $observerID, $teacherID, $startDate, $endDate, $status);
    mysqli_stmt_execute($stmt);

    return $observerID;
}

function saveExternalObserverRecord($conn, $currentExternalObserverID, $teacherID, $startDate, $endDate, $status) {
    if (empty($teacherID)) return null;

    if (!empty($currentExternalObserverID)) {
        $check = mysqli_prepare($conn, "SELECT teacherID FROM external_observer WHERE externalObserverID = ? LIMIT 1");
        mysqli_stmt_bind_param($check, 's', $currentExternalObserverID);
        mysqli_stmt_execute($check);
        $current = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

        if (($current['teacherID'] ?? '') === $teacherID) {
            $stmt = mysqli_prepare($conn, "
                UPDATE external_observer
                SET startDate = ?, endDate = ?, status = ?
                WHERE externalObserverID = ?
            ");
            mysqli_stmt_bind_param($stmt, "ssss", $startDate, $endDate, $status, $currentExternalObserverID);
            mysqli_stmt_execute($stmt);
            return $currentExternalObserverID;
        }
    }

    $existingID = findExternalObserverIDByTeacher($conn, $teacherID);
    if (!empty($existingID)) {
        $stmt = mysqli_prepare($conn, "
            UPDATE external_observer
            SET startDate = ?, endDate = ?, status = ?
            WHERE externalObserverID = ?
        ");
        mysqli_stmt_bind_param($stmt, "ssss", $startDate, $endDate, $status, $existingID);
        mysqli_stmt_execute($stmt);

        return $existingID;
    }

    $externalObserverID = generateNextId($conn, 'external_observer', 'externalObserverID', 'EXT', 4);

    $stmt = mysqli_prepare($conn, "
        INSERT INTO external_observer (externalObserverID, teacherID, startDate, endDate, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "sssss", $externalObserverID, $teacherID, $startDate, $endDate, $status);
    mysqli_stmt_execute($stmt);

    return $externalObserverID;
}

$errorMessage = '';
$allowedTabs = ['secondary', 'primary', 'preschool', 'others', 'organisations', 'new_teacher'];


/* POST is an optional choice after a submitted External REPEAT; repeating External remains possible. */
if (isset($_POST['assign_post_observer'])) {
    $returnSchoolID = trim((string)($_POST['return_school_id'] ?? ''));
    try {
        verifyCsrfToken();
        $newTeacherID = trim((string)($_POST['gn_id'] ?? ''));
        $teacherID = trim((string)($_POST['post_teacher_id'] ?? ''));
        $observationDate = trim((string)($_POST['post_observation_date'] ?? ''));
        if ($newTeacherID === '' || $teacherID === '') {
            throw new RuntimeException('Select a teacher for Post Observation.');
        }
        $attempt = PostObservationWorkflow::assign($conn, $newTeacherID, $teacherID, $observationDate, $staffID);
        $params = ['tab' => 'new_teacher', 'post_assigned' => $attempt];
        if ($returnSchoolID !== '') $params['schoolID'] = $returnSchoolID;
        header('Location: teacher.php?' . http_build_query($params));
        exit();
    } catch (Throwable $e) {
        $errorMessage = 'Post Observation assignment cannot be saved: ' . $e->getMessage();
        // Return to the correct school when the page re-renders after an error.
        $_GET['tab'] = 'new_teacher';
        if ($returnSchoolID !== '') $_GET['schoolID'] = $returnSchoolID;
    }
}

/* =========================
   SAVE OBSERVER ASSIGNMENT
========================= */

if (isset($_POST['assign_observer'])) {
    $returnTab = $_POST['return_tab'] ?? 'new_teacher';
    if (!in_array($returnTab, $allowedTabs, true)) {
        $returnTab = 'new_teacher';
    }
    $returnSchoolID = trim((string)($_POST['return_school_id'] ?? ''));

    try {
        verifyCsrfToken();

        $observerAssignmentID = $_POST['observerAssignmentID'] ?? '';
        $externalAssignmentID = $_POST['externalAssignmentID'] ?? '';
        $gn_id = trim((string)($_POST['gn_id'] ?? ''));
        $schoolID = $_POST['schoolID'] ?? '';

        if ($gn_id === '') {
            throw new RuntimeException('New teacher ID is missing.');
        }

        // Assignment is only allowed while the new teacher is active. Keep this
        // server-side check even though the UI hides the Assign/Edit button for
        // inactive records so a crafted POST cannot bypass the rule.
        $statusStmt = mysqli_prepare($conn, "SELECT current_status FROM guru_new WHERE gn_id = ? LIMIT 1");
        mysqli_stmt_bind_param($statusStmt, 's', $gn_id);
        mysqli_stmt_execute($statusStmt);
        $statusRow = mysqli_fetch_assoc(mysqli_stmt_get_result($statusStmt));
        $newTeacherStatus = strtolower(trim((string)($statusRow['current_status'] ?? '')));
        if (!in_array($newTeacherStatus, ['active', 'aktif'], true)) {
            throw new RuntimeException('Observer assignment is only allowed for an active new teacher.');
        }

        $currentObserverID = $_POST['currentObserverID'] ?? '';
        $currentExternalObserverID = $_POST['currentExternalObserverID'] ?? '';

        $observerTeacherID = !empty($_POST['observer_teacher_id']) ? $_POST['observer_teacher_id'] : null;
        $observerStartDate = !empty($_POST['observerStartDate']) ? $_POST['observerStartDate'] : null;
        $observerEndDate = !empty($_POST['observerEndDate']) ? $_POST['observerEndDate'] : null;
        $observerStatus = 'active';

        $externalTeacherID = !empty($_POST['external_teacher_id']) ? $_POST['external_teacher_id'] : null;
        $externalStartDate = !empty($_POST['externalStartDate']) ? $_POST['externalStartDate'] : null;
        $externalEndDate = !empty($_POST['externalEndDate']) ? $_POST['externalEndDate'] : null;
        $externalStatus = 'active';

        $observerAssignedDate = !empty($_POST['observerAssignedDate']) ? $_POST['observerAssignedDate'] : $observerStartDate;
        $observerAssignmentEndDate = !empty($_POST['observerAssignmentEndDate']) ? $_POST['observerAssignmentEndDate'] : $observerEndDate;
        $observerAssignmentStatus = 'active';

        $externalAssignedDate = !empty($_POST['externalAssignedDate']) ? $_POST['externalAssignedDate'] : $externalStartDate;
        $externalAssignmentEndDate = !empty($_POST['externalAssignmentEndDate']) ? $_POST['externalAssignmentEndDate'] : $externalEndDate;
        $externalAssignmentStatus = 'active';

        $hasObserverNow = !empty($observerTeacherID);
        $hasExternalNow = !empty($externalTeacherID);

        if (!$hasObserverNow && !$hasExternalNow) {
            throw new RuntimeException('Please select at least one observer or external observer.');
        }
        if ($hasObserverNow && empty($observerStartDate)) {
            throw new RuntimeException('Please enter observer start date.');
        }
        if ($hasObserverNow && empty($observerAssignedDate)) {
            throw new RuntimeException('Please enter Pre Observation assignment date.');
        }
        if ($hasExternalNow && empty($externalStartDate)) {
            throw new RuntimeException('Please enter external observer start date.');
        }
        if ($hasExternalNow && empty($externalAssignedDate)) {
            throw new RuntimeException('Please enter External Observation assignment date.');
        }

        if ($hasObserverNow && !empty($observerEndDate) && $observerEndDate <= $observerStartDate) {
            throw new RuntimeException('Observer end date must be after observer start date.');
        }
        if ($hasObserverNow && !empty($observerAssignmentEndDate) && $observerAssignmentEndDate <= $observerAssignedDate) {
            throw new RuntimeException('Observer assignment end date must be after assignment date.');
        }
        if ($hasExternalNow && !empty($externalEndDate) && $externalEndDate <= $externalStartDate) {
            throw new RuntimeException('External observer end date must be after external observer start date.');
        }
        if ($hasExternalNow && !empty($externalAssignmentEndDate) && $externalAssignmentEndDate <= $externalAssignedDate) {
            throw new RuntimeException('External assignment end date must be after assignment date.');
        }
        if ($hasObserverNow && $hasExternalNow && $observerTeacherID === $externalTeacherID) {
            throw new RuntimeException('Observer and external observer must be two different teachers.');
        }

        mysqli_begin_transaction($conn);
        // Serialize assignments per new teacher so attempts remain unique for each stage.
        $lockTeacher = mysqli_prepare($conn, 'SELECT gn_id FROM guru_new WHERE gn_id = ? FOR UPDATE');
        mysqli_stmt_bind_param($lockTeacher, 's', $gn_id);
        mysqli_stmt_execute($lockTeacher);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($lockTeacher))) {
            throw new RuntimeException('New teacher not found.');
        }
        $nextStageAttempt = static function (string $stage) use ($conn, $gn_id): int {
            $query = mysqli_prepare($conn, 'SELECT COALESCE(MAX(attemptNo), 0) + 1 AS nextAttempt FROM observer_assignment WHERE gn_id = ? AND assignmentType = ?');
            mysqli_stmt_bind_param($query, 'ss', $gn_id, $stage);
            mysqli_stmt_execute($query);
            return (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($query))['nextAttempt'] ?? 1);
        };

        if ($hasObserverNow) {
            $observerID = saveObserverRecord(
                $conn,
                $currentObserverID,
                $observerTeacherID,
                $observerStartDate,
                $observerEndDate,
                $observerStatus
            );

            $nullExternalObserverID = null;

            if (!empty($observerAssignmentID)) {
                $stmt = mysqli_prepare($conn, "
                    UPDATE observer_assignment
                    SET observerID = ?,
                        externalObserverID = ?,
                        assignedDate = ?,
                        endDate = ?,
                        status = ?,
                        assignmentType = 'Pre Observation'
                    WHERE assignmentID = ? AND gn_id = ?
                ");
                mysqli_stmt_bind_param($stmt, "sssssss", $observerID, $nullExternalObserverID, $observerAssignedDate, $observerAssignmentEndDate, $observerAssignmentStatus, $observerAssignmentID, $gn_id);
                mysqli_stmt_execute($stmt);
            } else {
                $newObserverAssignmentID = generateNextId($conn, 'observer_assignment', 'assignmentID', 'ASG', 4);

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO observer_assignment
                    (assignmentID, gn_id, observerID, externalObserverID, assignedDate, endDate, status, assignmentType, attemptNo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pre Observation', ?)
                ");
                $attempt = $nextStageAttempt('Pre Observation');
                mysqli_stmt_bind_param($stmt, "sssssssi", $newObserverAssignmentID, $gn_id, $observerID, $nullExternalObserverID, $observerAssignedDate, $observerAssignmentEndDate, $observerAssignmentStatus, $attempt);
                mysqli_stmt_execute($stmt);
            }
        }

        // If an existing role is cleared while editing, keep the historical
        // assignment row but mark it inactive. This makes Edit accurately reflect
        // the new selection and also fires the canonical UPDATE audit trigger.
        if (!$hasObserverNow && !empty($observerAssignmentID)) {
            $stmt = mysqli_prepare($conn, "
                UPDATE observer_assignment
                SET status = 'inactive',
                    endDate = COALESCE(endDate, CURDATE())
                WHERE assignmentID = ? AND gn_id = ?
            ");
            mysqli_stmt_bind_param($stmt, 'ss', $observerAssignmentID, $gn_id);
            mysqli_stmt_execute($stmt);
        }

        if ($hasExternalNow) {
            $externalObserverID = saveExternalObserverRecord(
                $conn,
                $currentExternalObserverID,
                $externalTeacherID,
                $externalStartDate,
                $externalEndDate,
                $externalStatus
            );

            $nullObserverID = null;

            if (!empty($externalAssignmentID)) {
                $stmt = mysqli_prepare($conn, "
                    UPDATE observer_assignment
                    SET observerID = ?,
                        externalObserverID = ?,
                        assignedDate = ?,
                        endDate = ?,
                        status = ?,
                        assignmentType = 'External Observation'
                    WHERE assignmentID = ? AND gn_id = ?
                ");
                mysqli_stmt_bind_param($stmt, "sssssss", $nullObserverID, $externalObserverID, $externalAssignedDate, $externalAssignmentEndDate, $externalAssignmentStatus, $externalAssignmentID, $gn_id);
                mysqli_stmt_execute($stmt);
            } else {
                $newExternalAssignmentID = generateNextId($conn, 'observer_assignment', 'assignmentID', 'ASG', 4);

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO observer_assignment
                    (assignmentID, gn_id, observerID, externalObserverID, assignedDate, endDate, status, assignmentType, attemptNo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'External Observation', ?)
                ");
                $attempt = $nextStageAttempt('External Observation');
                mysqli_stmt_bind_param($stmt, "sssssssi", $newExternalAssignmentID, $gn_id, $nullObserverID, $externalObserverID, $externalAssignedDate, $externalAssignmentEndDate, $externalAssignmentStatus, $attempt);
                mysqli_stmt_execute($stmt);
            }
        }

        if (!$hasExternalNow && !empty($externalAssignmentID)) {
            $stmt = mysqli_prepare($conn, "
                UPDATE observer_assignment
                SET status = 'inactive',
                    endDate = COALESCE(endDate, CURDATE())
                WHERE assignmentID = ? AND gn_id = ?
            ");
            mysqli_stmt_bind_param($stmt, 'ss', $externalAssignmentID, $gn_id);
            mysqli_stmt_execute($stmt);
        }

        mysqli_commit($conn);

        $params = ['tab' => $returnTab, 'assigned' => 1];
        if ($returnSchoolID !== '') {
            $params['schoolID'] = $returnSchoolID;
        }

        header('Location: teacher.php?' . http_build_query($params));
        exit();
    } catch (Throwable $e) {
        try { mysqli_rollback($conn); } catch (Throwable $ignored) {}
        $errorMessage = 'Assignment cannot be saved: ' . $e->getMessage();
    }
}

/* =========================
   DATA FOR TABS
========================= */

$activeTab = $_GET['tab'] ?? 'secondary';
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'secondary';
}

$selectedSchoolID = trim((string)($_GET['schoolID'] ?? ''));

$tabLabels = [
    'secondary' => 'Secondary School',
    'primary' => 'Primary School',
    'preschool' => 'Preschool',
    'others' => 'Others',
    'organisations' => 'Organisations',
    'new_teacher' => 'New Teacher'
];

$schoolsByCategory = [
    'secondary' => [],
    'primary' => [],
    'preschool' => [],
    'others' => []
];

$schoolsByID = [];
$teachersBySchool = [];
$newTeachers = [];
$newTeachersBySchool = [];
$eligibleTeacherList = [];
$organizations = [];

$totalSchools = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM school"))['total'] ?? 0);
$totalTeacherAll = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM teacher"))['total'] ?? 0);
$totalGuruNewAll = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM guru_new"))['total'] ?? 0);
$totalActiveAssignment = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM observer_assignment WHERE status='active'"))['total'] ?? 0);

$schoolResult = mysqli_query($conn, "
    SELECT
        s.schoolID,
        s.schoolName,
        s.schoolAddress,
        s.totalTeacher,
        s.phoneNumber,
        COALESCE(tc.teacher_total, 0) AS teacher_total,
        COALESCE(g.new_teacher_total, 0) AS new_teacher_total
    FROM school s
    LEFT JOIN (
        SELECT schoolID, COUNT(DISTINCT teacherID) AS teacher_total
        FROM assign
        WHERE status IN ('Aktif', 'active')
        GROUP BY schoolID
    ) tc ON tc.schoolID = s.schoolID
    LEFT JOIN (
        SELECT schoolID, COUNT(*) AS new_teacher_total
        FROM guru_new
        GROUP BY schoolID
    ) g ON g.schoolID = s.schoolID
    ORDER BY s.schoolName ASC
");

while ($school = mysqli_fetch_assoc($schoolResult)) {
    $category = classifySchool($school['schoolID'] ?? '', $school['schoolName'] ?? '');
    $school['category'] = $category;
    $schoolsByCategory[$category][] = $school;
    $schoolsByID[$school['schoolID']] = $school;
}

$selectedSchool = null;
$selectedSchoolCategory = '';
if ($selectedSchoolID !== '' && isset($schoolsByID[$selectedSchoolID])) {
    $selectedSchool = $schoolsByID[$selectedSchoolID];
    $selectedSchoolCategory = $selectedSchool['category'];
    if ($activeTab !== $selectedSchoolCategory && $activeTab !== 'new_teacher') {
        $activeTab = $selectedSchoolCategory;
    }
}

/* Only load teacher/new-teacher detail rows when the user opens a school or
   the New Teacher tab. The initial school list uses the stored school counts. */
if ($selectedSchoolID !== '') {
    $safeSelectedSchoolID = mysqli_real_escape_string($conn, $selectedSchoolID);
    $teacherResult = mysqli_query($conn, "
        SELECT
            t.teacherID,
            t.teacherName,
            t.ICNumber,
            t.phoneNumber,
            t.email,
            t.appointedDate,
            t.serviceDate,
            tcs.schoolID,
            t.resignation_request_status,
            s.schoolName
        FROM teacher t
        INNER JOIN v_teacher_current_school tcs ON t.teacherID = tcs.teacherID
        LEFT JOIN school s ON tcs.schoolID = s.schoolID
        WHERE tcs.schoolID = '$safeSelectedSchoolID'
        ORDER BY t.teacherName ASC
    ");

    while ($teacher = mysqli_fetch_assoc($teacherResult)) {
        $teachersBySchool[$selectedSchoolID][] = $teacher;
    }
}

if ($activeTab === 'organisations') {
    $organizationResult = mysqli_query($conn, "
        SELECT OrganizationID, OrganizationName, OrganizationAddress, RegisterDate, PhoneNumber
        FROM organizations
        ORDER BY OrganizationName ASC
    ");
    while ($organization = $organizationResult ? mysqli_fetch_assoc($organizationResult) : null) {
        $organizations[] = $organization;
    }
}

$needsNewTeacherDetails = ($selectedSchool !== null);
if ($needsNewTeacherDetails) {
    $eligibleResult = mysqli_query($conn, "
        SELECT
            t.teacherID,
            t.teacherName,
            tcs.schoolID,
            s.schoolName,
            t.appointedDate,
            TIMESTAMPDIFF(YEAR, t.appointedDate, CURDATE()) AS serviceYears
        FROM teacher t
        LEFT JOIN v_teacher_current_school tcs ON t.teacherID = tcs.teacherID
        LEFT JOIN school s ON tcs.schoolID = s.schoolID
        WHERE t.appointedDate IS NOT NULL
          AND t.appointedDate < DATE_SUB(CURDATE(), INTERVAL 10 YEAR)
        ORDER BY t.teacherName ASC
    ");

    while ($row = mysqli_fetch_assoc($eligibleResult)) {
        $eligibleTeacherList[] = $row;
    }

    $newTeacherWhere = '';
    if ($selectedSchoolID !== '') {
        $safeSelectedSchoolID = mysqli_real_escape_string($conn, $selectedSchoolID);
        $newTeacherWhere = "WHERE g.schoolID = '$safeSelectedSchoolID'";
    }

    $newTeacherResult = mysqli_query($conn, "
        SELECT
            g.gn_id,
            g.gn_name,
            g.email,
            g.phone_number,
            g.ic_number,
            g.gender,
            g.appointed_date,
            g.current_status,
            g.schoolID,
            s.schoolName,

            oaObs.assignmentID AS observerAssignmentID,
            oaObs.assignedDate AS observerAssignedDate,
            oaObs.endDate AS observerAssignmentEndDate,
            oaObs.status AS observerAssignmentStatus,

            o.observerID,
            o.startDate AS observerStartDate,
            o.endDate AS observerEndDate,
            o.status AS observerStatus,
            obsT.teacherID AS observerTeacherID,
            obsT.teacherName AS observerName,
            obsSchool.schoolID AS observerSchoolID,

            oaExt.assignmentID AS externalAssignmentID,
            oaExt.assignedDate AS externalAssignedDate,
            oaExt.endDate AS externalAssignmentEndDate,
            oaExt.status AS externalAssignmentStatus,

            eo.externalObserverID,
            eo.startDate AS externalStartDate,
            eo.endDate AS externalEndDate,
            eo.status AS externalStatus,
            extT.teacherID AS externalTeacherID,
            extT.teacherName AS externalObserverName,
            extSchool.schoolID AS externalObserverSchoolID
        FROM guru_new g
        LEFT JOIN school s ON g.schoolID = s.schoolID

        LEFT JOIN observer_assignment oaObs
            ON oaObs.assignmentID = (
                SELECT oa1.assignmentID
                FROM observer_assignment oa1
                WHERE oa1.gn_id = g.gn_id
                  AND oa1.observerID IS NOT NULL
                  AND oa1.status = 'active'
                ORDER BY oa1.assignedDate DESC, oa1.assignmentID DESC
                LIMIT 1
            )
        LEFT JOIN observer o ON oaObs.observerID = o.observerID
        LEFT JOIN teacher obsT ON o.teacherID = obsT.teacherID
        LEFT JOIN v_teacher_current_school obsSchool ON obsT.teacherID = obsSchool.teacherID

        LEFT JOIN observer_assignment oaExt
            ON oaExt.assignmentID = (
                SELECT oa2.assignmentID
                FROM observer_assignment oa2
                WHERE oa2.gn_id = g.gn_id
                  AND oa2.externalObserverID IS NOT NULL
                  AND oa2.status = 'active'
                ORDER BY oa2.assignedDate DESC, oa2.assignmentID DESC
                LIMIT 1
            )
        LEFT JOIN external_observer eo ON oaExt.externalObserverID = eo.externalObserverID
        LEFT JOIN teacher extT ON eo.teacherID = extT.teacherID
        LEFT JOIN v_teacher_current_school extSchool ON extT.teacherID = extSchool.teacherID

        $newTeacherWhere
        ORDER BY COALESCE(s.schoolName, ''), g.gn_name ASC
    ");

    while ($row = mysqli_fetch_assoc($newTeacherResult)) {
        // Syiqin is authoritative for submitted assessment results.
        if ($activeTab === 'new_teacher') {
            $row['postObservation'] = PostObservationWorkflow::state($conn, (string)$row['gn_id']);
        }
        $newTeachers[] = $row;
        $newTeachersBySchool[(string)($row['schoolID'] ?? '')][] = $row;
    }
}

function tabCount(array $schoolsByCategory, string $category): int {
    return count($schoolsByCategory[$category] ?? []);
}

function teacherStatusClass(array $teacher): string {
    $resignationStatus = strtolower((string)($teacher['resignation_request_status'] ?? ''));
    return in_array($resignationStatus, ['approved', 'resigned', 'inactive'], true) ? 'inactive' : 'active';
}

function renderTeacherRows(array $teachers): void {
    if (empty($teachers)) {
        echo '<div class="empty-state">No teacher found in this school.</div>';
        return;
    }

    foreach ($teachers as $t) {
        $teacherServiceYears = serviceYears($t['appointedDate'] ?? null);
        $teacherStatus = teacherStatusClass($t);
        ?>
        <div class="teacher-row compact-teacher-row">
            <div class="teacher-avatar"><?php echo e(strtoupper(substr((string)$t['teacherName'], 0, 1))); ?></div>
            <div>
                <strong><?php echo e($t['teacherName']); ?></strong>
                <span><?php echo e($t['email'] ?: 'No email'); ?></span>
                <small>
                    Appointed: <?php echo e($t['appointedDate'] ?: '-'); ?>
                    <?php if ($teacherServiceYears !== null) { ?> • <?php echo e($teacherServiceYears); ?> years service<?php } ?>
                </small>
            </div>
            <em class="teacher-status-pill <?php echo $teacherStatus === 'active' ? 'active' : 'inactive'; ?>"><?php echo e(ucfirst($teacherStatus)); ?></em>
        </div>
        <?php
    }
}

function renderSchoolNewTeacherRows(array $newTeachers): void {
    if (empty($newTeachers)) {
        echo '<div class="empty-state">No new teacher found in this school.</div>';
        return;
    }

    foreach ($newTeachers as $g) {
        $status = strtolower((string)($g['current_status'] ?? 'Inactive'));
        $statusClass = $status === 'active' ? 'active' : ($status === 'complete' ? 'complete' : 'inactive');
        ?>
        <div class="teacher-row compact-teacher-row school-new-teacher-row">
            <div class="teacher-avatar"><?php echo e(strtoupper(substr((string)$g['gn_name'], 0, 1))); ?></div>
            <div>
                <strong><?php echo e($g['gn_name']); ?></strong>
                <span><?php echo e($g['email'] ?: 'No email'); ?></span>
                <small>
                    Phone: <?php echo e($g['phone_number'] ?: '-'); ?>
                    <?php if (!empty($g['hire_date'])) { ?> • Appointed: <?php echo e($g['hire_date']); ?><?php } ?>
                </small>
            </div>
            <em class="teacher-status-pill <?php echo e($statusClass); ?>"><?php echo e(ucfirst($status ?: 'Inactive')); ?></em>
        </div>
        <?php
    }
}

function renderNewTeacherCard(array $g, array $eligibleTeacherList, string $contextPrefix, string $returnTab, string $returnSchoolID = ''): void {
    $hasAnyAssignment = !empty($g['observerAssignmentID']) || !empty($g['externalAssignmentID']) || !empty($g['observerName']) || !empty($g['externalObserverName']);
    $newTeacherStatus = strtolower(trim((string)($g['current_status'] ?? 'inactive')));
    $isActiveNewTeacher = in_array($newTeacherStatus, ['active', 'aktif'], true);
    $safeId = preg_replace('/[^A-Za-z0-9]/', '', (string)$g['gn_id']);
    $formId = 'assignForm' . $contextPrefix . $safeId;
    $newTeacherSearchText = implode(' ', [
        $g['gn_name'] ?? '',
        $g['email'] ?? '',
        $g['phone_number'] ?? '',
        $g['schoolName'] ?? '',
        $g['observerName'] ?? '',
        $g['externalObserverName'] ?? ''
    ]);
    ?>
    <div
        class="new-teacher-card filterable-new-teacher-card"
        data-has-assignment="<?php echo $hasAnyAssignment ? '1' : '0'; ?>"
        data-assignment="<?php echo $hasAnyAssignment ? 'assigned' : 'pending'; ?>"
        data-has-observer="<?php echo !empty($g['observerName']) ? '1' : '0'; ?>"
        data-has-external="<?php echo !empty($g['externalObserverName']) ? '1' : '0'; ?>"
        data-new-teacher-status="<?php echo $isActiveNewTeacher ? 'active' : 'inactive'; ?>"
        data-search="<?php echo e($newTeacherSearchText . ' ' . ($isActiveNewTeacher ? 'active' : 'inactive deactivated')); ?>"
    >
        <div class="new-teacher-head list-mode-head">
            <div class="teacher-avatar"><?php echo e(strtoupper(substr((string)$g['gn_name'], 0, 1))); ?></div>

            <div>
                <strong><?php echo e($g['gn_name']); ?></strong>
                <span><?php echo e($g['schoolName'] ?: 'School not assigned'); ?></span>
                <small>
                    Pre Observation: <?php echo !empty($g['observerName']) ? e($g['observerName']) : 'Not assigned'; ?>
                    • External Observation: <?php echo !empty($g['externalObserverName']) ? e($g['externalObserverName']) : 'Not assigned'; ?>

                </small>
            </div>

            <div class="new-teacher-actions">
                <?php if (!$isActiveNewTeacher) { ?>
                    <em class="new-teacher-inactive-badge">Inactive / Deactivated</em>
                <?php } elseif ($hasAnyAssignment) { ?>
                    <em class="original-badge">Assigned</em>
                <?php } else { ?>
                    <em class="replace-badge">Pending</em>
                <?php } ?>

                <?php if ($isActiveNewTeacher) { ?>
                    <button type="button" class="assign-toggle-btn" data-mode="<?php echo $hasAnyAssignment ? 'edit' : 'assign'; ?>" data-target="<?php echo e($formId); ?>">
                        <?php echo $hasAnyAssignment ? 'Edit' : 'Assign'; ?>
                    </button>
                <?php } else { ?>
                    <button type="button" class="assign-toggle-btn disabled" disabled title="Inactive new teachers cannot be assigned an observer.">Not Active</button>
                <?php } ?>
            </div>
        </div>

        <?php
        $post = $g['postObservation'] ?? null;
        if ($post && !empty($post['preResult'])) { ?>
            <div class="pre-observation-summary">Pre Observation Result:
            <strong><?php echo e($post['preResult']['achievement_level'] ?: 'Submitted'); ?></strong>
            <?php if ($post['preResult']['percentage'] !== null) { ?>
                <span>(<?php echo e($post['preResult']['percentage']); ?>%)</span>
            <?php } ?>
            </div>
        <?php }

        $postHistory = $post['history'] ?? [];
        // Keep the optional Post section visible for every new teacher, even
        // if the external result is PASS or there is no assignment yet.
        if ($post) { ?>
        <section class="post-observation-card" aria-label="Optional Post Observation">
            <div class="post-observation-title">
                <strong>Post Observation <span class="post-optional-tag">Optional</span></strong>
                <?php if ($post['externalResult'] === 'REPEAT') { ?>
                    <em class="post-result repeat">Latest External Observation: REPEAT</em>
                <?php } elseif ($post['externalResult'] === 'PASS') { ?>
                    <em class="post-result pass">Latest External Observation: PASS</em>
                <?php } else { ?>
                    <em class="post-result waiting">External Observation result pending</em>
                <?php } ?>
            </div>
            <?php if (!empty($post['externalHistory'])) { ?>
                <div class="post-external-history">
                    <strong>External Observation attempts</strong>
                    <?php foreach ($post['externalHistory'] as $externalRow) { ?>
                        <span>Attempt <?php echo (int)$externalRow['attempt']; ?>:
                            <?php echo e($externalRow['result'] ?: 'Awaiting result'); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if ($post['externalResult'] === 'REPEAT') { ?>
                <p class="post-optional-note">External Observation needs another attempt. You may repeat External Observation in the Teacher Employment System <strong>or</strong> optionally assign a Post Observation teacher here.</p>
            <?php } elseif ($post['externalResult'] === 'PASS') { ?>
                <p class="post-optional-note">External Observation has passed. A new Post Observation assignment is not required. Previous Post Observation attempts remain available below.</p>
            <?php } else { ?>
                <p class="post-optional-note">Post Observation is optional and becomes available after a submitted External Observation <strong>REPEAT</strong>. No assignment is required at this stage.</p>
            <?php } ?>
            <?php if (!$postHistory) { ?>
                <p class="post-empty-history">No Post Observation attempts assigned.</p>
            <?php } ?>
            <?php foreach ($postHistory as $attemptRow) { ?>
                <div class="post-attempt-row"><span>Attempt <?php echo (int)$attemptRow['attempt']; ?> · <?php echo e($attemptRow['teacher']); ?>
                    <small><?php echo e($attemptRow['date'] ?: 'No date'); ?></small></span>
                    <em class="post-result <?php echo ($attemptRow['result'] ?? '') === 'PASS' ? 'pass' : (($attemptRow['result'] ?? '') === 'REPEAT' ? 'repeat' : 'waiting'); ?>">
                        <?php echo e($attemptRow['result'] ?: 'Awaiting result'); ?>
                    </em>
                </div>
            <?php } ?>
            <?php if ($post['nextAttempt'] && $post['ready'] && $isActiveNewTeacher) { ?>
                <button type="button" class="assign-toggle-btn post-assign-button" data-target="postForm<?php echo e($safeId); ?>">Assign Post Observation Attempt <?php echo (int)$post['nextAttempt']; ?> (Optional)</button>
            <?php } elseif (!$post['ready'] && $post['externalResult'] === 'REPEAT') { ?>
                <p class="post-module-notice">Post Observation setup required. Check that the existing observer_assignment table has the five Post columns in DBeaver; do not rerun the patch if already applied.</p>
            <?php } elseif ($postHistory && ($postHistory[count($postHistory)-1]['result'] ?? null) === null) { ?>
                <p class="post-module-notice">A Post Observation attempt is awaiting its assessment result. The next attempt becomes available only after a submitted REPEAT.</p>
            <?php } ?>
        </section>
        <?php } ?>

        <?php if ($post && $post['nextAttempt'] && $post['ready'] && $isActiveNewTeacher) { ?>
        <form method="POST" class="assignment-form assignment-modal post-assignment-form" id="postForm<?php echo e($safeId); ?>" aria-hidden="true">
          <div class="assignment-modal-shell post-modal-shell">
            <div class="assignment-modal-header"><div><h3>Post Observation · Attempt <?php echo (int)$post['nextAttempt']; ?></h3>
            <p><?php echo e($g['gn_name']); ?> · Post Observation is optional after External Observation REPEAT. Choose the same External Observer or another eligible teacher.</p></div>
            <button type="button" class="assignment-modal-close" data-close-target="postForm<?php echo e($safeId); ?>" aria-label="Close">×</button></div>
            <div class="post-form-body">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="gn_id" value="<?php echo e($g['gn_id']); ?>">
                <input type="hidden" name="return_school_id" value="<?php echo e($g['schoolID']); ?>">
                <?php if (!empty($g['externalTeacherID'])) { ?>
                <label class="post-reuse-option"><input type="checkbox" class="post-reuse-external" data-teacher="<?php echo e($g['externalTeacherID']); ?>">
                    Use same External Observer: <strong><?php echo e($g['externalObserverName']); ?></strong></label>
                <?php } ?>
                <label for="postTeacher<?php echo e($safeId); ?>" >Post Observation Teacher (more than 10 years' service)</label>
                <input type="search" class="post-observer-search" placeholder="Search teacher name or school..." autocomplete="off">
                <select id="postTeacher<?php echo e($safeId); ?>" class="post-teacher-select" name="post_teacher_id" required>
                    <option value="">Choose an eligible teacher</option>
                    <?php foreach ($eligibleTeacherList as $eligible) { ?>
                        <option value="<?php echo e($eligible['teacherID']); ?>" data-search="<?php echo e(strtolower($eligible['teacherName'].' '.$eligible['schoolName'].' '.$eligible['schoolID'])); ?>">
                            <?php echo e($eligible['teacherName'].' · '.($eligible['schoolName'] ?: $eligible['schoolID']).' · '.$eligible['serviceYears'].' years'); ?>
                        </option>
                    <?php } ?>
                </select>
                <label for="postDate<?php echo e($safeId); ?>">Observation date</label>
                <input id="postDate<?php echo e($safeId); ?>" type="date" name="post_observation_date" required min="<?php echo date('Y-m-d'); ?>">
                <p class="post-module-notice">Assessment result will be retrieved from the Teacher Employment system after submission.</p>
            </div>
            <input type="hidden" name="assign_post_observer" value="1">
            <div class="assignment-modal-actions"><button type="button" class="modal-cancel-btn" data-close-target="postForm<?php echo e($safeId); ?>">Cancel</button>
            <button type="submit" class="primary-btn save-assignment-btn">Save Post Observation Assignment</button></div>
          </div>
        </form>
        <?php } ?>

        <?php if ($isActiveNewTeacher) { ?>
        <form method="POST" class="assignment-form assignment-modal observer-assignment-form" id="<?php echo e($formId); ?>" aria-hidden="true">
            <div class="assignment-modal-shell">
                <div class="assignment-modal-header">
                    <div>
                        <h3><?php echo e($g['gn_name']); ?></h3>
                        <p>Choose Pre Observation, External Observation, or both. Eligible teachers may be from the same or a different school and must have more than 10 years of service.</p>
                    </div>
                    <button type="button" class="assignment-modal-close" data-close-target="<?php echo e($formId); ?>" aria-label="Close assignment form">×</button>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="observerAssignmentID" value="<?php echo e($g['observerAssignmentID'] ?? ''); ?>">
                <input type="hidden" name="externalAssignmentID" value="<?php echo e($g['externalAssignmentID'] ?? ''); ?>">
                <input type="hidden" name="gn_id" value="<?php echo e($g['gn_id']); ?>">
                <input type="hidden" name="schoolID" value="<?php echo e($g['schoolID']); ?>">
                <input type="hidden" name="return_tab" value="<?php echo e($returnTab); ?>">
                <input type="hidden" name="return_school_id" value="<?php echo e($returnSchoolID); ?>">
                <input type="hidden" name="currentObserverID" value="<?php echo e($g['observerID'] ?? ''); ?>">
                <input type="hidden" name="currentExternalObserverID" value="<?php echo e($g['externalObserverID'] ?? ''); ?>">

                <div class="assignment-role-grid">
                    <div class="role-card observer-role-card">
                        <div class="role-card-header">
                            <div>
                                <span class="role-kicker">Internal Role</span>
                                <h4>Pre Observation</h4>
                            </div>
                            <em>Optional</em>
                        </div>

                        <div class="table-field-box observer-table-box">
                            <div class="field-box-title"><strong>Pre Observation Details</strong></div>

                            <div class="form-grid-small">
                                <div>
                                    <label>Pre Observation Teacher</label>
                                    <input type="search" class="observer-option-search" placeholder="Search observer name or school..." autocomplete="off">
                                    <select name="observer_teacher_id" class="observer-select">
                                        <option value="">Select observer / optional</option>
                                        <?php foreach ($eligibleTeacherList as $o) { ?>
                                            <option value="<?php echo e($o['teacherID']); ?>" data-teacher-id="<?php echo e($o['teacherID']); ?>" data-search="<?php echo e(strtolower(($o['teacherName'] ?? '') . ' ' . ($o['schoolName'] ?? '') . ' ' . ($o['schoolID'] ?? ''))); ?>" <?php echo (($g['observerTeacherID'] ?? '') == $o['teacherID']) ? 'selected' : ''; ?>>
                                                <?php echo e($o['teacherName']); ?> - <?php echo e($o['schoolName'] ?: $o['schoolID']); ?> (<?php echo e($o['serviceYears']); ?> yrs)
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <label>Pre Observation Start Date</label>
                                    <input type="date" name="observerStartDate" class="observer-start-date" value="<?php echo e($g['observerStartDate'] ?? ''); ?>">
                                </div>

                                <div>
                                    <label>Pre Observation End Date</label>
                                    <input type="date" name="observerEndDate" value="<?php echo e($g['observerEndDate'] ?? ''); ?>">
                                </div>

                                <input type="hidden" name="observerStatus" value="<?php echo e($g['observerStatus'] ?? 'active'); ?>">
                            </div>
                        </div>

                        <div class="table-field-box assignment-table-box">
                            <div class="field-box-title"><strong>Pre Observation Assignment Details</strong></div>
                            <div class="form-grid-small">
                                <div>
                                    <label>Assignment Date</label>
                                    <input type="date" name="observerAssignedDate" value="<?php echo e($g['observerAssignedDate'] ?? $g['observerStartDate'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div>
                                    <label>Assignment End Date</label>
                                    <input type="date" name="observerAssignmentEndDate" value="<?php echo e($g['observerAssignmentEndDate'] ?? $g['observerEndDate'] ?? ''); ?>">
                                </div>
                                <input type="hidden" name="observerAssignmentStatus" value="<?php echo e($g['observerAssignmentStatus'] ?? 'active'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="role-card external-role-card">
                        <div class="role-card-header">
                            <div>
                                <span class="role-kicker">External Role</span>
                                <h4>External Observation</h4>
                            </div>
                            <em>Optional</em>
                        </div>

                        <div class="table-field-box external-table-box">
                            <div class="field-box-title"><strong>External Observation Details</strong></div>

                            <div class="form-grid-small">
                                <div>
                                    <label>External Observation Teacher</label>
                                    <input type="search" class="external-option-search" placeholder="Search external observer name or school..." autocomplete="off">
                                    <select name="external_teacher_id" class="external-select">
                                        <option value="">Select external observer / optional</option>
                                        <?php foreach ($eligibleTeacherList as $eo) { ?>
                                            <option value="<?php echo e($eo['teacherID']); ?>" data-teacher-id="<?php echo e($eo['teacherID']); ?>" data-search="<?php echo e(strtolower(($eo['teacherName'] ?? '') . ' ' . ($eo['schoolName'] ?? '') . ' ' . ($eo['schoolID'] ?? ''))); ?>" <?php echo (($g['externalTeacherID'] ?? '') == $eo['teacherID']) ? 'selected' : ''; ?>>
                                                <?php echo e($eo['teacherName']); ?> - <?php echo e($eo['schoolName'] ?: $eo['schoolID']); ?> (<?php echo e($eo['serviceYears']); ?> yrs)
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <label>External Observation Start Date</label>
                                    <input type="date" name="externalStartDate" class="external-start-date" value="<?php echo e($g['externalStartDate'] ?? ''); ?>">
                                </div>

                                <div>
                                    <label>External Observation End Date</label>
                                    <input type="date" name="externalEndDate" value="<?php echo e($g['externalEndDate'] ?? ''); ?>">
                                </div>

                                <input type="hidden" name="externalStatus" value="<?php echo e($g['externalStatus'] ?? 'active'); ?>">
                            </div>
                        </div>

                        <div class="table-field-box assignment-table-box">
                            <div class="field-box-title"><strong>External Assignment Details</strong></div>
                            <div class="form-grid-small">
                                <div>
                                    <label>Assignment Date</label>
                                    <input type="date" name="externalAssignedDate" value="<?php echo e($g['externalAssignedDate'] ?? $g['externalStartDate'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div>
                                    <label>Assignment End Date</label>
                                    <input type="date" name="externalAssignmentEndDate" value="<?php echo e($g['externalAssignmentEndDate'] ?? $g['externalEndDate'] ?? ''); ?>">
                                </div>
                                <input type="hidden" name="externalAssignmentStatus" value="<?php echo e($g['externalAssignmentStatus'] ?? 'active'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="assign_observer" value="1">
                <div class="assignment-modal-actions">
                    <button type="button" class="modal-cancel-btn" data-close-target="<?php echo e($formId); ?>">Cancel</button>
                    <button type="submit" class="primary-btn save-assignment-btn">Save</button>
                </div>
            </div>
        </form>
        <?php } ?>
    </div>
    <?php
}

function renderNewTeacherList(array $newTeachers, array $eligibleTeacherList, string $contextPrefix, string $returnTab, string $returnSchoolID = ''): void {
    if (empty($newTeachers)) {
        echo '<div class="empty-state">No new teacher found.</div>';
        return;
    }

    foreach ($newTeachers as $g) {
        renderNewTeacherCard($g, $eligibleTeacherList, $contextPrefix, $returnTab, $returnSchoolID);
    }
}
