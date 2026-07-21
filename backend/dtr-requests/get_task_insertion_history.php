<?php
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

// ========================================
// 1️⃣ AUTH CHECK
// ========================================
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = strtolower($_SESSION['role']);

// ========================================
// 2️⃣ QUERY PARAMETERS
// ========================================
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$status = $_GET['status'] ?? 'All';
$search = trim($_GET['search'] ?? '');

// ========================================
// 3️⃣ BASE QUERY + JOINS
// ========================================
$base_query = "
    FROM task_insertion_requests tir
    JOIN users u ON tir.user_id = u.id
    JOIN work_modes wm ON tir.work_mode_id = wm.id
    JOIN task_descriptions td ON tir.task_description_id = td.id
    LEFT JOIN users pr ON tir.processed_by = pr.id
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
";

// ========================================
// 4️⃣ FILTERS
// ========================================
$where = [];
$params = [];
$types = "";

// ✅ Filter by status
if ($status !== 'All') {
    $where[] = "tir.status = ?";
    $params[] = $status;
    $types .= "s";
}

// ✅ Search by name, UID, department
if (!empty($search)) {
    $where[] = "(
        CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        OR tir.request_uid LIKE ?
        OR wm.name LIKE ?
        OR td.description LIKE ?
        OR d.name LIKE ?
    )";
    for ($i = 0; $i < 5; $i++) {
        $params[] = "%$search%";
        $types .= "s";
    }
}

// ✅ Role-based visibility
if ($role === 'supervisor') {
    // Supervisors see requests from their departments (and their own)
    $base_query .= "
        JOIN user_departments ud_sup ON ud_sup.department_id = ud.department_id
        AND ud_sup.user_id = ?
    ";
    $params[] = $user_id;
    $types .= "i";
} elseif (!in_array($role, ['admin', 'hr', 'executive'])) {
    // Regular users see only their own requests
    $where[] = "tir.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ========================================
// 5️⃣ COUNT TOTAL RECORDS
// ========================================
$count_sql = "SELECT COUNT(DISTINCT tir.id) AS total $base_query $whereSQL";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

// ========================================
// 6️⃣ FETCH PAGINATED RESULTS
// ========================================
$query = "
    SELECT 
        tir.id,
        tir.request_uid,
        tir.date,
        tir.start_time,
        tir.end_time,
        tir.status,
        tir.remarks,
        tir.processed_at,
        CONCAT(pr.first_name, ' ', pr.last_name) AS processed_by_name,
        wm.name AS work_mode,
        td.description AS task_desc,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) AS requester_name
    $base_query
    $whereSQL
    GROUP BY tir.id
    ORDER BY tir.created_at DESC
    LIMIT ?, ?
";

$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ========================================
// 7️⃣ RESPONSE
// ========================================
echo json_encode([
    'success' => true,
    'requests' => $result,
    'total' => (int)$total,
    'page' => $page,
    'limit' => $limit,
    'total_pages' => ceil($total / $limit)
]);

$conn->close();
