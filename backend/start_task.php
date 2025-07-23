<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

// Validate inputs
$user_id = $_SESSION['user_id'] ?? null;
$work_mode_id = $_POST['work_mode_id'] ?? null;
$task_description_id = $_POST['task_description_id'] ?? null;

if (!$user_id || !$work_mode_id || !$task_description_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Get the end_time of the latest task
$previous_end_time = null;
$stmt = $conn->prepare("SELECT end_time FROM task_logs WHERE user_id = ? ORDER BY end_time DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($previous_end_time);
$stmt->fetch();
$stmt->close();

// Set start_time to previous end_time (or now if none)
$start_time = $previous_end_time ?: date("Y-m-d H:i:s");
$end_time = date("Y-m-d H:i:s");

// Calculate duration in minutes
$start = new DateTime($start_time);
$end = new DateTime($end_time);
$interval = $start->diff($end);
$total_minutes = ($interval->h * 60) + $interval->i;
$total_duration = $total_minutes . " min" . ($total_minutes === 1 ? "" : "s");

// Insert new task log
$stmt = $conn->prepare("INSERT INTO task_logs (user_id, work_mode_id, task_description_id, start_time, end_time, total_duration) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiisss", $user_id, $work_mode_id, $task_description_id, $start_time, $end_time, $total_duration);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task logged successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database insert error.']);
}
