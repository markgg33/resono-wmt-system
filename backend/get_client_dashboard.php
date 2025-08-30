<?php
require_once "../connection_db.php";

$dept = isset($_GET['dept']) ? $_GET['dept'] : 'Web';

// Example query (adjust based on your actual DB schema)
$query = $conn->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SEC_TO_TIME(SUM(TIME_TO_SEC(total_duration))) as total_hours,
        COUNT(DISTINCT user_id) as unique_users
    FROM task_logs tl
    JOIN users u ON u.id = tl.user_id
    JOIN departments d ON d.id = u.department_id
    WHERE d.name = ?
");
$query->bind_param("s", $dept);
$query->execute();
$result = $query->get_result()->fetch_assoc();

// Example chart data: tasks by description
$chartQuery = $conn->prepare("
    SELECT td.name as task_name, COUNT(*) as task_count
    FROM task_logs tl
    JOIN task_descriptions td ON td.id = tl.task_description_id
    JOIN users u ON u.id = tl.user_id
    JOIN departments d ON d.id = u.department_id
    WHERE d.name = ?
    GROUP BY td.name
");
$chartQuery->bind_param("s", $dept);
$chartQuery->execute();
$chartRes = $chartQuery->get_result();

$labels = [];
$data = [];
while ($row = $chartRes->fetch_assoc()) {
    $labels[] = $row['task_name'];
    $data[] = $row['task_count'];
}

echo json_encode([
    "total_tasks" => $result['total_tasks'],
    "total_hours" => $result['total_hours'],
    "unique_users" => $result['unique_users'],
    "chart_labels" => $labels,
    "chart_data" => $data
]);
