<?php
session_start();
require_once "connection_db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

$id = (int)$data['id'];
$volume = $data['volume_remark'];

// Handle empty/null
if ($volume === null || $volume === "") {
    $stmt = $conn->prepare("UPDATE task_logs SET volume_remark = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
} else {
    if (!is_numeric($volume)) {
        echo json_encode(["status" => "error", "message" => "Volume must be numeric or empty"]);
        exit;
    }
    $volume = (float)$volume;
    $stmt = $conn->prepare("UPDATE task_logs SET volume_remark = ? WHERE id = ?");
    $stmt->bind_param("si", $volume, $id);
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "id" => $id,
        "volume_remark" => ($volume === "" ? null : $volume)
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
