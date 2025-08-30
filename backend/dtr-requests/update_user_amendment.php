<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

function jsonResponse($status, $message)
{
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) jsonResponse("error", "Not logged in");

$id = $_POST['id'] ?? null;
$field = $_POST['field'] ?? null;
$newValue = $_POST['new_value'] ?? null;
$reason = $_POST['reason'] ?? null;
$recipientId = $_POST['recipient_id'] ?? null;

if (!$id || !$field || !$newValue || !$reason) jsonResponse("error", "Missing required fields");

// Validate ownership and pending status
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id=? AND user_id=? AND status='Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$result = $stmt->get_result();
$amendment = $result->fetch_assoc();
$stmt->close();

if (!$amendment) jsonResponse("error", "Request not found or not editable");

// Update amendment
$stmt = $conn->prepare("
  UPDATE dtr_amendments
  SET field = ?, new_value = ?, reason = ?, recipient_id = ?
  WHERE id = ? AND user_id = ?
");
$stmt->bind_param("sssiii", $field, $newValue, $reason, $recipientId, $id, $userId);
if ($stmt->execute()) {
    jsonResponse("success", "Request updated successfully");
} else {
    jsonResponse("error", "Update failed");
}
$stmt->close();
