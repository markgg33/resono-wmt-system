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

$user_id = $_SESSION['user_id'];
$role = strtolower($_SESSION['role']);

// ========================================
// 2️⃣ QUERY PARAMETERS
// ========================================
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$status = $_GET['status'] ?? 'Pending';
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

// ✅ Filter by search (includes requestor)
if (!empty($search)) {
    $where[] = "(
        tir.request_uid LIKE ?
        OR wm.name LIKE ?
        OR td.description LIKE ?
        OR d.name LIKE ?
        OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        OR u.first_name LIKE ?
        OR u.last_name LIKE ?
    )";
    for ($i = 0; $i < 7; $i++) {
        $params[] = "%$search%";
        $types .= "s";
    }
}
    

// ========================================
// 5️⃣ ROLE-BASED VISIBILITY
// ========================================
if (in_array($role, ['admin', 'executive', 'hr'])) {
    // Full access (no extra filter)
} elseif ($role === 'supervisor') {
    // Supervisors see their own + department
    $where[] = "(
        tir.user_id = ? 
        OR u.id IN (
            SELECT ud2.user_id
            FROM user_departments ud2
            WHERE ud2.department_id IN (
                SELECT department_id
                FROM user_departments
                WHERE user_id = ?
            )
        )
    )";
    $params[] = $user_id;
    $params[] = $user_id;
    $types .= "ii";
} else {
    // Regular users see only their own requests
    $where[] = "tir.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ========================================
// 6️⃣ COUNT TOTAL
// ========================================
$count_sql = "SELECT COUNT(DISTINCT tir.id) AS total $base_query $whereSQL";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

// ========================================
// 7️⃣ FETCH PAGINATED RESULTS (WITH DETAILS)
// ========================================
$query = "
    SELECT 
        tir.id,
        tir.request_uid,
        tir.status,
        tir.created_at,
        tir.reason,
        tir.date,
        tir.start_time,
        tir.end_time,
        tir.processed_at,
        wm.name AS work_mode,
        td.description AS task_desc,
        CONCAT(
            u.first_name, ' ',
            COALESCE(CONCAT(LEFT(u.middle_name, 1), '. '), ''),
            u.last_name
        ) AS requester_name,
        CONCAT(pr.first_name, ' ', pr.last_name) AS processed_by_name,
        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS departments
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
// 8️⃣ RESPONSE
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
