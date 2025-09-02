<?php
require_once "../connection_db.php";

$deptId = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 1;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$start = $month . "-01";
$end = date("Y-m-t", strtotime($start));

$sql = "
    SELECT td.description AS task_name, COUNT(*) AS task_count
    FROM (
        SELECT user_id, task_description_id, date FROM task_logs WHERE date BETWEEN ? AND ?
        UNION ALL
        SELECT user_id, task_description_id, date FROM task_logs_archive WHERE date BETWEEN ? AND ?
    ) t
    INNER JOIN users u ON t.user_id = u.id
    INNER JOIN task_descriptions td ON t.task_description_id = td.id
    WHERE u.department_id = ?
      AND DATE_FORMAT(t.date, '%Y-%m') = ?
    GROUP BY td.description
    ORDER BY task_count DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssisi", $start, $end, $start, $end, $deptId, $month);
if (!$stmt->execute()) {
    echo json_encode(["error" => "Database query failed"]);
    exit;
}

$res = $stmt->get_result();
$tasks = [];
while ($row = $res->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);
