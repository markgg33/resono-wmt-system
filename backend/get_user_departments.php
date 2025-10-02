<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];
$role   = strtolower($_SESSION['role']); // normalize role case

$departments = [];

if (in_array($role, ['admin', 'executive', 'hr'])) {
    // Admins, Executives & HR see all
    $sql = "SELECT id, name FROM departments ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
} elseif (in_array($role, ['client', 'supervisor'])) {
    // Clients & Supervisors → only assigned departments
    $sql = "
        SELECT d.id, d.name
        FROM user_departments ud
        INNER JOIN departments d ON d.id = ud.department_id
        WHERE ud.user_id = ?
        ORDER BY d.name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
} else {
    // Regular user, or role not allowed → no departments
    $departments = [];
}

echo json_encode($departments);
