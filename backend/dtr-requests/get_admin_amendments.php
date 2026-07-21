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
             WHEN da.field = 'end_time'   THEN tl.end_time
             WHEN da.field = 'date'       THEN tl.date
             ELSE da.old_value
         END AS old_value,
         da.new_value, da.status, da.reason, da.requested_at,
         da.processed_by, da.processed_at,
         u.id AS requester_id,
         CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
         tl.date AS original_date, tl.start_time, tl.end_time, tl.total_duration,
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

    $requests[] = [
      "id" => $row["id"],
      "request_uid" => $row["request_uid"],
      "field" => $row["field"],
      "status" => $row["status"],
      "reason" => $row["reason"],
      "requested_at" => $row["requested_at"],
      "processed_at" => $row["processed_at"],
      "requester_id" => $row["requester_id"],
      "requester_name" => $row["requester_name"],
      "recipient_id" => $row["recipient_id"],
      "recipient_name" => $row["recipient_name"],
      "recipient_role" => $row["recipient_role"],
      "task_description" => $row["task_description"],

      // for table
      "old_value" => $row["old_value"],
      "new_value" => $row["new_value"],

      // explicit values for modal
      "old_start_time" => $row["start_time"],
      "new_start_time" => ($row['field'] === 'start_time') ? $row["new_value"] : null,
      "old_end_time"   => $row["end_time"],
      "new_end_time"   => ($row['field'] === 'end_time') ? $row["new_value"] : null,
      "date"          => $row["original_date"],
      "old_date"       => $row["original_date"],
      "new_date"       => ($row['field'] === 'date') ? $row["new_value"] : null,
    ];
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

// Pagination setup
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

// 🧩 Role-based filter
$whereClause = "WHERE da.status = 'Pending'";
if ($userRole !== 'hr') {
    $whereClause .= " AND (da.recipient_id = " . intval($userId) . " OR da.user_id = " . intval($userId) . ")";
}

// 🧮 Count total for pagination
$countQuery = "SELECT COUNT(*) AS total FROM dtr_amendments da $whereClause";
$countResult = $conn->query($countQuery);
$totalRows   = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages  = ceil($totalRows / $limit);

// 🧠 ARCHIVE-AWARE MAIN QUERY
$query = "
    SELECT 
        da.id,
        da.request_uid,
        da.field,
        da.old_value,
        da.new_value,
        da.status,
        da.reason,
        da.requested_at,
        da.processed_by,
        da.processed_at,

        -- requester
        u.id AS requester_id,
        CONCAT(u.first_name, ' ', u.last_name) AS requester_name,

        -- task details (from main or archive)
        COALESCE(tl_main.date, tl_archive.date) AS original_date,
        COALESCE(tl_main.start_time, tl_archive.start_time) AS start_time,
        COALESCE(tl_main.end_time, tl_archive.end_time) AS end_time,
        COALESCE(tl_main.total_duration, tl_archive.total_duration) AS total_duration,

        -- task description
        td.description AS task_description,

        -- recipient info
        r.id AS recipient_id,
        CONCAT(r.first_name, ' ', r.last_name) AS recipient_name,
        r.role AS recipient_role,

        -- identify source table
        CASE 
            WHEN tl_main.id IS NOT NULL THEN 'main'
            WHEN tl_archive.id IS NOT NULL THEN 'archive'
            ELSE 'unknown'
        END AS source_table
    FROM dtr_amendments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
    LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.id
    LEFT JOIN task_descriptions td 
        ON (td.id = tl_main.task_description_id OR td.id = tl_archive.task_description_id)
    LEFT JOIN users r ON da.recipient_id = r.id
    $whereClause
    ORDER BY da.id DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);
$requests = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // 🚫 Skip self-directed requests
        if ($row['requester_id'] == $row['recipient_id']) continue;

        $requests[] = [
            "id"              => $row["id"],
            "request_uid"     => $row["request_uid"],
            "field"           => $row["field"],
            "status"          => $row["status"],
            "reason"          => $row["reason"],
            "requested_at"    => $row["requested_at"],
            "processed_at"    => $row["processed_at"],
            "requester_id"    => $row["requester_id"],
            "requester_name"  => $row["requester_name"],
            "recipient_id"    => $row["recipient_id"],
            "recipient_name"  => $row["recipient_name"],
            "recipient_role"  => $row["recipient_role"],
            "task_description"=> $row["task_description"],
            "source_table"    => $row["source_table"],

            // for table display
            "old_value"       => $row["old_value"],
            "new_value"       => $row["new_value"],

            // for modal
            "old_start_time"  => $row["start_time"],
            "new_start_time"  => ($row['field'] === 'start_time') ? $row["new_value"] : null,
            "old_end_time"    => $row["end_time"],
            "new_end_time"    => ($row['field'] === 'end_time') ? $row["new_value"] : null,
            "date"            => $row["original_date"],
            "old_date"        => $row["original_date"],
            "new_date"        => ($row['field'] === 'date') ? $row["new_value"] : null,
        ];
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

$conn->close();
?>
