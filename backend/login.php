<?php
// FOR LOGIN
header('Content-Type: application/json');
session_start();
require 'connection_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepare statement to find user (include status!)
    $stmt = $conn->prepare("
        SELECT id, first_name, middle_name, last_name, email, password, role, status, profile_image
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        // Block inactive accounts before verifying password/session
        if (isset($user['status']) && strtolower($user['status']) !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Account is inactive. Please contact an administrator.']);
            exit;
        }

        if (password_verify($password, $user['password'])) {
            // Store user session
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['first_name']  = $user['first_name'];
            $_SESSION['middle_name'] = $user['middle_name'];
            $_SESSION['last_name']   = $user['last_name'];
            $_SESSION['email']       = $user['email'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image']
                ? $user['profile_image']
                : 'assets/default-avatar.jpg';

            // Display name
            $full_name = trim(
                $user['first_name'] . ' ' .
                    ($user['middle_name'] ? substr($user['middle_name'], 0, 1) . '. ' : '') .
                    $user['last_name']
            );
            $_SESSION['name'] = $full_name;

            // Mark online , need to set to 0 if ever tab was closed accidentally
            $conn->query("UPDATE users SET is_online = 1 WHERE id = {$user['id']}");
            // Role-based redirect
            $adminRoles = ['admin', 'executive', 'hr', 'supervisor']; //Removed client to adminRoles (unnecessary)
            if (in_array($user['role'], $adminRoles, true)) {
                echo json_encode(['success' => true, 'redirect' => 'dashboards/admin-dashboard.php']);
            } elseif ($user['role'] === 'user') {
                echo json_encode(['success' => true, 'redirect' => 'dashboards/user-dashboard.php']);
            } elseif ($user['role'] === 'client') {
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
