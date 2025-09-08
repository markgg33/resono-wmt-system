<?php
require_once __DIR__ . '/connection_db.php';
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['id']) || empty($data['name'])) {
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$stmt = $conn->prepare("UPDATE departments SET name=? WHERE id=?");
$stmt->bind_param("si", $data['name'], $data['id']);

if ($stmt->execute()) {
    echo json_encode(["success" => "Department updated"]);
} else {
    echo json_encode(["error" => $conn->error]);
}
$stmt->close();
