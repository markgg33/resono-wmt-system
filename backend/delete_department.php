<?php
require_once __DIR__ . '/connection_db.php';
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['id'])) {
    echo json_encode(["error" => "Invalid department ID"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM departments WHERE id=?");
$stmt->bind_param("i", $data['id']);

if ($stmt->execute()) {
    echo json_encode(["success" => "Department deleted"]);
} else {
    echo json_encode(["error" => $conn->error]);
}
$stmt->close();
