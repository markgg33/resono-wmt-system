<?php
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$status = $data['is_active'] ?? null;

if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
UPDATE billing_categories
SET is_active = ?
WHERE id = ?
");

$stmt->bind_param("ii", $status, $id);

$success = $stmt->execute();

echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
