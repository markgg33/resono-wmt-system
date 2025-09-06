<?php
session_start();
require_once "connection_db.php";

// Allow access only to Admin, HR, or Executive
$allowed_roles = ['admin', 'hr', 'executive'];

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$userId = intval($data['id']);
$status = $data['status'] === 'active' ? 'active' : 'inactive'; // sanitize

$sql = "UPDATE users SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $userId);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "User status updated",
        "user_id" => $userId,
        "status" => $status
    ]);
} else {
    echo json_encode(["error" => "Error updating user status"]);
}
