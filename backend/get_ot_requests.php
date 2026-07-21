<?php
/*
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Optional filters
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';
$dept  = $_GET['dept'] ?? '';
$user  = $_GET['user'] ?? '';

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15; // default 15 rows per page
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 15;
if ($limit > 15) $limit = 15; // enforce maximum 15

$offset = ($page - 1) * $limit;

// Build FROM + JOIN + WHERE parts so we can reuse them for count and data queries
$fromWhere = "FROM ot_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN users c ON ot.checked_by = c.id
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    WHERE 1 ";

$params = [];
$types  = '';

if (!empty($start)) {
    $fromWhere .= " AND ot.tracker_date >= ? ";
    $params[] = $start;
    $types .= 's';
}
if (!empty($end)) {
    $fromWhere .= " AND ot.tracker_date <= ? ";
    $params[] = $end;
    $types .= 's';
}
if (!empty($dept)) {
    $fromWhere .= " AND d.id = ? ";
    $params[] = $dept;
    $types .= 'i';
}
if (!empty($user)) {
    $fromWhere .= " AND u.id = ? ";
    $params[] = $user;
    $types .= 'i';
}

// Supervisors, HR, Admins, Executives can see all; normal users only see their own
if (!in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    $fromWhere .= " AND ot.user_id = ? ";
    $params[] = $userId;
    $types .= 'i';
}

// Count total
$countQuery = "SELECT COUNT(DISTINCT ot.id) AS total " . $fromWhere;
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = 0;
if ($row = $countResult->fetch_assoc()) {
    $total = (int)$row['total'];
}
$countStmt->close();

// Data query with limit
$dataQuery = "SELECT 
        ot.id,
        ot.tracker_date,
        TIME_FORMAT(ot.hours, '%H:%i') AS hours,
        ot.status,
        ot.date_created,
        ot.reason,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
        CONCAT(c.first_name, ' ', c.last_name) AS checked_by,
        ot.remarks
    " . $fromWhere . " ORDER BY ot.date_created DESC LIMIT ?, ?";

$stmt = $conn->prepare($dataQuery);

// Bind params + offset + limit
$bindTypes = $types . 'ii';
$bindParams = $params;
$bindParams[] = $offset;
$bindParams[] = $limit;

if (!empty($bindParams)) {
    $stmt->bind_param($bindTypes, ...$bindParams);
}

$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$totalPages = $limit > 0 ? (int)ceil($total / $limit) : 1;

echo json_encode([
    'data' => $requests,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => $totalPages
    ]
]);

$stmt->close();
$conn->close();
*/

session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ------------------------------
// GET FILTERS
// ------------------------------
$start  = $_GET['start']  ?? '';
$end    = $_GET['end']    ?? '';
$dept   = $_GET['dept']   ?? '';
$user   = $_GET['user']   ?? '';
$status = $_GET['status'] ?? '';

// Pagination
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

if ($page < 1)  $page = 1;
if ($limit < 1) $limit = 15;
if ($limit > 15) $limit = 15;

$offset = ($page - 1) * $limit;


// ================================
// 🔹 BUILD WHERE CLAUSE
// ================================
$where = " WHERE 1 ";
$params = [];
$types  = "";

// STATUS FILTER
if (!empty($status) && $status !== "all") {
    $where .= " AND ot.status = ? ";
    $params[] = $status;
    $types .= "s";
}

// DATE CREATED RANGE FILTER
if (!empty($start)) {
    $where .= " AND DATE(ot.date_created) >= ? ";
    $params[] = $start;
    $types .= "s";
}

if (!empty($end)) {
    $where .= " AND DATE(ot.date_created) <= ? ";
    $params[] = $end;
    $types .= "s";
}

// DEPARTMENT FILTER
if (!empty($dept)) {
    $where .= " AND d.id = ? ";
    $params[] = $dept;
    $types .= "i";
}

// USER FILTER
if (!empty($user)) {
    $where .= " AND u.id = ? ";
    $params[] = $user;
    $types .= "i";
}

// ================================
// STRICT ROLE-BASED VISIBILITY //NEW FOR TESTING
// ================================
if ($userRole === 'supervisor') {

    // Get supervisor departments (primary only for strict filtering)
    $deptStmt = $conn->prepare("
        SELECT department_id 
        FROM user_departments 
        WHERE user_id = ? AND is_primary = 1
    ");
    $deptStmt->bind_param("i", $userId);
    $deptStmt->execute();
    $deptRes = $deptStmt->get_result();

    $supervisorDept = $deptRes->fetch_assoc()['department_id'] ?? null;
    $deptStmt->close();

    if (!$supervisorDept) {
        // No department = see nothing
        $where .= " AND 1 = 0 ";
    } else {
        // Supervisor only sees:
        // 1) Employees under his primary department
        // 2) Requests where he is the recipient
        $where .= " AND (
            ud.department_id = ?
            OR ot.recipient_id = ?
        )";

        $params[] = $supervisorDept;
        $params[] = $userId;
        $types .= "ii";
    }
}
// NON-SUPERVISOR EMPLOYEE
else if (!in_array($userRole, ['admin', 'executive', 'hr'])) {
    // Regular employees only see their own requests
    $where .= " AND ot.user_id = ? ";
    $params[] = $userId;
    $types .= "i";
}

// ROLE-BASED VISIBILITY
/*
if (!in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    $where .= " AND ot.user_id = ? ";
    $params[] = $userId;
    $types .= "i";
}*/



// ================================
// 🔹 SUPERVISOR DEPARTMENT VISIBILITY //TEST (working)
// ================================
/*
if ($userRole === 'supervisor') {

    // Load supervisor’s departments
    $deptStmt = $conn->prepare("
        SELECT department_id 
        FROM user_departments 
        WHERE user_id = ?
    ");
    $deptStmt->bind_param("i", $userId);
    $deptStmt->execute();
    $deptRes = $deptStmt->get_result();

    $supervisorDepts = [];
    while ($r = $deptRes->fetch_assoc()) {
        $supervisorDepts[] = (int) $r['department_id'];
    }
    $deptStmt->close();

    // If supervisor has no assigned departments → show nothing
    if (count($supervisorDepts) === 0) {
        $where .= " AND 1 = 0 "; // returns empty result
    } else {
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($supervisorDepts), '?'));

        // Filter by supervisor’s departments
        $where .= " AND ud.department_id IN ($placeholders) ";

        foreach ($supervisorDepts as $d) {
            $params[] = $d;
            $types .= "i";
        }
    }
}*/

// ================================
// SUPERVISOR VISIBILITY RULE (under department and recipient)
// ================================
if ($userRole === 'supervisor') {

    // Get supervisor departments
    $deptStmt = $conn->prepare("
        SELECT department_id 
        FROM user_departments 
        WHERE user_id = ?
    ");
    $deptStmt->bind_param("i", $userId);
    $deptStmt->execute();
    $deptRes = $deptStmt->get_result();

    $supervisorDepts = [];
    while ($r = $deptRes->fetch_assoc()) {
        $supervisorDepts[] = (int)$r['department_id'];
    }
    $deptStmt->close();

    if (count($supervisorDepts) === 0) {
        // If they have no department assignment → show nothing
        $where .= " AND 1 = 0 ";
    } else {
        // Supervisor can only see:
        // 1. Requests from employees in their departments
        // 2. Requests where they are the recipient
        $placeholders = implode(',', array_fill(0, count($supervisorDepts), '?'));

        $where .= " AND (
            ud.department_id IN ($placeholders)
            OR ot.recipient_id = ?
        )";

        // Bind department list
        foreach ($supervisorDepts as $d) {
            $params[] = $d;
            $types .= "i";
        }

        // Bind the supervisor as recipient
        $params[] = $userId;
        $types .= "i";
    }
}



// ================================
// 🔹 COUNT QUERY
// ================================
/*$countSql = "
    SELECT COUNT(DISTINCT ot.id) AS total
    FROM ot_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN users c ON ot.checked_by = c.id
    $where
";*/

$countSql = "
    SELECT COUNT(DISTINCT ot.id) AS total
    FROM ot_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN user_departments ud 
       ON u.id = ud.user_id AND ud.is_primary = 1
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN users c ON ot.checked_by = c.id
    $where
";

$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countRes = $countStmt->get_result();
$total = ($row = $countRes->fetch_assoc()) ? (int)$row['total'] : 0;
$countStmt->close();


// ================================
// 🔹 DATA QUERY
// ================================
/*$dataSql = "
    SELECT DISTINCT
        ot.id,
        ot.tracker_date,
        TIME_FORMAT(ot.hours, '%H:%i') AS hours,
        ot.status,
        ot.date_created,
        ot.reason,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
        CONCAT(c.first_name, ' ', c.last_name) AS checked_by,
        ot.remarks
    FROM ot_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN users c ON ot.checked_by = c.id
    $where
    ORDER BY ot.date_created DESC
    LIMIT ?, ?
";*/

$dataSql = "
    SELECT DISTINCT
        ot.id,
        ot.tracker_date,
        TIME_FORMAT(ot.hours, '%H:%i') AS hours,
        ot.status,
        ot.date_created,
        ot.reason,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
        CONCAT(c.first_name, ' ', c.last_name) AS checked_by,
        ot.remarks
    FROM ot_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN user_departments ud 
       ON u.id = ud.user_id AND ud.is_primary = 1
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN users c ON ot.checked_by = c.id
    $where
    ORDER BY ot.date_created DESC
    LIMIT ?, ?
";

$stmt = $conn->prepare($dataSql);

// Add pagination params
$bindTypes = $types . "ii";
$bindParams = $params;
$bindParams[] = $offset;
$bindParams[] = $limit;

$stmt->bind_param($bindTypes, ...$bindParams);

$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();
$conn->close();

$totalPages = ($limit > 0) ? ceil($total / $limit) : 1;

echo json_encode([
    "data" => $requests,
    "pagination" => [
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "totalPages" => $totalPages
    ]
]);
