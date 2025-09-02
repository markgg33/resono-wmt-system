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
$month  = isset($_GET['month']) ? (int)$_GET['month'] : 0;  // 1..12
$year   = isset($_GET['year'])  ? (int)$_GET['year']  : 0;

if ($deptId <= 0 || $month < 1 || $month > 12 || $year < 2000) {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

// Build first/last day of selected month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate   = date('Y-m-t', strtotime($startDate)); // last day of month

// Decide source table: current selected month -> live table only; otherwise archive only
$isSelectedCurrentMonth = ((int)date('Y') === $year) && ((int)date('n') === $month);
$table = $isSelectedCurrentMonth ? 'task_logs' : 'task_logs_archive';
$source = $isSelectedCurrentMonth ? 'live' : 'archive';

// NOTE: If your schema uses `start_time` instead of `date`, switch `t.date` to `t.start_time` below.
// We assume a DATE (or DATETIME) column named `date`, as used in your pie-chart query.
$sql = "
  SELECT 
    t.date AS task_date,
    COUNT(*) AS task_count
  FROM {$table} t
  INNER JOIN users u ON t.user_id = u.id
  WHERE u.department_id = ?
    AND t.date BETWEEN ? AND ?
  GROUP BY t.date
  ORDER BY t.date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $deptId, $startDate, $endDate);
$stmt->execute();
$res = $stmt->get_result();

// Put results into a hashmap keyed by date
$taskData = [];
while ($row = $res->fetch_assoc()) {
    $taskData[$row['task_date']] = (int)$row['task_count'];
}

// Generate every day in the month, fill 0s
$daily = [];
$period = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day') // inclusive end
);

foreach ($period as $d) {
    $day = $d->format('Y-m-d');
    $daily[] = [
        'task_date'  => $day,
        'task_count' => $taskData[$day] ?? 0
    ];
}

echo json_encode([
    'status' => 'success',
    'source' => $source,   // helpful for debugging (will say "live" in current month)
    'daily'  => $daily
]);
