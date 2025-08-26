<?php
session_start();
require_once "connection_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_GET['id'] ?? $_SESSION['user_id'];

$sql = "SELECT u.employee_id, u.first_name, u.middle_name, u.last_name, u.email, u.role, 
               u.department_id, d.name AS department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode($result->fetch_assoc());
