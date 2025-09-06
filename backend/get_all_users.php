<?php
require_once "connection_db.php";
header('Content-Type: application/json');

$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

$sql = "SELECT u.id, u.first_name, u.middle_name, u.last_name, 
               u.email, u.role, u.employee_id,
               u.status, u.profile_image,
               d.name AS department_name, u.department_id
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id";

if ($departmentId > 0) {
    $sql .= " WHERE u.department_id = ?";
}

$stmt = $conn->prepare($sql);
if ($departmentId > 0) {
    $stmt->bind_param("i", $departmentId);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
