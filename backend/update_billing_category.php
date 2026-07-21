<?php
require 'connection_db.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');

if (!$id || $name === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

/* Check duplicate */
$check = $conn->prepare("
SELECT id FROM billing_categories
WHERE LOWER(category_name)=LOWER(?) AND id != ?
");

$check->bind_param("si", $name, $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'duplicate' => true]);
    exit;
}

$stmt = $conn->prepare("
UPDATE billing_categories
SET category_name = ?
WHERE id = ?
");

$stmt->bind_param("si", $name, $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
