<?php
require 'connection_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id   = trim($_POST['employee_id'] ?? '');
    $first_name    = trim($_POST['first_name']);
    $middle_name   = trim($_POST['middle_name'] ?? '');
    $last_name     = trim($_POST['last_name']);
    $email         = trim($_POST['email']);
    $password      = $_POST['password'];
    $role          = $_POST['role'];
    $department_id = ($role === 'user' && !empty($_POST['department_id']))
        ? intval($_POST['department_id'])
        : null;

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        echo "<script>alert('Please fill out all required fields.'); window.history.back();</script>";
        exit;
    }

    // Handle profile image upload
    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = __DIR__ . "/../uploads/"; // absolute folder
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // create if not exists
        }

        $fileName   = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFile = $targetDir . $fileName;

        // Allow only image types
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($_FILES['profile_image']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                // Save web-accessible relative path
                $profile_image = "uploads/" . $fileName;
            }
        }
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users 
        (employee_id, first_name, middle_name, last_name, email, password, role, department_id, profile_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssssssis",
        $employee_id,
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $hashedPassword,
        $role,
        $department_id,
        $profile_image
    );

    if ($stmt->execute()) {
        echo "<script>alert('User added successfully.'); window.location.href='../dashboards/admin-dashboard.php';</script>";
    } else {
        echo "<script>alert('Error adding user: possibly duplicate email.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}
