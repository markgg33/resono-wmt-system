<?php

//WORKING VERSION
/*session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Pagination
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

// Filters
$status    = $_GET['status'] ?? '';
$requestor = $_GET['requestor'] ?? '';

$where = [];

// Filter by status
if (!empty($status)) {
    $where[] = "da.status = '" . $conn->real_escape_string($status) . "'";
}

// Filter by requestor (user who filed)
if (!empty($requestor)) {
    $where[] = "da.user_id = " . intval($requestor);
}

// ==================================
// ROLE-BASED ACCESS
// ==================================
if ($userRole === 'supervisor') {
    // Supervisors see requests only from users in their department
    $deptQuery = $conn->query("
        SELECT department_id 
        FROM user_departments 
        WHERE user_id = $userId AND is_primary = 1 LIMIT 1
    ");
    $deptId = ($deptQuery && $deptQuery->num_rows > 0)
        ? (int)$deptQuery->fetch_assoc()['department_id']
        : 0;

    if ($deptId > 0) {
        $where[] = "da.user_id IN (
            SELECT user_id FROM user_departments WHERE department_id = $deptId
        )";
    }
} elseif (in_array($userRole, ['admin', 'hr', 'executive'])) {
    // Full access
} else {
    // Regular users only see their own
    $where[] = "da.user_id = " . intval($userId);
}

$whereClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ==================================
// QUERY MERGE LOGIC
// ==================================
// We LEFT JOIN both task_logs and task_logs_archive.
// If the log_id matches in task_logs, we use that; else we fallback to archive.

$query = "
    SELECT 
        da.id,
        da.request_uid,
        da.user_id,
        da.log_id,
        da.field,
        da.old_value,
        da.new_value,
        da.reason,
        da.status,
        da.processed_by,
        da.processed_at,
        da.requested_at,

        u.first_name AS requester_fname,
        u.last_name AS requester_lname,

        CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
        td.description AS task_description,

        COALESCE(tl_main.date, tl_archive.date) AS task_date,
        COALESCE(tl_main.start_time, tl_archive.start_time) AS start_time,
        COALESCE(tl_main.end_time, tl_archive.end_time) AS end_time,

        COALESCE(tl_main.total_duration, tl_archive.total_duration) AS total_duration,

        p.id AS processed_by_id,
        CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
        p.role AS processed_by_role

    FROM dtr_amendments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
    LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
    LEFT JOIN task_descriptions td 
        ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
    LEFT JOIN users p ON da.processed_by = p.id
    $whereClause
    ORDER BY da.id DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "SQL Error: " . $conn->error
    ]);
    exit;
}

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        "id"               => $row["id"],
        "request_uid"      => $row["request_uid"],
        "requester_name"   => $row["requester_name"],
        "task_description" => $row["task_description"],
        "field"            => $row["field"],
        "old_value"        => $row["old_value"],
        "new_value"        => $row["new_value"],
        "reason"           => $row["reason"],
        "status"           => $row["status"],
        "processed_by_name" => $row["processed_by_name"],
        "requested_at"     => $row["requested_at"],
        "processed_at"     => $row["processed_at"]
    ];
}

// Count total for pagination
$countQuery = "SELECT COUNT(*) AS total FROM dtr_amendments da $whereClause";
$countResult = $conn->query($countQuery);
$totalRows   = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages  = ceil($totalRows / $limit);

// Response
echo json_encode([
    "status"     => "success",
    "requests"   => $requests,
    "pagination" => [
        "currentPage" => $page,
        "totalPages"  => $totalPages,
        "totalRows"   => $totalRows
    ]
]);

$conn->close();*/

//CURRENT WORKING VERSION 
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ==============================
// SINGLE REQUEST FETCH (for modal)
// ==============================
if (isset($_GET['id'])) {
    $requestId = (int) $_GET['id'];

    $query = "
        SELECT 
            da.id,
            da.request_uid,
            da.user_id,
            da.log_id,
            da.field,
            da.old_value,
            da.new_value,
            da.reason,
            da.status,
            da.processed_by,
            da.processed_at,
            da.requested_at,

            u.first_name AS requester_fname,
            u.last_name AS requester_lname,
            CONCAT(u.first_name, ' ', u.last_name) AS requester_name,

            td.description AS task_description,

            COALESCE(tl_main.date, tl_archive.date) AS task_date,
            COALESCE(tl_main.start_time, tl_archive.start_time) AS start_time,
            COALESCE(tl_main.end_time, tl_archive.end_time) AS end_time,
            COALESCE(tl_main.total_duration, tl_archive.total_duration) AS total_duration,

            p.id AS processed_by_id,
            CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
            p.role AS processed_by_role

        FROM dtr_amendments da
        JOIN users u ON da.user_id = u.id
        LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
        LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
        LEFT JOIN task_descriptions td 
            ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
        LEFT JOIN users p ON da.processed_by = p.id
        WHERE da.id = $requestId
        LIMIT 1
    ";

    $res = $conn->query($query);
    if ($res && $res->num_rows > 0) {
        $req = $res->fetch_assoc();
        echo json_encode([
            "status"  => "success",
            "request" => [
                "id"                => $req["id"],
                "request_uid"       => $req["request_uid"],
                "user_id"           => $req["user_id"],           // ✅ Added: requester's user ID
                "requester_id"      => $req["user_id"],           // ✅ Added: alias for consistency
                "requester_name"    => $req["requester_name"],
                "task_description"  => $req["task_description"],
                "field"             => $req["field"],
                "old_value"         => $req["old_value"],
                "new_value"         => $req["new_value"],
                "reason"            => $req["reason"],
                "status"            => $req["status"],
                "requested_at"      => $req["requested_at"],
                "processed_by_name" => $req["processed_by_name"],
                "task_date"         => $req["task_date"],
                "start_time"        => $req["start_time"],
                "end_time"          => $req["end_time"]
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Request not found"]);
    }
    exit;
}

// ==============================
// PAGINATION + FILTER SECTION
// ==============================
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$status    = $_GET['status'] ?? '';
$requestor = $_GET['requestor'] ?? '';
$search    = $_GET['search'] ?? '';
$where = [];

// Filter by status
if (!empty($status)) {
    $where[] = "da.status = '" . $conn->real_escape_string($status) . "'";
}

// Filter by requestor
if (!empty($requestor)) {
    $where[] = "da.user_id = " . intval($requestor);
}

// ✅ New: Search filter (matches name or task description)
if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $where[] = "(
        CONCAT(u.first_name, ' ', u.last_name) LIKE '%$safeSearch%' 
        OR td.description LIKE '%$safeSearch%' 
        OR da.request_uid LIKE '%$safeSearch%'
    )";
}

// Role-based access
if ($userRole === 'supervisor') {
    $deptQuery = $conn->query("
        SELECT department_id 
        FROM user_departments 
        WHERE user_id = $userId AND is_primary = 1 LIMIT 1
    ");
    $deptId = ($deptQuery && $deptQuery->num_rows > 0)
        ? (int)$deptQuery->fetch_assoc()['department_id']
        : 0;

    if ($deptId > 0) {
        $where[] = "da.user_id IN (
            SELECT user_id FROM user_departments WHERE department_id = $deptId
        )";
    }
} elseif (!in_array($userRole, ['admin', 'hr', 'executive'])) {
    $where[] = "da.user_id = " . intval($userId);
}

$whereClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// Main query (with pagination)
$query = "
    SELECT 
        da.id,
        da.request_uid,
        da.user_id,
        da.field,
        da.old_value,
        da.new_value,
        da.reason,
        da.status,
        da.processed_by,
        da.processed_at,
        da.requested_at,
        CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
        td.description AS task_description,
        p.id AS processed_by_id,
        CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name
    FROM dtr_amendments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
    LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
    LEFT JOIN task_descriptions td 
        ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
    LEFT JOIN users p ON da.processed_by = p.id
    $whereClause
    ORDER BY da.id DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);
if (!$result) {
    echo json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]);
    exit;
}

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        "id"                => $row["id"],
        "requester_id"      => $row["user_id"],        // <-- add this
        "user_id"           => $row["user_id"],        // optional, can keep
        "request_uid"       => $row["request_uid"],
        "requester_name"    => $row["requester_name"],
        "task_description"  => $row["task_description"],
        "field"             => $row["field"],
        "old_value"         => $row["old_value"],
        "new_value"         => $row["new_value"],
        "reason"            => $row["reason"],
        "status"            => $row["status"],
        "processed_by_name" => $row["processed_by_name"],
        "requested_at"      => $row["requested_at"],
        "processed_at"      => $row["processed_at"]
    ];
}

// Count total for pagination
$countQuery = "
    SELECT COUNT(*) AS total
    FROM dtr_amendments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
    LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
    LEFT JOIN task_descriptions td 
        ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
    LEFT JOIN users p ON da.processed_by = p.id
    $whereClause
";

$countResult = $conn->query($countQuery);
$totalRows   = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages  = ceil($totalRows / $limit);

echo json_encode([
    "status" => "success",
    "current_user_id" => $userId,
    "current_user_role" => $userRole,
    "requests" => $requests,
    "pagination" => [
        "currentPage" => $page,
        "totalPages" => $totalPages,
        "totalRows" => $totalRows
    ]
]);

$conn->close();
*/

//TESTING VERSION (SUPERVISOR RBAC)
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

/* ======================================================
   ============= SINGLE REQUEST FETCH ===================
   ====================================================== */
if (isset($_GET['id'])) {
    $requestId = (int) $_GET['id'];

    $query = "
        SELECT 
            da.id,
            da.request_uid,
            da.user_id,
            da.recipient_id,
            da.log_id,
            da.field,
            da.old_value,
            da.new_value,
            da.reason,
            da.status,
            da.processed_by,
            da.processed_at,
            da.requested_at,

            u.first_name AS requester_fname,
            u.last_name AS requester_lname,
            CONCAT(u.first_name, ' ', u.last_name) AS requester_name,

            td.description AS task_description,

            COALESCE(tl_main.date, tl_archive.date) AS task_date,
            COALESCE(tl_main.start_time, tl_archive.start_time) AS start_time,
            COALESCE(tl_main.end_time, tl_archive.end_time) AS end_time,
            COALESCE(tl_main.total_duration, tl_archive.total_duration) AS total_duration,

            p.id AS processed_by_id,
            CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name,
            p.role AS processed_by_role

        FROM dtr_amendments da
        JOIN users u ON da.user_id = u.id
        LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
        LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
        LEFT JOIN task_descriptions td 
            ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
        LEFT JOIN users p ON da.processed_by = p.id
        WHERE da.id = $requestId
        LIMIT 1
    ";

    $res = $conn->query($query);
    if (!$res || $res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Request not found"]);
        exit;
    }

    $req = $res->fetch_assoc();

    /* ============================================
       SUPERVISOR ACCESS CHECK (MULTI-DEPARTMENT)
       ============================================ */
    if ($userRole === 'supervisor') {

        // Get ALL departments the supervisor belongs to
        $deptQuery = $conn->query("
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = $userId
        ");

        $supervisorDepts = [];
        if ($deptQuery && $deptQuery->num_rows > 0) {
            while ($d = $deptQuery->fetch_assoc()) {
                $supervisorDepts[] = (int)$d['department_id'];
            }
        }

        // Rule A: assigned directly
        $isRecipient = ($req['recipient_id'] == $userId);

        // Rule B: requester belongs to any supervisor department
        $inDept = false;
        if (!empty($supervisorDepts)) {
            $deptList = implode(",", $supervisorDepts);
            $checkDept = $conn->query("
                SELECT 1 FROM user_departments
                WHERE user_id = {$req['user_id']} 
                AND department_id IN ($deptList)
                LIMIT 1
            ");
            $inDept = ($checkDept && $checkDept->num_rows > 0);
        }

        if (!$isRecipient && !$inDept) {
            echo json_encode([
                "status" => "error",
                "message" => "Unauthorized: This request is not assigned to you."
            ]);
            exit;
        }
    }

    echo json_encode([
        "status"  => "success",
        "request" => [
            "id"                => $req["id"],
            "request_uid"       => $req["request_uid"],
            "user_id"           => $req["user_id"],
            "requester_id"      => $req["user_id"],
            "requester_name"    => $req["requester_name"],
            "task_description"  => $req["task_description"],
            "field"             => $req["field"],
            "old_value"         => $req["old_value"],
            "new_value"         => $req["new_value"],
            "reason"            => $req["reason"],
            "status"            => $req["status"],
            "requested_at"      => $req["requested_at"],
            "processed_by_name" => $req["processed_by_name"],
            "task_date"         => $req["task_date"],
            "start_time"        => $req["start_time"],
            "end_time"          => $req["end_time"]
        ]
    ]);
    exit;
}

/* ======================================================
   ============= PAGINATION + FILTER SECTION ============
   ====================================================== */

$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$status    = $_GET['status'] ?? '';
$requestor = $_GET['requestor'] ?? '';
$search    = $_GET['search'] ?? '';

$where = [];

/* Filters */
if (!empty($status)) {
    $where[] = "da.status = '" . $conn->real_escape_string($status) . "'";
}

if (!empty($requestor)) {
    $where[] = "da.user_id = " . intval($requestor);
}

if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $where[] = "(
        CONCAT(u.first_name, ' ', u.last_name) LIKE '%$safeSearch%' 
        OR td.description LIKE '%$safeSearch%' 
        OR da.request_uid LIKE '%$safeSearch%'
    )";
}

/* ======================================================
   SUPERVISOR ACCESS (LIST) — MULTI-DEPARTMENT
   ====================================================== */
if ($userRole === 'supervisor') {

    // Get all supervisor departments
    $deptQuery = $conn->query("
        SELECT department_id 
        FROM user_departments 
        WHERE user_id = $userId
    ");

    $supervisorDepts = [];
    if ($deptQuery && $deptQuery->num_rows > 0) {
        while ($d = $deptQuery->fetch_assoc()) {
            $supervisorDepts[] = (int)$d['department_id'];
        }
    }

    if (!empty($supervisorDepts)) {
        $deptList = implode(",", $supervisorDepts);

        // Supervisor sees:
        // 1) requests assigned directly to them
        // 2) requests of users in ANY of their departments
        $where[] = "
            (
                da.recipient_id = $userId
                OR da.user_id IN (
                    SELECT user_id FROM user_departments 
                    WHERE department_id IN ($deptList)
                )
            )
        ";
    } else {
        // Edge case fallback
        $where[] = "da.recipient_id = $userId";
    }
} elseif (!in_array($userRole, ['admin', 'hr', 'executive'])) {
    // Normal employee => only own requests
    $where[] = "da.user_id = " . intval($userId);
}

$whereClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

/* Main query */
$query = "
    SELECT 
        da.id,
        da.request_uid,
        da.user_id,
        da.field,
        da.old_value,
        da.new_value,
        da.reason,
        da.status,
        da.processed_by,
        da.processed_at,
        da.requested_at,
        CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
        td.description AS task_description,
        p.id AS processed_by_id,
        CONCAT(p.first_name, ' ', p.last_name) AS processed_by_name
    FROM dtr_amendments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
    LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
    LEFT JOIN task_descriptions td 
        ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
    LEFT JOIN users p ON da.processed_by = p.id
    $whereClause
    ORDER BY da.id DESC
    LIMIT $limit OFFSET $offset
";

$result   = $conn->query($query);
$requests = [];

while ($row = $result->fetch_assoc()) {
    $requests[] = [
        "id"                => $row["id"],
        "requester_id"      => $row["user_id"],
        "user_id"           => $row["user_id"],
        "request_uid"       => $row["request_uid"],
        "requester_name"    => $row["requester_name"],
        "task_description"  => $row["task_description"],
        "field"             => $row["field"],
        "old_value"         => $row["old_value"],
        "new_value"         => $row["new_value"],
        "reason"            => $row["reason"],
        "status"            => $row["status"],
        "processed_by_name" => $row["processed_by_name"],
        "requested_at"      => $row["requested_at"],
        "processed_at"      => $row["processed_at"]
    ];
}

/* Count for pagination */
$countQuery = "
    SELECT COUNT(*) AS total
    FROM dtr_amendments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN task_logs tl_main ON da.log_id = tl_main.id
    LEFT JOIN task_logs_archive tl_archive ON da.log_id = tl_archive.original_id
    LEFT JOIN task_descriptions td 
        ON td.id = COALESCE(tl_main.task_description_id, tl_archive.task_description_id)
    LEFT JOIN users p ON da.processed_by = p.id
    $whereClause
";

$countResult = $conn->query($countQuery);
$totalRows   = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$totalPages  = ceil($totalRows / $limit);

echo json_encode([
    "status" => "success",
    "current_user_id" => $userId,
    "current_user_role" => $userRole,
    "requests" => $requests,
    "pagination" => [
        "currentPage" => $page,
        "totalPages"  => $totalPages,
        "totalRows"   => $totalRows
    ]
]);

$conn->close();
