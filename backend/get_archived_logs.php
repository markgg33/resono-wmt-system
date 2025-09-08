<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  echo json_encode(["status" => "error", "message" => "No user ID found"]);
  exit;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : 0;

if ($month < 1 || $month > 12 || $year < 2000) {
  echo json_encode(["status" => "error", "message" => "Invalid month/year"]);
  exit;
}

$firstOfMonth = sprintf('%04d-%02d-01', $year, $month);

$sql = "
  SELECT 
    tla.id,
    tla.date,
    TIME(tla.start_time) AS start_time,
    TIME(tla.end_time) AS end_time,
    tla.start_time AS full_start,
    tla.end_time AS full_end,
    tla.total_duration,
    tla.remarks,
    tla.volume_remark,         
    wm.name AS work_mode,
    td.description AS task_description
  FROM task_logs_archive tla
  JOIN work_modes wm ON wm.id = tla.work_mode_id
  JOIN task_descriptions td ON td.id = tla.task_description_id
  WHERE tla.user_id = ?
    AND tla.archived_month = ?
  ORDER BY tla.date ASC, tla.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $userId, $firstOfMonth);
$stmt->execute();
$res = $stmt->get_result();

$logs = [];
while ($row = $res->fetch_assoc()) {
  if (!empty($row['full_end'])) {
    $start = new DateTime($row['full_start']);
    $end = new DateTime($row['full_end']);
    $interval = $start->diff($end);
    $row['computed_duration'] = $interval->format('%H:%I');
  } else {
    $row['computed_duration'] = '--';
  }
  $logs[] = $row;
}

echo json_encode(["status" => "success", "logs" => $logs]);
