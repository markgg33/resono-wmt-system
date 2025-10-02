<?php
require "../connection_db.php";

$deptId = intval($_GET['dept'] ?? 0);
$year   = intval($_GET['year'] ?? date("Y"));
$month  = intval($_GET['month'] ?? date("n"));

if (!$deptId) {
    echo json_encode(["success" => false, "message" => "No department"]);
    exit;
}

$sql = "
SELECT td.description AS task_name,
       COUNT(*) AS task_count,
       ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600,2) AS total_hours
FROM (
    SELECT task_description_id, total_duration, date, user_id FROM task_logs
    UNION ALL
    SELECT task_description_id, total_duration, archived_month AS date, user_id FROM task_logs_archive
) t
INNER JOIN user_departments ud ON t.user_id = ud.user_id AND ud.is_primary = 1
LEFT JOIN task_descriptions td ON t.task_description_id = td.id
WHERE YEAR(t.date) = ? AND MONTH(t.date) = ? AND ud.department_id = ?
GROUP BY td.description
ORDER BY task_count DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $year, $month, $deptId);
$stmt->execute();
$result = $stmt->get_result();

$labels = $values = $list = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['task_name'] ?? "Unspecified";
    $values[] = intval($row['task_count']);
    $list[]   = $row;
}

echo json_encode([
    "success" => true,
    "labels"  => $labels,
    "values"  => $values,
    "list"    => $list
]);
