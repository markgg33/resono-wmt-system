<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

// Check role
$userRole = $_SESSION['role'] ?? null;
if (!in_array($userRole, ['admin', 'executive', 'hr'])) {
  echo json_encode(["status" => "error", "message" => "Unauthorized"]);
  exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total (only pending)
$countQuery = "SELECT COUNT(*) AS total FROM dtr_amendments WHERE status = 'pending'";
$countResult = $conn->query($countQuery);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Query with LIMIT/OFFSET (only pending)
$query = "
  SELECT da.id, da.request_uid, da.field, da.old_value, da.new_value, da.status, da.reason, da.requested_at,
         da.processed_by, da.processed_at,
         u.id AS requester_id,
         CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
         tl.date, tl.start_time, tl.end_time, tl.total_duration,
         td.description AS task_description
  FROM dtr_amendments da
  JOIN users u ON da.user_id = u.id
  JOIN task_logs tl ON da.log_id = tl.id
  LEFT JOIN task_descriptions td ON tl.task_description_id = td.id
  WHERE da.status = 'pending'
  ORDER BY da.id DESC
  LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);

$requests = [];
while ($row = $result->fetch_assoc()) {
  $requests[] = $row;
}

echo json_encode(["status" => "success", "requests" => $requests]);
