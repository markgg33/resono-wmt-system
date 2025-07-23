<?php
require __DIR__ . '/connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$remarks = $data['remarks'] ?? '';

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

$stmt = $conn->prepare("UPDATE task_logs SET remarks = ? WHERE id = ?");
$stmt->bind_param("si", $remarks, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
