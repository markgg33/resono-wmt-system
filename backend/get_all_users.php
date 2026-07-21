<?php
//WORKING VERSION
/*
require_once "connection_db.php";
header('Content-Type: application/json');

$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

// Support BOTH:  - legacy users with u.department_id  - new users with user_departments mapping
$sql = "
    SELECT 
        u.id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        u.role,
        u.employee_id,
        u.status,
        u.profile_image,
        COALESCE(d.id, d2.id) AS department_id,
        COALESCE(d.name, d2.name) AS department_name
    FROM users u
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN departments d2 ON u.department_id = d2.id
";

if ($departmentId > 0) {
    $sql .= " WHERE COALESCE(d.id, d2.id) = ?";
}

$stmt = $conn->prepare($sql);
if ($departmentId > 0) {
    $stmt->bind_param("i", $departmentId);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {
    $userId = $row['id'];

    if (!isset($users[$userId])) {
        $users[$userId] = [
            "id" => $row['id'],
            "first_name" => $row['first_name'],
            "middle_name" => $row['middle_name'],
            "last_name" => $row['last_name'],
            "email" => $row['email'],
            "role" => $row['role'],
            "employee_id" => $row['employee_id'],
            "status" => $row['status'],
            "profile_image" => $row['profile_image'],
            "departments" => []
        ];
    }

    if (!empty($row['department_id'])) {
        $users[$userId]['departments'][] = [
            "id" => $row['department_id'],
            "name" => $row['department_name']
        ];
    }
}

echo json_encode(array_values($users));
*/

//WORKING VERSION V2

require_once "connection_db.php";
header('Content-Type: application/json');

// -------------------------------------------------
// 🔹 Get department filter (single or multiple)
// -------------------------------------------------
$departmentIds = [];

// single department_id (legacy)
if (isset($_GET['department_id']) && is_numeric($_GET['department_id'])) {
    $departmentIds[] = intval($_GET['department_id']);
}

// multiple department_ids (comma-separated)
if (isset($_GET['department_ids'])) {
    $ids = explode(',', $_GET['department_ids']);
    foreach ($ids as $id) {
        $id = intval($id);
        if ($id > 0) $departmentIds[] = $id;
    }
}

// remove duplicates
$departmentIds = array_unique($departmentIds);

// -------------------------------------------------
// 🔹 Base SQL (support legacy u.department_id and new user_departments mapping)
// -------------------------------------------------
$sql = "
    SELECT 
        u.id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        u.role,
        u.employee_id,
        u.status,
        u.profile_image,
        COALESCE(d.id, d2.id) AS department_id,
        COALESCE(d.name, d2.name) AS department_name
    FROM users u
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN departments d2 ON u.department_id = d2.id
";

// -------------------------------------------------
// 🔹 Add WHERE if filtering by department(s)
// -------------------------------------------------
$params = [];
$types = '';
if (!empty($departmentIds)) {
    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
    $sql .= " WHERE COALESCE(d.id, d2.id) IN ($placeholders)";
    $types = str_repeat('i', count($departmentIds));
    $params = $departmentIds;
}

// -------------------------------------------------
// 🔹 Prepare and execute
// -------------------------------------------------
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// -------------------------------------------------
// 🔹 Process results, group departments per user
// -------------------------------------------------
$users = [];
while ($row = $result->fetch_assoc()) {
    $userId = $row['id'];
    if (!isset($users[$userId])) {
        $users[$userId] = [
            "id" => $row['id'],
            "first_name" => $row['first_name'],
            "middle_name" => $row['middle_name'],
            "last_name" => $row['last_name'],
            "email" => $row['email'],
            "role" => $row['role'],
            "employee_id" => $row['employee_id'],
            "status" => $row['status'],
            "profile_image" => $row['profile_image'],
            "departments" => []
        ];
    }

    if (!empty($row['department_id'])) {
        $users[$userId]['departments'][] = [
            "id" => $row['department_id'],
            "name" => $row['department_name']
        ];
    }
}

// -------------------------------------------------
// 🔹 Return JSON
// -------------------------------------------------
echo json_encode(array_values($users));


//WORKING VERSION WITH PAGINATION
/*

require_once "connection_db.php";
header('Content-Type: application/json');

// ---- Pagination ----
$page   = isset($_GET['page'])  ? max(1, intval($_GET['page'])) : 1;
$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

// -------------------------------------------------
// 🔹 Get department filter (single or multiple)
// -------------------------------------------------
$departmentIds = [];

// single department_id (legacy)
if (isset($_GET['department_id']) && is_numeric($_GET['department_id'])) {
    $departmentIds[] = intval($_GET['department_id']);
}

// multiple department_ids (comma-separated)
if (isset($_GET['department_ids'])) {
    $ids = explode(',', $_GET['department_ids']);
    foreach ($ids as $id) {
        $id = intval($id);
        if ($id > 0) $departmentIds[] = $id;
    }
}

$departmentIds = array_unique($departmentIds);

// -------------------------------------------------
// 🔹 MAIN SQL (paginated)
// -------------------------------------------------
$sql = "
    SELECT 
        u.id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        u.role,
        u.employee_id,
        u.status,
        u.profile_image,
        COALESCE(d.id, d2.id) AS department_id,
        COALESCE(d.name, d2.name) AS department_name
    FROM users u
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN departments d2 ON u.department_id = d2.id
";

$params = [];
$types = '';

if (!empty($departmentIds)) {
    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
    $sql .= " WHERE COALESCE(d.id, d2.id) IN ($placeholders)";
    $types .= str_repeat('i', count($departmentIds));
    $params = array_merge($params, $departmentIds);
}

$sql .= " GROUP BY u.id ORDER BY u.id DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$users = [];
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}

// -------------------------------------------------
// 🔹 Count total users (for pagination)
// -------------------------------------------------
$countSql = "
    SELECT COUNT(DISTINCT u.id) AS total
    FROM users u
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    LEFT JOIN departments d2 ON u.department_id = d2.id
";

$countParams = [];
$countTypes = '';

if (!empty($departmentIds)) {
    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
    $countSql .= " WHERE COALESCE(d.id, d2.id) IN ($placeholders)";
    $countTypes = str_repeat('i', count($departmentIds));
    $countParams = $departmentIds;
}

$countStmt = $conn->prepare($countSql);

if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}

$countStmt->execute();
$countRes = $countStmt->get_result();
$total = $countRes->fetch_assoc()['total'];

// -------------------------------------------------
// 🔹 Return JSON
// -------------------------------------------------
echo json_encode([
    "users" => $users,
    "total" => $total,
    "page" => $page,
    "limit" => $limit,
    "total_pages" => ceil($total / $limit)
]);
*/

