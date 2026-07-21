<?php
//WORKING VERSION
/*
require "../connection_db.php";

$deptId = intval($_GET['dept'] ?? 0);
$year   = intval($_GET['year'] ?? date("Y"));
$month  = intval($_GET['month'] ?? date("n"));

if (!$deptId) {
    echo json_encode(["success" => false, "message" => "No department"]);
    exit;
}

// Get department name
$deptName = "";
$deptStmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$deptStmt->bind_param("i", $deptId);
$deptStmt->execute();
$deptStmt->bind_result($deptName);
$deptStmt->fetch();
$deptStmt->close();

$sql = "
SELECT 
    td.description AS task_name,
    COUNT(*) AS task_count,
    ROUND(SUM(TIME_TO_SEC(t.total_duration)) / 3600, 2) AS total_hours
FROM (
    SELECT task_description_id, total_duration, date, user_id FROM task_logs
    UNION ALL
    SELECT task_description_id, total_duration, archived_month AS date, user_id FROM task_logs_archive
) t
INNER JOIN user_departments ud ON t.user_id = ud.user_id AND ud.is_primary = 1
LEFT JOIN task_descriptions td ON t.task_description_id = td.id
WHERE YEAR(t.date) = ? 
  AND MONTH(t.date) = ? 
  AND ud.department_id = ?
  AND td.work_mode_id <> 2  -- exclude Away-Time (non-billable)
GROUP BY td.description
ORDER BY total_hours DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $year, $month, $deptId);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];
$list   = [];

while ($row = $result->fetch_assoc()) {
    $task_name = $row['task_name'] ?? "Unspecified";
    $total_hours = round($row['total_hours'], 2);

    $labels[] = $task_name;
    $values[] = $total_hours; // ✅ fixed: use total_hours instead of task_count
    $list[]   = [
        "task_name"   => $task_name,
        "task_count"  => intval($row['task_count']),
        "total_hours" => $total_hours
    ];
}

echo json_encode([
    "success"  => true,
    "deptName" => $deptName ?: "Unknown Department",
    "labels"   => $labels,
    "values"   => $values,
    "list"     => $list
]);
*/

// TESTING
require "../connection_db.php";

$deptId = intval($_GET['dept'] ?? 0);
$year   = intval($_GET['year'] ?? date("Y"));
$month  = intval($_GET['month'] ?? date("n"));
$userId = intval($_GET['user_id'] ?? 0);

if (!$deptId) {
    echo json_encode(["success" => false, "message" => "No department"]);
    exit;
}

// Fetch department name
$deptName = "";
$deptStmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$deptStmt->bind_param("i", $deptId);
$deptStmt->execute();
$deptStmt->bind_result($deptName);
$deptStmt->fetch();
$deptStmt->close();

$employeeName = "";

if ($userId > 0) {

    $empStmt = $conn->prepare("
        SELECT CONCAT(first_name,' ',last_name)
        FROM users
        WHERE id = ?
    ");

    $empStmt->bind_param("i", $userId);
    $empStmt->execute();
    $empStmt->bind_result($employeeName);
    $empStmt->fetch();
    $empStmt->close();
}

$sql = "
SELECT
    td.description AS task_name,
    COUNT(*) AS task_count,
    ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600,2) AS total_hours

FROM (

    SELECT task_description_id,total_duration,date,user_id
    FROM task_logs

    UNION ALL

    SELECT task_description_id,total_duration,archived_month AS date,user_id
    FROM task_logs_archive

) t

INNER JOIN task_descriptions td
ON t.task_description_id = td.id

INNER JOIN department_work_modes dwm
ON dwm.work_mode_id = td.work_mode_id

WHERE YEAR(t.date)=?
AND MONTH(t.date)=?
AND dwm.department_id=?
AND td.work_mode_id<>2
";

if ($userId > 0) {

    $sql .= " AND t.user_id=? ";

}

$sql .= "
GROUP BY td.description
ORDER BY total_hours DESC
";

$stmt = $conn->prepare($sql);

if ($userId > 0) {

    $stmt->bind_param(
        "iiii",
        $year,
        $month,
        $deptId,
        $userId
    );
} else {

    $stmt->bind_param(
        "iii",
        $year,
        $month,
        $deptId
    );
}
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];
$list   = [];

while ($row = $result->fetch_assoc()) {
    $task_name = $row['task_name'] ?? "Unspecified";
    $total_hours = round($row['total_hours'], 2);

    $labels[] = $task_name;
    $values[] = $total_hours;
    $list[] = [
        "task_name"   => $task_name,
        "task_count"  => intval($row['task_count']),
        "total_hours" => $total_hours
    ];
}

echo json_encode([

    "success"       => true,

    "deptName"      => $deptName ?: "Unknown Department",

    "employeeName"  => $employeeName,

    "labels"        => $labels,

    "values"        => $values,

    "list"          => $list

]);
