<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$desc = trim($data['description'] ?? '');

if ($name === '') {
    echo json_encode(['status' => 'error', 'message' => 'Value name is required']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO values_list (name, description) VALUES (?, ?)"
);
$stmt->bind_param("ss", $name, $desc);
$stmt->execute();

echo json_encode(['status' => 'success']);
