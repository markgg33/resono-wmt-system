<?php
require 'connection_db.php';
header('Content-Type: application/json');

$workModeId = $_GET['work_mode_id'] ?? null;

if (!$workModeId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, description FROM task_descriptions WHERE work_mode_id = ? ORDER BY description ASC");
$stmt->bind_param("i", $workModeId);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);
$stmt->close();
$conn->close();
