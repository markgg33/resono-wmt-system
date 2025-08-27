<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$id = $_POST['id'] ?? null;
$newValue = $_POST['new_value'] ?? null;
$reason = $_POST['reason'] ?? null;

if (!$id || !$newValue || !$reason) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Make sure the request belongs to the logged-in user and is still Pending
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id = ? AND user_id = ? AND status = 'Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$result = $stmt->get_result();
$amendment = $result->fetch_assoc();
$stmt->close();

if (!$amendment) {
    echo json_encode(["status" => "error", "message" => "Request not found or not editable"]);
    exit;
}

// Update amendment
$stmt = $conn->prepare("UPDATE dtr_amendments SET new_value = ?, reason = ? WHERE id = ?");
$stmt->bind_param("ssi", $newValue, $reason, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Request updated"]);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed"]);
}
$stmt->close();
