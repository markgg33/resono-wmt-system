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

// Pagination
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

// WHERE clause based on role
$whereClause = "WHERE da.status = 'Pending'";
if ($userRole !== 'hr') {
  $whereClause .= " AND (da.recipient_id = " . intval($userId) . " OR da.user_id = " . intval($userId) . ")";
}


// Count total
$countQuery = "SELECT COUNT(*) AS total FROM dtr_amendments da $whereClause";
$countResult = $conn->query($countQuery);
$totalRows   = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages  = ceil($totalRows / $limit);

// Main query
$query = "
  SELECT da.id, da.request_uid, da.field, 
         CASE
             WHEN da.field = 'start_time' THEN tl.start_time
             WHEN da.field = 'end_time' THEN tl.end_time
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at,
         da.processed_by, da.processed_at,
         u.id AS requester_id,
         CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
         tl.date, tl.start_time, tl.end_time, tl.total_duration,
         td.description AS task_description,
         r.id AS recipient_id,
         CONCAT(r.first_name, ' ', r.last_name) AS recipient_name,
         r.role AS recipient_role
  FROM dtr_amendments da
  JOIN users u ON da.user_id = u.id
  JOIN task_logs tl ON da.log_id = tl.id
  LEFT JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users r ON da.recipient_id = r.id
  $whereClause
  ORDER BY da.id DESC
  LIMIT $limit OFFSET $offset
";


$result = $conn->query($query);

$requests = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    if ($row['requester_id'] == $row['recipient_id']) {
      continue;
    }
    $requests[] = $row;
  }
}

echo json_encode([
  "status"     => "success",
  "requests"   => $requests,
  "pagination" => [
    "currentPage" => $page,
    "totalPages"  => $totalPages,
    "totalRows"   => $totalRows
  ]
]);
