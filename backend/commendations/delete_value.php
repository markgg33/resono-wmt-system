<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    http_response_code(403);
    exit;
}

$id = intval($_POST['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM values_list WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['status' => 'success']);
