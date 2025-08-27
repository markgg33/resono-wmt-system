<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  echo json_encode(["status" => "error", "message" => "Not logged in"]);
  exit;
}

$query = "
  SELECT da.id, da.request_uid, da.field, da.old_value, da.new_value, da.status, da.reason, da.requested_at,
         da.processed_at,
         tl.date, td.description AS task_description,
         u.id AS recipient_id,
         CONCAT(u.first_name, ' ', u.last_name) AS recipient_name,
         u.role AS recipient_role,
         p.id AS processed_by_id,
         CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
         p.role AS processed_by_role
  FROM dtr_amendments da
  JOIN task_logs tl ON da.log_id = tl.id
  JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users u ON da.recipient_id = u.id
  LEFT JOIN users p ON da.processed_by = p.id
  WHERE da.user_id = ?
  ORDER BY da.id DESC
";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
  $requests[] = $row;
}

echo json_encode(["status" => "success", "requests" => $requests]);
