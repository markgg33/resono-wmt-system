<?php
//FOR LOGIN
header('Content-Type: application/json');
session_start();
require 'connection_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare statement to find user
    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Store user session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Update is_online (optional)
            $conn->query("UPDATE users SET is_online = 1 WHERE id = {$user['id']}");

            // Determine redirect based on role
            $adminRoles = ['admin', 'executive', 'hr'];

            if (in_array($user['role'], $adminRoles)) {
                echo json_encode(['success' => true, 'redirect' => 'admin-dashboard.php']);
            } else if ($user['role'] === 'user') {
                echo json_encode(['success' => true, 'redirect' => 'user-dashboard.php']);
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
