<?php
require 'connection_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id'] ?? null);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department_id = ($role === 'user' || $role === 'admin') ? ($_POST['department_id'] ?? null) : null;


    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        echo "<script>alert('Please fill out all required fields.'); window.history.back();</script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (employee_id, first_name, middle_name, last_name, email, password, role, department_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $employee_id, $first_name, $middle_name, $last_name, $email, $hashedPassword, $role, $department_id);

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
