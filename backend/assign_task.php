<?php
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;

if (!$id || !$task_description_id) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

$stmt = $conn->prepare("UPDATE task_logs SET task_description_id = ? WHERE id = ?");
$stmt->bind_param("ii", $task_description_id, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
