<?php
require 'connection_db.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');

if (!$id || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Check for duplicate name
$check = $conn->prepare("SELECT id FROM work_modes WHERE LOWER(name) = LOWER(?) AND id != ?");
$check->bind_param("si", $name, $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'duplicate' => true, 'message' => 'Work mode already exists.']);
    exit;
}

$stmt = $conn->prepare("UPDATE work_modes SET name = ? WHERE id = ?");
$stmt->bind_param("si", $name, $id);
$stmt->execute();

echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
