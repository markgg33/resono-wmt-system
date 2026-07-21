<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

$page   = intval($_GET['page'] ?? 1);
$limit  = intval($_GET['limit'] ?? 10);
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$offset = ($page - 1) * $limit;

/* 🔹 NEW: FILTER PARAMS */
$leaveFrom   = $_GET['leave_from'] ?? '';
$leaveTo     = $_GET['leave_to'] ?? '';
$departmentId = $_GET['department_id'] ?? '';
$employeeId   = $_GET['employee_id'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

/* -----------------------------
   BASE SELECT
----------------------------- */

//removed lr.leave_type replaced by SELECT (FROM ELSE MAX TO ELSE LOWER)
$selectSql = "
SELECT 
    lr.id,
    lr.created_at,
    lr.start_date,
    lr.end_date,
    (
  SELECT 
    CASE 
      WHEN COUNT(DISTINCT lrd.leave_type) > 1 THEN 'Mixed'
      ELSE TRIM(MAX(lrd.leave_type))
    END
  FROM leave_request_dates lrd
  WHERE lrd.leave_request_id = lr.id
) AS leave_type,
    CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
    lr.status,
    CONCAT(cb.first_name, ' ', cb.last_name) AS checked_by_name,
    CASE 
    WHEN lr.status = 'Cancelled' THEN NULL
    ELSE lr.leave_payment_status
END AS leave_payment_status
FROM leave_requests lr
JOIN users u ON u.id = lr.user_id
LEFT JOIN users cb ON cb.id = lr.checked_by
";

/* -----------------------------
   WHERE CLAUSE BASED ON ROLE
----------------------------- */
$where  = [];
$params = [];
$types  = "";

/**
 * REGULAR USER → OWN ONLY
 */
if ($user_role === 'user') {

    $where[]  = "lr.user_id = ?";
    $params[] = $user_id;
    $types   .= "i";
}

/**
 * SUPERVISOR → OWN + DEPARTMENT + RECIPIENT
 */
elseif ($user_role === 'supervisor') {

    $where[] = "(
        lr.user_id = ?
        OR lr.recipient_id = ?
        OR EXISTS (
            SELECT 1
            FROM user_departments ud1
            JOIN user_departments ud2
              ON ud1.department_id = ud2.department_id
            WHERE ud1.user_id = lr.user_id
              AND ud2.user_id = ?
        )
    )";

    $params[] = $user_id;
    $params[] = $user_id;
    $params[] = $user_id;
    $types   .= "iii";
}

/**
 * ADMIN / HR / EXECUTIVE → SEE ALL
 */
elseif (in_array($user_role, ['admin', 'hr', 'executive'])) {
    // no base filter
}

/**
 * SAFETY FALLBACK
 */
else {
    $where[]  = "lr.user_id = ?";
    $params[] = $user_id;
    $types   .= "i";
}

/* -----------------------------
   OPTIONAL FILTERS
----------------------------- */

// 🔍 Search
if ($search !== "") {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR lr.leave_type LIKE ?)";
    $types  .= "sss";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 📌 Status
if ($status !== "") {
    $where[]  = "lr.status = ?";
    $types   .= "s";
    $params[] = $status;
}

// 📅 Date From (created_at)
if ($leaveFrom !== "") {
    $where[]  = "DATE(lr.created_at) >= ?";
    $types   .= "s";
    $params[] = $leaveFrom;
}

// 📅 Date To (created_at)
if ($leaveTo !== "") {
    $where[]  = "DATE(lr.created_at) <= ?";
    $types   .= "s";
    $params[] = $leaveTo;
}

// 🏢 Department filter (multi-department safe)
if ($departmentId !== "") {
    $where[] = "
        EXISTS (
            SELECT 1
            FROM user_departments ud
            WHERE ud.user_id = lr.user_id
              AND ud.department_id = ?
        )
    ";
    $types   .= "i";
    $params[] = intval($departmentId);
}

// 👤 Employee filter
if ($employeeId !== "") {
    $where[]  = "lr.user_id = ?";
    $types   .= "i";
    $params[] = intval($employeeId);
}

$whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";

/* -----------------------------
   COUNT TOTAL
----------------------------- */
$countSql = "
    SELECT COUNT(*) AS total
    FROM leave_requests lr
    JOIN users u ON u.id = lr.user_id
    $whereSql
";

$countStmt = $conn->prepare($countSql);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalRows / $limit);

/* -----------------------------
   FINAL PAGINATED QUERY
----------------------------- */
$finalSql = $selectSql . " $whereSql ORDER BY lr.created_at DESC LIMIT ?, ?";
$finalTypes  = $types . "ii";
$finalParams = array_merge($params, [$offset, $limit]);

$finalStmt = $conn->prepare($finalSql);
$finalStmt->bind_param($finalTypes, ...$finalParams);
$finalStmt->execute();

$result = $finalStmt->get_result();
$leaveRequests = [];

while ($row = $result->fetch_assoc()) {

    // Normalize leave type for TOIL
    $type = strtolower(trim($row['leave_type']));

    $row['leave_type'] = match ($type) {
        'vacation leave' => 'Vacation Leave',
        'sick leave' => 'Sick Leave',
        'emergency leave' => 'Emergency Leave',
        'compassionate leave' => 'Compassionate Leave',
        'toil' => 'Time In Lieu Off', //  or 'TOIL' if you prefer
        'mixed' => 'Mixed',
        default => ucfirst($row['leave_type'])
    };

    $leaveRequests[] = $row;
}

$finalStmt->close();
$conn->close();

/* -----------------------------
   OUTPUT JSON
----------------------------- */
echo json_encode([
    "status" => "success",
    "data" => $leaveRequests,
    "pagination" => [
        "page" => $page,
        "limit" => $limit,
        "totalRows" => $totalRows,
        "totalPages" => $totalPages
    ]
]);
