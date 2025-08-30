<?php
require_once "../connection_db.php";

$deptId = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 1;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$sql = "
    SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(total_duration))) AS total_hours
    FROM (
        SELECT total_duration, user_id, date FROM task_logs WHERE DATE_FORMAT(date, '%Y-%m') = ?
        UNION ALL
        SELECT total_duration, user_id, date FROM task_logs_archive WHERE DATE_FORMAT(date, '%Y-%m') = ?
    ) t
    INNER JOIN users u ON t.user_id = u.id
    WHERE u.department_id = ? AND t.total_duration IS NOT NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $month, $month, $deptId);
if (!$stmt->execute()) {
    echo json_encode(["error" => "Database query failed"]);
    exit;
}

$result = $stmt->get_result()->fetch_assoc();
echo json_encode([
    "total_hours" => $result['total_hours'] ?? "00:00:00"
]);
