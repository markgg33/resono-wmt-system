<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ✅ Get supervisor's departments (multi-department)
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

// Base WHERE clause
$whereClause = "WHERE da.status = 'Pending'";

if ($userRole === 'hr') {
    // HR sees all pending requests
    $whereClause .= "";
} elseif ($userRole === 'supervisor') {
    // Supervisors only see requests if they are the recipient
    $whereClause .= " AND da.recipient_id = " . intval($userId);
} else {
    // Admin/executive only see requests directly addressed to them
    $whereClause .= " AND da.recipient_id = " . intval($userId);
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
]);
