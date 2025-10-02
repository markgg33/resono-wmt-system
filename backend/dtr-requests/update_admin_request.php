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
$field = $_POST['field'] ?? null;
$reason = $_POST['reason'] ?? null;
$recipient_id = $_POST['recipient_id'] ?? null;

// Extract the new value depending on field
$new_value = null;
if ($field === "start_time") {
    $new_value = $_POST['new_start_time'] ?? null;
} elseif ($field === "end_time") {
    $new_value = $_POST['new_end_time'] ?? null;
}

if (!$id || !$field || !$reason || !$new_value) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Verify ownership and pending status
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id=? AND user_id=? AND status='Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$res = $stmt->get_result();
$amend = $res->fetch_assoc();
$stmt->close();

if (!$amend) {
    echo json_encode(["status" => "error", "message" => "Request not found or not editable"]);
    exit;
}

// Update the record (always update new_value column)
$stmt = $conn->prepare("UPDATE dtr_amendments SET new_value=?, reason=?, recipient_id=? WHERE id=?");
$stmt->bind_param("ssii", $new_value, $reason, $recipient_id, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Request updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update request"]);
}

$stmt->close();
$conn->close();
