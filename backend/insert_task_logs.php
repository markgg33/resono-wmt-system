<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $_SESSION['user_id'] ?? null;
$work_mode_id = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;
$date = $data['date'] ?? null;
$start_time = $data['start_time'] ?? null;
$remarks = $data['remarks'] ?? '';

if (!$user_id || !$work_mode_id || !$task_description_id || !$date || !$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$start_datetime = "$date $start_time"; // Full datetime for start_time

// 🟢 Insert WITHOUT end_time or duration yet
$stmt = $conn->prepare("
    INSERT INTO task_logs 
    (user_id, work_mode_id, task_description_id, date, start_time, remarks) 
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("iiisss", $user_id, $work_mode_id, $task_description_id, $date, $start_datetime, $remarks);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'inserted_id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
