<?php
require_once "../connection_db.php";
header("Content-Type: application/json");

$year   = isset($_GET['year']) ? intval($_GET['year']) : date("Y");
$deptId = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? intval($_GET['dept_id']) : null;

$params = [$year, $year];
$types  = "ii";

$deptConditionLogs    = "";
$deptConditionArchive = "";

if ($deptId) {
    $deptConditionLogs    = " AND u.department_id = ? ";
    $deptConditionArchive = " AND t.department_id = ? ";
    $params[] = $deptId;
    $params[] = $deptId;
    $types   .= "ii";
}

$sql = "
    SELECT MONTH(t.date) AS month,
           d.name AS department,
           ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600, 2) AS total_hours,
           COUNT(t.id) AS task_count
    FROM task_logs t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE YEAR(t.date) = ? $deptConditionLogs
    GROUP BY MONTH(t.date), d.name

    UNION ALL

    SELECT MONTH(STR_TO_DATE(CONCAT(t.archived_month, '-01'), '%Y-%m-%d')) AS month,
           d.name AS department,
           ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600, 2) AS total_hours,
           COUNT(t.id) AS task_count
    FROM task_logs_archive t
    LEFT JOIN departments d ON t.department_id = d.id
    WHERE YEAR(STR_TO_DATE(CONCAT(t.archived_month, '-01'), '%Y-%m-%d')) = ? $deptConditionArchive
    GROUP BY MONTH(STR_TO_DATE(CONCAT(t.archived_month, '-01'), '%Y-%m-%d')), d.name
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Step 1: Collect real data
$raw = [];
while ($row = $result->fetch_assoc()) {
    $m = intval($row['month']);
    $dept = $row['department'] ?? "Unassigned";

    if (!isset($raw[$m])) {
        $raw[$m] = [];
    }
    $raw[$m][$dept] = [
        "hours" => floatval($row['total_hours']),
        "count" => intval($row['task_count'])
    ];
}

// Step 2: Fill all months Jan-Dec (1–12)
$data = [];
for ($m = 1; $m <= 12; $m++) {
    if (isset($raw[$m])) {
        $data[$m] = $raw[$m];
    } else {
        $data[$m] = []; // no data → empty
    }
}

// Step 3: Prepare labels as month names (IMPORTANT!)
$labels = [];
$values = [];
for ($m = 1; $m <= 12; $m++) {
    $labels[] = date("F", mktime(0, 0, 0, $m, 1)); // e.g., "January"
    
    $monthData = $data[$m] ?? [];
    $total = 0;
    foreach ($monthData as $dept => $info) {
        $total += $info['hours']; // sum all departments
    }
    $values[] = $total;
}

echo json_encode([
    "labels" => $labels,
    "values" => $values
]);

