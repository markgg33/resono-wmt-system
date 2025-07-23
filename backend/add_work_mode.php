<?php
require 'connection_db.php';
header('Content-Type: application/json');

$workModeName = trim($_POST['work_mode_name'] ?? '');

if (empty($workModeName)) {
    echo json_encode(['success' => false, 'message' => 'Work mode name is required.']);
    exit;
}

// Check for duplicates (case-insensitive)
$check = $conn->prepare("SELECT id FROM work_modes WHERE LOWER(name) = LOWER(?)");
$check->bind_param("s", $workModeName);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'duplicate' => true, 'message' => 'Duplicate work mode.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO work_modes (name) VALUES (?)");
$stmt->bind_param("s", $workModeName);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to insert work mode.']);
}

$stmt->close();
$conn->close();


//NOW REJECTS DUPLICATES 