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

$userId      = intval($_POST['id']);
$first_name  = trim($_POST['first_name']);
$middle_name = trim($_POST['middle_name']);
$last_name   = trim($_POST['last_name']);
$employee_id = !empty($_POST['employee_id']) ? trim($_POST['employee_id']) : null;
$role        = trim($_POST['role']);
$department  = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

if (!$first_name || !$last_name) {
    echo json_encode(["error" => "First and last name required"]);
    exit;
}

// Handle profile image upload
$profile_image_path = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmp  = $_FILES['profile_image']['tmp_name'];
    $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
    $target   = $uploadDir . $fileName;

    // Validate file type (only images)
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    if (in_array($_FILES['profile_image']['type'], $allowed_types)) {
        if (move_uploaded_file($fileTmp, $target)) {
            // Save relative path instead of ../uploads
            $profile_image_path = "uploads/" . $fileName;
        }
    }
}

if ($profile_image_path) {
    $sql = "UPDATE users 
            SET first_name = ?, middle_name = ?, last_name = ?, employee_id = ?, role = ?, department_id = ?, profile_image = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssisi", $first_name, $middle_name, $last_name, $employee_id, $role, $department, $profile_image_path, $userId);
} else {
    $sql = "UPDATE users 
            SET first_name = ?, middle_name = ?, last_name = ?, employee_id = ?, role = ?, department_id = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", $first_name, $middle_name, $last_name, $employee_id, $role, $department, $userId);
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => "User updated successfully",
        "profile_image" => $profile_image_path // send back for frontend update
    ]);
} else {
    echo json_encode(["error" => "Error updating user"]);
}
