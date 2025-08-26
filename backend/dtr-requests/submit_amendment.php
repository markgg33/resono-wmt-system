<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$logId = $_POST['log_id'] ?? null;
$field = $_POST['field'] ?? null;
$oldValue = $_POST['old_value'] ?? null;
$newValue = $_POST['new_value'] ?? null;
$reason = $_POST['reason'] ?? null;
$recipientId = $_POST['recipient_id'] ?? ($POST['recipient']) ?? null;

if (!$logId || !$field || !$oldValue || !$newValue || !$reason) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

if (!$recipientId) {
    echo json_encode(["status" => "error", "message" => "Recipient required"]);
    exit;
}

// Generate unique UID
function generateRequestUid($length = 12)
{
    return strtoupper(bin2hex(random_bytes($length / 2)));
}
$requestUid = "REQ-" . generateRequestUid();

// Insert into dtr_amendments table
$stmt = $conn->prepare("
    INSERT INTO dtr_amendments 
    (request_uid, user_id, log_id, field, old_value, new_value, reason, recipient_id, status, requested_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
");
$stmt->bind_param("siissssi", $requestUid, $userId, $logId, $field, $oldValue, $newValue, $reason, $recipientId);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment request submitted"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
