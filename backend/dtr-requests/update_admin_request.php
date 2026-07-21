<?php

//WORKING VERSION
/*
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

// Update the record (update field + new_value + reason + recipient_id)
$stmt = $conn->prepare("UPDATE dtr_amendments 
    SET field=?, new_value=?, reason=?, recipient_id=? 
    WHERE id=?");
$stmt->bind_param("sssii", $field, $new_value, $reason, $recipient_id, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Request updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update request"]);
}

$stmt->close();
$conn->close();*/

//WORKING VERSION FOR THE NEW LOGIC
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit;
}

$id            = $_POST['id'] ?? null;
$new_date      = trim($_POST['new_date'] ?? '');
$new_start_time = trim($_POST['new_start_time'] ?? '');
$reason        = trim($_POST['reason'] ?? '');
$recipient_id  = $_POST['recipient_id'] ?? null;

if (!$id || !$new_start_time || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Fetch old values
$stmt = $conn->prepare("SELECT old_value FROM dtr_amendments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Request not found."]);
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

// Build new value string (replaced comma with a space)
$new_value = !empty($new_date)
    ? "{$new_date} {$new_start_time}"
    : $new_start_time;

// Update amendment
$stmt = $conn->prepare("
  UPDATE dtr_amendments 
  SET new_value=?, reason=?, recipient_id=?
  WHERE id=? AND status='Pending'
");
$stmt->bind_param("ssii", $new_value, $reason, $recipient_id, $id);


if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment updated successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update amendment."]);
}

$stmt->close();
$conn->close();
