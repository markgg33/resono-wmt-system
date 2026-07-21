<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$role   = $_SESSION['role'] ?? 'user';

$payload = json_decode(file_get_contents("php://input"), true);
$requestCode = trim($payload['request_code'] ?? '');
$remarks = trim($payload['remarks'] ?? '');

if ($requestCode === '') {
    echo json_encode(["status" => "error", "message" => "Missing request_code"]);
    exit;
}

$q = "SELECT * FROM commendations WHERE request_code=? LIMIT 1";
$stmt = $conn->prepare($q);
$stmt->bind_param("s", $requestCode);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Not found"]);
    exit;
}

$isOwner = ((int)$row['from_user_id'] === (int)$userId);
$isFinal = in_array($row['status'], ['approved', 'rejected']);
if ($isFinal) {
    echo json_encode(["status" => "error", "message" => "Already finalized"]);
    exit;
}

$canApprove = false;
if (in_array($role, ['admin', 'executive'])) $canApprove = true;
else if ($role === 'hr' && !$isOwner) $canApprove = true;

if (!$canApprove) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$u = "UPDATE commendations
      SET status='rejected', checked_by=?, remarks=?
      WHERE request_code=? AND status='pending'";
$st = $conn->prepare($u);
$st->bind_param("iss", $userId, $remarks, $requestCode);
$st->execute();
$st->close();

echo json_encode(["status" => "success"]);
