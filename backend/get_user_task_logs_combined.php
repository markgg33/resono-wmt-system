<?php
/*
//WORKING VERSION OF VIEWING BOTH TASK_LOGS AND ARCHIVE
require 'connection_db.php';
session_start();
header('Content-Type: application/json');

/*$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_role = $_SESSION['role'] ?? '';
$canViewOthers = in_array($user_role, ['admin', 'hr', 'executive', 'supervisor']);

// 🔹 Determine which user's logs to fetch
if ($canViewOthers && isset($_GET['target_user_id']) && $_GET['target_user_id'] !== '') {
    $user_id = intval($_GET['target_user_id']); // ✅ selected user (tracker summary)
} else {
    $user_id = intval($_SESSION['user_id']); // ✅ self-view (default)
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
    exit;
}


// Optional date range
$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

// Build query dynamically
$filter = "";
$params = [];
$types  = "ii"; // first two are user_id twice (for union below)

if ($start_date && $end_date) {
    $filter = " AND t.date BETWEEN ? AND ? ";
    $params = [$user_id, $user_id, $start_date, $end_date];
    $types .= "ss"; // add two string params for date range
} else {
    $params = [$user_id, $user_id];
}

// Main combined query (active + archive)
$sql = "
SELECT 
    t.id,
    w.name AS work_mode,
    d.description AS task_description,
    t.date,
    t.start_time,
    t.end_time,
    t.total_duration,
    t.remarks,
    t.volume_remark,
    t.source
FROM (
    SELECT 
        id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks, volume_remark, 'active' AS source
    FROM task_logs
    WHERE user_id = ?
    UNION ALL
    SELECT 
        id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks, volume_remark, 'archive' AS source
    FROM task_logs_archive
    WHERE user_id = ?
) AS t
JOIN work_modes w ON t.work_mode_id = w.id
JOIN task_descriptions d ON t.task_description_id = d.id
" . ($filter ? "WHERE t.date BETWEEN ? AND ?" : "") . "
ORDER BY t.date ASC, t.start_time ASC
";

// Prepare statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

if (empty($logs)) {
    echo json_encode(['status' => 'success', 'logs' => []]);
} else {
    echo json_encode(['status' => 'success', 'logs' => $logs]);
}

$stmt->close();
$conn->close();


require 'connection_db.php';
session_start();
header('Content-Type: application/json');

$user_role = $_SESSION['role'] ?? '';
$canViewOthers = in_array($user_role, ['admin', 'hr', 'executive', 'supervisor']);

if ($canViewOthers && isset($_GET['target_user_id']) && $_GET['target_user_id'] !== '') {
    $user_id = intval($_GET['target_user_id']);
} else {
    $user_id = intval($_SESSION['user_id']);
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
    exit;
}

$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

$params = [$user_id, $user_id];
$types  = "ii";

$filter = "";
if ($start_date && $end_date) {
    $filter = "WHERE t.work_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$sql = "
SELECT 
    t.unified_id AS id,
    w.name AS work_mode,
    d.description AS task_description,
    t.date,
    t.work_date,
    t.call_time,
    t.start_time,
    t.end_time,
    t.total_duration,
    t.remarks,
    t.volume_remark,
    t.source
FROM (
    SELECT 
        id AS unified_id,
        id,
        work_mode_id,
        task_description_id,
        date,
        work_date,
        call_time,
        start_time,
        end_time,
        total_duration,
        remarks,
        volume_remark,
        'active' AS source
    FROM task_logs
    WHERE user_id = ?

    UNION ALL

    SELECT
        COALESCE(original_id, id) AS unified_id,
        id,
        work_mode_id,
        task_description_id,
        date,
        work_date,
        NULL AS call_time,
        start_time,
        end_time,
        total_duration,
        remarks,
        volume_remark,
        'archive' AS source
    FROM task_logs_archive
    WHERE user_id = ?
) AS t
JOIN work_modes w ON t.work_mode_id = w.id
JOIN task_descriptions d ON t.task_description_id = d.id
$filter
ORDER BY t.work_date ASC, t.start_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) $logs[] = $row;

echo json_encode(['status' => 'success', 'logs' => $logs]);

$stmt->close();
$conn->close();
*/

//RECENT WORKING VERSION
/*
require 'connection_db.php';
session_start();
header('Content-Type: application/json');

$user_role = $_SESSION['role'] ?? '';
$canViewOthers = in_array($user_role, ['admin', 'hr', 'executive', 'supervisor']);

if ($canViewOthers && isset($_GET['target_user_id']) && $_GET['target_user_id'] !== '') {
    $user_id = intval($_GET['target_user_id']);
} else {
    $user_id = intval($_SESSION['user_id']);
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
    exit;
}

$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

$params = [$user_id, $user_id];
$types  = "ii";

/*$filter = "";
if ($start_date && $end_date) {
    $filter = "WHERE t.work_date <= ? AND COALESCE(t.end_date, t.work_date) >= ?";
    $params[] = $end_date;
    $params[] = $start_date;
    $types .= "ss";
}

$overlap = ($_GET['overlap'] ?? '0') === '1';

$filter = "";
if ($start_date && $end_date) {
    if ($overlap) {
        $filter = "WHERE t.work_date <= ? AND COALESCE(t.end_date, t.work_date) >= ?";
        $params[] = $end_date;
        $params[] = $start_date;
        $types .= "ss";
    } else {
        // ✅ strict: only rows whose work_date is in the requested window
        $filter = "WHERE t.work_date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
}

$sql = "
SELECT 
    t.unified_id AS id,
    w.name AS work_mode,
    d.description AS task_description,
    t.date,
    t.work_date,
    t.end_date,
    t.call_time,
    t.start_time,
    t.end_time,
    t.total_duration,
    t.remarks,
    t.volume_remark,
    t.source
FROM (
    SELECT 
        id AS unified_id,
        id,
        work_mode_id,
        task_description_id,
        date,
        work_date,
        end_date,
        call_time,
        start_time,
        end_time,
        total_duration,
        remarks,
        volume_remark,
        'active' AS source
    FROM task_logs
    WHERE user_id = ?

    UNION ALL

    SELECT
        COALESCE(original_id, id) AS unified_id,
        id,
        work_mode_id,
        task_description_id,
        date,
        work_date,
        end_date,
        NULL AS call_time,
        start_time,
        end_time,
        total_duration,
        remarks,
        volume_remark,
        'archive' AS source
    FROM task_logs_archive
    WHERE user_id = ?
) AS t
JOIN work_modes w ON t.work_mode_id = w.id
JOIN task_descriptions d ON t.task_description_id = d.id
$filter
ORDER BY
  t.work_date ASC,
  CASE WHEN LOWER(d.description) LIKE '%end shift%' THEN 1 ELSE 0 END ASC,
  (TIME_TO_SEC(t.start_time) + CASE WHEN COALESCE(t.end_date, t.date, t.work_date) > t.work_date THEN 86400 ELSE 0 END) ASC,
  t.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) $logs[] = $row;

echo json_encode(['status' => 'success', 'logs' => $logs]);

$stmt->close();
$conn->close();
*/

require 'connection_db.php';
session_start();
header('Content-Type: application/json');

$user_role = $_SESSION['role'] ?? '';
$canViewOthers = in_array($user_role, ['admin', 'hr', 'executive', 'supervisor']);

if ($canViewOthers && isset($_GET['target_user_id']) && $_GET['target_user_id'] !== '') {
    $user_id = intval($_GET['target_user_id']);
} else {
    $user_id = intval($_SESSION['user_id']);
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
    exit;
}

$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

$params = [$user_id, $user_id];
$types  = "ii";

$overlap = ($_GET['overlap'] ?? '0') === '1';

$filter = "";
if ($start_date && $end_date) {
    if ($overlap) {
        $filter = "WHERE t.work_date <= ? AND COALESCE(t.end_date, t.work_date) >= ?";
        $params[] = $end_date;
        $params[] = $start_date;
        $types .= "ss";
    } else {
        $filter = "WHERE t.work_date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
}

$sql = "
SELECT 
    t.unified_id AS id,
    w.name AS work_mode,
    d.description AS task_description,
    t.date,
    t.work_date,
    t.end_date,
    TIME_FORMAT(t.call_time, '%H:%i:00') AS call_time,
    TIME_FORMAT(t.start_time, '%H:%i:00') AS start_time,
    TIME_FORMAT(t.end_time, '%H:%i:00') AS end_time,
    TIME_FORMAT(t.total_duration, '%H:%i:00') AS total_duration,
    t.remarks,
    t.volume_remark,
    t.source
FROM (
    SELECT 
        id AS unified_id,
        id,
        work_mode_id,
        task_description_id,
        date,
        work_date,
        end_date,
        call_time,
        start_time,
        end_time,
        total_duration,
        remarks,
        volume_remark,
        'active' AS source
    FROM task_logs
    WHERE user_id = ?

    UNION ALL

    SELECT
        COALESCE(original_id, id) AS unified_id,
        id,
        work_mode_id,
        task_description_id,
        date,
        work_date,
        end_date,
        NULL AS call_time,
        start_time,
        end_time,
        total_duration,
        remarks,
        volume_remark,
        'archive' AS source
    FROM task_logs_archive
    WHERE user_id = ?
) AS t
JOIN work_modes w ON t.work_mode_id = w.id
JOIN task_descriptions d ON t.task_description_id = d.id
$filter
ORDER BY
  t.work_date ASC,
  CASE WHEN LOWER(d.description) LIKE '%end shift%' THEN 1 ELSE 0 END ASC,
  (TIME_TO_SEC(TIME_FORMAT(t.start_time, '%H:%i:00')) + CASE WHEN COALESCE(t.end_date, t.date, t.work_date) > t.work_date THEN 86400 ELSE 0 END) ASC,
  t.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) $logs[] = $row;

echo json_encode(['status' => 'success', 'logs' => $logs]);

$stmt->close();
$conn->close();
