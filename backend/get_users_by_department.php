<?php
session_start();
require_once "connection_db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit;
}

$role = $_SESSION['role'];
if (!in_array($role, ["admin", "hr", "executive", "supervisor"])) {
    // Only these roles can list users
    echo json_encode([]);
    exit;
}

if (!isset($_GET['department_id']) || $_GET['department_id'] === "") {
    echo json_encode([]);
    exit;
}

$departmentId = intval($_GET['department_id']);

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS name
        FROM users
        WHERE department_id = ? 
        ORDER BY first_name, last_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $departmentId);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
