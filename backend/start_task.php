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

// ✅ Always use MySQL server time (prevents client-side tampering)
$current_time = null;
$current_date = null;

$res = $conn->query("SELECT NOW() AS current_time, CURDATE() AS current_date");
if ($res && $row = $res->fetch_assoc()) {
    $current_time = $row['current_time'];  // e.g., 2025-08-26 09:15:00
    $current_date = $row['current_date'];  // e.g., 2025-08-26
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to get server time.']);
    exit;
}

// 1. Check for unfinished task today
$stmt = $conn->prepare("
    SELECT id, start_time 
    FROM task_logs 
    WHERE user_id = ? AND end_time IS NULL AND DATE(start_time) = ? 
    ORDER BY start_time DESC 
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $current_date);
$stmt->execute();
$result = $stmt->get_result();
$unfinishedTask = $result->fetch_assoc();
$stmt->close();

// 2. If unfinished task exists, close it
if ($unfinishedTask) {
    $task_id = $unfinishedTask['id'];
    $start_time = new DateTime($unfinishedTask['start_time']);
    $end_time = new DateTime($current_time);

    // Calculate duration in H:i:s
    $interval = $start_time->diff($end_time);
    $duration = $interval->format('%H:%I:%S');

    $stmt = $conn->prepare("UPDATE task_logs SET end_time = ?, total_duration = ? WHERE id = ?");
    $stmt->bind_param("ssi", $current_time, $duration, $task_id);
    $stmt->execute();
    $stmt->close();
}

// 3. Insert new task (ongoing, no end_time yet)
$stmt = $conn->prepare("INSERT INTO task_logs (user_id, work_mode_id, task_description_id, start_time) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $user_id, $work_mode_id, $task_description_id, $current_time);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task started successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database insert error.']);
}
$stmt->close();
$conn->close();
