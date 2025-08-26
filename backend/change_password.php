<?php
session_start();
require_once "connection_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$current_password = trim($data['current_password']);
$new_password     = trim($data['new_password']);
$confirm_password = trim($data['confirm_password']);

if (!$current_password || !$new_password || !$confirm_password) {
    echo json_encode(["error" => "All password fields are required"]);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(["error" => "New passwords do not match"]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(["error" => "New password must be at least 6 characters"]);
    exit;
}

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

if (!password_verify($current_password, $hashed_password)) {
    echo json_encode(["error" => "Current password is incorrect"]);
    exit;
}

$new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $new_hashed, $userId);

if ($stmt->execute()) {
    echo json_encode(["success" => "Password updated successfully"]);
} else {
    echo json_encode(["error" => "Error updating password"]);
}
