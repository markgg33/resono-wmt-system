<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole || !in_array($userRole, ['admin', 'executive', 'hr'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Base WHERE
$whereClause = "WHERE da.status = 'pending'"; // ✅ Only pending requests here

if ($userRole === 'hr') {
    // HR sees all pending requests
    $whereClause .= "";
} else {
    // Admin/executive only see pending requests addressed to them
    $whereClause .= " AND da.recipient_id = " . intval($userId);
}

$query = "
  SELECT da.id, da.request_uid, da.field, da.old_value, da.new_value, da.status, da.reason, da.requested_at,
         da.processed_at,
         u.id AS requester_id,
         CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
         tl.date, td.description AS task_description,
         r.id AS recipient_id,
         CONCAT(r.first_name, ' ', r.last_name) AS recipient_name,
         r.role AS recipient_role,
         p.id AS processed_by_id,
         CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
         p.role AS processed_by_role
  FROM dtr_amendments da
  JOIN users u ON da.user_id = u.id
  JOIN task_logs tl ON da.log_id = tl.id
  JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users r ON da.recipient_id = r.id
  LEFT JOIN users p ON da.processed_by = p.id
  $whereClause
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
