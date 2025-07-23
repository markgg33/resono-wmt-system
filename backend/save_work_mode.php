<?php
require 'connection_db.php'; // Adjust path if needed
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workModeName = trim($_POST['work_mode'] ?? '');
    $tasks = $_POST['tasks'] ?? [];

    if (empty($workModeName) || empty($tasks)) {
        echo "<script>alert('Work mode or tasks missing.'); window.history.back();</script>";
        exit;
    }

    // Insert work mode
    $stmt = $conn->prepare("INSERT INTO work_modes (name) VALUES (?)");
    $stmt->bind_param("s", $workModeName);
    if (!$stmt->execute()) {
        echo "<script>alert('Failed to save work mode.'); window.history.back();</script>";
        exit;
    }

    $workModeId = $stmt->insert_id;
    $stmt->close();

    // Insert task descriptions
    $taskStmt = $conn->prepare("INSERT INTO task_descriptions (work_mode_id, description) VALUES (?, ?)");
    foreach ($tasks as $task) {
        $desc = trim($task);
        if (!empty($desc)) {
            $taskStmt->bind_param("is", $workModeId, $desc);
            $taskStmt->execute();
        }
    }
    $taskStmt->close();

    echo "<script>alert('Work mode and tasks saved successfully!'); window.location.href='admin-dashboard.php';</script>";
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
