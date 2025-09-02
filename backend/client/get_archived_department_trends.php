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

// Start and end of the selected month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate   = date("Y-m-t", strtotime($startDate)); // last day of month

// Query: daily task counts
$sql = "
  SELECT 
    DATE(tla.start_time) AS task_date,
    COUNT(*) AS task_count
  FROM task_logs_archive tla
  INNER JOIN users u ON tla.user_id = u.id
  WHERE u.department_id = ?
    AND DATE(tla.start_time) BETWEEN ? AND ?
  GROUP BY task_date
  ORDER BY task_date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $deptId, $startDate, $endDate);
$stmt->execute();
$res = $stmt->get_result();

// Put results in associative array keyed by date
$taskData = [];
while ($row = $res->fetch_assoc()) {
    $taskData[$row['task_date']] = (int)$row['task_count'];
}

// Generate all days of the month, filling in 0 if no tasks
$daily = [];
$period = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day') // inclusive end
);

foreach ($period as $date) {
    $d = $date->format("Y-m-d");
    $daily[] = [
        "task_date" => $d,
        "task_count" => $taskData[$d] ?? 0
    ];
}

echo json_encode(["status" => "success", "daily" => $daily]);
