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

$first_name  = trim($data['first_name']);
$middle_name = trim($data['middle_name']);
$last_name   = trim($data['last_name']);
$employee_id = isset($data['employee_id']) && $data['employee_id'] !== ''
    ? trim($data['employee_id'])
    : null;

if (!$first_name || !$last_name) {
    echo json_encode(["error" => "First name and last name are required"]);
    exit;
}

$sql = "UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, employee_id = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $first_name, $middle_name, $last_name, $employee_id, $userId);

if ($stmt->execute()) {
    // ✅ Build full name with middle initial (if middle name exists)
    $middleInitial = $middle_name ? " " . strtoupper(substr($middle_name, 0, 1)) . "." : "";
    $fullName = $first_name . $middleInitial . " " . $last_name;

    // ✅ Update session
    $_SESSION['name'] = $fullName;

    echo json_encode([
        "success" => "Profile updated successfully",
        "name" => $fullName
    ]);
} else {
    echo json_encode(["error" => "Error updating profile"]);
}


$stmt->close();
$conn->close();
