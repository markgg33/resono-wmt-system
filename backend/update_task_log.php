<?php
file_put_contents("debug_update_calls.txt", json_encode($_POST) . PHP_EOL, FILE_APPEND);
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$end_time = $data['end_time'] ?? null;
$duration = $data['duration'] ?? null;

if (!$id || !$end_time || !$duration) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

$stmt = $conn->prepare("UPDATE task_logs SET end_time = ?, total_duration = ? WHERE id = ?");
$stmt->bind_param("ssi", $end_time, $duration, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
