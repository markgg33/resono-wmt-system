<?php
require "connection_db.php";

$data = json_decode(file_get_contents("php://input"), true);

foreach ($data['tasks'] as $task) {
    $stmt = $conn->prepare("UPDATE task_descriptions SET display_order = ? WHERE id = ?");
    $stmt->bind_param("ii", $task['order'], $task['id']);
    $stmt->execute();
}

echo json_encode(["success" => true]);
