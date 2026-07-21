<?php
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$isActive = intval($data['is_active'] ?? 1);

if (!$id) {
    echo json_encode(["success" => false, "message" => "Invalid task description ID"]);
    exit;
}

$stmt = $conn->prepare("UPDATE task_descriptions SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $isActive, $id);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
$stmt->close();
$conn->close();
