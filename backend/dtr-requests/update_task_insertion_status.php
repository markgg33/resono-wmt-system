<?php

//WORKING VERSION (WILL BE IMPLEMENTING ARCHIVE-AWARE APPROACH)
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
$allowedRoles = ['admin', 'hr', 'executive', 'supervisor'];
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$processed_by = (int)$_SESSION['user_id'];
$request_uid  = $_POST['request_uid'] ?? '';
$newStatus    = $_POST['status'] ?? '';

if (!$request_uid || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing request parameters.']);
    exit;
}

// ===============================
// 2️⃣ UTILITIES
// ===============================
function parse_time($t)
{
    if (!$t || $t === '--') return null;
    $p = explode(':', $t);
    return ($p[0] * 3600) + ($p[1] * 60) + ($p[2] ?? 0);
}
function to_hms($s)
{
    $h = floor($s / 3600);
    $m = floor(($s % 3600) / 60);
    $sec = $s % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $sec);
}
function duration($start, $end)
{
    $s = parse_time($start);
    $e = parse_time($end);
    if ($s === null || $e === null) return null;
    $diff = $e - $s;
    if ($diff < 0) $diff += 86400; // overnight fix
    return to_hms($diff);
}

// ===============================
// 3️⃣ FETCH REQUEST
// ===============================
$stmt = $conn->prepare("SELECT * FROM task_insertion_requests WHERE request_uid = ? LIMIT 1");
$stmt->bind_param("s", $request_uid);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

$user_id = $request['user_id'];
$date = $request['date'];
$start = $request['start_time'];
$end = $request['end_time'];
$reason = $request['reason'];
$work_mode = $request['work_mode_id'];
$task_desc = $request['task_description_id'];

// ===============================
// 4️⃣ HANDLE REJECT
// ===============================
if (strtolower($newStatus) === 'rejected') {
    $upd = $conn->prepare("UPDATE task_insertion_requests 
                           SET status='Rejected', processed_by=?, processed_at=NOW() 
                           WHERE request_uid=?");
    $upd->bind_param("is", $processed_by, $request_uid);
    $upd->execute();
    echo json_encode(['success' => true, 'message' => 'Request rejected successfully.']);
    exit;
}

// ===============================
// 5️⃣ HANDLE APPROVAL
// ===============================
if (strtolower($newStatus) !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit;
}

$startSec = parse_time($start);
$endSec = parse_time($end);

// ✅ PATCH 1: Allow equal start/end times but reject reversed intervals
if ($startSec === null || $endSec === null) {
    echo json_encode(['success' => false, 'message' => 'Missing start or end time.']);
    exit;
}
if ($endSec < $startSec) {
    echo json_encode(['success' => false, 'message' => 'End time cannot be before start time.']);
    exit;
}

$conn->begin_transaction();

try {
    // FETCH ALL EXISTING LOGS
    $logs_q = $conn->prepare("SELECT * FROM task_logs WHERE user_id=? AND date=? ORDER BY start_time ASC");
    $logs_q->bind_param("is", $user_id, $date);
    $logs_q->execute();
    $logs = $logs_q->get_result()->fetch_all(MYSQLI_ASSOC);
    $logs_q->close();

    if (count($logs) === 0) {
        throw new Exception('No existing logs found to fit the requested task.');
    }

    $inserted = false;
    $allLogs = [];

    foreach ($logs as $l) {
        $lStartSec = parse_time($l['start_time']);
        $lEndSec = parse_time($l['end_time']);

        // ✅ PATCH 2: Allow equal edges (e.g., 8:30–9:30 between 7:00–10:00 or 8:30–9:30 after 8:30 end)
        if ($startSec >= $lStartSec && $endSec <= $lEndSec && $startSec < $endSec) {

            // Split into before → inserted → after
            if ($startSec > $lStartSec) {
                $allLogs[] = [
                    'start_time' => $l['start_time'],
                    'end_time' => $start,
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $allLogs[] = [
                'start_time' => $start,
                'end_time' => $end,
                'work_mode_id' => $work_mode,
                'task_description_id' => $task_desc,
                'remarks' => $reason
            ];

            if ($endSec < $lEndSec) {
                $allLogs[] = [
                    'start_time' => $end,
                    'end_time' => $l['end_time'],
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $inserted = true;
        } else {
            $allLogs[] = $l;
        }
    }

    if (!$inserted) {
        throw new Exception('Requested task does not fit inside any existing time window.');
    }

    // ✅ PATCH 3: Use prepared delete
    $del = $conn->prepare("DELETE FROM task_logs WHERE user_id=? AND date=?");
    $del->bind_param("is", $user_id, $date);
    $del->execute();
    $del->close();

    // Reinsert all logs
    $new_id = null;
    foreach ($allLogs as $l) {
        $dur = duration($l['start_time'], $l['end_time']);
        $ins = $conn->prepare("INSERT INTO task_logs 
            (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iiisssss", $user_id, $l['work_mode_id'], $l['task_description_id'], $date, $l['start_time'], $l['end_time'], $dur, $l['remarks']);
        $ins->execute();
        $new_id = $ins->insert_id;
        $ins->close();
    }

    // Update status
    $upd = $conn->prepare("UPDATE task_insertion_requests 
                           SET status='Approved', processed_by=?, processed_at=NOW(), affected_log_id=? 
                           WHERE request_uid=?");
    $upd->bind_param("iis", $processed_by, $new_id, $request_uid);
    $upd->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Request approved and task inserted successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
*/

//PREVIOUS WORKING VERSION COMMENTED OUT
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$processed_by = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$request_uid = $_POST['request_uid'] ?? null;
$newStatus = $_POST['status'] ?? null;

if (!$request_uid || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// ===============================
// 3️⃣ FETCH REQUEST DETAILS
// ===============================
$query = $conn->prepare("
    SELECT *
    FROM task_insertion_requests
    WHERE request_uid = ?
    LIMIT 1
");
$query->bind_param("s", $request_uid);
$query->execute();
$result = $query->get_result();
$request = $result->fetch_assoc();
$query->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

// Shortcuts
$user_id = $request['user_id'];
$date = $request['date'];
$work_mode_id = $request['work_mode_id'];
$task_description_id = $request['task_description_id'];
$start_time = $request['start_time'];
$end_time = $request['end_time'];
$reason = $request['reason'];

// ===============================
// 4️⃣ APPROVAL / REJECTION LOGIC
// ===============================
if ($newStatus === 'Approved') {

    // Get description text
    $descStmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
    $descStmt->bind_param("i", $task_description_id);
    $descStmt->execute();
    $descRow = $descStmt->get_result()->fetch_assoc();
    $descStmt->close();
    $task_text = strtolower($descRow['description'] ?? '');

    // ========================================
    // 🧠 CASE A: "End Shift" task
    // ========================================
    if (strpos($task_text, 'end shift') !== false) {
        $end_time = null; // Open-ended (no end_time)

        // 🔍 Close previous open log if exists
        $prevStmt = $conn->prepare("
            SELECT id, start_time
            FROM task_logs
            WHERE user_id = ? AND date = ? AND end_time IS NULL
            ORDER BY start_time DESC
            LIMIT 1
        ");
        $prevStmt->bind_param("is", $user_id, $date);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result()->fetch_assoc();
        $prevStmt->close();

        if ($prevResult) {
            $prev_id = $prevResult['id'];
            $prev_start = $prevResult['start_time'];

            // Compute duration
            $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
            $diffStmt->bind_param("ss", $start_time, $prev_start);
            $diffStmt->execute();
            $diffRow = $diffStmt->get_result()->fetch_assoc();
            $diffStmt->close();
            $duration = $diffRow['duration'] ?? null;

            // Close previous task
            $updatePrev = $conn->prepare("
                UPDATE task_logs
                SET end_time = ?, total_duration = ?
                WHERE id = ?
            ");
            $updatePrev->bind_param("ssi", $start_time, $duration, $prev_id);
            $updatePrev->execute();
            $updatePrev->close();
        }
    }

    // ========================================
    // 🧠 CASE B: Regular tasks
    // ========================================
    else {
        if (empty($end_time) || $end_time === '0000-00-00 00:00:00') {
            // find next task (either from logs or insertion requests already approved)
            $nextStmt = $conn->prepare("
                SELECT start_time 
                FROM task_logs
                WHERE user_id = ? AND date = ? AND start_time > ?
                ORDER BY start_time ASC
                LIMIT 1
            ");
            $nextStmt->bind_param("iss", $user_id, $date, $start_time);
            $nextStmt->execute();
            $nextResult = $nextStmt->get_result()->fetch_assoc();
            $nextStmt->close();

            if ($nextResult) {
                $end_time = $nextResult['start_time'];
            } else {
                // No next task found → open-ended
                $end_time = null;
            }
        }
    }

    // ========================================
    // ⏱ Compute duration if we have end_time
    // ========================================
    $total_duration = null;
    if (!empty($end_time)) {
        $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
        $diffStmt->bind_param("ss", $end_time, $start_time);
        $diffStmt->execute();
        $diffResult = $diffStmt->get_result()->fetch_assoc();
        $diffStmt->close();
        $total_duration = $diffResult['duration'] ?? null;
    }

    // ========================================
    // 🧱 INSERT INTO task_logs
    // ========================================
    $remarks = "Inserted via approved request ($request_uid)";
    $insertStmt = $conn->prepare("
        INSERT INTO task_logs (
            user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->bind_param(
        "iiisssss",
        $user_id,
        $work_mode_id,
        $task_description_id,
        $date,
        $start_time,
        $end_time,
        $total_duration,
        $remarks
    );

    if ($insertStmt->execute()) {
        // Update request as approved
        $updateStmt = $conn->prepare("
            UPDATE task_insertion_requests
            SET status = 'Approved', processed_by = ?, processed_by_role = ?, processed_at = NOW()
            WHERE request_uid = ?
        ");
        $updateStmt->bind_param("iss", $processed_by, $role, $request_uid);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode(['success' => true, 'message' => 'Task insertion approved and added to logs.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert task log.']);
    }

    $insertStmt->close();

} elseif ($newStatus === 'Rejected') {
    // REJECT CASE
    $updateStmt = $conn->prepare("
        UPDATE task_insertion_requests
        SET status = 'Rejected', processed_by = ?, processed_by_role = ?, processed_at = NOW()
        WHERE request_uid = ?
    ");
    $updateStmt->bind_param("iss", $processed_by, $role, $request_uid);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
}

$conn->close();
?>*/

//FOR TESTING (WORKING)
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$processed_by = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$request_uid = $_POST['request_uid'] ?? null;
$newStatus = $_POST['status'] ?? null;

if (!$request_uid || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// ===============================
// 3️⃣ FETCH REQUEST DETAILS
// ===============================
$query = $conn->prepare("
    SELECT *
    FROM task_insertion_requests
    WHERE request_uid = ?
    LIMIT 1
");
$query->bind_param("s", $request_uid);
$query->execute();
$request = $query->get_result()->fetch_assoc();
$query->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

// Shortcuts
$user_id = $request['user_id'];
$date = $request['date'];
$work_mode_id = $request['work_mode_id'];
$task_description_id = $request['task_description_id'];
$start_time = $request['start_time'];
$end_time = $request['end_time'];
$reason = $request['reason'];

// ===============================
// 4️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$descStmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$descStmt->bind_param("i", $task_description_id);
$descStmt->execute();
$descRow = $descStmt->get_result()->fetch_assoc();
$descStmt->close();
$task_text = strtolower($descRow['description'] ?? '');

// ===============================
// 5️⃣ HANDLE REJECTION
// ===============================
if (strtolower($newStatus) === 'rejected') {
    $updateStmt = $conn->prepare("
        UPDATE task_insertion_requests
        SET status = 'Rejected', processed_by = ?, processed_by_role = ?, processed_at = NOW()
        WHERE request_uid = ?
    ");
    $updateStmt->bind_param("iss", $processed_by, $role, $request_uid);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    exit;
}

// ===============================
// 6️⃣ HANDLE APPROVAL
// ===============================
if (strtolower($newStatus) !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

$conn->begin_transaction();
try {
    // Fetch existing task logs for the day
    $logsStmt = $conn->prepare("SELECT * FROM task_logs WHERE user_id=? AND date=? ORDER BY start_time ASC");
    $logsStmt->bind_param("is", $user_id, $date);
    $logsStmt->execute();
    $logs = $logsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $logsStmt->close();

    $inserted = false;
    $allLogs = [];

    // ===============================
    // CASE 1️⃣: End Shift
    // ===============================
    if (strpos($task_text, 'end shift') !== false) {
        $end_time = null;

        // Close previous open-ended task if exists
        $prevStmt = $conn->prepare("
            SELECT id, start_time
            FROM task_logs
            WHERE user_id=? AND date=? AND end_time IS NULL
            ORDER BY start_time DESC
            LIMIT 1
        ");
        $prevStmt->bind_param("is", $user_id, $date);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result()->fetch_assoc();
        $prevStmt->close();

        if ($prevResult) {
            $prev_id = $prevResult['id'];
            $prev_start = $prevResult['start_time'];

            // Compute duration
            $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
            $diffStmt->bind_param("ss", $start_time, $prev_start);
            $diffStmt->execute();
            $duration = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
            $diffStmt->close();

            // Close previous task
            $updatePrev = $conn->prepare("UPDATE task_logs SET end_time=?, total_duration=? WHERE id=?");
            $updatePrev->bind_param("ssi", $start_time, $duration, $prev_id);
            $updatePrev->execute();
            $updatePrev->close();
        }

        // Insert End Shift task
        $insertStmt = $conn->prepare("
            INSERT INTO task_logs (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
        ");
        $remarks = "Inserted via approved request ($request_uid)";
        $insertStmt->bind_param("iiissss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $end_time, $remarks);
        $insertStmt->execute();
        $insertStmt->close();

        // Update request
        $upd = $conn->prepare("UPDATE task_insertion_requests SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW() WHERE request_uid=?");
        $upd->bind_param("iss", $processed_by, $role, $request_uid);
        $upd->execute();
        $upd->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'End Shift task approved.']);
        exit;
    }

    // ===============================
    // CASE 2️⃣: Forgot First Task (start_time < first log)
    // ===============================
    if (!empty($logs) && strtotime($start_time) < strtotime($logs[0]['start_time'])) {
        // Optional end_time → compute from next task if not provided
        if (empty($end_time)) {
            $end_time = $logs[0]['start_time'];
        }

        $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
        $diffStmt->bind_param("ss", $end_time, $start_time);
        $diffStmt->execute();
        $total_duration = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
        $diffStmt->close();

        $insertStmt = $conn->prepare("
            INSERT INTO task_logs (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $remarks = "Inserted via approved request ($request_uid)";
        $insertStmt->bind_param("iiisssss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $end_time, $total_duration, $remarks);
        $insertStmt->execute();
        $insertStmt->close();

        // Update request
        $upd = $conn->prepare("UPDATE task_insertion_requests SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW() WHERE request_uid=?");
        $upd->bind_param("iss", $processed_by, $role, $request_uid);
        $upd->execute();
        $upd->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Forgot First Task approved and inserted.']);
        exit;
    }

    // ===============================
    // CASE 3️⃣: In-Between Task
    // ===============================
    foreach ($logs as $l) {
        $lStartSec = strtotime($l['start_time']);
        $lEndSec = strtotime($l['end_time']);
        $reqStartSec = strtotime($start_time);
        $reqEndSec = strtotime($end_time);

        if ($reqStartSec >= $lStartSec && $reqEndSec <= $lEndSec) {
            // Split before → inserted → after
            if ($reqStartSec > $lStartSec) {
                $allLogs[] = [
                    'start_time' => $l['start_time'],
                    'end_time' => $start_time,
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $allLogs[] = [
                'start_time' => $start_time,
                'end_time' => $end_time,
                'work_mode_id' => $work_mode_id,
                'task_description_id' => $task_description_id,
                'remarks' => $reason
            ];

            if ($reqEndSec < $lEndSec) {
                $allLogs[] = [
                    'start_time' => $end_time,
                    'end_time' => $l['end_time'],
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $inserted = true;
        } else {
            $allLogs[] = $l;
        }
    }

    if (!$inserted) {
        throw new Exception('Requested task does not fit inside any existing task log.');
    }

    // Delete old logs
    $delStmt = $conn->prepare("DELETE FROM task_logs WHERE user_id=? AND date=?");
    $delStmt->bind_param("is", $user_id, $date);
    $delStmt->execute();
    $delStmt->close();

    // Reinsert all logs
    foreach ($allLogs as $l) {
        $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
        $diffStmt->bind_param("ss", $l['end_time'], $l['start_time']);
        $diffStmt->execute();
        $duration = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
        $diffStmt->close();

        $insStmt = $conn->prepare("
            INSERT INTO task_logs (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->bind_param("iiisssss", $user_id, $l['work_mode_id'], $l['task_description_id'], $date, $l['start_time'], $l['end_time'], $duration, $l['remarks']);
        $insStmt->execute();
        $insStmt->close();
    }

    // Update request as approved
    $upd = $conn->prepare("UPDATE task_insertion_requests SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW() WHERE request_uid=?");
    $upd->bind_param("iss", $processed_by, $role, $request_uid);
    $upd->execute();
    $upd->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'In-Between Task approved and inserted successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>*/

/*
// ======================================
// update_task_insertion_status.php
// Archive-Aware Version (FINAL) (WORKING)
// ======================================
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$processed_by = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$request_uid = $_POST['request_uid'] ?? null;
$newStatus = $_POST['status'] ?? null;

if (!$request_uid || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// ===============================
// 3️⃣ FETCH REQUEST DETAILS
// ===============================
$query = $conn->prepare("
    SELECT *
    FROM task_insertion_requests
    WHERE request_uid = ?
    LIMIT 1
");
$query->bind_param("s", $request_uid);
$query->execute();
$request = $query->get_result()->fetch_assoc();
$query->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

// Shortcuts
$user_id = $request['user_id'];
$date = $request['date'];
$work_mode_id = $request['work_mode_id'];
$task_description_id = $request['task_description_id'];
$start_time = $request['start_time'];
$end_time = $request['end_time'];
$reason = $request['reason'];

// ===============================
// 4️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$descStmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$descStmt->bind_param("i", $task_description_id);
$descStmt->execute();
$descRow = $descStmt->get_result()->fetch_assoc();
$descStmt->close();
$task_text = strtolower($descRow['description'] ?? '');

// ===============================
// 5️⃣ HANDLE REJECTION
// ===============================
if (strtolower($newStatus) === 'rejected') {
    $updateStmt = $conn->prepare("
        UPDATE task_insertion_requests
        SET status = 'Rejected', processed_by = ?, processed_by_role = ?, processed_at = NOW()
        WHERE request_uid = ?
    ");
    $updateStmt->bind_param("iss", $processed_by, $role, $request_uid);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    exit;
}

// ===============================
// 6️⃣ HANDLE APPROVAL
// ===============================
if (strtolower($newStatus) !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// ===============================
// 7️⃣ DETECT ARCHIVE AWARENESS
// ===============================
$isArchivedMonth = false;
$checkArchive = $conn->prepare("
    SELECT COUNT(*) AS cnt 
    FROM task_logs_archive 
    WHERE user_id = ? 
      AND MONTH(archived_month) = MONTH(?) 
      AND YEAR(archived_month) = YEAR(?)
");
$checkArchive->bind_param("iss", $user_id, $date, $date);
$checkArchive->execute();
$checkResult = $checkArchive->get_result()->fetch_assoc();
$checkArchive->close();

if ($checkResult && $checkResult['cnt'] > 0) {
    $isArchivedMonth = true;
}

$table = $isArchivedMonth ? "task_logs_archive" : "task_logs";

// ===============================
// 8️⃣ MAIN PROCESSING
// ===============================
$conn->begin_transaction();
try {
    // Fetch existing task logs
    $logsStmt = $conn->prepare("SELECT * FROM {$table} WHERE user_id=? AND date=? ORDER BY start_time ASC");
    $logsStmt->bind_param("is", $user_id, $date);
    $logsStmt->execute();
    $logs = $logsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $logsStmt->close();

    $inserted = false;
    $allLogs = [];

    // CASE 1️⃣: End Shift
    if (strpos($task_text, 'end shift') !== false) {
        $end_time = null;

        // Close previous open-ended task if exists
        $prevStmt = $conn->prepare("
            SELECT id, start_time
            FROM {$table}
            WHERE user_id=? AND date=? AND end_time IS NULL
            ORDER BY start_time DESC
            LIMIT 1
        ");
        $prevStmt->bind_param("is", $user_id, $date);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result()->fetch_assoc();
        $prevStmt->close();

        if ($prevResult) {
            $prev_id = $prevResult['id'];
            $prev_start = $prevResult['start_time'];

            $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
            $diffStmt->bind_param("ss", $start_time, $prev_start);
            $diffStmt->execute();
            $duration = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
            $diffStmt->close();

            $updatePrev = $conn->prepare("UPDATE {$table} SET end_time=?, total_duration=? WHERE id=?");
            $updatePrev->bind_param("ssi", $start_time, $duration, $prev_id);
            $updatePrev->execute();
            $updatePrev->close();
        }

        $remarks = "Inserted via approved request ($request_uid)";
        $insertStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
        ");
        $insertStmt->bind_param("iiissss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $end_time, $remarks);
        $insertStmt->execute();
        $insertStmt->close();

        $upd = $conn->prepare("UPDATE task_insertion_requests SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW() WHERE request_uid=?");
        $upd->bind_param("iss", $processed_by, $role, $request_uid);
        $upd->execute();
        $upd->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'End Shift task approved (Archive-aware).']);
        exit;
    }

    // CASE 2️⃣: Forgot First Task
    if (!empty($logs) && strtotime($start_time) < strtotime($logs[0]['start_time'])) {
        if (empty($end_time)) $end_time = $logs[0]['start_time'];

        $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
        $diffStmt->bind_param("ss", $end_time, $start_time);
        $diffStmt->execute();
        $total_duration = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
        $diffStmt->close();

        $remarks = "Inserted via approved request ($request_uid)";
        $insertStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param("iiisssss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $end_time, $total_duration, $remarks);
        $insertStmt->execute();
        $insertStmt->close();

        $upd = $conn->prepare("UPDATE task_insertion_requests SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW() WHERE request_uid=?");
        $upd->bind_param("iss", $processed_by, $role, $request_uid);
        $upd->execute();
        $upd->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Forgot First Task approved (Archive-aware).']);
        exit;
    }

    // CASE 3️⃣: In-Between Task
    foreach ($logs as $l) {
        $lStartSec = strtotime($l['start_time']);
        $lEndSec = strtotime($l['end_time']);
        $reqStartSec = strtotime($start_time);
        $reqEndSec = strtotime($end_time);

        if ($reqStartSec >= $lStartSec && $reqEndSec <= $lEndSec) {
            if ($reqStartSec > $lStartSec) {
                $allLogs[] = [
                    'start_time' => $l['start_time'],
                    'end_time' => $start_time,
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $allLogs[] = [
                'start_time' => $start_time,
                'end_time' => $end_time,
                'work_mode_id' => $work_mode_id,
                'task_description_id' => $task_description_id,
                'remarks' => $reason
            ];

            if ($reqEndSec < $lEndSec) {
                $allLogs[] = [
                    'start_time' => $end_time,
                    'end_time' => $l['end_time'],
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $inserted = true;
        } else {
            $allLogs[] = $l;
        }
    }

    if (!$inserted) throw new Exception('Requested task does not fit inside any existing task log.');

    // Delete old logs
    $delStmt = $conn->prepare("DELETE FROM {$table} WHERE user_id=? AND date=?");
    $delStmt->bind_param("is", $user_id, $date);
    $delStmt->execute();
    $delStmt->close();

    // Reinsert all logs
    foreach ($allLogs as $l) {
        $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
        $diffStmt->bind_param("ss", $l['end_time'], $l['start_time']);
        $diffStmt->execute();
        $duration = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
        $diffStmt->close();

        $insStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->bind_param("iiisssss", $user_id, $l['work_mode_id'], $l['task_description_id'], $date, $l['start_time'], $l['end_time'], $duration, $l['remarks']);
        $insStmt->execute();
        $insStmt->close();
    }

    $upd = $conn->prepare("UPDATE task_insertion_requests SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW() WHERE request_uid=?");
    $upd->bind_param("iss", $processed_by, $role, $request_uid);
    $upd->execute();
    $upd->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'In-Between Task approved and inserted successfully (Archive-aware).']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>*/

/*
// ======================================
// update_task_insertion_status.php
// Archive-Aware Version (PATCHED - COPY/PASTE)
// Supports: End Shift, Forgot First, Open Split (end_time NULL), Gap Insert, In-between Split
// ======================================
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$processed_by = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$request_uid = $_POST['request_uid'] ?? null;
$newStatus   = $_POST['status'] ?? null;

if (!$request_uid || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// ===============================
// 3️⃣ FETCH REQUEST DETAILS
// ===============================
$query = $conn->prepare("
    SELECT *
    FROM task_insertion_requests
    WHERE request_uid = ?
    LIMIT 1
");
$query->bind_param("s", $request_uid);
$query->execute();
$request = $query->get_result()->fetch_assoc();
$query->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

// Shortcuts
$user_id             = (int)$request['user_id'];
$date                = $request['date'];
$work_mode_id        = (int)$request['work_mode_id'];
$task_description_id = (int)$request['task_description_id'];
$start_time          = $request['start_time']; // DATETIME string
$end_time            = $request['end_time'];   // DATETIME string or NULL
$reason              = $request['reason'] ?? '';
$affected_log_id     = isset($request['affected_log_id']) ? (int)$request['affected_log_id'] : null;

// ===============================
// 4️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$descStmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$descStmt->bind_param("i", $task_description_id);
$descStmt->execute();
$descRow = $descStmt->get_result()->fetch_assoc();
$descStmt->close();

$task_text  = strtolower($descRow['description'] ?? '');
$isEndShift = (strpos($task_text, 'end shift') !== false);

// ===============================
// 5️⃣ HANDLE REJECTION
// ===============================
if (strtolower($newStatus) === 'rejected') {
    $updateStmt = $conn->prepare("
        UPDATE task_insertion_requests
        SET status = 'Rejected', processed_by = ?, processed_by_role = ?, processed_at = NOW()
        WHERE request_uid = ?
    ");
    $updateStmt->bind_param("iss", $processed_by, $role, $request_uid);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    exit;
}

// ===============================
// 6️⃣ HANDLE APPROVAL
// ===============================
if (strtolower($newStatus) !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// ===============================
// 7️⃣ DETECT ARCHIVE AWARENESS
// ===============================
$isArchivedMonth = false;

$checkArchive = $conn->prepare("
    SELECT COUNT(*) AS cnt 
    FROM task_logs_archive 
    WHERE user_id = ? 
      AND MONTH(archived_month) = MONTH(?) 
      AND YEAR(archived_month) = YEAR(?)
");
$checkArchive->bind_param("iss", $user_id, $date, $date);
$checkArchive->execute();
$checkResult = $checkArchive->get_result()->fetch_assoc();
$checkArchive->close();

if ($checkResult && (int)$checkResult['cnt'] > 0) {
    $isArchivedMonth = true;
}

$table = $isArchivedMonth ? "task_logs_archive" : "task_logs";

// ===============================
// 8️⃣ MAIN PROCESSING
// ===============================
$conn->begin_transaction();

try {
    // Fetch existing logs (needed for Forgot First / In-between / validation)
    $logsStmt = $conn->prepare("SELECT * FROM {$table} WHERE user_id=? AND date=? ORDER BY start_time ASC");
    $logsStmt->bind_param("is", $user_id, $date);
    $logsStmt->execute();
    $logs = $logsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $logsStmt->close();

    // Helper: mark request approved
    $markApproved = function() use ($conn, $processed_by, $role, $request_uid) {
        $upd = $conn->prepare("
            UPDATE task_insertion_requests
            SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW()
            WHERE request_uid=?
        ");
        $upd->bind_param("iss", $processed_by, $role, $request_uid);
        $upd->execute();
        $upd->close();
    };

    // Helper: duration between two datetimes
    $calcDuration = function($end, $start) use ($conn) {
        $diffStmt = $conn->prepare("SELECT TIMEDIFF(?, ?) AS duration");
        $diffStmt->bind_param("ss", $end, $start);
        $diffStmt->execute();
        $dur = $diffStmt->get_result()->fetch_assoc()['duration'] ?? null;
        $diffStmt->close();
        return $dur;
    };

    // ===============================
    // CASE 1️⃣: End Shift (open-ended)
    // ===============================
    if ($isEndShift) {
        $end_time = null;

        // Close previous open-ended task if exists
        $prevStmt = $conn->prepare("
            SELECT id, start_time
            FROM {$table}
            WHERE user_id=? AND date=? AND end_time IS NULL
            ORDER BY start_time DESC
            LIMIT 1
        ");
        $prevStmt->bind_param("is", $user_id, $date);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result()->fetch_assoc();
        $prevStmt->close();

        if ($prevResult) {
            $prev_id    = (int)$prevResult['id'];
            $prev_start = $prevResult['start_time'];

            $duration = $calcDuration($start_time, $prev_start);

            $updatePrev = $conn->prepare("UPDATE {$table} SET end_time=?, total_duration=? WHERE id=?");
            $updatePrev->bind_param("ssi", $start_time, $duration, $prev_id);
            $updatePrev->execute();
            $updatePrev->close();
        }

        $remarks = "Inserted via approved request ($request_uid)";
        $insertStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, NULL, NULL, ?)
        ");
        $insertStmt->bind_param("iiisss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $remarks);
        $insertStmt->execute();
        $insertStmt->close();

        $markApproved();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'End Shift task approved (Archive-aware).']);
        exit;
    }

    // ===============================
    // CASE 2️⃣: Forgot First Task (insert before first log)
    // ===============================
    if (!empty($logs) && strtotime($start_time) < strtotime($logs[0]['start_time'])) {
        if (empty($end_time)) {
            // safety: fill to first log start
            $end_time = $logs[0]['start_time'];
        }

        $total_duration = $calcDuration($end_time, $start_time);
        $remarks = "Inserted via approved request ($request_uid)";

        $insertStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param("iiisssss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $end_time, $total_duration, $remarks);
        $insertStmt->execute();
        $insertStmt->close();

        $markApproved();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Forgot First Task approved (Archive-aware).']);
        exit;
    }

    // ===============================
    // CASE 3️⃣: OPEN SPLIT (request end_time is NULL, not End Shift)
    // - Close the current open task at request start_time
    // - Insert new open task starting at request start_time
    // ===============================
    if (empty($end_time)) {

        // Prefer using affected_log_id if present
        if (!empty($affected_log_id)) {
            $prevStmt = $conn->prepare("
                SELECT id, start_time
                FROM {$table}
                WHERE id=? AND user_id=? AND date=?
                LIMIT 1
            ");
            $prevStmt->bind_param("iis", $affected_log_id, $user_id, $date);
            $prevStmt->execute();
            $prev = $prevStmt->get_result()->fetch_assoc();
            $prevStmt->close();
        } else {
            // fallback: latest open task
            $prevStmt = $conn->prepare("
                SELECT id, start_time
                FROM {$table}
                WHERE user_id=? AND date=? AND end_time IS NULL
                ORDER BY start_time DESC
                LIMIT 1
            ");
            $prevStmt->bind_param("is", $user_id, $date);
            $prevStmt->execute();
            $prev = $prevStmt->get_result()->fetch_assoc();
            $prevStmt->close();
        }

        if (empty($prev)) {
            throw new Exception("No open task found to split.");
        }

        // Close previous task at $start_time
        $prevDuration = $calcDuration($start_time, $prev['start_time']);

        $updPrev = $conn->prepare("UPDATE {$table} SET end_time=?, total_duration=? WHERE id=?");
        $prev_id = (int)$prev['id'];
        $updPrev->bind_param("ssi", $start_time, $prevDuration, $prev_id);
        $updPrev->execute();
        $updPrev->close();

        // Insert new OPEN task
        $remarks = "Inserted via approved request ($request_uid)";
        $insStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, NULL, NULL, ?)
        ");
        $insStmt->bind_param("iiisss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $remarks);
        $insStmt->execute();
        $insStmt->close();

        $markApproved();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Open-ended task insertion approved and applied (Archive-aware).']);
        exit;
    }

    // ===============================
    // CASE 4️⃣: GAP INSERTION (between tasks)
    // If prev.end_time <= req.start AND req.end <= next.start_time -> insert directly.
    // ===============================
    if (!empty($end_time)) {
        // prev task ending at/before req start
        $prevStmt = $conn->prepare("
            SELECT id, start_time, end_time
            FROM {$table}
            WHERE user_id=? AND date=?
              AND end_time IS NOT NULL
              AND end_time <= ?
            ORDER BY end_time DESC
            LIMIT 1
        ");
        $prevStmt->bind_param("iss", $user_id, $date, $start_time);
        $prevStmt->execute();
        $prev = $prevStmt->get_result()->fetch_assoc();
        $prevStmt->close();

        // next task starting at/after req end (note: includes End Shift with end_time NULL)
        $nextStmt = $conn->prepare("
            SELECT id, start_time, end_time
            FROM {$table}
            WHERE user_id=? AND date=?
              AND start_time >= ?
            ORDER BY start_time ASC
            LIMIT 1
        ");
        $nextStmt->bind_param("iss", $user_id, $date, $end_time);
        $nextStmt->execute();
        $next = $nextStmt->get_result()->fetch_assoc();
        $nextStmt->close();

        if ($prev && $next) {
            $prevEndOk = !empty($prev['end_time']) && strtotime($prev['end_time']) <= strtotime($start_time);
            $nextStartOk = !empty($next['start_time']) && strtotime($end_time) <= strtotime($next['start_time']);

            if ($prevEndOk && $nextStartOk) {
                $dur = $calcDuration($end_time, $start_time);
                $remarks = "Inserted via approved request ($request_uid)";

                $insStmt = $conn->prepare("
                    INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insStmt->bind_param("iiisssss", $user_id, $work_mode_id, $task_description_id, $date, $start_time, $end_time, $dur, $remarks);
                $insStmt->execute();
                $insStmt->close();

                $markApproved();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Gap insertion approved and inserted successfully (Archive-aware).']);
                exit;
            }
        }
    }

    // ===============================
    // CASE 5️⃣: IN-BETWEEN SPLIT (inside a container log)
    // Rebuild day: split container into up to 3 pieces.
    // ===============================
    $inserted = false;
    $allLogs = [];

    $reqStartSec = strtotime($start_time);
    $reqEndSec   = strtotime($end_time);

    foreach ($logs as $l) {
        $lStartSec = strtotime($l['start_time']);
        // Treat NULL end_time as infinity
        $lEndSec = !empty($l['end_time']) ? strtotime($l['end_time']) : PHP_INT_MAX;

        if ($reqStartSec >= $lStartSec && $reqEndSec <= $lEndSec) {

            // left piece
            if ($reqStartSec > $lStartSec) {
                $allLogs[] = [
                    'start_time' => $l['start_time'],
                    'end_time'   => $start_time,
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            // inserted piece
            $allLogs[] = [
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'work_mode_id' => $work_mode_id,
                'task_description_id' => $task_description_id,
                'remarks' => $reason
            ];

            // right piece (only if original had a real end_time, not infinity)
            if (!empty($l['end_time']) && $reqEndSec < $lEndSec) {
                $allLogs[] = [
                    'start_time' => $end_time,
                    'end_time'   => $l['end_time'],
                    'work_mode_id' => $l['work_mode_id'],
                    'task_description_id' => $l['task_description_id'],
                    'remarks' => $l['remarks']
                ];
            }

            $inserted = true;
        } else {
            $allLogs[] = $l;
        }
    }

    if (!$inserted) {
        throw new Exception('Requested task does not fit inside any existing task log (and not a valid gap/open split).');
    }

    // Delete old logs for that day
    $delStmt = $conn->prepare("DELETE FROM {$table} WHERE user_id=? AND date=?");
    $delStmt->bind_param("is", $user_id, $date);
    $delStmt->execute();
    $delStmt->close();

    // Reinsert rebuilt logs
    foreach ($allLogs as $l) {
        // skip invalid rows
        if (empty($l['start_time']) || empty($l['end_time'])) {
            // if a row has NULL end_time here, it means it's open-ended; allow it:
            // but total_duration must be NULL in that case
            $remarks = $l['remarks'] ?? null;
            $insStmt = $conn->prepare("
                INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
                VALUES (?, ?, ?, ?, ?, NULL, NULL, ?)
            ");
            $insStmt->bind_param("iiisss", $user_id, $l['work_mode_id'], $l['task_description_id'], $date, $l['start_time'], $remarks);
            $insStmt->execute();
            $insStmt->close();
            continue;
        }

        $duration = $calcDuration($l['end_time'], $l['start_time']);

        $insStmt = $conn->prepare("
            INSERT INTO {$table} (user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->bind_param("iiisssss", $user_id, $l['work_mode_id'], $l['task_description_id'], $date, $l['start_time'], $l['end_time'], $duration, $l['remarks']);
        $insStmt->execute();
        $insStmt->close();
    }

    $markApproved();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'In-between task approved and applied successfully (Archive-aware).']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
*/

// ======================================
// update_task_insertion_status.php
// SHIFT-AWARE + MINUTE-LOCKED + SPLIT SAFE
// Archive bypass: uses task_logs only
//
// Request assumptions:
// - task_insertion_requests.date      = WORK DATE (shift date)
// - start_time/end_time               = TIME (HH:MM:SS or HH:MM)
// - end_date                          = DATE (nullable, optional)
// - end shift = task_description_id == 11 OR description contains "end shift"
//
// Supports:
// 1) End Shift
// 2) Forgot First Task
// 3) Gap insert between tasks
// 4) In-between split inside container task
// 5) Open split (end_time NULL)
// ======================================

require '../connection_db.php';
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$processed_by = (int)$_SESSION['user_id'];
$role = (string)$_SESSION['role'];

// ===============================
// Helpers
// ===============================
function floorToMinute(?string $t): ?string
{
    if ($t === null) return null;
    $t = trim($t);
    if ($t === '' || $t === '--') return null;

    // accept HH:MM
    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';

    // accept HH:MM:SS -> floor seconds
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return substr($t, 0, 5) . ':00';

    return null;
}

function addDays(string $date, int $days): string
{
    return date("Y-m-d", strtotime($date . " {$days} day"));
}

function makeDT(string $date, string $time): DateTime
{
    return new DateTime($date . ' ' . $time);
}

function diffMinutes(DateTime $a, DateTime $b): int
{
    $sec = $b->getTimestamp() - $a->getTimestamp();
    if ($sec < 0) $sec = 0;
    return intdiv($sec, 60);
}

function fmtDurationMinutes(int $mins): string
{
    $h = intdiv($mins, 60);
    $m = $mins % 60;
    return sprintf("%02d:%02d:00", $h, $m);
}

function overlaps(DateTime $aStart, DateTime $aEnd, DateTime $bStart, DateTime $bEnd): bool
{
    return ($aStart < $bEnd) && ($bStart < $aEnd);
}

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$request_uid = $_POST['request_uid'] ?? null;
$newStatus   = $_POST['status'] ?? null;

if (!$request_uid || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$newStatusLower = strtolower(trim($newStatus));

// ===============================
// 3️⃣ FETCH REQUEST DETAILS
// ===============================
$q = $conn->prepare("
    SELECT *
    FROM task_insertion_requests
    WHERE request_uid = ?
    LIMIT 1
");
$q->bind_param("s", $request_uid);
$q->execute();
$request = $q->get_result()->fetch_assoc();
$q->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

$user_id            = (int)$request['user_id'];
$work_date          = $request['date']; // ✅ work_date (shift date)
$work_mode_id       = (int)$request['work_mode_id'];
$task_description_id = (int)$request['task_description_id'];
$start_time_req     = floorToMinute($request['start_time'] ?? null);
$end_time_req       = floorToMinute($request['end_time'] ?? null);  // may be null
$req_end_date_raw   = $request['end_date'] ?? null;                 // optional
$reason             = (string)($request['reason'] ?? '');
$affected_log_id    = !empty($request['affected_log_id']) ? (int)$request['affected_log_id'] : null;

if (!$user_id || !$work_date || !$start_time_req) {
    echo json_encode(['success' => false, 'message' => 'Invalid request fields (user/date/start_time).']);
    exit;
}

// ===============================
// 4️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$descStmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id=? LIMIT 1");
$descStmt->bind_param("i", $task_description_id);
$descStmt->execute();
$desc = $descStmt->get_result()->fetch_assoc()['description'] ?? '';
$descStmt->close();

$task_text = strtolower(trim($desc));
$isEndShift = ((int)$task_description_id === 11) || (strpos($task_text, 'end shift') !== false);

// ===============================
// 5️⃣ HANDLE REJECTION
// ===============================
if ($newStatusLower === 'rejected') {
    $upd = $conn->prepare("
        UPDATE task_insertion_requests
        SET status='Rejected',
            processed_by=?,
            processed_by_role=?,
            processed_at=NOW()
        WHERE request_uid=?
    ");
    $upd->bind_param("iss", $processed_by, $role, $request_uid);
    $upd->execute();
    $upd->close();

    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    exit;
}

// ===============================
// 6️⃣ HANDLE APPROVAL ONLY
// ===============================
if ($newStatusLower !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// ===============================
// 7️⃣ ALWAYS USE task_logs (bypass archive)
// ===============================
$table = "task_logs";

// ===============================
// 8️⃣ LOAD SHIFT call_time (minute-locked)
// ===============================
// first row of that shift with call_time
$call_time = null;
$ct = $conn->prepare("
    SELECT call_time
    FROM {$table}
    WHERE user_id=? AND work_date=? AND call_time IS NOT NULL
    ORDER BY id ASC
    LIMIT 1
");
$ct->bind_param("is", $user_id, $work_date);
$ct->execute();
$call_time = $ct->get_result()->fetch_assoc()['call_time'] ?? null;
$ct->close();

$call_time = floorToMinute($call_time) ?: "00:00:00";

// If time < call_time => calendar date is next day
$getCalendarDate = function (string $t) use ($work_date, $call_time) {
    return ($t < $call_time) ? addDays($work_date, 1) : $work_date;
};

/* request calendar dates
$start_date_req = $getCalendarDate($start_time_req);*/

// replacement for request calendar dates 
$start_date_req = $work_date;

// end_date selection priority:
// 1) use request.end_date if present (since you added it)
// 2) else infer from call_time rule on end_time (WORKING VERSION LOGIC)
/*$end_date_req = null;
if ($end_time_req) {
    if (!empty($req_end_date_raw)) {
        $end_date_req = $req_end_date_raw;
    } else {
        $end_date_req = $getCalendarDate($end_time_req);
        // safety if same day but time goes backwards
        if ($end_date_req === $start_date_req && $end_time_req < $start_time_req) {
            $end_date_req = addDays($end_date_req, 1);
        }
    }
}*/

// end_date selection priority (NEW LOGIC):
$end_date_req = null;

if ($end_time_req) {

    // Use explicitly stored end_date if available
    if (!empty($req_end_date_raw)) {

        $end_date_req = $req_end_date_raw;
    } else {

        // Default: SAME DAY as work_date
        $end_date_req = $work_date;

        // Only rollover if truly overnight
        if ($end_time_req < $start_time_req) {
            $end_date_req = addDays($work_date, 1);
        }
    }
}

// ===============================
// 9️⃣ MAIN PROCESSING
// ===============================
$conn->begin_transaction();

try {
    // load existing logs for this shift (work_date)
    $logsStmt = $conn->prepare("
        SELECT *
        FROM {$table}
        WHERE user_id=? AND work_date=?
        ORDER BY
          CASE WHEN end_time IS NULL THEN 1 ELSE 0 END ASC,
          start_time ASC,
          id ASC
    ");
    $logsStmt->bind_param("is", $user_id, $work_date);
    $logsStmt->execute();
    $logs = $logsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $logsStmt->close();

    // helper mark approved
    $markApproved = function () use ($conn, $processed_by, $role, $request_uid) {
        $u = $conn->prepare("
            UPDATE task_insertion_requests
            SET status='Approved', processed_by=?, processed_by_role=?, processed_at=NOW()
            WHERE request_uid=?
        ");
        $u->bind_param("iss", $processed_by, $role, $request_uid);
        $u->execute();
        $u->close();
    };

    // build segments (DT start/end). open rows keep endDT null.
    $segments = [];
    foreach ($logs as $log) {
        $st = floorToMinute($log['start_time'] ?? null);
        if (!$st) continue;

        //$sd = !empty($log['date']) ? $log['date'] : $getCalendarDate($st);
        // test
        $sd = !empty($log['date'])
            ? $log['date']
            : $work_date;

        $et = floorToMinute($log['end_time'] ?? null);
        $ed = $log['end_date'] ?? null;

        if (!$et) {
            $segments[] = [
                'open' => true,
                'log' => $log,
                'startDT' => makeDT($sd, $st),
                'endDT' => null
            ];
            continue;
        }

        if (!$ed) {
            $ed = $sd;
            if ($et < $st) $ed = addDays($sd, 1);
        }

        $segments[] = [
            'open' => false,
            'log' => $log,
            'startDT' => makeDT($sd, $st),
            'endDT' => makeDT($ed, $et),
        ];
    }

    // First closed segment on this shift
    $firstClosed = null;
    foreach ($segments as $seg) {
        if ($seg['open']) {
            continue;
        }
        $firstClosed = $seg;
        break;
    }

    // Forgot-first: times before the first tag share that tag's calendar date.
    // Do NOT use getCalendarDate here — it pushes pre-call_time times to work_date+1
    // (night-shift rule), which breaks day shifts (e.g. 06:00 insert before 10:11 tag).
    if ($firstClosed) {
        $firstStartTime = floorToMinute($firstClosed['startDT']->format('H:i:s'));
        $anchorCalendarDate = !empty($firstClosed['log']['date'])
            ? $firstClosed['log']['date']
            : $firstClosed['startDT']->format('Y-m-d');

        if ($start_time_req < $firstStartTime) {
            $start_date_req = $anchorCalendarDate;

            if (!$end_time_req) {
                $end_time_req = $firstStartTime;
            } elseif ($end_time_req > $firstStartTime) {
                $end_time_req = $firstStartTime;
            }

            $end_date_req = $anchorCalendarDate;
            if ($end_time_req <= $start_time_req) {
                $end_date_req = addDays($anchorCalendarDate, 1);
            }
        }
    }

    $reqStartDT = makeDT($start_date_req, $start_time_req);

    // ===============================
    // CASE 1️⃣ END SHIFT
    // ===============================
    // Insert End Shift row (CLOSED immediately like tagging)
    if ($isEndShift) {
        $remarks = "Inserted via approved request ($request_uid)";

        // close current open task in this shift if any
        $prev = null;
        $prevStmt = $conn->prepare("
            SELECT id, date, start_time
            FROM {$table}
            WHERE user_id=? AND work_date=? AND (end_time IS NULL OR TRIM(end_time)='')
            ORDER BY start_time DESC, id DESC
            LIMIT 1
        ");
        $prevStmt->bind_param("is", $user_id, $work_date);
        $prevStmt->execute();
        $prev = $prevStmt->get_result()->fetch_assoc();
        $prevStmt->close();

        if ($prev) {
            $prev_id = (int)$prev['id'];
            $prev_start_t = floorToMinute($prev['start_time'] ?? null) ?: "00:00:00";
            $prev_start_d = !empty($prev['date']) ? $prev['date'] : $getCalendarDate($prev_start_t);

            $mins = diffMinutes(makeDT($prev_start_d, $prev_start_t), makeDT($start_date_req, $start_time_req));
            $dur  = fmtDurationMinutes($mins);

            $up = $conn->prepare("UPDATE {$table} SET end_time=?, end_date=?, total_duration=? WHERE id=?");
            $up->bind_param("sssi", $start_time_req, $start_date_req, $dur, $prev_id);
            $up->execute();
            $up->close();
        }

        // insert End Shift row (end_time NULL)
        /*
        $ins = $conn->prepare("
            INSERT INTO {$table}
              (user_id, work_mode_id, task_description_id, date, work_date, start_time, end_time, end_date, total_duration, remarks, call_time)
            VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, '00:00:00', ?, ?)
        ");
        $ins->bind_param(
            "iiisssss",
            $user_id,
            $work_mode_id,
            $task_description_id,
            $start_date_req,
            $work_date,
            $start_time_req,
            $remarks,
            $call_time
        );
        $ins->execute();
        $ins->close();
        */

        $insertStmt = $conn->prepare("
    INSERT INTO {$table}
      (user_id, work_mode_id, task_description_id, date, work_date,
       start_time, end_time, end_date, total_duration, remarks, call_time)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, '00:00:00', ?, ?)
");

        $insertStmt->bind_param(
            "iiisssssss",
            $user_id,
            $work_mode_id,
            $task_description_id,
            $start_date_req,   // calendar date of End Shift tag
            $work_date,        // shift date being closed
            $start_time_req,
            $start_time_req,   // ✅ end_time = start_time (CLOSED)
            $start_date_req,   // ✅ end_date = calendar date of tag
            $remarks,
            $call_time
        );

        $insertStmt->execute();
        $insertStmt->close();

        $markApproved();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'End Shift approved (shift-aware).', "did_close_shift" => true]);
        exit;
    }

    // ===============================
    // CASE 2️⃣ OPEN SPLIT (end_time NULL)
    // ===============================
    if (!$end_time_req) {
        // find open row to split
        $open = null;

        if ($affected_log_id) {
            $s = $conn->prepare("
                SELECT id, date, start_time
                FROM {$table}
                WHERE id=? AND user_id=? AND work_date=?
                LIMIT 1
            ");
            $s->bind_param("iis", $affected_log_id, $user_id, $work_date);
            $s->execute();
            $open = $s->get_result()->fetch_assoc();
            $s->close();
        }

        if (!$open) {
            $s = $conn->prepare("
                SELECT id, date, start_time
                FROM {$table}
                WHERE user_id=? AND work_date=? AND (end_time IS NULL OR TRIM(end_time)='')
                ORDER BY start_time DESC, id DESC
                LIMIT 1
            ");
            $s->bind_param("is", $user_id, $work_date);
            $s->execute();
            $open = $s->get_result()->fetch_assoc();
            $s->close();
        }

        if (!$open) {
            throw new Exception("No open task found to split.");
        }

        $open_id = (int)$open['id'];
        $open_st = floorToMinute($open['start_time'] ?? null) ?: "00:00:00";
        $open_sd = !empty($open['date']) ? $open['date'] : $getCalendarDate($open_st);

        // close open at request start
        $mins = diffMinutes(makeDT($open_sd, $open_st), makeDT($start_date_req, $start_time_req));
        $dur  = fmtDurationMinutes($mins);

        $up = $conn->prepare("UPDATE {$table} SET end_time=?, end_date=?, total_duration=? WHERE id=?");
        $up->bind_param("sssi", $start_time_req, $start_date_req, $dur, $open_id);
        $up->execute();
        $up->close();

        // insert new open task starting at request start
        $remarks = "Inserted via approved request ($request_uid)";
        $ins = $conn->prepare("
            INSERT INTO {$table}
              (user_id, work_mode_id, task_description_id, date, work_date, start_time, end_time, end_date, total_duration, remarks, call_time)
            VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?, ?)
        ");
        $ins->bind_param(
            "iiisssss",
            $user_id,
            $work_mode_id,
            $task_description_id,
            $start_date_req,
            $work_date,
            $start_time_req,
            $remarks,
            $call_time
        );
        $ins->execute();
        $ins->close();

        $markApproved();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Open split approved and applied (shift-aware).', "did_close_shift" => false]);
        exit;
    }

    // from here: request has end_time
    if (!$end_date_req) {
        // should not happen; safety
        $end_date_req = $getCalendarDate($end_time_req);
    }

    $reqEndDT = makeDT($end_date_req, $end_time_req);
    if ($reqEndDT <= $reqStartDT) {
        throw new Exception("Invalid request time range (end must be after start).");
    }

    // ===============================
    // CASE 3️⃣ FORGOT FIRST TASK (before first segment)
    // ===============================
    if ($firstClosed && $reqStartDT < $firstClosed['startDT']) {
        // clamp end to first start if overlaps it
        if ($reqEndDT > $firstClosed['startDT']) {
            $reqEndDT = $firstClosed['startDT'];
            $end_date_req = $reqEndDT->format("Y-m-d");
            $end_time_req = floorToMinute($reqEndDT->format("H:i:s"));
        }

        $mins = diffMinutes($reqStartDT, $reqEndDT);
        $dur  = fmtDurationMinutes($mins);
        $remarks = "Inserted via approved request ($request_uid)";

        $ins = $conn->prepare("
            INSERT INTO {$table}
              (user_id, work_mode_id, task_description_id, date, work_date, start_time, end_time, end_date, total_duration, remarks, call_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $sd = $reqStartDT->format("Y-m-d");
        $st = floorToMinute($reqStartDT->format("H:i:s"));
        $ed = $reqEndDT->format("Y-m-d");
        $et = floorToMinute($reqEndDT->format("H:i:s"));

        $ins->bind_param(
            "iiissssssss",
            $user_id,
            $work_mode_id,
            $task_description_id,
            $sd,
            $work_date,
            $st,
            $et,
            $ed,
            $dur,
            $remarks,
            $call_time
        );
        $ins->execute();
        $ins->close();

        $markApproved();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Forgot first task approved (shift-aware).', "did_close_shift" => false]);
        exit;
    }

    // ===============================
    // CASE 4/5: GAP INSERT or IN-BETWEEN SPLIT
    // Strategy:
    // - rebuild only CLOSED segments by trimming overlap with request
    // - if request overlaps a CLOSED segment => split happens
    // - if no overlap with any CLOSED => it's a pure GAP insert
    // - open rows are preserved and not split
    // ===============================
    $rebuilt = [];
    $didOverlapClosed = false;

    foreach ($segments as $seg) {
        if ($seg['open']) {
            // keep open row untouched
            $rebuilt[] = $seg;
            continue;
        }

        $segStart = $seg['startDT'];
        $segEnd   = $seg['endDT'];

        // no overlap
        if (!overlaps($segStart, $segEnd, $reqStartDT, $reqEndDT)) {
            $rebuilt[] = $seg;
            continue;
        }

        $didOverlapClosed = true;

        // left piece
        if ($reqStartDT > $segStart) {
            $rebuilt[] = [
                'open' => false,
                'log' => $seg['log'],
                'startDT' => $segStart,
                'endDT' => $reqStartDT
            ];
        }

        // right piece
        if ($reqEndDT < $segEnd) {
            $rebuilt[] = [
                'open' => false,
                'log' => $seg['log'],
                'startDT' => $reqEndDT,
                'endDT' => $segEnd
            ];
        }
        // (middle piece is the request, inserted once later)
    }

    // Insert request segment
    $rebuilt[] = [
        'open' => false,
        'log' => [
            'work_mode_id' => $work_mode_id,
            'task_description_id' => $task_description_id,
            'remarks' => "Inserted via approved request ($request_uid)",
            'call_time' => $call_time
        ],
        'startDT' => $reqStartDT,
        'endDT' => $reqEndDT
    ];

    // sort by startDT
    usort($rebuilt, function ($a, $b) {
        return $a['startDT']->getTimestamp() <=> $b['startDT']->getTimestamp();
    });

    // ✅ Validate: request must not overlap OPEN row (we refused splitting open rows)
    foreach ($segments as $seg) {
        if (!$seg['open']) continue;
        $openStart = $seg['startDT'];
        // open has no end; treat as very large (shift end unknown). disallow overlaps after open start.
        if ($reqEndDT > $openStart) {
            // if request is entirely before openStart it's fine
            if (!($reqEndDT <= $openStart)) {
                throw new Exception("Cannot insert inside/after an OPEN task. Close the active task first, or submit as open split (no end_time).");
            }
        }
    }

    // ✅ Apply rebuild:
    // Delete all logs of this shift then reinsert rebuilt
    $del = $conn->prepare("DELETE FROM {$table} WHERE user_id=? AND work_date=?");
    $del->bind_param("is", $user_id, $work_date);
    $del->execute();
    $del->close();

    // reinsert
    foreach ($rebuilt as $seg) {
        if ($seg['open']) {
            $log = $seg['log'];

            $wm = (int)$log['work_mode_id'];
            $td = (int)$log['task_description_id'];

            $sd = !empty($log['date']) ? $log['date'] : $seg['startDT']->format("Y-m-d");
            $st = floorToMinute($log['start_time'] ?? $seg['startDT']->format("H:i:s")) ?: $seg['startDT']->format("H:i:s");

            $rm = (string)($log['remarks'] ?? '');
            $ctv = floorToMinute($log['call_time'] ?? null) ?: $call_time;

            $insOpen = $conn->prepare("
                INSERT INTO {$table}
                  (user_id, work_mode_id, task_description_id, date, work_date, start_time, end_time, end_date, total_duration, remarks, call_time)
                VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?, ?)
            ");
            $insOpen->bind_param("iiisssss", $user_id, $wm, $td, $sd, $work_date, $st, $rm, $ctv);
            $insOpen->execute();
            $insOpen->close();
            continue;
        }

        $log = $seg['log'];
        $wm = (int)$log['work_mode_id'];
        $td = (int)$log['task_description_id'];
        $rm = (string)($log['remarks'] ?? '');
        $ctv = floorToMinute($log['call_time'] ?? null) ?: $call_time;

        $sd = $seg['startDT']->format("Y-m-d");
        $st = floorToMinute($seg['startDT']->format("H:i:s"));
        $ed = $seg['endDT']->format("Y-m-d");
        $et = floorToMinute($seg['endDT']->format("H:i:s"));

        $mins = diffMinutes(makeDT($sd, $st), makeDT($ed, $et));
        $dur = fmtDurationMinutes($mins);

        $ins = $conn->prepare("
            INSERT INTO {$table}
              (user_id, work_mode_id, task_description_id, date, work_date, start_time, end_time, end_date, total_duration, remarks, call_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->bind_param(
            "iiissssssss",
            $user_id,
            $wm,
            $td,
            $sd,
            $work_date,
            $st,
            $et,
            $ed,
            $dur,
            $rm,
            $ctv
        );
        $ins->execute();
        $ins->close();
    }

    // mark request approved
    $markApproved();

    $conn->commit();

    // message differs if gap vs split, but both are valid
    $msg = $didOverlapClosed
        ? "Approved: inserted and rebuilt overlapping segments (shift-aware)."
        : "Approved: inserted in a gap (no overlap) (shift-aware).";

    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
