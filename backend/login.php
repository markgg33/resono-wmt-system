<?php
// FOR LOGIN
header('Content-Type: application/json');
session_start();
require 'connection_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare statement to find user
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Store user session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['middle_name'] = $user['middle_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Create full name for display purposes
            $full_name = trim($user['first_name'] . ' ' . ($user['middle_name'] ? substr($user['middle_name'], 0, 1) . '. ' : '') . $user['last_name']);
            $_SESSION['name'] = $full_name; // Use this when displaying

            // Update is_online (optional)
            $conn->query("UPDATE users SET is_online = 1 WHERE id = {$user['id']}");

            // Determine redirect based on role
            $adminRoles = ['admin', 'executive', 'hr'];
            if (in_array($user['role'], $adminRoles)) {
                echo json_encode(['success' => true, 'redirect' => 'dashboards/admin-dashboard.php']);
            } else if ($user['role'] === 'user') {
                echo json_encode(['success' => true, 'redirect' => 'dashboards/user-dashboard.php']);
            } else if ($user['role'] === 'client') {
                echo json_encode(['success' => true, 'redirect' => 'dashboards/client-dashboard.php']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized role.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
