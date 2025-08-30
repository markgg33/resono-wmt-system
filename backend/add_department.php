<?php
require_once __DIR__ . '/connection_db.php';
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['name'])) {
    echo json_encode(["error" => "Department name required"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
$stmt->bind_param("s", $data['name']);

if ($stmt->execute()) {
    echo json_encode(["success" => "Department added"]);
} else {
    echo json_encode(["error" => $conn->error]);
}
$stmt->close();
