<?php
//WORKING VERSION
/*session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr', 'user', 'supervisor'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    http_response_code(403);
    exit;
}

$sql = "SELECT id, name, description, status FROM values_list ORDER BY name";
$result = $conn->query($sql);

echo json_encode($result->fetch_all(MYSQLI_ASSOC));*/

// WITH EXTERNAL ACCESS VERSION
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

// ✅ allow access if internal OR external
$allowedRoles = ['admin', 'executive', 'hr', 'user', 'supervisor'];

if (isset($_SESSION['role']) && !in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    exit;
}

// 🔥 external users (no session) are allowed

$sql = "SELECT id, name, description, status FROM values_list WHERE status = 'active' ORDER BY name";
$result = $conn->query($sql);

echo json_encode($result->fetch_all(MYSQLI_ASSOC));