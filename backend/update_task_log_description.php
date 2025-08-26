<?php
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$logId = $data['log_id'] ?? null;
$workModeId = $data['work_mode_id'] ?? null;
$taskDescriptionId = $data['task_description_id'] ?? null;

if (!$logId || !$workModeId || !$taskDescriptionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$stmt = $conn->prepare("UPDATE task_logs SET work_mode_id = ?, task_description_id = ? WHERE id = ?");
$stmt->bind_param("iii", $workModeId, $taskDescriptionId, $logId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
}

$stmt->close();
$conn->close();
    