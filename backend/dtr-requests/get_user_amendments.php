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
  $id = $row['id'];

  if (!isset($requests[$id])) {
    $requests[$id] = [
      "id" => $row["id"],
      "request_uid" => $row["request_uid"],
      "field" => $row["field"],
      "task_description" => $row["task_description"],
      "date" => $row["date"],
      "reason" => $row["reason"],
      "status" => $row["status"],
      "requested_at" => $row["requested_at"],
      "processed_at" => $row["processed_at"],
      "recipient_id" => $row["recipient_id"],
      "recipient_name" => $row["recipient_name"],
      "recipient_role" => $row["recipient_role"],
      "processed_by_id" => $row["processed_by_id"],
      "processed_by_name" => $row["processed_by_name"],
      "processed_by_role" => $row["processed_by_role"],
      // Keep generic old/new values for table display
      "old_value" => $row["old_value"],
      "new_value" => $row["new_value"],
      // Add explicit fields for modal
      "old_start_time" => null,
      "new_start_time" => null,
      "old_end_time" => null,
      "new_end_time" => null,
      "old_date" => null,
      "new_date" => null
    ];
  }

  // Map field → explicit modal keys
  switch ($row['field']) {
    case "start_time":
      $requests[$id]["old_start_time"] = $row["old_value"];
      $requests[$id]["new_start_time"] = $row["new_value"];
      break;
    case "end_time":
      $requests[$id]["old_end_time"] = $row["old_value"];
      $requests[$id]["new_end_time"] = $row["new_value"];
      break;
    case "date":
      $requests[$id]["old_date"] = $row["old_value"];
      $requests[$id]["new_date"] = $row["new_value"];
      break;
  }
}

echo json_encode(["status" => "success", "requests" => array_values($requests)]);
