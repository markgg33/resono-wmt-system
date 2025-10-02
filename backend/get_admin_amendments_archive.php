<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

// Check role
$userRole = $_SESSION['role'] ?? null;
if (!in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$countQuery = "
    SELECT COUNT(*) as total
    FROM dtr_amendments
    WHERE status IN ('approved', 'rejected')
";
$countResult = $conn->query($countQuery);
$totalRows = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit);

// Main query
$query = "
  SELECT da.id, da.request_uid, da.field, da.old_value, da.new_value, da.status,
         da.reason, da.requested_at, da.processed_at,
         tl.date, td.description AS task_description,
         CONCAT(reqUser.first_name, ' ', reqUser.last_name) AS requester_name,
         CONCAT(procUser.first_name, ' ', procUser.last_name) AS processed_by_name,
         procUser.role AS processed_by_role
  FROM dtr_amendments da
  JOIN task_logs tl ON da.log_id = tl.id
  JOIN task_descriptions td ON tl.task_description_id = td.id
  JOIN users reqUser ON da.user_id = reqUser.id
  LEFT JOIN users procUser ON da.processed_by = procUser.id
  WHERE da.status IN ('Approved', 'Rejected')
  ORDER BY da.processed_at DESC
  LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Database query failed: " . $conn->error
    ]);
    exit;
}

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode([
    "status" => "success",
    "requests" => $requests,
    "pagination" => [
        "currentPage" => $page,
        "totalPages" => $totalPages,
        "totalRows" => $totalRows
    ]
]);
