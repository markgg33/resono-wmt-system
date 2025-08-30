<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "No user ID found"]);
    exit;
}

$deptId = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;
$month  = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year   = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if ($deptId <= 0 || $month < 1 || $month > 12 || $year < 2000) {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

// Format archived_month as YYYY-MM-01
$archivedMonth = sprintf('%04d-%02d-01', $year, $month);

// Query archived logs for the department
$sql = "
  SELECT 
    td.description AS task_name,
    COUNT(*) AS task_count
  FROM task_logs_archive tla
  INNER JOIN users u ON tla.user_id = u.id
  INNER JOIN task_descriptions td ON tla.task_description_id = td.id
  WHERE u.department_id = ?
    AND tla.archived_month = ?
  GROUP BY td.description
  ORDER BY task_count DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $deptId, $archivedMonth);
$stmt->execute();
$res = $stmt->get_result();

$tasks = [];
while ($row = $res->fetch_assoc()) {
    $tasks[] = $row;
}

if (empty($tasks)) {
    echo json_encode(["status" => "empty", "message" => "No data for this month"]);
    exit;
}

echo json_encode(["status" => "success", "tasks" => $tasks]);
