<?php
require 'connection_db.php';
session_start();
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

// Accept POST JSON or fallback to GET/session
$user_id = $input['user_id'] ?? ($_GET['user_id'] ?? $_SESSION['user_id'] ?? null);
$start_date = $input['start_date'] ?? ($_GET['start'] ?? null);
$end_date = $input['end_date'] ?? ($_GET['end'] ?? null);

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// Build query — use tl.date (date column) and correct column names 
$sql = "SELECT  
            tl.id,
            DATE_FORMAT(tl.date, '%b %e, %Y') AS date,
            wm.name AS work_mode,
            td.description AS task,
            TIME_FORMAT(tl.start_time, '%H:%i') AS start_time,
            TIME_FORMAT(tl.end_time, '%H:%i') AS end_time,
            IFNULL(TIME_FORMAT(tl.total_duration, '%H:%i'), '--') AS total_duration,
            IFNULL(tl.remarks, '--') AS remarks,
            IFNULL(tl.volume_remark, '--') AS volume
        FROM task_logs tl
        LEFT JOIN work_modes wm ON tl.work_mode_id = wm.id
        LEFT JOIN task_descriptions td ON tl.task_description_id = td.id
        WHERE tl.user_id = ?";

$params = [$user_id];
$types = "i";

if (!empty($start_date)) {
    $sql .= " AND tl.date >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $sql .= " AND tl.date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$sql .= " ORDER BY tl.date ASC, tl.start_time ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB prepare failed', 'db_error' => $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'status' => 'success',
    'logs' => $logs
]);
