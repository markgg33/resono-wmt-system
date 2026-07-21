<?php

//WORKING VERSION
/*session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ✅ Get supervisor's assigned departments
$supervisorDeptIds = [];
if ($userRole === 'supervisor') {
    $deptQuery = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
    $deptQuery->bind_param("i", $userId);
    $deptQuery->execute();
    $resultDept = $deptQuery->get_result();
    while ($row = $resultDept->fetch_assoc()) {
        $supervisorDeptIds[] = intval($row['department_id']);
    }
    $deptQuery->close();
}

// ✅ Base WHERE clause
$whereClause = "WHERE da.status = 'Pending'";

// ===============================
// ROLE-BASED VISIBILITY RULES
// ===============================
if (in_array($userRole, ['hr', 'admin', 'executive'])) {
    // HR, admin, executive see all pending requests
    $whereClause .= "";
} elseif ($userRole === 'supervisor') {
    if (!empty($supervisorDeptIds)) {
        // Supervisors see:
        //  - Requests directly addressed to them, OR
        //  - Requests from users in their assigned departments
        $deptIdsStr = implode(',', $supervisorDeptIds);
        $whereClause .= " AND (
            da.recipient_id = " . intval($userId) . "
            OR ud.department_id IN ($deptIdsStr)
        )";
    } else {
        // Supervisor with no assigned departments → no requests
        $whereClause .= " AND 0";
    }
}

$query = "
  SELECT da.id, da.request_uid, da.field, da.old_value, da.new_value, da.status, da.reason, da.requested_at,
         da.processed_at,
         u.id AS requester_id,
         CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
         GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS requester_departments,
         tl.date, td.description AS task_description,
         r.id AS recipient_id,
         CONCAT(r.first_name, ' ', r.last_name) AS recipient_name,
         r.role AS recipient_role,
         p.id AS processed_by_id,
         CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
         p.role AS processed_by_role
  FROM dtr_amendments da
  JOIN users u ON da.user_id = u.id
  LEFT JOIN user_departments ud ON u.id = ud.user_id
  LEFT JOIN departments d ON ud.department_id = d.id
  JOIN task_logs tl ON da.log_id = tl.id
  JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users r ON da.recipient_id = r.id
  LEFT JOIN users p ON da.processed_by = p.id
  $whereClause
  GROUP BY da.id
  ORDER BY da.id DESC
";

$result = $conn->query($query);
$requests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "requests" => $requests
]);*/

//WITH TASK_LOGS_ARCHIVE LOGIC
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ✅ Get supervisor's assigned departments
$supervisorDeptIds = [];
if ($userRole === 'supervisor') {
    $deptQuery = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
    $deptQuery->bind_param("i", $userId);
    $deptQuery->execute();
    $resultDept = $deptQuery->get_result();
    while ($row = $resultDept->fetch_assoc()) {
        $supervisorDeptIds[] = intval($row['department_id']);
    }
    $deptQuery->close();
}

// ✅ Base WHERE clause
$whereClause = "WHERE da.status = 'Pending'";

// ===============================
// ROLE-BASED VISIBILITY RULES
// ===============================
if (in_array($userRole, ['hr', 'admin', 'executive'])) {
    // HR, admin, executive see all pending requests
    $whereClause .= "";
} elseif ($userRole === 'supervisor') {
    if (!empty($supervisorDeptIds)) {
        $deptIdsStr = implode(',', $supervisorDeptIds);
        $whereClause .= " AND (
            da.recipient_id = " . intval($userId) . "
            OR ud.department_id IN ($deptIdsStr)
        )";
    } else {
        $whereClause .= " AND 0";
    }
}

// ✅ Combine task_logs and task_logs_archive
// Use UNION ALL to get a unified view
$unionLogs = "
(
    SELECT id, user_id, task_description_id, date, start_time, end_time, total_duration, work_mode_id
    FROM task_logs
    UNION ALL
    SELECT id, user_id, task_description_id, date, start_time, end_time, total_duration, work_mode_id
    FROM task_logs_archive
) AS tl_all
";

$query = "
  SELECT 
    da.id, da.request_uid, da.field, da.old_value, da.new_value, da.status, da.reason, da.requested_at,
    da.processed_at,
    u.id AS requester_id,
    CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
    GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS requester_departments,
    tl_all.date,
    td.description AS task_description,
    r.id AS recipient_id,
    CONCAT(r.first_name, ' ', r.last_name) AS recipient_name,
    r.role AS recipient_role,
    p.id AS processed_by_id,
    CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
    p.role AS processed_by_role
  FROM dtr_amendments da
  JOIN users u ON da.user_id = u.id
  LEFT JOIN user_departments ud ON u.id = ud.user_id
  LEFT JOIN departments d ON ud.department_id = d.id
  LEFT JOIN $unionLogs ON da.log_id = tl_all.id
  LEFT JOIN task_descriptions td ON tl_all.task_description_id = td.id
  LEFT JOIN users r ON da.recipient_id = r.id
  LEFT JOIN users p ON da.processed_by = p.id
  $whereClause
  GROUP BY da.id
  ORDER BY da.id DESC
";

$result = $conn->query($query);
$requests = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "requests" => $requests
]);

