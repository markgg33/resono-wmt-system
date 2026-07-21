<?php
require 'connection_db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}

/* Check if used by tasks */
$check = $conn->prepare("
SELECT id FROM task_descriptions
WHERE billing_category_id = ?
LIMIT 1
");

$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Billing category used by tasks'
    ]);

    exit;
}

$stmt = $conn->prepare("
DELETE FROM billing_categories
WHERE id = ?
");

$stmt->bind_param("i", $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
