<?php
session_start();
require_once "connection_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$userId      = intval($data['id']);
$first_name  = trim($data['first_name']);
$middle_name = trim($data['middle_name']);
$last_name   = trim($data['last_name']);
$employee_id = !empty($data['employee_id']) ? trim($data['employee_id']) : null;
$role        = trim($data['role']);
$department  = !empty($data['department_id']) ? intval($data['department_id']) : null;

if (!$first_name || !$last_name) {
    echo json_encode(["error" => "First and last name required"]);
    exit;
}

$sql = "UPDATE users 
        SET first_name = ?, middle_name = ?, last_name = ?, employee_id = ?, role = ?, department_id = ? 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssii", $first_name, $middle_name, $last_name, $employee_id, $role, $department, $userId);

if ($stmt->execute()) {
    echo json_encode(["success" => "User updated successfully"]);
} else {
    echo json_encode(["error" => "Error updating user"]);
}
