<?php
require_once "connection_db.php";
header('Content-Type: application/json');

$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

/**
 * Support BOTH:
 *  - legacy users with u.department_id
 *  - new users with user_departments mapping
 */
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
