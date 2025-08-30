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

// Pagination
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

// Count total requests filed by *me*
$countQuery = "
    SELECT COUNT(*) as total
    FROM dtr_amendments
    WHERE user_id = " . intval($userId);

$countResult = $conn->query($countQuery);
$totalRows   = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages  = ceil($totalRows / $limit);

// Main query → only requests I personally filed
$query = "
  SELECT da.id, da.request_uid, da.field, 
         CASE
             WHEN da.field = 'start_time' THEN tl.start_time
             WHEN da.field = 'end_time' THEN tl.end_time
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at,
         da.processed_at,
         CONCAT(reqUser.first_name, ' ', reqUser.last_name) AS requester_name,
         tl.date, tl.start_time, tl.end_time, tl.total_duration,
         td.description AS task_description,
         r.id AS recipient_id,
         CONCAT(r.first_name, ' ', r.last_name) AS recipient_name,
         r.role AS recipient_role,
         p.id AS processed_by_id,
         CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
         p.role AS processed_by_role
  FROM dtr_amendments da
  JOIN users reqUser ON da.user_id = reqUser.id
  JOIN task_logs tl ON da.log_id = tl.id
  LEFT JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users r ON da.recipient_id = r.id
  LEFT JOIN users p ON da.processed_by = p.id
  WHERE da.user_id = " . intval($userId) . "
  ORDER BY da.id DESC
  LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);

$requests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
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
