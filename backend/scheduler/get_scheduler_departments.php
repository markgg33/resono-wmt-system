<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit;
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$departments = [];

if (in_array($role, ['admin', 'executive', 'hr'])) {

    // Full access
    $result = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
} else if ($role === 'supervisor') {

    // Only departments assigned to supervisor
    $stmt = $conn->prepare("
        SELECT d.id, d.name
        FROM user_departments ud
        INNER JOIN departments d ON ud.department_id = d.id
        WHERE ud.user_id = ?
        ORDER BY d.name ASC
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $departments[] = $row;
    }

    $stmt->close();
}

$conn->close();

echo json_encode([
    "success" => true,
    "departments" => $departments
]);
