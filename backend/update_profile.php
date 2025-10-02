<?php
header('Content-Type: application/json');
session_start();
require 'connection_db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userRole = strtolower($_SESSION['role'] ?? '');

// Read text fields
$first_name  = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name   = trim($_POST['last_name'] ?? '');
$employee_id = trim($_POST['employee_id'] ?? '');

// Handle departments JSON for higher roles
$departmentsJson = $_POST['departments'] ?? null;
$departments = [];
$primaryDeptId = null;
if ($departmentsJson) {
    $departments = json_decode($departmentsJson, true);
    foreach ($departments as $d) {
        if (!empty($d['primary'])) {
            $primaryDeptId = $d['id'];
            break;
        }
    }
}

// Handle profile image upload
$profile_image_path = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['profile_image']['name']));
    $target = $uploadDir . $fileName;

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $mime = mime_content_type($_FILES['profile_image']['tmp_name']);

    if (!in_array($mime, $allowed_types)) {
        echo json_encode(["error" => "Invalid image type."]);
        exit;
    }

    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
        echo json_encode(["error" => "Failed to save uploaded image."]);
        exit;
    }

    $profile_image_path = "uploads/" . $fileName;
}

// If role = user → only allow image update
if ($userRole === 'user') {
    if ($profile_image_path === null) {
        echo json_encode(["error" => "Only profile image updates are allowed."]);
        exit;
    }

    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $profile_image_path, $userId);
    if ($stmt->execute()) {
        $_SESSION['profile_image'] = $profile_image_path;
        echo json_encode([
            "success" => "Photo updated successfully.",
            "profile_image" => $profile_image_path
        ]);
    } else {
        echo json_encode(["error" => "Error updating photo."]);
    }
    exit;
}

// Higher roles → validate first/last name
if (!$first_name || !$last_name) {
    echo json_encode(["error" => "First and last name required"]);
    exit;
}

// Update user info (with optional profile image)
if ($profile_image_path) {
    $sql = "UPDATE users
            SET first_name=?, middle_name=?, last_name=?, employee_id=?, profile_image=?
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $middle_name, $last_name, $employee_id, $profile_image_path, $userId);
} else {
    $sql = "UPDATE users
            SET first_name=?, middle_name=?, last_name=?, employee_id=?
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $first_name, $middle_name, $last_name, $employee_id, $userId);
}

if (!$stmt->execute()) {
    echo json_encode(["error" => "Error updating profile."]);
    exit;
}

// Update user's departments if provided
if ($departments) {
    // Clear existing assignments
    $conn->query("DELETE FROM user_departments WHERE user_id = $userId");

    $stmt = $conn->prepare("INSERT INTO user_departments (user_id, department_id, is_primary) VALUES (?, ?, ?)");
    foreach ($departments as $d) {
        $isPrimary = !empty($d['primary']) ? 1 : 0;
        $stmt->bind_param("iii", $userId, $d['id'], $isPrimary);
        $stmt->execute();
    }
}

// Refresh session values
$_SESSION['first_name']  = $first_name;
$_SESSION['middle_name'] = $middle_name;
$_SESSION['last_name']   = $last_name;
if ($profile_image_path) $_SESSION['profile_image'] = $profile_image_path;

$full_name = trim($first_name . ' ' . ($middle_name ? substr($middle_name, 0, 1) . '. ' : '') . $last_name);
$_SESSION['name'] = $full_name;

echo json_encode([
    "success" => "Profile updated successfully.",
    "name" => $full_name,
    "profile_image" => $_SESSION['profile_image'] ?? null
]);
