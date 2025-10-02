<?php
session_start();
require_once "connection_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_GET['id'] ?? $_SESSION['user_id'];

$sql = "SELECT 
            u.id,
            u.employee_id, 
            u.first_name, 
            u.middle_name, 
            u.last_name, 
            u.email, 
            u.role, 
            u.profile_image,
            d.id AS department_id,
            d.name AS department_name,
            ud.is_primary
        FROM users u
        LEFT JOIN user_departments ud ON u.id = ud.user_id
        LEFT JOIN departments d ON ud.department_id = d.id
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$user = null;
$departments = [];
while ($row = $result->fetch_assoc()) {
    if (!$user) {
        $user = [
            "id" => $row["id"],
            "employee_id" => $row["employee_id"],
            "first_name" => $row["first_name"],
            "middle_name" => $row["middle_name"],
            "last_name" => $row["last_name"],
            "email" => $row["email"],
            "role" => $row["role"],
            "profile_image" => $row["profile_image"] ?: "/assets/default-avatar.jpg"
        ];
    }
    if ($row["department_id"]) {
        $departments[] = [
            "id" => $row["department_id"],
            "name" => $row["department_name"],
            "is_primary" => (bool)$row["is_primary"]  // ✅ add primary info
        ];
    }
}

if ($user) {
    $user["departments"] = $departments;
    echo json_encode($user);
} else {
    echo json_encode(["error" => "User not found"]);
}
