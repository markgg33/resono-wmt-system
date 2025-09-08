<?php
session_start();
require_once "connection_db.php";
header('Content-Type: application/json');

// Ensure user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  echo json_encode(["status" => "error", "message" => "No user ID found"]);
  exit;
}

$query = "
  SELECT 
    tl.id,
    tl.date,
    TIME(tl.start_time) AS start_time,
    TIME(tl.end_time) AS end_time,
    tl.start_time AS full_start,
    tl.end_time AS full_end,
    tl.total_duration,
    tl.remarks,
    tl.volume_remark, -- 🔹 include this
    wm.name AS work_mode,
    td.description AS task_description
  FROM task_logs tl
  JOIN work_modes wm ON wm.id = tl.work_mode_id
  JOIN task_descriptions td ON td.id = tl.task_description_id
  WHERE tl.user_id = ?
  ORDER BY tl.date ASC, tl.id ASC
";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
  $logs[] = $row;
}

echo json_encode(["status" => "success", "logs" => $logs]);
