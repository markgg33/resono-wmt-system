<?php
require 'connection_db.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$description = trim($_POST['description'] ?? '');
$billing_category_id = $_POST['billing_category_id'] ?? null;
$standard_aht = $_POST['standard_aht'] ?? null;

// ✅ Convert empty string to NULL (fix FK issue)
if ($billing_category_id === "" || $billing_category_id === "null") {
    $billing_category_id = null;
}

if (!$id || $description === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// ✅ Check for duplicates in the same work mode
$check = $conn->prepare("
    SELECT COUNT(*) 
    FROM task_descriptions 
    WHERE LOWER(description) = LOWER(?) AND id != ?
");
$check->bind_param("si", $description, $id);
$check->execute();
$check->bind_result($count);
$check->fetch();
$check->close();

if ($count > 0) {
    echo json_encode(['success' => false, 'duplicate' => true]);
    exit;
}

// ✅ Update the task description
//$stmt = $conn->prepare("UPDATE task_descriptions SET description = ? WHERE id = ?");
/*$stmt = $conn->prepare("UPDATE task_descriptions
SET description = ?, billing_category_id = ?
WHERE id = ?");
//$stmt->bind_param("si", $description, $id);
$stmt->bind_param("sii", $description, $billing_category_id, $id);*/

// ADDED STANDARD AHT
$stmt = $conn->prepare("
 UPDATE task_descriptions
    SET description = ?,
        billing_category_id = ?,
        standard_aht = ?
    WHERE id = ?
");

if ($billing_category_id === null) {
    // Use NULL safely
    $stmt->bind_param("sidi", $description, $billing_category_id, $standard_aht, $id);
} else {
    $stmt->bind_param("sidi", $description, $billing_category_id, $standard_aht, $id);
}
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
$conn->close();
