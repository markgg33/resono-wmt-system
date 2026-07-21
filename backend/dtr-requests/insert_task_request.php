<?php
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;
$reason = trim($_POST['reason'] ?? '');

if (!$date || !$work_mode_id || !$task_description_id || !$start_time || !$end_time || !$reason) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// ===============================
// 3️⃣ RULE 1: Start < End
// ===============================
if (strtotime($start_time) >= strtotime($end_time)) {
    echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
    exit;
}

// ===============================
// 4️⃣ RULE 2: Validate existing task range
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM task_logs 
               WHERE user_id = ? AND date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeRow = $rangeResult->fetch_assoc();
$rangeStmt->close();

if (!$rangeRow['earliest'] || !$rangeRow['latest']) {
    echo json_encode(['success' => false, 'message' => 'No existing logs found for that date.']);
    exit;
}

// ===============================
// 5️⃣ RULE 3: Reject if outside earliest or latest task
// ===============================
if (strtotime($start_time) < strtotime($rangeRow['earliest'])) {
    echo json_encode(['success' => false, 'message' => 'Start time cannot be earlier than your first task.']);
    exit;
}

if (strtotime($end_time) > strtotime($rangeRow['latest'])) {
    echo json_encode(['success' => false, 'message' => 'End time cannot exceed your latest task.']);
    exit;
}

// ===============================
// 6️⃣ RULE 4: Check if fully inside one existing task log
// ===============================
$containerQuery = "
    SELECT id FROM task_logs
    WHERE user_id = ? AND date = ?
      AND start_time <= ? AND end_time >= ?
";
$checkStmt = $conn->prepare($containerQuery);
$checkStmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Task does not fit within any existing time range.']);
    exit;
}

$affected_log_id = $checkResult->fetch_assoc()['id'];

// ===============================
// 7️⃣ INSERT TASK REQUEST (Pending Only)
// ===============================
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,
    $reason,
    $affected_log_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
$conn->close();
*/

//CURRENT WORKING VERSION
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;
$reason = trim($_POST['reason'] ?? '');
$recipient_id = $_POST['recipient_id'] ?? null;

if (!$date || !$work_mode_id || !$task_description_id || !$start_time || !$reason || !$recipient_id) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

// ===============================
// 3️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$taskDescQuery = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$taskDescQuery->bind_param("i", $task_description_id);
$taskDescQuery->execute();
$taskDescResult = $taskDescQuery->get_result()->fetch_assoc();
$taskDescQuery->close();
$task_text = strtolower($taskDescResult['description'] ?? '');

// ===============================
// 4️⃣ FETCH EXISTING TIME RANGE FOR USER
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM task_logs 
               WHERE user_id = ? AND date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeRow = $rangeResult->fetch_assoc();
$rangeStmt->close();

// If no logs exist at all for that date
if (!$rangeRow['earliest'] && !$rangeRow['latest']) {
    echo json_encode(['success' => false, 'message' => 'No existing logs found for that date.']);
    exit;
}

// ===============================
// 5️⃣ SPECIAL CASES & VALIDATIONS
// ===============================

// 🟩 CASE A: “End Shift” task — end_time is optional
if (strpos($task_text, 'end shift') !== false) {
    $end_time = null; // force null for DB

} else {
    // Normal rule: Start < End (if both exist)
    if ($end_time && strtotime($start_time) >= strtotime($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
        exit;
    }
}

// 🟩 CASE B: “Forgot first task” — start earlier than earliest is allowed
//    Old restriction removed completely
//    We only check that date & start_time are valid, not overlapping validation here.

// 🟩 CASE C: Missing end time for first task
//    If end_time is not given (e.g., forgot first task), store as null for now
if (empty($end_time)) {
    $end_time = null;
}

// ===============================
// 6️⃣ AFFECTED LOG LOGIC (Optional reference for admin)
// ===============================
$affected_log_id = null;

// Try to find a nearby log (for context)
$findLogStmt = $conn->prepare("
    SELECT id 
    FROM task_logs 
    WHERE user_id = ? AND date = ? 
    ORDER BY ABS(TIMESTAMPDIFF(SECOND, start_time, ?)) ASC 
    LIMIT 1
");
$findLogStmt->bind_param("iss", $user_id, $date, $start_time);
$findLogStmt->execute();
$logResult = $findLogStmt->get_result();
if ($logRow = $logResult->fetch_assoc()) {
    $affected_log_id = $logRow['id'];
}
$findLogStmt->close();

// ===============================
// 7️⃣ INSERT REQUEST
// ===============================
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,
    $reason,
    $affected_log_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
$conn->close();
?>*/

// ===============================
//CURRENT WORKING VERSION(END SHIFT MISSED TASK & FIRST MISSED TASK)
// ===============================
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;
$reason = trim($_POST['reason'] ?? '');
//$recipient_id = $_POST['recipient_id'] ?? null;

if (!$date || !$work_mode_id || !$task_description_id || !$start_time || !$reason ){//!$recipient_id) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

// ===============================
// 3️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$taskDescQuery = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$taskDescQuery->bind_param("i", $task_description_id);
$taskDescQuery->execute();
$taskDescResult = $taskDescQuery->get_result()->fetch_assoc();
$taskDescQuery->close();
$task_text = strtolower($taskDescResult['description'] ?? '');

// ===============================
// 4️⃣ FETCH EXISTING RANGE
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM task_logs 
               WHERE user_id = ? AND date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeRow = $rangeResult->fetch_assoc();
$rangeStmt->close();

// ===============================
// 5️⃣ VALIDATION & CASE HANDLING
// ===============================
if (strpos($task_text, 'end shift') !== false) {
    // 🟩 CASE A: End Shift — no end_time, open-ended
    $end_time = null;

} else {

    // 🟩 CASE B: Normal task → ensure Start < End (if both exist)
    if ($end_time && strtotime($start_time) >= strtotime($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
        exit;
    }

    // If logs exist, enforce in-between logic
    if ($rangeRow['earliest'] && $rangeRow['latest']) {
        $earliest = $rangeRow['earliest'];
        $latest = $rangeRow['latest'];

        // Allow start earlier than earliest (forgot first task) — skip restriction
        if (strtotime($end_time) > strtotime($latest)) {
            echo json_encode(['success' => false, 'message' => 'End time cannot exceed your latest task.']);
            exit;
        }

        // Find if it fits inside an existing log (for split reference)
        $containerQuery = "
            SELECT id FROM task_logs
            WHERE user_id = ? AND date = ?
              AND start_time <= ? AND end_time >= ?
        ";
        $checkStmt = $conn->prepare($containerQuery);
        $checkStmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        $affected_log_id = null;
        if ($checkResult->num_rows > 0) {
            $affected_log_id = $checkResult->fetch_assoc()['id'];
        }
        $checkStmt->close();
    }
}

// 🟩 CASE C: Missing end_time (forgot first task)
if (empty($end_time)) {
    $end_time = null;
}

// ===============================
// 6️⃣ FALLBACK CONTEXT: nearest log reference if no affected_log_id
// ===============================
if (empty($affected_log_id)) {
    $findLogStmt = $conn->prepare("
        SELECT id 
        FROM task_logs 
        WHERE user_id = ? AND date = ? 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, start_time, ?)) ASC 
        LIMIT 1
    ");
    $findLogStmt->bind_param("iss", $user_id, $date, $start_time);
    $findLogStmt->execute();
    $logResult = $findLogStmt->get_result();
    if ($logRow = $logResult->fetch_assoc()) {
        $affected_log_id = $logRow['id'];
    }
    $findLogStmt->close();
}

// ===============================
// 7️⃣ INSERT REQUEST
// ===============================
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,
    $reason,
    $affected_log_id
    //$recipient_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
$conn->close();
?>
*/

//COMMENT OUT FIRST
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null; // optional for first task
$reason = trim($_POST['reason'] ?? '');

if (!$date || !$work_mode_id || !$task_description_id || !$start_time || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

// ===============================
// 3️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$taskDescQuery = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$taskDescQuery->bind_param("i", $task_description_id);
$taskDescQuery->execute();
$taskDescResult = $taskDescQuery->get_result()->fetch_assoc();
$taskDescQuery->close();
$task_text = strtolower($taskDescResult['description'] ?? '');

// ===============================
// 4️⃣ FETCH EXISTING RANGE
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM task_logs 
               WHERE user_id = ? AND date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeRow = $rangeResult->fetch_assoc();
$rangeStmt->close();

$earliest = $rangeRow['earliest'] ?? null;
$latest = $rangeRow['latest'] ?? null;

// ===============================
// 5️⃣ CASE HANDLING
// ===============================
$affected_log_id = null;

// 🟩 CASE A: End Shift — no end_time required
if (strpos($task_text, 'end shift') !== false) {
    $end_time = null; // enforce NULL
}

// 🟩 CASE B: Forgot First Task (optional end_time)
else if ($earliest && strtotime($start_time) < strtotime($earliest)) {
    // optional end_time, system will calculate on approval
    $end_time = $end_time ?: null;
}

// 🟩 CASE C: In-Between Task (normal insertion)
else {
    if (empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'End time is required for in-between task insertions.']);
        exit;
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
        exit;
    }

    // Check if it fits fully inside an existing task log
    $containerQuery = "
        SELECT id FROM task_logs
        WHERE user_id = ? AND date = ?
          AND start_time <= ? AND end_time >= ?
        LIMIT 1
    ";
    $checkStmt = $conn->prepare($containerQuery);
    $checkStmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Task does not fit within any existing time range.']);
        exit;
    }

    $affected_log_id = $checkResult->fetch_assoc()['id'];
    $checkStmt->close();
}

// ===============================
// 6️⃣ FALLBACK CONTEXT: nearest log if no affected_log_id
// ===============================
if (empty($affected_log_id)) {
    $findLogStmt = $conn->prepare("
        SELECT id 
        FROM task_logs 
        WHERE user_id = ? AND date = ? 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, start_time, ?)) ASC 
        LIMIT 1
    ");
    $findLogStmt->bind_param("iss", $user_id, $date, $start_time);
    $findLogStmt->execute();
    $logResult = $findLogStmt->get_result();
    if ($logRow = $logResult->fetch_assoc()) {
        $affected_log_id = $logRow['id'];
    }
    $findLogStmt->close();
}

// ===============================
// 7️⃣ INSERT TASK REQUEST
// ===============================
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,
    $reason,
    $affected_log_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
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
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time_input = $_POST['start_time'] ?? null;
$end_time_input = $_POST['end_time'] ?? null; // optional for first task
$reason = trim($_POST['reason'] ?? '');

if (!$date || !$work_mode_id || !$task_description_id || !$start_time_input || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

// ===============================
// 2️⃣b Combine date + time for DATETIME
// ===============================
$start_time = $date . ' ' . $start_time_input . ':00';
$end_time = (!empty($end_time_input)) ? $date . ' ' . $end_time_input . ':00' : null;

// ===============================
// 3️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$taskDescQuery = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$taskDescQuery->bind_param("i", $task_description_id);
$taskDescQuery->execute();
$taskDescResult = $taskDescQuery->get_result()->fetch_assoc();
$taskDescQuery->close();
$task_text = strtolower($taskDescResult['description'] ?? '');

// ===============================
// 4️⃣ FETCH EXISTING RANGE
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM task_logs 
               WHERE user_id = ? AND date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeRow = $rangeResult->fetch_assoc();
$rangeStmt->close();

$earliest = $rangeRow['earliest'] ?? null;
$latest = $rangeRow['latest'] ?? null;

// ===============================
// 5️⃣ CASE HANDLING
// ===============================
$affected_log_id = null;

// 🟩 CASE A: End Shift — no end_time required
if (strpos($task_text, 'end shift') !== false) {
    $end_time = null; // enforce NULL
}

// 🟩 CASE B: Forgot First Task (optional end_time)
else if ($earliest && strtotime($start_time) < strtotime($earliest)) {
    // optional end_time, system will calculate on approval
    $end_time = $end_time ?: null;
}

// 🟩 CASE C: In-Between Task (normal insertion)
else {
    if (empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'End time is required for in-between task insertions.']);
        exit;
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
        exit;
    }

    // Check if it fits fully inside an existing task log
    $containerQuery = "
        SELECT id FROM task_logs
        WHERE user_id = ? AND date = ?
          AND start_time <= ? AND end_time >= ?
        LIMIT 1
    ";
    $checkStmt = $conn->prepare($containerQuery);
    $checkStmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Task does not fit within any existing time range.']);
        exit;
    }

    $affected_log_id = $checkResult->fetch_assoc()['id'];
    $checkStmt->close();
}

// ===============================
// 6️⃣ FALLBACK CONTEXT: nearest log if no affected_log_id
// ===============================
if (empty($affected_log_id)) {
    $findLogStmt = $conn->prepare("
        SELECT id 
        FROM task_logs 
        WHERE user_id = ? AND date = ? 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, start_time, ?)) ASC 
        LIMIT 1
    ");
    $findLogStmt->bind_param("iss", $user_id, $date, $start_time);
    $findLogStmt->execute();
    $logResult = $findLogStmt->get_result();
    if ($logRow = $logResult->fetch_assoc()) {
        $affected_log_id = $logRow['id'];
    }
    $findLogStmt->close();
}

// ===============================
// 7️⃣ INSERT TASK REQUEST
// ===============================
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,
    $reason,
    $affected_log_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
$conn->close();
?>*/

//ARCHIVE AWARE TESTING VERSION (WORKING)
/*
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time_input = $_POST['start_time'] ?? null;
$end_time_input = $_POST['end_time'] ?? null; // optional
$reason = trim($_POST['reason'] ?? '');

if (!$date || !$work_mode_id || !$task_description_id || !$start_time_input || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

// ===============================
// 2️⃣b Combine date + time for DATETIME
// ===============================
$start_time = $date . ' ' . $start_time_input . ':00';
$end_time = (!empty($end_time_input)) ? $date . ' ' . $end_time_input . ':00' : null;

// ===============================
// 3️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$taskDescQuery = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$taskDescQuery->bind_param("i", $task_description_id);
$taskDescQuery->execute();
$taskDescResult = $taskDescQuery->get_result()->fetch_assoc();
$taskDescQuery->close();
$task_text = strtolower($taskDescResult['description'] ?? '');

// ===============================
// 3️⃣b DETERMINE IF ARCHIVED MONTH
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
// 4️⃣ FETCH EXISTING RANGE
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM {$table} 
               WHERE user_id = ? AND date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeRow = $rangeResult->fetch_assoc();
$rangeStmt->close();

$earliest = $rangeRow['earliest'] ?? null;
$latest = $rangeRow['latest'] ?? null;

// ===============================
// 🔹 FETCH PREVIOUS & NEXT TASK
// ===============================
$gapQuery = $conn->prepare("
    SELECT id, start_time, end_time
    FROM {$table}
    WHERE user_id = ?
      AND date = ?
      AND start_time > ?
    ORDER BY start_time ASC
    LIMIT 1
");
$gapQuery->bind_param("iss", $user_id, $date, $start_time);
$gapQuery->execute();
$nextTask = $gapQuery->get_result()->fetch_assoc();
$gapQuery->close();

$prevQuery = $conn->prepare("
    SELECT id, start_time, end_time
    FROM {$table}
    WHERE user_id = ?
      AND date = ?
      AND end_time <= ?
    ORDER BY end_time DESC
    LIMIT 1
");
$prevQuery->bind_param("iss", $user_id, $date, $start_time);
$prevQuery->execute();
$prevTask = $prevQuery->get_result()->fetch_assoc();
$prevQuery->close();


// ===============================
// 5️⃣ CASE HANDLING
// ===============================
$affected_log_id = null;

// 🟩 CASE A: End Shift — no end_time required
if (strpos($task_text, 'end shift') !== false) {
    $end_time = null; // enforce NULL
}

// 🟩 CASE B: Forgot First Task
else if ($earliest && strtotime($start_time) < strtotime($earliest)) {
    $end_time = $end_time ?: null;
}

// 🟨 CASE C: Gap Between Two Tasks
if ($prevTask && $nextTask) {

    if (
        strtotime($start_time) >= strtotime($prevTask['end_time']) &&
        strtotime($end_time) <= strtotime($nextTask['start_time'])
    ) {

        // Valid gap insertion
        $affected_log_id = $prevTask['id']; // anchor to previous task
    }
}


// 🟩 CASE C: In-Between Task
else {
    if (empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'End time is required for in-between task insertions.']);
        exit;
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
        exit;
    }

    // check container
    $containerQuery = "
        SELECT id FROM {$table}
        WHERE user_id = ? AND date = ?
          AND start_time <= ? AND end_time >= ?
        LIMIT 1
    ";
    $checkStmt = $conn->prepare($containerQuery);
    $checkStmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Task does not fit within any existing time range.']);
        exit;
    }

    $affected_log_id = $checkResult->fetch_assoc()['id'];
    $checkStmt->close();
}

// ===============================
// 6️⃣ FALLBACK CONTEXT
// ===============================
if (empty($affected_log_id)) {
    $findLogStmt = $conn->prepare("
        SELECT id 
        FROM {$table}
        WHERE user_id = ? AND date = ? 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, start_time, ?)) ASC 
        LIMIT 1
    ");
    $findLogStmt->bind_param("iss", $user_id, $date, $start_time);
    $findLogStmt->execute();
    $logResult = $findLogStmt->get_result();
    if ($logRow = $logResult->fetch_assoc()) {
        $affected_log_id = $logRow['id'];
    }
    $findLogStmt->close();
}

// ===============================
// 7️⃣ INSERT TASK REQUEST
// ===============================
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,
    $reason,
    $affected_log_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
$conn->close();
*/

// ARCHIVE AWARE TESTING VERSION (FULL PATCHED)
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ===============================
// 1️⃣ AUTH CHECK
// ===============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 2️⃣ INPUT VALIDATION
// ===============================
$date = $_POST['date'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;
$start_time_input = $_POST['start_time'] ?? null;
$end_time_input = $_POST['end_time'] ?? null; // optional
$reason = trim($_POST['reason'] ?? '');

if (!$date || !$work_mode_id || !$task_description_id || !$start_time_input || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

// ===============================
// 2️⃣b Combine date + time for DATETIME
// ===============================
/*
$start_time = $date . ' ' . $start_time_input . ':00';
$end_time   = (!empty($end_time_input)) ? $date . ' ' . $end_time_input . ':00' : null;
*/
// Store TIME (minute-locked), not DATETIME
$start_time = $start_time_input . ':00';
$end_time   = (!empty($end_time_input)) ? ($end_time_input . ':00') : null;

// end_date resolved after earliest-log lookup (forgot-first uses calendar date from first tag)
$end_date = null;


// ===============================
// 3️⃣ FETCH TASK DESCRIPTION TEXT
// ===============================
$taskDescQuery = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
$taskDescQuery->bind_param("i", $task_description_id);
$taskDescQuery->execute();
$taskDescResult = $taskDescQuery->get_result()->fetch_assoc();
$taskDescQuery->close();

$task_text = strtolower($taskDescResult['description'] ?? '');

// ===============================
// 3️⃣b DETERMINE IF ARCHIVED MONTH
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
// 🔹 FIND CURRENT OPEN TASK (end_time IS NULL)
// This is the active/unfinished task for that day.
// ===============================
$openTask = null;
$openStmt = $conn->prepare("
    SELECT id, start_time
    FROM {$table}
    WHERE user_id = ? AND work_date = ?
      AND end_time IS NULL
    ORDER BY start_time DESC
    LIMIT 1
");
$openStmt->bind_param("is", $user_id, $date);
$openStmt->execute();
$openTask = $openStmt->get_result()->fetch_assoc();
$openStmt->close();

$isEndShift = (strpos($task_text, 'end shift') !== false);

// ===============================
// 🔹 AUTO-INFER END TIME (END SHIFT STYLE FOR ANY TAG)
// If end_time not provided:
// 1) If not End Shift → try infer end_time = next task start_time
// 2) If no next task → allow only if there's an open task that started before start_time
//    In that case: end_time remains NULL and affected_log_id anchors to open task.
// ===============================
if (empty($end_time_input) && !$isEndShift) {

    // Find the next task after this start_time
    $nextStmt = $conn->prepare("
        SELECT start_time
        FROM {$table}
        WHERE user_id = ? AND work_date = ?
          AND start_time > ?
        ORDER BY start_time ASC
        LIMIT 1
    ");
    $nextStmt->bind_param("iss", $user_id, $date, $start_time);
    $nextStmt->execute();
    $nextRow = $nextStmt->get_result()->fetch_assoc();
    $nextStmt->close();

    if ($nextRow && !empty($nextRow['start_time'])) {
        // ✅ Normal inference: end_time becomes next task start_time
        $end_time = $nextRow['start_time'];
    } else {
        // ✅ No next task: allow if splitting an OPEN task
        if ($openTask && !empty($openTask['start_time']) && strtotime($openTask['start_time']) < strtotime($start_time)) {
            // Keep end_time NULL -> inserted task becomes new "open" task on approval
            $end_time = null;

            // Anchor affected_log_id to the open task so approval can close it at start_time
            $affected_log_id = (int)$openTask['id'];
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'End time is required because there is no next task to infer from, and no open task exists to split.'
            ]);
            exit;
        }
    }
}

// ===============================
// 4️⃣ FETCH EXISTING RANGE
// ===============================
$rangeQuery = "SELECT MIN(start_time) AS earliest, MAX(end_time) AS latest 
               FROM {$table} 
               WHERE user_id = ? AND work_date = ?";
$rangeStmt = $conn->prepare($rangeQuery);
$rangeStmt->bind_param("is", $user_id, $date);
$rangeStmt->execute();
$rangeRow = $rangeStmt->get_result()->fetch_assoc();
$rangeStmt->close();

$earliest = $rangeRow['earliest'] ?? null;

// Calendar date of the first tagged task on this shift (for forgot-first inserts)
$earliestLogDate = null;
if ($earliest) {
    $earliestDateStmt = $conn->prepare("
        SELECT date
        FROM {$table}
        WHERE user_id = ? AND work_date = ?
        ORDER BY start_time ASC, id ASC
        LIMIT 1
    ");
    $earliestDateStmt->bind_param('is', $user_id, $date);
    $earliestDateStmt->execute();
    $earliestLogDate = $earliestDateStmt->get_result()->fetch_assoc()['date'] ?? null;
    $earliestDateStmt->close();
}

$isForgotFirstTask = $earliest && (strtotime($start_time) < strtotime($earliest));

// end_date: forgot-first uses the same calendar date as the first existing tag
if (!empty($end_time)) {
    if ($isForgotFirstTask) {
        $end_date = $earliestLogDate ?: $date;
    } else {
        $end_date = $date;
    }
}

// ===============================
// 5️⃣ CASE HANDLING
// ===============================
// NOTE: do NOT reset this later; inference may set it
if (!isset($affected_log_id)) $affected_log_id = null;


// 🟩 CASE A: End Shift — no end_time required
if ($isEndShift) {
    $end_time = null;
}

// 🟩 CASE B: Forgot First Task (start_time earlier than earliest task)
if ($earliest && strtotime($start_time) < strtotime($earliest)) {
    // End time optional here (already inferred above if user left blank)
    // Nothing else required
}

// ===============================
// 🔹 FETCH PREVIOUS & NEXT TASK (FOR GAP DETECTION)
// ===============================
$nextTask = null;
$prevTask = null;

// Next task after start_time
$gapNextStmt = $conn->prepare("
    SELECT id, start_time, end_time
    FROM {$table}
    WHERE user_id = ? AND work_date = ?
      AND start_time > ?
    ORDER BY start_time ASC
    LIMIT 1
");
$gapNextStmt->bind_param("iss", $user_id, $date, $start_time);
$gapNextStmt->execute();
$nextTask = $gapNextStmt->get_result()->fetch_assoc();
$gapNextStmt->close();

// Previous task that ends on/before start_time
$gapPrevStmt = $conn->prepare("
    SELECT id, start_time, end_time
    FROM {$table}
    WHERE user_id = ? AND work_date = ?
      AND end_time <= ?
    ORDER BY end_time DESC
    LIMIT 1
");
$gapPrevStmt->bind_param("iss", $user_id, $date, $start_time);
$gapPrevStmt->execute();
$prevTask = $gapPrevStmt->get_result()->fetch_assoc();
$gapPrevStmt->close();

// ===============================
// ✅ TIME VALIDATION (only if end_time is not NULL)
// ===============================
if ($end_time !== null && strtotime($start_time) >= strtotime($end_time)) {
    echo json_encode(['success' => false, 'message' => 'Start time must be earlier than end time.']);
    exit;
}

// ===============================
// 🟨 CASE C: Gap Between Two Tasks
// start_time >= prev.end_time AND end_time <= next.start_time
// ===============================
if ($end_time !== null && $prevTask && $nextTask) {
    if (
        !empty($prevTask['end_time']) && !empty($nextTask['start_time']) &&
        strtotime($start_time) >= strtotime($prevTask['end_time']) &&
        strtotime($end_time)   <= strtotime($nextTask['start_time'])
    ) {
        $affected_log_id = (int)$prevTask['id']; // anchor to previous task
    }
}

// ===============================
// 🟩 CASE D: In-Between Task (INSIDE a container task)
// ===============================
if (empty($affected_log_id) && $end_time !== null) {

    $containerQuery = "
        SELECT id
        FROM {$table}
        WHERE user_id = ? AND work_date = ?
          AND start_time <= ?
          AND (end_time IS NULL OR end_time >= ?)
        LIMIT 1
    ";
    $checkStmt = $conn->prepare($containerQuery);
    $checkStmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $affected_log_id = (int)$checkResult->fetch_assoc()['id'];
    }
    $checkStmt->close();
}

// If still no affected log id and it's not end shift, reject
/*if (empty($affected_log_id) && strpos($task_text, 'end shift') === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Task does not fit inside an existing task or between two tasks.'
    ]);
    exit;
}*/

// Allow forgot-first-task inserts without affected_log_id
// Reject only if NOT forgot-first-task
if (
    empty($affected_log_id) &&
    !$isForgotFirstTask &&
    strpos($task_text, 'end shift') === false
) {
    echo json_encode([
        'success' => false,
        'message' => 'Task does not fit inside an existing task or between two tasks.'
    ]);
    exit;
}

// ===============================
// 6️⃣ FALLBACK CONTEXT (Optional safety, but keep)
// Only run if still empty AND not end shift AND not forgot-first.
// ===============================
if (empty($affected_log_id) && !$isForgotFirstTask && strpos($task_text, 'end shift') === false) {
    $findLogStmt = $conn->prepare("
        SELECT id 
        FROM {$table}
        WHERE user_id = ? AND work_date = ? 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, start_time, ?)) ASC 
        LIMIT 1
    ");
    $findLogStmt->bind_param("iss", $user_id, $date, $start_time);
    $findLogStmt->execute();
    $logRow = $findLogStmt->get_result()->fetch_assoc();
    if ($logRow) {
        $affected_log_id = (int)$logRow['id'];
    }
    $findLogStmt->close();
}

// ===============================
// 7️⃣ INSERT TASK REQUEST
// ===============================
/*
$stmt = $conn->prepare("
    INSERT INTO task_insertion_requests 
    (request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, reason, status, created_at, affected_log_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");
*/

$stmt = $conn->prepare("
   INSERT INTO task_insertion_requests
(request_uid, user_id, date, work_mode_id, task_description_id, start_time, end_time, end_date, reason, status, created_at, affected_log_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)
");

/*
$stmt->bind_param(
    "sisiisssi",
    $request_uid,
    $user_id,
    $date,
    $work_mode_id,
    $task_description_id,
    $start_time,
    $end_time,      // can be NULL (End Shift)
    $reason,
    $affected_log_id
);
*/

$stmt->bind_param(
    "sisiissssi",
    $request_uid,          // s
    $user_id,              // i
    $date,                 // s
    $work_mode_id,         // i
    $task_description_id,  // i
    $start_time,           // s (TIME as string)
    $end_time,             // s (TIME as string, can be NULL)
    $end_date,             // s (DATE as string, can be NULL)
    $reason,               // s
    $affected_log_id        // i
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task insertion request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit task insertion request.']);
}

$stmt->close();
$conn->close();
