<?php
session_start();
require_once "connection_db.php";

header("Content-Type: application/json");

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'], $data['volume_remark'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

$id = intval($data['id']);
$volume = (float)$data['volume_remark']; // allow decimals

$stmt = $conn->prepare("UPDATE task_logs SET volume_remark = ? WHERE id = ?");
$stmt->bind_param("di", $volume, $id); // d = double/float, i = int

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "id" => $id,
        "volume_remark" => $volume
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$stmt->close();
$conn->close();
