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

$id          = $_POST['id'] ?? null;
$field       = $_POST['field'] ?? null;
$reason      = $_POST['reason'] ?? null;
$recipientId = $_POST['recipient_id'] ?? null;

// Extract the new value depending on field
$newValue = null;
if ($field === "start_time") {
    $newValue = $_POST['new_start_time'] ?? null;
} elseif ($field === "end_time") {
    $newValue = $_POST['new_end_time'] ?? null;
} elseif ($field === "date") {
    $newValue = $_POST['new_date'] ?? null;
} else {
    $newValue = $_POST['new_value'] ?? null;
}

if (!$id || !$field || !$reason || !$newValue) {
    jsonResponse("error", "Missing required fields");
}

// Verify ownership and pending status
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id=? AND user_id=? AND status='Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$res = $stmt->get_result();
$amend = $res->fetch_assoc();
$stmt->close();

if (!$amend) jsonResponse("error", "Request not found or not editable");

// Update the record (always update new_value column, like admin)
$stmt = $conn->prepare("UPDATE dtr_amendments SET new_value=?, reason=?, recipient_id=? WHERE id=? AND user_id=?");
$stmt->bind_param("ssiii", $newValue, $reason, $recipientId, $id, $userId);

if ($stmt->execute()) {
    jsonResponse("success", "Request updated successfully");
} else {
    jsonResponse("error", "Failed to update request");
}

$stmt->close();
