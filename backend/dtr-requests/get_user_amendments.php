<?php

//WORKING VERSION

/*session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  echo json_encode(["status" => "error", "message" => "Not logged in"]);
  exit;
}

$query = "
  SELECT da.id, da.request_uid, da.field, 
         CASE
             WHEN da.field = 'start_time' THEN tl.start_time
             WHEN da.field = 'end_time' THEN tl.end_time
             WHEN da.field = 'date' THEN tl.date
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at,
         da.processed_at,
         tl.date AS original_date, tl.start_time, tl.end_time,
         td.description AS task_description,
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
      "date" => $row["original_date"], // always original log date
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

      // For table display
      "old_value" => $row["old_value"],
      "new_value" => $row["new_value"],

      // Explicit values for modal
      "old_start_time" => $row["start_time"],
      "new_start_time" => ($row['field'] === 'start_time') ? $row["new_value"] : null,
      "old_end_time"   => $row["end_time"],
      "new_end_time"   => ($row['field'] === 'end_time') ? $row["new_value"] : null,
      "old_date"       => $row["original_date"],
      "new_date"       => ($row['field'] === 'date') ? $row["new_value"] : null
    ];
  }
}

echo json_encode(["status" => "success", "requests" => array_values($requests)]);*/

//WORKING V2
/*session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  echo json_encode(["status" => "error", "message" => "Not logged in"]);
  exit;
}

// Unified query for both active and archived logs
$query = "
  SELECT da.id, da.request_uid, da.field,
         CASE
             WHEN da.field = 'start_time' THEN tl.start_time
             WHEN da.field = 'end_time' THEN tl.end_time
             WHEN da.field = 'date' THEN tl.date
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at, da.processed_at,
         tl.date AS original_date, tl.start_time, tl.end_time,
         td.description AS task_description,
         u.id AS recipient_id, CONCAT(u.first_name, ' ', u.last_name) AS recipient_name, u.role AS recipient_role,
         p.id AS processed_by_id, CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name, p.role AS processed_by_role,
         'active' AS source
  FROM dtr_amendments da
  JOIN task_logs tl ON da.log_id = tl.id
  LEFT JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users u ON da.recipient_id = u.id
  LEFT JOIN users p ON da.processed_by = p.id
  WHERE da.user_id = ?

  UNION ALL

  SELECT da.id, da.request_uid, da.field,
         CASE
             WHEN da.field = 'start_time' THEN tla.start_time
             WHEN da.field = 'end_time' THEN tla.end_time
             WHEN da.field = 'date' THEN tla.date
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at, da.processed_at,
         tla.date AS original_date, tla.start_time, tla.end_time,
         td.description AS task_description,
         u.id AS recipient_id, CONCAT(u.first_name, ' ', u.last_name) AS recipient_name, u.role AS recipient_role,
         p.id AS processed_by_id, CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name, p.role AS processed_by_role,
         'archive' AS source
  FROM dtr_amendments da
  JOIN task_logs_archive tla ON da.log_id = tla.id
  LEFT JOIN task_descriptions td ON tla.task_description_id = td.id
  LEFT JOIN users u ON da.recipient_id = u.id
  LEFT JOIN users p ON da.processed_by = p.id
  WHERE da.user_id = ?

  ORDER BY id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $userId, $userId);
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
      "date" => $row["original_date"], // always original log date
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
      "source" => $row["source"], // tells if active or archive

      // For table display
      "old_value" => $row["old_value"],
      "new_value" => $row["new_value"],

      // Explicit values for modal
      "old_start_time" => $row["start_time"],
      "new_start_time" => ($row['field'] === 'start_time') ? $row["new_value"] : null,
      "old_end_time"   => $row["end_time"],
      "new_end_time"   => ($row['field'] === 'end_time') ? $row["new_value"] : null,
      "old_date"       => $row["original_date"],
      "new_date"       => ($row['field'] === 'date') ? $row["new_value"] : null
    ];
  }
}

echo json_encode(["status" => "success", "requests" => array_values($requests)]);*/

session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  echo json_encode(["status" => "error", "message" => "Not logged in"]);
  exit;
}

// --- PAGINATION PARAMETERS ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// --- TOTAL COUNT ---
$countSql = "SELECT COUNT(*) AS total FROM dtr_amendments WHERE user_id = ?";
$stmtCount = $conn->prepare($countSql);
$stmtCount->bind_param("i", $userId);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;

// --- MAIN QUERY ---
$query = "
  SELECT da.id, da.request_uid, da.field,
         CASE
             WHEN da.field = 'start_time' THEN tl.start_time
             WHEN da.field = 'end_time' THEN tl.end_time
             WHEN da.field = 'date' THEN tl.date
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at, da.processed_at,
         tl.date AS original_date, tl.start_time, tl.end_time,
         td.description AS task_description,
         u.id AS recipient_id, CONCAT(u.first_name, ' ', u.last_name) AS recipient_name, u.role AS recipient_role,
         p.id AS processed_by_id, CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name, p.role AS processed_by_role,
         'active' AS source
  FROM dtr_amendments da
  JOIN task_logs tl ON da.log_id = tl.id
  LEFT JOIN task_descriptions td ON tl.task_description_id = td.id
  LEFT JOIN users u ON da.recipient_id = u.id
  LEFT JOIN users p ON da.processed_by = p.id
  WHERE da.user_id = ?

  UNION ALL

  SELECT da.id, da.request_uid, da.field,
         CASE
             WHEN da.field = 'start_time' THEN tla.start_time
             WHEN da.field = 'end_time' THEN tla.end_time
             WHEN da.field = 'date' THEN tla.date
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at, da.processed_at,
         tla.date AS original_date, tla.start_time, tla.end_time,
         td.description AS task_description,
         u.id AS recipient_id, CONCAT(u.first_name, ' ', u.last_name) AS recipient_name, u.role AS recipient_role,
         p.id AS processed_by_id, CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name, p.role AS processed_by_role,
         'archive' AS source
  FROM dtr_amendments da
  JOIN task_logs_archive tla ON da.log_id = tla.id
  LEFT JOIN task_descriptions td ON tla.task_description_id = td.id
  LEFT JOIN users u ON da.recipient_id = u.id
  LEFT JOIN users p ON da.processed_by = p.id
  WHERE da.user_id = ?

  ORDER BY id DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $userId, $userId, $limit, $offset);
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
      "date" => $row["original_date"],
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
      "source" => $row["source"],

      "old_value" => $row["old_value"],
      "new_value" => $row["new_value"],
    ];
  }
}

echo json_encode([
  "status" => "success",
  "requests" => array_values($requests),
  "total" => intval($total),
  "page" => intval($page),
  "limit" => intval($limit),
  "total_pages" => ceil($total / $limit)
]);
?>


